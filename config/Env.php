<?php
//Gestor de Variables de Entorno
//Carga y gestiona variables de entorno desde archivo .env


class Env {
    
    //Variables de entorno cargadas
    private static $variables = [];
    
    //Indica si ya se cargó el archivo .env
     
    private static $loaded = false;
    
    /**
     * Cargar variables desde archivo .env
     * 
     * @param string $path Ruta al archivo .env
     * @return bool True si se cargó correctamente
     */
    public static function load($path = null) {
        // Si ya se cargó, no se vuelve a cargar
        if (self::$loaded) {
            return true;
        }
        
        // Determinar ruta del archivo .env
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }
        
        // Verificar que existe el archivo
        if (!file_exists($path)) {
            // En producción, no mostrar error detallado
            if (self::get('APP_ENV') === 'production') {
                error_log("Archivo .env no encontrado en: " . $path);
                return false;
            }
            throw new Exception("Archivo .env no encontrado. Copia .env.example como .env y configura tus variables.");
        }
        
        // Leer archivo línea por línea
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios y líneas vacías
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Separar clave=valor
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                
                $key = trim($key);
                $value = trim($value);
                
                // Remover comillas
                $value = self::removeQuotes($value);
                
                // Guardar en array y en $_ENV
                self::$variables[$key] = $value;
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$loaded = true;
        return true;
    }
    
    /**
     * Obtener valor de una variable de entorno
     * 
     * @param string $key Nombre de la variable
     * @param mixed $default Valor por defecto si no existe
     * @return mixed Valor de la variable
     */
    public static function get($key, $default = null) {
        // Intentar obtener de diferentes fuentes
        if (isset(self::$variables[$key])) {
            return self::$variables[$key];
        }
        
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        return $default;
    }
    
    //Obtener valor como string
    
    public static function getString($key, $default = '') {
        return (string) self::get($key, $default);
    }
    
    //Obtener valor como integer
    
    public static function getInt($key, $default = 0) {
        return (int) self::get($key, $default);
    }
    
    //Obtener valor como boolean

    public static function getBool($key, $default = false) {
        $value = self::get($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        // Convertir strings comunes a boolean
        $value = strtolower(trim($value));
        return in_array($value, ['true', '1', 'yes', 'on'], true);
    }
    
    //Verificar si existe una variable
    
    public static function has($key) {
        return self::get($key) !== null;
    }
    
    //Obtener todas las variables (debugging solo en desarrollo ----> no usar en producción)
    
    public static function all() {
        return self::$variables;
    }
    
    //Requerir que exista una variable (lanzar excepción si no existe)
    
    public static function require($key) {
        $value = self::get($key);
        
        if ($value === null) {
            throw new Exception("Variable de entorno requerida no encontrada: $key");
        }
        
        return $value;
    }
    
    // Requerir múltiples variables
     
    public static function requireMultiple(array $keys) {
        $missing = [];
        
        foreach ($keys as $key) {
            if (self::get($key) === null) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception("Variables de entorno requeridas no encontradas: " . implode(', ', $missing));
        }
    }
    
    //Remover comillas de un valor
    
    private static function removeQuotes($value) {
        // Remover comillas dobles o simples al inicio y final
        if (
            (substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")
        ) {
            return substr($value, 1, -1);
        }
        
        return $value;
    }
    
    //Verificar configuración crítica
     
    public static function validateCriticalConfig() {
        $critical = [
            'DB_HOST',
            'DB_NAME',
            'DB_USER',
            'DB_PASS'
        ];
        
        $missing = [];
        foreach ($critical as $key) {
            if (!self::has($key) || empty(self::get($key))) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception(
                "Configuración crítica faltante. Por favor configura las siguientes variables en .env: " . 
                implode(', ', $missing)
            );
        }
        
        return true;
    }
}
