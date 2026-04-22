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
class WPM_Hooks_Analyzer {
    
    private $hooks_data = array();
    private $total_hooks = 0;
    
    public function __construct() {
        add_action('shutdown', array($this, 'analyze_hooks'), 9999);
    }
    
    /**
     * Анализ всех хуков
     */
    public function analyze_hooks() {
        global $wp_filter, $wp_actions;
        
        $this->total_hooks = count($wp_actions);
        
        foreach ($wp_filter as $hook_name => $hook_obj) {
            $callbacks = $hook_obj->callbacks;
            $hook_data = array(
                'name' => $hook_name,
                'total_callbacks' => 0,
                'priorities' => array(),
                'callbacks' => array(),
                'memory_impact' => 0
            );
            
            foreach ($callbacks as $priority => $priority_callbacks) {
                $hook_data['priorities'][$priority] = count($priority_callbacks);
                $hook_data['total_callbacks'] += count($priority_callbacks);
                
                foreach ($priority_callbacks as $callback) {
                    $callback_info = $this->get_callback_info($callback);
                    $hook_data['callbacks'][] = $callback_info;
                    $hook_data['memory_impact'] += $callback_info['memory'] ?? 0;
                }
            }
            
            if ($hook_data['total_callbacks'] > 5) {
                $this->hooks_data[] = $hook_data;
            }
        }
        
        // Сортировка по количеству колбэков
        usort($this->hooks_data, function($a, $b) {
            return $b['total_callbacks'] - $a['total_callbacks'];
        });
        
        // Сохраняем данные
        $this->save_hooks_data();
    }
    
    /**
     * Получение информации о колбэке
     */
    private function get_callback_info($callback) {
        $info = array(
            'type' => 'unknown',
            'name' => 'unknown',
            'file' => '',
            'line' => 0,
            'memory' => 0
        );
        
        if (is_string($callback['function'])) {
            $info['type'] = 'function';
            $info['name'] = $callback['function'];
            
            // Попытка найти файл и строку
            $reflection = new ReflectionFunction($callback['function']);
            $info['file'] = $reflection->getFileName();
            $info['line'] = $reflection->getStartLine();
            
        } elseif (is_array($callback['function'])) {
            if (is_object($callback['function'][0])) {
                $info['type'] = 'method';
                $info['name'] = get_class($callback['function'][0]) . '::' . $callback['function'][1];
                
                $reflection = new ReflectionMethod($callback['function'][0], $callback['function'][1]);
                $info['file'] = $reflection->getFileName();
                $info['line'] = $reflection->getStartLine();
            } else {
                $info['type'] = 'static_method';
                $info['name'] = $callback['function'][0] . '::' . $callback['function'][1];
            }
        } elseif (is_object($callback['function'])) {
            $info['type'] = 'closure';
            $info['name'] = 'Closure';
        }
        
        return $info;
    }
    
    /**
     * Сохранение данных о хуках
     */
    private function save_hooks_data() {
        set_transient('wpm_hooks_analysis', array(
            'total_hooks' => $this->total_hooks,
            'heavy_hooks' => $this->hooks_data,
            'timestamp' => current_time('timestamp')
        ), DAY_IN_SECONDS);
    }
    
    /**
     * Получение данных о хуках
     */
    public function get_hooks_data() {
        $data = get_transient('wpm_hooks_analysis');
        if (!$data) {
            $this->analyze_hooks();
            $data = get_transient('wpm_hooks_analysis');
        }
        return $data;
    }
    
    /**
     * Получение рекомендаций
     */
    public function get_recommendations() {
        $data = $this->get_hooks_data();
        $recommendations = array();
        
        if (!$data) {
            return $recommendations;
        }
        
        if ($data['total_hooks'] > 500) {
            $recommendations[] = array(
                'severity' => 'high',
                'message' => sprintf(__('Your site has %d executed hooks. Too many hooks can slow down your site.', 'wp-performance-monitor'), $data['total_hooks']),
                'action' => 'reduce_hooks'
            );
        }
        
        foreach ($data['heavy_hooks'] as $hook) {
            if ($hook['total_callbacks'] > 20) {
                $recommendations[] = array(
                    'severity' => 'medium',
                    'message' => sprintf(__('Hook "%s" has %d callbacks. Consider removing unnecessary ones.', 'wp-performance-monitor'), $hook['name'], $hook['total_callbacks']),
                    'action' => 'review_hook'
                );
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Поиск конкретного хука
     */
    public function find_hook($hook_name) {
        global $wp_filter;
        
        if (isset($wp_filter[$hook_name])) {
            $hook_obj = $wp_filter[$hook_name];
            $callbacks = $hook_obj->callbacks;
            
            $result = array(
                'name' => $hook_name,
                'exists' => true,
                'total_callbacks' => 0,
                'callbacks' => array()
            );
            
            foreach ($callbacks as $priority => $priority_callbacks) {
                $result['total_callbacks'] += count($priority_callbacks);
                foreach ($priority_callbacks as $callback) {
                    $result['callbacks'][] = $this->get_callback_info($callback);
                }
            }
            
            return $result;
        }
        
        return array('exists' => false);
    }
}