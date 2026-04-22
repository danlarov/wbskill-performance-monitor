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
class WPM_Cron_Jobs {
    
    public function __construct() {
        add_action('wpm_auto_scan', array($this, 'run_auto_scan'));
        add_action('wpm_cleanup_old_logs', array($this, 'cleanup_old_logs'));
    }
    
    public function run_auto_scan() {
        $options = get_option('wpm_options', array());
        
        if ($options['auto_scan_enabled'] !== 'yes') {
            return;
        }
        
        $scanner = new WPM_Scanner();
        $result = $scanner->start_full_scan();
        
        $this->add_log('auto_scan', 'Auto scan completed');
        
        if ($options['email_reports'] === 'yes') {
            $this->send_report_email($result);
        }
    }
    
    public function cleanup_old_logs() {
        global $wpdb;
        $options = get_option('wpm_options', array());
        $keep_days = isset($options['keep_logs_days']) ? intval($options['keep_logs_days']) : 30;
        
        $wpdb->query("DELETE FROM {$wpdb->prefix}wpm_logs WHERE log_time < DATE_SUB(NOW(), INTERVAL $keep_days DAY)");
        $wpdb->query("DELETE FROM {$wpdb->prefix}wpm_scans WHERE scan_time < DATE_SUB(NOW(), INTERVAL $keep_days DAY)");
    }
    
    private function add_log($type, $message) {
        $options = get_option('wpm_options', array());
        
        if ($options['enable_logging'] !== 'yes') {
            return;
        }
        
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'wpm_logs',
            array(
                'log_time' => current_time('mysql', 1),
                'log_type' => $type,
                'log_message' => $message
            )
        );
    }
    
    private function send_report_email($data) {
        $options = get_option('wpm_options', array());
        $to = isset($options['admin_email']) ? $options['admin_email'] : get_option('admin_email');
        
        $subject = __('WP Performance Monitor - Daily Report', 'wp-performance-monitor');
        
        $plugins_count = isset($data['plugins']) ? count($data['plugins']) : 0;
        $slow_plugins = 0;
        if (isset($data['plugins'])) {
            foreach ($data['plugins'] as $plugin) {
                if ($plugin['performance_score'] < 70) {
                    $slow_plugins++;
                }
            }
        }
        
        $message = "<html><body>";
        $message .= "<h2>" . __('Performance Report', 'wp-performance-monitor') . "</h2>";
        $message .= "<p><strong>" . __('Date', 'wp-performance-monitor') . ":</strong> " . current_time('mysql') . "</p>";
        $message .= "<p><strong>" . __('Total Plugins', 'wp-performance-monitor') . ":</strong> " . $plugins_count . "</p>";
        $message .= "<p><strong>" . __('Plugins with Issues', 'wp-performance-monitor') . ":</strong> " . $slow_plugins . "</p>";
        $message .= "<p><a href='" . admin_url('admin.php?page=wpm-dashboard') . "'>" . __('View Full Report', 'wp-performance-monitor') . "</a></p>";
        $message .= "</body></html>";
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    public function get_all_cron_jobs() {
        $cron_jobs = _get_cron_array();
        $jobs = array();
        
        if ($cron_jobs) {
            foreach ($cron_jobs as $timestamp => $cron) {
                foreach ($cron as $hook => $details) {
                    foreach ($details as $key => $data) {
                        if (strpos($hook, 'wpm_') === 0) {
                            $jobs[] = array(
                                'hook' => $hook,
                                'next_run' => $timestamp,
                                'next_run_human' => human_time_diff($timestamp, current_time('timestamp')),
                                'schedule' => isset($data['schedule']) ? $data['schedule'] : 'once',
                                'interval' => isset($data['interval']) ? $data['interval'] : 0
                            );
                        }
                    }
                }
            }
        }
        
        usort($jobs, function($a, $b) {
            return $a['next_run'] - $b['next_run'];
        });
        
        return $jobs;
    }
}

// Add custom cron schedules
add_filter('cron_schedules', 'wpm_cron_schedules');
function wpm_cron_schedules($schedules) {
    $schedules['weekly'] = array(
        'interval' => WEEK_IN_SECONDS,
        'display' => __('Once Weekly', 'wp-performance-monitor')
    );
    $schedules['monthly'] = array(
        'interval' => MONTH_IN_SECONDS,
        'display' => __('Once Monthly', 'wp-performance-monitor')
    );
    return $schedules;
}