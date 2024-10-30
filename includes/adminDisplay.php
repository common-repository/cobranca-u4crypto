<?php

/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'u4crypto_checkout_field_display_admin_order_meta', 10, 1 );
function u4crypto_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->get_id(), '_payment_method', true );
    if($method == 'u4cryptopix'){
        wc_u4crypto_update_pix_data($order);
        return;
    }
    if($method != 'u4cryptoboleto')
        return;

    /** Gerar um novo boleto com a data nova, ou gerar o boleto quando não existe um boleto */
    if(
        (get_post_meta( $order->get_id(), 'boletovencimento', true ) < date('Y-m-d') AND $order->get_status() == 'on-hold')
        OR get_post_meta( $order->get_id(), 'boletoid', true ) == ""
    ){
        /**
         * Regerar o boleto com uma data de vencimento
         */
        if(function_exists('wc_u4crypto_update_boleto_data')){
            wc_u4crypto_update_boleto_data($order);
        }
    }


    $boleto = get_post_meta( $order->get_id(), 'boleto', true );
    $transaction = get_post_meta( $order->get_id(), 'transaction', true );

    $boletoid = get_post_meta( $order->get_id(), 'boletoid', true );
    $documentNumber = get_post_meta( $order->get_id(), 'documentNumber', true );
    $link = get_post_meta( $order->get_id(), 'boletolink', true );

    echo '<p><strong>'.__( 'Billet digitable line', 'u4crypto_boleto' ).':</strong> ' . $boleto . '</p>';
    echo '<p><strong>'.__( 'Billet ID', 'u4crypto_boleto').':</strong> ' . $boletoid . '</p>';

    echo '<p><strong>'.__( 'Transaction ID', 'u4crypto_boleto').':</strong> ' . $transaction . '</p>';
    echo '<p><strong>'.__( 'Document Number', 'u4crypto_boleto').':</strong> ' . $documentNumber . '</p>';
    if($link != ''){ echo '<p><a target="_blank" href="'.$link.'">'.__( 'Download the billet', 'u4crypto_boleto').'</a></p>';}

}