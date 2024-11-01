<?php

namespace JMS\WooCommerce\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class jmsRegisterBlocks
 * @package JMS\WooCommerce\Blocks
 */
class jmsRegisterBlocks {
	/**
	 * Initialize class
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
	}

	/**
	 * Register blocks
	 */
	public static function register_blocks() {
		$blocks = [
			'LatestProducts',
			'FeaturedProducts',
			'TopSellerProducts',
			'OnSaleProducts',
			'TopRatedProducts',
			'FilterTab',
			'CategoriesTab'
		];
		foreach ( $blocks as $class ) {
			$class    = __NAMESPACE__ . '\\BlockTypes\\' . $class;
			$instance = new $class();
			$instance->register_block_type();
		}
	}
}
