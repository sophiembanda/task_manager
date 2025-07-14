<?php
class EnvLoader {
    private static $loaded = false;
    
    /**
     * Load environment variables from .env file
     */
    public static function load($file = null) {
        if (self::$loaded) {
            return; // Already loaded
        }
        
        if ($file === null) {
            $file = dirname(__DIR__) . '/.env';
        }
        
        if (!file_exists($file)) {
            throw new Exception(".env file not found at: " . $file);
        }
        
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                // Set environment variable
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get environment variable with optional default value
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        return $_ENV[$key] ?? getenv($key) ?? $default;
    }
    
    /**
     * Get environment variable as boolean
     */
    public static function getBool($key, $default = false) {
        $value = self::get($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
    }
    
    /**
     * Get environment variable as integer
     */
    public static function getInt($key, $default = 0) {
        return (int) self::get($key, $default);
    }
    
    /**
     * Check if we're in development environment
     */
    public static function isDevelopment() {
        return self::get('APP_ENV', 'production') === 'development';
    }
    
    /**
     * Check if we're in production environment
     */
    public static function isProduction() {
        return self::get('APP_ENV', 'production') === 'production';
    }
}

// Auto-load environment variables
try {
    EnvLoader::load();
} catch (Exception $e) {
    // In production, you might want to handle this differently
    if (EnvLoader::get('APP_ENV', 'production') === 'development') {
        die("Environment configuration error: " . $e->getMessage());
    }
}
?>
