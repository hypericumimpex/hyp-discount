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
    exit; // Exit if accessed directly
}

if ( !class_exists( 'YWRFD_Discounts' ) ) {

    /**
     * Discount class
     *
     * @class   YWRFD_Discounts
     * @package Yithemes
     * @since   1.0.0
     * @author  Your Inspiration Themes
     *
     */
    class YWRFD_Discounts {

        /**
         * @var string coupon description
         */
        public $description = null;

        /**
         * @var string coupon trigger
         */
        public $trigger = null;

        /**
         * @var array coupon triggering products
         */
        public $trigger_product_ids = null;

        /**
         * @var array coupon triggering categories
         */
        public $trigger_product_categories = null;

        /**
         * @var int/string coupon triggering number of reviews
         */
        public $trigger_threshold = null;

        /**
         * @var string coupon enable notification of approaching to threshold
         */
        public $trigger_enable_notify = null;

        /**
         * @var int/string coupon number of reviews when to start sending notification
         */
        public $trigger_threshold_notify = null;

        /**
         * @var string coupon discount type
         */
        public $discount_type = null;

        /**
         * @var int coupon amount
         */
        public $coupon_amount = null;

        /**
         * @var int coupon expiry days
         */
        public $expiry_days = null;

        /**
         * @var string coupon free shipping
         */
        public $free_shipping = null;

        /**
         * @var string coupon individual use
         */
        public $individual_use = null;

        /**
         * @var int/string coupon minimum amount
         */
        public $minimum_amount = null;

        /**
         * @var int/string coupon maximum amount
         */
        public $maximum_amount = null;

        /**
         * @var array coupon allowed products
         */
        public $product_ids = null;

        /**
         * @var array coupon allowed categories
         */
        public $product_categories = null;

        /**
         * @var int coupon vendor id
         */
        public $vendor_id = null;

        /**
         * Constructor
         *
         * @since   1.0.0
         *
         * @param   $id
         *
         * @return  void
         * @author  Alberto Ruggiero
         */
        public function __construct( $id = false ) {

            $defaults = array(
                'description'                => '',
                'trigger'                    => 'review',
                'trigger_product_ids'        => array(),
                'trigger_product_categories' => array(),
                'trigger_threshold'          => '',
                'trigger_enable_notify'      => 'no',
                'trigger_threshold_notify'   => '',
                'discount_type'              => 'percent',
                'coupon_amount'              => 0,
                'expiry_days'                => 0,
                'free_shipping'              => 'no',
                'individual_use'             => 'no',
                'minimum_amount'             => '',
                'maximum_amount'             => '',
                'product_ids'                => array(),
                'product_categories'         => array(),
                'vendor_id'                  => 0,
            );

            if ( !$id ) {

                $defaults['description']   = __( '10% discount on cart total', 'yith-woocommerce-review-for-discounts' );
                $defaults['coupon_amount'] = '10';
                $defaults['expiry_days']   = 30;

            }

            foreach ( $defaults as $key => $value ) {

                if ( $id ) {

                    $this->$key = get_post_meta( $id, 'ywrfd_' . $key, true );

                }
                else {

                    $this->$key = '';

                }

                if ( empty( $this->$key ) ) {

                    $this->$key = $value;

                }
                elseif ( in_array( $key, array( 'product_ids', 'product_categories', 'trigger_product_ids', 'trigger_product_categories' ) ) ) {

                    $this->$key = $this->format_array( $this->$key );

                }

            }

        }

        /**
         * Format loaded data as array
         *
         * @since   1.0.0
         *
         * @param   $array
         *
         * @return  array
         * @author  Alberto Ruggiero
         */
        private function format_array( $array ) {

            if ( !is_array( $array ) ) {

                if ( is_serialized( $array ) ) {

                    $array = maybe_unserialize( $array );

                }
                else {

                    $array = explode( ',', $array );

                }

            }

            return array_filter( array_map( 'trim', array_map( 'strtolower', $array ) ) );

        }

    }

}

