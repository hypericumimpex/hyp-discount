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

/**
 * Implements Coupon Mail for YWRFD plugin (HTML)
 *
 * @class   YWRFD_Coupon_Mail
 * @package Yithemes
 * @since   1.0.0
 * @author  Your Inspiration Themes
 */

$email_templates = false;

if ( defined( 'YWRFD_PREMIUM' ) ) {
    $email_templates = YITH_WRFD()->is_email_templates_active();
}

if ( $email_templates ) {

    do_action( 'yith_wcet_email_header', $email_heading, 'yith-review-for-discounts' );

}
else {

    do_action( 'woocommerce_email_header', $email_heading );

}

echo $mail_body;


if ( $email_templates ) {

    do_action( 'yith_wcet_email_footer', 'yith-review-for-discounts' );

}
else {

    do_action( 'woocommerce_email_footer' );

}