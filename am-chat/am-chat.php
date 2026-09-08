<?php
/**
 * Plugin Name: AM-Chat – Live Chat & AI Chatbot (No Account Needed)
 * Plugin URI: https://github.com/yourusername/am-chat
 * Description: A fully self-hosted, no-account, no-external-API live chat + AI chatbot plugin. Everything runs 100% locally on your WordPress server.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPLv2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: amchat
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AMCHAT_VERSION', '1.0.0');
define('AMCHAT_PLUGIN_FILE', __FILE__);
define('AMCHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AMCHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AMCHAT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check minimum requirements
register_activation_hook(AMCHAT_PLUGIN_FILE, 'amchat_check_requirements_and_activate');

function amchat_check_requirements_and_activate() {
    global $wpdb;
    
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '6.0', '<')) {
        deactivate_plugins(AMCHAT_PLUGIN_BASENAME);
        wp_die(
            __('AM-Chat requires WordPress version 6.0 or higher.', 'amchat'),
            __('Plugin Activation Error', 'amchat'),
            ['back_link' => true]
        );
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        deactivate_plugins(AMCHAT_PLUGIN_BASENAME);
        wp_die(
            __('AM-Chat requires PHP version 8.0 or higher.', 'amchat'),
            __('Plugin Activation Error', 'amchat'),
            ['back_link' => true]
        );
    }
    
    // Check database version
    $db_version = $wpdb->db_version();
    if (version_compare($db_version, '5.7', '<')) {
        deactivate_plugins(AMCHAT_PLUGIN_BASENAME);
        wp_die(
            __('AM-Chat requires MySQL version 5.7 or higher (or MariaDB 10.3+).', 'amchat'),
            __('Plugin Activation Error', 'amchat'),
            ['back_link' => true]
        );
    }
    
    // Run activation
    require_once AMCHAT_PLUGIN_DIR . 'includes/class-activator.php';
    AmChat\Activator::activate();
}

// Deactivation hook
register_deactivation_hook(AMCHAT_PLUGIN_FILE, function() {
    require_once AMCHAT_PLUGIN_DIR . 'includes/class-deactivator.php';
    AmChat\Deactivator::deactivate();
});

// Uninstall hook is handled by uninstall.php

// Initialize plugin
add_action('plugins_loaded', 'amchat_init', 0);

function amchat_init() {
    // Load text domain
    load_plugin_textdomain('amchat', false, dirname(AMCHAT_PLUGIN_BASENAME) . '/languages');
    
    // Autoloader
    spl_autoload_register(function($class) {
        // Check if class is in our namespace
        $prefix = 'AmChat\\';
        $base_dir = AMCHAT_PLUGIN_DIR . 'includes/';
        
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
    require_once AMCHAT_PLUGIN_DIR . 'includes/class-loader.php';
    require_once AMCHAT_PLUGIN_DIR . 'includes/class-i18n.php';
    
    $loader = new AmChat\Loader();
    $i18n = new AmChat\I18n();
    
    $loader->add_action('init', $i18n, 'load_plugin_textdomain');
    
    // Initialize admin
    if (is_admin()) {
        require_once AMCHAT_PLUGIN_DIR . 'includes/admin/class-admin-menu.php';
        $admin = new AmChat\Admin\AdminMenu();
        $admin->init($loader);
    }
    
    // Initialize public widget
    require_once AMCHAT_PLUGIN_DIR . 'includes/public/class-widget-loader.php';
    $widget = new AmChat\Public\WidgetLoader();
    $widget->init($loader);
    
    // Initialize shortcode
    require_once AMCHAT_PLUGIN_DIR . 'includes/public/class-shortcode.php';
    $shortcode = new AmChat\Public\Shortcode();
    $shortcode->init($loader);
    
    // Initialize REST API
    require_once AMCHAT_PLUGIN_DIR . 'includes/rest-api/class-rest-controller.php';
    // Additional REST controllers will be registered here
    
    // Run the loader
    $loader->run();
}

// Add custom capabilities
add_action('init', 'amchat_add_capabilities', 11);

function amchat_add_capabilities() {
    require_once AMCHAT_PLUGIN_DIR . 'includes/security/class-capabilities.php';
    AmChat\Security\Capabilities::add_capabilities();
}
