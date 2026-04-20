<?php
/**
 * Modelo para gestionar el odontograma de un paciente.
 *
 */
class Odontograma
{
    private PDO $db;

    private int $idMotivoOdontograma;
    private int $idEstatusCitaDiag;
    private int $idProcSinAsignar;
    private ?bool $tieneNumeroPacienteEnTransacciones = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getCatalogos(): array
    {
        return [
            'anomalias' => $this->_fetchAll("
                SELECT id_anomalia_dental AS id,
                       nombre_anomalia    AS nombre
                FROM   anomaliasdentales
                ORDER BY nombre_anomalia
            "),
            'caras' => $this->_fetchAll("
                SELECT id_cara_dental AS id,
                       cara           AS nombre
                FROM   carasdentales
                ORDER BY id_cara_dental
            "),
            'procedimientos' => $this->_fetchAll("
                SELECT p.id_procedimiento    AS id,
                       p.nombre_procedimiento AS nombre,
                       p.precio_base
                FROM   procedimientos p
                JOIN   estatus        s ON s.id_estatus = p.id_estatus
                WHERE  s.estatus = 'Activo'
                ORDER BY p.nombre_procedimiento
            "),
            'estatus' => $this->_fetchAll("
                SELECT id_estatus_hallazgo AS id,
                       estatus_hallazgo    AS nombre
                FROM   estadoshallazgos
                ORDER BY id_estatus_hallazgo
            "),
            'especialistas' => $this->_fetchAll("
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

    /**
     * Retorna registros del odontograma agrupados por pieza FDI.
     * Tolera tanto el esquema nuevo (transaccionesdentales.numero_paciente)
     * como el flujo anterior basado en cita.
     */
    public function getByPaciente(int $numeroPaciente): array
    {
        if ($this->usaNumeroPacienteEnTransacciones()) {
            $stmt = $this->db->prepare("
                SELECT
                    o.id_odontograma,
                    o.numero_posicion,
                    o.id_anomalia_dental,
                    ad.nombre_anomalia,
                    o.id_cara_dental,
                    cd.cara,
                    o.id_estatus_hallazgo,
                    eh.estatus_hallazgo,
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
                    ) AS nombre_especialista
                FROM  odontograma               o
                JOIN  transaccionesdentales     td  ON td.id_transaccion_dental = o.id_transaccion_dental
                JOIN  anomaliasdentales         ad  ON ad.id_anomalia_dental    = o.id_anomalia_dental
                JOIN  carasdentales             cd  ON cd.id_cara_dental        = o.id_cara_dental
                JOIN  estadoshallazgos          eh  ON eh.id_estatus_hallazgo   = o.id_estatus_hallazgo
                LEFT JOIN procedimientos         p  ON p.id_procedimiento       = td.id_procedimiento
                LEFT JOIN cita                   c  ON c.id_cita                = td.id_cita
                LEFT JOIN especialista          esp ON esp.id_especialista      = c.id_especialista
                WHERE (
                    td.numero_paciente = :np1
                    OR c.numero_paciente = :np2
                )
                ORDER BY o.numero_posicion ASC,
                         o.id_odontograma  DESC
            ");
            $stmt->execute([':np1' => $numeroPaciente, ':np2' => $numeroPaciente]);
        } else {
            $stmt = $this->db->prepare("
                SELECT
                    o.id_odontograma,
                    o.numero_posicion,
                    o.id_anomalia_dental,
                    ad.nombre_anomalia,
                    o.id_cara_dental,
                    cd.cara,
                    o.id_estatus_hallazgo,
                    eh.estatus_hallazgo,
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
                    ) AS nombre_especialista
                FROM  odontograma               o
                JOIN  transaccionesdentales     td  ON td.id_transaccion_dental = o.id_transaccion_dental
                JOIN  cita                       c  ON c.id_cita                = td.id_cita
                JOIN  anomaliasdentales         ad  ON ad.id_anomalia_dental    = o.id_anomalia_dental
                JOIN  carasdentales             cd  ON cd.id_cara_dental        = o.id_cara_dental
                JOIN  estadoshallazgos          eh  ON eh.id_estatus_hallazgo   = o.id_estatus_hallazgo
                JOIN  especialista             esp  ON esp.id_especialista      = c.id_especialista
                LEFT JOIN procedimientos         p  ON p.id_procedimiento       = td.id_procedimiento
                WHERE c.numero_paciente = :numero_paciente
                ORDER BY o.numero_posicion ASC,
                         c.fecha_cita     DESC,
                         o.id_odontograma DESC
            ");
            $stmt->execute([':numero_paciente' => $numeroPaciente]);
        }

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $agrupado = [];
        foreach ($filas as $fila) {
            $pieza = (int) $fila['numero_posicion'];
            $agrupado[$pieza][] = $fila;
        }

        return $agrupado;
    }

    public function guardar(array $datos): array
    {
        $numeroPaciente  = (int) ($datos['numero_paciente'] ?? 0);
        $numeroPieza     = (int) ($datos['numero_pieza'] ?? 0);
        $idAnomalia      = (int) ($datos['id_anomalia'] ?? 0);
        $idCaras         = array_filter(array_map('intval', (array) ($datos['id_caras'] ?? [])));
        $idProcedimiento = (int) ($datos['id_procedimiento'] ?? 0);
        $idEstatus       = (int) ($datos['id_estatus_hallazgo'] ?? 1);

        if (!$numeroPaciente || !$numeroPieza || !$idAnomalia) {
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
            if (!$this->usaNumeroPacienteEnTransacciones()) {
                throw new \RuntimeException(
                    'La base de datos no esta actualizada para guardar odontograma sin cita.'
                );
            }

            $stmtTx = $this->db->prepare("
                INSERT INTO transaccionesdentales (id_cita, numero_paciente, id_procedimiento)
                VALUES (NULL, :numero_paciente, :id_procedimiento)
            ");
            $stmtTx->execute([
                ':numero_paciente' => $numeroPaciente,
                ':id_procedimiento' => $idProcedimiento,
            ]);
            $idTransaccion = (int) $this->db->lastInsertId();

            $stmtOd = $this->db->prepare("
                INSERT INTO odontograma
                    (id_cara_dental, id_anomalia_dental,
                     id_transaccion_dental, numero_posicion,
                     id_estatus_hallazgo)
                VALUES
                    (:id_cara, :id_anomalia, :id_transaccion,
                     :numero_posicion, :id_estatus)
            ");

            $idsOdontograma = [];
            foreach ($idCaras as $idCara) {
                $stmtOd->execute([
                    ':id_cara' => $idCara,
                    ':id_anomalia' => $idAnomalia,
                    ':id_transaccion' => $idTransaccion,
                    ':numero_posicion' => $numeroPieza,
                    ':id_estatus' => $idEstatus,
                ]);
                $idsOdontograma[] = (int) $this->db->lastInsertId();
            }

            $this->db->commit();
            return [
                'success' => true,
                'id_transaccion' => $idTransaccion,
                'ids_odontograma' => $idsOdontograma,
            ];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Actualiza solo el id_estatus_hallazgo de un registro de odontograma.
     * Verifica que el registro pertenezca al paciente antes de actualizar.
     */
    public function actualizarEstatus(int $idOdontograma, int $idEstatus, int $numeroPaciente): array
    {
        try {
            if ($this->usaNumeroPacienteEnTransacciones()) {
                $stmt = $this->db->prepare("
                    SELECT o.id_odontograma
                    FROM   odontograma           o
                    JOIN   transaccionesdentales td ON td.id_transaccion_dental = o.id_transaccion_dental
                    LEFT JOIN cita               c  ON c.id_cita                = td.id_cita
                    WHERE  o.id_odontograma = :id
                      AND  (td.numero_paciente = :np1 OR c.numero_paciente = :np2)
                ");
                $stmt->execute([':id' => $idOdontograma, ':np1' => $numeroPaciente, ':np2' => $numeroPaciente]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT o.id_odontograma
                    FROM   odontograma           o
                    JOIN   transaccionesdentales td ON td.id_transaccion_dental = o.id_transaccion_dental
                    JOIN   cita                   c ON c.id_cita                = td.id_cita
                    WHERE  o.id_odontograma  = :id
                      AND  c.numero_paciente = :numero_paciente
                ");
                $stmt->execute([':id' => $idOdontograma, ':numero_paciente' => $numeroPaciente]);
            }

            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Registro no encontrado o sin permiso.'];
            }

            $this->db->prepare("
                UPDATE odontograma
                SET    id_estatus_hallazgo = :estatus
                WHERE  id_odontograma      = :id
            ")->execute([':estatus' => $idEstatus, ':id' => $idOdontograma]);

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Actualiza el procedimiento asociado a la transaccion dental de un hallazgo.
     * Verifica que el registro pertenezca al paciente antes de actualizar.
     */
    public function actualizarProcedimiento(int $idOdontograma, int $idProcedimiento, int $numeroPaciente): array
    {
        try {
            if ($this->usaNumeroPacienteEnTransacciones()) {
                $stmt = $this->db->prepare("
                    SELECT o.id_transaccion_dental
                    FROM   odontograma           o
                    JOIN   transaccionesdentales td ON td.id_transaccion_dental = o.id_transaccion_dental
                    LEFT JOIN cita               c  ON c.id_cita                = td.id_cita
                    WHERE  o.id_odontograma = :id
                      AND  (td.numero_paciente = :np1 OR c.numero_paciente = :np2)
                ");
                $stmt->execute([':id' => $idOdontograma, ':np1' => $numeroPaciente, ':np2' => $numeroPaciente]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT o.id_transaccion_dental
                    FROM   odontograma           o
                    JOIN   transaccionesdentales td ON td.id_transaccion_dental = o.id_transaccion_dental
                    JOIN   cita                   c ON c.id_cita                = td.id_cita
                    WHERE  o.id_odontograma  = :id
                      AND  c.numero_paciente = :numero_paciente
                ");
                $stmt->execute([':id' => $idOdontograma, ':numero_paciente' => $numeroPaciente]);
            }
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$fila) {
                return ['success' => false, 'message' => 'Registro no encontrado o sin permiso.'];
            }

            $stmtProc = $this->db->prepare("
                SELECT id_procedimiento
                FROM   procedimientos
                WHERE  id_procedimiento = :id
                LIMIT 1
            ");
            $stmtProc->execute([':id' => $idProcedimiento]);

            if (!$stmtProc->fetch()) {
                return ['success' => false, 'message' => 'El procedimiento seleccionado no existe.'];
            }

            $this->db->prepare("
                UPDATE transaccionesdentales
                SET    id_procedimiento = :id_procedimiento
                WHERE  id_transaccion_dental = :id_transaccion
            ")->execute([
                ':id_procedimiento' => $idProcedimiento,
                ':id_transaccion' => (int) $fila['id_transaccion_dental'],
            ]);

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function eliminar(int $idOdontograma, int $numeroPaciente): array
    {
        $this->db->beginTransaction();
        try {
            $this->asegurarIdsResueltos();

            if ($this->usaNumeroPacienteEnTransacciones()) {
                $stmt = $this->db->prepare("
                    SELECT o.id_transaccion_dental,
                           td.id_cita
                    FROM   odontograma           o
                    JOIN   transaccionesdentales td ON td.id_transaccion_dental = o.id_transaccion_dental
                    LEFT JOIN cita               c  ON c.id_cita                = td.id_cita
                    WHERE  o.id_odontograma = :id
                      AND  (td.numero_paciente = :np1 OR c.numero_paciente = :np2)
                ");
                $stmt->execute([':id' => $idOdontograma, ':np1' => $numeroPaciente, ':np2' => $numeroPaciente]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT o.id_transaccion_dental,
                           td.id_cita
                    FROM   odontograma           o
                    JOIN   transaccionesdentales td ON td.id_transaccion_dental = o.id_transaccion_dental
                    JOIN   cita                   c ON c.id_cita                = td.id_cita
                    WHERE  o.id_odontograma  = :id
                      AND  c.numero_paciente = :numero_paciente
                ");
                $stmt->execute([':id' => $idOdontograma, ':numero_paciente' => $numeroPaciente]);
            }
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$fila) {
                throw new \RuntimeException('Registro no encontrado o sin permiso.');
            }

            $idTransaccion = (int) $fila['id_transaccion_dental'];
            $idCita = (int) $fila['id_cita'];

            $this->db->prepare("DELETE FROM odontograma WHERE id_odontograma = :id")
                ->execute([':id' => $idOdontograma]);

            $cntOd = (int) $this->db->query(
                "SELECT COUNT(*) FROM odontograma WHERE id_transaccion_dental = $idTransaccion"
            )->fetchColumn();

            if ($cntOd === 0) {
                $this->db->prepare(
                    "DELETE FROM transaccionesdentales WHERE id_transaccion_dental = :id"
                )->execute([':id' => $idTransaccion]);

                $cntTx = (int) $this->db->query(
                    "SELECT COUNT(*) FROM transaccionesdentales WHERE id_cita = $idCita"
                )->fetchColumn();

                if ($cntTx === 0) {
                    $this->db->prepare("
                        DELETE FROM cita
                        WHERE  id_cita            = :id
                          AND  id_motivo_consulta = :motivo
                    ")->execute([':id' => $idCita, ':motivo' => $this->idMotivoOdontograma]);
                }
            }

            $this->db->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function _obtenerOCrearCita(int $numeroPaciente, int $idEspecialista): int
    {
        $this->asegurarIdsResueltos();
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
            ':paciente' => $numeroPaciente,
            ':especialista' => $idEspecialista,
            ':hoy' => $hoy,
            ':motivo' => $this->idMotivoOdontograma,
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
            ':fecha' => $hoy,
            ':hora' => date('H:i:s'),
            ':id_estatus' => $this->idEstatusCitaDiag,
            ':id_motivo' => $this->idMotivoOdontograma,
            ':id_especialista' => $idEspecialista,
            ':numero_paciente' => $numeroPaciente,
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function _resolverIds(): void
    {
        $checks = [
            'idMotivoOdontograma' => [
                'sql' => "SELECT id_motivo_consulta AS id FROM motivoconsulta
                          WHERE motivo_consulta = 'Registro odontograma' LIMIT 1",
                'label' => "motivoconsulta 'Registro odontograma'",
            ],
            'idEstatusCitaDiag' => [
                'sql' => "SELECT id_estatus_cita AS id FROM estadoscita
                          WHERE estatus_cita = 'Registro diagnóstico' LIMIT 1",
                'label' => "estadoscita 'Registro diagnóstico'",
            ],
            'idProcSinAsignar' => [
                'sql' => "SELECT id_procedimiento AS id FROM procedimientos
                          WHERE nombre_procedimiento = 'Sin procedimiento asignado' LIMIT 1",
                'label' => "procedimientos 'Sin procedimiento asignado'",
            ],
        ];

        foreach ($checks as $propiedad => $check) {
            $fila = $this->db->query($check['sql'])->fetch(PDO::FETCH_ASSOC);
            if (!$fila) {
                throw new \RuntimeException(
                    "Odontograma: falta el registro semilla {$check['label']}."
                );
            }
            $this->{$propiedad} = (int) $fila['id'];
        }
    }

    private function _fetchAll(string $sql): array
    {
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function asegurarIdsResueltos(): void
    {
        if (
            isset($this->idMotivoOdontograma) &&
            isset($this->idEstatusCitaDiag) &&
            isset($this->idProcSinAsignar)
        ) {
            return;
        }

        $this->_resolverIds();
    }

    private function usaNumeroPacienteEnTransacciones(): bool
    {
        if ($this->tieneNumeroPacienteEnTransacciones !== null) {
            return $this->tieneNumeroPacienteEnTransacciones;
        }

        $stmt = $this->db->query("SHOW COLUMNS FROM transaccionesdentales LIKE 'numero_paciente'");
        $this->tieneNumeroPacienteEnTransacciones = (bool) $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->tieneNumeroPacienteEnTransacciones;
    }
}
