<?php
/*
* Plugin Name: Awesome blocks for WooCommerce
* Plugin URI: https://jmsthemes.com/product/woocommerce-blocks/
* Description: JMS WooCommerce Blocks for the Gutenberg editor.
* Version: 1.0.0
* Author: Jmsthemes
* Author URI: http://joommasters.com
* License:     GPL2
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: jms-wooblocks
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
    return;
}

require __DIR__ . '/vendor/autoload.php';

define('JMS_WOOBLOCKS_PLUGIN_PATH' , plugin_dir_path(__FILE__));
define('JMS_WOOBLOCKS_URL', plugin_dir_url(__FILE__));
define('JMS_WOOBLOCKS_CSS_URL', JMS_WOOBLOCKS_URL . 'assets/css/');
define('JMS_WOOBLOCKS_JS_URL', JMS_WOOBLOCKS_URL . 'assets/js/');
define('JMS_WOOBLOCKS_IMAGES_URL', JMS_WOOBLOCKS_URL . 'assets/images/');
define('JMS_WOOBLOCKS_ADMIN_PATH' , JMS_WOOBLOCKS_PLUGIN_PATH . 'admin/');
define( 'JMS_WOOBLOCKS_VERSION', '1.0.0' );


register_activation_hook( __FILE__, 'jms_wooblocks_activate' );
function jms_wooblocks_activate() {
    global $wp_version;
    if ( version_compare( $wp_version, "5.0", "<" ) ) {
        deactivate_plugins( basename( __FILE__ ) ); // Deactivate our plugin
        wp_die( "This plugin requires WordPress version 5.0 or higher." );
    }
    if ( !function_exists( 'WC' ) ) {
        deactivate_plugins( basename( __FILE__ ) ); // Deactivate our plugin
        wp_die( "This plugin requires WooCommerce in order to work." );
    }
}

register_deactivation_hook(__FILE__, 'jms_wooblocks_deactivation');
function jms_wooblocks_deactivation() {
    return true;
}

require 'includes/class-hook.php';
require 'includes/class-register-blocks.php';
require 'includes/class-restapi.php';

add_action( 'plugins_loaded', array( '\JMS\WooCommerce\Blocks\jmsRegisterBlocks', 'init' ) );
new jmsWooBlocks();
