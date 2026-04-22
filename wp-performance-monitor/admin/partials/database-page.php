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
        <h1><?php _e('Database Optimization', 'wp-performance-monitor'); ?></h1>
        <p><?php _e('Clean and optimize your WordPress database for better performance', 'wp-performance-monitor'); ?></p>
    </div>

    <?php
    global $wpdb;
    
    // Force refresh stats
    wp_cache_flush();
    
    $db_total_size = 0;
    $tables = $wpdb->get_results("SHOW TABLE STATUS");
    foreach ($tables as $table) {
        if (strpos($table->Name, $wpdb->prefix) === 0) {
            $db_total_size += ($table->Data_length + $table->Index_length) / 1024 / 1024;
        }
    }
    
    $revisions = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'");
    $auto_drafts = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");
    $trashed_posts = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'");
    $trashed_comments = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'");
    $transients = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
    
    $orphaned_meta = 0;
    $orphaned_meta += (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}postmeta pm LEFT JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID WHERE p.ID IS NULL");
    $orphaned_meta += (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}commentmeta cm LEFT JOIN {$wpdb->prefix}comments c ON cm.comment_id = c.comment_ID WHERE c.comment_ID IS NULL");
    
    $table_count = 0;
    foreach ($tables as $table) {
        if (strpos($table->Name, $wpdb->prefix) === 0) $table_count++;
    }
    ?>

    <div class="wpm-stats-row">
        <div class="wpm-stat-card wpm-stat-green">
            <div class="wpm-stat-value"><?php echo round($db_total_size, 2); ?> MB</div>
            <div class="wpm-stat-label"><?php _e('Database Size', 'wp-performance-monitor'); ?></div>
        </div>
        <div class="wpm-stat-card wpm-stat-blue">
            <div class="wpm-stat-value"><?php echo number_format($revisions + $auto_drafts + $trashed_posts + $trashed_comments + $transients + $orphaned_meta); ?></div>
            <div class="wpm-stat-label"><?php _e('Cleanable Items', 'wp-performance-monitor'); ?></div>
        </div>
        <div class="wpm-stat-card wpm-stat-yellow">
            <div class="wpm-stat-value"><?php echo $table_count; ?></div>
            <div class="wpm-stat-label"><?php _e('Total Tables', 'wp-performance-monitor'); ?></div>
        </div>
    </div>

    <div class="wpm-cleanup-grid">
        <!-- Card 1: Revisions -->
        <div class="wpm-cleanup-card">
            <div class="wpm-card-icon">📝</div>
            <h3 class="wpm-card-title"><?php _e('Post Revisions', 'wp-performance-monitor'); ?></h3>
            <p class="wpm-card-desc"><?php _e('WordPress saves every change to your posts as revisions', 'wp-performance-monitor'); ?></p>
            <div class="wpm-card-count" id="count-revisions"><?php echo number_format($revisions); ?> <span><?php _e('revisions found', 'wp-performance-monitor'); ?></span></div>
            <button class="wpm-cleanup-btn wpm-btn-revisions" data-type="revisions"><?php _e('Clean Revisions', 'wp-performance-monitor'); ?></button>
        </div>

        <!-- Card 2: Auto Drafts -->
        <div class="wpm-cleanup-card">
            <div class="wpm-card-icon">✏️</div>
            <h3 class="wpm-card-title"><?php _e('Auto Drafts', 'wp-performance-monitor'); ?></h3>
            <p class="wpm-card-desc"><?php _e('Automatically saved drafts that were never published', 'wp-performance-monitor'); ?></p>
            <div class="wpm-card-count" id="count-drafts"><?php echo number_format($auto_drafts); ?> <span><?php _e('drafts found', 'wp-performance-monitor'); ?></span></div>
            <button class="wpm-cleanup-btn wpm-btn-drafts" data-type="drafts"><?php _e('Clean Drafts', 'wp-performance-monitor'); ?></button>
        </div>

        <!-- Card 3: Trashed Items -->
        <div class="wpm-cleanup-card">
            <div class="wpm-card-icon">🗑️</div>
            <h3 class="wpm-card-title"><?php _e('Trashed Items', 'wp-performance-monitor'); ?></h3>
            <p class="wpm-card-desc"><?php _e('Posts and comments in trash that can be permanently deleted', 'wp-performance-monitor'); ?></p>
            <div class="wpm-card-count" id="count-trash"><?php echo number_format($trashed_posts + $trashed_comments); ?> <span><?php _e('items in trash', 'wp-performance-monitor'); ?></span></div>
            <button class="wpm-cleanup-btn wpm-btn-trash" data-type="trash"><?php _e('Clean Trash', 'wp-performance-monitor'); ?></button>
        </div>

        <!-- Card 4: Transients -->
        <div class="wpm-cleanup-card">
            <div class="wpm-card-icon">⚡</div>
            <h3 class="wpm-card-title"><?php _e('Expired Transients', 'wp-performance-monitor'); ?></h3>
            <p class="wpm-card-desc"><?php _e('Temporary data that has expired and is no longer needed', 'wp-performance-monitor'); ?></p>
            <div class="wpm-card-count" id="count-transients"><?php echo number_format($transients); ?> <span><?php _e('transients found', 'wp-performance-monitor'); ?></span></div>
            <button class="wpm-cleanup-btn wpm-btn-transients" data-type="transients"><?php _e('Clean Transients', 'wp-performance-monitor'); ?></button>
        </div>

        <!-- Card 5: Orphaned Meta -->
        <div class="wpm-cleanup-card">
            <div class="wpm-card-icon">🔗</div>
            <h3 class="wpm-card-title"><?php _e('Orphaned Meta Data', 'wp-performance-monitor'); ?></h3>
            <p class="wpm-card-desc"><?php _e('Meta data that belongs to deleted posts, comments, or users', 'wp-performance-monitor'); ?></p>
            <div class="wpm-card-count" id="count-orphaned"><?php echo number_format($orphaned_meta); ?> <span><?php _e('orphaned records', 'wp-performance-monitor'); ?></span></div>
            <button class="wpm-cleanup-btn wpm-btn-orphaned" data-type="orphaned"><?php _e('Clean Orphaned Meta', 'wp-performance-monitor'); ?></button>
        </div>

        <!-- Card 6: Optimize Tables -->
        <div class="wpm-cleanup-card">
            <div class="wpm-card-icon">🔧</div>
            <h3 class="wpm-card-title"><?php _e('Optimize Tables', 'wp-performance-monitor'); ?></h3>
            <p class="wpm-card-desc"><?php _e('Optimize database tables for better performance', 'wp-performance-monitor'); ?></p>
            <div class="wpm-card-count" id="count-tables"><?php echo $table_count; ?> <span><?php _e('tables', 'wp-performance-monitor'); ?></span></div>
            <button class="wpm-cleanup-btn wpm-btn-optimize" data-type="optimize"><?php _e('Optimize All Tables', 'wp-performance-monitor'); ?></button>
        </div>
    </div>

    <div class="wpm-cleanup-footer">
        <button class="wpm-clean-all-btn" data-type="all"><?php _e('Clean All', 'wp-performance-monitor'); ?></button>
    </div>
</div>

<style>
.wpm-wrap {
    margin: 20px 20px 0 0;
}
.wpm-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    color: white;
}
.wpm-header h1 {
    margin: 0 0 10px 0;
    color: white;
}
.wpm-header p {
    margin: 0;
    opacity: 0.95;
}
.wpm-stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}
.wpm-stat-card {
    padding: 20px;
    border-radius: 12px;
    text-align: center;
}
.wpm-stat-green {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}
.wpm-stat-green .wpm-stat-value {
    color: #22c55e;
}
.wpm-stat-blue {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
}
.wpm-stat-blue .wpm-stat-value {
    color: #3b82f6;
}
.wpm-stat-yellow {
    background: #fef3c7;
    border: 1px solid #fde68a;
}
.wpm-stat-yellow .wpm-stat-value {
    color: #d97706;
}
.wpm-stat-value {
    font-size: 32px;
    font-weight: bold;
}
.wpm-stat-label {
    font-size: 14px;
    margin-top: 8px;
}
.wpm-cleanup-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}
.wpm-cleanup-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px;
    transition: all 0.2s ease;
}
.wpm-cleanup-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.wpm-card-icon {
    font-size: 32px;
    margin-bottom: 12px;
}
.wpm-card-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #1f2937;
}
.wpm-card-desc {
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 12px 0;
    line-height: 1.4;
}
.wpm-card-count {
    font-size: 24px;
    font-weight: bold;
    color: #667eea;
    margin: 12px 0;
}
.wpm-card-count span {
    font-size: 13px;
    font-weight: normal;
    color: #6b7280;
}
.wpm-cleanup-btn {
    background: #f3f4f6;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}
.wpm-cleanup-btn:hover {
    background: #667eea;
    color: white;
}
.wpm-cleanup-footer {
    text-align: right;
}
.wpm-clean-all-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: opacity 0.2s;
}
.wpm-clean-all-btn:hover {
    opacity: 0.9;
}
@media (max-width: 768px) {
    .wpm-stats-row {
        grid-template-columns: 1fr;
    }
    .wpm-cleanup-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var nonce = '<?php echo wp_create_nonce('wpm_ajax_nonce'); ?>';
    
    function updateCount(elementId, newValue, suffix) {
        $('#' + elementId).html(newValue + ' <span>' + suffix + '</span>');
    }
    
    function showResult(message, type, elementId, newValue, suffix) {
        alert(message);
        if (elementId && newValue !== undefined) {
            updateCount(elementId, newValue, suffix);
        } else {
            location.reload();
        }
    }
    
    // Clean Revisions
    $('.wpm-btn-revisions').click(function() {
        if (!confirm('<?php _e('Delete all post revisions?', 'wp-performance-monitor'); ?>')) return;
        var $btn = $(this);
        $btn.text('Cleaning...').prop('disabled', true);
        $.post(ajaxurl, {action: 'wpm_clean_revisions', nonce: nonce}, function(r) {
            if (r.success) {
                updateCount('count-revisions', '0', '<?php _e('revisions found', 'wp-performance-monitor'); ?>');
                alert(r.data.message);
                $btn.text('<?php _e('Clean Revisions', 'wp-performance-monitor'); ?>').prop('disabled', false);
            }
        }).fail(function() { alert('Error'); $btn.text('<?php _e('Clean Revisions', 'wp-performance-monitor'); ?>').prop('disabled', false); });
    });
    
    // Clean Drafts
    $('.wpm-btn-drafts').click(function() {
        if (!confirm('<?php _e('Delete all auto drafts?', 'wp-performance-monitor'); ?>')) return;
        var $btn = $(this);
        $btn.text('Cleaning...').prop('disabled', true);
        $.post(ajaxurl, {action: 'wpm_clean_drafts', nonce: nonce}, function(r) {
            if (r.success) {
                updateCount('count-drafts', '0', '<?php _e('drafts found', 'wp-performance-monitor'); ?>');
                alert(r.data.message);
                $btn.text('<?php _e('Clean Drafts', 'wp-performance-monitor'); ?>').prop('disabled', false);
            }
        }).fail(function() { alert('Error'); $btn.text('<?php _e('Clean Drafts', 'wp-performance-monitor'); ?>').prop('disabled', false); });
    });
    
    // Clean Trash
    $('.wpm-btn-trash').click(function() {
        if (!confirm('<?php _e('Empty trash?', 'wp-performance-monitor'); ?>')) return;
        var $btn = $(this);
        $btn.text('Cleaning...').prop('disabled', true);
        $.post(ajaxurl, {action: 'wpm_clean_trash', nonce: nonce}, function(r) {
            if (r.success) {
                updateCount('count-trash', '0', '<?php _e('items in trash', 'wp-performance-monitor'); ?>');
                alert(r.data.message);
                $btn.text('<?php _e('Clean Trash', 'wp-performance-monitor'); ?>').prop('disabled', false);
            }
        }).fail(function() { alert('Error'); $btn.text('<?php _e('Clean Trash', 'wp-performance-monitor'); ?>').prop('disabled', false); });
    });
    
    // Clean Transients
    $('.wpm-btn-transients').click(function() {
        if (!confirm('<?php _e('Delete all transients?', 'wp-performance-monitor'); ?>')) return;
        var $btn = $(this);
        $btn.text('Cleaning...').prop('disabled', true);
        $.post(ajaxurl, {action: 'wpm_clean_transients', nonce: nonce}, function(r) {
            if (r.success) {
                updateCount('count-transients', '0', '<?php _e('transients found', 'wp-performance-monitor'); ?>');
                alert(r.data.message);
                $btn.text('<?php _e('Clean Transients', 'wp-performance-monitor'); ?>').prop('disabled', false);
            }
        }).fail(function() { alert('Error'); $btn.text('<?php _e('Clean Transients', 'wp-performance-monitor'); ?>').prop('disabled', false); });
    });
    
    // Clean Orphaned Meta
    $('.wpm-btn-orphaned').click(function() {
        if (!confirm('<?php _e('Delete orphaned meta data?', 'wp-performance-monitor'); ?>')) return;
        var $btn = $(this);
        $btn.text('Cleaning...').prop('disabled', true);
        $.post(ajaxurl, {action: 'wpm_clean_orphaned_meta', nonce: nonce}, function(r) {
            if (r.success) {
                updateCount('count-orphaned', '0', '<?php _e('orphaned records', 'wp-performance-monitor'); ?>');
                alert(r.data.message);
                $btn.text('<?php _e('Clean Orphaned Meta', 'wp-performance-monitor'); ?>').prop('disabled', false);
            }
        }).fail(function() { alert('Error'); $btn.text('<?php _e('Clean Orphaned Meta', 'wp-performance-monitor'); ?>').prop('disabled', false); });
    });
    
    // Optimize Tables
    $('.wpm-btn-optimize').click(function() {
        if (!confirm('<?php _e('Optimize database tables?', 'wp-performance-monitor'); ?>')) return;
        var $btn = $(this);
        $btn.text('Optimizing...').prop('disabled', true);
        $.post(ajaxurl, {action: 'wpm_optimize_tables', nonce: nonce}, function(r) {
            if (r.success) {
                alert(r.data.message);
                $btn.text('<?php _e('Optimize All Tables', 'wp-performance-monitor'); ?>').prop('disabled', false);
            }
        }).fail(function() { alert('Error'); $btn.text('<?php _e('Optimize All Tables', 'wp-performance-monitor'); ?>').prop('disabled', false); });
    });
    
    // Clean All
    $('.wpm-clean-all-btn').click(function() {
        if (!confirm('<?php _e('Clean ALL database trash? This cannot be undone.', 'wp-performance-monitor'); ?>')) return;
        var $btn = $(this);
        $btn.text('Cleaning all...').prop('disabled', true);
        $.post(ajaxurl, {action: 'wpm_clean_all', nonce: nonce}, function(r) {
            if (r.success) {
                updateCount('count-revisions', '0', '<?php _e('revisions found', 'wp-performance-monitor'); ?>');
                updateCount('count-drafts', '0', '<?php _e('drafts found', 'wp-performance-monitor'); ?>');
                updateCount('count-trash', '0', '<?php _e('items in trash', 'wp-performance-monitor'); ?>');
                updateCount('count-transients', '0', '<?php _e('transients found', 'wp-performance-monitor'); ?>');
                updateCount('count-orphaned', '0', '<?php _e('orphaned records', 'wp-performance-monitor'); ?>');
                alert(r.data.message);
                $btn.text('<?php _e('Clean All', 'wp-performance-monitor'); ?>').prop('disabled', false);
            }
        }).fail(function() { alert('Error'); $btn.text('<?php _e('Clean All', 'wp-performance-monitor'); ?>').prop('disabled', false); });
    });
});
</script>