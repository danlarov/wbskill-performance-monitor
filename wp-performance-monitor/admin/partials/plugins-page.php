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
        <h1><?php _e('Plugins Performance Analysis', 'wp-performance-monitor'); ?></h1>
        <p><?php _e('Detailed analysis of each plugin\'s impact on your site performance', 'wp-performance-monitor'); ?></p>
    </div>

    <?php
    $all_plugins = get_plugins();
    $active_plugins = get_option('active_plugins');
    $monitor = new WPM_Monitor();
    $plugin_performance = $monitor->get_plugin_performance();
    
    function wpm_calc_plugin_score($load_time) {
        if ($load_time <= 0.01) return 100;
        if ($load_time <= 0.02) return 90;
        if ($load_time <= 0.03) return 80;
        if ($load_time <= 0.05) return 70;
        if ($load_time <= 0.1) return 50;
        if ($load_time <= 0.2) return 30;
        return 10;
    }
    
    $total_time = 0;
    foreach ($plugin_performance as $perf) {
        $total_time += isset($perf['time']) ? $perf['time'] : 0;
    }
    ?>

    <div class="wpm-grid">
        <div class="wpm-card">
            <h3><?php _e('Total Plugins', 'wp-performance-monitor'); ?></h3>
            <div class="wpm-metric-value"><?php echo count($all_plugins); ?></div>
            <div class="wpm-metric-label"><?php _e('Installed', 'wp-performance-monitor'); ?></div>
        </div>
        <div class="wpm-card">
            <h3><?php _e('Active Plugins', 'wp-performance-monitor'); ?></h3>
            <div class="wpm-metric-value"><?php echo count($active_plugins); ?></div>
            <div class="wpm-metric-label"><?php _e('Currently Active', 'wp-performance-monitor'); ?></div>
        </div>
        <div class="wpm-card">
            <h3><?php _e('Performance Impact', 'wp-performance-monitor'); ?></h3>
            <div class="wpm-metric-value"><?php echo round($total_time, 3); ?> s</div>
            <div class="wpm-metric-label"><?php _e('Total Plugin Load Time', 'wp-performance-monitor'); ?></div>
        </div>
    </div>

    <div class="wpm-card">
        <h3><?php _e('Plugin Performance Details', 'wp-performance-monitor'); ?></h3>
        
        <div class="wpm-filters">
            <input type="text" id="wpm-plugin-search" placeholder="<?php _e('Search plugins...', 'wp-performance-monitor'); ?>" class="wpm-search">
            <select id="wpm-plugin-filter" class="wpm-filter">
                <option value="all"><?php _e('All Plugins', 'wp-performance-monitor'); ?></option>
                <option value="active"><?php _e('Active Only', 'wp-performance-monitor'); ?></option>
                <option value="inactive"><?php _e('Inactive Only', 'wp-performance-monitor'); ?></option>
            </select>
        </div>

        <table class="wpm-table" id="wpm-plugins-table">
            <thead>
                <tr>
                    <th><?php _e('Plugin', 'wp-performance-monitor'); ?></th>
                    <th><?php _e('Version', 'wp-performance-monitor'); ?></th>
                    <th><?php _e('Status', 'wp-performance-monitor'); ?></th>
                    <th><?php _e('Load Time', 'wp-performance-monitor'); ?></th>
                    <th><?php _e('Memory', 'wp-performance-monitor'); ?></th>
                    <th><?php _e('Queries', 'wp-performance-monitor'); ?></th>
                    <th><?php _e('Score', 'wp-performance-monitor'); ?></th>
                    <th><?php _e('Actions', 'wp-performance-monitor'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_plugins as $plugin_file => $plugin_data):
                    $is_active = in_array($plugin_file, $active_plugins);
                    $perf = isset($plugin_performance[$plugin_file]) ? $plugin_performance[$plugin_file] : array('time' => 0, 'queries' => 0, 'memory' => 0);
                    $load_time = isset($perf['time']) ? $perf['time'] : 0;
                    $score = $is_active ? wpm_calc_plugin_score($load_time) : 100;
                    $score_class = $score >= 80 ? 'wpm-score-excellent' : ($score >= 60 ? 'wpm-score-good' : 'wpm-score-poor');
                ?>
                    <tr data-status="<?php echo $is_active ? 'active' : 'inactive'; ?>">
                        <td>
                            <strong><?php echo esc_html($plugin_data['Name']); ?></strong>
                            <?php if (!empty($plugin_data['Description'])): ?>
                                <div class="wpm-plugin-desc"><?php echo esc_html(substr($plugin_data['Description'], 0, 100)); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($plugin_data['Version']); ?></td>
                        <td>
                            <span class="wpm-badge <?php echo $is_active ? 'wpm-badge-active' : 'wpm-badge-inactive'; ?>">
                                <?php echo $is_active ? __('Active', 'wp-performance-monitor') : __('Inactive', 'wp-performance-monitor'); ?>
                            </span>
                        </td>
                        <td><?php echo round($load_time, 4); ?> s</td>
                        <td><?php echo round(isset($perf['memory']) ? $perf['memory'] / 1024 / 1024 : 0, 2); ?> MB</td>
                        <td><?php echo isset($perf['queries']) ? $perf['queries'] : 0; ?></td>
                        <td>
                            <div class="wpm-score-cell">
                                <div class="wpm-progress-bar" style="width:80px;">
                                    <div class="wpm-progress-fill" style="width: <?php echo $score; ?>%; background: <?php echo $score >= 80 ? '#10b981' : ($score >= 60 ? '#f59e0b' : '#ef4444'); ?>"></div>
                                </div>
                                <span class="wpm-score-badge <?php echo $score_class; ?>"><?php echo $score; ?>%</span>
                            </div>
                         </td>
                        <td>
                            <button class="wpm-button-small wpm-plugin-details" data-plugin="<?php echo esc_attr($plugin_file); ?>">
                                <?php _e('Details', 'wp-performance-monitor'); ?>
                            </button>
                         </td>
                     </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.wpm-plugin-desc {
    font-size: 11px;
    color: #666;
    margin-top: 3px;
}
.wpm-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.wpm-search {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    max-width: 300px;
}
.wpm-filter {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
}
.wpm-score-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}
.wpm-score-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
}
.wpm-score-excellent {
    background: #10b981;
    color: white;
}
.wpm-score-good {
    background: #f59e0b;
    color: white;
}
.wpm-score-poor {
    background: #ef4444;
    color: white;
}
.wpm-progress-bar {
    background: #e5e7eb;
    border-radius: 10px;
    height: 6px;
    overflow: hidden;
}
.wpm-progress-fill {
    height: 100%;
    border-radius: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#wpm-plugin-search').on('keyup', function() {
        var search = $(this).val().toLowerCase();
        $('#wpm-plugins-table tbody tr').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(search) > -1);
        });
    });
    
    $('#wpm-plugin-filter').on('change', function() {
        var filter = $(this).val();
        $('#wpm-plugins-table tbody tr').each(function() {
            var status = $(this).data('status');
            if (filter === 'all') {
                $(this).show();
            } else if (filter === 'active' && status === 'active') {
                $(this).show();
            } else if (filter === 'inactive' && status === 'inactive') {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    $('.wpm-plugin-details').on('click', function() {
        var plugin = $(this).data('plugin');
        alert('Details for: ' + plugin);
    });
});
</script>