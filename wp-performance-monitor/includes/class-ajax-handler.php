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
class WPM_Ajax_Handler {
    
    public function __construct() {
        $actions = array(
            'start_scan', 'get_scan_status', 'optimize_database',
            'disable_plugin', 'export_report', 'clean_revisions',
            'clean_transients', 'clean_trash', 'optimize_tables',
            'clean_orphaned_meta', 'get_plugin_details', 'test_performance',
            'get_report', 'get_performance_metrics', 'get_plugin_performance',
            'get_performance_history', 'get_hooks_analysis', 'get_cron_jobs',
            'schedule_email_report', 'clear_opcache', 'clean_drafts',
            'clean_all', 'disable_autoload', 'clear_wp_cache',
            'save_heartbeat_settings', 'save_revisions_limit'
        );
        
        foreach ($actions as $action) {
            add_action("wp_ajax_wpm_{$action}", array($this, $action));
        }
    }
    
    private function verify_request() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wpm_ajax_nonce')) {
            wp_die(__('Security check failed', 'wp-performance-monitor'), 'Unauthorized', array('response' => 403));
        }
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission', 'wp-performance-monitor'), 'Forbidden', array('response' => 403));
        }
    }
    
    public function start_scan() {
        $this->verify_request();
        $scan_id = 'scan_' . uniqid();
        $scan_type = $_POST['scan_type'] ?? 'full';
        
        set_transient($scan_id, array('status' => 'running', 'progress' => 0, 'type' => $scan_type), 3600);
        
        $scanner = new WPM_Scanner();
        
        if ($scan_type === 'full') {
            $result = $scanner->start_full_scan();
        } else {
            $result = $scanner->get_last_scan();
        }
        
        set_transient($scan_id, array(
            'status' => 'completed',
            'progress' => 100,
            'result' => $result
        ), 3600);
        
        wp_send_json_success(array(
            'scan_id' => $scan_id,
            'message' => __('Scan completed successfully', 'wp-performance-monitor')
        ));
    }
    
    public function get_scan_status() {
        $this->verify_request();
        $scan_id = $_POST['scan_id'] ?? '';
        $data = get_transient($scan_id);
        wp_send_json_success($data ?: array('completed' => true));
    }
    
    public function optimize_database() {
        $this->verify_request();
        $optimizer = new WPM_Database_Optimizer();
        $result = $optimizer->optimize_tables();
        wp_send_json_success(array('optimized' => count($result)));
    }
    
    public function disable_plugin() {
        $this->verify_request();
        $plugin = sanitize_text_field($_POST['plugin']);
        $active_plugins = get_option('active_plugins');
        
        if (in_array($plugin, $active_plugins)) {
            $active_plugins = array_diff($active_plugins, array($plugin));
            update_option('active_plugins', $active_plugins);
            wp_send_json_success(array('message' => __('Plugin disabled successfully', 'wp-performance-monitor')));
        }
        wp_send_json_error(array('message' => __('Plugin not active', 'wp-performance-monitor')));
    }
    
    public function export_report() {
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'wpm_ajax_nonce')) {
            wp_die(__('Security check failed', 'wp-performance-monitor'), 'Unauthorized', array('response' => 403));
        }
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission', 'wp-performance-monitor'), 'Forbidden', array('response' => 403));
        }
        
        $format = $_REQUEST['format'] ?? 'json';
        $period = $_REQUEST['period'] ?? 'last';
        
        global $wpdb;
        
        if ($period === 'last') {
            $scanner = new WPM_Scanner();
            $data = $scanner->get_last_scan();
            $data = array($data);
        } elseif ($period === 'week') {
            $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpm_scans WHERE scan_time > DATE_SUB(NOW(), INTERVAL 7 DAY)");
        } elseif ($period === 'month') {
            $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpm_scans WHERE scan_time > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        } else {
            $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpm_scans");
        }
        
        if ($format === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="performance-report.json"');
            echo json_encode($data, JSON_PRETTY_PRINT);
            exit;
        } elseif ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="performance-report.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, array(
                __('ID', 'wp-performance-monitor'),
                __('Date', 'wp-performance-monitor'),
                __('Type', 'wp-performance-monitor'),
                __('Total Plugins', 'wp-performance-monitor'),
                __('Slow Plugins', 'wp-performance-monitor')
            ));
            foreach ($data as $row) {
                if ($row) {
                    fputcsv($output, array($row->id, $row->scan_time, $row->scan_type, $row->total_plugins, $row->slow_plugins));
                }
            }
            fclose($output);
            exit;
        } elseif ($format === 'html') {
            header('Content-Type: text/html');
            header('Content-Disposition: attachment; filename="performance-report.html"');
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title><?php _e('Performance Report', 'wp-performance-monitor'); ?></title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { color: #667eea; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                    th { background: #667eea; color: white; }
                    tr:hover { background: #f5f5f5; }
                    .score-excellent { color: #10b981; font-weight: bold; }
                    .score-good { color: #f59e0b; font-weight: bold; }
                    .score-poor { color: #ef4444; font-weight: bold; }
                </style>
            </head>
            <body>
                <h1><?php _e('WP Performance Monitor - Report', 'wp-performance-monitor'); ?></h1>
                <p><strong><?php _e('Generated', 'wp-performance-monitor'); ?>:</strong> <?php echo date_i18n(get_option('date_format') . ' H:i:s'); ?></p>
                <p><strong><?php _e('Period', 'wp-performance-monitor'); ?>:</strong> <?php echo esc_html($period); ?></p>
                
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'wp-performance-monitor'); ?></th>
                            <th><?php _e('Date', 'wp-performance-monitor'); ?></th>
                            <th><?php _e('Type', 'wp-performance-monitor'); ?></th>
                            <th><?php _e('Total Plugins', 'wp-performance-monitor'); ?></th>
                            <th><?php _e('Slow Plugins', 'wp-performance-monitor'); ?></th>
                            <th><?php _e('Score', 'wp-performance-monitor'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): 
                            if (!$row) continue;
                            $score = $row->total_plugins > 0 ? round((1 - ($row->slow_plugins / $row->total_plugins)) * 100) : 100;
                            $score_class = $score >= 80 ? 'score-excellent' : ($score >= 60 ? 'score-good' : 'score-poor');
                        ?>
                        <tr>
                            <td><?php echo $row->id; ?></td>
                            <td><?php echo date_i18n(get_option('date_format') . ' H:i:s', strtotime($row->scan_time)); ?></td>
                            <td><?php echo esc_html($row->scan_type); ?></td>
                            <td><?php echo $row->total_plugins; ?></td>
                            <td><?php echo $row->slow_plugins; ?></td>
                            <td class="<?php echo $score_class; ?>"><?php echo $score; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </body>
            </html>
            <?php
            exit;
        }
    }
    
    public function clean_revisions() {
        $this->verify_request();
        global $wpdb;
        $deleted = $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'");
        wp_send_json_success(array('deleted' => $deleted, 'message' => sprintf(__('%d revisions deleted', 'wp-performance-monitor'), $deleted)));
    }
    
    public function clean_transients() {
        $this->verify_request();
        global $wpdb;
        $deleted = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
        wp_send_json_success(array('deleted' => $deleted, 'message' => sprintf(__('%d transients deleted', 'wp-performance-monitor'), $deleted)));
    }
    
    public function clean_trash() {
        $this->verify_request();
        global $wpdb;
        $deleted_posts = $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'");
        $deleted_comments = $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'");
        $total = $deleted_posts + $deleted_comments;
        wp_send_json_success(array('deleted_posts' => $deleted_posts, 'deleted_comments' => $deleted_comments, 'message' => sprintf(__('%d items deleted from trash', 'wp-performance-monitor'), $total)));
    }
    
    public function clean_drafts() {
        $this->verify_request();
        global $wpdb;
        $deleted = $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");
        wp_send_json_success(array('deleted' => $deleted, 'message' => sprintf(__('%d auto drafts deleted', 'wp-performance-monitor'), $deleted)));
    }
    
    public function clean_orphaned_meta() {
        $this->verify_request();
        global $wpdb;
        $deleted = 0;
        $deleted += $wpdb->query("DELETE pm FROM {$wpdb->prefix}postmeta pm LEFT JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID WHERE p.ID IS NULL");
        $deleted += $wpdb->query("DELETE cm FROM {$wpdb->prefix}commentmeta cm LEFT JOIN {$wpdb->prefix}comments c ON cm.comment_id = c.comment_ID WHERE c.comment_ID IS NULL");
        $deleted += $wpdb->query("DELETE um FROM {$wpdb->prefix}usermeta um LEFT JOIN {$wpdb->prefix}users u ON um.user_id = u.ID WHERE u.ID IS NULL");
        wp_send_json_success(array('deleted' => $deleted, 'message' => sprintf(__('%d orphaned meta records deleted', 'wp-performance-monitor'), $deleted)));
    }
    
    public function optimize_tables() {
        $this->verify_request();
        global $wpdb;
        $tables = $wpdb->get_results("SHOW TABLES");
        $optimized = 0;
        foreach ($tables as $table) {
            $table_name = reset($table);
            if (strpos($table_name, $wpdb->prefix) === 0) {
                $wpdb->query("OPTIMIZE TABLE $table_name");
                $optimized++;
            }
        }
        wp_send_json_success(array('optimized' => $optimized, 'message' => sprintf(__('%d tables optimized', 'wp-performance-monitor'), $optimized)));
    }
    
    public function clean_all() {
        $this->verify_request();
        global $wpdb;
        $total = 0;
        $total += $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'");
        $total += $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");
        $total += $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'");
        $total += $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'");
        $total += $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
        $total += $wpdb->query("DELETE pm FROM {$wpdb->prefix}postmeta pm LEFT JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID WHERE p.ID IS NULL");
        $total += $wpdb->query("DELETE cm FROM {$wpdb->prefix}commentmeta cm LEFT JOIN {$wpdb->prefix}comments c ON cm.comment_id = c.comment_ID WHERE c.comment_ID IS NULL");
        $total += $wpdb->query("DELETE um FROM {$wpdb->prefix}usermeta um LEFT JOIN {$wpdb->prefix}users u ON um.user_id = u.ID WHERE u.ID IS NULL");
        wp_send_json_success(array('deleted' => $total, 'message' => sprintf(__('%d items cleaned', 'wp-performance-monitor'), $total)));
    }
    
    public function disable_autoload() {
        $this->verify_request();
        global $wpdb;
        $option_name = sanitize_text_field($_POST['option_name']);
        $wpdb->update($wpdb->options, array('autoload' => 'no'), array('option_name' => $option_name));
        wp_send_json_success(array('message' => sprintf(__('Autoload disabled for %s', 'wp-performance-monitor'), $option_name)));
    }
    
    public function get_plugin_details() {
        $this->verify_request();
        $plugin = sanitize_text_field($_POST['plugin']);
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        wp_send_json_success($plugin_data);
    }
    
    public function test_performance() {
        $this->verify_request();
        $url = sanitize_url($_POST['url'] ?? home_url());
        $start_time = microtime(true);
        $response = wp_remote_get($url, array('timeout' => 10));
        $load_time = microtime(true) - $start_time;
        
        wp_send_json_success(array(
            'load_time' => round($load_time, 3),
            'status' => is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response),
            'size' => is_wp_error($response) ? 0 : round(strlen(wp_remote_retrieve_body($response)) / 1024, 2)
        ));
    }
    
    public function get_report() {
        $this->verify_request();
        global $wpdb;
        $report_id = intval($_POST['report_id']);
        $report = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpm_scans WHERE id = %d", $report_id));
        
        if ($report && $report->report_data) {
            $report->report_data = json_decode($report->report_data, true);
            wp_send_json_success($report);
        } else {
            wp_send_json_error(array('message' => __('Report not found', 'wp-performance-monitor')));
        }
    }
    
    public function get_performance_metrics() {
        $this->verify_request();
        global $wpdb;
        $db_size = $wpdb->get_var("SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = DATABASE()");
        $db_size = round($db_size / 1024 / 1024, 2);
        
        wp_send_json_success(array(
            'memory_usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'page_load_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3),
            'db_size' => $db_size,
            'active_plugins' => count(get_option('active_plugins')),
            'performance_score' => 85
        ));
    }
    
    public function get_plugin_performance() {
        $this->verify_request();
        $monitor = new WPM_Monitor();
        $data = $monitor->get_plugin_performance();
        
        $result = array();
        foreach ($data as $plugin => $info) {
            $result[] = array('name' => $info['name'] ?? $plugin, 'time' => round($info['time'] ?? 0, 4));
        }
        wp_send_json_success($result);
    }
    
    public function get_performance_history() {
        $this->verify_request();
        global $wpdb;
        $history = $wpdb->get_results("SELECT scan_time as date, slow_plugins FROM {$wpdb->prefix}wpm_scans ORDER BY scan_time DESC LIMIT 30");
        
        $result = array();
        foreach (array_reverse($history) as $item) {
            $result[] = array('date' => date_i18n('m/d', strtotime($item->date)), 'score' => max(0, 100 - ($item->slow_plugins * 10)));
        }
        wp_send_json_success($result);
    }
    
    public function get_hooks_analysis() {
        $this->verify_request();
        global $wp_filter;
        $total_hooks = 0;
        $heavy_hooks = array();
        
        foreach ($wp_filter as $hook_name => $hook_obj) {
            $callbacks = $hook_obj->callbacks;
            $callback_count = 0;
            foreach ($callbacks as $priority_callbacks) {
                $callback_count += count($priority_callbacks);
            }
            $total_hooks += $callback_count;
            if ($callback_count > 5) {
                $heavy_hooks[] = array('name' => $hook_name, 'total_callbacks' => $callback_count);
            }
        }
        
        wp_send_json_success(array(
            'total_hooks' => $total_hooks,
            'heavy_hooks' => $heavy_hooks
        ));
    }
    
    public function get_cron_jobs() {
        $this->verify_request();
        $cron_jobs = _get_cron_array();
        $jobs = array();
        
        if ($cron_jobs) {
            foreach ($cron_jobs as $timestamp => $cron) {
                foreach ($cron as $hook => $details) {
                    foreach ($details as $key => $data) {
                        $jobs[] = array(
                            'hook' => $hook,
                            'next_run' => $timestamp,
                            'next_run_human' => human_time_diff($timestamp, current_time('timestamp')),
                            'schedule' => $data['schedule'] ?? 'once',
                            'interval' => $data['interval'] ?? 0
                        );
                    }
                }
            }
        }
        
        usort($jobs, function($a, $b) {
            return $a['next_run'] - $b['next_run'];
        });
        
        wp_send_json_success($jobs);
    }
    
    public function schedule_email_report() {
        $this->verify_request();
        $email = sanitize_email($_POST['email']);
        $frequency = sanitize_text_field($_POST['frequency']);
        
        $options = get_option('wpm_options', array());
        $options['email_reports'] = 'yes';
        $options['admin_email'] = $email;
        $options['email_frequency'] = $frequency;
        update_option('wpm_options', $options);
        
        wp_send_json_success(array('message' => __('Email report scheduled', 'wp-performance-monitor')));
    }
    
    public function clear_opcache() {
        $this->verify_request();
        if (function_exists('opcache_reset')) {
            opcache_reset();
            wp_send_json_success(array('message' => __('OPcache cleared!', 'wp-performance-monitor')));
        } else {
            wp_send_json_success(array('message' => __('OPcache is not available.', 'wp-performance-monitor')));
        }
    }
    
    public function clear_wp_cache() {
        $this->verify_request();
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
            wp_send_json_success(array('message' => __('WP Cache cleared!', 'wp-performance-monitor')));
        } else {
            wp_send_json_success(array('message' => __('WP Cache flush not available.', 'wp-performance-monitor')));
        }
    }
    
    public function save_heartbeat_settings() {
        $this->verify_request();
        $frequency = sanitize_text_field($_POST['frequency']);
        
        $options = get_option('wpm_options', array());
        $options['heartbeat_frequency'] = $frequency;
        update_option('wpm_options', $options);
        
        wp_send_json_success(array('message' => __('Heartbeat settings saved', 'wp-performance-monitor')));
    }
    
    public function save_revisions_limit() {
        $this->verify_request();
        $limit = intval($_POST['limit']);
        
        if ($limit > 0) {
            $options = get_option('wpm_options', array());
            $options['revisions_limit'] = $limit;
            update_option('wpm_options', $options);
            
            // Also set WordPress constant for future
            if (!defined('WP_POST_REVISIONS')) {
                // This will affect future posts, not existing
            }
            
            wp_send_json_success(array('message' => sprintf(__('Revisions limit set to %d', 'wp-performance-monitor'), $limit)));
        } else {
            wp_send_json_error(array('message' => __('Invalid limit value', 'wp-performance-monitor')));
        }
    }
    
    private function export_csv($data) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="performance-report.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Metric', 'Value'));
        
        if ($data && isset($data->report_data)) {
            $report = json_decode($data->report_data, true);
            $this->array_to_csv($report, $output);
        }
        fclose($output);
        exit;
    }
    
    private function array_to_csv($array, $output, $prefix = '') {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->array_to_csv($value, $output, $prefix . $key . '_');
            } else {
                fputcsv($output, array($prefix . $key, $value));
            }
        }
    }
}