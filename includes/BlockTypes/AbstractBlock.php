<?php
namespace JMS\WooCommerce\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

abstract class AbstractBlock {

	protected $namespace = 'jmsthemes-blocks';

	protected $block_name = '';

    protected $query_args = array();

    public function register_block_type() {
		register_block_type(
			$this->namespace . '/' . $this->block_name,
			array(
				'editor_script'   => 'jms-' . $this->block_name,
				'editor_style'    => 'jms-block-editor',
				'style'           => 'jms-block-style',
			)
		);
	}
}
