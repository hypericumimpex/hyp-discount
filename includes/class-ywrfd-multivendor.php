<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'YWRFD_MultiVendor' ) ) {

	/**
	 * Implements compatibility with YITH WooCommerce Multi Vendor
	 *
	 * @class   YWRFD_MultiVendor
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 *
	 */
	class YWRFD_MultiVendor {

		/**
		 * Single instance of the class
		 *
		 * @var \YWRFD_MultiVendor
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \YWRFD_MultiVendor
		 * @since 1.0.0
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {

				self::$instance = new self( $_REQUEST );

			}

			return self::$instance;
		}

		/**
		 * @var YITH_Vendor current vendor
		 */
		protected $vendor;

		/**
		 * @var string YITH WooCommerce Review For Discounts vendor panel page
		 */
		protected $_panel_page = 'yith_vendor_rfd_settings';

		/**
		 * @var $post_type string post type name
		 */
		protected $post_type = 'ywrfd-discount';

		/**
		 * Panel object
		 *
		 * @var     /Yit_Plugin_Panel object
		 * @since   1.0.0
		 * @see     plugin-fw/lib/yit-plugin-panel.php
		 */
		protected $_vendor_panel = null;

		/**
		 * Constructor
		 *
		 * @since   1.0.0
		 * @return  mixed
		 * @author  Alberto Ruggiero
		 */
		public function __construct() {

			$this->vendor = yith_get_vendor( 'current', 'user' );

			add_action( 'admin_notices', array( $this, 'set_notices' ) );
			add_filter( 'ywrfd_multivendor_rfd_active_notice', '__return_false' );

			if ( $this->vendor->is_valid() && $this->vendor->has_limited_access() && $this->vendors_coupon_reviews_active() && $this->check_ywrfd_vendor_enabled() ) {

				add_action( 'admin_menu', array( $this, 'add_ywrfd_vendor' ), 5 );

			}

			if ( $this->vendor->is_super_user() ) {

				add_filter( "manage_{$this->post_type}_posts_columns", array( $this, 'set_custom_columns' ) );
				add_action( "manage_{$this->post_type}_posts_custom_column", array( $this, 'render_custom_columns' ), 10, 2 );

				add_filter( "manage_shop_coupon_posts_columns", array( $this, 'set_custom_columns' ) );
				add_action( "manage_shop_coupon_posts_custom_column", array( $this, 'render_custom_columns' ), 10, 2 );

			}

			add_filter( 'ywrfd_get_vendor_name', array( $this, 'get_vendor_name' ), 10, 2 );
			add_filter( 'ywrfd_set_vendor_id', array( $this, 'set_vendor_id' ), 10, 3 );
			add_filter( 'ywrfd_set_coupon_author', array( $this, 'set_vendor_owner' ), 10, 2 );
			add_filter( 'ywrfd_get_vendors_multiple_reviews_discounts', array( $this, 'get_vendors_multiple_reviews_discounts' ), 10, 2 );
			add_filter( 'ywrfd_get_vendors_multiple_reviews_notify', array( $this, 'get_vendors_multiple_reviews_notify' ), 10, 3 );
			add_filter( 'ywrfd_vendor_review_count', array( $this, 'vendor_review_count' ), 10, 3 );

		}

		/**
		 * Add Review For Discounts options panel for vendors
		 *
		 * @since   1.0.0
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function add_ywrfd_vendor() {

			if ( ! empty( $this->_vendor_panel ) ) {
				return;
			}

			$tabs = array(
				'vendor' => __( 'Settings', 'yith-woocommerce-review-for-discounts' ),
				'howto'  => __( 'How To', 'yith-woocommerce-review-for-discounts' )
			);

			$args = array(
				'create_menu_page' => false,
				'parent_slug'      => '',
				'page_title'       => __( 'Review For Discounts', 'yith-woocommerce-review-for-discounts' ),
				'menu_title'       => 'Review For Discounts',
				'capability'       => 'manage_vendor_store',
				'parent'           => '',
				'parent_page'      => '',
				'page'             => $this->_panel_page,
				'admin-tabs'       => $tabs,
				'options-path'     => YWRFD_DIR . 'plugin-options/vendor',
				'icon_url'         => 'dashicons-admin-settings',
				'position'         => 99
			);

			$this->_vendor_panel = new YIT_Plugin_Panel_WooCommerce( $args );

		}

		/**
		 * Advise if coupon and/or review management are deactivated
		 *
		 * @since   1.0.0
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function set_notices() {

			if ( $this->check_ywrfd_vendor_enabled() ) {

				$coupon   = get_option( 'yith_wpv_vendors_option_coupon_management' );
				$review   = get_option( 'yith_wpv_vendors_option_review_management' );
				$messages = array();

				if ( $coupon != 'yes' ) {

					$messages[] = __( 'Coupon management must be enabled to make YITH WooCommerce Review for Discounts work correctly for vendors.', 'yith-woocommerce-review-for-discounts' );

				}

				if ( $review != 'yes' ) {

					$messages[] = __( 'Review management must be enabled to make YITH WooCommerce Review for Discounts work correctly for vendors.', 'yith-woocommerce-review-for-discounts' );

				}

				if ( ! empty( $messages ) ) {

					?>
					<div class="update-nag">
						<ul>
							<?php foreach ( $messages as $message ): ?>

								<li><?php echo $message; ?></li>

							<?php endforeach; ?>
						</ul>
					</div>
					<?php

				}

			}

		}

		/**
		 * Check if there is at least a coupon event allowed
		 *
		 * @since   1.0.0
		 * @return  boolean
		 * @author  Alberto Ruggiero
		 */
		public function check_ywrfd_vendor_enabled() {

			if ( get_option( 'yith_wpv_vendors_option_review_discounts_management' ) == 'yes' ) {
				return true;
			}

			return false;

		}

		/**
		 * Get vendor's name
		 *
		 * @since   1.0.0
		 *
		 * @param   $value
		 * @param   $vendor_id
		 *
		 * @return  string
		 * @author  Alberto Ruggiero
		 */
		public function get_vendor_name( $value, $vendor_id ) {

			$vendor = yith_get_vendor( $vendor_id, 'vendor' );

			if ( $vendor->is_valid() ) {

				$value = $vendor->term->name;

			}

			return $value;

		}

		/**
		 * Get vendor's name
		 *
		 * @since   1.0.0
		 *
		 * @param   $value
		 * @param   $vendor_id
		 * @param   $label
		 *
		 * @return  string
		 * @author  Alberto Ruggiero
		 */
		public function set_vendor_id( $value, $vendor_id, $label = false ) {

			if ( $vendor_id != '' ) {

				$value = '_';

				if ( $label ) {
					$value .= 'vendor_';
				}

				$value .= $vendor_id;

			}

			return $value;

		}

		/**
		 * Set coupon owner
		 *
		 * @since   1.0.0
		 *
		 * @param   $value
		 * @param   $vendor_id
		 *
		 * @return  string
		 * @author  Alberto Ruggiero
		 */
		public function set_vendor_owner( $value, $vendor_id ) {

			$vendor = yith_get_vendor( $vendor_id, 'vendor' );

			if ( $vendor->is_valid() ) {

				$value = $vendor->get_owner();

			}

			return $value;

		}

		/**
		 * Check if coupon management of YITH WooCommerce Multi Vendor is active
		 *
		 * @since   1.0.0
		 * @return  bool
		 * @author  Alberto Ruggiero
		 */
		public function vendors_coupon_reviews_active() {

			$coupon = get_option( 'yith_wpv_vendors_option_coupon_management' );
			$review = get_option( 'yith_wpv_vendors_option_review_management' );

			return ( $coupon == 'yes' && $review == 'yes' ? true : false );

		}

		/* === Discounts for multiple reviews by vendor === */

		/**
		 * Count approved reviews by vendor
		 *
		 * @since   1.0.0
		 *
		 * @param   $author_email
		 *
		 * @return  array
		 * @author  Alberto Ruggiero
		 */
		public function count_approved_reviews_by_vendor( $author_email ) {

			$vendors_count = array();

			if ( YITH_WRFD()->is_advanced_reviews_active() ) {

				$args = array(
					'post_type'      => 'ywar_reviews',
					'post_status'    => 'publish',
					'posts_per_page' => - 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'key'     => '_ywar_review_author_email',
							'value'   => $author_email,
							'compare' => '='
						),
						array(
							'key'     => '_ywar_approved',
							'value'   => '1',
							'compare' => '='
						)
					)
				);

				$query = new WP_Query( $args );

				if ( $query->have_posts() ) {

					while ( $query->have_posts() ) {

						$query->the_post();

						$post_id = get_post_meta( $query->post->ID, '_ywar_product_id', true );

						$vendor = yith_get_vendor( $post_id, 'product' );

						if ( $vendor->is_valid() ) {

							if ( isset( $vendors_count[ $vendor->id ] ) ) {

								$vendors_count[ $vendor->id ] += 1;

							} else {

								$vendors_count[ $vendor->id ] = 1;

							}

						}

					}

				}

				wp_reset_query();
				wp_reset_postdata();

			} else {

				$comment_args = array(
					'author_email' => $author_email,
					'status'       => 'approve',
				);

				$approved_comments = new WP_Comment_Query();

				$comments = $approved_comments->query( $comment_args );

				if ( $comments ) {

					foreach ( $comments as $comment ) {

						$vendor = yith_get_vendor( $comment->comment_post_ID, 'product' );

						if ( $vendor->is_valid() ) {

							if ( isset( $vendors_count[ $vendor->id ] ) ) {

								$vendors_count[ $vendor->id ] += 1;

							} else {

								$vendors_count[ $vendor->id ] = 1;

							}

						}

					}

				}

			}

			return $vendors_count;

		}

		/**
		 * Discounts for multiple reviews by vendor
		 *
		 * @since   1.0.0
		 *
		 * @param   $value
		 * @param   $author_email
		 *
		 * @return  array
		 * @author  Alberto Ruggiero
		 */
		public function get_vendors_multiple_reviews_discounts( $value, $author_email ) {

			$counts = $this->count_approved_reviews_by_vendor( $author_email );

			if ( ! empty( $counts ) ) {

				foreach ( $counts as $vendor_id => $count ) {

					$multiple_args = array(
						'post_type'      => 'ywrfd-discount',
						'post_status'    => 'publish',
						'posts_per_page' => - 1,
						'meta_query'     => array(
							'relation' => 'AND',
							array(
								'key'   => 'ywrfd_trigger',
								'value' => 'multiple',
							),
							array(
								'key'   => 'ywrfd_vendor_id',
								'value' => $vendor_id,
							),
							array(
								'key'   => 'ywrfd_trigger_threshold',
								'value' => $count,
							),
						)
					);

					$multiple_query = new WP_Query( $multiple_args );

					if ( $multiple_query->have_posts() ) {

						while ( $multiple_query->have_posts() ) {

							$multiple_query->the_post();

							$discount = new YWRFD_Discounts( $multiple_query->post->ID );

							$value[] = $discount;

						}

					}

					wp_reset_query();
					wp_reset_postdata();

				}

			}

			return $value;

		}


		public function get_vendors_multiple_reviews_notify( $value, $author_email, $product_id ) {

			$counts = $this->count_approved_reviews_by_vendor( $author_email );

			$vendor = yith_get_vendor( $product_id, 'product' );

			if ( ! empty( $counts ) ) {

				$multiple_args = array(
					'post_type'      => 'ywrfd-discount',
					'post_status'    => 'publish',
					'posts_per_page' => - 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'key'   => 'ywrfd_trigger',
							'value' => 'multiple',
						),
						array(
							'key'   => 'ywrfd_trigger_enable_notify',
							'value' => 'yes',
						),
						array(
							'key'     => 'ywrfd_trigger_threshold_notify',
							'value'   => $counts[ $vendor->id ],
							'compare' => '<=',
							'type'    => 'NUMERIC'
						),
						array(
							'key'     => 'ywrfd_trigger_threshold',
							'value'   => $counts[ $vendor->id ],
							'compare' => '>',
							'type'    => 'NUMERIC'
						),
						array(
							'key'   => 'ywrfd_vendor_id',
							'value' => $vendor->id,
						),
					)
				);

				$multiple_query = new WP_Query( $multiple_args );

				if ( $multiple_query->have_posts() ) {

					while ( $multiple_query->have_posts() ) {

						$multiple_query->the_post();

						$discount = new YWRFD_Discounts( $multiple_query->post->ID );

						$value[] = $discount;

					}

				}

				wp_reset_query();
				wp_reset_postdata();


			}

			return $value;

		}

		public function vendor_review_count( $value, $author_email, $product_id ) {

			$counts = $this->count_approved_reviews_by_vendor( $author_email );
			$vendor = yith_get_vendor( $product_id, 'product' );

			return $counts[ $vendor->id ];

		}

		/* === Custom Post Type additional Columns === */

		/**
		 * Set custom columns
		 *
		 * @since   1.0.0
		 *
		 * @param   $columns
		 *
		 * @return  array
		 * @author  Alberto Ruggiero
		 */
		public function set_custom_columns( $columns ) {

			$columns['vendor'] = __( 'Vendor', 'yith-woocommerce-review-for-discounts' );

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

			if ( $column == 'vendor' ) {

				if ( $_GET['post_type'] == 'shop_coupon' ) {

					$post_meta = 'vendor_id';

				} else {

					$post_meta = 'ywrfd_vendor_id';

				}

				$vendor_id = get_post_meta( $post_id, $post_meta, true );
				$vendor    = yith_get_vendor( $vendor_id, 'vendor' );

				if ( $vendor->is_valid() ) {

					$query_args = array(
						'post_type'        => $_GET['post_type'],
						'yith_shop_vendor' => $vendor->term->slug,
					);

					$url = esc_url( add_query_arg( $query_args, admin_url( 'edit.php' ) ) );

					?>

					<a href="<?php echo $url; ?>"><?php echo $vendor->term->name; ?></a>

					<?php

				} else {

					echo "-";
				}


			}

		}

	}

	/**
	 * Unique access to instance of YWRFD_MultiVendor class
	 *
	 * @return \YWRFD_MultiVendor
	 */
	function YWRFD_MultiVendor() {

		return YWRFD_MultiVendor::get_instance();

	}

	YWRFD_MultiVendor();

}