jQuery(document).ready(function($) {
    
    function wpm_init() {
        wpm_init_tabs();
        wpm_init_scan_button();
        wpm_init_optimize_buttons();
        wpm_init_export_buttons();
    }
    
    function wpm_init_tabs() {
        $('.wpm-tab').on('click', function() {
            var tab_id = $(this).data('tab');
            $('.wpm-tab').removeClass('active');
            $('.wpm-tab-content').removeClass('active');
            $(this).addClass('active');
            $('#' + tab_id).addClass('active');
        });
    }
    
    function wpm_init_scan_button() {
        $('#wpm-start-scan').on('click', function() {
            var $btn = $(this);
            var original_text = $btn.text();
            var scan_type = $('#wpm-scan-type').val();
            
            $btn.prop('disabled', true).text('Scanning...');
            
            $.ajax({
                url: wpm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpm_start_scan',
                    scan_type: scan_type,
                    nonce: wpm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        wpm_show_notification('success', 'Scan completed successfully!');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        wpm_show_notification('error', response.data.message || 'Scan failed');
                        $btn.prop('disabled', false).text(original_text);
                    }
                },
                error: function() {
                    wpm_show_notification('error', 'An error occurred during scan');
                    $btn.prop('disabled', false).text(original_text);
                }
            });
        });
    }
    
    function wpm_init_optimize_buttons() {
        $('.wpm-optimize-db').on('click', function() {
            if (!confirm('Are you sure you want to optimize the database?')) return;
            var $btn = $(this);
            $btn.prop('disabled', true).text('Optimizing...');
            $.ajax({
                url: wpm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpm_optimize_tables',
                    nonce: wpm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        wpm_show_notification('success', 'Database optimized successfully!');
                        setTimeout(function() { location.reload(); }, 1500);
                    }
                },
                complete: function() { $btn.prop('disabled', false).text('Optimize Database'); }
            });
        });
        
        $('.wpm-clean-revisions').on('click', function() {
            if (!confirm('Delete all post revisions?')) return;
            var $btn = $(this);
            $btn.prop('disabled', true).text('Cleaning...');
            $.ajax({
                url: wpm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpm_clean_revisions',
                    nonce: wpm_ajax.nonce
                },
                success: function() { wpm_show_notification('success', 'Revisions cleaned!'); },
                complete: function() { $btn.prop('disabled', false).text('Clean Revisions'); }
            });
        });
        
        $('.wpm-clean-transients').on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).text('Cleaning...');
            $.ajax({
                url: wpm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpm_clean_transients',
                    nonce: wpm_ajax.nonce
                },
                success: function() { wpm_show_notification('success', 'Transients cleaned!'); },
                complete: function() { $btn.prop('disabled', false).text('Clean Transients'); }
            });
        });
    }
    
    function wpm_init_export_buttons() {
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
                        link.download = 'performance-report.json';
                        link.click();
                        wpm_show_notification('success', 'Report exported!');
                    }
                }
            });
        });
    }
    
    function wpm_show_notification(type, message) {
        var $notification = $('<div class="wpm-alert wpm-alert-' + type + '">' + message + '</div>');
        $('.wpm-wrap').prepend($notification);
        setTimeout(function() { $notification.fadeOut(function() { $(this).remove(); }); }, 5000);
    }
    
    $(document).on('click', '.wpm-disable-plugin', function() {
        if (!confirm(wpm_ajax.strings.confirm_disable)) return;
        var plugin = $(this).data('plugin');
        var $row = $(this).closest('tr');
        $.ajax({
            url: wpm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpm_disable_plugin',
                plugin: plugin,
                nonce: wpm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut();
                    wpm_show_notification('success', 'Plugin disabled successfully!');
                }
            }
        });
    });
    
    wpm_init();
});