<?php
/**
 * Language functions
 */

require_once 'config.php';
require_once 'functions.php';

/**
 * Get available languages
 *
 * @param bool $details
 * @return array
 */
function get_available_languages($details = false) {
    $language_settings = get_language_settings();
    $languages = isset($language_settings['languages']) ? $language_settings['languages'] : AVAILABLE_LANGUAGES;
    
    if (!$details) {
        return $languages;
    }
    
    // Return languages with details
    $language_details = [
        'en' => [
            'name' => 'English',
            'native_name' => 'English',
            'flag' => '🇬🇧',
            'locale' => 'en_US',
            'code' => 'en'
        ],
        'fr' => [
            'name' => 'French',
            'native_name' => 'Français',
            'flag' => '🇫🇷',
            'locale' => 'fr_FR',
            'code' => 'fr'
        ],
        'es' => [
            'name' => 'Spanish',
            'native_name' => 'Español',
            'flag' => '🇪🇸',
            'locale' => 'es_ES',
            'code' => 'es'
        ],
        'de' => [
            'name' => 'German',
            'native_name' => 'Deutsch',
            'flag' => '🇩🇪',
            'locale' => 'de_DE',
            'code' => 'de'
        ],
        'it' => [
            'name' => 'Italian',
            'native_name' => 'Italiano',
            'flag' => '🇮🇹',
            'locale' => 'it_IT',
            'code' => 'it'
        ],
        'ru' => [
            'name' => 'Russian',
            'native_name' => 'Русский',
            'flag' => '🇷🇺',
            'locale' => 'ru_RU',
            'code' => 'ru'
        ],
        'sv' => [
            'name' => 'Swedish',
            'native_name' => 'Svenska',
            'flag' => '🇸🇪',
            'locale' => 'sv_SE',
            'code' => 'sv'
        ],
        'zh' => [
            'name' => 'Chinese',
            'native_name' => '中文',
            'flag' => '🇨🇳',
            'locale' => 'zh_CN',
            'code' => 'zh'
        ],
        'ja' => [
            'name' => 'Japanese',
            'native_name' => '日本語',
            'flag' => '🇯🇵',
            'locale' => 'ja_JP',
            'code' => 'ja'
        ],
        'ko' => [
            'name' => 'Korean',
            'native_name' => '한국어',
            'flag' => '🇰🇷',
            'locale' => 'ko_KR',
            'code' => 'ko'
        ],
        'ar' => [
            'name' => 'Arabic',
            'native_name' => 'العربية',
            'flag' => '🇸🇦',
            'locale' => 'ar_SA',
            'code' => 'ar'
        ],
        'pt' => [
            'name' => 'Portuguese',
            'native_name' => 'Português',
            'flag' => '🇵🇹',
            'locale' => 'pt_PT',
            'code' => 'pt'
        ],
        'nl' => [
            'name' => 'Dutch',
            'native_name' => 'Nederlands',
            'flag' => '🇳🇱',
            'locale' => 'nl_NL',
            'code' => 'nl'
        ]
    ];
    
    $result = [];
    
    foreach ($languages as $lang_code) {
        if (isset($language_details[$lang_code])) {
            $result[$lang_code] = $language_details[$lang_code];
        } else {
            // Fallback for custom languages
            $result[$lang_code] = [
                'name' => strtoupper($lang_code),
                'native_name' => strtoupper($lang_code),
                'flag' => '🌐',
                'locale' => $lang_code . '_' . strtoupper($lang_code),
                'code' => $lang_code
            ];
        }
    }
    
    return $result;
}

/**
 * Get active languages
 *
 * @return array
 */
function get_active_languages() {
    $language_settings = get_language_settings();
    return isset($language_settings['active_languages']) ? $language_settings['active_languages'] : [DEFAULT_LANGUAGE];
}

/**
 * Create language switcher
 *
 * @param string $style
 * @return string
 */
function create_language_switcher($style = 'dropdown') {
    $active_languages = get_active_languages();
    $language_details = get_available_languages(true);
    $current_lang = defined('CURRENT_LANG') ? CURRENT_LANG : DEFAULT_LANGUAGE;
    
    if (count($active_languages) <= 1) {
        return '';
    }
    
    $output = '';
    
    switch ($style) {
        case 'dropdown':
            $output .= '<div class="dropdown language-switcher">';
            $output .= '<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="languageSwitcher" data-bs-toggle="dropdown" aria-expanded="false">';
            $output .= isset($language_details[$current_lang]) ? $language_details[$current_lang]['flag'] . ' ' . $language_details[$current_lang]['name'] : strtoupper($current_lang);
            $output .= '</button>';
            $output .= '<ul class="dropdown-menu" aria-labelledby="languageSwitcher">';
            
            foreach ($active_languages as $lang_code) {
                if ($lang_code === $current_lang) {
                    continue;
                }
                
                $url = get_language_url($lang_code);
                $lang_name = isset($language_details[$lang_code]) ? $language_details[$lang_code]['flag'] . ' ' . $language_details[$lang_code]['name'] : strtoupper($lang_code);
                
                $output .= '<li><a class="dropdown-item" href="' . $url . '">' . $lang_name . '</a></li>';
            }
            
            $output .= '</ul>';
            $output .= '</div>';
            break;
        
        case 'flags':
            $output .= '<div class="language-switcher-flags">';
            
            foreach ($active_languages as $lang_code) {
                $url = get_language_url($lang_code);
                $flag = isset($language_details[$lang_code]) ? $language_details[$lang_code]['flag'] : '🌐';
                $lang_name = isset($language_details[$lang_code]) ? $language_details[$lang_code]['name'] : strtoupper($lang_code);
                $active_class = $lang_code === $current_lang ? ' active' : '';
                
                $output .= '<a href="' . $url . '" class="language-flag' . $active_class . '" title="' . $lang_name . '">' . $flag . '</a>';
            }
            
            $output .= '</div>';
            break;
        
        case 'links':
            $output .= '<div class="language-switcher-links">';
            
            foreach ($active_languages as $lang_code) {
                $url = get_language_url($lang_code);
                $lang_name = isset($language_details[$lang_code]) ? $language_details[$lang_code]['name'] : strtoupper($lang_code);
                $active_class = $lang_code === $current_lang ? ' active' : '';
                
                $output .= '<a href="' . $url . '" class="language-link' . $active_class . '">' . $lang_name . '</a>';
            }
            
            $output .= '</div>';
            break;
        
        default:
            // Invalid style
            break;
    }
    
    return $output;
}

/**
 * Detect language from URL
 *
 * @param array $path_parts
 * @return string
 */
function detect_language_from_url($path_parts) {
    $language_settings = get_language_settings();
    $language_in_url = isset($language_settings['language_in_url']) ? $language_settings['language_in_url'] : true;
    
    // If language is not in URL or URL is empty, return default language
    if (!$language_in_url || empty($path_parts[0])) {
        return DEFAULT_LANGUAGE;
    }
    
    // Check if the first segment is a valid language code
    if (in_array($path_parts[0], get_available_languages())) {
        return $path_parts[0];
    }
    
    // Fallback to default language
    return DEFAULT_LANGUAGE;
}

/**
 * Get language URL
 *
 * @param string $language
 * @return string
 */
function get_language_url($language) {
    $current_uri = $_SERVER['REQUEST_URI'];
    $language_settings = get_language_settings();
    $language_in_url = isset($language_settings['language_in_url']) ? $language_settings['language_in_url'] : true;
    
    // If language is not in URL, return site URL with language code
    if (!$language_in_url) {
        return get_site_url() . '/' . $language;
    }
    
    // Get current language
    $current_lang = defined('CURRENT_LANG') ? CURRENT_LANG : DEFAULT_LANGUAGE;
    
    // If the URL already has a language code, replace it
    if (strpos($current_uri, '/' . $current_lang . '/') === 0) {
        return str_replace('/' . $current_lang . '/', '/' . $language . '/', $current_uri);
    } else if (strpos($current_uri, '/' . $current_lang) === 0) {
        return str_replace('/' . $current_lang, '/' . $language, $current_uri);
    }
    
    // Add language code to URL
    return get_site_url() . '/' . $language . $current_uri;
}

/**
 * Translate a string
 *
 * @param string $string
 * @param string $language
 * @return string
 */
function translate($string, $language = null) {
    // Use current language if not specified
    if ($language === null) {
        $language = defined('CURRENT_LANG') ? CURRENT_LANG : DEFAULT_LANGUAGE;
    }
    
    // Get translations
    $translations = get_translations();
    
    // Get string hash
    $string_hash = md5($string);
    
    // Check if translation exists
    if (isset($translations[$string_hash][$language])) {
        return $translations[$string_hash][$language];
    }
    
    // Fall back to default language if translation not available
    if (isset($translations[$string_hash][DEFAULT_LANGUAGE])) {
        return $translations[$string_hash][DEFAULT_LANGUAGE];
    }
    
    // Return original string if all else fails
    return $string;
}

/**
 * Get all translations
 *
 * @return array
 */
function get_translations() {
    $translations_file = STORAGE_PATH . '/translations.json';
    
    if (file_exists($translations_file)) {
        $translations = read_json_file($translations_file);
        
        if (is_array($translations)) {
            return $translations;
        }
    }
    
    return [];
}

/**
 * Save a translation
 *
 * @param string $string
 * @param string $translation
 * @param string $language
 * @return bool
 */
function save_translation($string, $translation, $language) {
    $translations_file = STORAGE_PATH . '/translations.json';
    $translations = get_translations();
    
    // Get string hash
    $string_hash = md5($string);
    
    // Add or update translation
    if (!isset($translations[$string_hash])) {
        $translations[$string_hash] = [
            'original' => $string
        ];
    }
    
    $translations[$string_hash][$language] = $translation;
    
    return write_json_file($translations_file, $translations);
}

/**
 * Import translations from file
 *
 * @param string $file
 * @return bool
 */
function import_translations($file) {
    if (!file_exists($file)) {
        return false;
    }
    
    $imported_translations = read_json_file($file);
    
    if (!is_array($imported_translations)) {
        return false;
    }
    
    $translations_file = STORAGE_PATH . '/translations.json';
    $translations = get_translations();
    
    // Merge translations
    $translations = array_merge($translations, $imported_translations);
    
    return write_json_file($translations_file, $translations);
}

/**
 * Export translations to file
 *
 * @param string $file
 * @return bool
 */
function export_translations($file) {
    $translations = get_translations();
    
    return write_json_file($file, $translations);
}
