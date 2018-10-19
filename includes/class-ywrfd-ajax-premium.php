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

if ( !class_exists( 'YWRFD_Ajax_Premium' ) ) {

    /**
     * Implements AJAX for YWRFD plugin
     *
     * @class   YWRFD_Ajax_Premium
     * @package Yithemes
     * @since   1.0.0
     * @author  Your Inspiration Themes
     *
     */
    class YWRFD_Ajax_Premium {

        /**
         * Single instance of the class
         *
         * @var \YWRFD_Ajax_Premium
         * @since 1.0.0
         */
        protected static $instance;

        /**
         * Returns single instance of the class
         *
         * @return \YWRFD_Ajax_Premium
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

            add_action( 'wp_ajax_ywrfd_get_minimum_threshold', array( $this, 'get_minimum_threshold' ) );
            add_action( 'wp_ajax_ywrfd_clear_expired_coupons', array( $this, 'clear_expired_coupons' ) );

        }

        /**
         * Send a test mail from option panel
         *
         * @since   1.0.0
         * @return  void
         * @author  Alberto Ruggiero
         */
        public function get_minimum_threshold() {

            try {
                $value = array();
                $args  = array(
                    'post_type'      => 'ywrfd-discount',
                    'post_status'    => 'publish',
                    'posts_per_page' => - 1,
                    'post__not_in'   => array( $_POST['post_id'] ),
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array(
                            'key'   => 'ywrfd_trigger',
                            'value' => 'multiple',
                        ),
                        array(
                            'key'   => 'ywrfd_vendor_id',
                            'value' => $_POST['vendor_id'],
                        ),
                        array(
                            'key'     => 'ywrfd_trigger_threshold',
                            'value'   => absint( $_POST['value'] ),
                            'compare' => '<',
                            'type'    => 'NUMERIC'
                        ),
                    )
                );

                $query = new WP_Query( $args );

                if ( $query->have_posts() ) {

                    while ( $query->have_posts() ) {

                        $query->the_post();

                        $discount = new YWRFD_Discounts( $query->post->ID );

                        $value[] = $discount->trigger_threshold;

                    }

                }

                wp_reset_query();
                wp_reset_postdata();

                $result = ( empty( $value ) ) ? 1 : max( $value ) + 1;

                wp_send_json( array( 'success' => true, 'value' => $result ) );


            } catch ( Exception $e ) {

                wp_send_json( array( 'success' => false, 'error' => $e->getMessage() ) );

            }

        }

        /**
         * Clear expired coupons manually
         *
         * @since   1.0.0
         * @return  void
         * @author  Alberto Ruggiero
         */
        public function clear_expired_coupons() {

            $result = array(
                'success' => true,
                'message' => ''
            );

            try {

                $count = YITH_WRFD()->trash_expired_coupons( true );

                $result['message'] = sprintf( _n( 'Operation completed. %d coupon trashed.', 'Operation completed. %d coupons trashed.', $count, 'yith-woocommerce-review-for-discounts' ), $count );

            } catch ( Exception $e ) {

                $result['success'] = false;
                $result['message'] = sprintf( __( 'An error occurred: %s', 'yith-woocommerce-review-for-discounts' ), $e->getMessage() );

            }

            wp_send_json( $result );

        }


    }

    /**
     * Unique access to instance of YWRFD_Ajax_Premium class
     *
     * @return \YWRFD_Ajax_Premium
     */
    function YWRFD_Ajax_Premium() {

        return YWRFD_Ajax_Premium::get_instance();

    }

    new YWRFD_Ajax_Premium();

}