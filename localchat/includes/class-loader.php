<?php
/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a central repository of all hooks that need to be registered,
 * and then run them during init.
 *
 * @since      1.0.0
 * @package    LocalChat
 * @subpackage LocalChat/includes
 */

namespace LocalChat;

if (!defined('ABSPATH')) {
    exit;
}

class Loader {
    
    /**
     * The array of actions associated with the plugin.
     *
     * @since    1.0.0
     * @var      array $actions
     */
    protected $actions;
    
    /**
     * The array of filters associated with the plugin.
     *
     * @since    1.0.0
     * @var      array $filters
     */
    protected $filters;
    
    /**
     * Initialize the collections used to maintain the actions and filters.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = [];
        $this->filters = [];
    }
    
    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string $hook             The name of the WordPress action that is being registered.
     * @param    object $component        A reference to the instance of the object on which the action is defined.
     * @param    string $callback         The name of the function definition on the $component.
     * @param    int    $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int    $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string $hook             The name of the WordPress filter that is being registered.
     * @param    object $component        A reference to the instance of the object on which the filter is defined.
     * @param    string $callback         The name of the function definition on the $component.
     * @param    int    $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int    $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * A utility function that organizes a single hook into an array.
     *
     * @since    1.0.0
     * @param    array  $hooks            The array of hooks to add to.
     * @param    string $hook             The name of the WordPress hook.
     * @param    object $component        The object containing the callback method.
     * @param    string $callback         The name of the callback method.
     * @param    int    $priority         The priority of the hook.
     * @param    int    $accepted_args    The number of arguments to pass to the callback.
     * @return   array                    An array of the hook configurations.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = [
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        ];
        
        return $hooks;
    }
    
    /**
     * Register the filters and actions with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                [$hook['component'], $hook['callback']],
                $hook['priority'],
                $hook['accepted_args']
            );
        }
        
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                [$hook['component'], $hook['callback']],
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
