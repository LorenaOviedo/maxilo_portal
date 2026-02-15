<?php
/**
 * Configuración de Google reCAPTCHA
 * Sistema Maxilofacial Texcoco
 * 
 * Utiliza variables de entorno para mayor seguridad
 */

require_once __DIR__ . '/Env.php';

// Cargar variables de entorno si no están cargadas
if (!Env::has('RECAPTCHA_SITE_KEY')) {
    Env::load();
}

class ReCaptcha {
    
    private static $siteKey;
    private static $secretKey;
    
    /**
     * Inicializar reCAPTCHA
     */
    public static function init() {
        self::$siteKey = Env::get('RECAPTCHA_SITE_KEY');
        self::$secretKey = Env::get('RECAPTCHA_SECRET_KEY');
        
        // Verificar que estén configuradas
        if (empty(self::$siteKey) || empty(self::$secretKey)) {
            if (Env::get('APP_ENV') !== 'production') {
                throw new Exception("reCAPTCHA no configurado. Por favor configura RECAPTCHA_SITE_KEY y RECAPTCHA_SECRET_KEY en .env");
            }
            error_log("WARNING: reCAPTCHA no configurado");
        }
    }
    
    /**
     * Obtener Site Key (para el frontend)
     */
    public static function getSiteKey() {
        if (self::$siteKey === null) {
            self::init();
        }
        return self::$siteKey;
    }
    
    /**
     * Obtener Secret Key (solo para backend)
     */
    private static function getSecretKey() {
        if (self::$secretKey === null) {
            self::init();
        }
        return self::$secretKey;
    }
    
    /**
     * Verificar respuesta de reCAPTCHA
     * 
     * @param string $response Token de respuesta del cliente
     * @param string $remoteIp IP del cliente (opcional)
     * @return array Resultado de la verificación
     */
    public static function verify($response, $remoteIp = null) {
        if (empty($response)) {
            return [
                'success' => false,
                'error' => 'Por favor completa el reCAPTCHA'
            ];
        }
        
        $secretKey = self::getSecretKey();
        
        if (empty($secretKey)) {
            // En desarrollo, permitir continuar sin reCAPTCHA
            if (Env::get('APP_ENV') !== 'production') {
                error_log("WARNING: reCAPTCHA deshabilitado en desarrollo");
                return ['success' => true];
            }
            return [
                'success' => false,
                'error' => 'reCAPTCHA no configurado'
            ];
        }
        
        // Preparar datos para verificación
        $data = [
            'secret' => $secretKey,
            'response' => $response
        ];
        
        if ($remoteIp !== null) {
            $data['remoteip'] = $remoteIp;
        }
        
        // Realizar petición a Google
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        
        $options = [
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($verifyUrl, false, $context);
        
        if ($result === false) {
            error_log("Error al verificar reCAPTCHA: No se pudo conectar con Google");
            return [
                'success' => false,
                'error' => 'Error al verificar reCAPTCHA. Intenta nuevamente.'
            ];
        }
        
        $resultJson = json_decode($result, true);
        
        if (!isset($resultJson['success'])) {
            error_log("Error al verificar reCAPTCHA: Respuesta inválida");
            return [
                'success' => false,
                'error' => 'Error al verificar reCAPTCHA'
            ];
        }
        
        if (!$resultJson['success']) {
            $errorCodes = isset($resultJson['error-codes']) ? $resultJson['error-codes'] : [];
            error_log("reCAPTCHA falló: " . implode(', ', $errorCodes));
            
            return [
                'success' => false,
                'error' => 'Verificación de reCAPTCHA falló. Por favor intenta nuevamente.',
                'error_codes' => $errorCodes
            ];
        }
        
        return [
            'success' => true,
            'score' => isset($resultJson['score']) ? $resultJson['score'] : null,
            'action' => isset($resultJson['action']) ? $resultJson['action'] : null
        ];
    }
    
    /**
     * Renderizar el widget de reCAPTCHA
     * 
     * @param array $options Opciones adicionales
     * @return string HTML del widget
     */
    public static function render($options = []) {
        $siteKey = self::getSiteKey();
        
        if (empty($siteKey)) {
            if (Env::get('APP_ENV') !== 'production') {
                return '<div class="alert alert-warning">reCAPTCHA no configurado (modo desarrollo)</div>';
            }
            return '';
        }
        
        $theme = isset($options['theme']) ? $options['theme'] : 'light';
        $size = isset($options['size']) ? $options['size'] : 'normal';
        $callback = isset($options['callback']) ? $options['callback'] : '';
        
        $html = '<div class="g-recaptcha" 
                    data-sitekey="' . htmlspecialchars($siteKey) . '"
                    data-theme="' . htmlspecialchars($theme) . '"
                    data-size="' . htmlspecialchars($size) . '"';
        
        if (!empty($callback)) {
            $html .= ' data-callback="' . htmlspecialchars($callback) . '"';
        }
        
        $html .= '></div>';
        
        return $html;
    }
    
    /**
     * Incluir script de reCAPTCHA
     * 
     * @param string $lang Idioma (opcional)
     * @return string HTML del script
     */
    public static function script($lang = 'es') {
        $siteKey = self::getSiteKey();
        
        if (empty($siteKey)) {
            return '';
        }
        
        return '<script src="https://www.google.com/recaptcha/api.js?hl=' . htmlspecialchars($lang) . '" async defer></script>';
    }
}

// Inicializar automáticamente
ReCaptcha::init();

// ==================== FUNCIONES DE AYUDA ====================

/**
 * Verificar reCAPTCHA desde formulario POST
 * 
 * @return array Resultado de la verificación
 */
function verificarRecaptcha() {
    $response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
    $remoteIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    
    return ReCaptcha::verify($response, $remoteIp);
}

/**
 * Renderizar widget de reCAPTCHA
 */
function renderRecaptcha($options = []) {
    return ReCaptcha::render($options);
}

/**
 * Incluir script de reCAPTCHA
 */
function incluirScriptRecaptcha($lang = 'es') {
    return ReCaptcha::script($lang);
}

// ==================== CONSTANTES LEGACY ====================

/**
 * NOTA: Mantener por compatibilidad
 * En código nuevo, usa ReCaptcha::getSiteKey()
 */
define('RECAPTCHA_SITE_KEY', ReCaptcha::getSiteKey());