<?php
/**
 * Template Name: El meu compte
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div class="container my-account-page">
    <?php if ( ! is_user_logged_in() ) : ?>
        <h1><?php esc_html_e( 'El meu compte', 'fcsd' ); ?></h1>
        <p><?php esc_html_e( "Has d'iniciar sessió per veure les teves comandes.", 'fcsd' ); ?></p>
        <?php wp_login_form(); ?>
    <?php else : ?>
        <h1><?php esc_html_e( 'Les meves comandes', 'fcsd' ); ?></h1>

        <?php
        $orders = fcsd_Shop_Account::get_user_orders( get_current_user_id() );
        ?>

        <?php if ( empty( $orders ) ) : ?>
            <p><?php esc_html_e( "Encara no has fet cap comanda.", 'fcsd' ); ?></p>
        <?php else : ?>
            <table class="table my-orders-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'fcsd' ); ?></th>
                        <th><?php esc_html_e( 'Data', 'fcsd' ); ?></th>
                        <th><?php esc_html_e( 'Total', 'fcsd' ); ?></th>
                        <th><?php esc_html_e( 'Estat', 'fcsd' ); ?></th>
                        <th><?php esc_html_e( 'Accions', 'fcsd' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $orders as $order ) : ?>
                        <tr>
                            <td>#<?php echo esc_html( $order->id ); ?></td>
                            <td>
                                <?php
                                $ts = is_string( $order->created_at ) ? strtotime( $order->created_at ) : false;
                                echo esc_html( $ts ? date_i18n( get_option( 'date_format' ), $ts ) : (string) $order->created_at );
                                ?>
                            </td>
                            <td><?php echo esc_html( number_format_i18n( $order->total, 2 ) ); ?> €</td>
                            <td><?php echo esc_html( $order->status ); ?></td>
                            <td>
                                <?php
                                $url = add_query_arg(
                                    [
                                        'repeat_order'       => $order->id,
                                        'repeat_order_nonce' => wp_create_nonce( 'fcsd_repeat_order_' . $order->id ),
                                    ],
                                    get_permalink()
                                );
                                ?>
                                <a href="<?php echo esc_url( $url ); ?>" class="btn btn-sm btn-secondary">
                                    <?php esc_html_e( 'Repetir comanda', 'fcsd' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
get_footer();
