/**
 * WP Performance Monitor - Dashboard Scripts
 */

jQuery(document).ready(function($) {
    
    // Инициализация дашборда
    function initDashboard() {
        loadPerformanceMetrics();
        loadPluginPerformanceChart();
        loadPerformanceHistory();
        setupAutoRefresh();
    }
    
    // Загрузка метрик производительности
    function loadPerformanceMetrics() {
        $.ajax({
            url: wpm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpm_get_performance_metrics',
                nonce: wpm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateMetricsDisplay(response.data);
                    updateScoreGauge(response.data.performance_score);
                }
            }
        });
    }
    
    // Обновление отображения метрик
    function updateMetricsDisplay(metrics) {
        $('#wpm-memory-usage').text(metrics.memory_usage + ' MB');
        $('#wpm-page-load-time').text(metrics.page_load_time + ' s');
        $('#wpm-db-size').text(metrics.db_size + ' MB');
        $('#wpm-active-plugins').text(metrics.active_plugins);
        
        // Обновляем прогресс-бары
        $('.wpm-progress-fill').each(function() {
            var target = $(this).data('target');
            if (metrics[target]) {
                $(this).css('width', metrics[target] + '%');
            }
        });
    }
    
    // Обновление спидометра оценки
    function updateScoreGauge(score) {
        var $gauge = $('#wpm-score-gauge');
        if ($gauge.length) {
            var percentage = (score / 100) * 180;
            $gauge.find('.wpm-gauge-fill').css('transform', 'rotate(' + percentage + 'deg)');
            $gauge.find('.wpm-gauge-value-text').text(score);
            
            // Обновляем цвет
            var color;
            if (score >= 80) color = '#10b981';
            else if (score >= 60) color = '#f59e0b';
            else color = '#ef4444';
            
            $gauge.find('.wpm-gauge-fill').css('stroke', color);
        }
    }
    
    // Загрузка графика производительности плагинов
    function loadPluginPerformanceChart() {
        $.ajax({
            url: wpm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpm_get_plugin_performance',
                nonce: wpm_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.length) {
                    var plugins = response.data.map(function(p) { return p.name; });
                    var times = response.data.map(function(p) { return p.time; });
                    
                    WPMCharts.createHorizontalBarChart('wpm-plugin-chart', times, plugins);
                }
            }
        });
    }
    
    // Загрузка истории производительности
    function loadPerformanceHistory() {
        $.ajax({
            url: wpm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpm_get_performance_history',
                nonce: wpm_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.length) {
                    var dates = response.data.map(function(d) { return d.date; });
                    var scores = response.data.map(function(d) { return d.score; });
                    
                    WPMCharts.createLineChart('wpm-history-chart', scores, dates, 'Performance Score');
                }
            }
        });
    }
    
    // Автообновление дашборда
    var refreshInterval;
    function setupAutoRefresh() {
        var $toggle = $('#wpm-auto-refresh');
        if ($toggle.length) {
            $toggle.on('change', function() {
                if ($(this).is(':checked')) {
                    startAutoRefresh();
                } else {
                    stopAutoRefresh();
                }
            });
            
            // Проверяем сохраненное состояние
            if (localStorage.getItem('wpm_auto_refresh') === 'true') {
                $toggle.prop('checked', true);
                startAutoRefresh();
            }
        }
    }
    
    function startAutoRefresh() {
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(function() {
            loadPerformanceMetrics();
        }, 30000); // Каждые 30 секунд
        localStorage.setItem('wpm_auto_refresh', 'true');
    }
    
    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
        localStorage.setItem('wpm_auto_refresh', 'false');
    }
    
    // Обновление дашборда вручную
    $('#wpm-refresh-dashboard').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.text('Refreshing...').prop('disabled', true);
        
        loadPerformanceMetrics();
        loadPluginPerformanceChart();
        loadPerformanceHistory();
        
        setTimeout(function() {
            $btn.text(originalText).prop('disabled', false);
        }, 2000);
    });
    
    // Экспорт отчета
    $('#wpm-export-report').on('click', function() {
        $.ajax({
            url: wpm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpm_export_report',
                format: 'json',
                nonce: wpm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var blob = new Blob([JSON.stringify(response.data, null, 2)], {type: 'application/json'});
                    var link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = 'performance-report-' + new Date().toISOString().split('T')[0] + '.json';
                    link.click();
                    
                    showNotification('success', 'Report exported successfully!');
                }
            }
        });
    });
    
    // Уведомления
    function showNotification(type, message) {
        var $notification = $('<div class="wpm-notification wpm-notification-' + type + '">' + message + '</div>');
        $('.wpm-wrap').prepend($notification);
        
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Инициализация
    initDashboard();
    
    // Добавляем стили для уведомлений
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .wpm-notification {
                position: fixed;
                top: 32px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 8px;
                background: white;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                animation: slideIn 0.3s ease;
            }
            .wpm-notification-success {
                border-left: 4px solid #10b981;
                color: #065f46;
            }
            .wpm-notification-error {
                border-left: 4px solid #ef4444;
                color: #991b1b;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `)
        .appendTo('head');
});