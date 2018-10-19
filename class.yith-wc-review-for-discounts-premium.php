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
	exit;
} // Exit if accessed directly

/**
 * Main class
 *
 * @class   YITH_WC_Review_For_Discounts_Premium
 * @package Yithemes
 * @since   1.0.0
 * @author  Your Inspiration Themes
 */

if ( ! class_exists( 'YITH_WC_Review_For_Discounts_Premium' ) ) {

	class YITH_WC_Review_For_Discounts_Premium extends YITH_WC_Review_For_Discounts {

		/**
		 * @var array
		 */
		protected $_email_types = array();

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WC_Review_For_Discounts_Premium
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

			parent::__construct();

			// register plugin to licence/update system
			add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
			add_action( 'admin_init', array( $this, 'register_plugin_for_updates' ) );

			$this->includes_premium();

			if ( is_admin() ) {

				add_filter( 'ywrfd_admin_scripts_filter', array( $this, 'admin_scripts_filter' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_premium' ) );

			}

			if ( get_option( 'ywrfd_enable_plugin' ) == 'yes' ) {

				add_action( 'init', array( $this, 'init_multivendor_compatibility' ), 20 );
				add_action( 'comment_post', array( $this, 'on_comment_written' ), 10, 1 );
				add_filter( 'yith_wcet_email_template_types', array( $this, 'add_yith_wcet_template' ) );
				add_filter( 'ywrfd_get_discounts', array( $this, 'get_discounts' ), 10, 3 );
				add_action( 'ywrfd_notification_sending', array( $this, 'notification_sending' ), 10, 4 );

				if ( get_option( 'ywrfd_coupon_purge' ) == 'yes' ) {

					add_action( 'ywrfd_trash_coupon_cron', array( $this, 'trash_expired_coupons' ) );

				}

				if ( $this->is_advanced_reviews_active() ) {

					add_action( 'ywar_review_approve_status_changed', array( $this, 'on_comment_approvation_ywar' ), 10, 2 );
					remove_action( 'comment_unapproved_to_approved', array( $this, 'on_comment_approvation' ) );
					add_action( 'admin_notices', array( $this, 'set_notices_ywar' ) );
					remove_action( 'admin_notices', array( $this, 'set_notices' ) );

				}

			}

		}

		/* === GLOBAL FUNCTIONS === */

		/**
		 * Files inclusion
		 *
		 * @since   1.0.0
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		private function includes_premium() {

			include_once( 'includes/class-ywrfd-mandrill.php' );
			include_once( 'includes/class-ywrfd-discounts-helper.php' );

			if ( defined( 'YITH_FUNDS_PREMIUM' ) && YITH_FUNDS_PREMIUM ) {
				include_once( 'includes/class-ywrfd-funds-emails.php' );
			}

			if ( is_admin() ) {

				include_once( 'includes/class-ywrfd-ajax-premium.php' );
				include_once( 'templates/admin/class-ywrfd-custom-vendor-panel.php' );
				include_once( 'templates/admin/class-ywrfd-custom-coupon-purge.php' );

			}

		}

		/**
		 * On comment sending
		 *
		 * @since   1.0.0
		 *
		 * @param   $comment_id
		 *
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function on_comment_written( $comment_id ) {

			$comment = get_comment( $comment_id );

			if ( ! $this->user_already_commented( $comment ) && ! $this->is_moderation_required() ) {

				$this->get_coupons( $comment );

			}

		}

		/**
		 * Count approved Reviews
		 *
		 * @since   1.0.0
		 *
		 * @param   $author_email
		 *
		 * @return  int
		 * @author  Alberto Ruggiero
		 */
		public function count_reviews( $author_email ) {

			$approved = $this->is_moderation_required();

			if ( $this->is_advanced_reviews_active() ) {

				if ( $approved ) {

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

				} else {

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
						)
					);

				}

				$query = new WP_Query( $args );

				$count = $query->post_count;

				wp_reset_query();
				wp_reset_postdata();

			} else {

				if ( $approved ) {

					$args = array(
						'author_email' => $author_email,
						'status'       => 'approve',
						'count'        => true,
					);

				} else {

					$args = array(
						'author_email' => $author_email,
						'status'       => 'all',
						'count'        => true,
					);

				}

				$comments = new WP_Comment_Query();

				$count = $comments->query( $args );

			}

			return $count;

		}

		/**
		 * Get saved discounts
		 *
		 * @since   1.0.0
		 *
		 * @param   $value
		 * @param   $author_email
		 * @param   $product_id
		 *
		 * @return  array
		 * @author  Alberto Ruggiero
		 */
		public function get_discounts( $value, $product_id, $author_email ) {

			$value = array();

			//get coupon for single review
			$single_args = array(
				'post_type'      => 'ywrfd-discount',
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'   => 'ywrfd_trigger',
						'value' => 'review',
					)
				)
			);

			$single_query = new WP_Query( $single_args );

			if ( $single_query->have_posts() ) {

				while ( $single_query->have_posts() ) {

					$valid = false;

					$single_query->the_post();

					$discount = new YWRFD_Discounts( $single_query->post->ID );

					if ( in_array( $product_id, $discount->trigger_product_ids ) ) {

						$valid = true;

					} else {

						$categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

						if ( $categories ) {

							foreach ( $categories as $category_id ) {

								if ( in_array( $category_id, $discount->trigger_product_categories ) ) {

									$valid = true;

								}

							}

						}

					}

					if ( empty( $discount->trigger_product_ids ) && empty( $discount->trigger_product_categories ) ) {

						$valid = true;

					}

					if ( $valid ) {
						$value[] = $discount;
					}

				}

			}

			wp_reset_query();
			wp_reset_postdata();

			//get coupon for multiple review
			$count = $this->count_reviews( $author_email );

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
						'value' => '0',
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

			return apply_filters( 'ywrfd_get_vendors_multiple_reviews_discounts', $value, $author_email );

		}

		/**
		 * Count reviews and sent notification if near to some threshold
		 *
		 * @since   1.0.0
		 *
		 * @param   $user_id
		 * @param   $user_email
		 * @param   $nickname
		 * @param   $product_id
		 *
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function notification_sending( $user_id, $user_email, $nickname, $product_id ) {

			$count     = $this->count_reviews( $user_email );
			$discounts = array();
			$args      = array(
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
						'value'   => $count,
						'compare' => '<=',
						'type'    => 'NUMERIC'
					),
					array(
						'key'     => 'ywrfd_trigger_threshold',
						'value'   => $count,
						'compare' => '>',
						'type'    => 'NUMERIC'
					),
					array(
						'key'   => 'ywrfd_vendor_id',
						'value' => '0',
					),
				)
			);

			$query = new WP_Query( $args );

			if ( $query->have_posts() ) {

				while ( $query->have_posts() ) {

					$query->the_post();

					$discounts[] = new YWRFD_Discounts( $query->post->ID );

				}

			}

			wp_reset_query();
			wp_reset_postdata();

			$discounts = apply_filters( 'ywrfd_get_vendors_multiple_reviews_notify', $discounts, $user_email, $product_id );

			if ( ! empty( $discounts ) ) {

				foreach ( $discounts as $discount ) {

					$count = apply_filters( 'ywrfd_vendor_review_count', $count, $user_email, $product_id );

					$remaining_reviews = $discount->trigger_threshold - $count;

					$email_result = YWRFD_Emails()->prepare_coupon_mail( $user_id, '', 'notify', array( 'nickname' => $nickname, 'remaining_reviews' => $remaining_reviews ), $user_email, $discount->vendor_id );

				}

			}

		}

		/* === ADMIN ONLY FUNCTIONS === */

		/**
		 * Add premium strings for localization
		 *
		 * @since   1.0.0
		 *
		 * @param   $strings
		 *
		 * @return  array
		 * @author  Alberto Ruggiero
		 */
		public function admin_scripts_filter( $strings ) {

			$strings['comment_moderation_warning'] = __( 'This option cannot be modified because you have set YITH WooCommerce Review for Discounts to send coupons only after approval.', 'yith-woocommerce-review-for-discounts' );

			if ( $this->is_multivendor_active() ) {

				if ( function_exists( 'YWRFD_MultiVendor' ) && YWRFD_MultiVendor()->vendors_coupon_reviews_active() ) {

					$vendor               = yith_get_vendor( 'current', 'user' );
					$strings['vendor_id'] = $vendor->id;

				}

			}

			if ( $this->is_advanced_reviews_active() ) {

				$strings['ywar_active'] = true;

			}

			return $strings;

		}

		/**
		 * Initializes CSS and javascript
		 *
		 * @since   1.0.0
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function admin_scripts_premium() {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'ywrfd-admin-premium', YWRFD_ASSETS_URL . '/js/ywrfd-admin-premium' . $suffix . '.js', array( 'jquery' ), YWRFD_VERSION );

		}

		/**
		 * Trash expired coupons
		 *
		 * @since   1.0.0
		 *
		 * @param   $return
		 *
		 * @return  mixed
		 * @author  Alberto Ruggiero
		 */
		public function trash_expired_coupons( $return = false ) {

			$args = array(
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => 'generated_by',
						'value' => 'ywrfd',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'expiry_date',
							'value'   => date( 'Y-m-d', strtotime( "today" ) ),
							'compare' => '<',
							'type'    => 'DATE'
						),
						array(
							'key'     => 'usage_count',
							'value'   => 1,
							'compare' => '>='
						)
					)
				)
			);

			$query = new WP_Query( $args );
			$count = $query->post_count;

			if ( $query->have_posts() ) {

				while ( $query->have_posts() ) {

					$query->the_post();

					wp_trash_post( $query->post->ID );

				}

			}

			wp_reset_query();
			wp_reset_postdata();

			if ( $return ) {

				return $count;

			}

			return null;

		}

		/* === Multi Vendor Compatibility Functions === */

		/**
		 * Check if YITH WooCommerce Multi Vendor is active
		 *
		 * @since   1.0.0
		 * @return  bool
		 * @author  Alberto Ruggiero
		 */
		public function is_multivendor_active() {

			return defined( 'YITH_WPV_PREMIUM' ) && YITH_WPV_PREMIUM;

		}

		/**
		 * Initialize compatibility module for YITH WooCommerce Multi Vendor
		 *
		 * @since   1.0.0
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function init_multivendor_compatibility() {

			if ( $this->is_multivendor_active() ) {

				include_once( 'includes/class-ywrfd-multivendor.php' );

			}

		}

		/* === Email Templates Compatibility Functions === */

		/**
		 * Check if YITH WooCommerce Email Templates is active
		 *
		 * @since   1.0.0
		 * @return  bool
		 * @author  Alberto Ruggiero
		 */
		public function is_email_templates_active() {

			return defined( 'YITH_WCET_PREMIUM' ) && YITH_WCET_PREMIUM;

		}

		/**
		 * If is active YITH WooCommerce Email Templates, add YWRFD to list
		 *
		 * @since   1.0.0
		 *
		 * @param   $templates
		 *
		 * @return  array
		 * @author  Alberto Ruggiero
		 */
		public function add_yith_wcet_template( $templates ) {

			$templates[] = array(
				'id'   => 'yith-review-for-discounts',
				'name' => 'YITH WooCommerce Review for Discounts',
			);

			return $templates;

		}

		/* === Advanced Reviews Compatibility Functions === */

		/**
		 * Check if YITH WooCommerce Advanced Reviews is active
		 *
		 * @since   1.0.0
		 * @return  bool
		 * @author  Alberto Ruggiero
		 */
		public function is_advanced_reviews_active() {

			return defined( 'YITH_YWAR_PREMIUM' ) && YITH_YWAR_PREMIUM;

		}

		/**
		 * On comment approvation with YITH WooCommerce Advanced Reviews
		 *
		 * @since   1.0.0
		 *
		 * @param   $comment_id
		 * @param   $approved
		 *
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function on_comment_approvation_ywar( $comment_id, $approved ) {

			if ( $approved ) {

				$comment                       = new stdClass();
				$comment->comment_author       = get_post_meta( $comment_id, '_ywar_review_author', true );
				$comment->comment_author_email = get_post_meta( $comment_id, '_ywar_review_author_email', true );
				$comment->comment_post_ID      = get_post_meta( $comment_id, '_ywar_product_id', true );
				$comment->comment_parent       = wp_get_post_parent_id( $comment_id );
				$comment->user_id              = get_post_meta( $comment_id, '_ywar_review_user_id', true );

				if ( ! $this->user_already_commented_ywar( $comment ) && $this->is_moderation_required() ) {

					$this->get_coupons( $comment );

				}

			}

		}

		/**
		 * Check if an user has already commented with YITH WooCommerce Advanced Reviews
		 *
		 * @since   1.0.0
		 *
		 * @param   $comment
		 *
		 * @return  bool
		 * @author  Alberto Ruggiero
		 */
		public function user_already_commented_ywar( $comment ) {

			$args  = array(
				'post_type'      => 'ywar_reviews',
				'post_status'    => array( 'any', 'trash' ),
				'posts_per_page' => - 1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_ywar_review_author_email',
						'value'   => $comment->comment_author_email,
						'compare' => '='
					),
					array(
						'key'     => '_ywar_product_id',
						'value'   => $comment->comment_post_ID,
						'compare' => '='
					),
				)
			);
			$query = new WP_Query( $args );

			if ( $query->post_count > 1 ) {
				return true;
			}

			wp_reset_query();
			wp_reset_postdata();

			return false;

		}

		/**
		 * Advise if comment moderation is activated with YITH WooCommerce Advanced Reviews
		 *
		 * @since   1.0.0
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function set_notices_ywar() {

			if ( $this->is_moderation_required() ) {

				if ( get_option( 'ywar_review_moderation' ) == 'no' && empty( $_COOKIE['ywrfd_warning_3'] ) ) {

					update_option( 'ywar_review_moderation', 'yes' );
					?>
                    <div id="ywrfd-warning-3" class="notice notice-warning" style="position: relative;">
                        <p>
							<?php _e( 'Comment moderation has been enabled to make YITH WooCommerce Review for Discounts work correctly.', 'yith-woocommerce-review-for-discounts' ); ?>
                        </p>
                        <span class="notice-dismiss ywrfd-warning-3"></span>
                    </div>
					<?php
				}

			} else {

				if ( get_option( 'ywar_review_moderation' ) == 'yes' && empty( $_COOKIE['ywrfd_warning_4'] ) ) {

					?>
                    <div id="ywrfd-warning-4" class="notice notice-warning" style="position: relative;">
                        <p>
							<?php printf( __( 'Comment moderation is enabled but is not essential to make YITH WooCommerce Review for Discounts work correctly. If you want to change this option click %s here %s', 'yith-woocommerce-review-for-discounts' ), '<a href="' . esc_url( add_query_arg( array( 'page' => 'yith_ywar_panel', 'tab' => 'premium' ), admin_url( 'admin.php' ) ) ) . '" target="_blank">', '</a>' ); ?>
                        </p>
                        <span class="notice-dismiss ywrfd-warning-4"></span>
                    </div>
					<?php

				}

			}

		}

		public function give_funds( $discount, $user_id, $product_id, $user_mail ) {

			$fund_user = new YITH_YWF_Customer( $user_id );
			$new_funds = $discount->coupon_amount + $fund_user->get_funds();

			$fund_user->set_funds( $new_funds );

			$log_args = array(
				'user_id'        => $user_id,
				'type_operation' => 'review',
				'fund_user'      => $discount->coupon_amount,
				'description'    => $discount->description
			);

			YWF_Log()->add_log( $log_args );
			$email_result = YWRFD_Fund_Emails()->prepare_coupon_mail( $user_id, $discount->trigger, array( 'product_id' => $product_id, 'total_reviews' => $discount->trigger_threshold, 'amount'=> $discount->coupon_amount ), $user_mail, $discount->vendor_id );

		}

		/* === YITH FRAMEWORK === */

		/**
		 * Register plugins for activation tab
		 *
		 * @since   2.0.0
		 * @return  void
		 * @author  Andrea Grillo <andrea.grillo@yithemes.com>
		 */
		public function register_plugin_for_activation() {
			if ( ! class_exists( 'YIT_Plugin_Licence' ) ) {
				require_once 'plugin-fw/licence/lib/yit-licence.php';
				require_once 'plugin-fw/licence/lib/yit-plugin-licence.php';
			}
			YIT_Plugin_Licence()->register( YWRFD_INIT, YWRFD_SECRET_KEY, YWRFD_SLUG );
		}

		/**
		 * Register plugins for update tab
		 *
		 * @since   2.0.0
		 * @return  void
		 * @author  Andrea Grillo <andrea.grillo@yithemes.com>
		 */
		public function register_plugin_for_updates() {
			if ( ! class_exists( 'YIT_Upgrade' ) ) {
				require_once( 'plugin-fw/lib/yit-upgrade.php' );
			}
			YIT_Upgrade()->register( YWRFD_SLUG, YWRFD_INIT );
		}

		/**
		 * Plugin row meta
		 *
		 * add the action links to plugin admin page
		 *
		 * @since   1.0.0
		 *
		 * @param   $new_row_meta_args
		 * @param   $plugin_meta
		 * @param   $plugin_file
		 * @param   $plugin_data
		 * @param   $status
		 * @param   $init_file
		 *
		 * @return  array
		 * @author  Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use     plugin_row_meta
		 */
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YWRFD_INIT' ) {
			$new_row_meta_args = parent::plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file );

			if ( defined( $init_file ) && constant( $init_file ) == $plugin_file ) {
				$new_row_meta_args['is_premium'] = true;
			}

			return $new_row_meta_args;
		}

		/**
		 * Action Links
		 *
		 * add the action links to plugin admin page
		 * @since   1.0.0
		 *
		 * @param   $links | links plugin array
		 *
		 * @return  mixed
		 * @author  Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use     plugin_action_links_{$plugin_file_name}
		 */
		public function action_links( $links ) {
			$links = yith_add_action_links( $links, $this->_panel_page, true );

			return $links;
		}

	}

}