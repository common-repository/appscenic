<?php

namespace AppScenic;

/*
* Plugin Name:       AppScenic
* Description:       Seamlessly connect your WooCommerce store to top-tier domestic dropshipping suppliers worldwide (USA/UK/EU/CAN etc.). Automate orders, sync stock & prices 24/7, and unleash AI-powered product optimization. Start dropshipping smarter with AppScenic for WooCommerce.
* Version:           1.2.2
* Requires at least: 5.4
* Requires PHP:      7.0
* Author:            AppScenic
* Author URI:        https://appscenic.com
* License:           GPL v2 or later
* License URI:       https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain:       appscenic
* Domain Path:       /languages
*/

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook( __FILE__, 'flush_rewrite_rules' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

(new Bootstrap())->register();
