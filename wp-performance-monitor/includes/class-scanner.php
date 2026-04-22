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
class WPM_Scanner {
    private $scan_results = array();
    private $is_scanning = false;
    
    public function init() {
        if (get_transient('wpm_is_scanning')) {
            $this->is_scanning = true;
        }
    }
    
    public function start_full_scan() {
        set_transient('wpm_is_scanning', true, 3600);
        
        $this->scan_results = array(
            'plugins' => $this->scan_plugins(),
            'theme' => $this->scan_theme(),
            'database' => $this->scan_database(),
            'assets' => $this->scan_assets(),
            'hooks' => $this->scan_hooks(),
            'cron' => $this->scan_cron_jobs(),
            'options' => $this->scan_options()
        );
        
        $this->generate_report();
        delete_transient('wpm_is_scanning');
        
        return $this->scan_results;
    }
    
    private function scan_plugins() {
        $results = array();
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            $is_active = in_array($plugin_file, $active_plugins);
            $performance = $this->analyze_plugin_performance($plugin_file);
            
            $results[] = array(
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
                'active' => $is_active,
                'size' => $this->get_folder_size(WP_PLUGIN_DIR . '/' . dirname($plugin_file)),
                'performance_score' => $performance['score'],
                'issues' => $performance['issues'],
                'suggestions' => $performance['suggestions']
            );
        }
        
        return $results;
    }
    
    private function analyze_plugin_performance($plugin_file) {
        $score = 100;
        $issues = array();
        $suggestions = array();
        
        $plugin_path = WP_PLUGIN_DIR . '/' . dirname($plugin_file);
        
        if (!file_exists($plugin_path)) {
            return array('score' => 100, 'issues' => array(), 'suggestions' => array());
        }
        
        $js_files = glob($plugin_path . '/*.js');
        $css_files = glob($plugin_path . '/*.css');
        
        if (count($js_files) > 10) {
            $score -= 20;
            $issues[] = __('Too many JavaScript files', 'wp-performance-monitor');
            $suggestions[] = __('Combine and minify JS files', 'wp-performance-monitor');
        }
        
        if (count($css_files) > 5) {
            $score -= 10;
            $issues[] = __('Too many CSS files', 'wp-performance-monitor');
            $suggestions[] = __('Combine CSS files', 'wp-performance-monitor');
        }
        
        $plugin_main_file = $plugin_path . '/' . $plugin_file;
        if (file_exists($plugin_main_file)) {
            $plugin_content = file_get_contents($plugin_main_file);
            $query_count = substr_count($plugin_content, '$wpdb->');
            
            if ($query_count > 50) {
                $score -= 15;
                $issues[] = __('Excessive database queries', 'wp-performance-monitor');
                $suggestions[] = __('Consider caching database results', 'wp-performance-monitor');
            }
            
            $external_requests = substr_count($plugin_content, 'wp_remote_');
            if ($external_requests > 5) {
                $score -= 10;
                $issues[] = __('Too many external HTTP requests', 'wp-performance-monitor');
                $suggestions[] = __('Cache external API responses', 'wp-performance-monitor');
            }
        }
        
        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions
        );
    }
    
    private function scan_theme() {
        $theme = wp_get_theme();
        $theme_path = get_template_directory();
        
        return array(
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'author' => $theme->get('Author'),
            'size' => $this->get_folder_size($theme_path),
            'performance_score' => $this->analyze_theme_performance($theme_path),
            'has_child_theme' => is_child_theme()
        );
    }
    
    private function analyze_theme_performance($theme_path) {
        $score = 100;
        
        $functions_file = $theme_path . '/functions.php';
        if (file_exists($functions_file)) {
            $content = file_get_contents($functions_file);
            $hook_count = substr_count($content, 'add_action') + substr_count($content, 'add_filter');
            
            if ($hook_count > 20) {
                $score -= 15;
            }
        }
        
        return max(0, $score);
    }
    
    private function scan_database() {
        global $wpdb;
        
        $results = array(
            'total_size' => 0,
            'tables' => array(),
            'revisions' => 0,
            'autoload_size' => 0,
            'transients' => 0,
            'orphaned_data' => array()
        );
        
        $tables = $wpdb->get_results("SHOW TABLE STATUS");
        if ($tables) {
            foreach ($tables as $table) {
                if (strpos($table->Name, $wpdb->prefix) === 0) {
                    $size = ($table->Data_length + $table->Index_length) / 1024 / 1024;
                    $results['total_size'] += $size;
                    $results['tables'][] = array(
                        'name' => $table->Name,
                        'size_mb' => round($size, 2),
                        'rows' => $table->Rows
                    );
                }
            }
        }
        $results['total_size'] = round($results['total_size'], 2);
        
        $results['revisions'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'");
        
        $autoload_data = $wpdb->get_results("SELECT option_name, LENGTH(option_value) as size FROM {$wpdb->options} WHERE autoload = 'yes'");
        $autoload_total = 0;
        if ($autoload_data) {
            foreach ($autoload_data as $option) {
                $autoload_total += $option->size;
            }
        }
        $results['autoload_size'] = round($autoload_total / 1024, 2);
        
        $results['transients'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
        
        return $results;
    }
    
    private function scan_assets() {
        global $wp_scripts, $wp_styles;
        
        $assets = array(
            'css' => array(),
            'js' => array(),
            'total_size' => 0
        );
        
        if ($wp_styles && !empty($wp_styles->queue)) {
            foreach ($wp_styles->queue as $handle) {
                $style = isset($wp_styles->registered[$handle]) ? $wp_styles->registered[$handle] : null;
                if ($style && !empty($style->src)) {
                    $size = $this->get_file_size($style->src);
                    $assets['css'][] = array(
                        'handle' => $handle,
                        'src' => $style->src,
                        'size' => $size
                    );
                    if (is_numeric($size)) {
                        $assets['total_size'] += $size;
                    }
                }
            }
        }
        
        if ($wp_scripts && !empty($wp_scripts->queue)) {
            foreach ($wp_scripts->queue as $handle) {
                $script = isset($wp_scripts->registered[$handle]) ? $wp_scripts->registered[$handle] : null;
                if ($script && !empty($script->src)) {
                    $size = $this->get_file_size($script->src);
                    $assets['js'][] = array(
                        'handle' => $handle,
                        'src' => $script->src,
                        'size' => $size
                    );
                    if (is_numeric($size)) {
                        $assets['total_size'] += $size;
                    }
                }
            }
        }
        
        return $assets;
    }
    
    private function get_file_size($url) {
        $path = str_replace(home_url(), ABSPATH, $url);
        if (file_exists($path)) {
            return round(filesize($path) / 1024, 2);
        }
        return 'unknown';
    }
    
    private function scan_hooks() {
        global $wp_filter;
        
        $hooks = array();
        $total_hooks = 0;
        
        foreach ($wp_filter as $hook_name => $hook_obj) {
            $callbacks = $hook_obj->callbacks;
            $hook_count = 0;
            
            foreach ($callbacks as $priority_callbacks) {
                $hook_count += count($priority_callbacks);
            }
            
            if ($hook_count > 10) {
                $hooks[] = array(
                    'name' => $hook_name,
                    'callback_count' => $hook_count,
                    'priority' => array_keys($callbacks)
                );
            }
            
            $total_hooks += $hook_count;
        }
        
        return array(
            'total_hooks' => $total_hooks,
            'heavy_hooks' => $hooks
        );
    }
    
    private function scan_cron_jobs() {
        $cron_jobs = _get_cron_array();
        $jobs = array();
        
        if ($cron_jobs) {
            foreach ($cron_jobs as $timestamp => $cron) {
                foreach ($cron as $hook => $details) {
                    foreach ($details as $key => $data) {
                        $jobs[] = array(
                            'hook' => $hook,
                            'next_run' => $timestamp,
                            'schedule' => isset($data['schedule']) ? $data['schedule'] : 'once',
                            'args' => isset($data['args']) ? $data['args'] : array()
                        );
                    }
                }
            }
        }
        
        return $jobs;
    }
    
    private function scan_options() {
        global $wpdb;
        
        return array(
            'total_options' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options}"),
            'autoload_options' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE autoload = 'yes'"),
            'orphaned_options' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_plugin_%' AND autoload = 'yes'")
        );
    }
    
    private function get_folder_size($dir) {
        $size = 0;
        if (!is_dir($dir)) return 0;
        
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return round($size / 1024 / 1024, 2);
    }
    
    private function generate_report() {
        global $wpdb;
        
        $slow_plugins = 0;
        foreach ($this->scan_results['plugins'] as $plugin) {
            if ($plugin['performance_score'] < 70) {
                $slow_plugins++;
            }
        }
        
        $wpdb->insert(
            $wpdb->prefix . 'wpm_scans',
            array(
                'scan_time' => current_time('mysql', 1),
                'scan_type' => 'full',
                'total_plugins' => count($this->scan_results['plugins']),
                'slow_plugins' => $slow_plugins,
                'total_time' => 0,
                'report_data' => json_encode($this->scan_results)
            )
        );
    }
    
    public function get_last_scan() {
        global $wpdb;
        $last_scan = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wpm_scans ORDER BY scan_time DESC LIMIT 1");
        
        if ($last_scan && !empty($last_scan->report_data)) {
            $report_data = json_decode($last_scan->report_data, true);
            if (is_array($report_data)) {
                $last_scan->report_data = $report_data;
            }
        }
        
        return $last_scan;
    }
    
    public static function get_last_scan_static() {
        $scanner = new self();
        return $scanner->get_last_scan();
    }
}