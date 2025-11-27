<?php
// inc/ecommerce/class-shop-discounts.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class fcsd_Shop_Discounts {

    public static function apply_user_discounts( $total ) {
        if ( ! is_user_logged_in() ) {
            return $total;
        }

        // Ejemplo básico: 10% de descuento a usuarios logueados
        $discounted = $total * 0.9;

        return round( $discounted, 2 );
    }
}
