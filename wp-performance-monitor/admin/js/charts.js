/**
 * WP Performance Monitor - Charts Library
 */

var WPMCharts = (function($) {
    
    // Chart.js CDN (будет загружен если не доступен)
    function loadChartJS() {
        if (typeof Chart !== 'undefined') {
            return Promise.resolve();
        }
        
        return new Promise(function(resolve, reject) {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    /**
     * Создание круговой диаграммы
     */
    function createPieChart(canvasId, data, labels, colors) {
        return loadChartJS().then(function() {
            var ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors || ['#667eea', '#764ba2', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.raw || 0;
                                    var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    var percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        });
    }
    
    /**
     * Создание столбчатой диаграммы
     */
    function createBarChart(canvasId, data, labels, label) {
    return loadChartJS().then(function() {
        var canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.warn('Canvas element not found:', canvasId);
            return null;
        }
        var ctx = canvas.getContext('2d');
        return new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: [{ label: label || 'Value', data: data, backgroundColor: '#667eea', borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true } } }
        });
    });
}
    
    /**
     * Создание линейной диаграммы
     */
    function createLineChart(canvasId, data, labels, label) {
        return loadChartJS().then(function() {
            var ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label || 'Value',
                        data: data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#e5e7eb'
                            }
                        }
                    }
                }
            });
        });
    }
    
    /**
     * Создание диаграммы-спидометра (gauge)
     */
    function createGaugeChart(canvasId, value, maxValue) {
        return loadChartJS().then(function() {
            var ctx = document.getElementById(canvasId).getContext('2d');
            var percentage = (value / maxValue) * 100;
            
            // Определяем цвет в зависимости от значения
            var color;
            if (percentage >= 80) color = '#10b981';
            else if (percentage >= 60) color = '#f59e0b';
            else color = '#ef4444';
            
            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [percentage, 100 - percentage],
                        backgroundColor: [color, '#e5e7eb'],
                        borderWidth: 0,
                        circumference: 180,
                        rotation: 270
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '70%',
                    plugins: {
                        tooltip: { enabled: false },
                        legend: { display: false }
                    }
                }
            });
        });
    }
    
    /**
     * Создание горизонтальной столбчатой диаграммы
     */
    function createHorizontalBarChart(canvasId, data, labels) {
        return loadChartJS().then(function() {
            var ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: '#667eea',
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: '#e5e7eb'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.raw + ' ms';
                                }
                            }
                        }
                    }
                }
            });
        });
    }
    
    /**
     * Обновление существующей диаграммы
     */
    function updateChart(chart, data, labels) {
        if (!chart) return;
        
        chart.data.datasets[0].data = data;
        if (labels) {
            chart.data.labels = labels;
        }
        chart.update();
    }
    
    /**
     * Создание мини-спарклайна
     */
    function createSparkline(container, data, width, height) {
        if (!data || data.length === 0) return;
        
        var max = Math.max(...data);
        var min = Math.min(...data);
        var range = max - min;
        
        var svg = '<svg width="' + width + '" height="' + height + '" viewBox="0 0 ' + width + ' ' + height + '">';
        svg += '<polyline fill="none" stroke="#667eea" stroke-width="2" points="';
        
        var step = width / (data.length - 1);
        for (var i = 0; i < data.length; i++) {
            var x = i * step;
            var y = height - ((data[i] - min) / range) * height;
            svg += x + ',' + y + ' ';
        }
        
        svg += '" />';
        svg += '</svg>';
        
        $(container).html(svg);
    }
    
    // Публичное API
    return {
        loadChartJS: loadChartJS,
        createPieChart: createPieChart,
        createBarChart: createBarChart,
        createLineChart: createLineChart,
        createGaugeChart: createGaugeChart,
        createHorizontalBarChart: createHorizontalBarChart,
        updateChart: updateChart,
        createSparkline: createSparkline
    };
    
})(jQuery);