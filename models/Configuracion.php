<?php
/**
 * Configuracion.php — Modelo
 *
 * Gestiona: perfil de usuario, usuarios, roles y permisos por módulo
 * Tablas: usuario, rol, modulos, rolpermiso, estatus
 */
class Configuracion
{
    private PDO $db;
 
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // PERFIL
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getPerfil(int $idUsuario): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id_usuario,
                u.usuario,
                u.nombre_usuario,
                u.email,
                u.fecha_registro,
                r.nombre_rol,
                r.id_rol,
                e.estatus
            FROM usuario u
            JOIN rol     r ON r.id_rol    = u.id_rol
            JOIN estatus e ON e.id_estatus = u.id_estatus
            WHERE u.id_usuario = :id
        ");
        $stmt->execute([':id' => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
 
    public function actualizarPerfil(int $idUsuario, array $data): bool
    {
        // Validar email único (excluyendo el usuario actual)
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM usuario WHERE email = :email AND id_usuario != :id"
        );
        $stmt->execute([':email' => trim($data['email']), ':id' => $idUsuario]);
        if ((int)$stmt->fetchColumn() > 0) return false;
 
        $stmt = $this->db->prepare("
            UPDATE usuario SET
                nombre_usuario = :nombre,
                email          = :email
            WHERE id_usuario = :id
        ");
        return $stmt->execute([
            ':nombre' => mb_strtoupper(trim($data['nombre_usuario']), 'UTF-8'),
            ':email'  => strtolower(trim($data['email'])),
            ':id'     => $idUsuario,
        ]);
    }
 
    public function cambiarContrasena(int $idUsuario, string $actual, string $nueva): array
    {
        // Obtener hash actual
        $stmt = $this->db->prepare(
            "SELECT contrasena FROM usuario WHERE id_usuario = :id"
        );
        $stmt->execute([':id' => $idUsuario]);
        $hash = $stmt->fetchColumn();
 
        if (!$hash || !password_verify($actual, $hash)) {
            return ['success' => false, 'message' => 'La contraseña actual es incorrecta'];
        }
        if (strlen($nueva) < 8) {
            return ['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres'];
        }
 
        $stmt = $this->db->prepare(
            "UPDATE usuario SET contrasena = :hash WHERE id_usuario = :id"
        );
        $ok = $stmt->execute([
            ':hash' => password_hash($nueva, PASSWORD_BCRYPT),
            ':id'   => $idUsuario,
        ]);
        return $ok
            ? ['success' => true,  'message' => 'Contraseña actualizada correctamente']
            : ['success' => false, 'message' => 'Error al actualizar la contraseña'];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // USUARIOS
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getUsuarios(): array
    {
        return $this->_query("
            SELECT
                u.id_usuario,
                u.usuario,
                u.nombre_usuario,
                u.email,
                u.fecha_registro,
                r.nombre_rol,
                r.id_rol,
                e.estatus,
                e.id_estatus
            FROM usuario u
            JOIN rol     r ON r.id_rol     = u.id_rol
            JOIN estatus e ON e.id_estatus = u.id_estatus
            ORDER BY u.nombre_usuario ASC
        ");
    }
 
    public function crearUsuario(array $data): array
    {
        // Validar usuario único
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM usuario WHERE usuario = :u OR email = :e"
        );
        $stmt->execute([':u' => trim($data['usuario']), ':e' => trim($data['email'])]);
        if ((int)$stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'El usuario o email ya existe'];
        }
 
        $stmt = $this->db->prepare("
            INSERT INTO usuario
                (usuario, contrasena, nombre_usuario, email, id_estatus, id_rol)
            VALUES
                (:usuario, :contrasena, :nombre, :email, 1, :id_rol)
        ");
        $ok = $stmt->execute([
            ':usuario'    => strtolower(trim($data['usuario'])),
            ':contrasena' => password_hash($data['contrasena'], PASSWORD_BCRYPT),
            ':nombre'     => mb_strtoupper(trim($data['nombre_usuario']), 'UTF-8'),
            ':email'      => strtolower(trim($data['email'])),
            ':id_rol'     => (int) $data['id_rol'],
        ]);
 
        return $ok
            ? ['success' => true,  'message' => 'Usuario creado correctamente']
            : ['success' => false, 'message' => 'Error al crear el usuario'];
    }
 
    public function actualizarUsuario(int $id, array $data): array
    {
        // Verificar email/usuario únicos excluyendo el actual
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM usuario
             WHERE (usuario = :u OR email = :e) AND id_usuario != :id"
        );
        $stmt->execute([
            ':u' => trim($data['usuario']),
            ':e' => trim($data['email']),
            ':id'=> $id,
        ]);
        if ((int)$stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'El usuario o email ya existe'];
        }
 
        $stmt = $this->db->prepare("
            UPDATE usuario SET
                usuario        = :usuario,
                nombre_usuario = :nombre,
                email          = :email,
                id_rol         = :id_rol
            WHERE id_usuario = :id
        ");
        $ok = $stmt->execute([
            ':usuario' => strtolower(trim($data['usuario'])),
            ':nombre'  => mb_strtoupper(trim($data['nombre_usuario']), 'UTF-8'),
            ':email'   => strtolower(trim($data['email'])),
            ':id_rol'  => (int) $data['id_rol'],
            ':id'      => $id,
        ]);
 
        return $ok
            ? ['success' => true,  'message' => 'Usuario actualizado correctamente']
            : ['success' => false, 'message' => 'Error al actualizar el usuario'];
    }
 
    public function toggleEstatus(int $id): array
    {
        // Obtener estatus actual
        $stmt = $this->db->prepare(
            "SELECT id_estatus FROM usuario WHERE id_usuario = :id"
        );
        $stmt->execute([':id' => $id]);
        $actual = (int) $stmt->fetchColumn();
 
        // Buscar IDs de estatus activo/inactivo
        $stmtE = $this->db->query(
            "SELECT id_estatus, LOWER(estatus) AS estatus FROM estatus
             WHERE LOWER(estatus) IN ('activo','inactivo')"
        );
        $estatuses = [];
        foreach ($stmtE->fetchAll(PDO::FETCH_ASSOC) as $e) {
            $estatuses[$e['estatus']] = (int) $e['id_estatus'];
        }
 
        $idActivo   = $estatuses['activo']   ?? 1;
        $idInactivo = $estatuses['inactivo'] ?? 2;
        $nuevo = ($actual === $idActivo) ? $idInactivo : $idActivo;
 
        $stmt = $this->db->prepare(
            "UPDATE usuario SET id_estatus = :id_estatus WHERE id_usuario = :id"
        );
        $ok = $stmt->execute([':id_estatus' => $nuevo, ':id' => $id]);
 
        return $ok
            ? ['success' => true,
               'message' => $nuevo === $idActivo ? 'Usuario activado' : 'Usuario desactivado',
               'estatus' => $nuevo === $idActivo ? 'Activo' : 'Inactivo']
            : ['success' => false, 'message' => 'Error al cambiar estatus'];
    }
 
    public function resetContrasena(int $id, string $nueva): array
    {
        if (strlen($nueva) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres'];
        }
        $stmt = $this->db->prepare(
            "UPDATE usuario SET contrasena = :hash WHERE id_usuario = :id"
        );
        $ok = $stmt->execute([
            ':hash' => password_hash($nueva, PASSWORD_BCRYPT),
            ':id'   => $id,
        ]);
        return $ok
            ? ['success' => true,  'message' => 'Contraseña restablecida correctamente']
            : ['success' => false, 'message' => 'Error al restablecer la contraseña'];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // ROLES Y PERMISOS
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getRoles(): array
    {
        return $this->_query(
            "SELECT id_rol, nombre_rol FROM rol ORDER BY nombre_rol"
        );
    }
 
    public function getModulos(): array
    {
        return $this->_query(
            "SELECT id_modulo, modulo, descripcion_modulo
             FROM modulos WHERE modulo <> 'Inicio' ORDER BY modulo"
        );
    }
 
    /** Matriz de permisos: rol → módulos asignados */
    public function getPermisosMatriz(): array
    {
        $roles   = $this->getRoles();
        $modulos = $this->getModulos();
 
        // Cargar todos los permisos existentes
        $stmt = $this->db->query(
            "SELECT id_rol, id_modulo FROM rolpermiso"
        );
        $asignados = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
            $asignados[$p['id_rol']][$p['id_modulo']] = true;
        }
 
        return [
            'roles'    => $roles,
            'modulos'  => $modulos,
            'asignados'=> $asignados,
        ];
    }
 
    /** Guardar permisos de un rol (reemplaza completo) */
    public function guardarPermisos(int $idRol, array $idModulos): bool
    {
        $this->db->beginTransaction();
        try {
            // Eliminar permisos actuales del rol
            $this->db->prepare("DELETE FROM rolpermiso WHERE id_rol = :id")
                     ->execute([':id' => $idRol]);
 
            // Insertar los nuevos
            if (!empty($idModulos)) {
                $stmt = $this->db->prepare(
                    "INSERT INTO rolpermiso (id_rol, id_modulo) VALUES (:id_rol, :id_modulo)"
                );
                foreach ($idModulos as $idModulo) {
                    $stmt->execute([
                        ':id_rol'    => $idRol,
                        ':id_modulo' => (int) $idModulo,
                    ]);
                }
            }
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('Configuracion::guardarPermisos — ' . $e->getMessage());
            return false;
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CATÁLOGOS
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getCatalogos(): array
    {
        return [
            'roles' => $this->getRoles(),
        ];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // HELPER
    // ─────────────────────────────────────────────────────────────────────────
 
    private function _query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}