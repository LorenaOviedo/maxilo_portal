<?php
class User {
    private $conn;
    private $table = 'usuarios';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function findByUsernameOrEmail($identifier) {
        //Buscar por usuario o email
        $query = "SELECT id, usuario, email, password, nombre_completo, rol, activo 
                  FROM " . $this->table . " 
                  WHERE usuario = :username OR email = :email 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        
        // Usar dos parámetros separados con el mismo valor - para evitar confusiones en la consulta
        $stmt->bindValue(':username', $identifier, PDO::PARAM_STR);
        $stmt->bindValue(':email', $identifier, PDO::PARAM_STR);
        
        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result : false;
        } catch (PDOException $e) {
            error_log("Error en findByUsernameOrEmail: " . $e->getMessage());
            return false;
        }
    }
    
    public function findById($id) {
        $query = "SELECT id, usuario, email, nombre_completo, rol, activo, created_at 
                  FROM " . $this->table . " 
                  WHERE id = :id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result : false;
        } catch (PDOException $e) {
            error_log("Error en findById: " . $e->getMessage());
            return false;
        }
    }
    
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (usuario, email, password, nombre_completo, rol) 
                  VALUES 
                  (:usuario, :email, :password, :nombre_completo, :rol)";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash de la contraseña 
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $stmt->bindValue(':usuario', $data['usuario']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':password', $passwordHash);
        $stmt->bindValue(':nombre_completo', $data['nombre_completo']);
        $stmt->bindValue(':rol', $data['rol']);
        
        try {
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error en create user: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['usuario'])) {
            $fields[] = "usuario = :usuario";
            $params[':usuario'] = $data['usuario'];
        }
        
        if (isset($data['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        
        if (isset($data['nombre_completo'])) {
            $fields[] = "nombre_completo = :nombre_completo";
            $params[':nombre_completo'] = $data['nombre_completo'];
        }
        
        if (isset($data['password'])) {
            $fields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        if (isset($data['rol'])) {
            $fields[] = "rol = :rol";
            $params[':rol'] = $data['rol'];
        }
        
        if (isset($data['activo'])) {
            $fields[] = "activo = :activo";
            $params[':activo'] = $data['activo'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $query = "UPDATE " . $this->table . " 
                  SET " . implode(', ', $fields) . " 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        try {
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error en update user: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        // solo desactivar
        $query = "UPDATE " . $this->table . " 
                  SET activo = 0 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en delete user: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAll($filtros = []) {
        $query = "SELECT id, usuario, email, nombre_completo, rol, activo, created_at 
                  FROM " . $this->table;
        
        $conditions = [];
        $params = [];
        
        if (isset($filtros['activo'])) {
            $conditions[] = "activo = :activo";
            $params[':activo'] = $filtros['activo'];
        }
        
        if (isset($filtros['rol'])) {
            $conditions[] = "rol = :rol";
            $params[':rol'] = $filtros['rol'];
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        try {
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getAll users: " . $e->getMessage());
            return [];
        }
    }
    
    public function emailExists($email, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email";
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        
        $query .= " LIMIT 1"; // Solo necesitamos saber si existe al menos uno
        
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
    
    public function usernameExists($usuario, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table . " WHERE usuario = :usuario";
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
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