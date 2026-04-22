<?php
/**
 * Performance Monitor
 *
 * @package     Performance_Monitor
 * @author      Daniel Larin
 * @copyright   2026 Daniel Larin
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Author:      Daniel Larin
 * Author URI:  https://wbskill.ru
 * Email:       camlife73@gmail.com
 * Plugin URI:  https://github.com/danlarov/wp-performance-monitor
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class WPM_Assets_Analyzer {
    
    private $assets = array(
        'css' => array(),
        'js' => array(),
        'images' => array(),
        'fonts' => array()
    );
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'capture_assets'), 9999);
        add_action('admin_enqueue_scripts', array($this, 'capture_admin_assets'), 9999);
    }
    
    /**
     * Захват ассетов на фронтенде
     */
    public function capture_assets() {
        global $wp_scripts, $wp_styles;
        
        $this->analyze_styles($wp_styles);
        $this->analyze_scripts($wp_scripts);
    }
    
    /**
     * Захват ассетов в админке
     */
    public function capture_admin_assets() {
        global $wp_scripts, $wp_styles;
        
        $this->analyze_styles($wp_styles);
        $this->analyze_scripts($wp_scripts);
    }
    
    /**
     * Анализ CSS файлов
     */
    private function analyze_styles($wp_styles) {
        if (!$wp_styles || !$wp_styles->queue) {
            return;
        }
        
        foreach ($wp_styles->queue as $handle) {
            $style = $wp_styles->registered[$handle] ?? null;
            if (!$style) continue;
            
            $src = $style->src;
            if (!$src) continue;
            
            $size = $this->get_asset_size($src);
            $this->assets['css'][] = array(
                'handle' => $handle,
                'src' => $src,
                'size_kb' => $size,
                'deps' => $style->deps,
                'media' => $style->args ?? 'all',
                'is_inline' => false
            );
        }
        
        // Поиск inline стилей
        $this->find_inline_styles();
    }
    
    /**
     * Анализ JS файлов
     */
    private function analyze_scripts($wp_scripts) {
        if (!$wp_scripts || !$wp_scripts->queue) {
            return;
        }
        
        foreach ($wp_scripts->queue as $handle) {
            $script = $wp_scripts->registered[$handle] ?? null;
            if (!$script) continue;
            
            $src = $script->src;
            if (!$src) continue;
            
            $size = $this->get_asset_size($src);
            $this->assets['js'][] = array(
                'handle' => $handle,
                'src' => $src,
                'size_kb' => $size,
                'deps' => $script->deps,
                'in_footer' => $script->extra['group'] ?? 0,
                'is_inline' => false
            );
        }
        
        // Поиск inline скриптов
        $this->find_inline_scripts();
    }
    
    /**
     * Поиск inline стилей
     */
    private function find_inline_styles() {
        $content = ob_get_contents();
        if ($content) {
            preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $content, $matches);
            foreach ($matches[1] as $inline_css) {
                $size = strlen($inline_css) / 1024;
                if ($size > 1) { // Только большие inline стили
                    $this->assets['css'][] = array(
                        'handle' => 'inline_' . uniqid(),
                        'src' => 'inline',
                        'size_kb' => round($size, 2),
                        'deps' => array(),
                        'media' => 'all',
                        'is_inline' => true
                    );
                }
            }
        }
    }
    
    /**
     * Поиск inline скриптов
     */
    private function find_inline_scripts() {
        $content = ob_get_contents();
        if ($content) {
            preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $content, $matches);
            foreach ($matches[1] as $inline_js) {
                if (strpos($inline_js, 'var') !== false || strlen($inline_js) > 500) {
                    $size = strlen($inline_js) / 1024;
                    $this->assets['js'][] = array(
                        'handle' => 'inline_' . uniqid(),
                        'src' => 'inline',
                        'size_kb' => round($size, 2),
                        'deps' => array(),
                        'in_footer' => 1,
                        'is_inline' => true
                    );
                }
            }
        }
    }
    
    /**
     * Получение размера ассета
     */
    private function get_asset_size($url) {
        // Пропускаем внешние URL
        if (strpos($url, 'http') === 0 && strpos($url, home_url()) === false) {
            return 'external';
        }
        
        // Преобразуем URL в путь
        $path = str_replace(home_url(), ABSPATH, $url);
        $path = str_replace(site_url(), ABSPATH, $path);
        
        if (file_exists($path)) {
            $size = filesize($path) / 1024;
            return round($size, 2);
        }
        
        return 'not_found';
    }
    
    /**
     * Получение всех ассетов
     */
    public function get_all_assets() {
        return $this->assets;
    }
    
    /**
     * Получение статистики по ассетам
     */
    public function get_assets_stats() {
        $stats = array(
            'total_css' => 0,
            'total_js' => 0,
            'total_images' => 0,
            'total_fonts' => 0,
            'total_size_css' => 0,
            'total_size_js' => 0,
            'total_size_images' => 0,
            'total_size_fonts' => 0,
            'external_css' => 0,
            'external_js' => 0,
            'inline_css' => 0,
            'inline_js' => 0
        );
        
        foreach ($this->assets['css'] as $css) {
            if ($css['size_kb'] !== 'external' && $css['size_kb'] !== 'not_found') {
                $stats['total_size_css'] += $css['size_kb'];
            }
            if ($css['src'] === 'external') {
                $stats['external_css']++;
            }
            if (isset($css['is_inline']) && $css['is_inline']) {
                $stats['inline_css']++;
            }
            $stats['total_css']++;
        }
        
        foreach ($this->assets['js'] as $js) {
            if ($js['size_kb'] !== 'external' && $js['size_kb'] !== 'not_found') {
                $stats['total_size_js'] += $js['size_kb'];
            }
            if ($js['src'] === 'external') {
                $stats['external_js']++;
            }
            if (isset($js['is_inline']) && $js['is_inline']) {
                $stats['inline_js']++;
            }
            $stats['total_js']++;
        }
        
        $stats['total_size_css'] = round($stats['total_size_css'], 2);
        $stats['total_size_js'] = round($stats['total_size_js'], 2);
        
        return $stats;
    }
    
    /**
     * Сканирование медиа-библиотеки
     */
    public function scan_media_library() {
        global $wpdb;
        
        $images = $wpdb->get_results("
            SELECT ID, post_title, guid 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            ORDER BY ID DESC
            LIMIT 100
        ");
        
        $results = array();
        foreach ($images as $image) {
            $file_path = get_attached_file($image->ID);
            if (file_exists($file_path)) {
                $size = filesize($file_path) / 1024 / 1024;
                $results[] = array(
                    'id' => $image->ID,
                    'title' => $image->post_title,
                    'url' => $image->guid,
                    'size_mb' => round($size, 2),
                    'dimensions' => $this->get_image_dimensions($file_path)
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Получение размеров изображения
     */
    private function get_image_dimensions($file_path) {
        $size = getimagesize($file_path);
        if ($size) {
            return $size[0] . 'x' . $size[1];
        }
        return 'unknown';
    }
    
    /**
     * Рекомендации по оптимизации ассетов
     */
    public function get_optimization_recommendations() {
        $stats = $this->get_assets_stats();
        $recommendations = array();
        
        if ($stats['total_size_css'] > 100) {
            $recommendations[] = array(
                'type' => 'css',
                'severity' => 'high',
                'message' => sprintf(__('CSS files total size is %.2f KB. Consider minifying and combining CSS files.', 'wp-performance-monitor'), $stats['total_size_css']),
                'action' => 'minify_css'
            );
        }
        
        if ($stats['total_size_js'] > 200) {
            $recommendations[] = array(
                'type' => 'js',
                'severity' => 'high',
                'message' => sprintf(__('JavaScript files total size is %.2f KB. Consider minifying and deferring JS.', 'wp-performance-monitor'), $stats['total_size_js']),
                'action' => 'minify_js'
            );
        }
        
        if ($stats['external_css'] > 3) {
            $recommendations[] = array(
                'type' => 'css',
                'severity' => 'medium',
                'message' => sprintf(__('You have %d external CSS files. Each external request adds latency.', 'wp-performance-monitor'), $stats['external_css']),
                'action' => 'combine_external'
            );
        }
        
        if ($stats['inline_css'] > 0) {
            $recommendations[] = array(
                'type' => 'css',
                'severity' => 'low',
                'message' => sprintf(__('Found %d inline CSS blocks. Move large inline styles to external files.', 'wp-performance-monitor'), $stats['inline_css']),
                'action' => 'extract_inline'
            );
        }
        
        return $recommendations;
    }
}