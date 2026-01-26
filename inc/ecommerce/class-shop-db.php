<?php
// inc/ecommerce/class-shop-db.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class fcsd_Shop_DB {

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $orders_table    = $wpdb->prefix . 'shop_orders';
        $items_table     = $wpdb->prefix . 'shop_order_items';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql_orders = "CREATE TABLE {$orders_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NULL,
            email VARCHAR(190) NOT NULL,
            total DECIMAL(10,2) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'completed',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) {$charset_collate};";

        $sql_items = "CREATE TABLE {$items_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            quantity INT(11) NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY product_id (product_id)
        ) {$charset_collate};";

        dbDelta( $sql_orders );
        dbDelta( $sql_items );
    }

    public static function insert_order( $data, $items ) {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'shop_orders';
        $items_table  = $wpdb->prefix . 'shop_order_items';

        $wpdb->query( 'START TRANSACTION' );

        $inserted = $wpdb->insert(
            $orders_table,
            [
                'user_id'    => $data['user_id'],
                'email'      => $data['email'],
                'total'      => $data['total'],
                'status'     => 'completed',
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%f', '%s', '%s', '%s' ]
        );

        if ( ! $inserted ) {
            $wpdb->query( 'ROLLBACK' );
            return false;
        }

        $order_id = $wpdb->insert_id;

        foreach ( $items as $item ) {
            $wpdb->insert(
                $items_table,
                [
                    'order_id'   => $order_id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ],
                [ '%d', '%d', '%d', '%f' ]
            );
        }

        $wpdb->query( 'COMMIT' );

        return $order_id;
    }

    public static function get_orders_by_user( $user_id, $user_email = '' ) {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'shop_orders';

        $where  = [];
        $params = [];

        // Buscar por user_id si lo tenemos
        if ( ! empty( $user_id ) ) {
            $where[]  = 'user_id = %d';
            $params[] = (int) $user_id;
        }

        // Además, buscar por email si lo tenemos
        if ( ! empty( $user_email ) ) {
            $where[]  = 'email = %s';
            $params[] = $user_email;
        }

        // Si no hay ni id ni email, no buscamos nada
        if ( empty( $where ) ) {
            return [];
        }

        $sql = "SELECT * FROM {$orders_table} WHERE " . implode( ' OR ', $where ) . " ORDER BY created_at DESC";

        return $wpdb->get_results(
            $wpdb->prepare( $sql, ...$params )
        );
    }

}
