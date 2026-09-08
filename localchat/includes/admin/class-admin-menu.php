<?php
/**
 * Admin menu and dashboard pages.
 *
 * Registers all admin menu items and loads admin views.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes/admin
 */

namespace LocalChat\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class AdminMenu {
    
    /**
     * The loader instance.
     *
     * @var Loader
     */
    private $loader;
    
    /**
     * Menu slug.
     *
     * @var string
     */
    private $menu_slug = 'localchat';
    
    /**
     * Initialize admin menu.
     *
     * @since    1.0.0
     * @param    Loader $loader
     */
    public function init($loader) {
        $this->loader = $loader;
        
        $this->loader->add_action('admin_menu', $this, 'add_admin_menu');
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
        $this->loader->add_action('admin_bar_menu', $this, 'add_admin_bar_items', 100);
    }
    
    /**
     * Register admin menu pages.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('LocalChat Dashboard', 'localchat'),
            __('LocalChat', 'localchat'),
            Security\Capabilities::CAP_REPLY_CHATS,
            $this->menu_slug,
            [$this, 'render_dashboard'],
            'dashicons-comments',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            $this->menu_slug,
            __('Dashboard', 'localchat'),
            __('Dashboard', 'localchat'),
            Security\Capabilities::CAP_REPLY_CHATS,
            $this->menu_slug,
            [$this, 'render_dashboard']
        );
        
        // Inbox
        add_submenu_page(
            $this->menu_slug,
            __('Inbox', 'localchat'),
            __('Inbox', 'localchat'),
            Security\Capabilities::CAP_REPLY_CHATS,
            'localchat-inbox',
            [$this, 'render_inbox']
        );
        
        // Visitors
        add_submenu_page(
            $this->menu_slug,
            __('Visitors', 'localchat'),
            __('Visitors', 'localchat'),
            Security\Capabilities::CAP_VIEW_ALL_CONVERSATIONS,
            'localchat-visitors',
            [$this, 'render_visitors']
        );
        
        // Chatbot (Flow Builder + KB + AI Settings)
        add_submenu_page(
            $this->menu_slug,
            __('Chatbot', 'localchat'),
            __('Chatbot', 'localchat'),
            Security\Capabilities::CAP_MANAGE_SETTINGS,
            'localchat-chatbot',
            [$this, 'render_chatbot']
        );
        
        // Triggers & Popups
        add_submenu_page(
            $this->menu_slug,
            __('Triggers & Popups', 'localchat'),
            __('Triggers & Popups', 'localchat'),
            Security\Capabilities::CAP_MANAGE_SETTINGS,
            'localchat-triggers',
            [$this, 'render_triggers']
        );
        
        // Departments & Agents
        add_submenu_page(
            $this->menu_slug,
            __('Departments & Agents', 'localchat'),
            __('Departments & Agents', 'localchat'),
            Security\Capabilities::CAP_MANAGE_SETTINGS,
            'localchat-departments',
            [$this, 'render_departments']
        );
        
        // Appearance
        add_submenu_page(
            $this->menu_slug,
            __('Appearance', 'localchat'),
            __('Appearance', 'localchat'),
            Security\Capabilities::CAP_MANAGE_SETTINGS,
            'localchat-appearance',
            [$this, 'render_appearance']
        );
        
        // Settings
        add_submenu_page(
            $this->menu_slug,
            __('Settings', 'localchat'),
            __('Settings', 'localchat'),
            Security\Capabilities::CAP_MANAGE_SETTINGS,
            'localchat-settings',
            [$this, 'render_settings']
        );
        
        // System Info / Logs
        add_submenu_page(
            $this->menu_slug,
            __('System Info / Logs', 'localchat'),
            __('System Info / Logs', 'localchat'),
            Security\Capabilities::CAP_MANAGE_SETTINGS,
            'localchat-system-info',
            [$this, 'render_system_info']
        );
    }
    
    /**
     * Enqueue admin assets.
     *
     * @since    1.0.0
     * @param    string $hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on LocalChat admin pages
        if (strpos($hook, 'localchat') === false) {
            return;
        }
        
        wp_enqueue_style(
            'localchat-admin',
            LOCALCHAT_PLUGIN_URL . 'admin/css/admin-app.css',
            [],
            LOCALCHAT_VERSION
        );
        
        wp_enqueue_script(
            'localchat-admin',
            LOCALCHAT_PLUGIN_URL . 'admin/js/admin-app.js',
            ['jquery', 'wp-api', 'wp-heartbeat-js'],
            LOCALCHAT_VERSION,
            true
        );
        
        wp_localize_script('localchat-admin', 'localchatAdmin', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'pluginUrl' => LOCALCHAT_PLUGIN_URL,
            'i18n' => [
                'loading' => __('Loading...', 'localchat'),
                'saving' => __('Saving...', 'localchat'),
                'saved' => __('Saved!', 'localchat'),
                'error' => __('Error', 'localchat'),
                'confirmDelete' => __('Are you sure you want to delete this item?', 'localchat'),
            ],
        ]);
    }
    
    /**
     * Add admin bar items.
     *
     * @since    1.0.0
     * @param    \WP_Admin_Bar $wp_admin_bar
     */
    public function add_admin_bar_items($wp_admin_bar) {
        if (!Security\Capabilities::can_reply_chats()) {
            return;
        }
        
        // Get open conversations count
        $count = DB\Conversations_Repo::get_count(['status' => 'open']);
        
        // Main LocalChat node
        $wp_admin_bar->add_node([
            'id' => 'localchat',
            'title' => '<span class="ab-icon dashicons dashicons-comments"></span><span class="ab-label">' . __('LocalChat', 'localchat') . '</span>',
            'href' => admin_url('admin.php?page=localchat'),
        ]);
        
        // Open conversations badge
        if ($count > 0) {
            $wp_admin_bar->add_node([
                'id' => 'localchat-open',
                'parent' => 'localchat',
                'title' => sprintf(
                    __('Open Conversations (%d)', 'localchat'),
                    $count
                ),
                'href' => admin_url('admin.php?page=localchat-inbox&status=open'),
            ]);
        }
        
        // Quick links
        $wp_admin_bar->add_node([
            'id' => 'localchat-inbox',
            'parent' => 'localchat',
            'title' => __('Inbox', 'localchat'),
            'href' => admin_url('admin.php?page=localchat-inbox'),
        ]);
        
        $wp_admin_bar->add_node([
            'id' => 'localchat-visitors',
            'parent' => 'localchat',
            'title' => __('Visitors', 'localchat'),
            'href' => admin_url('admin.php?page=localchat-visitors'),
        ]);
        
        $wp_admin_bar->add_node([
            'id' => 'localchat-settings',
            'parent' => 'localchat',
            'title' => __('Settings', 'localchat'),
            'href' => admin_url('admin.php?page=localchat-settings'),
        ]);
    }
    
    /**
     * Render dashboard page.
     *
     * @since    1.0.0
     */
    public function render_dashboard() {
        $this->render_view('dashboard');
    }
    
    /**
     * Render inbox page.
     *
     * @since    1.0.0
     */
    public function render_inbox() {
        $this->render_view('inbox');
    }
    
    /**
     * Render visitors page.
     *
     * @since    1.0.0
     */
    public function render_visitors() {
        $this->render_view('visitors');
    }
    
    /**
     * Render chatbot page.
     *
     * @since    1.0.0
     */
    public function render_chatbot() {
        $this->render_view('chatbot');
    }
    
    /**
     * Render triggers page.
     *
     * @since    1.0.0
     */
    public function render_triggers() {
        $this->render_view('triggers');
    }
    
    /**
     * Render departments page.
     *
     * @since    1.0.0
     */
    public function render_departments() {
        $this->render_view('departments');
    }
    
    /**
     * Render appearance page.
     *
     * @since    1.0.0
     */
    public function render_appearance() {
        $this->render_view('appearance');
    }
    
    /**
     * Render settings page.
     *
     * @since    1.0.0
     */
    public function render_settings() {
        $this->render_view('settings');
    }
    
    /**
     * Render system info page.
     *
     * @since    1.0.0
     */
    public function render_system_info() {
        $this->render_view('system-info');
    }
    
    /**
     * Render a view template.
     *
     * @since    1.0.0
     * @param    string $view
     */
    private function render_view($view) {
        $view_file = LOCALCHAT_PLUGIN_DIR . 'includes/admin/views/' . $view . '.php';
        
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<div class="notice notice-error"><p>' . 
                 sprintf(__('View file not found: %s', 'localchat'), esc_html($view)) . 
                 '</p></div>';
        }
    }
}
