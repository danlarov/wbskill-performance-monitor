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
class WPM_Database_Optimizer {
    
    public function __construct() {
        add_action('wp_ajax_wpm_clean_revisions', array($this, 'clean_revisions'));
        add_action('wp_ajax_wpm_clean_transients', array($this, 'clean_transients'));
        add_action('wp_ajax_wpm_clean_trash', array($this, 'clean_trash'));
    }
    
    /**
     * Получение статистики базы данных
     */
    public function get_database_stats() {
        global $wpdb;
        
        $stats = array(
            'total_size' => 0,
            'tables' => array(),
            'revisions' => 0,
            'auto_drafts' => 0,
            'trashed_posts' => 0,
            'trashed_comments' => 0,
            'transients' => array(
                'expired' => 0,
                'total' => 0
            ),
            'orphaned_meta' => 0,
            'tables_count' => 0
        );
        
        // Получаем все таблицы
        $tables = $wpdb->get_results("SHOW TABLE STATUS");
        foreach ($tables as $table) {
            if (strpos($table->Name, $wpdb->prefix) === 0) {
                $size = ($table->Data_length + $table->Index_length) / 1024 / 1024;
                $stats['total_size'] += $size;
                $stats['tables'][] = array(
                    'name' => $table->Name,
                    'size_mb' => round($size, 2),
                    'rows' => $table->Rows,
                    'data_free_mb' => round($table->Data_free / 1024 / 1024, 2)
                );
                $stats['tables_count']++;
            }
        }
        $stats['total_size'] = round($stats['total_size'], 2);
        
        // Подсчет ревизий
        $stats['revisions'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'");
        
        // Подсчет черновиков
        $stats['auto_drafts'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");
        
        // Подсчет удаленных записей
        $stats['trashed_posts'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'");
        
        // Подсчет удаленных комментариев
        $stats['trashed_comments'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'");
        
        // Подсчет транзиентов
        $stats['transients']['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
        $stats['transients']['expired'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%' AND option_value IS NULL");
        
        // Подсчет сиротливых мета-полей
        $stats['orphaned_meta'] = $this->count_orphaned_meta();
        
        // Анализ autoload
        $stats['autoload'] = $this->analyze_autoload();
        
        return $stats;
    }
    
    /**
     * Очистка ревизий
     */
    public function clean_revisions() {
        global $wpdb;
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_ajax_referer('wpm_ajax_nonce', 'nonce');
        
        $deleted = $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'");
        
        wp_send_json_success(array(
            'deleted' => $deleted,
            'message' => sprintf(__('Deleted %d revisions', 'wp-performance-monitor'), $deleted)
        ));
    }
    
    /**
     * Очистка транзиентов
     */
    public function clean_transients() {
        global $wpdb;
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_ajax_referer('wpm_ajax_nonce', 'nonce');
        
        $deleted = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%' AND option_value IS NULL");
        $deleted += $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < " . time());
        
        wp_send_json_success(array(
            'deleted' => $deleted,
            'message' => sprintf(__('Deleted %d expired transients', 'wp-performance-monitor'), $deleted)
        ));
    }
    
    /**
     * Очистка корзины
     */
    public function clean_trash() {
        global $wpdb;
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_ajax_referer('wpm_ajax_nonce', 'nonce');
        
        // Удаление записей из корзины
        $deleted_posts = $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'");
        
        // Удаление комментариев из корзины
        $deleted_comments = $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'");
        
        wp_send_json_success(array(
            'deleted_posts' => $deleted_posts,
            'deleted_comments' => $deleted_comments,
            'message' => sprintf(__('Deleted %d posts and %d comments from trash', 'wp-performance-monitor'), $deleted_posts, $deleted_comments)
        ));
    }
    
    /**
     * Оптимизация таблиц
     */
    public function optimize_tables() {
        global $wpdb;
        
        $results = array();
        $tables = $wpdb->get_results("SHOW TABLES");
        
        foreach ($tables as $table) {
            $table_name = reset($table);
            if (strpos($table_name, $wpdb->prefix) === 0) {
                $result = $wpdb->query("OPTIMIZE TABLE $table_name");
                $results[] = array(
                    'table' => $table_name,
                    'success' => ($result !== false)
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Подсчет сиротливых мета-полей
     */
    private function count_orphaned_meta() {
        global $wpdb;
        
        $count = 0;
        $meta_tables = array(
            'postmeta' => 'posts',
            'commentmeta' => 'comments',
            'usermeta' => 'users',
            'termmeta' => 'terms'
        );
        
        foreach ($meta_tables as $meta_table => $parent_table) {
            $count += $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}{$meta_table} pm
                LEFT JOIN {$wpdb->prefix}{$parent_table} p ON pm.{$parent_table}_id = p.ID
                WHERE p.ID IS NULL
            ");
        }
        
        return $count;
    }
    
    /**
     * Анализ autoload опций
     */
    private function analyze_autoload() {
        global $wpdb;
        
        $options = $wpdb->get_results("
            SELECT option_name, LENGTH(option_value) as size 
            FROM {$wpdb->options} 
            WHERE autoload = 'yes' 
            ORDER BY size DESC 
            LIMIT 20
        ");
        
        $total_size = $wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes'");
        
        return array(
            'total_options' => count($options),
            'total_size_kb' => round($total_size / 1024, 2),
            'largest_options' => $options
        );
    }
    
    /**
     * Удаление сиротливых мета-полей
     */
    public function clean_orphaned_meta() {
        global $wpdb;
        
        $deleted = 0;
        
        // Очистка postmeta
        $deleted += $wpdb->query("
            DELETE pm FROM {$wpdb->prefix}postmeta pm
            LEFT JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Очистка commentmeta
        $deleted += $wpdb->query("
            DELETE cm FROM {$wpdb->prefix}commentmeta cm
            LEFT JOIN {$wpdb->prefix}comments c ON cm.comment_id = c.comment_ID
            WHERE c.comment_ID IS NULL
        ");
        
        // Очистка usermeta
        $deleted += $wpdb->query("
            DELETE um FROM {$wpdb->prefix}usermeta um
            LEFT JOIN {$wpdb->prefix}users u ON um.user_id = u.ID
            WHERE u.ID IS NULL
        ");
        
        return $deleted;
    }
}