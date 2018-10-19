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

if ( ! class_exists( 'YWRFD_Ajax' ) ) {

	/**
	 * Implements AJAX for YWRFD plugin
	 *
	 * @class   YWRFD_Ajax
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 *
	 */
	class YWRFD_Ajax {

		/**
		 * Single instance of the class
		 *
		 * @var \YWRFD_Ajax
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \YWRFD_Ajax
		 * @since 1.0.0
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {

				self::$instance = new self( $_REQUEST );

			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since   1.0.0
		 * @return  mixed
		 * @author  Alberto Ruggiero
		 */
		public function __construct() {

			add_action( 'wp_ajax_ywrfd_send_test_mail', array( $this, 'send_test_mail' ) );

		}

		/**
		 * Send a test mail from option panel
		 *
		 * @since   1.0.0
		 * @return  void
		 * @author  Alberto Ruggiero
		 */
		public function send_test_mail() {

			try {

				$current_user = wp_get_current_user();

				$product_id  = 0;
				$coupon_code = '';
				$user_info   = array(
					'nickname' => $current_user->nickname,
					'email'    => $current_user->user_email
				);
				$mail_type   = str_replace( 'funds_', '', $_POST['type'] );
				$is_funds    = strpos( $_POST['type'], 'funds_' ) !== false;

				if ( $mail_type != 'notify' ) {

					$args = array(
						'posts_per_page' => 1,
						'orderby'        => 'rand',
						'post_type'      => 'product'
					);

					$random_products = get_posts( $args );

					foreach ( $random_products as $item ) {

						$product_id = $item->ID;

					}

					$discount = new YWRFD_Discounts();

					if ( ! $is_funds ) {
						$coupon_code = YITH_WRFD()->create_coupon( $discount, $user_info );
					}

				}

				$args = array(
					'product_id'        => $product_id,
					'total_reviews'     => 10,
					'remaining_reviews' => 3,
					'amount'            => 5
				);

				if ( ! $is_funds ) {
					$email_result = YWRFD_Emails()->prepare_coupon_mail( $current_user->ID, $coupon_code, $mail_type, $args, $_POST['email'], $_POST['vendor_id'] );
				} else {
					$email_result = YWRFD_Fund_Emails()->prepare_coupon_mail( $current_user->ID, $mail_type, $args, $_POST['email'], $_POST['vendor_id'] );
				}


				if ( ! $email_result ) {

					wp_send_json( array( 'error' => __( 'There was an error while sending the email', 'yith-woocommerce-coupon-email-system' ) ) );

				} else {

					wp_send_json( true );

				}

			} catch ( Exception $e ) {

				wp_send_json( array( 'error' => $e->getMessage() ) );

			}


		}

	}

	/**
	 * Unique access to instance of YWRFD_Ajax class
	 *
	 * @return \YWRFD_Ajax
	 */
	function YWRFD_Ajax() {

		return YWRFD_Ajax::get_instance();

	}

	new YWRFD_Ajax();

}