<?php
/*
 * Plugin Name: Pluginboilerplate
 * Description: Pluginboilerplate__description
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Text Domain: pluginboilerplate
 */

declare(strict_types=1);

use Pluginboilerplatevendor\Pluginboilerplate\Activate;
use Pluginboilerplatevendor\Pluginboilerplate\Deactivate;
use Pluginboilerplatevendor\Pluginboilerplate\Plugin;

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook(__FILE__, function () {
    Activate::run();
});

register_deactivation_hook(__FILE__, function () {
    Deactivate::run();
});

add_action('plugins_loaded', function () {
    Plugin::run(__FILE__);
});