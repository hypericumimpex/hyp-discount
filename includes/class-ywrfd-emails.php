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

if ( ! class_exists( 'YWRFD_Emails' ) ) {

	/**
	 * Implements email functions for YWRFD plugin
	 *
	 * @class   YWRFD_Emails
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 *
	 */
	class YWRFD_Emails {

		/**
		 * Single instance of the class
		 *
		 * @var \YWRFD_Emails
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \YWRFD_Emails
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
		public function prepare_coupon_mail( $user_id, $coupon_code, $type, $args = array(), $user_email, $vendor_id = '' ) {

			if ( $user_id ) {

				$first_name = get_user_meta( $user_id, 'billing_first_name', true );

				if ( $first_name == '' ) {
					$first_name = get_user_meta( $user_id, 'nickname', true );
				}

				$last_name = get_user_meta( $user_id, 'billing_last_name', true );

				if ( $last_name == '' ) {
					$last_name = get_user_meta( $user_id, 'nickname', true );
				}

			} else {

				$first_name = $args['nickname'];
				$last_name  = $args['nickname'];

			}

			global $sitepress;

			$wp_language = get_user_meta( $user_id, 'locale', true );
			$language    = '';

			if ( ! empty( $sitepress ) ) {

				if ( $wp_language ) {
					$language = $sitepress->get_language_code_from_locale( $wp_language );
				} else {

					if ( isset( $args['product_id'] ) && $args['product_id'] != 0 ) {
						$product = wc_get_product( $args['product_id'] );
						if ( $product && ! $wp_language ) {
							$language = $sitepress->get_language_for_element( $product->get_id(), 'post_product' );
						}
					}

					if ( ! $language ) {
						$language = $sitepress->get_default_language();
					}

				}

			}

			$mail_body    = $this->get_mail_body( $coupon_code, $type, $first_name, $last_name, $user_email, $args, $vendor_id, $language );
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
		public function get_mail_body( $coupon_code, $type, $first_name, $last_name, $user_email, $args = array(), $vendor_id = '', $language = '' ) {


			$option_name = 'ywrfd_email_' . $type . '_mailbody' . ( apply_filters( 'ywrfd_set_vendor_id', '', $vendor_id ) );
			$mail_body   = apply_filters( 'wpml_translate_single_string', get_option( $option_name ), 'admin_texts_' . $option_name, $option_name, $language );
			$coupon      = $this->get_coupon_info( $coupon_code );
			$find        = array(
				'{coupon_description}',
				'{site_title}',
				'{customer_name}',
				'{customer_last_name}',
				'{customer_email}',
				'{vendor_name}',
			);
			$replace     = array(
				$coupon,
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

				case 'notify':
					$find[]    = '{remaining_reviews}';
					$replace[] = $args['remaining_reviews'];
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

			$option_name = 'ywrfd_email_' . $type . '_subject' . ( apply_filters( 'ywrfd_set_vendor_id', '', $vendor_id ) );
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
		 * Get coupon info
		 *
		 * @since   1.0.0
		 *
		 * @param   $coupon_code
		 *
		 * @return  string
		 * @author  Alberto Ruggiero
		 */
		public function get_coupon_info( $coupon_code ) {

			$result    = '';
			$coupon    = new WC_Coupon( $coupon_code );
			$coupon_id = yit_get_prop( $coupon, 'id' );

			if ( $coupon_id ) {

				$post = get_post( $coupon_id );
				if ( $post ) {

					$amount_suffix = get_woocommerce_currency_symbol();

					if ( function_exists( 'wc_price' ) ) {

						$amount_suffix = null;

					}

					$discount_type = yit_get_prop( $coupon, 'discount_type' );

					if ( $discount_type == 'percent' || $discount_type == 'percent_product' ) {

						$amount_suffix = '%';

					}

					$amount = yit_get_prop( $coupon, 'coupon_amount' );
					if ( $amount_suffix === null ) {
						$amount        = wc_price( $amount );
						$amount_suffix = '';
					}

					$products            = array();
					$products_excluded   = array();
					$categories          = array();
					$categories_excluded = array();

					$product_ids                = yit_get_prop( $coupon, 'product_ids' );
					$exclude_product_ids        = yit_get_prop( $coupon, 'exclude_product_ids' );
					$product_categories         = yit_get_prop( $coupon, 'product_categories' );
					$exclude_product_categories = yit_get_prop( $coupon, 'exclude_product_categories' );
					$minimum_amount             = yit_get_prop( $coupon, 'minimum_amount' );
					$maximum_amount             = yit_get_prop( $coupon, 'maximum_amount' );
					$expiry_date                = yit_get_prop( $coupon, 'expiry_date' );

					if ( $product_ids && count( $product_ids ) >= 1 ) {
						foreach ( $product_ids as $product_id ) {
							$product = wc_get_product( $product_id );
							if ( $product ) {
								$products[] = $this->render_mailbody_link( $product_id, 'product' );
							}
						}
					}

					if ( $exclude_product_ids && count( $exclude_product_ids ) >= 1 ) {
						foreach ( $exclude_product_ids as $product_id ) {
							$product = wc_get_product( $product_id );
							if ( $product ) {
								$products_excluded[] = $this->render_mailbody_link( $product_id, 'product' );
							}
						}
					}

					if ( $product_categories && count( $product_categories ) >= 1 ) {
						foreach ( $product_categories as $term_id ) {
							$term = get_term_by( 'id', $term_id, 'product_cat' );
							if ( $term ) {
								$categories[] = $this->render_mailbody_link( $term_id, 'category' );
							}
						}
					}

					if ( $exclude_product_categories && count( $exclude_product_categories ) >= 1 ) {
						foreach ( $exclude_product_categories as $term_id ) {
							$term = get_term_by( 'id', $term_id, 'product_cat' );
							if ( $term ) {
								$categories_excluded[] = $this->render_mailbody_link( $term_id, 'category' );
							}
						}
					}

					ob_start();
					?>

                    <h2>
						<?php echo __( 'Coupon code: ', 'yith-woocommerce-review-for-discounts' ) . yit_get_prop( $coupon, 'code' ); ?>
                    </h2>

					<?php if ( ! empty( $post->post_excerpt ) ) : ?>

                        <i>
							<?php echo $post->post_excerpt; ?>
                        </i>

					<?php endif; ?>

                    <p>
                        <b>
							<?php printf( __( 'Coupon amount: %s%s off', 'yith-woocommerce-review-for-discounts' ), $amount, $amount_suffix ); ?>
							<?php if ( yit_get_prop( $coupon, 'free_shipping' ) == 'yes' ) : ?>
                                + <?php _e( 'Free shipping', 'yith-woocommerce-review-for-discounts' ); ?>
                                <br />
							<?php endif; ?>
                        </b>
                        <span>
                            <?php if ( $minimum_amount != '' && $maximum_amount == '' ) : ?>
	                            <?php printf( __( 'Valid for a minimum purchase of %s', 'yith-woocommerce-review-for-discounts' ), wc_price( yit_get_prop( $coupon, 'minimum_amount' ) ) ); ?>
                            <?php endif; ?>
                            <?php if ( $minimum_amount == '' && $maximum_amount != '' ) : ?>
	                            <?php printf( __( 'Valid for a maximum purchase of %s', 'yith-woocommerce-review-for-discounts' ), wc_price( yit_get_prop( $coupon, 'maximum_amount' ) ) ); ?>
                            <?php endif; ?>
                            <?php if ( $minimum_amount != '' && $maximum_amount != '' ) : ?>
	                            <?php printf( __( 'Valid for a minimum purchase of %s and a maximum of %s', 'yith-woocommerce-review-for-discounts' ), wc_price( $minimum_amount ), wc_price( $maximum_amount ) ); ?>
                            <?php endif; ?>
                        </span>
                    </p>

					<?php if ( count( $products ) > 0 || count( $categories ) > 0 ) : ?>
                        <p>
                            <b><?php echo __( 'Valid for:' ); ?></b>
                            <br />
							<?php if ( count( $products ) > 0 ) : ?>
								<?php printf( __( 'Products: %s', 'yith-woocommerce-review-for-discounts' ), implode( ',', $products ) ); ?>
                                <br />
							<?php endif; ?>

							<?php if ( count( $categories ) > 0 ) : ?>
								<?php printf( __( 'Products of these categories: %s', 'yith-woocommerce-review-for-discounts' ), implode( ',', $categories ) ); ?>
                                <br />
							<?php endif; ?>

                        </p>
					<?php endif; ?>

					<?php if ( count( $products_excluded ) > 0 || count( $categories_excluded ) > 0 ) : ?>
                        <p>
                            <b><?php echo __( 'Not valid for:' ); ?></b>
                            <br />
							<?php if ( count( $products_excluded ) > 0 ): ?>
								<?php printf( __( 'Products: %s', 'yith-woocommerce-review-for-discounts' ), implode( ',', $products_excluded ) ) ?>
                                <br />
							<?php endif; ?>

							<?php if ( count( $categories_excluded ) > 0 ): ?>
								<?php printf( __( 'Products of these categories: %s', 'yith-woocommerce-review-for-discounts' ), implode( ',', $categories_excluded ) ) ?>
                                <br />
							<?php endif; ?>
                        </p>
					<?php endif; ?>

                    <span>
                        <?php if ( yit_get_prop( $coupon, 'individual_use' ) == 'yes' ) : ?>
                            &bull; <?php _e( 'This coupon cannot be used with other coupons', 'yith-woocommerce-review-for-discounts' ); ?>
                            <br />
                        <?php endif; ?>
						<?php if ( yit_get_prop( $coupon, 'exclude_sale_items' ) == 'yes' ) : ?>
                            &bull; <?php _e( 'This coupon will not be applied to items on sale', 'yith-woocommerce-review-for-discounts' ); ?>
                            <br />
						<?php endif; ?>
                    </span>

					<?php if ( $expiry_date != '' ) : ?>
                        <p>
                            <br />
                            <b>
								<?php printf( __( 'Expiration date: %s', 'yith-woocommerce-review-for-discounts' ), ucwords( date_i18n( get_option( 'date_format' ), yit_datetime_to_timestamp( $expiry_date ) ) ) ); ?>
                            </b>
                        </p>
					<?php endif; ?>

					<?php

					$result = ob_get_clean();

				}

			}

			return $result;

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
	 * Unique access to instance of YWRFD_Emails class
	 *
	 * @return \YWRFD_Emails
	 */
	function YWRFD_Emails() {

		return YWRFD_Emails::get_instance();

	}

}