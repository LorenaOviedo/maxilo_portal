<?php
// Configuración de Google reCAPTCHA v2

require_once __DIR__ . '/Env.php';

// Cargar variables de entorno si no están cargadas
if (!Env::has('RECAPTCHA_SITE_KEY')) {
    Env::load();
}

class ReCaptcha {
    
    private static $siteKey;
    private static $secretKey;
    
    //Inicializar reCAPTCHA
    
    public static function init() {
        self::$siteKey = Env::get('RECAPTCHA_SITE_KEY');
        self::$secretKey = Env::get('RECAPTCHA_SECRET_KEY');
        
        // Verificar que estén configuradas
        if (empty(self::$siteKey) || empty(self::$secretKey)) {
            if (Env::get('APP_ENV') !== 'production') {
                error_log("WARNING: reCAPTCHA no configurado en .env");
            }
        }
    }
    
    //Obtener Site Key (para el frontend)
    
    public static function getSiteKey() {
        if (self::$siteKey === null) {
            self::init();
        }
        return self::$siteKey;
    }
    
    //Obtener Secret Key (solo para backend)
    
    private static function getSecretKey() {
        if (self::$secretKey === null) {
            self::init();
        }
        return self::$secretKey;
    }
    
    /**
     * Verificar respuesta de RECAPTCHA
     * 
     * @param string $response Token de respuesta del cliente
     * @param string $remoteIp IP del cliente (opcional)
     * @return array Resultado de la verificación
     */
    public static function verify($response, $remoteIp = null) {
        // Verificar que se haya enviado el token de reCAPTCHA
        if (empty($response)) {
            return [
                'success' => false,
                'error' => 'Por favor completa la verificación reCAPTCHA'
            ];
        }
        
        $secretKey = self::getSecretKey();
        
        // Si no hay secret key configurada
        if (empty($secretKey)) {
            // En desarrollo permitir continuar sin reCAPTCHA
            if (Env::get('APP_ENV') !== 'production') {
                error_log("WARNING: reCAPTCHA deshabilitado en desarrollo");
                return ['success' => true];
            }
            return [
                'success' => false,
                'error' => 'reCAPTCHA no está configurado correctamente'
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
                'error' => 'Error al verificar reCAPTCHA. Por favor intenta nuevamente.'
            ];
        }
        
        $resultJson = json_decode($result, true);
        
        if (!isset($resultJson['success'])) {
            error_log("Error al verificar reCAPTCHA: Respuesta inválida de Google");
            return [
                'success' => false,
                'error' => 'Error al verificar reCAPTCHA. Por favor intenta nuevamente.'
            ];
        }
        
        if (!$resultJson['success']) {
            $errorCodes = isset($resultJson['error-codes']) ? $resultJson['error-codes'] : [];
            error_log("reCAPTCHA falló. Códigos de error: " . implode(', ', $errorCodes));
            
            return [
                'success' => false,
                'error' => 'La verificación reCAPTCHA falló. Por favor intenta nuevamente.',
                'error_codes' => $errorCodes
            ];
        }
        
        // Verificación exitosa
        return [
            'success' => true,
            'challenge_ts' => isset($resultJson['challenge_ts']) ? $resultJson['challenge_ts'] : null,
            'hostname' => isset($resultJson['hostname']) ? $resultJson['hostname'] : null
        ];
    }
    
    /**
     * Renderizar el widget de reCAPTCHA
     * 
     * @param array $options Opciones adicionales (theme, size, callback)
     * @return string HTML del widget
     */
    public static function render($options = []) {
        $siteKey = self::getSiteKey();
        
        if (empty($siteKey)) {
            if (Env::get('APP_ENV') !== 'production') {
                return '<div style="padding: 10px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; color: #856404;">⚠️ reCAPTCHA no configurado (modo desarrollo)</div>';
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
}

// Inicializar automáticamente
ReCaptcha::init();

// FUNCIONES DE AYUDA 

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
 * 
 * @param array $options Opciones (theme: light|dark, size: normal|compact)
 * @return string HTML del widget
 */
function renderRecaptcha($options = []) {
    return ReCaptcha::render($options);
}

// CONSTANTE LEGACY

//NOTA: Mantener por compatibilidad

define('RECAPTCHA_SITE_KEY', ReCaptcha::getSiteKey());
?>