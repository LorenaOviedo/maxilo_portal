<?php
//Configuración de Base de Datos

// Cargar archivo de variables de entorno
require_once __DIR__ . '/Env.php';

// Cargar variables del archivo .env
Env::load();

// Validar que exista la configuración crítica
try {
    Env::validateCriticalConfig();
} catch (Exception $e) {
    die("Error de configuración: " . $e->getMessage());
}

// CONFIGURACIÓN DE BASE DE DATOS

class Database {
    
    private static $instance = null;
    private $connection;
    
    // Configuración desde variables de entorno
    private $host;
    private $database;
    private $username;
    private $password;
    private $charset;
    
    /**
     * Constructor privado (Singleton)
     */
    private function __construct() {
        // Cargar configuración desde .env
        $this->host = Env::require('DB_HOST');
        $this->database = Env::require('DB_NAME');
        $this->username = Env::require('DB_USER');
        $this->password = Env::require('DB_PASS');
        $this->charset = Env::get('DB_CHARSET', 'utf8mb4');
        
        $this->connect();
    }
    
    //Obtener instancia única de la base de datos
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    //Conectar a la base de datos
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
            // Log solo en desarrollo
            if (Env::get('APP_ENV') !== 'production') {
                error_log("✓ Conexión a base de datos establecida");
            }
            
        } catch (PDOException $e) {
            // En producción (no mostrar detalles de error)
            if (Env::get('APP_ENV') === 'production') {
                error_log("Error de conexión a BD: " . $e->getMessage());
                die("Error al conectar con la base de datos. Por favor contacta al administrador.");
            } else {
                die("Error de conexión: " . $e->getMessage());
            }
        }
    }
    
    //Obtener conexión PDO
    public function getConnection() {
        return $this->connection;
    }
    
    //Evitar clonación
    private function __clone() {}
    
    /**
     * Prevenir deserialización
     */
    public function __wakeup() {
        throw new Exception("No se puede deserializar un singleton");
    }
}

// FUNCIÓN DE AYUDA PARA OBTENER CONEXIÓN

/**
 * Obtener conexión a la base de datos
 * 
 * @return PDO Conexión PDO
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

// CONFIGURACIÓN LEGACY (compatibilidad) 

/**
 * NOTA: Mantener estas constantes por compatibilidad con código existente
 * En nuevos archivos, usa Env::get() directamente
 */
define('DB_HOST', Env::get('DB_HOST'));
define('DB_NAME', Env::get('DB_NAME'));
define('DB_USER', Env::get('DB_USER'));
define('DB_PASS', Env::get('DB_PASS'));
define('DB_CHARSET', Env::get('DB_CHARSET', 'utf8mb4'));

// FUNCIÓN LEGACY DE CONEXIÓN 

/**
 * Función legacy de conexión (compatibilidad)
 * 
 * @deprecated Usar getDB() en su lugar
 */
function conectarDB() {
    return getDB();
}