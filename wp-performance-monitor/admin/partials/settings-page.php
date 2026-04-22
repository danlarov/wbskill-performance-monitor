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
        <h1><?php _e('Plugin Settings', 'wp-performance-monitor'); ?></h1>
        <p><?php _e('Configure Performance Monitor to suit your needs', 'wp-performance-monitor'); ?></p>
    </div>

    <?php
    $options = get_option('wpm_options', array(
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
    ));
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpm_save_settings'])) {
        check_admin_referer('wpm_settings');
        
        $options['auto_scan_enabled'] = $_POST['auto_scan_enabled'] ?? 'no';
        $options['auto_scan_frequency'] = $_POST['auto_scan_frequency'] ?? 'daily';
        $options['email_reports'] = $_POST['email_reports'] ?? 'no';
        $options['admin_email'] = sanitize_email($_POST['admin_email']);
        $options['scan_plugins'] = $_POST['scan_plugins'] ?? 'no';
        $options['scan_theme'] = $_POST['scan_theme'] ?? 'no';
        $options['scan_database'] = $_POST['scan_database'] ?? 'no';
        $options['performance_threshold'] = floatval($_POST['performance_threshold']);
        $options['enable_logging'] = $_POST['enable_logging'] ?? 'no';
        $options['keep_logs_days'] = intval($_POST['keep_logs_days']);
        $options['dashboard_widget'] = $_POST['dashboard_widget'] ?? 'no';
        $options['admin_bar_menu'] = $_POST['admin_bar_menu'] ?? 'no';
        
        update_option('wpm_options', $options);
        echo '<div class="wpm-alert wpm-alert-success">' . __('Settings saved successfully!', 'wp-performance-monitor') . '</div>';
    }
    ?>

    <form method="post" action="">
        <?php wp_nonce_field('wpm_settings'); ?>
        
        <!-- General Settings -->
        <div class="wpm-card">
            <h3><?php _e('General Settings', 'wp-performance-monitor'); ?></h3>
            
            <div class="wpm-setting">
                <label class="wpm-setting-label">
                    <input type="checkbox" name="auto_scan_enabled" value="yes" <?php checked($options['auto_scan_enabled'], 'yes'); ?>>
                    <?php _e('Enable Automatic Scans', 'wp-performance-monitor'); ?>
                </label>
                <p class="wpm-setting-desc"><?php _e('Automatically scan your site on a schedule.', 'wp-performance-monitor'); ?></p>
            </div>
            
            <div class="wpm-setting">
                <label class="wpm-setting-label"><?php _e('Scan Frequency', 'wp-performance-monitor'); ?></label>
                <select name="auto_scan_frequency" class="wpm-select">
                    <option value="daily" <?php selected($options['auto_scan_frequency'], 'daily'); ?>><?php _e('Daily', 'wp-performance-monitor'); ?></option>
                    <option value="weekly" <?php selected($options['auto_scan_frequency'], 'weekly'); ?>><?php _e('Weekly', 'wp-performance-monitor'); ?></option>
                    <option value="monthly" <?php selected($options['auto_scan_frequency'], 'monthly'); ?>><?php _e('Monthly', 'wp-performance-monitor'); ?></option>
                </select>
            </div>
            
            <div class="wpm-setting">
                <label class="wpm-setting-label">
                    <input type="checkbox" name="email_reports" value="yes" <?php checked($options['email_reports'], 'yes'); ?>>
                    <?php _e('Send Email Reports', 'wp-performance-monitor'); ?>
                </label>
                <p class="wpm-setting-desc"><?php _e('Receive performance reports via email.', 'wp-performance-monitor'); ?></p>
            </div>
            
            <div class="wpm-setting">
                <label class="wpm-setting-label"><?php _e('Admin Email', 'wp-performance-monitor'); ?></label>
                <input type="email" name="admin_email" value="<?php echo esc_attr($options['admin_email']); ?>" class="wpm-input" style="width:300px;">
            </div>
        </div>
        
        <!-- Scan Settings -->
        <div class="wpm-card">
            <h3><?php _e('Scan Settings', 'wp-performance-monitor'); ?></h3>
            
            <div class="wpm-setting">
                <label class="wpm-setting-label">
                    <input type="checkbox" name="scan_plugins" value="yes" <?php checked($options['scan_plugins'], 'yes'); ?>>
                    <?php _e('Scan Plugins', 'wp-performance-monitor'); ?>
                </label>
            </div>
            
            <div class="wpm-setting">
                <label class="wpm-setting-label">
                    <input type="checkbox" name="scan_theme" value="yes" <?php checked($options['scan_theme'], 'yes'); ?>>
                    <?php _e('Scan Theme', 'wp-performance-monitor'); ?>
                </label>
            </div>
            
            <div class="wpm-setting">
                <label class="wpm-setting-label">
                    <input type="checkbox" name="scan_database" value="yes" <?php checked($options['scan_database'], 'yes'); ?>>
                    <?php _e('Scan Database', 'wp-performance-monitor'); ?>
                </label>
            </div>
            
            <div class="wpm-setting">
                <label class="wpm-setting-label"><?php _e('Performance Threshold (seconds)', 'wp-performance-monitor'); ?></label>
                <input type="number" name="performance_threshold" value="<?php echo $options['performance_threshold']; ?>" step="0.01" min="0" max="1" class="wpm-input" style="width:100px;">
                <p class="wpm-setting-desc"><?php _e('Plugins taking longer than this will be marked as slow.', 'wp-performance-monitor'); ?></p>
            </div>
        </div>
        
        <!-- Display Settings -->
        <div class="wpm-card">
            <h3><?php _e('Display Settings', 'wp-performance-monitor'); ?></h3>
            
            <div class="wpm-setting">
                <label class="wpm-setting-label">
                    <input type="checkbox" name="dashboard_widget" value="yes" <?php checked($options['dashboard_widget'], 'yes'); ?>>
                    <?php _e('Show Dashboard Widget', 'wp-performance-monitor'); ?>
                </label>
                <p class="wpm-setting-desc"><?php _e('Display performance summary on WordPress dashboard.', 'wp-performance-monitor'); ?></p>
            </div>
            
            <div class="wpm-setting">
                <label class="wpm-setting-label">
                    <input type="checkbox" name="admin_bar_menu" value="yes" <?php checked($options['admin_bar_menu'], 'yes'); ?>>
                    <?php _e('Show Admin Bar Menu', 'wp-performance-monitor'); ?>
                </label>
                <p class="wpm-setting-desc"><?php _e('Quick access to performance tools from admin bar.', 'wp-performance-monitor'); ?></p>
            </div>
        </div>
        
        <!-- Logging Settings -->
        <div class="wpm-card">
            <h3><?php _e('Logging Settings', 'wp-performance-monitor'); ?></h3>
            
            <div class="wpm-setting">
                <label class="wpm-setting-label">
                    <input type="checkbox" name="enable_logging" value="yes" <?php checked($options['enable_logging'], 'yes'); ?>>
                    <?php _e('Enable Logging', 'wp-performance-monitor'); ?>
                </label>
                <p class="wpm-setting-desc"><?php _e('Store performance logs for debugging.', 'wp-performance-monitor'); ?></p>
            </div>
            
            <div class="wpm-setting">
                <label class="wpm-setting-label"><?php _e('Keep Logs (days)', 'wp-performance-monitor'); ?></label>
                <input type="number" name="keep_logs_days" value="<?php echo $options['keep_logs_days']; ?>" min="1" max="365" class="wpm-input" style="width:100px;">
            </div>
        </div>
        
        <!-- Support & Donate -->
        <div class="wpm-card">
            <h3><?php _e('Support Development', 'wp-performance-monitor'); ?></h3>
            <p><?php _e('If this plugin helps you, consider supporting future development:', 'wp-performance-monitor'); ?></p>
            <div class="wpm-donate-buttons">
                <a href="https://yoomoney.ru/fundraise/1GV365N919A.260405" class="wpm-button" target="_blank">
                    💝 <?php _e('Donate via YooMoney', 'wp-performance-monitor'); ?>
                </a>
            </div>
            <hr style="margin: 20px 0;">
            <p>
                <strong><?php _e('Contact:', 'wp-performance-monitor'); ?></strong><br>
                📧 <?php _e('Email:', 'wp-performance-monitor'); ?> camlife73@gmail.com<br>
                🌐 <?php _e('Website:', 'wp-performance-monitor'); ?> https://wbskill.ru<br>
                🐙 <?php _e('GitHub:', 'wp-performance-monitor'); ?> https://github.com/danlarov/wp-wp-performance-monitor
            </p>
        </div>
        
        <div class="wpm-save-section">
            <button type="submit" name="wpm_save_settings" class="wpm-button wpm-button-primary"><?php _e('Save All Settings', 'wp-performance-monitor'); ?></button>
        </div>
    </form>
</div>

<style>
.wpm-setting {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}
.wpm-setting:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
.wpm-setting-label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}
.wpm-setting-desc {
    margin: 5px 0 0 0;
    font-size: 12px;
    color: #666;
}
.wpm-donate-buttons {
    display: flex;
    gap: 15px;
    margin: 15px 0;
    flex-wrap: wrap;
}
.wpm-save-section {
    margin-top: 20px;
    text-align: right;
}
.wpm-button-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 12px 30px;
    font-size: 16px;
}
</style>