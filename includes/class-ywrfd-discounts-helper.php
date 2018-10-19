<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'YWRFD_Discounts_Helper' ) ) {

	/**
	 * Discount class
	 *
	 * @class   YWRFD_Discounts_Helper
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 *
	 */
	class YWRFD_Discounts_Helper {

		/**
		 * Single instance of the class
		 *
		 * @var \YWRFD_Discounts_Helper
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * @var $post_type string post type name
		 */
		protected $post_type = 'ywrfd-discount';

		/**
		 * @var $saved_meta_box bool
		 */
		private $saved_meta_box = false;

		/**
		 * Returns single instance of the class
		 *
		 * @return \YWRFD_Discounts_Helper
		 * @since 1.0.0
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {

				self::$instance = new self;

			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since   1.0.0
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function __construct() {

			add_action( 'admin_init', array( $this, 'add_capabilities' ) );
			add_filter( 'yith_wcmv_review_discounts_caps', array( $this, 'get_capabilities' ) );

			add_action( 'init', array( $this, 'add_ywrfd_post_type' ) );

			if ( is_admin() ) {

				add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_ids' ) );
				add_filter( "manage_{$this->post_type}_posts_columns", array( $this, 'set_custom_columns' ) );
				add_action( "manage_{$this->post_type}_posts_custom_column", array( $this, 'render_custom_columns' ), 10, 2 );
				add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );
				add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
				add_action( 'ywrfd_process_meta', array( $this, 'save_metabox' ) );
				add_filter( "views_edit-{$this->post_type}", array( $this, 'set_views' ), 10, 1 );

			}

		}

		/**
		 * Add YWRFD post type
		 *
		 * @since   1.0.0
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function add_ywrfd_post_type() {

			$labels = array(
				'name'               => _x( 'Review Discounts', 'Post Type General Name', 'yith-woocommerce-review-for-discounts' ),
				'singular_name'      => _x( 'Review Discount', 'Post Type Singular Name', 'yith-woocommerce-review-for-discounts' ),
				'add_new_item'       => __( 'Add New Review Discount', 'yith-woocommerce-review-for-discounts' ),
				'add_new'            => __( 'Add Review Discount', 'yith-woocommerce-review-for-discounts' ),
				'new_item'           => __( 'New Review Discount', 'yith-woocommerce-review-for-discounts' ),
				'edit_item'          => __( 'Edit Review Discount', 'yith-woocommerce-review-for-discounts' ),
				'view_item'          => __( 'View Review Discount', 'yith-woocommerce-review-for-discounts' ),
				'search_items'       => __( 'Search Review Discount', 'yith-woocommerce-review-for-discounts' ),
				'not_found'          => __( 'Not found', 'yith-woocommerce-review-for-discounts' ),
				'not_found_in_trash' => __( 'Not found in Trash', 'yith-woocommerce-review-for-discounts' ),
			);

			$args = array(
				'labels'              => $labels,
				'supports'            => array( 'title' ),
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'menu_position'       => 10,
				'show_in_nav_menus'   => false,
				'has_archive'         => true,
				'exclude_from_search' => true,
				'menu_icon'           => 'dashicons-awards',
				'capability_type'     => 'ywrfd-discount',
				'capabilities'        => $this->get_capabilities(),
				'map_meta_cap'        => true,
				'rewrite'             => false,
				'publicly_queryable'  => false,
				'query_var'           => false,
			);

			register_post_type( $this->post_type, $args );

		}

		/**
		 * Add management capabilities to Admin and Shop Manager
		 *
		 * @since   1.0.0
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function add_capabilities() {

			$caps = $this->get_capabilities();

			// gets the admin and shop_mamager roles
			$admin        = get_role( 'administrator' );
			$shop_manager = get_role( 'shop_manager' );

			foreach ( $caps as $key => $cap ) {
				$admin->add_cap( $cap );
				$shop_manager->add_cap( $cap );
			}

		}

		/**
		 * Get capabilities for custom post type
		 *
		 * @since   1.0.0
		 * @return  array
		 * @author  Alberto Ruggiero
		 */
		public function get_capabilities() {

			$capability_type = 'ywrfd-discount';

			return array(
				'edit_post'              => "edit_{$capability_type}",
				'read_post'              => "read_{$capability_type}",
				'delete_post'            => "delete_{$capability_type}",
				'edit_posts'             => "edit_{$capability_type}s",
				'edit_others_posts'      => "edit_others_{$capability_type}s",
				'publish_posts'          => "publish_{$capability_type}s",
				'read_private_posts'     => "read_private_{$capability_type}s",
				'read'                   => "read",
				'delete_posts'           => "delete_{$capability_type}s",
				'delete_private_posts'   => "delete_private_{$capability_type}s",
				'delete_published_posts' => "delete_published_{$capability_type}s",
				'delete_others_posts'    => "delete_others_{$capability_type}s",
				'edit_private_posts'     => "edit_private_{$capability_type}s",
				'edit_published_posts'   => "edit_published_{$capability_type}s",
				'create_posts'           => "edit_{$capability_type}s",
				'manage_posts'           => "manage_{$capability_type}s",
			);

		}

		/**
		 * Set custom columns
		 *
		 * @since   1.0.0
		 * @return  array
		 * @author  Alberto Ruggiero
		 */
		public function set_custom_columns() {

			$columns = array(
				'cb'          => '<input type="checkbox" />',
				'title'       => __( 'Title', 'yith-woocommerce-review-for-discounts' ),
				'description' => __( 'Description', 'yith-woocommerce-review-for-discounts' ),
				'trigger'     => __( 'Triggering event', 'yith-woocommerce-review-for-discounts' ),
				'date'        => __( 'Date', 'yith-woocommerce-review-for-discounts' ),
			);

			return $columns;

		}

		/**
		 * Render custom columns
		 *
		 * @since   1.0.0
		 *
		 * @param   $column
		 * @param   $post_id
		 *
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function render_custom_columns( $column, $post_id ) {

			$discount = new YWRFD_Discounts( $post_id );

			switch ( $column ) {

				case 'description' :

					echo $discount->description;

					break;

				case 'trigger' :

					$trigger = get_post_meta( $post_id, 'ywrfd_trigger', true );

					if ( $trigger == 'review' ) {

						$column_label = '';
						$column_tip   = '';
						$products     = count( $discount->trigger_product_ids );
						$categories   = count( $discount->trigger_product_categories );

						if ( $products > 0 ) {

							$column_label .= sprintf( _n( 'Review of %d specific product', 'Review of %d specific products', $products, 'yith-woocommerce-review-for-discounts' ), $products ) . '<br />';
							$column_tip   .= __( 'Products', 'yith-woocommerce-review-for-discounts' ) . ':<br />';

							foreach ( $discount->trigger_product_ids as $product_id ) {

								$product = wc_get_product( $product_id );

								if ( is_object( $product ) ) {

									$column_tip .= wp_kses_post( $product->get_formatted_name() ) . '<br />';

								}

							}

							if ( $categories > 0 ) {
								$column_tip .= '<br />';
							}

						}

						if ( $categories > 0 ) {

							$column_label .= sprintf( _n( 'Review of any product of %d specific category', 'Review of any product of %d specific categories', $categories, 'yith-woocommerce-review-for-discounts' ), $categories ) . '<br />';
							$column_tip   .= __( 'Categories', 'yith-woocommerce-review-for-discounts' ) . ':<br />';

							foreach ( $discount->trigger_product_categories as $category_id ) {

								$category = get_term( $category_id, 'product_cat' );

								if ( is_object( $category ) ) {

									$column_tip .= wp_kses_post( $category->name ) . '<br />';

								}

							}

						}

						if ( $categories == 0 && $products == 0 ) {

							_e( 'Review of any product', 'yith-woocommerce-review-for-discounts' );

						} else {

							?>

                            <span class="tips" data-tip="<?php echo wc_sanitize_tooltip( $column_tip ) ?>"><?php echo $column_label; ?></span>

							<?php

						}

					} else {

						printf( _n( '%s review written', '%s reviews written', $discount->trigger_threshold, 'yith-woocommerce-review-for-discounts' ), $discount->trigger_threshold );

						if ( $discount->trigger_enable_notify == 'yes' ) {

							echo '<br />' . sprintf( __( 'Send notifications after %s reviews', 'yith-woocommerce-review-for-discounts' ), $discount->trigger_threshold_notify );
						}

					}

					break;

			}

		}

		/**
		 * Add custom post type screen to WooCommerce list
		 *
		 * @since   1.0.0
		 *
		 * @param   $screen_ids
		 *
		 * @return  array
		 * @author  Alberto Ruggiero
		 */
		public function add_screen_ids( $screen_ids ) {

			$screen_ids[] = 'edit-' . $this->post_type;
			$screen_ids[] = $this->post_type;

			return $screen_ids;

		}

		/**
		 * Filters views in custom post type
		 *
		 * @since   1.0.0
		 *
		 * @param   $views
		 *
		 * @return  array
		 * @author  Alberto Ruggiero
		 */
		public function set_views( $views ) {

			if ( isset( $views['mine'] ) ) {

				unset( $views['mine'] );

			}

			return $views;

		}

		/**
		 * Save meta box process
		 *
		 * @since   1.0.0
		 *
		 * @param   $post_id
		 * @param   $post
		 *
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function save_post( $post_id, $post ) {

			// $post_id and $post are required
			if ( empty( $post_id ) || empty( $post ) || $this->saved_meta_box ) {
				return;
			}

			// Dont' save meta boxes for revisions or autosaves
			if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
				return;
			}

			// Check the nonce
			if ( empty( $_POST['ywrfd_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ywrfd_meta_nonce'], 'ywrfd_save_data' ) ) {
				return;
			}

			// Check the post being saved == the $post_id to prevent triggering this call for other save_post events
			if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
				return;
			}

			// Check user has permission to edit
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// We need this save event to run once to avoid potential endless loops. This would have been perfect:
			// remove_action( current_filter(), __METHOD__ );
			// But cannot be used due to https://github.com/woothemes/woocommerce/issues/6485
			// When that is patched in core we cna use the above. For now:
			$this->saved_meta_box = true;

			do_action( 'ywrfd_process_meta', $post_id );

		}

		/**
		 * Add a metabox on product page
		 *
		 * @since   1.0.0
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function add_metabox() {
			add_meta_box( 'ywrfd-metabox', __( 'Discount Options', 'yith-woocommerce-review-for-discounts' ), array( $this, 'output_metabox' ), $this->post_type, 'normal', 'default' );
		}

		/**
		 * Output Meta Box
		 *
		 * The function to be called to output the meta box in YWRFD discount details page.
		 *
		 * @since   1.0.0
		 *
		 * @param   $post
		 *
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function output_metabox( $post ) {

			wp_nonce_field( 'ywrfd_save_data', 'ywrfd_meta_nonce' );

			$coupon_types = wc_get_coupon_types();

			if ( defined( 'YITH_FUNDS_PREMIUM' ) && YITH_FUNDS_PREMIUM ) {
				$coupon_types['funds'] = __( 'Add Funds', 'yith-woocommerce-review-for-discounts' );
			}

			?>
            <div class="panel woocommerce_options_panel">
                <div class="options_group">
					<?php

					woocommerce_wp_textarea_input(
						array(
							'id'          => 'ywrfd_description',
							'label'       => 'Coupon description',
							'description' => __( 'Description of the coupon', 'yith-woocommerce-review-for-discounts' ),
							'desc_tip'    => true
						)
					);

					woocommerce_wp_select(
						array(
							'id'          => 'ywrfd_trigger',
							'label'       => __( 'Triggering event', 'yith-woocommerce-review-for-discounts' ),
							'options'     => array(
								'review'   => __( 'Single Review', 'yith-woocommerce-review-for-discounts' ),
								'multiple' => __( 'Multiple Reviews', 'yith-woocommerce-review-for-discounts' ),
							),
							'description' => __( 'When the coupon will be sent', 'yith-woocommerce-review-for-discounts' ),
							'desc_tip'    => true
						)
					);

					?>
                </div>
                <div class="options_group ywrfd_review">
                    <p class="form-field">
                        <i><?php _e( 'Leaving the following fields empty, users will receive a coupon for the review of any product', 'yith-woocommerce-review-for-discounts' ); ?></i>
                    </p>

                    <p class="form-field">
                        <label><?php _e( 'Triggering products', 'yith-woocommerce-review-for-discounts' ); ?></label>
						<?php

						$trigger_product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $post->ID, 'ywrfd_trigger_product_ids', true ) ) ) );
						$trigger_json_ids    = array();

						foreach ( $trigger_product_ids as $trigger_product_id ) {
							$trigger_product = wc_get_product( $trigger_product_id );
							if ( is_object( $trigger_product ) ) {

								$trigger_json_ids[ $trigger_product_id ] = wp_kses_post( $trigger_product->get_formatted_name() );

							}
						}

						$select_args = array(
							'class'            => 'wc-product-search',
							'id'               => 'ywrfd_trigger_product_ids',
							'name'             => 'ywrfd_trigger_product_ids',
							'data-placeholder' => __( 'Search for a product...', 'yith-woocommerce-review-for-discounts' ),
							'data-allow_clear' => false,
							'data-selected'    => $trigger_json_ids,
							'data-multiple'    => true,
							'data-action'      => 'woocommerce_json_search_products_and_variations',
							'value'            => implode( ',', array_keys( $trigger_json_ids ) ),
							'style'            => 'width: 50%'
						);

						yit_add_select2_fields( $select_args );

						echo wc_help_tip( __( 'Products that will give a coupon if reviewed', 'yith-woocommerce-review-for-discounts' ) )
						?>

                    </p>

                    <p class="form-field">
                        <label for="ywrfd_trigger_product_categories"><?php _e( 'Triggering product categories', 'yith-woocommerce-review-for-discounts' ); ?></label>
                        <select id="ywrfd_trigger_product_categories" name="ywrfd_trigger_product_categories[]" style="width: 50%;" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any category', 'yith-woocommerce-review-for-discounts' ); ?>">
							<?php
							$trigger_category_ids = (array) get_post_meta( $post->ID, 'ywrfd_trigger_product_categories', true );
							$trigger_categories   = get_terms( array(
								                                   'taxonomy'   => 'product_cat',
								                                   'orderby'    => 'name',
								                                   'hide_empty' => false,
							                                   ) );

							if ( $trigger_categories ) {
								foreach ( $trigger_categories as $trigger_cat ) {
									echo '<option value="' . esc_attr( $trigger_cat->term_id ) . '" ' . selected( in_array( $trigger_cat->term_id, $trigger_category_ids ), true, false ) . '>' . esc_html( $trigger_cat->name ) . '</option>';
								}
							}
							?>
                        </select>

						<?php

						echo wc_help_tip( __( 'Reviewing products of the selected categories will send a coupon.', 'yith-woocommerce-review-for-discounts' ) )

						?>

                    </p>
                </div>
                <div class="options_group ywrfd_multiple">

					<?php

					woocommerce_wp_text_input(
						array(
							'id'                => 'ywrfd_trigger_threshold',
							'label'             => __( 'Requested quantity', 'yith-woocommerce-review-for-discounts' ),
							'description'       => __( 'How many reviews have to be written to receive the coupon.', 'yith-woocommerce-review-for-discounts' ),
							'type'              => 'number',
							'desc_tip'          => true,
							'custom_attributes' => array(
								'min'      => 1,
								'required' => 'required'
							),
							'class'             => 'ywrfd-review-number ywrfd-target',
						)
					);

					woocommerce_wp_checkbox(
						array(
							'id'          => 'ywrfd_trigger_enable_notify',
							'label'       => __( 'Send notification approaching requested quantity', 'yith-woocommerce-review-for-discounts' ),
							'description' => __( 'Send an email from a certain amount of review to get to the requested quantity to encourage users make other reviews', 'yith-woocommerce-review-for-discounts' )
						)
					);

					woocommerce_wp_text_input(
						array(
							'id'                => 'ywrfd_trigger_threshold_notify',
							'label'             => __( 'Initial quantity', 'yith-woocommerce-review-for-discounts' ),
							'description'       => __( 'Set how many reviews are needed to start sending the email notifications.', 'yith-woocommerce-review-for-discounts' ),
							'type'              => 'number',
							'desc_tip'          => true,
							'custom_attributes' => array(
								'min' => 1,
								'max' => 1,
							),
							'class'             => 'ywrfd-review-number ywrfd-notify',
						)
					);

					?>

                </div>
                <div class="options_group">

					<?php

					// Type
					woocommerce_wp_select(
						array(
							'id'      => 'ywrfd_discount_type',
							'label'   => __( 'Discount type', 'yith-woocommerce-review-for-discounts' ),
							'options' => $coupon_types
						)
					);

					// Amount
					woocommerce_wp_text_input(
						array(
							'id'                => 'ywrfd_coupon_amount',
							'label'             => __( 'Coupon amount', 'yith-woocommerce-review-for-discounts' ),
							'placeholder'       => wc_format_localized_price( 0 ),
							'description'       => __( 'Value of the coupon.', 'yith-woocommerce-review-for-discounts' ),
							'data_type'         => 'price',
							'desc_tip'          => true,
							'custom_attributes' => array(
								'required' => true,
							)
						)
					);

					// Expiry date
					woocommerce_wp_text_input(
						array(
							'id'                => 'ywrfd_expiry_days',
							'label'             => __( 'Validity days', 'yith-woocommerce-review-for-discounts' ),
							'description'       => __( 'Set for how many days the coupon will be valid since the creation. Set to "0" for no expiration.', 'yith-woocommerce-review-for-discounts' ),
							'type'              => 'number',
							'desc_tip'          => true,
							'custom_attributes' => array(
								'min' => 0,
							)
						)
					);

					// Free Shipping
					woocommerce_wp_checkbox(
						array(
							'id'          => 'ywrfd_free_shipping',
							'label'       => __( 'Allow free shipping', 'yith-woocommerce-review-for-discounts' ),
							'description' => sprintf( __( 'Check this box if the coupon grants free shipping. The <a href="%s">free shipping method</a> must be enabled and set to require "a valid free shipping coupon" (see the "Free Shipping Requires" setting).', 'yith-woocommerce-review-for-discounts' ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=WC_Shipping_Free_Shipping' ) )
						)
					); ?>

                </div>
                <div class="options_group coupon_options">

					<?php

					// Individual use
					woocommerce_wp_checkbox(
						array(
							'id'          => 'ywrfd_individual_use',
							'label'       => __( 'Single use only', 'yith-woocommerce-review-for-discounts' ),
							'description' => __( 'Check this box if the coupon cannot be used in conjunction with other coupons.', 'yith-woocommerce-review-for-discounts' )
						)
					);

					// minimum spend
					woocommerce_wp_text_input(
						array(
							'id'          => 'ywrfd_minimum_amount',
							'label'       => __( 'Minimum amount to spend', 'yith-woocommerce-review-for-discounts' ),
							'placeholder' => __( 'No minimum', 'yith-woocommerce-review-for-discounts' ),
							'description' => __( 'This field allows you to set the minimum subtotal needed to use the coupon.', 'yith-woocommerce-review-for-discounts' ),
							'data_type'   => 'price',
							'desc_tip'    => true
						)
					);

					// maximum spend
					woocommerce_wp_text_input(
						array(
							'id'          => 'ywrfd_maximum_amount',
							'label'       => __( 'Maximum amount to spend', 'yith-woocommerce-review-for-discounts' ),
							'placeholder' => __( 'No maximum', 'yith-woocommerce-review-for-discounts' ),
							'description' => __( 'This field allows you to set the maximum subtotal allowed when using the coupon.', 'yith-woocommerce-review-for-discounts' ),
							'data_type'   => 'price',
							'desc_tip'    => true
						)
					);

					?>
                    <p class="form-field">
                        <label><?php _e( 'Products', 'yith-woocommerce-review-for-discounts' ); ?></label>
						<?php

						$product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $post->ID, 'ywrfd_product_ids', true ) ) ) );
						$json_ids    = array();

						foreach ( $product_ids as $product_id ) {
							$product = wc_get_product( $product_id );
							if ( is_object( $product ) ) {
								$json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
							}
						}

						$select_args = array(
							'class'            => 'wc-product-search',
							'id'               => 'ywrfd_product_ids',
							'name'             => 'ywrfd_product_ids',
							'data-placeholder' => __( 'Search for a product...', 'yith-woocommerce-review-for-discounts' ),
							'data-allow_clear' => false,
							'data-selected'    => $json_ids,
							'data-multiple'    => true,
							'data-action'      => 'woocommerce_json_search_products_and_variations',
							'value'            => implode( ',', array_keys( $json_ids ) ),
							'style'            => 'width: 50%'
						);

						yit_add_select2_fields( $select_args );

						echo wc_help_tip( __( 'Products which need to be in the cart to use this coupon; if "Product Discounts" is selected, set which products will be discounted.', 'yith-woocommerce-review-for-discounts' ) )

						?>

                    </p>

                    <p class="form-field">
                        <label for="product_categories"><?php _e( 'Product categories', 'yith-woocommerce-review-for-discounts' ); ?></label>
                        <select id="ywrfd_product_categories" name="ywrfd_product_categories[]" style="width: 50%;" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any category', 'yith-woocommerce-review-for-discounts' ); ?>">
							<?php
							$category_ids = (array) get_post_meta( $post->ID, 'ywrfd_product_categories', true );
							$categories   = get_terms( array(
								                           'taxonomy'   => 'product_cat',
								                           'orderby'    => 'name',
								                           'hide_empty' => false,
							                           ) );

							if ( $categories ) {
								foreach ( $categories as $cat ) {
									echo '<option value="' . esc_attr( $cat->term_id ) . '" ' . selected( in_array( $cat->term_id, $category_ids ), true, false ) . '>' . esc_html( $cat->name ) . '</option>';
								}
							}
							?>
                        </select>
						<?php

						echo wc_help_tip( __( 'A product must be in this category for the coupon to remain valid; if "Product Discounts" is selected, the products of these categories will be discounted.', 'yith-woocommerce-review-for-discounts' ) )

						?>
                    </p>

                </div>
                <div class="clear"></div>
            </div>

			<?php

		}

		/**
		 * Saves Meta Box content
		 *
		 * @since   1.0.0
		 *
		 * @param   $post_id
		 *
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function save_metabox( $post_id ) {

			$trigger_product_ids = array();
			$product_ids         = array();

			if ( isset( $_POST['ywrfd_trigger_product_ids'] ) ) {

				$trigger_product_ids = is_array( $_POST['ywrfd_trigger_product_ids'] ) ? $_POST['ywrfd_trigger_product_ids'] : explode( ',', $_POST['ywrfd_trigger_product_ids'] );

			}

			if ( isset( $_POST['ywrfd_product_ids'] ) ) {

				$product_ids = is_array( $_POST['ywrfd_product_ids'] ) ? $_POST['ywrfd_product_ids'] : explode( ',', $_POST['ywrfd_product_ids'] );

			}

			$description                = wc_clean( $_POST['ywrfd_description'] );
			$trigger                    = wc_clean( $_POST['ywrfd_trigger'] );
			$trigger_product_ids        = implode( ',', array_filter( array_map( 'intval', $trigger_product_ids ) ) );
			$trigger_product_categories = isset( $_POST['ywrfd_trigger_product_categories'] ) ? array_map( 'intval', $_POST['ywrfd_trigger_product_categories'] ) : array();
			$trigger_threshold          = wc_clean( $_POST['ywrfd_trigger_threshold'] );
			$trigger_enable_notify      = isset( $_POST['ywrfd_trigger_enable_notify'] ) ? 'yes' : 'no';
			$trigger_threshold_notify   = wc_clean( $_POST['ywrfd_trigger_threshold_notify'] );
			$type                       = wc_clean( $_POST['ywrfd_discount_type'] );
			$amount                     = wc_format_decimal( $_POST['ywrfd_coupon_amount'] );
			$expiry_date                = isset( $_POST['ywrfd_expiry_days'] ) ? $_POST['ywrfd_expiry_days'] : 0;
			$free_shipping              = isset( $_POST['ywrfd_free_shipping'] ) ? 'yes' : 'no';
			$individual_use             = isset( $_POST['ywrfd_individual_use'] ) ? 'yes' : 'no';
			$minimum_amount             = wc_format_decimal( $_POST['ywrfd_minimum_amount'] );
			$maximum_amount             = wc_format_decimal( $_POST['ywrfd_maximum_amount'] );
			$product_ids                = implode( ',', array_filter( array_map( 'intval', $product_ids ) ) );
			$product_categories         = isset( $_POST['ywrfd_product_categories'] ) ? array_map( 'intval', $_POST['ywrfd_product_categories'] ) : array();
			$vendor_id                  = 0;

			if ( YITH_WRFD()->is_multivendor_active() ) {

				$vendor    = yith_get_vendor( 'current', 'user' );
				$vendor_id = $vendor->id;

			}

			update_post_meta( $post_id, 'ywrfd_description', $description );
			update_post_meta( $post_id, 'ywrfd_trigger', $trigger );
			update_post_meta( $post_id, 'ywrfd_trigger_product_ids', $trigger_product_ids );
			update_post_meta( $post_id, 'ywrfd_trigger_product_categories', $trigger_product_categories );
			update_post_meta( $post_id, 'ywrfd_trigger_threshold', $trigger_threshold );
			update_post_meta( $post_id, 'ywrfd_trigger_enable_notify', $trigger_enable_notify );
			update_post_meta( $post_id, 'ywrfd_trigger_threshold_notify', $trigger_threshold_notify );
			update_post_meta( $post_id, 'ywrfd_discount_type', $type );
			update_post_meta( $post_id, 'ywrfd_coupon_amount', $amount );
			update_post_meta( $post_id, 'ywrfd_expiry_days', $expiry_date );
			update_post_meta( $post_id, 'ywrfd_free_shipping', $free_shipping );
			update_post_meta( $post_id, 'ywrfd_individual_use', $individual_use );
			update_post_meta( $post_id, 'ywrfd_minimum_amount', $minimum_amount );
			update_post_meta( $post_id, 'ywrfd_maximum_amount', $maximum_amount );
			update_post_meta( $post_id, 'ywrfd_product_ids', $product_ids );
			update_post_meta( $post_id, 'ywrfd_product_categories', $product_categories );
			update_post_meta( $post_id, 'ywrfd_vendor_id', $vendor_id );

		}

	}

	/**
	 * Unique access to instance of YWRFD_Discounts_Helper class
	 *
	 * @return \YWRFD_Discounts_Helper
	 */
	function YWRFD_Discounts_Helper() {

		return YWRFD_Discounts_Helper::get_instance();

	}

	new YWRFD_Discounts_Helper();

}