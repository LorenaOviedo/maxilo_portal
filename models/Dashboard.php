<?php
/**
 * Model: Dashboard
 *
 * Consultas para los resúmenes del dashboard de citas.
 */
class Dashboard
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ── Citas programadas para hoy (total + desglose por motivo) ──

    /**
     * Total de citas programadas para hoy (cualquier estatus activo).
     */
    public function totalCitasHoy(): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM cita c
            INNER JOIN estadosCita ec ON ec.id_estatus_cita = c.id_estatus_cita
            WHERE c.fecha_cita = CURDATE()
              AND ec.estatus_cita NOT IN ('Cancelada', 'No asistió')
        ");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Desglose de citas de hoy agrupadas por motivo de consulta.
     * Retorna: [['motivo' => '...', 'total' => N], ...]
     */
    public function citasHoyPorMotivo(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(mc.motivo_consulta, 'Sin motivo') AS motivo,
                COUNT(*)                                   AS total
            FROM cita c
            LEFT JOIN motivoConsulta mc ON mc.id_motivo_consulta = c.id_motivo_consulta
            INNER JOIN estadosCita   ec ON ec.id_estatus_cita    = c.id_estatus_cita
            WHERE c.fecha_cita = CURDATE()
              AND ec.estatus_cita NOT IN ('Cancelada', 'No asistió')
            GROUP BY mc.motivo_consulta
            ORDER BY total DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Citas atendidas ───────────────────────────────────────────

    /**
     * Total de citas con estatus "Atendida" en una fecha dada.
     * @param string $fecha  Formato YYYY-MM-DD. Por defecto ayer.
     */
    public function citasAtendidas(string $fecha = ''): int
    {
        if ($fecha === '') {
            $fecha = date('Y-m-d', strtotime('-1 day'));
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM cita c
            INNER JOIN estadosCita ec ON ec.id_estatus_cita = c.id_estatus_cita
            WHERE c.fecha_cita      = :fecha
              AND ec.estatus_cita   = 'Atendida'
        ");
        $stmt->execute([':fecha' => $fecha]);
        return (int) $stmt->fetchColumn();
    }

    // ── Agenda: próximas citas del día (las que faltan por atender) ──

    /**
     * Lista de citas pendientes de hoy ordenadas por hora.
     * Retorna: [['paciente' => '...', 'hora' => 'HH:MM', 'motivo' => '...'], ...]
     *
     * @param int $limite  Máximo de registros a devolver.
     */
    public function agendaHoy(int $limite = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT
                CONCAT(p.nombre, ' ', p.apellido_paterno, ' ', COALESCE(p.apellido_materno,'')) AS paciente,
                TIME_FORMAT(c.hora_inicio, '%h:%i %p')                                          AS hora,
                COALESCE(mc.motivo_consulta, 'Consulta general')                                AS motivo,
                ec.estatus_cita                                                                  AS estatus
            FROM cita c
            INNER JOIN paciente      p  ON p.numero_paciente   = c.numero_paciente
            INNER JOIN estadosCita   ec ON ec.id_estatus_cita  = c.id_estatus_cita
            LEFT  JOIN motivoConsulta mc ON mc.id_motivo_consulta = c.id_motivo_consulta
            WHERE c.fecha_cita    = CURDATE()
              AND ec.estatus_cita NOT IN ('Cancelada', 'No asistió', 'Atendida')
            ORDER BY c.hora_inicio ASC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Facturas pendientes ───────────────────────────────────────

    /**
     * Total de solicitudes de factura con estatus pendiente.
     */
    public function facturasPendientes(): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM solicitudfactura sf
            INNER JOIN estadosfactura ef ON ef.id_estatus_factura = sf.id_estatus_factura
            WHERE ef.estatus_factura = 'Pendiente'
        ");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    // ── Resumen completo (una sola llamada para el dashboard) ─────

    /**
     * Devuelve todas las métricas del dashboard en un solo array.
     * Ideal para una petición AJAX única.
     */
    public function resumen(): array
    {
        $hoy = date('Y-m-d');
        $ayer = date('Y-m-d', strtotime('-1 day'));

        return [
            'citas_hoy' => $this->totalCitasHoy(),
            'citas_hoy_motivo' => $this->citasHoyPorMotivo(),
            'citas_atendidas_hoy' => $this->citasAtendidas($hoy),
            'citas_atendidas_ayer' => $this->citasAtendidas($ayer),
            'agenda' => $this->agendaHoy(10),
            'facturas_pendientes' => $this->facturasPendientes(),
            'fecha_hoy' => date('d/m/Y'),
        ];
    }
}
