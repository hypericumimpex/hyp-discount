<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

if ( !class_exists( 'YWRFD_Coupon_Mail' ) ) {

    /**
     * Implements Coupon Mail for YWRFD plugin
     *
     * @class   YWRFD_Coupon_Mail
     * @package Yithemes
     * @since   1.0.0
     * @author  Your Inspiration Themes
     * @extends WC_Email
     *
     */
    class YWRFD_Coupon_Mail extends WC_Email {

        /**
         * @var int $mail_body content of the email
         */
        var $mail_body;

        /**
         * Constructor
         *
         * Initialize email type and set templates paths
         *
         * @since   1.0.0
         * @author  Alberto Ruggiero
         */
        public function __construct() {

            $this->id             = 'yith-review-for-discounts';
            $this->customer_email = true;
            $this->description    = __( 'Reward your customers with a gentle discount: approve their reviews and encourage them to purchase again in your shop!', 'yith-woocommerce-review-for-discounts' );
            $this->title          = __( 'Review For Discounts Emails', 'yith-woocommerce-review-for-discounts' );
            $this->template_html  = '/emails/coupon-email.php';
            $this->template_plain = '/emails/plain/coupon-email.php';
            $this->enabled        = 'yes';

            parent::__construct();

        }

        /**
         * Trigger email send
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
        public function trigger( $mail_body, $mail_subject, $mail_address, $type, $vendor_id = '' ) {

            $this->email_type = get_option( 'ywrfd_email_' . $type . '_type' . ( apply_filters( 'ywrfd_set_vendor_id', '', $vendor_id ) ) );
            $this->heading    = $mail_subject;
            $this->subject    = $mail_subject;
            $this->mail_body  = $mail_body;
            $this->recipient  = $mail_address;

            if ( !$this->get_recipient() ) {
                return false;
            }

            return $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), "" );

        }

        /**
         * Send the email.
         *
         * @since   1.0.0
         *
         * @param   string $to
         * @param   string $subject
         * @param   string $message
         * @param   string $headers
         * @param   string $attachments
         *
         * @return  bool
         * @author  Alberto Ruggiero
         */
        public function send( $to, $subject, $message, $headers, $attachments ) {

            add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
            add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
            add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

            $message = apply_filters( 'woocommerce_mail_content', $this->style_inline( $message ) );

            if ( defined( 'YWRFD_PREMIUM' ) && get_option( 'ywrfd_mandrill_enable' ) == 'yes' ) {

                $return = YWRFD_Mandrill()->send_email( $to, $subject, $message, $headers, $attachments );

            }
            else {

                $return = wp_mail( $to, $subject, $message, $headers, $attachments );

            }

            remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
            remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
            remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

            return $return;

        }

        /**
         * Get HTML content
         *
         * @since   1.0.0
         * @return  string
         * @author  Alberto Ruggiero
         */
        public function get_content_html() {

            ob_start();

            wc_get_template( $this->template_html, array(
                'email_heading' => $this->get_heading(),
                'mail_body'     => $this->mail_body,
                'sent_to_admin' => false,
                'plain_text'    => false
            ), YWRFD_TEMPLATE_PATH, YWRFD_TEMPLATE_PATH );

            return ob_get_clean();

        }

        /**
         * Get Plain content
         *
         * @since   1.0.0
         * @return  string
         * @author  Alberto Ruggiero
         */
        public function get_content_plain() {

            ob_start();

            wc_get_template( $this->template_plain, array(
                'email_heading' => $this->get_heading(),
                'mail_body'     => $this->mail_body,
                'sent_to_admin' => false,
                'plain_text'    => true
            ), YWRFD_TEMPLATE_PATH, YWRFD_TEMPLATE_PATH );

            return ob_get_clean();

        }

        /**
         * Get email content type.
         *
         * @since   1.0.2
         * @return  string
         * @author  Alberto Ruggiero
         */
        public function get_content_type() {

            return 'text/html';

        }

        /**
         * Checks if this email is enabled and will be sent.
         * @since   1.0.2
         * @return  bool
         * @author  Alberto Ruggiero
         */
        public function is_enabled() {
            return true;
        }

        /**
         * Admin Panel Options Processing - Saves the options to the DB
         *
         * @since   1.0.0
         * @return  boolean|null
         * @author  Alberto Ruggiero
         */
        public function process_admin_options() {

            $tab_name = ( defined( 'YWRFD_PREMIUM' ) ? 'premium-general' : 'general' );

            woocommerce_update_options( $this->form_fields[$tab_name] );

        }

        /**
         * Setup email settings screen.
         *
         * @since   1.0.0
         * @return  string
         * @author  Alberto Ruggiero
         */
        public function admin_options() {

            $tab_name = ( defined( 'YWRFD_PREMIUM' ) ? 'premium-general' : 'general' );

            ?>
            <table class="form-table">
                <?php woocommerce_admin_fields( $this->form_fields[$tab_name] ); ?>
            </table>
            <?php

        }

        /**
         * Initialise Settings Form Fields
         *
         * @since   1.0.0
         * @return  void
         * @author  Alberto Ruggiero
         */
        public function init_form_fields() {

            $tab_name = ( defined( 'YWRFD_PREMIUM' ) ? 'premium-general' : 'general' );

            $this->form_fields = include( YWRFD_DIR . '/plugin-options/' . $tab_name . '-options.php' );

        }

    }

}

return new YWRFD_Coupon_Mail();