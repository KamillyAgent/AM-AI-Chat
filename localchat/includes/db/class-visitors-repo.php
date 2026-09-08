<?php
/**
 * Repository for visitor database operations.
 *
 * Handles all CRUD operations for the visitors table.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes/db
 */

namespace LocalChat\DB;

if (!defined('ABSPATH')) {
    exit;
}

class Visitors_Repo {
    
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
        self::$table = $wpdb->prefix . 'localchat_visitors';
    }
    
    /**
     * Get a visitor by UUID.
     *
     * @since    1.0.0
     * @param    string $uuid
     * @return   object|null
     */
    public static function get_by_uuid($uuid) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $sql = $wpdb->prepare(
            "SELECT * FROM " . self::$table . " WHERE uuid = %s",
            $uuid
        );
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Create a new visitor.
     *
     * @since    1.0.0
     * @param    array $data
     * @return   int Visitor ID
     */
    public static function create($data) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $defaults = [
            'uuid' => wp_generate_uuid4(),
            'ip_hash' => self::hash_ip(self::get_visitor_ip()),
            'country' => '',
            'current_url' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '',
            'device_type' => self::detect_device_type(),
            'browser' => self::detect_browser(),
            'referrer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '',
            'wp_user_id' => get_current_user_id() ?: null,
            'custom_attributes' => json_encode([]),
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Ensure custom_attributes is JSON
        if (is_array($data['custom_attributes'])) {
            $data['custom_attributes'] = json_encode($data['custom_attributes']);
        }
        
        $wpdb->insert(self::$table, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update visitor last seen and current URL.
     *
     * @since    1.0.0
     * @param    int    $visitor_id
     * @param    string $current_url
     * @return   bool
     */
    public static function update_last_seen($visitor_id, $current_url = '') {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $data = ['last_seen' => current_time('mysql')];
        
        if ($current_url) {
            $data['current_url'] = esc_url_raw($current_url);
        }
        
        return $wpdb->update(
            self::$table,
            $data,
            ['id' => $visitor_id]
        );
    }
    
    /**
     * Get active visitors (seen within last N minutes).
     *
     * @since    1.0.0
     * @param    int    $minutes
     * @param    int    $limit
     * @return   array
     */
    public static function get_active_visitors($minutes = 5, $limit = 50) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));
        
        $sql = $wpdb->prepare(
            "SELECT * FROM " . self::$table . " 
             WHERE last_seen >= %s 
             ORDER BY last_seen DESC 
             LIMIT %d",
            $cutoff,
            $limit
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get visitor count.
     *
     * @since    1.0.0
     * @return   int
     */
    public static function get_total_count() {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM " . self::$table);
    }
    
    /**
     * Delete old visitors (cleanup cron job).
     *
     * @since    1.0.0
     * @param    int    $days
     * @return   int Number of rows deleted
     */
    public static function delete_old_visitors($days = 90) {
        global $wpdb;
        
        if (!self::$table) {
            self::init();
        }
        
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . self::$table . " WHERE last_seen < %s",
                $cutoff
            )
        );
    }
    
    /**
     * Hash IP address for privacy.
     *
     * @since    1.0.0
     * @param    string $ip
     * @return   string
     */
    private static function hash_ip($ip) {
        return wp_hash($ip . wp_salt());
    }
    
    /**
     * Get visitor IP address.
     *
     * @since    1.0.0
     * @return   string
     */
    private static function get_visitor_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', sanitize_text_field($_SERVER[$key]));
                return trim($ip[0]);
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Detect device type from user agent.
     *
     * @since    1.0.0
     * @return   string
     */
    private static function detect_device_type() {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return 'desktop';
        }
        
        $ua = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
        
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobi))/i', $ua)) {
            return 'tablet';
        }
        
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $ua)) {
            return 'mobile';
        }
        
        return 'desktop';
    }
    
    /**
     * Detect browser from user agent.
     *
     * @since    1.0.0
     * @return   string
     */
    private static function detect_browser() {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return '';
        }
        
        $ua = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
        $browser = 'Unknown';
        
        if (preg_match('/MSIE|Trident/i', $ua)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Edge/i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome/i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $ua)) {
            $browser = 'Safari';
        } elseif (preg_match('/Firefox/i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Opera/i', $ua)) {
            $browser = 'Opera';
        }
        
        return $browser;
    }
}

// Initialize on load
Visitors_Repo::init();
