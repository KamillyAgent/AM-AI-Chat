<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin so that it
 * is ready for translation.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes
 */

namespace LocalChat;

if (!defined('ABSPATH')) {
    exit;
}

class I18n {
    
    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'localchat',
            false,
            dirname(LOCALCHAT_PLUGIN_BASENAME) . '/languages/'
        );
    }
}
