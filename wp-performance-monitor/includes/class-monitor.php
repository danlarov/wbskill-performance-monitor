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
class WPM_Monitor {
    private $start_time;
    private $plugin_times = array();
    
    public function __construct() {
        $this->start_time = microtime(true);
        add_action('plugins_loaded', array($this, 'start_monitoring'), 0);
        add_action('shutdown', array($this, 'stop_monitoring'));
    }
    
    public function start_monitoring() {
        $plugins = get_option('active_plugins');
        foreach ($plugins as $plugin) {
            $plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
            if (file_exists($plugin_file)) {
                $plugin_data = get_plugin_data($plugin_file);
                $this->plugin_times[$plugin] = array(
                    'name' => $plugin_data['Name'],
                    'start' => microtime(true),
                    'queries' => 0,
                    'memory' => 0
                );
            }
        }
        
        // Подсчет запросов к БД
        add_filter('query', array($this, 'count_queries'));
    }
    
    public function count_queries($query) {
        $current_filter = current_filter();
        foreach ($this->plugin_times as $plugin => &$data) {
            $data['queries']++;
        }
        return $query;
    }
    
    public function stop_monitoring() {
        $total_time = microtime(true) - $this->start_time;
        
        foreach ($this->plugin_times as $plugin => &$data) {
            $data['time'] = microtime(true) - $data['start'];
            $data['percentage'] = ($data['time'] / $total_time) * 100;
        }
        
        $this->save_metrics();
    }
    
    private function save_metrics() {
        global $wpdb;
        
        $slow_plugins = 0;
        $threshold = get_option('wpm_options', array())['performance_threshold'] ?? 0.05;
        
        $report = array(
            'total_time' => microtime(true) - $this->start_time,
            'plugins' => $this->plugin_times,
            'timestamp' => current_time('mysql')
        );
        
        foreach ($this->plugin_times as $plugin) {
            if ($plugin['time'] > $threshold) {
                $slow_plugins++;
            }
        }
        
        $wpdb->insert(
            $wpdb->prefix . 'wpm_scans',
            array(
                'scan_time' => current_time('mysql'),
                'scan_type' => 'performance',
                'total_plugins' => count($this->plugin_times),
                'slow_plugins' => $slow_plugins,
                'total_time' => microtime(true) - $this->start_time,
                'report_data' => json_encode($report)
            )
        );
    }
    
    public function get_plugin_performance($plugin_file = null) {
        if ($plugin_file && isset($this->plugin_times[$plugin_file])) {
            return $this->plugin_times[$plugin_file];
        }
        return $this->plugin_times;
    }
    
    public function get_performance_score() {
        $total_plugins = count($this->plugin_times);
        $slow_plugins = 0;
        $total_time = 0;
        
        foreach ($this->plugin_times as $plugin) {
            if ($plugin['time'] > 0.05) {
                $slow_plugins++;
            }
            $total_time += $plugin['time'];
        }
        
        $score = 100;
        if ($total_plugins > 0) {
            $score -= ($slow_plugins / $total_plugins) * 50;
            $score -= min(50, ($total_time / 2) * 10);
        }
        
        return max(0, min(100, round($score)));
    }
}