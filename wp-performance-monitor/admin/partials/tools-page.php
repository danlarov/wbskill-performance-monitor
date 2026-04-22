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
        <h1><?php _e('Performance Tools', 'wp-performance-monitor'); ?></h1>
        <p><?php _e('Advanced tools to optimize and maintain your WordPress site', 'wp-performance-monitor'); ?></p>
    </div>

    <div class="wpm-grid">
        <!-- Heartbeat Control -->
        <div class="wpm-card">
            <h3><?php _e('WordPress Heartbeat Control', 'wp-performance-monitor'); ?></h3>
            <p><?php _e('The WordPress Heartbeat API can cause high CPU usage. Control how often it runs.', 'wp-performance-monitor'); ?></p>
            <div class="wpm-tool-control">
                <select id="wpm-heartbeat-frequency" class="wpm-select">
                    <option value="default"><?php _e('Default (60 seconds)', 'wp-performance-monitor'); ?></option>
                    <option value="120"><?php _e('Every 2 minutes', 'wp-performance-monitor'); ?></option>
                    <option value="180"><?php _e('Every 3 minutes', 'wp-performance-monitor'); ?></option>
                    <option value="300"><?php _e('Every 5 minutes', 'wp-performance-monitor'); ?></option>
                    <option value="disable"><?php _e('Disable completely', 'wp-performance-monitor'); ?></option>
                </select>
                <button id="wpm-save-heartbeat" class="wpm-button"><?php _e('Save', 'wp-performance-monitor'); ?></button>
            </div>
        </div>

        <!-- Post Revisions Limit -->
        <div class="wpm-card">
            <h3><?php _e('Post Revisions Limit', 'wp-performance-monitor'); ?></h3>
            <p><?php _e('Limit the number of revisions saved per post to reduce database bloat.', 'wp-performance-monitor'); ?></p>
            <div class="wpm-tool-control">
                <input type="number" id="wpm-revisions-limit" placeholder="5" value="<?php echo defined('WP_POST_REVISIONS') && is_numeric(WP_POST_REVISIONS) ? WP_POST_REVISIONS : ''; ?>" class="wpm-input" style="width:100px;">
                <button id="wpm-save-revisions" class="wpm-button"><?php _e('Apply', 'wp-performance-monitor'); ?></button>
            </div>
        </div>
    </div>

    <!-- Performance Test -->
    <div class="wpm-card">
        <h3><?php _e('Performance Test', 'wp-performance-monitor'); ?></h3>
        <p><?php _e('Test your site\'s load time from different perspectives.', 'wp-performance-monitor'); ?></p>
        
        <div class="wpm-test-controls">
            <input type="url" id="wpm-test-url" placeholder="<?php _e('Enter URL to test', 'wp-performance-monitor'); ?>" value="<?php echo home_url(); ?>" class="wpm-input" style="flex:1;">
            <button id="wpm-run-test" class="wpm-button"><?php _e('Run Test', 'wp-performance-monitor'); ?></button>
        </div>
        
        <div id="wpm-test-results" style="display:none; margin-top:20px;">
            <div class="wpm-test-grid">
                <div class="wpm-test-item">
                    <div class="wpm-test-label"><?php _e('Load Time', 'wp-performance-monitor'); ?></div>
                    <div class="wpm-test-value" id="wpm-test-load-time">-</div>
                </div>
                <div class="wpm-test-item">
                    <div class="wpm-test-label"><?php _e('Page Size', 'wp-performance-monitor'); ?></div>
                    <div class="wpm-test-value" id="wpm-test-page-size">-</div>
                </div>
                <div class="wpm-test-item">
                    <div class="wpm-test-label"><?php _e('Status', 'wp-performance-monitor'); ?></div>
                    <div class="wpm-test-value" id="wpm-test-status">-</div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div class="wpm-card">
        <h3><?php _e('System Information', 'wp-performance-monitor'); ?></h3>
        <button id="wpm-copy-system-info" class="wpm-button"><?php _e('Copy System Info', 'wp-performance-monitor'); ?></button>
        
        <div id="wpm-system-info" style="margin-top:15px;">
            <?php
            global $wpdb;
            $system_info = array(
                'WordPress Version' => get_bloginfo('version'),
                'PHP Version' => phpversion(),
                'MySQL Version' => $wpdb->get_var("SELECT VERSION()"),
                'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'WP Memory Limit' => WP_MEMORY_LIMIT,
                'WP Max Upload Size' => size_format(wp_max_upload_size()),
                'WP Debug Mode' => defined('WP_DEBUG') && WP_DEBUG ? 'Yes' : 'No',
                'Active Theme' => wp_get_theme()->get('Name'),
                'Active Plugins' => count(get_option('active_plugins')),
                'Site URL' => home_url(),
                'Admin Email' => get_option('admin_email')
            );
            ?>
            <table class="wpm-table">
                <?php foreach ($system_info as $key => $value): ?>
                    <tr>
                        <th style="width:200px;"><?php echo esc_html($key); ?></th>
                        <td><?php echo esc_html($value); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- Cache Management -->
    <div class="wpm-card">
        <h3><?php _e('Cache Management', 'wp-performance-monitor'); ?></h3>
        <p><?php _e('Clear various caches to resolve issues or see changes immediately.', 'wp-performance-monitor'); ?></p>
        
        <div class="wpm-cache-buttons">
            <button id="wpm-clear-transients" class="wpm-button"><?php _e('Clear Transients', 'wp-performance-monitor'); ?></button>
            <button id="wpm-clear-wp-cache" class="wpm-button"><?php _e('Clear WP Cache', 'wp-performance-monitor'); ?></button>
            <button id="wpm-clear-opcache" class="wpm-button"><?php _e('Clear OPcache', 'wp-performance-monitor'); ?></button>
        </div>
    </div>
</div>

<style>
.wpm-tool-control {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: 15px;
}
.wpm-test-controls {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.wpm-test-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}
.wpm-test-item {
    text-align: center;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
}
.wpm-test-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}
.wpm-test-value {
    font-size: 24px;
    font-weight: bold;
    color: #667eea;
}
.wpm-cache-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
}
</style>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var nonce = '<?php echo wp_create_nonce('wpm_ajax_nonce'); ?>';
    
    // Save Heartbeat Settings
    $('#wpm-save-heartbeat').on('click', function() {
        var frequency = $('#wpm-heartbeat-frequency').val();
        var $btn = $(this);
        var originalText = $btn.text();
        
        $btn.text('Saving...').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'wpm_save_heartbeat_settings',
            frequency: frequency,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Error: ' + response.data.message);
            }
            $btn.text(originalText).prop('disabled', false);
        }).fail(function() {
            alert('AJAX error occurred');
            $btn.text(originalText).prop('disabled', false);
        });
    });
    
    // Save Revisions Limit
    $('#wpm-save-revisions').on('click', function() {
        var limit = $('#wpm-revisions-limit').val();
        var $btn = $(this);
        var originalText = $btn.text();
        
        if (limit < 0 || limit === '') {
            alert('Please enter a valid number (0 = unlimited)');
            return;
        }
        
        $btn.text('Saving...').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'wpm_save_revisions_limit',
            limit: limit,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Error: ' + response.data.message);
            }
            $btn.text(originalText).prop('disabled', false);
        }).fail(function() {
            alert('AJAX error occurred');
            $btn.text(originalText).prop('disabled', false);
        });
    });
    
    // Performance Test
    $('#wpm-run-test').on('click', function() {
        var url = $('#wpm-test-url').val();
        var $btn = $(this);
        var originalText = $btn.text();
        
        $btn.text('Testing...').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'wpm_test_performance',
            url: url,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                $('#wpm-test-load-time').text(response.data.load_time + ' s');
                $('#wpm-test-page-size').text(response.data.size + ' KB');
                $('#wpm-test-status').text(response.data.status);
                $('#wpm-test-results').fadeIn();
            } else {
                alert('Test failed');
            }
            $btn.text(originalText).prop('disabled', false);
        }).fail(function() {
            alert('AJAX error occurred');
            $btn.text(originalText).prop('disabled', false);
        });
    });
    
    // Copy System Info
    $('#wpm-copy-system-info').on('click', function() {
        var text = '';
        $('#wpm-system-info table tr').each(function() {
            text += $(this).find('th').text() + ': ' + $(this).find('td').text() + '\n';
        });
        
        navigator.clipboard.writeText(text).then(function() {
            alert('System info copied to clipboard!');
        });
    });
    
    // Clear Transients
    $('#wpm-clear-transients').on('click', function() {
        if (!confirm('Delete all transients?')) return;
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.text('Clearing...').prop('disabled', true);
        $.post(ajaxurl, {
            action: 'wpm_clean_transients',
            nonce: nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Error clearing transients');
            }
            $btn.text(originalText).prop('disabled', false);
        }).fail(function() {
            alert('AJAX error occurred');
            $btn.text(originalText).prop('disabled', false);
        });
    });
    
    // Clear WP Cache
    $('#wpm-clear-wp-cache').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.text('Clearing...').prop('disabled', true);
        $.post(ajaxurl, {
            action: 'wpm_clear_wp_cache',
            nonce: nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Error clearing WP cache');
            }
            $btn.text(originalText).prop('disabled', false);
        }).fail(function() {
            alert('AJAX error occurred');
            $btn.text(originalText).prop('disabled', false);
        });
    });
    
    // Clear OPcache
    $('#wpm-clear-opcache').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.text('Clearing...').prop('disabled', true);
        $.post(ajaxurl, {
            action: 'wpm_clear_opcache',
            nonce: nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Error clearing OPcache');
            }
            $btn.text(originalText).prop('disabled', false);
        }).fail(function() {
            alert('AJAX error occurred');
            $btn.text(originalText).prop('disabled', false);
        });
    });
});
</script>