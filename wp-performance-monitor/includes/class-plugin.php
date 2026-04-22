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
class WPM_Plugin {
    protected $loader;
    protected $plugin_name;
    protected $version;
    
    public function __construct() {
        $this->plugin_name = 'wp-performance-monitor';
        $this->version = WPM_VERSION;
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    private function load_dependencies() {
        require_once WPM_PLUGIN_DIR . 'includes/class-loader.php';
        require_once WPM_PLUGIN_DIR . 'includes/class-i18n.php';
        require_once WPM_PLUGIN_DIR . 'includes/class-monitor.php';
        require_once WPM_PLUGIN_DIR . 'includes/class-scanner.php';
        require_once WPM_PLUGIN_DIR . 'includes/class-database-optimizer.php';
        require_once WPM_PLUGIN_DIR . 'includes/class-assets-analyzer.php';
        require_once WPM_PLUGIN_DIR . 'includes/class-hooks-analyzer.php';
        require_once WPM_PLUGIN_DIR . 'includes/class-cron-jobs.php';
        require_once WPM_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        
        $this->loader = new WPM_Loader();
    }
    
    private function set_locale() {
        $plugin_i18n = new WPM_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }
    
    private function define_admin_hooks() {
        $monitor = new WPM_Monitor();
        $scanner = new WPM_Scanner();
        $db_optimizer = new WPM_Database_Optimizer();
        $assets_analyzer = new WPM_Assets_Analyzer();
        $hooks_analyzer = new WPM_Hooks_Analyzer();
        $ajax_handler = new WPM_Ajax_Handler();
        
        // Admin menu
        $this->loader->add_action('admin_menu', $this, 'add_admin_pages');
        
        // Admin scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_styles');
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_scripts');
        
        // ALL AJAX handlers
        $this->loader->add_action('wp_ajax_wpm_start_scan', $ajax_handler, 'start_scan');
        $this->loader->add_action('wp_ajax_wpm_get_scan_status', $ajax_handler, 'get_scan_status');
        $this->loader->add_action('wp_ajax_wpm_optimize_database', $ajax_handler, 'optimize_database');
        $this->loader->add_action('wp_ajax_wpm_disable_plugin', $ajax_handler, 'disable_plugin');
        $this->loader->add_action('wp_ajax_wpm_export_report', $ajax_handler, 'export_report');
        $this->loader->add_action('wp_ajax_wpm_clean_revisions', $ajax_handler, 'clean_revisions');
        $this->loader->add_action('wp_ajax_wpm_clean_transients', $ajax_handler, 'clean_transients');
        $this->loader->add_action('wp_ajax_wpm_clean_trash', $ajax_handler, 'clean_trash');
        $this->loader->add_action('wp_ajax_wpm_clean_drafts', $ajax_handler, 'clean_drafts');
        $this->loader->add_action('wp_ajax_wpm_clean_orphaned_meta', $ajax_handler, 'clean_orphaned_meta');
        $this->loader->add_action('wp_ajax_wpm_optimize_tables', $ajax_handler, 'optimize_tables');
        $this->loader->add_action('wp_ajax_wpm_clean_all', $ajax_handler, 'clean_all');
        $this->loader->add_action('wp_ajax_wpm_get_report', $ajax_handler, 'get_report');
        $this->loader->add_action('wp_ajax_wpm_get_hooks_analysis', $ajax_handler, 'get_hooks_analysis');
        $this->loader->add_action('wp_ajax_wpm_get_cron_jobs', $ajax_handler, 'get_cron_jobs');
        $this->loader->add_action('wp_ajax_wpm_get_performance_metrics', $ajax_handler, 'get_performance_metrics');
        $this->loader->add_action('wp_ajax_wpm_get_plugin_performance', $ajax_handler, 'get_plugin_performance');
        $this->loader->add_action('wp_ajax_wpm_get_performance_history', $ajax_handler, 'get_performance_history');
        $this->loader->add_action('wp_ajax_wpm_test_performance', $ajax_handler, 'test_performance');
        $this->loader->add_action('wp_ajax_wpm_clear_opcache', $ajax_handler, 'clear_opcache');
        $this->loader->add_action('wp_ajax_wpm_clear_wp_cache', $ajax_handler, 'clear_wp_cache');
        $this->loader->add_action('wp_ajax_wpm_save_heartbeat_settings', $ajax_handler, 'save_heartbeat_settings');
        $this->loader->add_action('wp_ajax_wpm_save_revisions_limit', $ajax_handler, 'save_revisions_limit');
        $this->loader->add_action('wp_ajax_wpm_schedule_email_report', $ajax_handler, 'schedule_email_report');
        
        // Scanner initialization
        $this->loader->add_action('init', $scanner, 'init');
        
        // Cron jobs
        $cron = new WPM_Cron_Jobs();
        $this->loader->add_action('wpm_daily_scan', $cron, 'run_daily_scan');
        $this->loader->add_action('wpm_auto_scan', $cron, 'run_auto_scan');
        $this->loader->add_action('wpm_cleanup_old_logs', $cron, 'cleanup_old_logs');
        
        // Dashboard widget
        $this->loader->add_action('wp_dashboard_setup', $this, 'add_dashboard_widget');
        
        // Admin bar menu
        $this->loader->add_action('admin_bar_menu', $this, 'add_admin_bar_menu', 100);
    }
    
    private function define_public_hooks() {
        // Public hooks if needed
    }
    
    public function add_admin_pages() {
        add_menu_page(
            __('WP Performance Monitor', 'wp-performance-monitor'),
            __('Performance', 'wp-performance-monitor'),
            'manage_options',
            'wpm-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-superhero',
            30
        );
        
        add_submenu_page(
            'wpm-dashboard',
            __('Scanner', 'wp-performance-monitor'),
            __('Scanner', 'wp-performance-monitor'),
            'manage_options',
            'wpm-scanner',
            array($this, 'render_scanner_page')
        );
        
        add_submenu_page(
            'wpm-dashboard',
            __('Plugins Analysis', 'wp-performance-monitor'),
            __('Plugins', 'wp-performance-monitor'),
            'manage_options',
            'wpm-plugins',
            array($this, 'render_plugins_page')
        );
        
        add_submenu_page(
            'wpm-dashboard',
            __('Database', 'wp-performance-monitor'),
            __('Database', 'wp-performance-monitor'),
            'manage_options',
            'wpm-database',
            array($this, 'render_database_page')
        );
        
        add_submenu_page(
            'wpm-dashboard',
            __('Reports', 'wp-performance-monitor'),
            __('Reports', 'wp-performance-monitor'),
            'manage_options',
            'wpm-reports',
            array($this, 'render_reports_page')
        );
        
        add_submenu_page(
            'wpm-dashboard',
            __('Tools', 'wp-performance-monitor'),
            __('Tools', 'wp-performance-monitor'),
            'manage_options',
            'wpm-tools',
            array($this, 'render_tools_page')
        );
        
        add_submenu_page(
            'wpm-dashboard',
            __('Settings', 'wp-performance-monitor'),
            __('Settings', 'wp-performance-monitor'),
            'manage_options',
            'wpm-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function enqueue_admin_styles($hook) {
        if (strpos($hook, 'wpm-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'wpm-admin-styles',
            WPM_PLUGIN_URL . 'admin/css/admin-styles.css',
            array(),
            $this->version
        );
        
        wp_enqueue_style(
            'wpm-charts',
            WPM_PLUGIN_URL . 'admin/css/charts.css',
            array(),
            $this->version
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wpm-') === false) {
            return;
        }
        
        wp_enqueue_script(
            'wpm-admin-scripts',
            WPM_PLUGIN_URL . 'admin/js/admin-scripts.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'wpm-charts',
            WPM_PLUGIN_URL . 'admin/js/charts.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'wpm-dashboard',
            WPM_PLUGIN_URL . 'admin/js/dashboard.js',
            array('jquery', 'wpm-charts'),
            $this->version,
            true
        );
        
        wp_localize_script('wpm-admin-scripts', 'wpm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpm_ajax_nonce'),
            'strings' => array(
                'scan_started' => __('Scan started successfully!', 'wp-performance-monitor'),
                'scan_completed' => __('Scan completed!', 'wp-performance-monitor'),
                'error' => __('An error occurred', 'wp-performance-monitor'),
                'confirm_disable' => __('Are you sure you want to disable this plugin?', 'wp-performance-monitor')
            )
        ));
    }
    
    public function add_dashboard_widget() {
        $options = get_option('wpm_options', array());
        
        if (isset($options['dashboard_widget']) && $options['dashboard_widget'] !== 'yes') {
            return;
        }
        
        wp_add_dashboard_widget(
            'wpm_dashboard_widget',
            __('Performance Monitor', 'wp-performance-monitor'),
            array($this, 'render_dashboard_widget_content')
        );
    }
    
    public function render_dashboard_widget_content() {
        $scanner = new WPM_Scanner();
        $last_scan = $scanner->get_last_scan();
        $monitor = new WPM_Monitor();
        $score = $monitor->get_performance_score();
        ?>
        <div style="text-align: center;">
            <div style="font-size: 32px; font-weight: bold; color: #667eea;"><?php echo $score; ?>%</div>
            <div style="margin: 10px 0;"><?php _e('Performance Score', 'wp-performance-monitor'); ?></div>
            <?php if ($last_scan && isset($last_scan->scan_time)): ?>
                <div style="font-size: 12px; color: #666;">
                    <?php echo __('Last scan', 'wp-performance-monitor'); ?>: <?php echo date_i18n(get_option('date_format'), strtotime($last_scan->scan_time)); ?>
                </div>
            <?php endif; ?>
            <div style="margin-top: 15px;">
                <a href="<?php echo admin_url('admin.php?page=wpm-dashboard'); ?>" class="button button-primary">
                    <?php _e('View Details', 'wp-performance-monitor'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    public function add_admin_bar_menu($wp_admin_bar) {
        $options = get_option('wpm_options', array());
        
        if (isset($options['admin_bar_menu']) && $options['admin_bar_menu'] !== 'yes') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $wp_admin_bar->add_node(array(
            'id' => 'wpm-admin-bar',
            'title' => '<span class="ab-icon dashicons-performance-chart"></span> ' . __('Performance', 'wp-performance-monitor'),
            'href' => admin_url('admin.php?page=wpm-dashboard'),
            'meta' => array(
                'class' => 'wpm-admin-bar-menu'
            )
        ));
        
        $wp_admin_bar->add_node(array(
            'id' => 'wpm-admin-bar-scanner',
            'parent' => 'wpm-admin-bar',
            'title' => __('Scanner', 'wp-performance-monitor'),
            'href' => admin_url('admin.php?page=wpm-scanner')
        ));
        
        $wp_admin_bar->add_node(array(
            'id' => 'wpm-admin-bar-database',
            'parent' => 'wpm-admin-bar',
            'title' => __('Database', 'wp-performance-monitor'),
            'href' => admin_url('admin.php?page=wpm-database')
        ));
        
        $wp_admin_bar->add_node(array(
            'id' => 'wpm-admin-bar-settings',
            'parent' => 'wpm-admin-bar',
            'title' => __('Settings', 'wp-performance-monitor'),
            'href' => admin_url('admin.php?page=wpm-settings')
        ));
    }
    
    public function render_dashboard_page() {
        require_once WPM_PLUGIN_DIR . 'admin/partials/dashboard-page.php';
    }
    
    public function render_scanner_page() {
        require_once WPM_PLUGIN_DIR . 'admin/partials/scanner-page.php';
    }
    
    public function render_plugins_page() {
        require_once WPM_PLUGIN_DIR . 'admin/partials/plugins-page.php';
    }
    
    public function render_database_page() {
        require_once WPM_PLUGIN_DIR . 'admin/partials/database-page.php';
    }
    
    public function render_reports_page() {
        require_once WPM_PLUGIN_DIR . 'admin/partials/reports-page.php';
    }
    
    public function render_tools_page() {
        require_once WPM_PLUGIN_DIR . 'admin/partials/tools-page.php';
    }
    
    public function render_settings_page() {
        require_once WPM_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }
    
    public function run() {
        $this->loader->run();
    }
}