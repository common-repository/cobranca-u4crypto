<?php

/**
 * https://developer.wordpress.org/plugins/cron/scheduling-wp-cron-events/
 * https://developer.wordpress.org/reference/functions/wp_next_scheduled/
*/
function u4crypto_cron_schedule( $schedules ) {
    $schedules['u4crypto_billet_cancel'] = array(
        'interval' => 3600, //3600,
        'display'  => __( 'A cada hora' ),
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'u4crypto_cron_schedule' );

if ( ! wp_next_scheduled( 'u4crypto_cron_hook' ) ) {
    wp_schedule_event( time(), 'u4crypto_billet_cancel', 'u4crypto_cron_hook' );
}

add_action('u4crypto_cron_hook', 'u4crypto_billet_cron');

function u4crypto_billet_cron(){

    /**
     * https://stackoverflow.com/questions/45848249/woocommerce-get-all-orders-for-a-product
     */
    global $wpdb;
    $results = $wpdb->get_results("
        SELECT *
        FROM {$wpdb->prefix}posts AS post

        WHERE post.post_type = 'shop_order'
        AND post.post_status = 'wc-pending'
        ORDER BY post.ID

        LIMIT 1
    ");

    /**wp_postmeta AS pmetas ON pmetas.post_id = posts.ID */
    $archive = fopen(U4CRYPTO_PLUGIN_DIR.'tmp/cron.txt', 'w');
    fwrite($archive, json_encode( $results, true ) );
    fclose($archive);

}