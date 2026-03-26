<?php
/**
 * Model: Dashboard
 *
 * Consultas para los resúmenes del dashboard de citas.
 */
class Dashboard{
    private PDO $db;
 
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
 
    public function totalCitasHoy(): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM cita c
            INNER JOIN estadoscita ec ON ec.id_estatus_cita = c.id_estatus_cita
            WHERE c.fecha_cita = CURDATE()
              AND ec.estatus_cita NOT IN ('Cancelada', 'No asistió')
        ");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
 
    public function citasHoyPorMotivo(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(mc.motivo_consulta, 'Sin motivo') AS motivo,
                COUNT(*)                                   AS total
            FROM cita c
            LEFT  JOIN motivoconsulta mc ON mc.id_motivo_consulta = c.id_motivo_consulta
            INNER JOIN estadoscita    ec ON ec.id_estatus_cita    = c.id_estatus_cita
            WHERE c.fecha_cita = CURDATE()
              AND ec.estatus_cita NOT IN ('Cancelada', 'No asistió')
            GROUP BY mc.motivo_consulta
            ORDER BY total DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 
    public function citasAtendidas(string $fecha = ''): int
    {
        if ($fecha === '') {
            $fecha = date('Y-m-d', strtotime('-1 day'));
        }
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM cita c
            INNER JOIN estadoscita ec ON ec.id_estatus_cita = c.id_estatus_cita
            WHERE c.fecha_cita    = :fecha
              AND ec.estatus_cita = 'Atendida'
        ");
        $stmt->execute([':fecha' => $fecha]);
        return (int) $stmt->fetchColumn();
    }
 
    public function agendaHoy(int $limite = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT
                CONCAT(p.nombre, ' ', p.apellido_paterno,
                       IF(p.apellido_materno IS NOT NULL AND p.apellido_materno <> '',
                          CONCAT(' ', p.apellido_materno), ''))    AS paciente,
                TIME_FORMAT(c.hora_inicio, '%h:%i %p')             AS hora,
                COALESCE(mc.motivo_consulta, 'Consulta general')   AS motivo,
                ec.estatus_cita                                     AS estatus
            FROM cita c
            INNER JOIN paciente       p  ON p.numero_paciente     = c.numero_paciente
            INNER JOIN estadoscita    ec ON ec.id_estatus_cita    = c.id_estatus_cita
            LEFT  JOIN motivoconsulta mc ON mc.id_motivo_consulta = c.id_motivo_consulta
            WHERE c.fecha_cita    = CURDATE()
              AND ec.estatus_cita NOT IN ('Cancelada', 'No asistió', 'Atendida')
            ORDER BY c.hora_inicio ASC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 
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
 
    public function resumen(): array
    {
        $hoy  = date('Y-m-d');
        $ayer = date('Y-m-d', strtotime('-1 day'));
 
        return [
            'citas_hoy'            => $this->totalCitasHoy(),
            'citas_hoy_motivo'     => $this->citasHoyPorMotivo(),
            'citas_atendidas_hoy'  => $this->citasAtendidas($hoy),
            'citas_atendidas_ayer' => $this->citasAtendidas($ayer),
            'agenda'               => $this->agendaHoy(10),
            'facturas_pendientes'  => $this->facturasPendientes(),
            'fecha_hoy'            => date('d/m/Y'),
        ];
    }
}