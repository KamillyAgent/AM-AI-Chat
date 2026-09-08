<?php
/**
 * Widget loader - enqueues frontend chat widget assets.
 *
 * Handles loading the chat widget JS/CSS on public pages.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes/public
 */

namespace LocalChat\Public;

if (!defined('ABSPATH')) {
    exit;
}

class WidgetLoader {
    
    /**
     * Initialize widget loader.
     *
     * @since    1.0.0
     * @param    Loader $loader
     */
    public function init($loader) {
        $loader->add_action('wp_enqueue_scripts', $this, 'enqueue_widget_assets');
        $loader->add_action('wp_footer', $this, 'render_widget_container');
        $loader->add_filter('script_loader_tag', $this, 'add_defer_to_widget_script'), 10, 3;
    }
    
    /**
     * Enqueue widget CSS and JS.
     *
     * @since    1.0.0
     */
    public function enqueue_widget_assets() {
        $settings = get_option('localchat_settings', []);
        
        // Check if widget is enabled
        if (isset($settings['widget_enabled']) && !$settings['widget_enabled']) {
            return;
        }
        
        // Check visibility rules
        if (!$this->should_show_widget($settings)) {
            return;
        }
        
        // Enqueue widget CSS
        wp_enqueue_style(
            'localchat-widget',
            LOCALCHAT_PLUGIN_URL . 'public/css/widget.css',
            [],
            LOCALCHAT_VERSION
        );
        
        // Enqueue widget JS
        wp_enqueue_script(
            'localchat-widget',
            LOCALCHAT_PLUGIN_URL . 'public/js/widget.js',
            [],
            LOCALCHAT_VERSION,
            true
        );
        
        // Localize script with settings
        wp_localize_script('localchat-widget', 'localchatWidget', [
            'apiRoot' => esc_url_raw(rest_url('localchat/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'settings' => $this->get_widget_settings($settings),
            'visitorId' => $this->get_or_create_visitor_id(),
            'i18n' => [
                'chatWithUs' => __('Chat with us', 'localchat'),
                'howCanWeHelp' => __('How can we help you today?', 'localchat'),
                'typeMessage' => __('Type a message...', 'localchat'),
                'send' => __('Send', 'localchat'),
                'poweredBy' => __('Powered by LocalChat', 'localchat'),
                'close' => __('Close', 'localchat'),
                'minimize' => __('Minimize', 'localchat'),
                'newMessage' => __('New message', 'localchat'),
                'connecting' => __('Connecting...', 'localchat'),
                'disconnected' => __('Disconnected. Reconnecting...', 'localchat'),
                'attachmentTooLarge' => __('File is too large', 'localchat'),
                'fileTypeError' => __('File type not allowed', 'localchat'),
                'uploadError' => __('Upload error', 'localchat'),
                'typing' => __('typing...', 'localchat'),
                'you' => __('You', 'localchat'),
                'today' => __('Today', 'localchat'),
                'yesterday' => __('Yesterday', 'localchat'),
                'startChat' => __('Start Chat', 'localchat'),
                'endChat' => __('End Chat', 'localchat'),
                'rateConversation' => __('Rate this conversation', 'localchat'),
                'submit' => __('Submit', 'localchat'),
                'cancel' => __('Cancel', 'localchat'),
            ],
        ]);
    }
    
    /**
     * Render widget container div.
     *
     * @since    1.0.0
     */
    public function render_widget_container() {
        $settings = get_option('localchat_settings', []);
        
        if (isset($settings['widget_enabled']) && !$settings['widget_enabled']) {
            return;
        }
        
        if (!$this->should_show_widget($settings)) {
            return;
        }
        
        ?>
        <div id="localchat-widget-container" class="localchat-widget-hidden" aria-live="polite" aria-label="<?php esc_attr_e('Live Chat Widget', 'localchat'); ?>">
            <!-- Launcher button -->
            <button 
                id="localchat-launcher" 
                class="localchat-launcher" 
                aria-expanded="false" 
                aria-controls="localchat-window"
                aria-label="<?php esc_attr_e('Open chat', 'localchat'); ?>"
            >
                <span class="localchat-launcher-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24">
                        <path fill="currentColor" d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                    </svg>
                </span>
                <span class="localchat-unread-badge" style="display: none;">0</span>
            </button>
            
            <!-- Chat window -->
            <div 
                id="localchat-window" 
                class="localchat-window" 
                role="dialog" 
                aria-modal="true"
                aria-labelledby="localchat-window-title"
            >
                <!-- Header -->
                <div class="localchat-header">
                    <div class="localchat-header-info">
                        <img class="localchat-avatar" src="" alt="" style="display: none;">
                        <div class="localchat-team-info">
                            <h3 id="localchat-window-title" class="localchat-team-name"></h3>
                            <span class="localchat-status-indicator"></span>
                        </div>
                    </div>
                    <button class="localchat-close-btn" aria-label="<?php esc_attr_e('Close chat', 'localchat'); ?>">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Messages area -->
                <div class="localchat-messages" role="log" aria-live="polite">
                    <!-- Messages will be inserted here -->
                </div>
                
                <!-- Typing indicator -->
                <div class="localchat-typing" style="display: none;">
                    <span class="localchat-typing-dot"></span>
                    <span class="localchat-typing-dot"></span>
                    <span class="localchat-typing-dot"></span>
                </div>
                
                <!-- Composer -->
                <div class="localchat-composer">
                    <div class="localchat-composer-actions">
                        <button class="localchat-attach-btn" aria-label="<?php esc_attr_e('Attach file', 'localchat'); ?>">
                            <svg viewBox="0 0 24 24" width="20" height="20">
                                <path fill="currentColor" d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 0 1 5 0v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5a2.5 2.5 0 0 0 5 0V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/>
                            </svg>
                        </button>
                        <input type="file" id="localchat-file-input" multiple style="display: none;">
                    </div>
                    <textarea 
                        id="localchat-message-input" 
                        class="localchat-message-input" 
                        placeholder="<?php esc_attr_e('Type a message...', 'localchat'); ?>"
                        rows="1"
                        aria-label="<?php esc_attr_e('Type your message', 'localchat'); ?>"
                    ></textarea>
                    <button 
                        id="localchat-send-btn" 
                        class="localchat-send-btn" 
                        aria-label="<?php esc_attr_e('Send message', 'localchat'); ?>"
                    >
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path fill="currentColor" d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Check if widget should be shown based on visibility rules.
     *
     * @since    1.0.0
     * @param    array $settings
     * @return   bool
     */
    private function should_show_widget($settings) {
        // Hide for admins if configured
        if (isset($settings['hide_for_admins']) && $settings['hide_for_admins'] && current_user_can('manage_options')) {
            return false;
        }
        
        // Hide on mobile if configured
        if (isset($settings['hide_on_mobile']) && $settings['hide_on_mobile'] && wp_is_mobile()) {
            return false;
        }
        
        // Check page targeting
        $visibility_mode = isset($settings['visibility_mode']) ? $settings['visibility_mode'] : 'all';
        
        if ($visibility_mode === 'all') {
            return true;
        }
        
        $current_id = get_the_ID();
        $visibility_pages = isset($settings['visibility_pages']) ? $settings['visibility_pages'] : [];
        
        if ($visibility_mode === 'include') {
            // Show only on included pages
            return in_array($current_id, $visibility_pages) || $this->url_matches_patterns($visibility_pages);
        } elseif ($visibility_mode === 'exclude') {
            // Show on all except excluded pages
            return !in_array($current_id, $visibility_pages) && !$this->url_matches_patterns($visibility_pages);
        }
        
        return true;
    }
    
    /**
     * Check if current URL matches any patterns.
     *
     * @since    1.0.0
     * @param    array $patterns
     * @return   bool
     */
    private function url_matches_patterns($patterns) {
        $current_url = home_url($_SERVER['REQUEST_URI']);
        
        foreach ($patterns as $pattern) {
            if (is_string($pattern)) {
                // Simple string match
                if (strpos($current_url, $pattern) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get filtered widget settings for frontend.
     *
     * @since    1.0.0
     * @param    array $settings
     * @return   array
     */
    private function get_widget_settings($settings) {
        return [
            'position' => isset($settings['widget_position']) ? $settings['widget_position'] : 'bottom-right',
            'color' => isset($settings['widget_color']) ? $settings['widget_color'] : '#0084ff',
            'greeting' => isset($settings['widget_greeting']) ? $settings['widget_greeting'] : '',
            'name' => isset($settings['widget_name']) ? $settings['widget_name'] : __('Support Team', 'localchat'),
            'avatar' => isset($settings['widget_avatar']) && $settings['widget_avatar'] ? $settings['widget_avatar'] : '',
            'darkMode' => isset($settings['dark_mode']) ? $settings['dark_mode'] : 'auto',
            'prechatEnabled' => isset($settings['prechat_enabled']) && $settings['prechat_enabled'],
            'prechatFields' => isset($settings['prechat_fields']) ? $settings['prechat_fields'] : [],
            'officeHoursEnabled' => isset($settings['office_hours_enabled']) && $settings['office_hours_enabled'],
            'offlineMessage' => isset($settings['offline_message']) ? $settings['offline_message'] : '',
        ];
    }
    
    /**
     * Get or create visitor ID from cookie/localStorage.
     *
     * @since    1.0.0
     * @return   string
     */
    private function get_or_create_visitor_id() {
        $visitor_id = '';
        
        // Try to get from cookie first
        if (isset($_COOKIE['localchat_visitor_id'])) {
            $visitor_id = sanitize_text_field($_COOKIE['localchat_visitor_id']);
        }
        
        // If no cookie, generate new UUID
        if (empty($visitor_id)) {
            $visitor_id = wp_generate_uuid4();
        }
        
        return $visitor_id;
    }
    
    /**
     * Add defer attribute to widget script for better performance.
     *
     * @since    1.0.0
     * @param    string $tag
     * @param    string $handle
     * @param    string $src
     * @return   string
     */
    public function add_defer_to_widget_script($tag, $handle, $src) {
        if ($handle === 'localchat-widget') {
            $tag = str_replace(' src', ' defer="defer" src', $tag);
        }
        return $tag;
    }
}
