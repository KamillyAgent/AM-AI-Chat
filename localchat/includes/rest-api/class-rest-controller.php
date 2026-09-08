<?php
/**
 * Base REST Controller for LocalChat API.
 *
 * Provides common functionality for all REST controllers.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes/rest-api
 */

namespace LocalChat\REST;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

abstract class REST_Controller extends WP_REST_Controller {
    
    /**
     * Namespace for REST routes.
     *
     * @var string
     */
    protected $namespace = 'localchat/v1';
    
    /**
     * Check if current user can manage LocalChat settings.
     *
     * @since    1.0.0
     * @return   bool
     */
    public function check_manage_settings_permission() {
        return \LocalChat\Security\Capabilities::can_manage_settings();
    }
    
    /**
     * Check if current user can reply to chats.
     *
     * @since    1.0.0
     * @return   bool
     */
    public function check_reply_chats_permission() {
        return \LocalChat\Security\Capabilities::can_reply_chats();
    }
    
    /**
     * Check if current user can view all conversations.
     *
     * @since    1.0.0
     * @return   bool
     */
    public function check_view_all_permission() {
        return \LocalChat\Security\Capabilities::can_view_all_conversations();
    }
    
    /**
     * Sanitize message body.
     *
     * @since    1.0.0
     * @param    string $body
     * @return   string
     */
    public function sanitize_message_body($body) {
        // Allow safe HTML tags for rich text messages
        $allowed_html = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'em' => [],
            'u' => [],
            'a' => ['href' => true, 'title' => true, 'target' => true],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'blockquote' => [],
            'code' => [],
            'pre' => [],
        ];
        
        return wp_kses_post($body);
    }
    
    /**
     * Get current visitor ID from request or cookie.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request
     * @return   string
     */
    public function get_visitor_id_from_request($request) {
        $visitor_id = $request->get_param('visitor_id');
        
        if (empty($visitor_id)) {
            $visitor_id = isset($_COOKIE['localchat_visitor_id']) 
                ? sanitize_text_field($_COOKIE['localchat_visitor_id']) 
                : '';
        }
        
        return $visitor_id;
    }
    
    /**
     * Format conversation response.
     *
     * @since    1.0.0
     * @param    object $conversation
     * @return   array
     */
    public function format_conversation_response($conversation) {
        if (!$conversation) {
            return [];
        }
        
        return [
            'id' => (int) $conversation->id,
            'visitor_id' => (int) $conversation->visitor_id,
            'department_id' => $conversation->department_id ? (int) $conversation->department_id : null,
            'assigned_agent_id' => $conversation->assigned_agent_id ? (int) $conversation->assigned_agent_id : null,
            'status' => $conversation->status,
            'channel' => $conversation->channel,
            'started_at' => mysql_to_rfc3339($conversation->started_at),
            'closed_at' => $conversation->closed_at ? mysql_to_rfc3339($conversation->closed_at) : null,
            'source_url' => $conversation->source_url,
            'rating' => $conversation->rating ? (int) $conversation->rating : null,
            'tags' => json_decode($conversation->tags, true) ?: [],
            'watchers' => json_decode($conversation->watchers, true) ?: [],
        ];
    }
    
    /**
     * Format message response.
     *
     * @since    1.0.0
     * @param    object $message
     * @return   array
     */
    public function format_message_response($message) {
        if (!$message) {
            return [];
        }
        
        return [
            'id' => (int) $message->id,
            'conversation_id' => (int) $message->conversation_id,
            'sender_type' => $message->sender_type,
            'sender_id' => $message->sender_id ? (int) $message->sender_id : null,
            'body' => $message->body,
            'attachments' => json_decode($message->attachments, true) ?: [],
            'created_at' => mysql_to_rfc3339($message->created_at),
            'read_at' => $message->read_at ? mysql_to_rfc3339($message->read_at) : null,
        ];
    }
    
    /**
     * Format visitor response.
     *
     * @since    1.0.0
     * @param    object $visitor
     * @return   array
     */
    public function format_visitor_response($visitor) {
        if (!$visitor) {
            return [];
        }
        
        return [
            'id' => (int) $visitor->id,
            'uuid' => $visitor->uuid,
            'first_seen' => mysql_to_rfc3339($visitor->first_seen),
            'last_seen' => mysql_to_rfc3339($visitor->last_seen),
            'country' => $visitor->country,
            'current_url' => $visitor->current_url,
            'device_type' => $visitor->device_type,
            'browser' => $visitor->browser,
            'referrer' => $visitor->referrer,
            'wp_user_id' => $visitor->wp_user_id ? (int) $visitor->wp_user_id : null,
            'custom_attributes' => json_decode($visitor->custom_attributes, true) ?: [],
        ];
    }
    
    /**
     * Add error logging with key redaction.
     *
     * @since    1.0.0
     * @param    string $message
     * @param    mixed  $data
     * @param    string $level
     */
    protected function log_error($message, $data = null, $level = 'error') {
        // Redact any potential API keys from logs
        if (is_array($data)) {
            $redacted_keys = ['api_key', 'apikey', 'key', 'secret', 'token', 'password'];
            foreach ($redacted_keys as $key) {
                if (isset($data[$key])) {
                    $data[$key] = '[REDACTED]';
                }
            }
        }
        
        error_log(sprintf(
            '[LocalChat %s] %s%s',
            strtoupper($level),
            $message,
            $data ? ': ' . wp_json_encode($data) : ''
        ));
    }
}
