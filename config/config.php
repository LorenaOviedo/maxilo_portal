<?php
/**
 * Configuración Principal del Sistema
 * Sistema Maxilofacial Texcoco
 * 
 * Versión mejorada con soporte para variables de entorno (.env)
 */

// ==================== CARGAR VARIABLES DE ENTORNO ====================

require_once __DIR__ . '/Env.php';

// Cargar archivo .env (si existe)
Env::load(__DIR__ . '/../.env');

// ==================== CONFIGURACIÓN DE ENTORNO ====================

/**
 * Determinar entorno automáticamente
 * Prioridad: 
 * 1. Variable de entorno APP_ENV del .env
 * 2. Variable de servidor (configurada en hosting)
 * 3. Detección automática por dominio
 */
function detectEnvironment() {
    // 1. Intentar desde .env
    $envFromFile = Env::get('APP_ENV');
    if ($envFromFile) {
        return $envFromFile;
    }
    
    // 2. Variable de servidor
    if (isset($_ENV['APP_ENV'])) {
        return $_ENV['APP_ENV'];
    }
    
    // 3. Detección automática por dominio
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    
    // Si es localhost o tiene números de IP, es desarrollo
    if (
        strpos($host, 'localhost') !== false || 
        strpos($host, '127.0.0.1') !== false ||
        strpos($host, '192.168.') !== false ||
        empty($host)
    ) {
        return 'development';
    }
    
    // Si tiene un dominio real, es producción
    return 'production';
}

// Definir entorno
define('ENVIRONMENT', detectEnvironment());

// ==================== CONFIGURACIÓN DE URLS ====================

if (ENVIRONMENT === 'production') {
    // PRODUCCIÓN - Usar valores del .env o valores por defecto
    define('BASE_URL', Env::get('BASE_URL', 'https://portal-maxilofacial.site'));
    define('BASE_PATH', Env::get('BASE_PATH', '/'));
    
} else {
    // DESARROLLO - Usar valores del .env o valores por defecto
    define('BASE_URL', Env::get('BASE_URL', 'http://localhost'));
    define('BASE_PATH', Env::get('BASE_PATH', '/PT_MAXILOFACIAL_TEXOCOCO/'));
}

// URL completa del proyecto
define('SITE_URL', BASE_URL . BASE_PATH);

// ==================== CONFIGURACIÓN DE RUTAS ====================

// Ruta raíz del proyecto
define('ROOT_PATH', dirname(__DIR__) . '/');

// Directorios principales
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('CONTROLLERS_PATH', ROOT_PATH . 'controllers/');
define('MODELS_PATH', ROOT_PATH . 'models/');
define('VIEWS_PATH', ROOT_PATH . 'views/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');

// Assets
define('ASSETS_PATH', ROOT_PATH . 'assets/');
define('CSS_PATH', ASSETS_PATH . 'css/');
define('JS_PATH', ASSETS_PATH . 'js/');
define('IMG_PATH', ASSETS_PATH . 'img/');
define('UPLOADS_PATH', ASSETS_PATH . 'uploads/');

// URLs de Assets (para HTML)
define('CSS_URL', SITE_URL . 'assets/css/');
define('JS_URL', SITE_URL . 'assets/js/');
define('IMG_URL', SITE_URL . 'assets/img/');
define('UPLOADS_URL', SITE_URL . 'assets/uploads/');

// ==================== BASE DE DATOS ====================

/**
 * Configuración de base de datos desde .env
 * Si no existe .env, usa valores por defecto (desarrollo local)
 */
define('DB_HOST', Env::get('DB_HOST', 'localhost'));
define('DB_NAME', Env::get('DB_NAME', 'maxilofacial_texcoco'));
define('DB_USER', Env::get('DB_USER', 'root'));
define('DB_PASS', Env::get('DB_PASS', ''));
define('DB_CHARSET', Env::get('DB_CHARSET', 'utf8mb4'));

// ==================== MANEJO DE ERRORES ====================

$appDebug = Env::getBool('APP_DEBUG', ENVIRONMENT === 'development');

if (ENVIRONMENT === 'production' && !$appDebug) {
    // PRODUCCIÓN: No mostrar errores al usuario
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    
    // Guardar errores en archivo de log
    ini_set('log_errors', 1);
    $logPath = ROOT_PATH . 'logs/error.log';
    
    // Crear directorio de logs si no existe
    if (!file_exists(dirname($logPath))) {
        @mkdir(dirname($logPath), 0755, true);
    }
    
    ini_set('error_log', $logPath);
    
} else {
    // DESARROLLO: Mostrar todos los errores
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // También guardar en log en desarrollo (útil para debugging)
    ini_set('log_errors', 1);
    $logPath = ROOT_PATH . 'logs/error.log';
    
    if (!file_exists(dirname($logPath))) {
        @mkdir(dirname($logPath), 0755, true);
    }
    
    ini_set('error_log', $logPath);
}

// ==================== CONFIGURACIÓN DE ZONA HORARIA ====================

$timezone = Env::get('APP_TIMEZONE', 'America/Mexico_City');
date_default_timezone_set($timezone);

// ==================== INFORMACIÓN DEL SITIO ====================

define('SITE_NAME', Env::get('APP_NAME', 'Maxilofacial Texcoco'));
define('SITE_DESCRIPTION', Env::get('APP_DESCRIPTION', 'Sistema de Gestión Integral'));
define('SITE_EMAIL', Env::get('SITE_EMAIL', 'contacto@maxilofacialtexcoco.com'));
define('SITE_VERSION', Env::get('APP_VERSION', '1.0.0'));

// ==================== CONFIGURACIÓN DE SESIONES ====================

define('SESSION_LIFETIME', Env::getInt('SESSION_LIFETIME', 7200)); // 2 horas por defecto
define('SESSION_NAME', Env::get('SESSION_NAME', 'SMT_SESSION'));

// Configurar sesión
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    ini_set('session.cookie_httponly', 1);
    
    // En producción, usar cookie segura (HTTPS)
    if (ENVIRONMENT === 'production') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_name(SESSION_NAME);
}

// ==================== CONFIGURACIÓN DE UPLOADS ====================

define('UPLOAD_MAX_SIZE', Env::getInt('UPLOAD_MAX_SIZE', 5242880)); // 5MB por defecto
define('ALLOWED_EXTENSIONS', Env::get('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,pdf,doc,docx'));

// ==================== FUNCIONES DE UTILIDAD ====================

/**
 * Generar URL completa
 * @param string $path Ruta relativa
 * @return string URL completa
 */
function url($path = '') {
    return SITE_URL . ltrim($path, '/');
}

/**
 * Generar URL de asset (CSS, JS, IMG, etc.)
 * @param string $path Ruta del asset
 * @return string URL completa del asset
 */
function asset($path) {
    $cleanPath = ltrim($path, '/');
    $url = SITE_URL . 'assets/' . $cleanPath;
    
    // Agregar versión para cache busting en producción
    if (ENVIRONMENT === 'production') {
        $versionParam = '?v=' . SITE_VERSION;
        // Solo agregar a archivos CSS y JS
        if (preg_match('/\.(css|js)$/i', $path)) {
            $url .= $versionParam;
        }
    }
    
    return $url;
}

/**
 * Generar URL de vista
 * @param string $view Nombre de la vista
 * @return string URL completa de la vista
 */
function view_url($view) {
    return SITE_URL . 'views/' . ltrim($view, '/');
}

/**
 * Redireccionar a una URL
 * @param string $path Ruta a redireccionar
 */
function redirect($path = '') {
    header('Location: ' . url($path));
    exit;
}

/**
 * Verificar si está en producción
 * @return bool
 */
function is_production() {
    return ENVIRONMENT === 'production';
}

/**
 * Verificar si está en desarrollo
 * @return bool
 */
function is_development() {
    return ENVIRONMENT === 'development';
}

/**
 * Obtener variable de configuración
 * @param string $key Nombre de la variable
 * @param mixed $default Valor por defecto
 * @return mixed
 */
function config($key, $default = null) {
    return Env::get($key, $default);
}

/**
 * Registrar error en log
 * @param string $message Mensaje de error
 * @param string $level Nivel: error, warning, info
 */
function log_error($message, $level = 'error') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    $logFile = ROOT_PATH . 'logs/error.log';
    
    // Crear directorio si no existe
    if (!file_exists(dirname($logFile))) {
        @mkdir(dirname($logFile), 0755, true);
    }
    
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Debug - Solo muestra en desarrollo
 * @param mixed $data Datos a mostrar
 * @param bool $die Terminar ejecución
 */
function debug($data, $die = false) {
    if (is_development()) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        
        if ($die) {
            die();
        }
    }
}

/**
 * Sanitizar entrada de usuario
 * @param string $data Datos a sanitizar
 * @return string
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Verificar si el sistema está configurado correctamente
 * @return array Estado de la configuración
 */
function check_config() {
    $status = [
        'env_file_exists' => file_exists(ROOT_PATH . '.env'),
        'db_configured' => !empty(DB_HOST) && !empty(DB_NAME),
        'uploads_writable' => is_writable(UPLOADS_PATH),
        'logs_writable' => is_writable(ROOT_PATH . 'logs') || @mkdir(ROOT_PATH . 'logs', 0755, true),
        'environment' => ENVIRONMENT,
        'errors' => []
    ];
    
    // Verificar configuración crítica
    if (!$status['env_file_exists'] && ENVIRONMENT === 'production') {
        $status['errors'][] = 'Archivo .env no encontrado en producción';
    }
    
    if (!$status['db_configured']) {
        $status['errors'][] = 'Base de datos no configurada';
    }
    
    if (!$status['uploads_writable']) {
        $status['errors'][] = 'Directorio uploads no tiene permisos de escritura';
    }
    
    $status['is_ok'] = empty($status['errors']);
    
    return $status;
}

// ==================== VERIFICACIÓN AUTOMÁTICA (Solo en desarrollo) ====================

if (is_development() && Env::getBool('APP_DEBUG', true)) {
    $configStatus = check_config();
    
    if (!$configStatus['is_ok']) {
        foreach ($configStatus['errors'] as $error) {
            log_error("Config Warning: {$error}", 'warning');
        }
    }
}

// ==================== CONSTANTES DE COMPATIBILIDAD ====================

/**
 * Estas constantes mantienen compatibilidad con código antiguo
 * que pueda estar usando constantes de reCAPTCHA directamente
 */
if (file_exists(CONFIG_PATH . 'recaptcha.php')) {
    require_once CONFIG_PATH . 'recaptcha.php';
}

// ==================== LOG DE INICIO (Solo en desarrollo) ====================

if (is_development()) {
    log_error("Sistema iniciado - Entorno: " . ENVIRONMENT . " - URL: " . SITE_URL, 'info');
}

?>
