<?php
/**
 * Configuration Helper Class
 * Ba Dɛre Exchange
 */

class Config {
    private static $config = [];
    private static $loaded = [];

    /**
     * Load a configuration file
     */
    private static function load($file) {
        if (isset(self::$loaded[$file])) {
            return;
        }

        $path = __DIR__ . '/settings/' . $file . '.php';
        
        if (file_exists($path)) {
            self::$config[$file] = require $path;
            self::$loaded[$file] = true;
        } else {
            throw new Exception("Configuration file not found: {$file}");
        }
    }

    /**
     * Get a configuration value using dot notation
     * 
     * Example: Config::get('core.app.name')
     * Example: Config::get('business.commission.rate')
     */
    public static function get($key, $default = null) {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        // Load the configuration file if not already loaded
        if (!isset(self::$loaded[$file])) {
            self::load($file);
        }

        // Navigate through the array using dot notation
        $value = self::$config[$file] ?? null;
        
        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Get all configuration from a file
     */
    public static function getAll($file) {
        if (!isset(self::$loaded[$file])) {
            self::load($file);
        }

        return self::$config[$file] ?? [];
    }

    /**
     * Set a configuration value (runtime only)
     */
    public static function set($key, $value) {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset(self::$config[$file])) {
            self::$config[$file] = [];
        }

        $current = &self::$config[$file];
        
        foreach ($parts as $part) {
            if (!isset($current[$part]) || !is_array($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }
        
        $current = $value;
    }

    /**
     * Check if a configuration key exists
     */
    public static function has($key) {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset(self::$loaded[$file])) {
            try {
                self::load($file);
            } catch (Exception $e) {
                return false;
            }
        }

        $value = self::$config[$file] ?? null;
        
        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Get database credentials for the current environment
     */
    public static function getDbCredentials() {
        $environment = self::get('core.app.environment', 'production');
        $creds = self::getAll('db_cred');
        
        // Return environment-specific credentials if available
        if (isset($creds[$environment])) {
            return array_merge($creds, $creds[$environment]);
        }
        
        return $creds;
    }

    /**
     * Check if application is in debug mode
     */
    public static function isDebug() {
        return self::get('core.app.debug', false);
    }

    /**
     * Check if application is in production
     */
    public static function isProduction() {
        return self::get('core.app.environment') === 'production';
    }

    /**
     * Get app URL
     */
    public static function url($path = '') {
        $baseUrl = self::get('core.app.url');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Get upload path
     */
    public static function uploadPath($type = '') {
        $basePath = self::get('core.uploads.uploads_path');
        
        if ($type) {
            $typePath = self::get("core.uploads.{$type}_path");
            return $typePath ?: $basePath . '/' . $type;
        }
        
        return $basePath;
    }

    /**
     * Get commission rate
     */
    public static function commissionRate() {
        return self::get('business.commission.rate', 0.10);
    }

    /**
     * Get vendor commission rate
     */
    public static function vendorRate() {
        return self::get('business.commission.vendor_rate', 0.90);
    }

    /**
     * Get minimum payout amount
     */
    public static function minimumPayout() {
        return self::get('business.payouts.minimum_balance', 10.00);
    }

    /**
     * Check if feature is enabled
     */
    public static function isEnabled($feature) {
        return self::get($feature . '.enabled', false);
    }

    /**
     * Get pagination setting
     */
    public static function perPage($type = 'books') {
        return self::get("core.pagination.{$type}_per_page", 20);
    }

    /**
     * Get payment methods
     */
    public static function paymentMethods() {
        return self::get('business.payment.methods', ['mobile_money', 'visa', 'cash']);
    }

    /**
     * Get delivery methods
     */
    public static function deliveryMethods() {
        return self::get('business.delivery.methods', ['pickup', 'delivery']);
    }

    /**
     * Get supported locations (cities)
     */
    public static function cities() {
        return self::get('business.locations.major_cities', []);
    }

    /**
     * Get supported regions
     */
    public static function regions() {
        return self::get('business.locations.regions', []);
    }

    /**
     * Get book conditions
     */
    public static function bookConditions() {
        return self::get('business.books.conditions', ['like_new', 'good', 'acceptable', 'poor']);
    }

    /**
     * Get mobile money providers
     */
    public static function momoProviders() {
        return self::get('business.payment.mobile_money.providers', ['MTN', 'Vodafone', 'AirtelTigo']);
    }

    /**
     * Clear all loaded configuration (useful for testing)
     */
    public static function clear() {
        self::$config = [];
        self::$loaded = [];
    }
}
