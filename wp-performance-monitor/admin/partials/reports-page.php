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
        <h1><?php _e('Performance Reports', 'wp-performance-monitor'); ?></h1>
        <p><?php _e('View and export detailed performance reports', 'wp-performance-monitor'); ?></p>
    </div>

    <div class="wpm-grid">
        <!-- Export Options -->
        <div class="wpm-card">
            <h3><?php _e('Export Report', 'wp-performance-monitor'); ?></h3>
            <div class="wpm-export-options">
                <select id="wpm-export-format" class="wpm-select">
                    <option value="json">JSON Format</option>
                    <option value="csv">CSV Format</option>
                    <option value="html">HTML Report</option>
                </select>
                <select id="wpm-export-period" class="wpm-select">
                    <option value="last"><?php _e('Last Scan', 'wp-performance-monitor'); ?></option>
                    <option value="week"><?php _e('Last Week', 'wp-performance-monitor'); ?></option>
                    <option value="month"><?php _e('Last Month', 'wp-performance-monitor'); ?></option>
                    <option value="all"><?php _e('All Time', 'wp-performance-monitor'); ?></option>
                </select>
                <button id="wpm-export-report" class="wpm-button"><?php _e('Export', 'wp-performance-monitor'); ?></button>
            </div>
        </div>

        <!-- Email Report -->
        <div class="wpm-card">
            <h3><?php _e('Email Report', 'wp-performance-monitor'); ?></h3>
            <div class="wpm-email-options">
                <input type="email" id="wpm-report-email" placeholder="<?php _e('Email address', 'wp-performance-monitor'); ?>" value="<?php echo get_option('admin_email'); ?>" class="wpm-input">
                <select id="wpm-email-frequency" class="wpm-select">
                    <option value="daily"><?php _e('Daily', 'wp-performance-monitor'); ?></option>
                    <option value="weekly"><?php _e('Weekly', 'wp-performance-monitor'); ?></option>
                    <option value="monthly"><?php _e('Monthly', 'wp-performance-monitor'); ?></option>
                </select>
                <button id="wpm-schedule-report" class="wpm-button"><?php _e('Schedule', 'wp-performance-monitor'); ?></button>
            </div>
        </div>
    </div>

    <!-- Reports List -->
    <div class="wpm-card">
        <h3><?php _e('Scan History', 'wp-performance-monitor'); ?></h3>
        
        <div class="wpm-filters">
            <input type="text" id="wpm-report-search" placeholder="<?php _e('Search reports...', 'wp-performance-monitor'); ?>" class="wpm-search">
            <select id="wpm-report-filter" class="wpm-filter">
                <option value="all"><?php _e('All Reports', 'wp-performance-monitor'); ?></option>
                <option value="full"><?php _e('Full Scans', 'wp-performance-monitor'); ?></option>
                <option value="quick"><?php _e('Quick Scans', 'wp-performance-monitor'); ?></option>
            </select>
        </div>

        <table class="wpm-table" id="wpm-reports-table">
            <thead>
                <tr>
                    <th><?php _e('Date', 'wp-performance-monitor'); ?></th>
                    <th><?php _e('Type', 'wp-performance-monitor'); ?></th>
                    <th><?php _e('Plugins', 'wp-performance-monitor'); ?></th>
                    <th><?php _e('Issues', 'wp-performance-monitor'); ?></th>
                    <th><?php _e('Score', 'wp-performance-monitor'); ?></th>
                    <th><?php _e('Actions', 'wp-performance-monitor'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                $reports = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpm_scans ORDER BY scan_time DESC LIMIT 50");
                foreach ($reports as $report):
                    $score = $report->total_plugins > 0 ? round((1 - ($report->slow_plugins / $report->total_plugins)) * 100) : 100;
                ?>
                    <tr data-type="<?php echo esc_attr($report->scan_type); ?>">
                        <td><?php echo date_i18n(get_option('date_format') . ' H:i:s', strtotime($report->scan_time)); ?></td>
                        <td>
                            <span class="wpm-badge"><?php echo esc_html($report->scan_type); ?></span>
                        </td>
                        <td><?php echo $report->total_plugins; ?></td>
                        <td><?php echo $report->slow_plugins; ?></td>
                        <td>
                            <div class="wpm-score-cell">
                                <div class="wpm-progress-bar" style="width:80px;">
                                    <div class="wpm-progress-fill" style="width: <?php echo $score; ?>%; background: <?php echo $score >= 80 ? '#10b981' : ($score >= 60 ? '#f59e0b' : '#ef4444'); ?>"></div>
                                </div>
                                <span><?php echo $score; ?>%</span>
                            </div>
                        </td>
                        <td>
                            <button class="wpm-button-small wpm-view-report" data-id="<?php echo $report->id; ?>">
                                <?php _e('View', 'wp-performance-monitor'); ?>
                            </button>
                            <button class="wpm-button-small wpm-delete-report" data-id="<?php echo $report->id; ?>">
                                <?php _e('Delete', 'wp-performance-monitor'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Report Modal -->
    <div id="wpm-report-modal" class="wpm-modal" style="display:none;">
        <div class="wpm-modal-content">
            <div class="wpm-modal-header">
                <h3><?php _e('Report Details', 'wp-performance-monitor'); ?></h3>
                <span class="wpm-modal-close">&times;</span>
            </div>
            <div class="wpm-modal-body" id="wpm-report-content">
                <!-- Report content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.wpm-export-options, .wpm-email-options {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.wpm-input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    min-width: 200px;
}
.wpm-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.wpm-modal-content {
    background: white;
    border-radius: 12px;
    max-width: 800px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
}
.wpm-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.wpm-modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #666;
}
.wpm-modal-body {
    padding: 20px;
    overflow-y: auto;
    max-height: calc(80vh - 60px);
}
</style>

<script>
jQuery(document).ready(function($) {
    // Просмотр отчета
    $('.wpm-view-report').on('click', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: wpm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpm_get_report',
                report_id: id,
                nonce: wpm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var content = '<pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
                    $('#wpm-report-content').html(content);
                    $('#wpm-report-modal').fadeIn();
                }
            }
        });
    });
    
   
    $('.wpm-modal-close, .wpm-modal').on('click', function(e) {
        if (e.target === this) {
            $('#wpm-report-modal').fadeOut();
        }
    });
    
   
    $('#wpm-export-report').on('click', function() {
    var format = $('#wpm-export-format').val();
    var period = $('#wpm-export-period').val();
    
    // Create a form and submit it
    var form = $('<form method="POST" action="' + wpm_ajax.ajax_url + '">');
    form.append('<input type="hidden" name="action" value="wpm_export_report">');
    form.append('<input type="hidden" name="format" value="' + format + '">');
    form.append('<input type="hidden" name="period" value="' + period + '">');
    form.append('<input type="hidden" name="nonce" value="' + wpm_ajax.nonce + '">');
    $('body').append(form);
    form.submit();
});
    
    
    $('#wpm-report-filter, #wpm-report-search').on('change keyup', function() {
        var filter = $('#wpm-report-filter').val();
        var search = $('#wpm-report-search').val().toLowerCase();
        
        $('#wpm-reports-table tbody tr').each(function() {
            var type = $(this).data('type');
            var text = $(this).text().toLowerCase();
            var show = true;
            
            if (filter !== 'all' && type !== filter) show = false;
            if (search && !text.includes(search)) show = false;
            
            $(this).toggle(show);
        });
    });
});
</script>