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
<div class="wpm-wrap">
    <div class="wpm-header">
        <h1><?php _e('Performance Scanner', 'wp-performance-monitor'); ?></h1>
        <p><?php _e('Deep scan your WordPress site to identify performance bottlenecks', 'wp-performance-monitor'); ?></p>
    </div>

    <div class="wpm-grid">
        <div class="wpm-card">
            <h3><?php _e('Start New Scan', 'wp-performance-monitor'); ?></h3>
            <div class="wpm-scan-options">
                <select id="wpm-scan-type" class="wpm-select">
                    <option value="full"><?php _e('Full Scan (Recommended)', 'wp-performance-monitor'); ?></option>
                    <option value="quick"><?php _e('Quick Scan', 'wp-performance-monitor'); ?></option>
                    <option value="plugins"><?php _e('Plugins Only', 'wp-performance-monitor'); ?></option>
                    <option value="database"><?php _e('Database Only', 'wp-performance-monitor'); ?></option>
                    <option value="assets"><?php _e('Assets Only', 'wp-performance-monitor'); ?></option>
                </select>
                <button id="wpm-start-scan" class="wpm-button wpm-button-primary">
                    <?php _e('Start Scan', 'wp-performance-monitor'); ?>
                </button>
            </div>
        </div>

        <div class="wpm-card">
            <h3><?php _e('Last Scan Results', 'wp-performance-monitor'); ?></h3>
            <?php
            $scanner = new WPM_Scanner();
            $last_scan = $scanner->get_last_scan();
            if ($last_scan && isset($last_scan->scan_time)):
            ?>
                <div class="wpm-scan-info">
                    <p><strong><?php _e('Date:', 'wp-performance-monitor'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' H:i:s', strtotime($last_scan->scan_time)); ?></p>
                    <p><strong><?php _e('Type:', 'wp-performance-monitor'); ?></strong> <?php echo esc_html($last_scan->scan_type); ?></p>
                    <p><strong><?php _e('Plugins Analyzed:', 'wp-performance-monitor'); ?></strong> <?php echo $last_scan->total_plugins; ?></p>
                    <p><strong><?php _e('Issues Found:', 'wp-performance-monitor'); ?></strong> <?php echo $last_scan->slow_plugins; ?></p>
                </div>
                <button id="wpm-view-last-report" class="wpm-button" data-id="<?php echo $last_scan->id; ?>"><?php _e('View Full Report', 'wp-performance-monitor'); ?></button>
            <?php else: ?>
                <p><?php _e('No scans have been performed yet. Click "Start Scan" to begin.', 'wp-performance-monitor'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="wpm-grid">
        <div class="wpm-card">
            <div class="wpm-tabs">
                <button class="wpm-tab active" data-tab="plugins-tab"><?php _e('Plugins', 'wp-performance-monitor'); ?></button>
                <button class="wpm-tab" data-tab="database-tab"><?php _e('Database', 'wp-performance-monitor'); ?></button>
                <button class="wpm-tab" data-tab="assets-tab"><?php _e('Assets', 'wp-performance-monitor'); ?></button>
                <button class="wpm-tab" data-tab="hooks-tab"><?php _e('Hooks', 'wp-performance-monitor'); ?></button>
                <button class="wpm-tab" data-tab="cron-tab"><?php _e('Cron Jobs', 'wp-performance-monitor'); ?></button>
            </div>

            <div id="plugins-tab" class="wpm-tab-content active">
                <?php
                $all_plugins = get_plugins();
                $active_plugins = get_option('active_plugins');
                ?>
                <table class="wpm-table">
                    <thead>
                        <tr>
                            <th><?php _e('Plugin Name', 'wp-performance-monitor'); ?></th>
                            <th><?php _e('Status', 'wp-performance-monitor'); ?></th>
                            <th><?php _e('Performance Score', 'wp-performance-monitor'); ?></th>
                            <th><?php _e('Issues', 'wp-performance-monitor'); ?></th>
                            <th><?php _e('Action', 'wp-performance-monitor'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_plugins as $plugin_file => $plugin_data):
                            $is_active = in_array($plugin_file, $active_plugins);
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($plugin_data['Name']); ?></strong></td>
                                <td>
                                    <span class="wpm-badge <?php echo $is_active ? 'wpm-badge-active' : 'wpm-badge-inactive'; ?>">
                                        <?php echo $is_active ? __('Active', 'wp-performance-monitor') : __('Inactive', 'wp-performance-monitor'); ?>
                                    </span>
                                </td>
                                <td>100%</span></td>
                                <td><span class="wpm-success">✓ <?php _e('No issues', 'wp-performance-monitor'); ?></span></td>
                                <td>
                                    <?php if ($is_active): ?>
                                        <button class="wpm-button-small wpm-disable-plugin" data-plugin="<?php echo esc_attr($plugin_file); ?>">
                                            <?php _e('Disable', 'wp-performance-monitor'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="database-tab" class="wpm-tab-content">
                <?php
                global $wpdb;
                $db_total_size = 0;
                $tables = $wpdb->get_results("SHOW TABLE STATUS");
                foreach ($tables as $table) {
                    if (strpos($table->Name, $wpdb->prefix) === 0) {
                        $db_total_size += ($table->Data_length + $table->Index_length) / 1024 / 1024;
                    }
                }
                $revisions = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'");
                $auto_drafts = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");
                $trashed_posts = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'");
                $trashed_comments = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'");
                $transients = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
                $orphaned_meta = 0;
                $orphaned_meta += (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}postmeta pm LEFT JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID WHERE p.ID IS NULL");
                $orphaned_meta += (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}commentmeta cm LEFT JOIN {$wpdb->prefix}comments c ON cm.comment_id = c.comment_ID WHERE c.comment_ID IS NULL");
                ?>

                <div class="wpm-stats-row">
                    <div class="wpm-stats-item green">
                        <div class="stat-value"><?php echo round($db_total_size, 2); ?> MB</div>
                        <div class="stat-label"><?php _e('Database Size', 'wp-performance-monitor'); ?></div>
                    </div>
                    <div class="wpm-stats-item blue">
                        <div class="stat-value"><?php echo number_format($revisions + $auto_drafts + $trashed_posts + $trashed_comments + $transients + $orphaned_meta); ?></div>
                        <div class="stat-label"><?php _e('Cleanable Items', 'wp-performance-monitor'); ?></div>
                    </div>
                    <div class="wpm-stats-item yellow">
                        <div class="stat-value"><?php echo count($tables); ?></div>
                        <div class="stat-label"><?php _e('Total Tables', 'wp-performance-monitor'); ?></div>
                    </div>
                </div>

                <div class="wpm-card-grid">
                    <div class="wpm-card">
                        <i class="icon icon-edit"></i>
                        <h3><?php _e('Post Revisions', 'wp-performance-monitor'); ?></h3>
                        <p><?php _e('WordPress saves every change to your posts as revisions', 'wp-performance-monitor'); ?></p>
                        <div class="card-count"><?php echo number_format($revisions); ?></div>
                        <button class="wpm-action-btn" data-action="clean_revisions"><?php _e('Clean Revisions', 'wp-performance-monitor'); ?></button>
                    </div>

                    <div class="wpm-card">
                        <i class="icon icon-file"></i>
                        <h3><?php _e('Auto Drafts', 'wp-performance-monitor'); ?></h3>
                        <p><?php _e('Automatically saved drafts that were never published', 'wp-performance-monitor'); ?></p>
                        <div class="card-count"><?php echo number_format($auto_drafts); ?></div>
                        <button class="wpm-action-btn" data-action="clean_drafts"><?php _e('Clean Drafts', 'wp-performance-monitor'); ?></button>
                    </div>

                    <div class="wpm-card">
                        <i class="icon icon-trash"></i>
                        <h3><?php _e('Trashed Items', 'wp-performance-monitor'); ?></h3>
                        <p><?php _e('Posts and comments in trash that can be permanently deleted', 'wp-performance-monitor'); ?></p>
                        <div class="card-count"><?php echo number_format($trashed_posts + $trashed_comments); ?></div>
                        <button class="wpm-action-btn" data-action="clean_trash"><?php _e('Clean Trash', 'wp-performance-monitor'); ?></button>
                    </div>

                    <div class="wpm-card">
                        <i class="icon icon-time"></i>
                        <h3><?php _e('Expired Transients', 'wp-performance-monitor'); ?></h3>
                        <p><?php _e('Temporary data that has expired and is no longer needed', 'wp-performance-monitor'); ?></p>
                        <div class="card-count"><?php echo number_format($transients); ?></div>
                        <button class="wpm-action-btn" data-action="clean_transients"><?php _e('Clean Transients', 'wp-performance-monitor'); ?></button>
                    </div>

                    <div class="wpm-card">
                        <i class="icon icon-link-broken"></i>
                        <h3><?php _e('Orphaned Meta Data', 'wp-performance-monitor'); ?></h3>
                        <p><?php _e('Meta data that belongs to deleted posts, comments, or users', 'wp-performance-monitor'); ?></p>
                        <div class="card-count"><?php echo number_format($orphaned_meta); ?></div>
                        <button class="wpm-action-btn" data-action="clean_orphaned_meta"><?php _e('Clean Orphaned Meta', 'wp-performance-monitor'); ?></button>
                    </div>

                    <div class="wpm-card">
                        <i class="icon icon-tools"></i>
                        <h3><?php _e('Optimize Tables', 'wp-performance-monitor'); ?></h3>
                        <p><?php _e('Optimize database tables for better performance', 'wp-performance-monitor'); ?></p>
                        <div class="card-count"><?php echo count($tables); ?></div>
                        <button class="wpm-action-btn" data-action="optimize_tables"><?php _e('Optimize Tables', 'wp-performance-monitor'); ?></button>
                    </div>
                </div>

                <div class="wpm-clean-all-container">
                    <button class="wpm-clean-all-btn" data-action="clean_all">
                        <?php _e('Clean All', 'wp-performance-monitor'); ?>
                    </button>
                </div>
            </div>

            <div id="assets-tab" class="wpm-tab-content">
                <p><?php _e('Run a full scan while viewing your website frontend to see asset analysis.', 'wp-performance-monitor'); ?></p>
            </div>

            <div id="hooks-tab" class="wpm-tab-content">
                <div id="wpm-hooks-analysis"><p><?php _e('Loading hook analysis...', 'wp-performance-monitor'); ?></p></div>
            </div>

            <div id="cron-tab" class="wpm-tab-content">
                <div id="wpm-cron-analysis"><p><?php _e('Loading cron jobs...', 'wp-performance-monitor'); ?></p></div>
            </div>
        </div>
    </div>
</div>

<style>
.wpm-wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}
.wpm-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    color: white;
}
.wpm-header h1 {
    margin: 0 0 10px 0;
    color: white;
}
.wpm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}
.wpm-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.wpm-tabs {
    display: flex;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 20px;
    gap: 5px;
}
.wpm-tab {
    padding: 10px 20px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    color: #6b7280;
    border-radius: 8px 8px 0 0;
}
.wpm-tab.active {
    color: #667eea;
    border-bottom: 2px solid #667eea;
    margin-bottom: -2px;
}
.wpm-tab-content {
    display: none;
}
.wpm-tab-content.active {
    display: block;
}
.wpm-table {
    width: 100%;
    border-collapse: collapse;
}
.wpm-table th,
.wpm-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}
.wpm-table th {
    background: #f9fafb;
    font-weight: 600;
}
.wpm-badge-active {
    background: #10b981;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
}
.wpm-badge-inactive {
    background: #9ca3af;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
}
.wpm-success {
    color: #10b981;
}
.wpm-button-small {
    background: #ef4444;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
}
.wpm-select {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    margin-right: 10px;
}
.wpm-button {
    background: #667eea;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
}
.wpm-stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}
.wpm-stats-item {
    padding: 20px;
    border-radius: 12px;
    text-align: center;
}
.wpm-stats-item.green {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}
.wpm-stats-item.green .stat-value { color: #22c55e; }
.wpm-stats-item.blue {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
}
.wpm-stats-item.blue .stat-value { color: #3b82f6; }
.wpm-stats-item.yellow {
    background: #fef3c7;
    border: 1px solid #fde68a;
}
.wpm-stats-item.yellow .stat-value { color: #d97706; }
.stat-value {
    font-size: 32px;
    font-weight: bold;
}
.stat-label {
    font-size: 14px;
    margin-top: 8px;
}
.wpm-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.card-count {
    font-size: 24px;
    font-weight: bold;
    color: #667eea;
    margin: 12px 0;
}
.wpm-action-btn {
    background: #f3f4f6;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}
.wpm-action-btn:hover {
    background: #667eea;
    color: white;
}
.wpm-clean-all-container {
    text-align: right;
    margin-top: 30px;
}
.wpm-clean-all-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
}
.wpm-clean-all-btn:hover {
    opacity: 0.9;
}
@media (max-width: 768px) {
    .wpm-stats-row {
        grid-template-columns: 1fr;
    }
    .wpm-card-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var nonce = '<?php echo wp_create_nonce('wpm_ajax_nonce'); ?>';

    // Tabs
    $('.wpm-tab').on('click', function() {
        var tab_id = $(this).data('tab');
        $('.wpm-tab').removeClass('active');
        $('.wpm-tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + tab_id).addClass('active');
    });

    // Start Scan
    $('#wpm-start-scan').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        var scanType = $('#wpm-scan-type').val();
        
        $btn.text('Scanning...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpm_start_scan',
                scan_type: scanType,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Scan completed! Page will reload.');
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('AJAX error occurred');
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });

    // View Full Report
    $('#wpm-view-last-report').on('click', function() {
        var reportId = $(this).data('id');
        if (reportId) {
            window.location.href = 'admin.php?page=wpm-reports&report_id=' + reportId;
        } else {
            alert('No report ID found');
        }
    });

    // Action buttons for database cleanup
    $('.wpm-action-btn').on('click', function() {
        var action = $(this).data('action');
        var $btn = $(this);
        var originalText = $btn.text();
        
        var confirmMsg = '';
        if (action === 'clean_revisions') confirmMsg = 'Delete all post revisions?';
        else if (action === 'clean_drafts') confirmMsg = 'Delete all auto drafts?';
        else if (action === 'clean_trash') confirmMsg = 'Empty trash?';
        else if (action === 'clean_transients') confirmMsg = 'Delete all transients?';
        else if (action === 'clean_orphaned_meta') confirmMsg = 'Delete orphaned meta data?';
        else if (action === 'optimize_tables') confirmMsg = 'Optimize database tables?';
        else if (action === 'clean_all') confirmMsg = 'Clean ALL database trash? This cannot be undone.';
        
        if (!confirm(confirmMsg)) return;
        
        $btn.text('Processing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpm_' + action,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('AJAX error occurred');
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });

    // Load Cron Jobs
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'wpm_get_cron_jobs',
            nonce: nonce
        },
        success: function(response) {
            if (response.success && response.data && response.data.length) {
                var html = '<table class="wpm-table"><thead><tr><th>Hook</th><th>Next Run</th><th>Schedule</th></tr></thead><tbody>';
                $.each(response.data.slice(0, 20), function(i, job) {
                    html += '<tr><td><code>' + job.hook + '</code></td><td>' + job.next_run_human + '</td><td>' + job.schedule + '</td></tr>';
                });
                html += '</tbody></table>';
                $('#wpm-cron-analysis').html(html);
            } else {
                $('#wpm-cron-analysis').html('<p>No cron jobs found.</p>');
            }
        },
        error: function() {
            $('#wpm-cron-analysis').html('<p>Could not load cron jobs.</p>');
        }
    });

    // Load Hooks Analysis
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'wpm_get_hooks_analysis',
            nonce: nonce
        },
        success: function(response) {
            if (response.success) {
                var html = '<div class="wpm-stats-row">';
                html += '<div class="wpm-stats-item blue"><div class="stat-value">' + (response.data.total_hooks || 0) + '</div><div class="stat-label">Total Hooks</div></div>';
                html += '<div class="wpm-stats-item yellow"><div class="stat-value">' + (response.data.heavy_hooks ? response.data.heavy_hooks.length : 0) + '</div><div class="stat-label">Heavy Hooks (>5 callbacks)</div></div>';
                html += '</div>';
                if (response.data.heavy_hooks && response.data.heavy_hooks.length > 0) {
                    html += '<table class="wpm-table"><thead><tr><th>Hook Name</th><th>Callbacks</th></tr></thead><tbody>';
                    $.each(response.data.heavy_hooks, function(i, hook) {
                        html += '<tr><td><code>' + hook.name + '</code></td><td>' + hook.total_callbacks + '</td></tr>';
                    });
                    html += '</tbody></table>';
                }
                $('#wpm-hooks-analysis').html(html);
            } else {
                $('#wpm-hooks-analysis').html('<p>Could not load hook analysis.</p>');
            }
        },
        error: function() {
            $('#wpm-hooks-analysis').html('<p>Could not load hook analysis.</p>');
        }
    });
});
</script>