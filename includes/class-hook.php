<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'jmsWooBlocks' ) ) {
	/**
	 *  Class jmsWooBlocks.
	 */
	class jmsWooBlocks {

		/**
		 *  Class jmsWooBlocks constructor.
		 */
		public function __construct() {

			/*Action hook*/
			add_action( 'init', array( $this, 'init_func' ) );
			add_action( 'enqueue_block_assets', array( $this, 'jms_frontend_scripts' ) );
			add_action( 'wp_ajax_jms_ajax_get_products', array( $this, 'jms_ajax_get_products' ) );
			add_action( 'wp_ajax_nopriv_jms_ajax_get_products', array( $this, 'jms_ajax_get_products' ) );

			/*Filter hook*/
			add_filter( 'block_categories', array( $this, 'gutenberg_jms_wooblocks_category' ), 10, 2 );
		}

		/**
		 * -------------------------------------------------------------------------------------------------------------
		 * Public function
		 * -------------------------------------------------------------------------------------------------------------
		 */

		/**
		 * Register block scripts & styles.
		 */
		public function init_func() {
			$this->register_style( 'jms-block-editor', plugins_url( 'assets/css/editor.css', __DIR__ ), array( 'wp-edit-blocks' ) );
			$this->register_style( 'jms-block-style', plugins_url( 'assets/css/style.css', __DIR__ ), array() );

			// Shared libraries and components across all blocks.
			$this->register_script( 'jms-blocks', plugins_url( 'assets/js/blocks.js', __DIR__ ), array(), false );
			$this->register_script( 'jms-vendors', plugins_url( 'assets/js/vendors.js', __DIR__ ), array(), false );
			$this->register_script( 'jms-vendors-frontend', plugins_url( 'assets/js/vendors-frontend.js', __DIR__ ), array(), false );

			// Individual blocks.
			$this->register_script( 'jms-latest-products', plugins_url( 'assets/js/latest-products.js', __DIR__ ), array( 'jms-vendors', 'jms-blocks' ) );
			$this->register_script( 'jms-featured-products', plugins_url( 'assets/js/featured-products.js', __DIR__ ), array( 'jms-vendors', 'jms-blocks' ) );
			$this->register_script( 'jms-top-seller-products', plugins_url( 'assets/js/top-seller-products.js', __DIR__ ), array( 'jms-vendors', 'jms-blocks' ) );
			$this->register_script( 'jms-top-rated-products', plugins_url( 'assets/js/top-rated-products.js', __DIR__ ), array( 'jms-vendors', 'jms-blocks' ) );
			$this->register_script( 'jms-on-sale-products', plugins_url( 'assets/js/on-sale-products.js', __DIR__ ), array( 'jms-vendors', 'jms-blocks' ) );
			$this->register_script( 'jms-filter-tab', plugins_url( 'assets/js/filter-tab.js', __DIR__ ), array( 'jms-vendors', 'jms-blocks' ) );
			$this->register_script( 'jms-categories-tab', plugins_url( 'assets/js/categories-tab.js', __DIR__ ), array( 'jms-vendors', 'jms-blocks' ) );

			$this->register_script( 'jms-block-frontend', plugins_url( 'assets/js/blocks.frontend.js', __DIR__ ), array( 'jms-vendors-frontend' ) );
			wp_localize_script( 'jms-block-frontend', 'JmsWooBlocks', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

			$this->register_script( 'jms-filter-tab-frontend', plugins_url( 'assets/js/filter-tab-frontend.js', __DIR__ ), array( 'jms-vendors-frontend' ) );
			wp_localize_script( 'jms-filter-tab-frontend', 'JmsWooFilterTabBlocks', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

			$this->register_script( 'jms-categories-tab-frontend', plugins_url( 'assets/js/categories-tab-frontend.js', __DIR__ ), array( 'jms-vendors-frontend' ) );
			$product_categories = get_terms(
				'product_cat',
				array(
					'hide_empty' => false,
					'pad_counts' => true,
				)
			);
			foreach ( $product_categories as &$category ) {
				$thumbnail_id    = get_term_meta( $category->term_id, 'thumbnail_id', true );
				$category->image = wp_get_attachment_image_src( $thumbnail_id )[0];
				$category->link  = get_term_link( $category->term_id, 'product_cat' );
			};
			wp_localize_script( 'jms-categories-tab-frontend', 'JmsWooCategoriesTabBlocks', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'productCategories' => $product_categories ) );
		}

		/**
		 * Check frontend has block
		 */
		public function jms_frontend_scripts() {
			if ( has_block( 'jmsthemes-blocks/latest-products' ) || has_block( 'jmsthemes-blocks/on-sale-products' ) || has_block( 'jmsthemes-blocks/top-rated-products' ) || has_block( 'jmsthemes-blocks/top-seller-products' ) || has_block( 'jmsthemes-blocks/featured-products' ) ) {
				wp_enqueue_script( 'jms-block-frontend' );
			}
			if ( has_block( 'jmsthemes-blocks/categories-tab' ) ) {
				wp_enqueue_script( 'jms-categories-tab-frontend' );
			}
			if ( has_block( 'jmsthemes-blocks/filter-tab' ) ) {
				wp_enqueue_script( 'jms-filter-tab-frontend' );
			}
		}

		/**
		 * Add gutenberg category
		 *
		 * @param array $categories Array of block categories.
		 * @param int   $post       Post being loaded.
		 *
		 * @return array
		 */
		public function gutenberg_jms_wooblocks_category( $categories, $post ) {
			$check = array_search( 'jmsthemes-blocks', array_column( $categories, 'slug' ) , true );
			if ( ! $check ) {
				return array_merge(
					$categories,
					array(
						array(
							'slug'  => 'jmsthemes-blocks',
							'title' => __( 'Jmsthemes Blocks', 'jms-wooblocks' ),
						),
					)
				);
			}

			return $categories;
		}

		/**
		 * Ajax get content-product template
		 */
		public function jms_ajax_get_products() {
			$results_html = [];
			$args         = array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'orderby'     => 'date ID',
				'order'       => 'DESC',
			);
			$slug         = 'content';
			$name         = 'product';
			if ( isset( $_REQUEST['attributes'] ) && is_array( $_REQUEST['attributes'] ) && ! empty( $_REQUEST['attributes'] ) ) {
				$attributes = array_map( 'sanitize_text_field', wp_unslash( $_REQUEST['attributes'] ) );
				$custom_tpl = ! empty( $attributes['custom_tpl'] ) ? sanitize_file_name( $attributes['custom_tpl'] ) : '';
				$limit      = ! empty( $attributes['limit'] ) ? intval( $attributes['limit'] ) : 15;
				$order      = ! empty( $attributes['order'] ) ? $attributes['order'] : 'DESC';

				$args['posts_per_page'] = $limit;
				$args['order']          = $order;

				if ( ! empty( $attributes['orderby'] ) ) {
					switch ( $attributes['orderby'] ) {
						case 'date':
							$args['orderby'] = 'date ID';
							break;
						case 'price':
							$args['orderby']  = 'meta_value_num ID';
							$args['meta_key'] = '_price';
							break;
						case 'title':
							$args['orderby'] = 'title';
							break;
						case 'menu_order':
							$args['orderby'] = 'menu_order title';
							break;
						case 'popularity':
							$args['orderby']  = 'meta_value_num ID';
							$args['meta_key'] = 'total_sales';
							break;
					}
				}

				if ( ! empty( $attributes['block_type'] ) ) {
					switch ( $attributes['block_type'] ) {
						case 'latest-products':
							$args['orderby'] = 'date ID';
							break;
						case 'featured-products':
							$args['tax_query'][] = array(
								'taxonomy' => 'product_visibility',
								'field'    => 'name',
								'terms'    => 'featured',
								'operator' => 'IN',
							);
							break;
						case 'top-seller-products':
							$args['orderby']  = 'meta_value_num';
							$args['meta_key'] = 'total_sales';
							break;
						case 'on-sale-products':
							$args['post__in'] = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
							break;
						case 'top-rated-products':
							$args['meta_key'] = '_wc_average_rating';
							$args['orderby']  = 'meta_value_num ID';
							break;
					}
				}

				if ( ! empty( $attributes['categories'] ) ) {
					$args['tax_query'][] = array(
						'taxonomy'         => 'product_cat',
						'terms'            => absint( $attributes['categories'] ),
						'field'            => 'term_id',
						'operator'         => 'IN',
						'include_children' => true,
					);
				}

				$product_visibility_term_ids = wc_get_product_visibility_term_ids();
				$args['tax_query'][]         = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_term_ids['exclude-from-catalog'],
					'operator' => 'NOT IN',
				);
				if ( $custom_tpl ) {
					$filename = basename( $custom_tpl, '.php' );
					if ( strpos( $filename, '-' ) ) {
						$template = locate_template(
							array(
								"{$filename}.php",
								WC()->template_path() . "{$filename}.php",
							)
						);
						if ( ! $template ) {
							$fallback = WC()->plugin_path() . "/templates/{$filename}.php";
							$template = file_exists( $fallback ) ? $fallback : '';
						}
						if ( $template ) {
							$slug = substr( $filename, 0, strpos( $filename, '-' ) );
							$name = substr( $filename, strpos( $filename, '-' ) + 1 );
						}
					}
				}

				$ajax_query = new WP_Query( $args );

				if ( $ajax_query->have_posts() ) {
					while ( $ajax_query->have_posts() ) {
						$ajax_query->the_post();
						ob_start();
						wc_get_template_part( $slug, $name );
						$results_html[] = ob_get_contents();
						ob_end_clean();
					}
					wp_reset_postdata();
				}
			}

			echo wp_json_encode( $results_html );

			die();
		}

		/**
		 * -------------------------------------------------------------------------------------------------------------
		 * Protected function
		 * -------------------------------------------------------------------------------------------------------------
		 */


		/**
		 * Registers a script according to `wp_register_script`, additionally loading the translations for the file.
		 *
		 * @param string $handle    Name of the script. Should be unique.
		 * @param string $src       Full URL of the script, or path of the script relative to the WordPress root directory.
		 * @param array  $deps      Optional. An array of registered script handles this script depends on. Default empty array.
		 * @param bool   $has_i18n  Optional. Whether to add a script translation call to this file. Default 'true'.
		 */
		protected function register_script( $handle, $src, $deps = array(), $has_i18n = true ) {
			$filename     = str_replace( plugins_url( '/', __DIR__ ), '', $src );
			$deps_path    = dirname( __DIR__ ) . '/' . str_replace( '.js', '.deps.json', $filename );
			$dependencies = file_exists( $deps_path ) ? json_decode( file_get_contents( $deps_path ) ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$dependencies = array_merge( $dependencies, $deps );

			wp_register_script( $handle, $src, $dependencies, JMS_WOOBLOCKS_VERSION, true );
			if ( $has_i18n && function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( $handle, 'jms-wooblocks', dirname( __DIR__ ) . '/languages' );
			}
		}


		/**
		 * Registers a style according to `wp_register_style`.
		 *
		 * @param string $handle Name of the stylesheet. Should be unique.
		 * @param string $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
		 * @param array  $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
		 * @param string $media  Optional. The media for which this stylesheet has been defined. Default 'all'. Accepts media types like
		 *                       'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
		 */
		protected function register_style( $handle, $src, $deps = array(), $media = 'all' ) {
			wp_register_style( $handle, $src, $deps, JMS_WOOBLOCKS_VERSION, $media );
		}
	}
}
