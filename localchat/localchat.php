<?php
/**
 * Plugin Name: LocalChat – Live Chat & AI Chatbot (No Account Needed)
 * Plugin URI: https://github.com/yourusername/localchat
 * Description: A fully self-hosted, no-account, no-external-API live chat + AI chatbot plugin. Everything runs 100% locally on your WordPress server.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPLv2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: localchat
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LOCALCHAT_VERSION', '1.0.0');
define('LOCALCHAT_PLUGIN_FILE', __FILE__);
define('LOCALCHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LOCALCHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LOCALCHAT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check minimum requirements
register_activation_hook(LOCALCHAT_PLUGIN_FILE, 'localchat_check_requirements_and_activate');

function localchat_check_requirements_and_activate() {
    global $wpdb;
    
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '6.0', '<')) {
        deactivate_plugins(LOCALCHAT_PLUGIN_BASENAME);
        wp_die(
            __('LocalChat requires WordPress version 6.0 or higher.', 'localchat'),
            __('Plugin Activation Error', 'localchat'),
            ['back_link' => true]
        );
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        deactivate_plugins(LOCALCHAT_PLUGIN_BASENAME);
        wp_die(
            __('LocalChat requires PHP version 8.0 or higher.', 'localchat'),
            __('Plugin Activation Error', 'localchat'),
            ['back_link' => true]
        );
    }
    
    // Check database version
    $db_version = $wpdb->db_version();
    if (version_compare($db_version, '5.7', '<')) {
        deactivate_plugins(LOCALCHAT_PLUGIN_BASENAME);
        wp_die(
            __('LocalChat requires MySQL version 5.7 or higher (or MariaDB 10.3+).', 'localchat'),
            __('Plugin Activation Error', 'localchat'),
            ['back_link' => true]
        );
    }
    
    // Run activation
    require_once LOCALCHAT_PLUGIN_DIR . 'includes/class-activator.php';
    LocalChat\Activator::activate();
}

// Deactivation hook
register_deactivation_hook(LOCALCHAT_PLUGIN_FILE, function() {
    require_once LOCALCHAT_PLUGIN_DIR . 'includes/class-deactivator.php';
    LocalChat\Deactivator::deactivate();
});

// Uninstall hook is handled by uninstall.php

// Initialize plugin
add_action('plugins_loaded', 'localchat_init', 0);

function localchat_init() {
    // Load text domain
    load_plugin_textdomain('localchat', false, dirname(LOCALCHAT_PLUGIN_BASENAME) . '/languages');
    
    // Autoloader
    spl_autoload_register(function($class) {
        // Check if class is in our namespace
        $prefix = 'LocalChat\\';
        $base_dir = LOCALCHAT_PLUGIN_DIR . 'includes/';
        
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        // Get relative class name
        $relative_class = substr($class, $len);
        
        // Replace namespace separators with directory separators
        $file = $base_dir . str_replace('\\', '-', strtolower($relative_class)) . '.php';
        
        // If file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    });
    
    // Initialize core classes
    require_once LOCALCHAT_PLUGIN_DIR . 'includes/class-loader.php';
    require_once LOCALCHAT_PLUGIN_DIR . 'includes/class-i18n.php';
    
    $loader = new LocalChat\Loader();
    $i18n = new LocalChat\I18n();
    
    $loader->add_action('init', $i18n, 'load_plugin_textdomain');
    
    // Initialize admin
    if (is_admin()) {
        require_once LOCALCHAT_PLUGIN_DIR . 'includes/admin/class-admin-menu.php';
        $admin = new LocalChat\Admin\AdminMenu();
        $admin->init($loader);
    }
    
    // Initialize public widget
    require_once LOCALCHAT_PLUGIN_DIR . 'includes/public/class-widget-loader.php';
    $widget = new LocalChat\Public\WidgetLoader();
    $widget->init($loader);
    
    // Initialize shortcode
    require_once LOCALCHAT_PLUGIN_DIR . 'includes/public/class-shortcode.php';
    $shortcode = new LocalChat\Public\Shortcode();
    $shortcode->init($loader);
    
    // Initialize REST API
    require_once LOCALCHAT_PLUGIN_DIR . 'includes/rest-api/class-rest-controller.php';
    // Additional REST controllers will be registered here
    
    // Run the loader
    $loader->run();
}

// Add custom capabilities
add_action('init', 'localchat_add_capabilities', 11);

function localchat_add_capabilities() {
    require_once LOCALCHAT_PLUGIN_DIR . 'includes/security/class-capabilities.php';
    LocalChat\Security\Capabilities::add_capabilities();
}
