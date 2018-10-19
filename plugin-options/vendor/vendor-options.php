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

$query_args            = array(
    'page' => isset( $_GET['page'] ) ? $_GET['page'] : '',
    'tab'  => 'howto',
);
$howto_url             = esc_url( add_query_arg( $query_args, admin_url( 'admin.php' ) ) );
$placeholders_text     = __( 'Allowed placeholders:', 'yith-woocommerce-review-for-discounts' );
$ph_reference_link     = ' - <a href="' . $howto_url . '" target="_blank">' . __( 'More info', 'yith-woocommerce-review-for-discounts' ) . '</a>';
$ph_site_title         = ' <b>{site_title}</b>';
$ph_vendor_name        = ' <b>{vendor_name}</b>';
$ph_customer_name      = ' <b>{customer_name}</b>';
$ph_customer_last_name = ' <b>{customer_last_name}</b >';
$ph_customer_email     = ' <b>{customer_email}</b>';
$ph_product_name       = ' <b>{product_name}</b>';
$ph_coupon_description = ' <b>{coupon_description}</b>';
$ph_total_reviews      = ' <b>{total_reviews}</b>';
$ph_remaining_reviews  = ' <b>{remaining_reviews}</b>';
$vendor                = yith_get_vendor( 'current', 'user' );

return array(

    'vendor' => array(

        'ywrfd_main_section_title' => array(
            'name' => __( 'Review For Discounts settings', 'yith-woocommerce-review-for-discounts' ),
            'desc' => __( 'Only administrators of the site can manage these settings', 'yith-woocommerce-review-for-discounts' ),
            'type' => 'title',
        ),
        'ywrfd_main_settings'      => array(
            'type' => 'ywrfd-vendor-panel',
        ),
        'ywrfd_main_section_end'   => array(
            'type' => 'sectionend',
        ),

        'ywrfd_email_review_section_title' => array(
            'name' => __( 'Email settings for single review', 'yith-woocommerce-review-for-discounts' ),
            'type' => 'title',
        ),
        'ywrfd_email_review_type'          => array(
            'name'    => __( 'Email type', 'yith-woocommerce-review-for-discounts' ),
            'type'    => 'select',
            'desc'    => __( 'Choose which email format you want to use', 'yith-woocommerce-review-for-discounts' ),
            'options' => array(
                'html'  => __( 'HTML', 'yith-woocommerce-review-for-discounts' ),
                'plain' => __( 'Plain text', 'yith-woocommerce-review-for-discounts' )
            ),
            'default' => 'html',
            'id'      => 'ywrfd_email_review_type_' . $vendor->id
        ),
        'ywrfd_email_review_subject'       => array(
            'name'              => __( 'Email subject', 'yith-woocommerce-review-for-discounts' ),
            'type'              => 'text',
            'desc'              => $placeholders_text . $ph_site_title . $ph_vendor_name . $ph_customer_name . $ph_customer_last_name . $ph_reference_link,
            'id'                => 'ywrfd_email_review_subject_' . $vendor->id,
            'default'           => __( 'You have received a coupon from {vendor_name} on {site_title}', 'yith-woocommerce-review-for-discounts' ),
            'class'             => 'ywrfd-text',
            'custom_attributes' => array(
                'required' => 'required'
            )
        ),
        'ywrfd_email_review_mailbody'      => array(
            'name'              => __( 'Email content', 'yith-woocommerce-review-for-discounts' ),
            'type'              => 'yith-wc-textarea',
            'desc'              => $placeholders_text . $ph_site_title . $ph_vendor_name . $ph_customer_name . $ph_customer_last_name . $ph_customer_email . $ph_product_name . $ph_coupon_description . $ph_reference_link,
            'id'                => 'ywrfd_email_review_mailbody_' . $vendor->id,
            'default'           => __( 'Hi {customer_name},
thanks for the review about {product_name}!
Because of this, we would like to offer you this coupon as a little gift:

{coupon_description}

See you on our shop,

{vendor_name}.', 'yith-woocommerce-review-for-discounts' ),
            'class'             => 'yith-wc-textarea',
            'custom_attributes' => array(
                'required' => 'required'
            )
        ),
        'ywrfd_email_review_test'          => array(
            'name'     => __( 'Test email', 'yith-woocommerce-review-for-discounts' ),
            'type'     => 'ywrfd-send',
            'field_id' => 'ywrfd_email_review_test',
            'class'    => 'review'
        ),
        'ywrfd_email_review_section_end'   => array(
            'type' => 'sectionend',
        ),

        'ywrfd_email_multiple_section_title' => array(
            'name' => __( 'Email settings for multiple reviews', 'yith-woocommerce-review-for-discounts' ),
            'type' => 'title',
        ),
        'ywrfd_email_multiple_type'          => array(
            'name'    => __( 'Email type', 'yith-woocommerce-review-for-discounts' ),
            'type'    => 'select',
            'desc'    => __( 'Choose which email format you want to use', 'yith-woocommerce-review-for-discounts' ),
            'options' => array(
                'html'  => __( 'HTML', 'yith-woocommerce-review-for-discounts' ),
                'plain' => __( 'Plain text', 'yith-woocommerce-review-for-discounts' )
            ),
            'default' => 'html',
            'id'      => 'ywrfd_email_multiple_type_' . $vendor->id
        ),
        'ywrfd_email_multiple_subject'       => array(
            'name'              => __( 'Email subject', 'yith-woocommerce-review-for-discounts' ),
            'type'              => 'text',
            'desc'              => $placeholders_text . $ph_site_title . $ph_vendor_name . $ph_customer_name . $ph_customer_last_name . $ph_reference_link,
            'id'                => 'ywrfd_email_multiple_subject_' . $vendor->id,
            'default'           => __( 'You have received a coupon from {vendor_name} on {site_title}', 'yith-woocommerce-review-for-discounts' ),
            'class'             => 'ywrfd-text',
            'custom_attributes' => array(
                'required' => 'required'
            )
        ),
        'ywrfd_email_multiple_mailbody'      => array(
            'name'              => __( 'Email content', 'yith-woocommerce-review-for-discounts' ),
            'type'              => 'yith-wc-textarea',
            'desc'              => $placeholders_text . $ph_site_title . $ph_vendor_name . $ph_customer_name . $ph_customer_last_name . $ph_customer_email . $ph_total_reviews . $ph_coupon_description . $ph_reference_link,
            'id'                => 'ywrfd_email_multiple_mailbody_' . $vendor->id,
            'default'           => __( 'Hi {customer_name},
with the latest review you have written {total_reviews} reviews!
Because of this, we would like to offer you this coupon as a little gift:

{coupon_description}

See you on our shop,

{vendor_name}.', 'yith-woocommerce-review-for-discounts' ),
            'class'             => 'yith-wc-textarea',
            'custom_attributes' => array(
                'required' => 'required'
            )
        ),
        'ywrfd_email_multiple_test'          => array(
            'name'     => __( 'Test email', 'yith-woocommerce-review-for-discounts' ),
            'type'     => 'ywrfd-send',
            'field_id' => 'ywrfd_email_multiple_test',
            'class'    => 'review'
        ),
        'ywrfd_email_multiple_section_end'   => array(
            'type' => 'sectionend',
        ),

        'ywrfd_email_notify_section_title' => array(
            'name' => __( 'Email settings for customer close to the target', 'yith-woocommerce-review-for-discounts' ),
            'type' => 'title',
        ),
        'ywrfd_email_notify_type'          => array(
            'name'    => __( 'Email type', 'yith-woocommerce-review-for-discounts' ),
            'type'    => 'select',
            'desc'    => __( 'Choose which email format you want to use', 'yith-woocommerce-review-for-discounts' ),
            'options' => array(
                'html'  => __( 'HTML', 'yith-woocommerce-review-for-discounts' ),
                'plain' => __( 'Plain text', 'yith-woocommerce-review-for-discounts' )
            ),
            'default' => 'html',
            'id'      => 'ywrfd_email_notify_type_' . $vendor->id
        ),
        'ywrfd_email_notify_subject'       => array(
            'name'              => __( 'Email subject', 'yith-woocommerce-review-for-discounts' ),
            'type'              => 'text',
            'desc'              => $placeholders_text . $ph_site_title . $ph_vendor_name . $ph_customer_name . $ph_customer_last_name . $ph_reference_link,
            'id'                => 'ywrfd_email_notify_subject_' . $vendor->id,
            'default'           => __( 'You are getting near to an important goal on {vendor_name} su {site_title}', 'yith-woocommerce-review-for-discounts' ),
            'class'             => 'ywrfd-text',
            'custom_attributes' => array(
                'required' => 'required'
            )
        ),
        'ywrfd_email_notify_mailbody'      => array(
            'name'              => __( 'Email content', 'yith-woocommerce-review-for-discounts' ),
            'type'              => 'yith-wc-textarea',
            'desc'              => $placeholders_text . $ph_site_title . $ph_vendor_name . $ph_customer_name . $ph_customer_last_name . $ph_customer_email . $ph_remaining_reviews . $ph_reference_link,
            'id'                => 'ywrfd_email_notify_mailbody_' . $vendor->id,
            'default'           => __( 'Hi {customer_name},
you need still {remaining_reviews} reviews and you will get a coupon for our shop!

See you on our shop,

{vendor_name}.', 'yith-woocommerce-review-for-discounts' ),
            'class'             => 'yith-wc-textarea',
            'custom_attributes' => array(
                'required' => 'required'
            )
        ),
        'ywrfd_email_notify_test'          => array(
            'name'     => __( 'Test email', 'yith-woocommerce-review-for-discounts' ),
            'type'     => 'ywrfd-send',
            'field_id' => 'ywrfd_email_notify_test',
            'class'    => 'review'
        ),
        'ywrfd_email_notify_section_end'   => array(
            'type' => 'sectionend',
        ),

    )

);