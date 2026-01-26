<?php
// inc/ecommerce/class-shop-cart.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class fcsd_Shop_Cart {

    const CART_KEY = 'fcsd_cart';

    /**
     * Obtiene el carrito actual
     * 
     * @return array Carrito con estructura: [ 'product_id_hash' => [ 'product_id' => int, 'quantity' => int, 'attributes' => array ] ]
     */
    public static function get_cart() {
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $cart    = get_user_meta( $user_id, self::CART_KEY, true );
        } else {
            $cart = isset( $_SESSION[ self::CART_KEY ] ) ? $_SESSION[ self::CART_KEY ] : array();
        }

        if ( ! is_array( $cart ) ) {
            $cart = array();
        }
        
        // Verificar si hay estructura antigua y limpiarla
        if ( ! empty( $cart ) ) {
            foreach ( $cart as $key => $value ) {
                if ( ! is_array( $value ) ) {
                    // Estructura antigua detectada, resetear todo
                    $cart = array();
                    self::save_cart( $cart );
                    break;
                }
            }
        }

        return $cart;
    }

    /**
     * Guarda el carrito
     */
    public static function save_cart( $cart ) {
        if ( is_user_logged_in() ) {
            update_user_meta( get_current_user_id(), self::CART_KEY, $cart );
        } else {
            $_SESSION[ self::CART_KEY ] = $cart;
        }
    }

    /**
     * Genera un hash único para el producto con sus atributos
     */
    private static function get_cart_item_key( $product_id, $attributes = array() ) {
        $key = $product_id;
        if ( ! empty( $attributes ) ) {
            ksort( $attributes );
            $key .= '_' . md5( serialize( $attributes ) );
        }
        return $key;
    }

    /**
     * Añade un producto al carrito
     */
    public static function add_to_cart( $product_id, $quantity = 1, $attributes = array() ) {
        $product_id = absint( $product_id );
        $quantity   = max( 1, absint( $quantity ) );

        if ( ! $product_id ) {
            return false;
        }

        $cart = self::get_cart();
        
        // MIGRACIÓN: Verificar si hay estructura antigua y limpiarla
        $needs_reset = false;
        foreach ( $cart as $key => $value ) {
            // Si encontramos un valor que no es array, es estructura antigua
            if ( ! is_array( $value ) ) {
                $needs_reset = true;
                break;
            }
        }
        
        // Si hay estructura antigua, resetear el carrito
        if ( $needs_reset ) {
            $cart = array();
            self::save_cart( $cart );
        }
        
        $key = self::get_cart_item_key( $product_id, $attributes );

        if ( isset( $cart[ $key ] ) && is_array( $cart[ $key ] ) ) {
            $cart[ $key ]['quantity'] += $quantity;
        } else {
            $cart[ $key ] = array(
                'product_id' => $product_id,
                'quantity'   => $quantity,
                'attributes' => $attributes,
            );
        }

        self::save_cart( $cart );
        return true;
    }

    /**
     * Actualiza la cantidad de un producto en el carrito
     */
    public static function update_cart_item( $cart_key, $quantity ) {
        $quantity = max( 0, absint( $quantity ) );
        $cart     = self::get_cart();

        if ( isset( $cart[ $cart_key ] ) ) {
            if ( $quantity === 0 ) {
                unset( $cart[ $cart_key ] );
            } else {
                $cart[ $cart_key ]['quantity'] = $quantity;
            }
            self::save_cart( $cart );
            return true;
        }

        return false;
    }

    /**
     * Elimina un producto del carrito
     */
    public static function remove_from_cart( $cart_key ) {
        $cart = self::get_cart();

        if ( isset( $cart[ $cart_key ] ) ) {
            unset( $cart[ $cart_key ] );
            self::save_cart( $cart );
            return true;
        }

        return false;
    }

    /**
     * Vacía el carrito completamente
     */
    public static function clear_cart() {
        self::save_cart( array() );
    }

    /**
     * Obtiene el número total de productos en el carrito
     */
    public static function get_cart_count() {
        $cart = self::get_cart();
        $count = 0;
        
        if ( empty( $cart ) || ! is_array( $cart ) ) {
            return 0;
        }
        
        foreach ( $cart as $key => $item ) {
            // Verificar si es la estructura nueva (array con 'quantity')
            if ( is_array( $item ) && isset( $item['quantity'] ) ) {
                $count += absint( $item['quantity'] );
            }
            // Si es la estructura antigua (solo cantidad como valor entero)
            elseif ( is_int( $item ) || is_numeric( $item ) ) {
                $count += absint( $item );
            }
        }
        
        return $count;
    }

    /**
     * Obtiene los datos completos del carrito con información de productos
     */
    public static function get_cart_items_with_data() {
        $cart  = self::get_cart();
        $items = array();
        $total = 0;

        foreach ( $cart as $cart_key => $item ) {
            // Si es estructura antigua, convertirla
            if ( ! is_array( $item ) ) {
                continue;
            }

            $product_id = $item['product_id'];
            $quantity   = $item['quantity'];
            $attributes = isset( $item['attributes'] ) ? $item['attributes'] : array();

            // Obtener precio según tipo de usuario
            if ( function_exists( 'fcsd_get_product_prices' ) ) {
                $prices = fcsd_get_product_prices( $product_id );
            } else {
                $prices = array( 
                    'regular' => (float) get_post_meta( $product_id, '_price', true ),
                    'sale'    => 0,
                    'member'  => 0,
                );
            }

            $price = $prices['regular'];
            
            // Si hay precio de miembro y el usuario está logueado
            if ( is_user_logged_in() && ! empty( $prices['member'] ) && $prices['member'] > 0 ) {
                $price = $prices['member'];
            }
            // Si hay precio de oferta
            elseif ( ! empty( $prices['sale'] ) && $prices['sale'] > 0 && $prices['sale'] < $price ) {
                $price = $prices['sale'];
            }

            $subtotal = $price * $quantity;
            $total   += $subtotal;

            $items[] = array(
                'cart_key'    => $cart_key,
                'product_id'  => $product_id,
                'title'       => get_the_title( $product_id ),
                'permalink'   => get_permalink( $product_id ),
                'thumbnail'   => get_the_post_thumbnail_url( $product_id, 'thumbnail' ),
                'quantity'    => $quantity,
                'price'       => $price,
                'subtotal'    => $subtotal,
                'attributes'  => $attributes,
            );
        }

        return array(
            'items' => $items,
            'total' => $total,
        );
    }
}