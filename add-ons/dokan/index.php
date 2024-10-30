<?php
// Menu Dokan
add_filter( 'dokan_query_var_filter', 'dokan_load_document_menu_u4' );
function dokan_load_document_menu_u4( $query_vars ) {
    $query_vars['u4crypto'] = 'u4crypto';
    return $query_vars;
}

add_filter( 'dokan_get_dashboard_nav', 'dokan_add_u4crypto_menu_u4' );
function dokan_add_u4crypto_menu_u4( $urls ) {
    $urls['u4crypto'] = array(
        'title' => __( 'Pagamento U4crypto', 'dokan'),
        'icon'  => '<i class="fa fa-credit-card"></i>',
        'url'   => dokan_get_navigation_url( 'u4crypto' ),
        'pos'   => 51
    );
    return $urls;
}

add_action( 'dokan_load_custom_template', 'dokan_load_template_u4' );
function dokan_load_template_u4( $query_vars ) {
    if ( isset( $query_vars['u4crypto'] ) ) {
        require_once dirname( __FILE__ ). '/u4crypto.php';
       }
}