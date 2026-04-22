<?php
// Если файл вызван не через WordPress, прекращаем выполнение
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Удаляем все данные плагина при удалении
global $wpdb;

// Удаляем таблицы
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wpm_scans");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wpm_metrics");

// Удаляем опции
delete_option('wpm_options');

// Удаляем все транзиенты
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_wpm_%'");

// Удаляем расписания
wp_clear_scheduled_hook('wpm_daily_scan');
wp_clear_scheduled_hook('wpm_background_scan');

// Удаляем пользовательские роли если есть
remove_role('wpm_viewer');