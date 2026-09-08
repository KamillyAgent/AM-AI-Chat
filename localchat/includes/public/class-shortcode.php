<?php
/**
 * Shortcode handler for [localchat] and [localchat hide="true"].
 *
 * Allows showing/hiding chat on specific pages/posts via shortcode.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes/public
 */

namespace LocalChat\Public;

if (!defined('ABSPATH')) {
    exit;
}

class Shortcode {
    
    /**
     * Initialize shortcode.
     *
     * @since    1.0.0
     * @param    Loader $loader
     */
    public function init($loader) {
        $loader->add_shortcode('localchat', $this, 'render_shortcode');
    }
    
    /**
     * Render shortcode.
     *
     * Usage:
     * - [localchat] - Show chat on this page (overrides visibility rules)
     * - [localchat hide="true"] - Hide chat on this page
     * - [localchat show="true"] - Explicitly show chat
     *
     * @since    1.0.0
     * @param    array  $atts
     * @param    string $content
     * @return   string
     */
    public function render_shortcode($atts = [], $content = '') {
        $atts = shortcode_atts([
            'hide' => 'false',
            'show' => 'false',
        ], $atts, 'localchat');
        
        // Store in global for widget loader to check
        global $localchat_shortcode_override;
        
        if ($atts['hide'] === 'true') {
            $localchat_shortcode_override = 'hide';
        } elseif ($atts['show'] === 'true') {
            $localchat_shortcode_override = 'show';
        }
        
        // Shortcode doesn't output anything visible
        // It just controls widget visibility
        return '';
    }
    
    /**
     * Check if shortcode override is active.
     *
     * @since    1.0.0
     * @return   string|false 'hide', 'show', or false
     */
    public static function get_override() {
        global $localchat_shortcode_override;
        return isset($localchat_shortcode_override) ? $localchat_shortcode_override : false;
    }
}
