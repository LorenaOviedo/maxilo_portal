<?php
/**
 * Odontograma.php — Modelo
 *
 * Flujo de persistencia (trazable para pagos):
 *   guardar()
 *     ├─ Crea o reutiliza Cita de tipo "Registro odontograma"
 *     │    (agrupada por día + paciente + especialista seleccionado)
 *     ├─ Crea TransaccionesDentales con el procedimiento elegido
 *     │    (o "Sin procedimiento asignado" si viene null)
 *     └─ Inserta N filas en Odontograma — una por cada cara seleccionada
 *
 * Flujo de lectura:
 *   getByPaciente()
 *     └─ JOIN Cita → TransaccionesDentales → Odontograma
 *        Retorna array agrupado por numero_posicion (pieza FDI)
 */
class Odontograma
{
    private PDO $db;

    // IDs de registros semilla (se resuelven en el constructor)
    private int $idMotivoOdontograma;  // MotivoConsulta  "Registro odontograma"
    private int $idEstatusCitaDiag;    // EstadosCita     "Registro diagnóstico"
    private int $idProcSinAsignar;     // Procedimientos  "Sin procedimiento asignado"

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->_resolverIds();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CATÁLOGOS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna todos los catálogos que necesita el frontend en una sola llamada.
     * Los especialistas se reutilizan del mismo endpoint que PlanTratamiento
     * (get_catalogos_planes), por lo que aquí solo enviamos los propios
     * del odontograma.
     *
     * @return array {
     *   anomalias:      [{id, nombre}],
     *   caras:          [{id, nombre}],
     *   procedimientos: [{id, nombre, precio_base}],
     *   estatus:        [{id, nombre}],
     *   especialistas:  [{id, nombre_completo}]
     * }
     */
    public function getCatalogos(): array
    {
        return [
            'anomalias'      => $this->_fetchAll("
                SELECT id_anomalia_dental AS id,
                       nombre_anomalia    AS nombre
                FROM   anomaliasdentales
                ORDER BY nombre_anomalia
            "),
            'caras'          => $this->_fetchAll("
                SELECT id_cara_dental AS id,
                       cara           AS nombre
                FROM   carasdentales
                ORDER BY id_cara_dental
            "),
            'procedimientos' => $this->_fetchAll("
                SELECT p.id_procedimiento   AS id,
                       p.nombre_procedimiento AS nombre,
                       p.precio_base
                FROM   procedimientos p
                JOIN   estatus        s ON s.id_estatus = p.id_estatus
                WHERE  s.estatus = 'Activo'
                ORDER BY p.nombre_procedimiento
            "),
            'estatus'        => $this->_fetchAll("
                SELECT id_estatus_tratamiento AS id,
                       estatus_tratamiento    AS nombre
                FROM   estadostratamiento
                ORDER BY id_estatus_tratamiento
            "),
            'especialistas'  => $this->_fetchAll("
                SELECT e.id_especialista AS id,
                       CONCAT_WS(' ',
                           e.nombre,
                           e.apellido_paterno,
                           e.apellido_materno
                       ) AS nombre_completo
                FROM   especialista e
                JOIN   estatus      s ON s.id_estatus = e.id_estatus
                WHERE  s.estatus = 'Activo'
                ORDER BY e.apellido_paterno, e.nombre
            "),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LECTURA
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Carga todos los registros de odontograma de un paciente.
     *
     * Retorna:
     * {
     *   "11": [
     *     {
     *       id_odontograma, numero_posicion,
     *       id_anomalia_dental, nombre_anomalia,
     *       id_cara_dental, cara,
     *       id_transaccion_dental, id_procedimiento, nombre_procedimiento,
     *       id_cita, fecha_cita,
     *       id_especialista, nombre_especialista,
     *       id_estatus_tratamiento, estatus_tratamiento
     *     }, ...
     *   ], ...
     * }
     *
     * Nota sobre estatus: se lee del PlanTratamiento vinculado (si existe).
     * Si no hay plan, se devuelve "Pendiente" como valor por defecto.
     */
    public function getByPaciente(int $numeroPaciente): array
    {
        $stmt = $this->db->prepare("
            SELECT
                o.id_odontograma,
                o.numero_posicion,
                o.id_anomalia_dental,
                ad.nombre_anomalia,
                o.id_cara_dental,
                cd.cara,
                td.id_transaccion_dental,
                td.id_procedimiento,
                p.nombre_procedimiento,
                c.id_cita,
                c.fecha_cita,
                c.id_especialista,
                CONCAT_WS(' ',
                    esp.nombre,
                    esp.apellido_paterno,
                    esp.apellido_materno
                ) AS nombre_especialista,
                COALESCE(et.id_estatus_tratamiento, 1)       AS id_estatus_tratamiento,
                COALESCE(et.estatus_tratamiento, 'Pendiente') AS estatus_tratamiento
            FROM  odontograma             o
            JOIN  transaccionesdentales   td  ON td.id_transaccion_dental = o.id_transaccion_dental
            JOIN  cita                     c  ON c.id_cita                = td.id_cita
            JOIN  anomaliasdentales       ad  ON ad.id_anomalia_dental    = o.id_anomalia_dental
            JOIN  carasdentales           cd  ON cd.id_cara_dental        = o.id_cara_dental
            JOIN  especialista            esp ON esp.id_especialista      = c.id_especialista
            LEFT JOIN procedimientos       p  ON p.id_procedimiento       = td.id_procedimiento
            LEFT JOIN plantratamiento     pt  ON pt.id_plan_tratamiento   = td.id_plan_tratamiento
            LEFT JOIN estadostratamiento  et  ON et.id_estatus_tratamiento= pt.id_estatus_tratamiento
            WHERE c.numero_paciente = :numero_paciente
            ORDER BY o.numero_posicion ASC,
                     c.fecha_cita     DESC,
                     o.id_odontograma DESC
        ");
        $stmt->execute([':numero_paciente' => $numeroPaciente]);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar por pieza FDI
        $agrupado = [];
        foreach ($filas as $fila) {
            $pieza = (int) $fila['numero_posicion'];
            $agrupado[$pieza][] = $fila;
        }

        return $agrupado;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ESCRITURA — guardar hallazgo
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Persiste un hallazgo odontológico de forma completamente trazable.
     *
     * @param array $datos {
     *   numero_paciente  int      requerido
     *   id_especialista  int      requerido  (elegido en el formulario del odontograma)
     *   numero_pieza     int      requerido  (posición FDI)
     *   id_anomalia      int      requerido  (FK AnomaliasDentales)
     *   id_caras         int[]    requerido  (FK CarasDentales — mínimo 1)
     *   id_procedimiento int      requerido  (FK Procedimientos)
     * }
     * @return array { success, id_cita, id_transaccion, ids_odontograma[]|message }
     */
    public function guardar(array $datos): array
    {
        // ── Validar entrada ──────────────────────────────────────────────────
        $numeroPaciente  = (int) ($datos['numero_paciente']  ?? 0);
        $idEspecialista  = (int) ($datos['id_especialista']  ?? 0);
        $numeroPieza     = (int) ($datos['numero_pieza']     ?? 0);
        $idAnomalia      = (int) ($datos['id_anomalia']      ?? 0);
        $idCaras         = array_filter(array_map('intval', (array) ($datos['id_caras'] ?? [])));
        $idProcedimiento = (int) ($datos['id_procedimiento'] ?? 0);

        if (!$numeroPaciente || !$idEspecialista || !$numeroPieza || !$idAnomalia) {
            return ['success' => false, 'message' => 'Faltan campos obligatorios.'];
        }
        if (empty($idCaras)) {
            return ['success' => false, 'message' => 'Se requiere al menos una cara dental.'];
        }
        if (!$idProcedimiento) {
            return ['success' => false, 'message' => 'El procedimiento es obligatorio.'];
        }

        $this->db->beginTransaction();
        try {
            // 1 ── Cita: reutilizar la del día o crear una nueva ──────────────
            $idCita = $this->_obtenerOCrearCita($numeroPaciente, $idEspecialista);

            // 2 ── Transacción dental ─────────────────────────────────────────
            $stmtTx = $this->db->prepare("
                INSERT INTO transaccionesdentales (id_cita, id_procedimiento)
                VALUES (:id_cita, :id_procedimiento)
            ");
            $stmtTx->execute([
                ':id_cita'          => $idCita,
                ':id_procedimiento' => $idProcedimiento,
            ]);
            $idTransaccion = (int) $this->db->lastInsertId();

            // 3 ── Un registro en Odontograma por cada cara seleccionada ──────
            $stmtOd = $this->db->prepare("
                INSERT INTO odontograma
                    (id_cara_dental, id_anomalia_dental,
                     id_transaccion_dental, numero_posicion)
                VALUES
                    (:id_cara, :id_anomalia, :id_transaccion, :numero_posicion)
            ");

            $idsOdontograma = [];
            foreach ($idCaras as $idCara) {
                $stmtOd->execute([
                    ':id_cara'         => $idCara,
                    ':id_anomalia'     => $idAnomalia,
                    ':id_transaccion'  => $idTransaccion,
                    ':numero_posicion' => $numeroPieza,
                ]);
                $idsOdontograma[] = (int) $this->db->lastInsertId();
            }

            $this->db->commit();

            return [
                'success'         => true,
                'id_cita'         => $idCita,
                'id_transaccion'  => $idTransaccion,
                'ids_odontograma' => $idsOdontograma,
            ];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ESCRITURA — eliminar hallazgo
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Elimina un registro de Odontograma por su id.
     * Limpia en cascada TransaccionesDentales y Cita si quedan huérfanas,
     * pero solo elimina la Cita si es de tipo "Registro odontograma"
     * (para no borrar citas reales por error).
     *
     * @param int $idOdontograma
     * @param int $numeroPaciente  Verificación de propiedad
     * @return array { success, message? }
     */
    public function eliminar(int $idOdontograma, int $numeroPaciente): array
    {
        $this->db->beginTransaction();
        try {
            // Verificar propiedad y obtener ids relacionados
            $stmt = $this->db->prepare("
                SELECT o.id_transaccion_dental,
                       td.id_cita
                FROM   odontograma             o
                JOIN   transaccionesdentales     td ON td.id_transaccion_dental = o.id_transaccion_dental
                JOIN   cita                     c ON c.id_cita                = td.id_cita
                WHERE  o.id_odontograma  = :id
                  AND  c.numero_paciente = :numero_paciente
            ");
            $stmt->execute([
                ':id'              => $idOdontograma,
                ':numero_paciente' => $numeroPaciente,
            ]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$fila) {
                throw new \RuntimeException('Registro no encontrado o sin permiso.');
            }

            $idTransaccion = (int) $fila['id_transaccion_dental'];
            $idCita        = (int) $fila['id_cita'];

            // Eliminar registro de Odontograma
            $this->db->prepare("DELETE FROM odontograma WHERE id_odontograma = :id")
                     ->execute([':id' => $idOdontograma]);

            // Limpiar TransaccionesDentales si quedó sin hijos
            $cntOd = (int) $this->db->query(
                "SELECT COUNT(*) FROM odontograma
                 WHERE id_transaccion_dental = $idTransaccion"
            )->fetchColumn();

            if ($cntOd === 0) {
                $this->db->prepare(
                    "DELETE FROM transaccionesdentales WHERE id_transaccion_dental = :id"
                )->execute([':id' => $idTransaccion]);

                // Limpiar Cita si quedó sin transacciones Y es de tipo diagnóstico
                $cntTx = (int) $this->db->query(
                    "SELECT COUNT(*) FROM transaccionesdentales WHERE id_cita = $idCita"
                )->fetchColumn();

                if ($cntTx === 0) {
                    $this->db->prepare("
                        DELETE FROM cita
                        WHERE  id_cita             = :id
                          AND  id_motivo_consulta  = :motivo
                    ")->execute([
                        ':id'     => $idCita,
                        ':motivo' => $this->idMotivoOdontograma,
                    ]);
                }
            }

            $this->db->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Busca una Cita de diagnóstico de HOY para el par paciente+especialista.
     * Si no existe, la crea. Así todos los hallazgos del mismo día y
     * especialista quedan bajo una misma cita (limpio en reportes de pagos).
     */
    private function _obtenerOCrearCita(int $numeroPaciente, int $idEspecialista): int
    {
        $hoy = date('Y-m-d');

        $stmt = $this->db->prepare("
            SELECT id_cita FROM cita
            WHERE  numero_paciente    = :paciente
              AND  id_especialista    = :especialista
              AND  fecha_cita         = :hoy
              AND  id_motivo_consulta = :motivo
            LIMIT 1
        ");
        $stmt->execute([
            ':paciente'     => $numeroPaciente,
            ':especialista' => $idEspecialista,
            ':hoy'          => $hoy,
            ':motivo'       => $this->idMotivoOdontograma,
        ]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila) {
            return (int) $fila['id_cita'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO cita
                (fecha_cita, hora_inicio, paciente_primera_vez,
                 id_estatus_cita, id_motivo_consulta,
                 id_especialista, numero_paciente)
            VALUES
                (:fecha, :hora, 0,
                 :id_estatus, :id_motivo,
                 :id_especialista, :numero_paciente)
        ");
        $stmt->execute([
            ':fecha'           => $hoy,
            ':hora'            => date('H:i:s'),
            ':id_estatus'      => $this->idEstatusCitaDiag,
            ':id_motivo'       => $this->idMotivoOdontograma,
            ':id_especialista' => $idEspecialista,
            ':numero_paciente' => $numeroPaciente,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Resuelve los IDs de los registros.
     * Lanza excepción temprana si falta ejecutar 2_odontograma_seeds.sql.
     */
    private function _resolverIds(): void
    {
        $checks = [
            'idMotivoOdontograma' => [
                'sql'   => "SELECT id_motivo_consulta AS id FROM motivoconsulta
                            WHERE motivo_consulta = 'Registro odontograma' LIMIT 1",
                'campo' => 'id',
                'label' => "motivoconsulta 'Registro odontograma'",
            ],
            'idEstatusCitaDiag'   => [
                'sql'   => "SELECT id_estatus_cita AS id FROM estadoscita
                            WHERE estatus_cita = 'Registro diagnóstico' LIMIT 1",
                'campo' => 'id',
                'label' => "estadoscita 'Registro diagnóstico'",
            ],
            'idProcSinAsignar'    => [
                'sql'   => "SELECT id_procedimiento AS id FROM procedimientos
                            WHERE nombre_procedimiento = 'Sin procedimiento asignado' LIMIT 1",
                'campo' => 'id',
                'label' => "procedimientos 'Sin procedimiento asignado'",
            ],
        ];

        foreach ($checks as $propiedad => $check) {
            $fila = $this->db->query($check['sql'])->fetch(PDO::FETCH_ASSOC);
            if (!$fila) {
                throw new \RuntimeException(
                    "Odontograma: falta el registro semilla {$check['label']}. " .
                    "Ejecuta 2_odontograma_seeds.sql"
                );
            }
            $this->{$propiedad} = (int) $fila[$check['campo']];
        }
    }

    /** Ejecuta una query y retorna todas las filas */
    private function _fetchAll(string $sql): array
    {
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}