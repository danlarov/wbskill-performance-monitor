<?php
/**
 * Plugin Name: WBSkill Performance Monitor
 * Plugin URI: https://github.com/danlarov/wp-performance-monitor
 * Description: Comprehensive WordPress performance monitoring. Analysis of plugins, databases, and scripts, and speed optimization.
 * Version: 1.0.0
 * Author: Daniel Larin
 * Author Email: camlife73@gmail.com
 * Author URI: https://wbskill.ru
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-performance-monitor
 * Domain Path: /languages
 *
 *
 * @package    Performance_Monitor
 * @author     Daniel Larin
 * @license    GPL-2.0+
 * @copyright  2026 Daniel Larin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Определение констант плагина
define('WPM_VERSION', '1.0.0');
define('WPM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Класс автозагрузки
spl_autoload_register(function ($class) {
    $prefix = 'WPM_';
    $base_dir = WPM_PLUGIN_DIR . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Запуск плагина
function wpm_run_plugin() {
    $plugin = new WPM_Plugin();
    $plugin->run();
}
wpm_run_plugin();

// Регистрация хуков активации/деактивации
register_activation_hook(__FILE__, function() {
    require_once WPM_PLUGIN_DIR . 'includes/class-activator.php';
    WPM_Activator::activate();
});

register_deactivation_hook(__FILE__, function() {
    require_once WPM_PLUGIN_DIR . 'includes/class-deactivator.php';
    WPM_Deactivator::deactivate();
});