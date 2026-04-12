<?php
class User {
    private $conn;
    private $table = 'usuario';
 
    //id_estatus de la tabla Estatus para usuarios activos, 0 para inactivos.
    private const ESTATUS_ACTIVO = 1;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function findByUsernameOrEmail($identifier) {
        //Buscar por usuario o email
        $query = "
            SELECT
                u.id_usuario        AS id,
                u.usuario,
                u.email,
                u.contrasena        AS password,
                u.nombre_usuario    AS nombre_completo,
                r.nombre_rol        AS rol,
                u.id_rol,
                u.id_estatus
            FROM usuario u
            INNER JOIN rol r ON r.id_rol = u.id_rol
            WHERE u.usuario = :username
               OR u.email   = :email
            LIMIT 1
        ";
 
        $stmt = $this->conn->prepare($query);
        
        // Usar dos parámetros separados con el mismo valor - para evitar confusiones en la consulta --
        $stmt->bindValue(':username', $identifier, PDO::PARAM_STR);
        $stmt->bindValue(':email', $identifier, PDO::PARAM_STR);
        
        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log("Error en findByUsernameOrEmail: " . $e->getMessage());
            return false;
        }
    }
    
    public function findById($id) {
        $query = "
            SELECT
                u.id_usuario        AS id,
                u.usuario,
                u.email,
                u.nombre_usuario    AS nombre_completo,
                r.nombre_rol        AS rol,
                u.id_estatus,
                u.fecha_registro    AS created_at
            FROM usuario u
            INNER JOIN rol r ON r.id_rol = u.id_rol
            WHERE u.id_usuario = :id
            LIMIT 1
        ";
 
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
 
        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log("Error en findById: " . $e->getMessage());
            return false;
        }
    }
 
    public function getAll($filtros = []) {
        $query = "
            SELECT
                u.id_usuario        AS id,
                u.usuario,
                u.email,
                u.nombre_usuario    AS nombre_completo,
                r.nombre_rol        AS rol,
                u.id_estatus,
                u.fecha_registro    AS created_at
            FROM Usuario u
            INNER JOIN Rol r ON r.id_rol = u.id_rol
        ";
 
        $conditions = [];
        $params     = [];
 
        if (isset($filtros['activo'])) {
            if ($filtros['activo']) {
                $conditions[]       = "u.id_estatus = :estatus";
                $params[':estatus'] = self::ESTATUS_ACTIVO;
            } else {
                $conditions[]       = "u.id_estatus != :estatus";
                $params[':estatus'] = self::ESTATUS_ACTIVO;
            }
        }
 
        if (isset($filtros['rol'])) {
            $conditions[]   = "r.nombre_rol = :rol";
            $params[':rol'] = $filtros['rol'];
        }
 
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
 
        $query .= " ORDER BY u.fecha_registro DESC";
 
        $stmt = $this->conn->prepare($query);
 
        try {
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getAll users: " . $e->getMessage());
            return [];
        }
    }
    
    //Crear nuevo usuario
    public function create($data) {
        $query = "
            INSERT INTO usuario (usuario, email, contrasena, nombre_usuario, id_rol, id_estatus)
            VALUES (:usuario, :email, :contrasena, :nombre_usuario, :id_rol, :id_estatus)
        ";
 
        $stmt = $this->conn->prepare($query);
 
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        $idEstatus    = $data['id_estatus'] ?? self::ESTATUS_ACTIVO;
 
        $stmt->bindValue(':usuario',        $data['usuario']);
        $stmt->bindValue(':email',          $data['email']);
        $stmt->bindValue(':contrasena',     $passwordHash);
        $stmt->bindValue(':nombre_usuario', $data['nombre_completo']);
        $stmt->bindValue(':id_rol',         $data['id_rol'],  PDO::PARAM_INT);
        $stmt->bindValue(':id_estatus',     $idEstatus,       PDO::PARAM_INT);
 
        try {
            return $stmt->execute() ? $this->conn->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error en create user: " . $e->getMessage());
            return false;
        }
    }
    
 
    //Actualizar usuario existente
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
 
        if (isset($data['usuario'])) {
            $fields[]           = "usuario = :usuario";
            $params[':usuario'] = $data['usuario'];
        }
 
        if (isset($data['email'])) {
            $fields[]         = "email = :email";
            $params[':email'] = $data['email'];
        }
 
        if (isset($data['nombre_completo'])) {
            $fields[]           = "nombre_usuario = :nombre_usuario";
            $params[':nombre_usuario'] = $data['nombre_completo'];
        }
 
        if (isset($data['password'])) {
            $fields[]              = "contrasena = :contrasena";
            $params[':contrasena'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
 
        if (isset($data['id_rol'])) {
            $fields[]          = "id_rol = :id_rol";
            $params[':id_rol'] = (int)$data['id_rol'];
        }
 
        // Soporte para id_estatus directo o booleano legacy 'activo'
        if (isset($data['id_estatus'])) {
            $fields[]              = "id_estatus = :id_estatus";
            $params[':id_estatus'] = (int)$data['id_estatus'];
        } elseif (isset($data['activo'])) {
            $fields[]              = "id_estatus = :id_estatus";
            $params[':id_estatus'] = $data['activo'] ? self::ESTATUS_ACTIVO : 2;
        }
 
        if (empty($fields)) {
            return false;
        }
 
        $query = "UPDATE usuario SET " . implode(', ', $fields) . " WHERE id_usuario = :id";
 
        $stmt = $this->conn->prepare($query);
 
        try {
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error en update user: " . $e->getMessage());
            return false;
        }
    }
    
 
    //Desactivar (eliminar) usaurio existente
    public function delete($id) {
        // solo desactivar
        $query = "UPDATE usuario SET id_estatus = 2 WHERE id_usuario = :id";
 
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
 
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en delete user: " . $e->getMessage());
            return false;
        }
    }
 
 
    // Función para verificar si el usuario está activo
    public function isActivo(array $userData): bool {
        return isset($userData['id_estatus']) && (int)$userData['id_estatus'] === self::ESTATUS_ACTIVO;
    }
    
    // Verificar si el email o usuario ya existe (para evitar duplicados) excluyendo el ID actual en caso de actualización
    public function emailExists($email, $excludeId = null) {
        $query = "SELECT id_usuario FROM Usuario WHERE email = :email";
 
        if ($excludeId) {
            $query .= " AND id_usuario != :exclude_id";
        }
 
        $query .= " LIMIT 1";
 
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':email', $email);
 
        if ($excludeId) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
 
        try {
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error en emailExists: " . $e->getMessage());
            return false;
        }
    }
    
 
    // Verificar si el nombre de usuario ya existe (para evitar duplicados) excluyendo el ID actual en caso de actualización
    public function usernameExists($usuario, $excludeId = null) {
        $query = "SELECT id_usuario FROM Usuario WHERE usuario = :usuario";
 
        if ($excludeId) {
            $query .= " AND id_usuario != :exclude_id";
        }
 
        $query .= " LIMIT 1";
 
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':usuario', $usuario);
 
        if ($excludeId) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
 
        try {
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error en usernameExists: " . $e->getMessage());
            return false;
        }
    }
}
?>