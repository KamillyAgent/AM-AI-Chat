<?php
/**
 * Uninstall LocalChat.
 *
 * Removes all plugin data from the database when the plugin is deleted.
 * Respects the "keep_data_on_uninstall" setting.
 *
 * @since      1.0.0
 * @package    LocalChat
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get settings to check if we should keep data
$settings = get_option('localchat_settings', []);
$keep_data = isset($settings['keep_data_on_uninstall']) && $settings['keep_data_on_uninstall'];

if (!$keep_data) {
    global $wpdb;
    
    // Drop custom tables
    $tables = [
        'localchat_visitors',
        'localchat_conversations',
        'localchat_messages',
        'localchat_departments',
        'localchat_agents',
        'localchat_triggers',
        'localchat_kb_entries',
        'localchat_flows',
        'localchat_flow_runs',
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
    }
    
    // Delete all plugin options
    delete_option('localchat_settings');
    delete_option('localchat_db_version');
    delete_option('localchat_version');
    
    // Delete user meta created by the plugin
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_localchat_%'");
    
    // Clear any scheduled hooks
    wp_clear_scheduled_hook('localchat_daily_cleanup');
    
    // Remove custom roles
    remove_role('localchat_manager');
    remove_role('localchat_agent');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Note: We intentionally don't delete uploaded files in localchat_uploads directory
// Site administrators should manually review and delete those if needed
