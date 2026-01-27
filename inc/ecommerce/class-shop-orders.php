<?php
// inc/ecommerce/class-shop-orders.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class fcsd_Shop_Orders {

    /**
     * Procesa el formulario de checkout, crea la comanda
     * i redirigeix a la pàgina de "pedido completado".
     */
    public static function handle_checkout() {
        if ( empty( $_POST['fcsd_checkout_nonce'] ) ||
             ! wp_verify_nonce( $_POST['fcsd_checkout_nonce'], 'fcsd_process_checkout' ) ) {
            return;
        }

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        if ( ! is_email( $email ) ) {
            // En el futur es podria guardar un missatge d'error en transient / sessió.
            return;
        }

        // Resum del carret (productes, quantitats, total...)
        $cart_data = fcsd_Shop_Cart::get_cart_items_with_data();

        if ( empty( $cart_data['items'] ) ) {
            return;
        }

        // Aplicar descomptes segons l'usuari
        $total_con_descuento = fcsd_Shop_Discounts::apply_user_discounts( $cart_data['total'] );
        $user_id             = is_user_logged_in() ? get_current_user_id() : null;

        // Crear comanda a la BD
        $order_id = fcsd_Shop_DB::insert_order(
            [
                'user_id' => $user_id,
                'email'   => $email,
                'total'   => $total_con_descuento,
            ],
            $cart_data['items']
        );

        if ( $order_id ) {
            // 1) Enviar email de resum de comanda al client.
            self::send_order_email( $email, $order_id, $cart_data, $total_con_descuento );

            // 2) Buidar carret
            fcsd_Shop_Cart::save_cart( [] );

            // 3) Redirigir a la pàgina de "Pedido completado"
            $success_page = get_page_by_path( 'pedido-completado' );

            if ( $success_page ) {
                $url = add_query_arg(
                    'order_id',
                    $order_id,
                    get_permalink( $success_page )
                );
                wp_safe_redirect( $url );
                exit;
            }
        }
    }

    /**
     * Envia un email de resum de comanda al client.
     *
     * @param string $email   Email del client.
     * @param int    $order_id ID de la comanda.
     * @param array  $cart_data Dades del carret: ['items' => [], 'total' => float].
     * @param float  $total_con_descuento Total final amb descomptes aplicats.
     */
    public static function send_order_email( $email, $order_id, $cart_data, $total_con_descuento ) {
        if ( empty( $email ) || ! is_email( $email ) ) {
            return;
        }

        $subject = sprintf(
            __( 'Resum de la teva comanda #%d', 'fcsd' ),
            $order_id
        );

        $lines = [];

        $lines[] = __( 'Hola,', 'fcsd' );
        $lines[] = '';
        $lines[] = __( 'Gràcies per la teva compra a FCSD.', 'fcsd' );
        $lines[] = '';
        $lines[] = __( 'Detall de la comanda:', 'fcsd' );
        $lines[] = '';

        if ( ! empty( $cart_data['items'] ) && is_array( $cart_data['items'] ) ) {
            foreach ( $cart_data['items'] as $item ) {
                $title    = isset( $item['title'] ) ? $item['title'] : '';
                $quantity = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
                $subtotal = isset( $item['subtotal'] ) ? (float) $item['subtotal'] : 0;

                if ( $title ) {
                    $lines[] = sprintf(
                        '- %1$s x%2$d — %3$s €',
                        $title,
                        $quantity,
                        number_format_i18n( $subtotal, 2 )
                    );
                }
            }
        }

        $lines[] = '';
        $lines[] = sprintf(
            __( 'Total (amb descomptes aplicats): %s €', 'fcsd' ),
            number_format_i18n( (float) $total_con_descuento, 2 )
        );
        $lines[] = '';

        // Enllaç a la pàgina de perfil o "Mi cuenta", si existeix.
        $profile_page = get_page_by_path( 'perfil-usuari' );
        if ( ! $profile_page ) {
            $profile_page = get_page_by_path( 'mi-cuenta' );
        }

        if ( $profile_page ) {
            $lines[] = sprintf(
                __( 'Pots consultar les teves comandes a: %s', 'fcsd' ),
                get_permalink( $profile_page )
            );
        }

        $message = implode( "\n", $lines );

        wp_mail( $email, $subject, $message );
    }
}
