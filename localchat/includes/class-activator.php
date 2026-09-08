<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes
 */

namespace LocalChat;

if (!defined('ABSPATH')) {
    exit;
}

class Activator {
    
    /**
     * Activate the plugin.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create custom database tables
        self::create_tables();
        
        // Create custom roles and capabilities
        self::create_roles();
        
        // Set default options
        self::set_default_options();
        
        // Schedule cron jobs
        self::schedule_cron_jobs();
        
        // Flush rewrite rules for REST API
        flush_rewrite_rules();
    }
    
    /**
     * Create custom database tables using dbDelta.
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        require_once LOCALCHAT_PLUGIN_DIR . 'includes/db/class-schema.php';
        Schema::create_tables();
    }
    
    /**
     * Create custom roles for LocalChat.
     *
     * @since    1.0.0
     */
    private static function create_roles() {
        require_once LOCALCHAT_PLUGIN_DIR . 'includes/security/class-capabilities.php';
        Capabilities::create_roles();
    }
    
    /**
     * Set default plugin options.
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        $default_options = [
            'version' => LOCALCHAT_VERSION,
            'db_version' => '1.0.0',
            
            // Widget settings
            'widget_enabled' => true,
            'widget_position' => 'bottom-right',
            'widget_color' => '#0084ff',
            'widget_greeting' => __('Hi there! How can we help you today?', 'localchat'),
            'widget_name' => __('Support Team', 'localchat'),
            'widget_avatar' => '',
            'launcher_icon' => '',
            'dark_mode' => 'auto', // auto, light, dark
            
            // Pre-chat survey
            'prechat_enabled' => false,
            'prechat_fields' => [
                'name' => ['enabled' => true, 'required' => true],
                'email' => ['enabled' => true, 'required' => true],
                'phone' => ['enabled' => false, 'required' => false],
            ],
            
            // Office hours
            'office_hours_enabled' => false,
            'office_hours' => [
                'monday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                'tuesday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                'wednesday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                'thursday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                'friday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                'saturday' => ['open' => '', 'close' => '', 'enabled' => false],
                'sunday' => ['open' => '', 'close' => '', 'enabled' => false],
            ],
            'timezone' => wp_timezone_string(),
            'offline_message' => __('We\'re currently away. Leave a message and we\'ll get back to you.', 'localchat'),
            
            // Visibility rules
            'visibility_mode' => 'all', // all, include, exclude
            'visibility_pages' => [],
            'hide_for_admins' => true,
            'hide_on_mobile' => false,
            
            // Data retention
            'data_retention_days' => 90,
            'keep_data_on_uninstall' => false,
            
            // AI settings (disabled by default)
            'ai_enabled' => false,
            'ai_provider' => '',
            'ai_api_key' => '',
            'ai_model' => '',
            'ai_custom_endpoint' => '',
            'ai_system_prompt' => __('You are a helpful customer support assistant. Be polite, professional, and concise.', 'localchat'),
            'ai_confidence_threshold' => 0.7,
            
            // WooCommerce integration
            'woocommerce_enabled' => false,
            
            // Real-time mode
            'realtime_mode' => 'heartbeat', // heartbeat, sse
            'heartbeat_interval' => 5,
        ];
        
        if (get_option('localchat_settings') === false) {
            add_option('localchat_settings', $default_options);
        }
    }
    
    /**
     * Schedule cron jobs for maintenance tasks.
     *
     * @since    1.0.0
     */
    private static function schedule_cron_jobs() {
        // Daily cleanup of old visitor data
        if (!wp_next_scheduled('localchat_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'localchat_daily_cleanup');
        }
    }
}
