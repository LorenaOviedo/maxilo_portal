<?php
/**
 * Modelo de Procedimiento Dental
 * Tabla: procedimientos
 * JOINs: especialidad, estatus
 */
class Procedimiento {
    private $conn;
 
    private const ESTATUS_ACTIVO   = 1;
    private const ESTATUS_INACTIVO = 2;
 
    public function __construct($db) {
        $this->conn = $db;
    }
 
    // ==================== LECTURA ====================
 
    //Obtener todos los procedimientos. Filtros opcionales: "id_estatus" y "id_especialidad"
    
    public function getAll($filtros = []) {
        $query = "
            SELECT
                p.id_procedimiento,
                p.nombre_procedimiento,
                p.descripcion,
                p.precio_base,
                p.tiempo_estimado,
                p.requiere_autorizacion,
                p.tipo,
                p.id_especialidad,
                p.id_estatus,
                e.nombre   AS especialidad,
                s.estatus  AS estatus
            FROM procedimientos p
            INNER JOIN especialidad e ON e.id_especialidad = p.id_especialidad
            INNER JOIN estatus s      ON s.id_estatus      = p.id_estatus
        ";
 
        $conditions = [];
        $params     = [];
 
        if (isset($filtros['id_estatus'])) {
            $conditions[]          = "p.id_estatus = :id_estatus";
            $params[':id_estatus'] = (int)$filtros['id_estatus'];
        }
 
        if (isset($filtros['id_especialidad'])) {
            $conditions[]               = "p.id_especialidad = :id_especialidad";
            $params[':id_especialidad'] = (int)$filtros['id_especialidad'];
        }
 
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
 
        $query .= " ORDER BY p.id_procedimiento DESC";
 
        $stmt = $this->conn->prepare($query);
 
        try {
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Procedimiento::getAll: " . $e->getMessage());
            return [];
        }
    }
 
    //Obtener un procedimiento por id.
    public function getById($id) {
        $query = "
            SELECT
                p.id_procedimiento,
                p.nombre_procedimiento,
                p.descripcion,
                p.id_especialidad,
                p.precio_base,
                p.tiempo_estimado,
                p.requiere_autorizacion,
                p.tipo,
                p.id_estatus,
                e.nombre   AS especialidad,
                s.estatus  AS estatus
            FROM procedimientos p
            INNER JOIN especialidad e ON e.id_especialidad = p.id_especialidad
            INNER JOIN estatus s      ON s.id_estatus      = p.id_estatus
            WHERE p.id_procedimiento = :id
            LIMIT 1
        ";
 
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
 
        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log("Error en Procedimiento::getById: " . $e->getMessage());
            return false;
        }
    }
 
    //Obtener todas las especialidades activas (para el select del formulario).
    public function getEspecialidades() {
        $query = "
            SELECT id_especialidad, nombre
            FROM especialidad
            WHERE id_estatus = :estatus
            ORDER BY nombre ASC
        ";
 
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':estatus', self::ESTATUS_ACTIVO, PDO::PARAM_INT);
 
        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Procedimiento::getEspecialidades: " . $e->getMessage());
            return [];
        }
    }
 
    // ==================== ESCRITURA ====================
 
    //Crear procedimiento nuevo.
    public function create($data) {
        $query = "
            INSERT INTO procedimientos
                (nombre_procedimiento, descripcion, id_especialidad,
                 precio_base, tiempo_estimado, requiere_autorizacion, tipo, id_estatus)
            VALUES
                (:nombre_procedimiento, :descripcion, :id_especialidad,
                 :precio_base, :tiempo_estimado, :requiere_autorizacion, :tipo, :id_estatus)
        ";
 
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':nombre_procedimiento', strtoupper(trim($data['nombre_procedimiento'])));
        $stmt->bindValue(':descripcion',          trim($data['descripcion'] ?? ''));
        $stmt->bindValue(':id_especialidad',      (int)$data['id_especialidad'],                    PDO::PARAM_INT);
        $stmt->bindValue(':precio_base',          (float)$data['precio_base']);
        $stmt->bindValue(':tiempo_estimado',      !empty($data['tiempo_estimado']) ? (int)$data['tiempo_estimado'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':requiere_autorizacion',(int)(bool)($data['requiere_autorizacion'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':tipo',                 strtoupper(trim($data['tipo'] ?? '')));
        $stmt->bindValue(':id_estatus',           (int)($data['id_estatus'] ?? self::ESTATUS_ACTIVO), PDO::PARAM_INT);
 
        try {
            return $stmt->execute() ? $this->conn->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error en Procedimiento::create: " . $e->getMessage());
            return false;
        }
    }
 
    //Actualizar procedimiento. No se puede cambiar el estatus aquí, para eso está cambiarEstatus()
    public function update($id, $data) {
        $query = "
            UPDATE procedimientos SET
                nombre_procedimiento  = :nombre_procedimiento,
                descripcion           = :descripcion,
                id_especialidad       = :id_especialidad,
                precio_base           = :precio_base,
                tiempo_estimado       = :tiempo_estimado,
                requiere_autorizacion = :requiere_autorizacion,
                tipo                  = :tipo,
                id_estatus            = :id_estatus
            WHERE id_procedimiento = :id
        ";
 
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':nombre_procedimiento', strtoupper(trim($data['nombre_procedimiento'])));
        $stmt->bindValue(':descripcion',          trim($data['descripcion'] ?? ''));
        $stmt->bindValue(':id_especialidad',      (int)$data['id_especialidad'],                    PDO::PARAM_INT);
        $stmt->bindValue(':precio_base',          (float)$data['precio_base']);
        $stmt->bindValue(':tiempo_estimado',      !empty($data['tiempo_estimado']) ? (int)$data['tiempo_estimado'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':requiere_autorizacion',(int)(bool)($data['requiere_autorizacion'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':tipo',                 strtoupper(trim($data['tipo'] ?? '')));
        $stmt->bindValue(':id_estatus',           (int)$data['id_estatus'],                        PDO::PARAM_INT);
        $stmt->bindValue(':id',                   (int)$id,                                        PDO::PARAM_INT);
 
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en Procedimiento::update: " . $e->getMessage());
            return false;
        }
    }
 
    //Cambiar estatus (activar / desactivar). No elimina físicamente
    public function cambiarEstatus($id, $nuevoEstatus) {
        $query = "UPDATE procedimientos SET id_estatus = :estatus WHERE id_procedimiento = :id";
 
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':estatus', (int)$nuevoEstatus, PDO::PARAM_INT);
        $stmt->bindValue(':id',      (int)$id,           PDO::PARAM_INT);
 
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en Procedimiento::cambiarEstatus: " . $e->getMessage());
            return false;
        }
    }
}
?>