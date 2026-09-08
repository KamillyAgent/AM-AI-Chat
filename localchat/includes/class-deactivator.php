<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes
 */

namespace LocalChat;

if (!defined('ABSPATH')) {
    exit;
}

class Deactivator {
    
    /**
     * Deactivate the plugin.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Unschedule cron jobs
        self::unschedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Unschedule cron jobs.
     *
     * @since    1.0.0
     */
    private static function unschedule_cron_jobs() {
        wp_clear_scheduled_hook('localchat_daily_cleanup');
    }
}
