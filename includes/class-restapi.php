<?php
namespace JMS\WooCommerce\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class jmsRestApi {

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ), 10);
    }

    public static function register_rest_routes() {
        $controllers = self::get_controllers();
        foreach ( $controllers as $name => $class ) {
            $instance = new $class();
            $instance->register_routes();
        }
    }

    protected static function get_controllers() {
        return [
            'products'  => __NAMESPACE__ . '\RestApi\Controllers\Products',
        ];
    }

}
jmsRestApi::init();