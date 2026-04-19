<?php
/**
 * Modelo de Antecedente Médico
 * Tabla: antecedentemedico
 */
class AntecedenteMedico
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
            SELECT id_antecedente, nombre_antecedente, tipo, implica_alerta_medica
            FROM   antecedentemedico
        ";
 
        $conditions = [];
        $params     = [];
 
        if (!empty($filtros['buscar'])) {
            $termino            = '%' . $filtros['buscar'] . '%';
            $conditions[]       = "(nombre_antecedente LIKE :buscar OR tipo LIKE :buscar2)";
            $params[':buscar']  = $termino;
            $params[':buscar2'] = $termino;
        }
 
        if (!empty($filtros['tipo'])) {
            $conditions[]    = "tipo = :tipo";
            $params[':tipo'] = $filtros['tipo'];
        }
 
        if (!empty($conditions))
            $query .= " WHERE " . implode(' AND ', $conditions);
 
        $query .= " ORDER BY tipo ASC, nombre_antecedente ASC";
 
        $stmt = $this->conn->prepare($query);
 
        try {
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en AntecedenteMedico::getAll: " . $e->getMessage());
            return [];
        }
    }
 
    public function getById($id)
    {
        $stmt = $this->conn->prepare("
            SELECT id_antecedente, nombre_antecedente, tipo, implica_alerta_medica
            FROM   antecedentemedico
            WHERE  id_antecedente = :id
            LIMIT 1
        ");
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
 
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log("Error en AntecedenteMedico::getById: " . $e->getMessage());
            return false;
        }
    }
 
    // ==================== ESCRITURA ====================
 
    public function create($data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO antecedentemedico (nombre_antecedente, tipo, implica_alerta_medica)
            VALUES (:nombre_antecedente, :tipo, :implica_alerta_medica)
        ");
        $stmt->bindValue(':nombre_antecedente',   ucwords(strtolower(trim($data['nombre_antecedente']))));
        $stmt->bindValue(':tipo',                 trim($data['tipo'] ?? ''));
        $stmt->bindValue(':implica_alerta_medica',(int)(bool)($data['implica_alerta_medica'] ?? 0), PDO::PARAM_INT);
 
        try {
            return $stmt->execute() ? $this->conn->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error en AntecedenteMedico::create: " . $e->getMessage());
            return false;
        }
    }
 
    public function update($id, $data)
    {
        $stmt = $this->conn->prepare("
            UPDATE antecedentemedico
            SET    nombre_antecedente   = :nombre_antecedente,
                   tipo                 = :tipo,
                   implica_alerta_medica = :implica_alerta_medica
            WHERE  id_antecedente = :id
        ");
        $stmt->bindValue(':nombre_antecedente',   ucwords(strtolower(trim($data['nombre_antecedente']))));
        $stmt->bindValue(':tipo',                 trim($data['tipo'] ?? ''));
        $stmt->bindValue(':implica_alerta_medica',(int)(bool)($data['implica_alerta_medica'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':id',                   (int) $id, PDO::PARAM_INT);
 
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en AntecedenteMedico::update: " . $e->getMessage());
            return false;
        }
    }
 
    public function delete($id)
    {
        $stmt = $this->conn->prepare("
            DELETE FROM antecedentemedico WHERE id_antecedente = :id
        ");
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
 
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en AntecedenteMedico::delete: " . $e->getMessage());
            return false;
        }
    }
 
    // ==================== VALIDACIÓN ====================
 
    public function validar($data): ?string
    {
        if (empty(trim($data['nombre_antecedente'] ?? '')))
            return 'El nombre del antecedente es obligatorio.';
        if (mb_strlen(trim($data['nombre_antecedente']), 'UTF-8') > 150)
            return 'El nombre no debe superar 150 caracteres.';
        if (empty(trim($data['tipo'] ?? '')))
            return 'El tipo es obligatorio.';
        return null;
    }
}