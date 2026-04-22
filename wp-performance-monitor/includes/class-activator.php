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
class WPM_Activator {
    public static function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_scans = $wpdb->prefix . 'wpm_scans';
        $sql_scans = "CREATE TABLE IF NOT EXISTS $table_scans (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            scan_time datetime DEFAULT CURRENT_TIMESTAMP,
            scan_type varchar(50) NOT NULL,
            total_plugins int DEFAULT 0,
            slow_plugins int DEFAULT 0,
            total_time float DEFAULT 0,
            report_data longtext,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $table_logs = $wpdb->prefix . 'wpm_logs';
        $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            log_time datetime DEFAULT CURRENT_TIMESTAMP,
            log_type varchar(50) NOT NULL,
            log_message text,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_scans);
        dbDelta($sql_logs);
        
        $default_options = array(
            'auto_scan_enabled' => 'yes',
            'auto_scan_frequency' => 'daily',
            'email_reports' => 'no',
            'admin_email' => get_option('admin_email'),
            'scan_plugins' => 'yes',
            'scan_theme' => 'yes',
            'scan_database' => 'yes',
            'performance_threshold' => 0.05,
            'enable_logging' => 'yes',
            'keep_logs_days' => 30,
            'dashboard_widget' => 'yes',
            'admin_bar_menu' => 'yes'
        );
        
        add_option('wpm_options', $default_options);
        
        self::schedule_cron_jobs();
    }
    
    public static function schedule_cron_jobs() {
        $options = get_option('wpm_options', array());
        
        if (isset($options['auto_scan_enabled']) && $options['auto_scan_enabled'] === 'yes') {
            $frequency = isset($options['auto_scan_frequency']) ? $options['auto_scan_frequency'] : 'daily';
            
            if (!wp_next_scheduled('wpm_auto_scan')) {
                wp_schedule_event(time(), $frequency, 'wpm_auto_scan');
            }
        }
    }
}