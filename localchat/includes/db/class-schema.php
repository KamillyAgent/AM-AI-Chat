<?php
/**
 * Database schema definitions and migrations.
 *
 * Defines all custom table structures for LocalChat using dbDelta.
 * Tables are versioned and migrated via the localchat_db_version option.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes/db
 */

namespace LocalChat\DB;

if (!defined('ABSPATH')) {
    exit;
}

class Schema {
    
    /**
     * Current database schema version.
     * Increment this when making schema changes to trigger migrations.
     */
    const VERSION = '1.0.0';
    
    /**
     * Create all custom tables.
     *
     * @since    1.0.0
     */
    public static function create_tables() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        self::create_visitors_table();
        self::create_conversations_table();
        self::create_messages_table();
        self::create_departments_table();
        self::create_agents_table();
        self::create_triggers_table();
        self::create_kb_entries_table();
        self::create_flows_table();
        self::create_flow_runs_table();
        
        // Update DB version
        update_option('localchat_db_version', self::VERSION);
    }
    
    /**
     * Create visitors table.
     */
    private static function create_visitors_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'localchat_visitors';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(64) NOT NULL,
            first_seen datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            last_seen datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            ip_hash varchar(64) NOT NULL,
            country varchar(2) DEFAULT '',
            current_url varchar(2048) DEFAULT '',
            device_type varchar(20) DEFAULT 'desktop',
            browser varchar(100) DEFAULT '',
            referrer varchar(2048) DEFAULT '',
            wp_user_id bigint(20) UNSIGNED DEFAULT NULL,
            custom_attributes longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY uuid (uuid),
            KEY last_seen (last_seen),
            KEY wp_user_id (wp_user_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create conversations table.
     */
    private static function create_conversations_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'localchat_conversations';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            visitor_id bigint(20) UNSIGNED NOT NULL,
            department_id bigint(20) UNSIGNED DEFAULT NULL,
            assigned_agent_id bigint(20) UNSIGNED DEFAULT NULL,
            status varchar(20) DEFAULT 'open' NOT NULL,
            channel varchar(20) DEFAULT 'widget' NOT NULL,
            started_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            closed_at datetime DEFAULT NULL,
            source_url varchar(2048) DEFAULT '',
            rating tinyint(1) DEFAULT NULL,
            tags longtext DEFAULT NULL,
            watchers longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY visitor_id (visitor_id),
            KEY status (status),
            KEY assigned_agent_id (assigned_agent_id),
            KEY started_at (started_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create messages table.
     */
    private static function create_messages_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'localchat_messages';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) UNSIGNED NOT NULL,
            sender_type varchar(20) DEFAULT 'visitor' NOT NULL,
            sender_id bigint(20) UNSIGNED DEFAULT NULL,
            body longtext NOT NULL,
            attachments longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            read_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY conversation_id (conversation_id),
            KEY created_at (created_at),
            KEY sender_type (sender_type)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create departments table.
     */
    private static function create_departments_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'localchat_departments';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            color varchar(7) DEFAULT '#0084ff' NOT NULL,
            default_agent_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create agents table (maps to WP users with localchat_agent role).
     */
    private static function create_agents_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'localchat_agents';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            status varchar(20) DEFAULT 'offline' NOT NULL,
            last_active datetime DEFAULT NULL,
            departments longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            UNIQUE KEY unique_user (user_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create triggers table.
     */
    private static function create_triggers_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'localchat_triggers';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            type varchar(50) NOT NULL,
            conditions longtext NOT NULL,
            action varchar(50) NOT NULL,
            message longtext DEFAULT NULL,
            target_pages longtext DEFAULT NULL,
            frequency_cap int(11) DEFAULT 0,
            enabled tinyint(1) DEFAULT 1 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY enabled (enabled),
            KEY type (type)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create knowledge base entries table.
     */
    private static function create_kb_entries_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'localchat_kb_entries';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            question varchar(500) NOT NULL,
            answer longtext NOT NULL,
            tags longtext DEFAULT NULL,
            source varchar(50) DEFAULT 'manual' NOT NULL,
            post_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY source (source),
            KEY post_id (post_id),
            FULLTEXT KEY ft_question_answer (question, answer)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create chatbot flows table.
     */
    private static function create_flows_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'localchat_flows';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            definition longtext NOT NULL,
            trigger_event varchar(50) DEFAULT 'widget_open' NOT NULL,
            is_active tinyint(1) DEFAULT 1 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY is_active (is_active),
            KEY trigger_event (trigger_event)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create flow runs table.
     */
    private static function create_flow_runs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'localchat_flow_runs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            flow_id bigint(20) UNSIGNED NOT NULL,
            conversation_id bigint(20) UNSIGNED NOT NULL,
            current_node_id varchar(64) DEFAULT NULL,
            variables longtext DEFAULT NULL,
            started_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            completed_at datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'running' NOT NULL,
            PRIMARY KEY  (id),
            KEY flow_id (flow_id),
            KEY conversation_id (conversation_id),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Run migrations if schema version differs.
     *
     * @since    1.0.0
     */
    public static function maybe_migrate() {
        $installed_version = get_option('localchat_db_version', '0.0.0');
        
        if (version_compare($installed_version, self::VERSION, '<')) {
            self::create_tables();
            
            // Run version-specific migrations here
            // Example:
            // if (version_compare($installed_version, '0.9.0', '<')) {
            //     self::migrate_to_1_0_0();
            // }
            
            update_option('localchat_db_version', self::VERSION);
        }
    }
}
