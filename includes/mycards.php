<?php

/**
 * Menu User e Page
 */
add_filter ( 'woocommerce_account_menu_items', 'u4crypto_more_link' );
function u4crypto_more_link( $menu_links ){
 
	$new = array( 'mycards' => 'Cartões' );
 
	// or in case you need 2 links
	// $new = array( 'link1' => 'Link 1', 'link2' => 'Link 2' );
 
	// array_slice() is good when you want to add an element between the other ones
	$menu_links = array_slice( $menu_links, 0, 5, true ) 
	+ $new 
	+ array_slice( $menu_links, 5, NULL, true );
 
	return $menu_links;
  
}

add_action( 'init', 'u4crypto_add_endpoint' );
function u4crypto_add_endpoint() {
 
	// WP_Rewrite is my Achilles' heel, so please do not ask me for detailed explanation
    add_rewrite_endpoint( 'mycards', EP_PAGES );
    // add_rewrite_endpoint( 'mycards', EP_ROOT | EP_PAGES );
    
}

add_action( 'woocommerce_account_mycards_endpoint', 'u4crypto_my_account_endpoint_content' );
function u4crypto_my_account_endpoint_content() {
 
	// of course you can print dynamic content here, one of the most useful functions here is get_current_user_id()
    echo '<h3>Seus cartões.</h3>';
    /**add_filter( 'page_template', 'mycards_page_template' ); */
 
}