<?php
/**
 * Modelo de Motivo de Consulta
 * Tabla: motivoconsulta
 */
class MotivoConsulta
{
    private $conn;
 
    public function __construct($db)
    {
        $this->conn = $db;
    }
 
    // ==================== LECTURA ====================
 
    public function getAll($filtros = [])
    {
        $query = "
            SELECT id_motivo_consulta, motivo_consulta, descripcion
            FROM   motivoconsulta
        ";
 
        $conditions = [];
        $params     = [];
 
        if (!empty($filtros['buscar'])) {
            $termino            = '%' . $filtros['buscar'] . '%';
            $conditions[]       = "(motivo_consulta LIKE :buscar OR descripcion LIKE :buscar2)";
            $params[':buscar']  = $termino;
            $params[':buscar2'] = $termino;
        }
 
        if (!empty($conditions))
            $query .= " WHERE " . implode(' AND ', $conditions);
 
        $query .= " ORDER BY motivo_consulta ASC";
 
        $stmt = $this->conn->prepare($query);
 
        try {
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en MotivoConsulta::getAll: " . $e->getMessage());
            return [];
        }
    }
 
    public function getById($id)
    {
        $stmt = $this->conn->prepare("
            SELECT id_motivo_consulta, motivo_consulta, descripcion
            FROM   motivoconsulta
            WHERE  id_motivo_consulta = :id
            LIMIT 1
        ");
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
 
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log("Error en MotivoConsulta::getById: " . $e->getMessage());
            return false;
        }
    }
 
    // ==================== ESCRITURA ====================
 
    public function create($data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO motivoconsulta (motivo_consulta, descripcion)
            VALUES (:motivo_consulta, :descripcion)
        ");
        $stmt->bindValue(':motivo_consulta', strtoupper(trim($data['motivo_consulta'])));
        $stmt->bindValue(':descripcion',     trim($data['descripcion'] ?? ''));
 
        try {
            return $stmt->execute() ? $this->conn->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error en MotivoConsulta::create: " . $e->getMessage());
            return false;
        }
    }
 
    public function update($id, $data)
    {
        $stmt = $this->conn->prepare("
            UPDATE motivoconsulta
            SET    motivo_consulta = :motivo_consulta,
                   descripcion     = :descripcion
            WHERE  id_motivo_consulta = :id
        ");
        $stmt->bindValue(':motivo_consulta', strtoupper(trim($data['motivo_consulta'])));
        $stmt->bindValue(':descripcion',     trim($data['descripcion'] ?? ''));
        $stmt->bindValue(':id',              (int) $id, PDO::PARAM_INT);
 
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en MotivoConsulta::update: " . $e->getMessage());
            return false;
        }
    }
 
    // ==================== VALIDACIÓN ====================
 
    public function validar($data): ?string
    {
        if (empty(trim($data['motivo_consulta'] ?? '')))
            return 'El nombre del motivo es obligatorio.';
        if (mb_strlen(trim($data['motivo_consulta']), 'UTF-8') > 100)
            return 'El nombre no debe superar 100 caracteres.';
        if (!empty($data['descripcion']) && mb_strlen(trim($data['descripcion']), 'UTF-8') > 200)
            return 'La descripción no debe superar 200 caracteres.';
        return null;
    }
}