<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db;
    private $userModel;
    private $maxIntentos = 5;
    private $tiempoBloqueo = 900; // 15 minutos en segundos
    
    public function __construct() {
        $database = new Database();
        $this->db = getDB();
        $this->userModel = new User($this->db);
    }
    
    public function login($usuario, $password) {
        //Limpiar entradas
        $usuario = trim($usuario);
        $password = trim($password);
        
        // Validaciones basicas
        if (empty($usuario) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Usuario y contraseña son requeridos'
            ];
        }
        
        // Verificar si está bloqueado por múltiples intentos
        if ($this->estaBloqueado()) {
            return [
                'success' => false,
                'message' => 'Demasiados intentos fallidos. Intente nuevamente en 15 minutos'
            ];
        }

        // Buscar usuario por username o email
        $userData = $this->userModel->findByUsernameOrEmail($usuario);
        
        if (!$userData) {
            $this->registrarIntentoFallido();
            return [
                'success' => false,
                'message' => 'El usuario no existe o es incorrecto'
            ];
        }
        
        // Verificar si el usuario está activo
        if (!$userData['activo']) {
            return [
                'success' => false,
                'message' => 'Usuario desactivado. Contacte al administrador'
            ];
        }
    
        //Verificar contraseña
        if (!password_verify($password, $userData['password'])) {
        //PRUEBA: die("FUNCIONA, el problema era un espacio o se trunca.");/
        $this->registrarIntentoFallido();
        return [
        'success' => false,
        'message' => 'Credenciales incorrectas - contraseña inválida'
        ];
        }
        
        // Login exitoso - limpiar intentos fallidos
        $this->limpiarIntentosFallidos();
        
        // Crear sesión
        $this->crearSesion($userData);
        
        // Registrar sesión en base de datos
        $this->registrarSesion($userData['id']);
        
        return [
            'success' => true,
            'message' => 'Login exitoso',
            //'redirect' => 'dashboard.php'
        ];
    }
    
    private function crearSesion($userData) {
        // Regenerar ID de sesión para prevenir fijación de sesión -session fixation
        session_regenerate_id(true);
        
        $_SESSION['usuario_id'] = $userData['id'];
        $_SESSION['usuario'] = $userData['usuario'];
        $_SESSION['email'] = $userData['email'];
        $_SESSION['nombre_completo'] = $userData['nombre_completo'];
        $_SESSION['rol'] = $userData['rol'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        // Token CSRF
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    private function registrarSesion($usuarioId) {
        try {
            $token = bin2hex(random_bytes(32));
            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            
            $query = "INSERT INTO sesiones (usuario_id, token, ip_address, user_agent) 
                     VALUES (:usuario_id, :token, :ip_address, :user_agent)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':ip_address', $ip);
            $stmt->bindParam(':user_agent', $userAgent);
            
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al registrar sesión: " . $e->getMessage());
        }
    }
    
    private function estaBloqueado() {
        if (!isset($_SESSION['intentos_fallidos'])) {
            return false;
        }
        
        if ($_SESSION['intentos_fallidos'] >= $this->maxIntentos) {
            $tiempoTranscurrido = time() - $_SESSION['tiempo_bloqueo'];
            
            if ($tiempoTranscurrido < $this->tiempoBloqueo) {
                return true;
            } else {
                // El bloqueo expiró, limpiar
                $this->limpiarIntentosFallidos();
                return false;
            }
        }
        
        return false;
    }
    
    private function registrarIntentoFallido() {
        if (!isset($_SESSION['intentos_fallidos'])) {
            $_SESSION['intentos_fallidos'] = 0;
        }
        
        $_SESSION['intentos_fallidos']++;
        
        if ($_SESSION['intentos_fallidos'] >= $this->maxIntentos) {
            $_SESSION['tiempo_bloqueo'] = time();
        }
    }
    
    private function limpiarIntentosFallidos() {
        unset($_SESSION['intentos_fallidos']);
        unset($_SESSION['tiempo_bloqueo']);
    }
    
    public function logout() {
        // Destruir sesión
        session_unset();
        session_destroy();
        
        // Iniciar nueva sesión limpia
        session_start();
        session_regenerate_id(true);
        
        return [
            'success' => true,
            'redirect' => '../index.php'
        ];
    }
    
    public function verificarSesion() {
        if (!isset($_SESSION['usuario_id'])) {
            return false;
        }
        
        // Verificar timeout de sesión (30 minutos de inactividad)
        $timeout = 1800; // 30 minutos
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        
        // Verificar que la IP y User Agent no hayan cambiado -seguridad adicional para prevenir secuestro de sesión
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    private function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}
?>