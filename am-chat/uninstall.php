<?php
/**
 * Uninstall AM-Chat.
 *
 * Removes all plugin data from the database when the plugin is deleted.
 * Respects the "keep_data_on_uninstall" setting.
 *
 * @since      1.0.0
 * @package    AM-Chat
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get settings to check if we should keep data
$settings = get_option('amchat_settings', []);
$keep_data = isset($settings['keep_data_on_uninstall']) && $settings['keep_data_on_uninstall'];

if (!$keep_data) {
    global $wpdb;
    
    // Drop custom tables
    $tables = [
        'amchat_visitors',
        'amchat_conversations',
        'amchat_messages',
        'amchat_departments',
        'amchat_agents',
        'amchat_triggers',
        'amchat_kb_entries',
        'amchat_flows',
        'amchat_flow_runs',
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
    }
    
    // Delete all plugin options
    delete_option('amchat_settings');
    delete_option('amchat_db_version');
    delete_option('amchat_version');
    
    // Delete user meta created by the plugin
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_amchat_%'");
    
    // Clear any scheduled hooks
    wp_clear_scheduled_hook('amchat_daily_cleanup');
    
    // Remove custom roles
    remove_role('amchat_manager');
    remove_role('amchat_agent');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Note: We intentionally don't delete uploaded files in amchat_uploads directory
// Site administrators should manually review and delete those if needed
