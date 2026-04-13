<?php
/**
 * Modelo Cita
 *
 * Mapea la tabla Cita
 * con las relaciones a EstadosCita, Paciente,
 * Especialista y MotivoConsulta.
 */
class Cita
{
    private PDO $db;
 
    // IDs reales según tabla EstadosCita en BD
    private const ESTATUS_IDS = [
        'Pendiente'            => 1,
        'Confirmada'           => 2,
        'Reprogramada'         => 3,
        'En curso'             => 4,
        'No asistió'           => 5,
        'Cancelada'            => 6,
        'Atendida'             => 7,
        'Pagada'               => 8,
        'Registro diagnóstico' => 9,
    ];
 
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
 
    // ──────────────────────────────────────────────
    //  CONSULTA helpers
    // ──────────────────────────────────────────────
 
    /**
     * Devuelve todas las citas con joins completos.
     * Filtros opcionales:
     *   fecha, estatus (texto), id_especialista,
     *   mes (1-12), anio (YYYY)
     */
    public function getAll(array $filtros = []): array
    {
        $where = ['1=1'];
        $params = [];
 
        if (!empty($filtros['fecha'])) {
            $where[]              = 'c.fecha_cita = :fecha';
            $params[':fecha']     = $filtros['fecha'];
        }
 
        if (!empty($filtros['estatus'])) {
            $idEstatus = $this->resolverIdEstatus($filtros['estatus']);
            if ($idEstatus) {
                $where[]              = 'c.id_estatus_cita = :id_estatus';
                $params[':id_estatus'] = $idEstatus;
            }
        }
 
        if (!empty($filtros['id_especialista'])) {
            $where[]                  = 'c.id_especialista = :id_esp';
            $params[':id_esp']        = (int) $filtros['id_especialista'];
        }
 
        if (!empty($filtros['mes']) && !empty($filtros['anio'])) {
            $where[]          = 'MONTH(c.fecha_cita) = :mes AND YEAR(c.fecha_cita) = :anio';
            $params[':mes']   = (int) $filtros['mes'];
            $params[':anio']  = (int) $filtros['anio'];
        }
 
        $sql = "
            SELECT
                c.id_cita,
                c.fecha_cita,
                c.hora_inicio,
                c.duracion_aproximada,
                c.paciente_primera_vez,
                c.costo_total,
                c.fecha_registro,
                c.numero_paciente,
                c.id_especialista,
                c.id_motivo_consulta,
                TRIM(CONCAT(p.nombre,' ',p.apellido_paterno,' ',COALESCE(p.apellido_materno,'')))
                    AS nombre_paciente,
                TRIM(CONCAT(e.nombre,' ',e.apellido_paterno))
                    AS nombre_especialista,
                mc.motivo_consulta,
                ec.estatus_cita
            FROM cita c
            INNER JOIN paciente      p  ON p.numero_paciente   = c.numero_paciente
            INNER JOIN especialista  e  ON e.id_especialista   = c.id_especialista
            LEFT  JOIN motivoconsulta mc ON mc.id_motivo_consulta = c.id_motivo_consulta
            LEFT  JOIN estadoscita   ec ON ec.id_estatus_cita  = c.id_estatus_cita
            WHERE " . implode(' AND ', $where) . "
            ORDER BY c.hora_inicio ASC
        ";
 
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 
    /** Obtener una cita por ID con todos los datos de joins */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                c.id_cita,
                c.fecha_cita,
                c.hora_inicio,
                c.duracion_aproximada,
                c.paciente_primera_vez,
                c.costo_total,
                c.fecha_registro,
                c.numero_paciente,
                c.id_especialista,
                c.id_motivo_consulta,
                c.id_estatus_cita,
                TRIM(CONCAT(p.nombre,' ',p.apellido_paterno,' ',COALESCE(p.apellido_materno,'')))
                    AS nombre_paciente,
                TRIM(CONCAT(e.nombre,' ',e.apellido_paterno))
                    AS nombre_especialista,
                mc.motivo_consulta,
                ec.estatus_cita
            FROM cita c
            INNER JOIN paciente      p  ON p.numero_paciente    = c.numero_paciente
            INNER JOIN especialista  e  ON e.id_especialista    = c.id_especialista
            LEFT  JOIN motivoconsulta mc ON mc.id_motivo_consulta = c.id_motivo_consulta
            LEFT  JOIN estadoscita   ec ON ec.id_estatus_cita   = c.id_estatus_cita
            WHERE c.id_cita = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
 
    /**
     * Días del mes que tienen citas.
     * Retorna array de objetos:
     *   { fecha: "YYYY-MM-DD", total: N, pendientes: N, confirmadas: N }
     */
    public function getDiasConCitas(int $mes, int $anio): array
    {
        $stmt = $this->db->prepare("
            SELECT
                DATE_FORMAT(c.fecha_cita, '%Y-%m-%d') AS fecha,
                COUNT(*)                               AS total,
                SUM(ec.estatus_cita = 'Pendiente')     AS pendientes,
                SUM(ec.estatus_cita = 'Confirmada')    AS confirmadas
            FROM cita c
            LEFT JOIN estadoscita ec ON ec.id_estatus_cita = c.id_estatus_cita
            WHERE MONTH(c.fecha_cita) = :mes
              AND YEAR(c.fecha_cita)  = :anio
            GROUP BY c.fecha_cita
            ORDER BY c.fecha_cita
        ");
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 
    // ──────────────────────────────────────────────
    //  ESCRITURA
    // ──────────────────────────────────────────────
 
    /** Crear nueva cita. Retorna el ID insertado o false */
    public function create(array $data)
    {
        $idEstatus = self::ESTATUS_IDS['Pendiente']; // nueva cita siempre Pendiente
        $primeraVez = ($data['tipoPaciente'] ?? '') === 'Primera vez' ? 1 : 0;
 
        $stmt = $this->db->prepare("
            INSERT INTO cita
                (fecha_cita, hora_inicio, paciente_primera_vez,
                 duracion_aproximada, id_estatus_cita, costo_total,
                 id_motivo_consulta, id_especialista, numero_paciente)
            VALUES
                (:fecha_cita, :hora_inicio, :primera_vez,
                 :duracion, :id_estatus, :costo,
                 :id_motivo, :id_especialista, :numero_paciente)
        ");
 
        $ok = $stmt->execute([
            ':fecha_cita'       => $data['fecha_cita'],
            ':hora_inicio'      => $data['hora_inicio'],
            ':primera_vez'      => $primeraVez,
            ':duracion'         => (int)   ($data['duracion_aproximada'] ?? 60),
            ':id_estatus'       => $idEstatus,
            ':costo'            => isset($data['costo_estimado']) && $data['costo_estimado'] !== ''
                                        ? (float) $data['costo_estimado'] : null,
            ':id_motivo'        => (int) $data['id_motivo_consulta'],
            ':id_especialista'  => (int) $data['id_especialista'],
            ':numero_paciente'  => (int) $data['id_paciente'],
        ]);
 
        return $ok ? (int) $this->db->lastInsertId() : false;
    }
 
    /** Actualizar cita existente */
    public function update(int $id, array $data): bool
    {
        $primeraVez = ($data['tipoPaciente'] ?? '') === 'Primera vez' ? 1 : 0;
 
        // Resolver estatus si viene como texto
        $idEstatus = null;
        if (!empty($data['estatus'])) {
            $idEstatus = $this->resolverIdEstatus($data['estatus']);
        }
 
        $sets   = [
            'numero_paciente       = :numero_paciente',
            'id_especialista       = :id_especialista',
            'id_motivo_consulta    = :id_motivo',
            'fecha_cita            = :fecha_cita',
            'hora_inicio           = :hora_inicio',
            'paciente_primera_vez  = :primera_vez',
            'duracion_aproximada   = :duracion',
            'costo_total           = :costo',
        ];
        $params = [
            ':numero_paciente'  => (int) $data['id_paciente'],
            ':id_especialista'  => (int) $data['id_especialista'],
            ':id_motivo'        => (int) $data['id_motivo_consulta'],
            ':fecha_cita'       => $data['fecha_cita'],
            ':hora_inicio'      => $data['hora_inicio'],
            ':primera_vez'      => $primeraVez,
            ':duracion'         => (int) ($data['duracion_aproximada'] ?? 60),
            ':costo'            => isset($data['costo_estimado']) && $data['costo_estimado'] !== ''
                                        ? (float) $data['costo_estimado'] : null,
            ':id'               => $id,
        ];
 
        if ($idEstatus) {
            $sets[]              = 'id_estatus_cita = :id_estatus';
            $params[':id_estatus'] = $idEstatus;
        }
 
        $stmt = $this->db->prepare(
            "UPDATE cita SET " . implode(', ', $sets) . " WHERE id_cita = :id"
        );
        return $stmt->execute($params);
    }
 
    /** Cambiar solo el estatus de una cita */
    public function cambiarEstatus(int $id, string $estatusTexto): bool
    {
        $idEstatus = $this->resolverIdEstatus($estatusTexto);
        if (!$idEstatus) {
            error_log("cambiarEstatus: no se pudo resolver estatus '{$estatusTexto}' para cita {$id}");
            return false;
        }
 
        $stmt = $this->db->prepare(
            "UPDATE cita SET id_estatus_cita = :id_estatus WHERE id_cita = :id"
        );
        $ok = $stmt->execute([':id_estatus' => $idEstatus, ':id' => $id]);
        if (!$ok) {
            error_log("cambiarEstatus: UPDATE falló para cita {$id}: " . implode(' | ', $stmt->errorInfo()));
        }
        return $ok;
    }
 
    /** Eliminar cita (CASCADE elimina TransaccionesDentales, Pagos relacionados) */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Cita WHERE id_cita = :id");
        return $stmt->execute([':id' => $id]);
    }
 
    // ──────────────────────────────────────────────
    //  VALIDACIÓN
    // ──────────────────────────────────────────────
 
    /**
     * Verificar si existe conflicto de horario para el especialista.
     * Considera la duración de la cita para detectar solapamientos.
     * @param int|null $excluirId  ID de cita a excluir (modo edición)
     */
    public function existeConflicto(
        int $idEspecialista,
        string $fecha,
        string $horaInicio,
        int $duracion = 60,
        ?int $excluirId = null
    ): bool {
        $sql = "
            SELECT COUNT(*) FROM cita c
            LEFT JOIN estadoscita ec ON ec.id_estatus_cita = c.id_estatus_cita
            WHERE c.id_especialista = :id_esp
              AND c.fecha_cita      = :fecha
              AND ec.estatus_cita NOT IN ('Cancelada', 'Atendida', 'Pagada', 'No asistió', 'Registro diagnóstico')
              AND (
                  /* Nueva cita empieza dentro de una existente */
                  :hora_inicio < ADDTIME(c.hora_inicio, SEC_TO_TIME(c.duracion_aproximada * 60))
                  AND
                  /* Nueva cita termina después del inicio de la existente */
                  ADDTIME(:hora_inicio2, SEC_TO_TIME(:duracion * 60)) > c.hora_inicio
              )
        ";
        $params = [
            ':id_esp'      => $idEspecialista,
            ':fecha'       => $fecha,
            ':hora_inicio' => $horaInicio,
            ':hora_inicio2'=> $horaInicio,
            ':duracion'    => $duracion,
        ];
 
        if ($excluirId) {
            $sql              .= ' AND c.id_cita != :excluir';
            $params[':excluir'] = $excluirId;
        }
 
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
 
    // ──────────────────────────────────────────────
    //  HELPERS PRIVADOS
    // ──────────────────────────────────────────────
 
    /**
     * Obtener id_estatus_cita por nombre de estatus.
     */
    private function resolverIdEstatus(string $texto): ?int
    {
        $textoLimpio = trim($texto);
 
        // 1. Buscar en el mapa local primero (más rápido)
        if (isset(self::ESTATUS_IDS[$textoLimpio])) {
            return self::ESTATUS_IDS[$textoLimpio];
        }
 
        // 2. Fallback: buscar en BD con TRIM para evitar problemas de espacios
        $stmt = $this->db->prepare(
            "SELECT id_estatus_cita FROM EstadosCita
             WHERE TRIM(estatus_cita) = TRIM(:texto)
             LIMIT 1"
        );
        $stmt->execute([':texto' => $textoLimpio]);
        $row = $stmt->fetchColumn();
 
        if (!$row) {
            error_log("resolverIdEstatus: no se encontró estatus '{$textoLimpio}' en EstadosCita");
            return null;
        }
 
        return (int) $row;
    }
}