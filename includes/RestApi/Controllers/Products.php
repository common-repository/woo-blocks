<?php
namespace JMS\WooCommerce\Blocks\RestApi\Controllers;

defined( 'ABSPATH' ) || exit;

use \WC_REST_Products_Controller;
/**
 * REST API Products controller class.
 */
class Products extends WC_REST_Products_Controller {

    protected $namespace = 'jms/blocks';

    protected $rest_base = 'products';

    public function register_routes() {
        \register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                    'args'                => $this->get_collection_params(),
                ),
                'schema' => array( $this, 'get_public_item_schema' ),
            )
        );
    }

    /**
     * Check if a given request has access to read items.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|boolean
     */
    public function get_items_permissions_check( $request ) {
        if ( ! \current_user_can( 'edit_posts' ) ) {
            return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'jms-wooblocks' ), array( 'status' => \rest_authorization_required_code() ) );
        }
        return true;
    }

    /**
     * Check if a given request has access to read an item.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|boolean
     */
    public function get_item_permissions_check( $request ) {
        if ( ! \current_user_can( 'edit_posts' ) ) {
            return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'jms-wooblocks' ), array( 'status' => \rest_authorization_required_code() ) );
        }
        return true;
    }

    /**
     * Make extra product orderby features supported by WooCommerce available to the WC API.
     * This includes 'price', 'popularity', and 'rating'.
     *
     * @param WP_REST_Request $request Request data.
     * @return array
     */
    protected function prepare_objects_query( $request ) {
        $args             = parent::prepare_objects_query( $request );
        $catalog_visibility = $request->get_param( 'catalog_visibility' );

        if ( in_array( $catalog_visibility, array_keys( wc_get_product_visibility_options() ), true ) ) {
            $exclude_from_catalog = 'search' === $catalog_visibility ? '' : 'exclude-from-catalog';
            $exclude_from_search  = 'catalog' === $catalog_visibility ? '' : 'exclude-from-search';

            $args['tax_query'][] = array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => array( $exclude_from_catalog, $exclude_from_search ),
                'operator' => 'hidden' === $catalog_visibility ? 'AND' : 'NOT IN',
            );
        }

        $orderby = $request->get_param( 'orderby' );
        if($orderby == 'top_seller'){
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'total_sales';
        }
        return $args;
    }
    /**
     * Get product data.
     *
     * @param \WC_Product|\WC_Product_Variation $product Product instance.
     * @param string                            $context Request context. Options: 'view' and 'edit'.
     * @return array
     */
    protected function get_product_data( $product, $context = 'view' ) {
        return array(
            'id'             => $product->get_id(),
            'name'           => $product->get_title(),
            'variation'      => $product->is_type( 'variation' ) ? wc_get_formatted_variation( $product, true, true, false ) : '',
            'permalink'      => $product->get_permalink(),
            'sku'            => $product->get_sku(),
            'description'    => apply_filters( 'woocommerce_short_description', $product->get_short_description() ? $product->get_short_description() : wc_trim_string( $product->get_description(), 400 ) ),
            'price'          => $product->get_price(),
            'price_html'     => $product->get_price_html(),
            'images'         => $this->get_images( $product ),
            'average_rating' => $product->get_average_rating(),
        );
    }

    /**
     * Get the images for a product or product variation.
     *
     * @param \WC_Product|\WC_Product_Variation $product Product instance.
     * @return array
     */
    protected function get_images( $product ) {
        $images         = array();
        $attachment_ids = array();

        // Add featured image.
        if ( $product->get_image_id() ) {
            $attachment_ids[] = $product->get_image_id();
        }

        // Add gallery images.
        $attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );

        // Build image data.
        foreach ( $attachment_ids as $attachment_id ) {
            $attachment_post = get_post( $attachment_id );
            if ( is_null( $attachment_post ) ) {
                continue;
            }

            $attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
            if ( ! is_array( $attachment ) ) {
                continue;
            }

            $images[] = array(
                'id'   => (int) $attachment_id,
                'src'  => current( $attachment ),
                'name' => get_the_title( $attachment_id ),
                'alt'  => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
            );
        }

        return $images;
    }

    /**
     * Update the collection params.
     *
     * Adds new options for 'orderby', and new parameters 'category_operator', 'attribute_operator'.
     *
     * @return array
     */
    public function get_collection_params() {
        $params                       = parent::get_collection_params();
        $params['orderby']['enum']    = array_merge( $params['orderby']['enum'], array( 'price', 'popularity', 'rating', 'menu_order','top_seller') );
        $params['catalog_visibility'] = array(
            'description'       => __( 'Determines if hidden or visible catalog products are shown.', 'woo-gutenberg-products-block' ),
            'type'              => 'string',
            'enum'              => array( 'visible', 'catalog', 'search', 'hidden' ),
            'sanitize_callback' => 'sanitize_key',
            'validate_callback' => 'rest_validate_request_arg',
        );

        return $params;
    }
    /**
     * Get the Product's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema() {
        $schema = array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'product_block_product',
            'type'       => 'object',
            'properties' => array(
                'id'             => array(
                    'description' => __( 'Unique identifier for the resource.', 'jms-wooblocks' ),
                    'type'        => 'integer',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'readonly'    => true,
                ),
                'name'           => array(
                    'description' => __( 'Product name.', 'jms-wooblocks' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit', 'embed' ),
                ),
                'variation'      => array(
                    'description' => __( 'Product variation attributes, if applicable.', 'jms-wooblocks' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit', 'embed' ),
                ),
                'permalink'      => array(
                    'description' => __( 'Product URL.', 'jms-wooblocks' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'readonly'    => true,
                ),
                'description'    => array(
                    'description' => __( 'Short description or excerpt from description.', 'jms-wooblocks' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit', 'embed' ),
                ),
                'sku'            => array(
                    'description' => __( 'Unique identifier.', 'jms-wooblocks' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                ),
                'price'          => array(
                    'description' => __( 'Current product price.', 'jms-wooblocks' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'price_html'     => array(
                    'description' => __( 'Price formatted in HTML.', 'jms-wooblocks' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'average_rating' => array(
                    'description' => __( 'Reviews average rating.', 'jms-wooblocks' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'images'         => array(
                    'description' => __( 'List of images.', 'jms-wooblocks' ),
                    'type'        => 'object',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'   => array(
                                'description' => __( 'Image ID.', 'jms-wooblocks' ),
                                'type'        => 'integer',
                                'context'     => array( 'view', 'edit' ),
                            ),
                            'src'  => array(
                                'description' => __( 'Image URL.', 'jms-wooblocks' ),
                                'type'        => 'string',
                                'format'      => 'uri',
                                'context'     => array( 'view', 'edit' ),
                            ),
                            'name' => array(
                                'description' => __( 'Image name.', 'jms-wooblocks' ),
                                'type'        => 'string',
                                'context'     => array( 'view', 'edit' ),
                            ),
                            'alt'  => array(
                                'description' => __( 'Image alternative text.', 'jms-wooblocks' ),
                                'type'        => 'string',
                                'context'     => array( 'view', 'edit' ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        return $this->add_additional_fields_schema( $schema );
    }
}
