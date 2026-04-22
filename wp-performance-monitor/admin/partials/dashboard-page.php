<?php
/**
 * Performance Monitor
 *
 * @package     Performance_Monitor
 * @author      Daniel Larin
 * @copyright   2026 Daniel Larin
 * @license     GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<?php
// Get all data for dashboard
global $wpdb;
$scanner = new WPM_Scanner();
$last_scan = $scanner->get_last_scan();
$monitor = new WPM_Monitor();
$score = $monitor->get_performance_score();

// Plugin stats
$all_plugins = get_plugins();
$active_plugins = get_option('active_plugins');

// Database stats
$db_total_size = 0;
$tables = $wpdb->get_results("SHOW TABLE STATUS");
foreach ($tables as $table) {
    if (strpos($table->Name, $wpdb->prefix) === 0) {
        $db_total_size += ($table->Data_length + $table->Index_length) / 1024 / 1024;
    }
}
$table_count = count($tables);

// Cleanable items
$cleanable_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'");
$cleanable_total += (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");
$cleanable_total += (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'");
$cleanable_total += (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'");
$cleanable_total += (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");

// Daily tips
$tips = array(
    __('Disable unused plugins to reduce server load.', 'wp-performance-monitor'),
    __('Use a caching plugin to improve page load speed.', 'wp-performance-monitor'),
    __('Optimize images before uploading to your site.', 'wp-performance-monitor'),
    __('Keep WordPress, themes, and plugins updated for security and performance.', 'wp-performance-monitor'),
    __('Use a CDN to serve static files faster.', 'wp-performance-monitor'),
    __('Limit post revisions to keep database size under control.', 'wp-performance-monitor'),
    __('Clean your database regularly from expired transients.', 'wp-performance-monitor')
);
$daily_tip = $tips[array_rand($tips)];
?>

<div class="wpm-dashboard">
    <!-- Header -->
    <div class="wpm-dashboard-header">
        <div class="wpm-dashboard-title">
            <h1><?php _e('WP Performance Monitor', 'wp-performance-monitor'); ?></h1>
            <p><?php _e('Comprehensive performance monitoring and optimization for your WordPress site', 'wp-performance-monitor'); ?></p>
        </div>
        <div class="wpm-dashboard-actions">
            <button id="wpm-quick-scan" class="wpm-btn wpm-btn-primary">
                <span class="dashicons dashicons-update"></span> <?php _e('Quick Scan', 'wp-performance-monitor'); ?>
            </button>
            <button id="wpm-quick-clean" class="wpm-btn wpm-btn-secondary">
                <span class="dashicons dashicons-trash"></span> <?php _e('Clean DB', 'wp-performance-monitor'); ?>
            </button>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="wpm-stats-row">
        <div class="wpm-stat-card wpm-stat-score">
            <div class="wpm-stat-icon">🎯</div>
            <div class="wpm-stat-content">
                <div class="wpm-stat-value" id="wpm-score-value"><?php echo $score; ?>%</div>
                <div class="wpm-stat-label"><?php _e('Performance Score', 'wp-performance-monitor'); ?></div>
            </div>
            <div class="wpm-stat-progress">
                <div class="wpm-progress-bar">
                    <div class="wpm-progress-fill" style="width: <?php echo $score; ?>%;"></div>
                </div>
            </div>
        </div>
        
        <div class="wpm-stat-card wpm-stat-plugins">
            <div class="wpm-stat-icon">🔌</div>
            <div class="wpm-stat-content">
                <div class="wpm-stat-value"><?php echo count($all_plugins); ?></div>
                <div class="wpm-stat-label"><?php _e('Total Plugins', 'wp-performance-monitor'); ?></div>
            </div>
            <div class="wpm-stat-detail">
                <span class="wpm-stat-active"><?php echo count($active_plugins); ?> <?php _e('active', 'wp-performance-monitor'); ?></span>
                <span class="wpm-stat-inactive"><?php echo count($all_plugins) - count($active_plugins); ?> <?php _e('inactive', 'wp-performance-monitor'); ?></span>
            </div>
        </div>
        
        <div class="wpm-stat-card wpm-stat-database">
            <div class="wpm-stat-icon">🗄️</div>
            <div class="wpm-stat-content">
                <div class="wpm-stat-value"><?php echo round($db_total_size, 2); ?> MB</div>
                <div class="wpm-stat-label"><?php _e('Database Size', 'wp-performance-monitor'); ?></div>
            </div>
            <div class="wpm-stat-detail">
                <span class="wpm-stat-tables"><?php echo $table_count; ?> <?php _e('tables', 'wp-performance-monitor'); ?></span>
            </div>
        </div>
        
        <div class="wpm-stat-card wpm-stat-cleanable">
            <div class="wpm-stat-icon">🧹</div>
            <div class="wpm-stat-content">
                <div class="wpm-stat-value"><?php echo number_format($cleanable_total); ?></div>
                <div class="wpm-stat-label"><?php _e('Cleanable Items', 'wp-performance-monitor'); ?></div>
            </div>
            <div class="wpm-stat-detail">
                <span class="wpm-stat-warning">⚠️ <?php _e('Ready to clean', 'wp-performance-monitor'); ?></span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="wpm-dashboard-grid">
        
        <!-- Quick Actions -->
        <div class="wpm-dashboard-card wpm-card-actions">
            <div class="wpm-card-header">
                <h3><span class="dashicons dashicons-admin-tools"></span> <?php _e('Quick Actions', 'wp-performance-monitor'); ?></h3>
            </div>
            <div class="wpm-card-content">
                <div class="wpm-action-buttons">
                    <a href="admin.php?page=wpm-scanner" class="wpm-action-btn">
                        <span class="dashicons dashicons-search"></span>
                        <span><?php _e('Full Scan', 'wp-performance-monitor'); ?></span>
                    </a>
                    <a href="admin.php?page=wpm-database" class="wpm-action-btn">
                        <span class="dashicons dashicons-database"></span>
                        <span><?php _e('Clean Database', 'wp-performance-monitor'); ?></span>
                    </a>
                    <a href="admin.php?page=wpm-settings" class="wpm-action-btn">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <span><?php _e('Settings', 'wp-performance-monitor'); ?></span>
                    </a>
                    <a href="admin.php?page=wpm-reports" class="wpm-action-btn">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                        <span><?php _e('Reports', 'wp-performance-monitor'); ?></span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Scans -->
        <div class="wpm-dashboard-card wpm-card-scans">
            <div class="wpm-card-header">
                <h3><span class="dashicons dashicons-backup"></span> <?php _e('Recent Scans', 'wp-performance-monitor'); ?></h3>
                <a href="admin.php?page=wpm-reports" class="wpm-card-link"><?php _e('View all', 'wp-performance-monitor'); ?> →</a>
            </div>
            <div class="wpm-card-content">
                <table class="wpm-scans-table">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'wp-performance-monitor'); ?></th>
                            <th><?php _e('Type', 'wp-performance-monitor'); ?></th>
                            <th><?php _e('Plugins', 'wp-performance-monitor'); ?></th>
                            <th><?php _e('Issues', 'wp-performance-monitor'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recent_scans = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpm_scans ORDER BY scan_time DESC LIMIT 5");
                        if ($recent_scans):
                            foreach ($recent_scans as $scan):
                        ?>
                            <tr>
                                <td><?php echo date_i18n('d.m.Y H:i', strtotime($scan->scan_time)); ?></td>
                                <td><?php echo esc_html($scan->scan_type); ?></td>
                                <td><?php echo $scan->total_plugins; ?></td>
                                <td><?php echo $scan->slow_plugins; ?></td>
                            </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <tr><td colspan="4"><?php _e('No scans yet. Click "Quick Scan" to start.', 'wp-performance-monitor'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tip of the Day -->
        <div class="wpm-dashboard-card wpm-card-tip">
            <div class="wpm-card-header">
                <h3><span class="dashicons dashicons-lightbulb"></span> <?php _e('Performance Tip', 'wp-performance-monitor'); ?></h3>
            </div>
            <div class="wpm-card-content">
                <div class="wpm-tip-content">
                    <p><?php echo $daily_tip; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Donate Card -->
        <div class="wpm-dashboard-card wpm-card-donate">
            <div class="wpm-card-header">
                <h3><span class="dashicons dashicons-heart"></span> <?php _e('Support the project', 'wp-performance-monitor'); ?></h3>
            </div>
            <div class="wpm-card-content">
                <p><?php _e('If you find this plugin useful, please consider supporting further development:', 'wp-performance-monitor'); ?></p>
                <a href="https://yoomoney.ru/fundraise/1GV365N919A.260405" class="wpm-donate-btn" target="_blank">
                    <span class="dashicons dashicons-money-alt"></span> <?php _e('Donate via YooMoney', 'wp-performance-monitor'); ?>
                </a>
            </div>
        </div>
        
        <!-- System Info Mini -->
        <div class="wpm-dashboard-card wpm-card-system">
            <div class="wpm-card-header">
                <h3><span class="dashicons dashicons-info"></span> <?php _e('System Info', 'wp-performance-monitor'); ?></h3>
            </div>
            <div class="wpm-card-content">
                <div class="wpm-system-grid">
                    <div class="wpm-system-item">
                        <span class="wpm-system-label">PHP:</span>
                        <span class="wpm-system-value"><?php echo phpversion(); ?></span>
                    </div>
                    <div class="wpm-system-item">
                        <span class="wpm-system-label">WP:</span>
                        <span class="wpm-system-value"><?php echo get_bloginfo('version'); ?></span>
                    </div>
                    <div class="wpm-system-item">
                        <span class="wpm-system-label">Memory:</span>
                        <span class="wpm-system-value"><?php echo WP_MEMORY_LIMIT; ?></span>
                    </div>
                    <div class="wpm-system-item">
                        <span class="wpm-system-label">Debug:</span>
                        <span class="wpm-system-value"><?php echo defined('WP_DEBUG') && WP_DEBUG ? 'ON' : 'OFF'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.wpm-dashboard {
    margin: 20px 20px 0 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header */
.wpm-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}
.wpm-dashboard-title h1 {
    margin: 0 0 5px 0;
    font-size: 24px;
    color: #1f2937;
}
.wpm-dashboard-title p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}
.wpm-dashboard-actions {
    display: flex;
    gap: 10px;
}
.wpm-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    border: none;
    transition: all 0.2s;
}
.wpm-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.wpm-btn-primary:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}
.wpm-btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
}
.wpm-btn-secondary:hover {
    background: #e5e7eb;
}

/* Stats Row */
.wpm-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.wpm-stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.wpm-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.wpm-stat-icon {
    font-size: 36px;
}
.wpm-stat-content {
    flex: 1;
}
.wpm-stat-value {
    font-size: 28px;
    font-weight: bold;
    color: #1f2937;
}
.wpm-stat-label {
    font-size: 12px;
    color: #6b7280;
}
.wpm-stat-progress {
    width: 100%;
    margin-top: 10px;
}
.wpm-progress-bar {
    background: #e5e7eb;
    border-radius: 10px;
    height: 6px;
    overflow: hidden;
}
.wpm-progress-fill {
    background: linear-gradient(90deg, #667eea, #764ba2);
    height: 100%;
    border-radius: 10px;
}
.wpm-stat-detail {
    font-size: 11px;
    color: #6b7280;
    margin-top: 8px;
}
.wpm-stat-active {
    color: #10b981;
}
.wpm-stat-inactive {
    color: #ef4444;
}
.wpm-stat-warning {
    color: #f59e0b;
}

/* Dashboard Grid */
.wpm-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
}
.wpm-dashboard-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}
.wpm-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    background: #fafafa;
}
.wpm-card-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}
.wpm-card-header h3 .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    color: #667eea;
}
.wpm-card-link {
    font-size: 12px;
    color: #667eea;
    text-decoration: none;
}
.wpm-card-link:hover {
    text-decoration: underline;
}
.wpm-card-content {
    padding: 20px;
}

/* Action Buttons */
.wpm-action-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
.wpm-action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 12px;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s;
    border: 1px solid #e5e7eb;
}
.wpm-action-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
.wpm-action-btn:hover .dashicons {
    color: white;
}
.wpm-action-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    color: #667eea;
}
.wpm-action-btn span:last-child {
    font-size: 13px;
    font-weight: 500;
}

/* Scans Table */
.wpm-scans-table {
    width: 100%;
    border-collapse: collapse;
}
.wpm-scans-table th,
.wpm-scans-table td {
    padding: 10px 8px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}
.wpm-scans-table th {
    font-weight: 600;
    color: #6b7280;
    font-size: 11px;
    text-transform: uppercase;
}
.wpm-scans-table td {
    color: #374151;
}

/* Tip */
.wpm-tip-content p {
    margin: 0;
    font-size: 14px;
    color: #4b5563;
    line-height: 1.5;
}

/* Donate Button */
.wpm-card-donate p {
    margin: 0 0 15px 0;
    font-size: 13px;
    color: #4b5563;
}
.wpm-donate-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}
.wpm-donate-btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    color: white;
}
.wpm-donate-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* System Grid */
.wpm-system-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
.wpm-system-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}
.wpm-system-label {
    color: #6b7280;
}
.wpm-system-value {
    font-weight: 500;
    color: #1f2937;
}

/* Responsive */
@media (max-width: 1200px) {
    .wpm-dashboard-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 768px) {
    .wpm-stats-row {
        grid-template-columns: 1fr;
    }
    .wpm-dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .wpm-action-buttons {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Quick Scan button
    $('#wpm-quick-scan').on('click', function() {
        var $btn = $(this);
        $btn.text('Scanning...').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'wpm_start_scan',
                scan_type: 'full',
                nonce: '<?php echo wp_create_nonce('wpm_ajax_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Scan completed! Page will reload.');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                    $btn.html('<span class="dashicons dashicons-update"></span> Quick Scan').prop('disabled', false);
                }
            },
            error: function() {
                alert('AJAX error occurred');
                $btn.html('<span class="dashicons dashicons-update"></span> Quick Scan').prop('disabled', false);
            }
        });
    });
    
    // Quick Clean button
    $('#wpm-quick-clean').on('click', function() {
        if (!confirm('Clean transients and expired data?')) return;
        var $btn = $(this);
        $btn.text('Cleaning...').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'wpm_clean_transients',
                nonce: '<?php echo wp_create_nonce('wpm_ajax_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Error cleaning');
                }
                $btn.html('<span class="dashicons dashicons-trash"></span> Clean DB').prop('disabled', false);
            },
            error: function() {
                alert('AJAX error');
                $btn.html('<span class="dashicons dashicons-trash"></span> Clean DB').prop('disabled', false);
            }
        });
    });
});
</script>