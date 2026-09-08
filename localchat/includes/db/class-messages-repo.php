<?php
/**
 * Repository for messages database operations.
 *
 * Handles all CRUD operations for the messages table.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes/db
 */

namespace LocalChat\DB;

if (!defined('ABSPATH')) {
    exit;
}

class Messages_Repo {
    
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
        self::$table = $wpdb->prefix . 'localchat_messages';
    }
    
    /**
     * Create a new message.
     *
     * @since    1.0.0
     * @param    array $data
     * @return   int Message ID
     */
    public static function create($data) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $defaults = [
            'conversation_id' => 0,
            'sender_type' => 'visitor',
            'sender_id' => null,
            'body' => '',
            'attachments' => json_encode([]),
            'read_at' => null,
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Ensure attachments is JSON
        if (is_array($data['attachments'])) {
            $data['attachments'] = json_encode($data['attachments']);
        }
        
        // Sanitize body but allow safe HTML
        $data['body'] = wp_kses_post($data['body']);
        
        $wpdb->insert(self::$table, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get messages for a conversation.
     *
     * @since    1.0.0
     * @param    int    $conversation_id
     * @param    int    $since_message_id (optional, get messages after this ID)
     * @param    int    $limit
     * @return   array
     */
    public static function get_by_conversation($conversation_id, $since_message_id = 0, $limit = 100) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $sql = $wpdb->prepare(
            "SELECT * FROM " . self::$table . " 
             WHERE conversation_id = %d AND id > %d 
             ORDER BY created_at ASC 
             LIMIT %d",
            $conversation_id,
            $since_message_id,
            $limit
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get a single message by ID.
     *
     * @since    1.0.0
     * @param    int $message_id
     * @return   object|null
     */
    public static function get_by_id($message_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $sql = $wpdb->prepare(
            "SELECT * FROM " . self::$table . " WHERE id = %d",
            $message_id
        );
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Mark message as read.
     *
     * @since    1.0.0
     * @param    int $message_id
     * @return   bool|int
     */
    public static function mark_as_read($message_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        return $wpdb->update(
            self::$table,
            ['read_at' => current_time('mysql')],
            ['id' => $message_id]
        );
    }
    
    /**
     * Mark all messages in a conversation as read.
     *
     * @since    1.0.0
     * @param    int $conversation_id
     * @return   int Number of messages updated
     */
    public static function mark_all_as_read($conversation_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        return $wpdb->query(
            $wpdb->prepare(
                "UPDATE " . self::$table . " 
                 SET read_at = %s 
                 WHERE conversation_id = %d AND read_at IS NULL",
                current_time('mysql'),
                $conversation_id
            )
        );
    }
    
    /**
     * Get unread message count for a conversation.
     *
     * @since    1.0.0
     * @param    int $conversation_id
     * @return   int
     */
    public static function get_unread_count($conversation_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::$table . " 
             WHERE conversation_id = %d AND read_at IS NULL",
            $conversation_id
        );
        
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Delete messages by conversation ID.
     *
     * @since    1.0.0
     * @param    int $conversation_id
     * @return   int Number of messages deleted
     */
    public static function delete_by_conversation($conversation_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        return $wpdb->delete(
            self::$table,
            ['conversation_id' => $conversation_id]
        );
    }
    
    /**
     * Delete a single message.
     *
     * @since    1.0.0
     * @param    int $message_id
     * @return   bool|int
     */
    public static function delete($message_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        return $wpdb->delete(
            self::$table,
            ['id' => $message_id]
        );
    }
    
    /**
     * Get latest message from a conversation.
     *
     * @since    1.0.0
     * @param    int $conversation_id
     * @return   object|null
     */
    public static function get_latest($conversation_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $sql = $wpdb->prepare(
            "SELECT * FROM " . self::$table . " 
             WHERE conversation_id = %d 
             ORDER BY created_at DESC 
             LIMIT 1",
            $conversation_id
        );
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Search messages by keyword.
     *
     * @since    1.0.0
     * @param    string $keyword
     * @param    int    $limit
     * @return   array
     */
    public static function search($keyword, $limit = 50) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $keyword = '%' . $wpdb->esc_like($keyword) . '%';
        
        $sql = $wpdb->prepare(
            "SELECT m.*, c.visitor_id, c.status 
             FROM " . self::$table . " m
             JOIN {$wpdb->prefix}localchat_conversations c ON m.conversation_id = c.id
             WHERE m.body LIKE %s 
             ORDER BY m.created_at DESC 
             LIMIT %d",
            $keyword,
            $limit
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get message count for a conversation.
     *
     * @since    1.0.0
     * @param    int $conversation_id
     * @return   int
     */
    public static function get_count($conversation_id) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::$table . " 
             WHERE conversation_id = %d",
            $conversation_id
        );
        
        return (int) $wpdb->get_var($sql);
    }
}

// Initialize on load
Messages_Repo::init();
