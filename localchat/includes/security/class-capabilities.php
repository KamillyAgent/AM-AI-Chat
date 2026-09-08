<?php
/**
 * Custom roles and capabilities for LocalChat.
 *
 * Defines custom user roles: localchat_manager, localchat_agent
 * and adds necessary capabilities.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes/security
 */

namespace LocalChat\Security;

if (!defined('ABSPATH')) {
    exit;
}

class Capabilities {
    
    /**
     * Custom capability name for managing LocalChat settings.
     */
    const CAP_MANAGE_SETTINGS = 'manage_localchat';
    
    /**
     * Custom capability name for replying to chats.
     */
    const CAP_REPLY_CHATS = 'reply_localchat_chats';
    
    /**
     * Custom capability name for viewing all conversations.
     */
    const CAP_VIEW_ALL_CONVERSATIONS = 'view_all_localchat_conversations';
    
    /**
     * Add custom capabilities to WordPress.
     *
     * @since    1.0.0
     */
    public static function add_capabilities() {
        // Add capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap(self::CAP_MANAGE_SETTINGS);
            $admin_role->add_cap(self::CAP_REPLY_CHATS);
            $admin_role->add_cap(self::CAP_VIEW_ALL_CONVERSATIONS);
        }
        
        // Ensure localchat_manager role has all capabilities
        $manager_role = get_role('localchat_manager');
        if ($manager_role) {
            $manager_role->add_cap(self::CAP_MANAGE_SETTINGS);
            $manager_role->add_cap(self::CAP_REPLY_CHATS);
            $manager_role->add_cap(self::CAP_VIEW_ALL_CONVERSATIONS);
            $manager_role->add_cap('read');
        }
        
        // Ensure localchat_agent role has chat capabilities
        $agent_role = get_role('localchat_agent');
        if ($agent_role) {
            $agent_role->add_cap(self::CAP_REPLY_CHATS);
            $agent_role->add_cap('read');
        }
    }
    
    /**
     * Create custom roles for LocalChat.
     *
     * @since    1.0.0
     */
    public static function create_roles() {
        // Remove existing roles to refresh capabilities
        remove_role('localchat_manager');
        remove_role('localchat_agent');
        
        // Create localchat_manager role
        // This role can manage all LocalChat settings but not full WP admin
        add_role(
            'localchat_manager',
            __('LocalChat Manager', 'localchat'),
            [
                'read'                          => true,
                self::CAP_MANAGE_SETTINGS       => true,
                self::CAP_REPLY_CHATS           => true,
                self::CAP_VIEW_ALL_CONVERSATIONS => true,
            ]
        );
        
        // Create localchat_agent role
        // This role can only reply to assigned conversations
        add_role(
            'localchat_agent',
            __('LocalChat Agent', 'localchat'),
            [
                'read'                          => true,
                self::CAP_REPLY_CHATS           => true,
            ]
        );
    }
    
    /**
     * Remove custom roles (on plugin uninstall).
     *
     * @since    1.0.0
     */
    public static function remove_roles() {
        remove_role('localchat_manager');
        remove_role('localchat_agent');
    }
    
    /**
     * Check if current user can manage LocalChat settings.
     *
     * @since    1.0.0
     * @return   bool
     */
    public static function can_manage_settings() {
        return current_user_can(self::CAP_MANAGE_SETTINGS) || current_user_can('manage_options');
    }
    
    /**
     * Check if current user can reply to chats.
     *
     * @since    1.0.0
     * @return   bool
     */
    public static function can_reply_chats() {
        return current_user_can(self::CAP_REPLY_CHATS) || current_user_can('manage_options');
    }
    
    /**
     * Check if current user can view all conversations.
     *
     * @since    1.0.0
     * @return   bool
     */
    public static function can_view_all_conversations() {
        return current_user_can(self::CAP_VIEW_ALL_CONVERSATIONS) || current_user_can('manage_options');
    }
}
