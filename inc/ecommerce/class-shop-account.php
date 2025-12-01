<?php
// inc/ecommerce/class-shop-account.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class fcsd_Shop_Account {

    public static function get_user_orders( $user_id ) {
        $user  = get_userdata( $user_id );
        $email = '';

        if ( $user && ! empty( $user->user_email ) ) {
            $email = $user->user_email;
        }

        return fcsd_Shop_DB::get_orders_by_user( $user_id, $email );
    }


    public static function repeat_order( $order_id ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        if ( empty( $_GET['repeat_order_nonce'] ) ||
             ! wp_verify_nonce( $_GET['repeat_order_nonce'], 'fcsd_repeat_order_' . $order_id ) ) {
            return;
        }

        global $wpdb;
        $items_table = $wpdb->prefix . 'shop_order_items';

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT product_id, quantity FROM {$items_table} WHERE order_id = %d",
                $order_id
            )
        );

        if ( empty( $items ) ) {
            return;
        }

        $cart = [];
        foreach ( $items as $item ) {
            $cart[ $item->product_id ] = (int) $item->quantity;
        }

        fcsd_Shop_Cart::save_cart( $cart );

        $cart_page = get_page_by_path( 'carrito' );
        if ( $cart_page ) {
            wp_safe_redirect( get_permalink( $cart_page ) );
            exit;
        }
    }
}
