<?php
/**
 * Language Helper Functions
 * Provides translation functionality for the application
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get the current language
 * Priority: Session > Cookie > Browser > Default (en)
 */
function getCurrentLanguage() {
    // Check session
    if (isset($_SESSION['lang'])) {
        return $_SESSION['lang'];
    }
    
    // Check cookie
    if (isset($_COOKIE['lang'])) {
        $_SESSION['lang'] = $_COOKIE['lang'];
        return $_COOKIE['lang'];
    }
    
    // Check browser language
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        $supportedLangs = ['en', 'fr', 'rw', 'sw'];
        if (in_array($browserLang, $supportedLangs)) {
            $_SESSION['lang'] = $browserLang;
            return $browserLang;
        }
    }
    
    // Default to English
    $_SESSION['lang'] = 'en';
    return 'en';
}

/**
 * Set the current language
 */
function setLanguage($lang) {
    $supportedLangs = ['en', 'fr', 'rw', 'sw'];
    
    if (in_array($lang, $supportedLangs)) {
        $_SESSION['lang'] = $lang;
        setcookie('lang', $lang, time() + (86400 * 365), '/'); // 1 year
        return true;
    }
    
    return false;
}

/**
 * Load language file
 */
function loadLanguage($lang = null) {
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    
    $langFile = __DIR__ . "/{$lang}.php";
    
    if (file_exists($langFile)) {
        return include $langFile;
    }
    
    // Fallback to English
    return include __DIR__ . "/en.php";
}

/**
 * Translate a key
 * Usage: __('nav_dashboard') or __('common_save')
 */
function __($key, $default = null) {
    static $translations = null;
    
    // Load translations once
    if ($translations === null) {
        $translations = loadLanguage();
    }
    
    // Return translation or default or key
    if (isset($translations[$key])) {
        return $translations[$key];
    }
    
    return $default ?? $key;
}

/**
 * Translate with parameters
 * Usage: __p('welcome_user', ['name' => 'John'])
 * In language file: 'welcome_user' => 'Welcome, {name}!'
 */
function __p($key, $params = []) {
    $translation = __($key);
    
    foreach ($params as $param => $value) {
        $translation = str_replace('{' . $param . '}', $value, $translation);
    }
    
    return $translation;
}

/**
 * Get all supported languages
 */
function getSupportedLanguages() {
    return [
        'en' => 'English',
        'fr' => 'Français',
        'rw' => 'Kinyarwanda',
        'sw' => 'Kiswahili'
    ];
}

/**
 * Get language name
 */
function getLanguageName($lang) {
    $languages = getSupportedLanguages();
    return $languages[$lang] ?? 'Unknown';
}
