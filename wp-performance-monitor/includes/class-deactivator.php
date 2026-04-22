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
class WPM_Deactivator {
    public static function deactivate() {
        // Очистка расписаний
        wp_clear_scheduled_hook('wpm_daily_scan');
        
        // Очистка временных данных
        delete_transient('wpm_last_scan_results');
        delete_transient('wpm_performance_cache');
        
        // Примечание: данные в БД НЕ удаляем, чтобы не потерять историю
        // Если хотите удалять всё при деактивации - раскомментируйте:
        // self::cleanup_database();
    }
    
    private static function cleanup_database() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wpm_scans");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wpm_metrics");
        delete_option('wpm_options');
    }
}