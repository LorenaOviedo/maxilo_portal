<?php
/**
 * Odontograma.php — Modelo
 *
 */
class Odontograma
{
    private PDO $db;
 
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CATÁLOGOS
    // ─────────────────────────────────────────────────────────────────────────
 
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
                SELECT p.id_procedimiento     AS id,
                       p.nombre_procedimiento AS nombre,
                       p.precio_base
                FROM   procedimientos p
                JOIN   estatus        s ON s.id_estatus = p.id_estatus
                WHERE  s.estatus = 'Activo'
                ORDER BY p.nombre_procedimiento
            "),
            'estatus'        => $this->_fetchAll("
                SELECT id_estatus_hallazgo AS id,
                       estatus_hallazgo    AS nombre
                FROM   estadoshallazgos
                ORDER BY id_estatus_hallazgo
            "),
            // 'especialistas' eliminado — ya no se usa en el odontograma
        ];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // LECTURA
    // ─────────────────────────────────────────────────────────────────────────
 
    /**
     * Retorna registros del odontograma agrupados por pieza FDI.
     * La verificación de pertenencia se hace por numero_paciente
     * directamente en TransaccionesDentales, sin pasar por Cita.
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
                o.id_estatus_hallazgo,
                eh.estatus_hallazgo,
                td.id_transaccion_dental,
                td.id_procedimiento,
                p.nombre_procedimiento,
                td.fecha_registro AS fecha_cita
            FROM  odontograma               o
            JOIN  transaccionesdentales     td  ON td.id_transaccion_dental = o.id_transaccion_dental
            JOIN  anomaliasdentales         ad  ON ad.id_anomalia_dental    = o.id_anomalia_dental
            JOIN  carasdentales             cd  ON cd.id_cara_dental        = o.id_cara_dental
            JOIN  estadoshallazgos          eh  ON eh.id_estatus_hallazgo   = o.id_estatus_hallazgo
            LEFT JOIN procedimientos         p  ON p.id_procedimiento       = td.id_procedimiento
            WHERE td.numero_paciente = :numero_paciente
            ORDER BY o.numero_posicion       ASC,
                     td.id_transaccion_dental DESC,
                     o.id_odontograma         DESC
        ");
        $stmt->execute([':numero_paciente' => $numeroPaciente]);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
        $agrupado = [];
        foreach ($filas as $fila) {
            $agrupado[(int) $fila['numero_posicion']][] = $fila;
        }
        return $agrupado;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // ESCRITURA — guardar
    // ─────────────────────────────────────────────────────────────────────────
 
    public function guardar(array $datos): array
    {
        $numeroPaciente  = (int) ($datos['numero_paciente']     ?? 0);
        $numeroPieza     = (int) ($datos['numero_pieza']        ?? 0);
        $idAnomalia      = (int) ($datos['id_anomalia']         ?? 0);
        $idCaras         = array_filter(array_map('intval', (array) ($datos['id_caras'] ?? [])));
        $idProcedimiento = (int) ($datos['id_procedimiento']    ?? 0);
        $idEstatus       = (int) ($datos['id_estatus_hallazgo'] ?? 1);
 
        if (!$numeroPaciente || !$numeroPieza || !$idAnomalia)
            return ['success' => false, 'message' => 'Faltan campos obligatorios.'];
        if (empty($idCaras))
            return ['success' => false, 'message' => 'Se requiere al menos una cara dental.'];
        if (!$idProcedimiento)
            return ['success' => false, 'message' => 'El procedimiento es obligatorio.'];
 
        $this->db->beginTransaction();
        try {
            // id_cita = NULL — el odontograma ya no genera citas
            $stmtTx = $this->db->prepare("
                INSERT INTO transaccionesdentales
                    (id_cita, numero_paciente, id_procedimiento)
                VALUES
                    (NULL, :numero_paciente, :id_procedimiento)
            ");
            $stmtTx->execute([
                ':numero_paciente'  => $numeroPaciente,
                ':id_procedimiento' => $idProcedimiento,
            ]);
            $idTransaccion = (int) $this->db->lastInsertId();
 
            $stmtOd = $this->db->prepare("
                INSERT INTO odontograma
                    (id_cara_dental, id_anomalia_dental,
                     id_transaccion_dental, numero_posicion, id_estatus_hallazgo)
                VALUES
                    (:id_cara, :id_anomalia, :id_transaccion,
                     :numero_posicion, :id_estatus)
            ");
 
            $idsOdontograma = [];
            foreach ($idCaras as $idCara) {
                $stmtOd->execute([
                    ':id_cara'         => $idCara,
                    ':id_anomalia'     => $idAnomalia,
                    ':id_transaccion'  => $idTransaccion,
                    ':numero_posicion' => $numeroPieza,
                    ':id_estatus'      => $idEstatus,
                ]);
                $idsOdontograma[] = (int) $this->db->lastInsertId();
            }
 
            $this->db->commit();
            return [
                'success'         => true,
                'id_transaccion'  => $idTransaccion,
                'ids_odontograma' => $idsOdontograma,
            ];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // ESCRITURA — actualizar estatus del hallazgo
    // ─────────────────────────────────────────────────────────────────────────
 
    public function actualizarEstatus(int $idOdontograma, int $idEstatus, int $numeroPaciente): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT o.id_odontograma
                FROM   odontograma           o
                JOIN   transaccionesdentales td ON td.id_transaccion_dental = o.id_transaccion_dental
                WHERE  o.id_odontograma    = :id
                  AND  td.numero_paciente  = :numero_paciente
            ");
            $stmt->execute([':id' => $idOdontograma, ':numero_paciente' => $numeroPaciente]);
 
            if (!$stmt->fetch())
                return ['success' => false, 'message' => 'Registro no encontrado o sin permiso.'];
 
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
 
    // ─────────────────────────────────────────────────────────────────────────
    // ESCRITURA — actualizar procedimiento
    // ─────────────────────────────────────────────────────────────────────────
 
    public function actualizarProcedimiento(int $idOdontograma, int $idProcedimiento, int $numeroPaciente): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT o.id_transaccion_dental
                FROM   odontograma           o
                JOIN   transaccionesdentales td ON td.id_transaccion_dental = o.id_transaccion_dental
                WHERE  o.id_odontograma    = :id
                  AND  td.numero_paciente  = :numero_paciente
            ");
            $stmt->execute([':id' => $idOdontograma, ':numero_paciente' => $numeroPaciente]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
 
            if (!$fila)
                return ['success' => false, 'message' => 'Registro no encontrado o sin permiso.'];
 
            $stmtProc = $this->db->prepare("
                SELECT id_procedimiento FROM procedimientos
                WHERE  id_procedimiento = :id LIMIT 1
            ");
            $stmtProc->execute([':id' => $idProcedimiento]);
            if (!$stmtProc->fetch())
                return ['success' => false, 'message' => 'El procedimiento seleccionado no existe.'];
 
            $this->db->prepare("
                UPDATE transaccionesdentales
                SET    id_procedimiento      = :id_procedimiento
                WHERE  id_transaccion_dental = :id_transaccion
            ")->execute([
                ':id_procedimiento' => $idProcedimiento,
                ':id_transaccion'   => (int) $fila['id_transaccion_dental'],
            ]);
 
            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // ESCRITURA — eliminar
    // ─────────────────────────────────────────────────────────────────────────
 
    public function eliminar(int $idOdontograma, int $numeroPaciente): array
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                SELECT o.id_transaccion_dental
                FROM   odontograma           o
                JOIN   transaccionesdentales td ON td.id_transaccion_dental = o.id_transaccion_dental
                WHERE  o.id_odontograma    = :id
                  AND  td.numero_paciente  = :numero_paciente
            ");
            $stmt->execute([':id' => $idOdontograma, ':numero_paciente' => $numeroPaciente]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
 
            if (!$fila)
                throw new \RuntimeException('Registro no encontrado o sin permiso.');
 
            $idTransaccion = (int) $fila['id_transaccion_dental'];
 
            $this->db->prepare("DELETE FROM odontograma WHERE id_odontograma = :id")
                     ->execute([':id' => $idOdontograma]);
 
            // Limpiar transacción si quedó huérfana
            $cnt = (int) $this->db->query(
                "SELECT COUNT(*) FROM odontograma WHERE id_transaccion_dental = $idTransaccion"
            )->fetchColumn();
 
            if ($cnt === 0) {
                $this->db->prepare(
                    "DELETE FROM transaccionesdentales WHERE id_transaccion_dental = :id"
                )->execute([':id' => $idTransaccion]);
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
 
    private function _fetchAll(string $sql): array
    {
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}