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

if ( ! class_exists( 'YWRFD_Fund_Emails' ) ) {

	/**
	 * Implements email functions for YWRFD plugin
	 *
	 * @class   YWRFD_Fund_Emails
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 *
	 */
	class YWRFD_Fund_Emails {

		/**
		 * Single instance of the class
		 *
		 * @var \YWRFD_Fund_Emails
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \YWRFD_Fund_Emails
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

		}

		/**
		 * Send the coupon mail
		 *
		 * @since   1.0.0
		 *
		 * @param   $mail_body
		 * @param   $mail_subject
		 * @param   $mail_address
		 * @param   $type
		 * @param   $vendor_id
		 *
		 * @return  bool
		 * @author  Alberto Ruggiero
		 */
		public function send_email( $mail_body, $mail_subject, $mail_address, $type, $vendor_id = '' ) {

			$wc_email = WC_Emails::instance();
			$email    = $wc_email->emails['YWRFD_Coupon_Mail'];

			return $email->trigger( $mail_body, $mail_subject, $mail_address, $type, $vendor_id );

		}

		/**
		 * Set the coupon email
		 *
		 * @since   1.0.0
		 *
		 * @param   $user_id
		 * @param   $coupon_code
		 * @param   $type
		 * @param   $args
		 * @param   $user_email
		 * @param   $vendor_id
		 *
		 * @return  bool
		 * @author  Alberto Ruggiero
		 */
		public function prepare_coupon_mail( $user_id, $type, $args = array(), $user_email, $vendor_id = '' ) {

			$first_name = get_user_meta( $user_id, 'billing_first_name', true );

			if ( $first_name == '' ) {
				$first_name = get_user_meta( $user_id, 'nickname', true );
			}

			$last_name = get_user_meta( $user_id, 'billing_last_name', true );

			if ( $last_name == '' ) {
				$last_name = get_user_meta( $user_id, 'nickname', true );
			}


			global $sitepress;

			$wp_language = get_user_meta( $user_id, 'locale', true );
			$language    = '';

			if ( ! empty( $sitepress ) ) {

				if ( $wp_language ) {
					$language = $sitepress->get_language_code_from_locale( $wp_language );
				} else {
					$language = $sitepress->get_default_language();
				}

			}

			$mail_body    = $this->get_mail_body( $type, $first_name, $last_name, $user_email, $args, $vendor_id, $language );
			$mail_subject = $this->get_subject( $type, $first_name, $last_name, $vendor_id, $language );

			return $this->send_email( $mail_body, $mail_subject, $user_email, $type, $vendor_id );

		}

		/**
		 * Set the mail body
		 *
		 * @since   1.0.0
		 *
		 * @param   $coupon_code
		 * @param   $type
		 * @param   $first_name
		 * @param   $last_name
		 * @param   $user_email
		 * @param   $args
		 * @param   $vendor_id
		 * @param   $language
		 *
		 * @return  string
		 * @author  Alberto Ruggiero
		 */
		public function get_mail_body( $type, $first_name, $last_name, $user_email, $args = array(), $vendor_id = '', $language = '' ) {
			$option_name = 'ywrfd_email_funds_' . $type . '_mailbody' . ( apply_filters( 'ywrfd_set_vendor_id', '', $vendor_id ) );
			$mail_body   = apply_filters( 'wpml_translate_single_string', get_option( $option_name ), 'admin_texts_' . $option_name, $option_name, $language );

			$find    = array(
				'{funds_amount}',
				'{site_title}',
				'{customer_name}',
				'{customer_last_name}',
				'{customer_email}',
				'{vendor_name}',
			);
			$replace = array(
				wc_price( $args['amount'] ),
				get_option( 'blogname' ),
				$first_name,
				$last_name,
				$user_email,
				apply_filters( 'ywrfd_get_vendor_name', '', $vendor_id ),
			);

			switch ( $type ) {

				case 'multiple':
					$find[]    = '{total_reviews}';
					$replace[] = $args['total_reviews'];
					break;

				default:
					$find[]    = '{product_name}';
					$replace[] = ( ! isset( $args['product_id'] ) ) ? '' : $this->render_mailbody_link( $args['product_id'], 'product' );

			}

			$mail_body = str_replace( $find, $replace, nl2br( $mail_body ) );

			return $mail_body;

		}

		/**
		 * Set the subject and mail heading
		 *
		 * @since   1.0.0
		 *
		 * @param   $type
		 * @param   $first_name
		 * @param   $last_name
		 * @param   $vendor_id
		 * @param   $language
		 *
		 * @return  string
		 * @author  Alberto Ruggiero
		 */
		public function get_subject( $type, $first_name, $last_name, $vendor_id = '', $language = '' ) {

			$option_name = 'ywrfd_email_funds_' . $type . '_subject' . ( apply_filters( 'ywrfd_set_vendor_id', '', $vendor_id ) );
			$subject     = apply_filters( 'wpml_translate_single_string', get_option( $option_name ), 'admin_texts_' . $option_name, $option_name, $language );
			$find        = array(
				'{site_title}',
				'{customer_name}',
				'{customer_last_name}',
				'{vendor_name}',
			);
			$replace     = array(
				get_option( 'blogname' ),
				$first_name,
				$last_name,
				apply_filters( 'ywrfd_get_vendor_name', '', $vendor_id ),
			);
			$subject     = str_replace( $find, $replace, $subject );

			return $subject;
		}

		/**
		 * Renders links for products or categories
		 *
		 * @since   1.0.0
		 *
		 * @param   $object_id
		 * @param   $type
		 *
		 * @return  string
		 * @author  Alberto Ruggiero
		 */
		public function render_mailbody_link( $object_id, $type ) {

			if ( $type == 'product' ) {

				$product = wc_get_product( $object_id );
				$url     = esc_url( get_permalink( yit_get_product_id( $product ) ) );
				$title   = $product->get_title();

			} else {

				$term = get_term_by( 'id', $object_id, 'product_cat' );

				$url   = get_term_link( $term->slug, 'product_cat' );
				$title = esc_html( $term->name );

			}

			return sprintf( '<a href="%s">%s</a>', $url, $title );
		}

	}

	/**
	 * Unique access to instance of YWRFD_Fund_Emails class
	 *
	 * @return \YWRFD_Fund_Emails
	 */
	function YWRFD_Fund_Emails() {

		return YWRFD_Fund_Emails::get_instance();

	}

}