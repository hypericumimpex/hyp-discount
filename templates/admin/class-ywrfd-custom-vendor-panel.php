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
 * Outputs a custom panel to show global plugin settings in vendors options panel
 *
 * @class   YWRFD_Custom_Vendor_Panel
 * @package Yithemes
 * @since   1.0.0
 * @author  Your Inspiration Themes
 *
 */
class YWRFD_Custom_Vendor_Panel {

    /**
     * Single instance of the class
     *
     * @var \YWRFD_Custom_Vendor_Panel
     * @since 1.0.0
     */
    protected static $instance;

    /**
     * Returns single instance of the class
     *
     * @return \YWRFD_Custom_Vendor_Panel
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

        add_action( 'woocommerce_admin_field_ywrfd-vendor-panel', array( $this, 'output' ) );

    }

    /**
     * Outputs a custom panel to show global plugin settings in vendors options panel
     *
     * @since   1.0.0
     *
     * @param   $option
     *
     * @return  void
     * @author  Alberto Ruggiero
     */
    public function output( $option ) {

        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for=""><?php _e( 'The coupon will be sent', 'yith-woocommerce-review-for-discounts' ); ?></label>
            </th>
            <td class="forminp forminp-custom-panel">

                <?php if ( get_option( 'ywrfd_coupon_sending' ) == 'moderated' ): ?>

                    <?php _e( 'After review approval', 'yith-woocommerce-review-for-discounts' ); ?>

                <?php else: ?>

                    <?php _e( 'After review composition', 'yith-woocommerce-review-for-discounts' ); ?>

                <?php endif; ?>

            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for=""><?php _e( 'Deletion of Expired Coupons', 'yith-woocommerce-review-for-discounts' ); ?></label>
            </th>
            <td class="forminp forminp-custom-panel">

                <?php if ( get_option( 'ywrfd_coupon_purge' ) == 'yes' ): ?>

                    <?php _e( 'Expired coupons will be deleted automatically', 'yith-woocommerce-review-for-discounts' ); ?>

                <?php else: ?>

                    <?php _e( 'Expired coupons must be deleted manually', 'yith-woocommerce-review-for-discounts' ); ?>

                <?php endif; ?>

            </td>
        </tr>
        <?php
    }

}

/**
 * Unique access to instance of YWRFD_Custom_Vendor_Panel class
 *
 * @return \YWRFD_Custom_Vendor_Panel
 */
function YWRFD_Custom_Vendor_Panel() {

    return YWRFD_Custom_Vendor_Panel::get_instance();

}

new YWRFD_Custom_Vendor_Panel();