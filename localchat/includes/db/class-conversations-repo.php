<?php
/**
 * Repository for conversations database operations.
 *
 * Handles all CRUD operations for the conversations table.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes/db
 */

namespace LocalChat\DB;

if (!defined('ABSPATH')) {
    exit;
}

class Conversations_Repo {
    
    /**
     * Table name.
     *
     * @var string
     */
    private static $table;
    
    /**
     * Initialize table name.
     */
    public static function init() {
        global $wpdb;
        self::$table = $wpdb->prefix . 'localchat_conversations';
    }
    
    /**
     * Create a new conversation.
     *
     * @since    1.0.0
     * @param    array $data
     * @return   int Conversation ID
     */
    public static function create($data) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $defaults = [
            'visitor_id' => 0,
            'department_id' => null,
            'assigned_agent_id' => null,
            'status' => 'open',
            'channel' => 'widget',
            'source_url' => '',
            'rating' => null,
            'tags' => json_encode([]),
            'watchers' => json_encode([]),
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Ensure JSON fields are properly encoded
        if (is_array($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }
        
        if (is_array($data['watchers'])) {
            $data['watchers'] = json_encode($data['watchers']);
        }
        
        $wpdb->insert(self::$table, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get a conversation by ID.
     *
     * @since    1.0.0
     * @param    int $conversation_id
     * @return   object|null
     */
    public static function get_by_id($conversation_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $sql = $wpdb->prepare(
            "SELECT * FROM " . self::$table . " WHERE id = %d",
            $conversation_id
        );
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Get conversations with filters.
     *
     * @since    1.0.0
     * @param    array $args
     * @return   array
     */
    public static function get_conversations($args = []) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $defaults = [
            'status' => '',
            'assigned_agent_id' => '',
            'department_id' => '',
            'visitor_id' => '',
            'search' => '',
            'orderby' => 'started_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = ['1=1'];
        $params = [];
        
        if ($args['status']) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }
        
        if ($args['assigned_agent_id']) {
            $where[] = 'assigned_agent_id = %d';
            $params[] = $args['assigned_agent_id'];
        }
        
        if ($args['department_id']) {
            $where[] = 'department_id = %d';
            $params[] = $args['department_id'];
        }
        
        if ($args['visitor_id']) {
            $where[] = 'visitor_id = %d';
            $params[] = $args['visitor_id'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $sql = "SELECT * FROM " . self::$table . " 
                WHERE {$where_clause} 
                ORDER BY {$args['orderby']} {$args['order']} 
                LIMIT %d OFFSET %d";
        
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        $prepared_sql = call_user_func_array([$wpdb, 'prepare'], array_merge([$sql], $params));
        
        return $wpdb->get_results($prepared_sql);
    }
    
    /**
     * Get conversation count with filters.
     *
     * @since    1.0.0
     * @param    array $args
     * @return   int
     */
    public static function get_count($args = []) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $defaults = [
            'status' => '',
            'assigned_agent_id' => '',
            'department_id' => '',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = ['1=1'];
        $params = [];
        
        if ($args['status']) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }
        
        if ($args['assigned_agent_id']) {
            $where[] = 'assigned_agent_id = %d';
            $params[] = $args['assigned_agent_id'];
        }
        
        if ($args['department_id']) {
            $where[] = 'department_id = %d';
            $params[] = $args['department_id'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(*) FROM " . self::$table . " WHERE {$where_clause}";
        
        $prepared_sql = call_user_func_array([$wpdb, 'prepare'], array_merge([$sql], $params));
        
        return (int) $wpdb->get_var($prepared_sql);
    }
    
    /**
     * Update conversation status.
     *
     * @since    1.0.0
     * @param    int    $conversation_id
     * @param    string $status
     * @return   bool|int
     */
    public static function update_status($conversation_id, $status) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $data = ['status' => $status];
        
        if ($status === 'closed') {
            $data['closed_at'] = current_time('mysql');
        }
        
        return $wpdb->update(
            self::$table,
            $data,
            ['id' => $conversation_id]
        );
    }
    
    /**
     * Assign conversation to an agent.
     *
     * @since    1.0.0
     * @param    int    $conversation_id
     * @param    int    $agent_id
     * @return   bool|int
     */
    public static function assign_agent($conversation_id, $agent_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        return $wpdb->update(
            self::$table,
            ['assigned_agent_id' => $agent_id],
            ['id' => $conversation_id]
        );
    }
    
    /**
     * Add watcher to conversation.
     *
     * @since    1.0.0
     * @param    int $conversation_id
     * @param    int $user_id
     * @return   bool
     */
    public static function add_watcher($conversation_id, $user_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $conversation = self::get_by_id($conversation_id);
        
        if (!$conversation) {
            return false;
        }
        
        $watchers = json_decode($conversation->watchers, true) ?: [];
        
        if (!in_array($user_id, $watchers)) {
            $watchers[] = $user_id;
        }
        
        return $wpdb->update(
            self::$table,
            ['watchers' => json_encode($watchers)],
            ['id' => $conversation_id]
        );
    }
    
    /**
     * Remove watcher from conversation.
     *
     * @since    1.0.0
     * @param    int $conversation_id
     * @param    int $user_id
     * @return   bool|int
     */
    public static function remove_watcher($conversation_id, $user_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $conversation = self::get_by_id($conversation_id);
        
        if (!$conversation) {
            return false;
        }
        
        $watchers = json_decode($conversation->watchers, true) ?: [];
        $watchers = array_diff($watchers, [$user_id]);
        
        return $wpdb->update(
            self::$table,
            ['watchers' => json_encode(array_values($watchers))],
            ['id' => $conversation_id]
        );
    }
    
    /**
     * Add tags to conversation.
     *
     * @since    1.0.0
     * @param    int   $conversation_id
     * @param    array $tags
     * @return   bool|int
     */
    public static function add_tags($conversation_id, $tags) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $conversation = self::get_by_id($conversation_id);
        
        if (!$conversation) {
            return false;
        }
        
        $current_tags = json_decode($conversation->tags, true) ?: [];
        $new_tags = array_unique(array_merge($current_tags, $tags));
        
        return $wpdb->update(
            self::$table,
            ['tags' => json_encode($new_tags)],
            ['id' => $conversation_id]
        );
    }
    
    /**
     * Update conversation rating.
     *
     * @since    1.0.0
     * @param    int $conversation_id
     * @param    int $rating (1-5)
     * @return   bool|int
     */
    public static function update_rating($conversation_id, $rating) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $rating = max(1, min(5, intval($rating)));
        
        return $wpdb->update(
            self::$table,
            ['rating' => $rating],
            ['id' => $conversation_id]
        );
    }
    
    /**
     * Delete a conversation.
     *
     * @since    1.0.0
     * @param    int $conversation_id
     * @return   bool|int
     */
    public static function delete($conversation_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        // First delete associated messages
        Messages_Repo::delete_by_conversation($conversation_id);
        
        return $wpdb->delete(
            self::$table,
            ['id' => $conversation_id]
        );
    }
    
    /**
     * Get open conversations count for an agent.
     *
     * @since    1.0.0
     * @param    int $agent_id
     * @return   int
     */
    public static function get_open_count_for_agent($agent_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::$table . " 
             WHERE assigned_agent_id = %d AND status = 'open'",
            $agent_id
        );
        
        return (int) $wpdb->get_var($sql);
    }
}

// Initialize on load
Conversations_Repo::init();
