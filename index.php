<?php
/*
	* Plugin Name: Cobrança U4crypto
	* Plugin URL: https://www.diletec.com.br
	* Description: Adiciona a U4Crypto como método de pagamento no seu WooCommerce
	* Version: 1.5.0
	* Author: Diletec
	* Author URI: https://www.diletec.com.br
    * Requires at least: 4.4
    * Requires PHP: 7.0
    * WC requires at least: 3.0.0
    * WC tested up to: 6.3.1
*/

/**
* https://stackoverflow.com/questions/17081483/custom-payment-method-in-woocommerce/37631908
* https://docs.woocommerce.com/document/payment-gateway-api/
* https://woocommerce.github.io/woocommerce-rest-api-docs/#order-properties
*/

if (class_exists( 'WC_Payment_Gateway' )){
    return;
}

/** Diretorio do Plugin no wp-content */
define('U4CRYPTO_PLUGIN_DIR', WP_CONTENT_DIR.'/cobranca-u4crypto/');
/** Verifica se o diretório existe */
if(!is_dir(U4CRYPTO_PLUGIN_DIR)){
    /** Cria o diretório */
    mkdir(U4CRYPTO_PLUGIN_DIR);
    mkdir(U4CRYPTO_PLUGIN_DIR.'tmp');
    mkdir(U4CRYPTO_PLUGIN_DIR.'pedidos');
    /** criar arquivo de log erros.json, cron.txt errors.log*/
    $file = U4CRYPTO_PLUGIN_DIR.'tmp/errors.json';
    $file_handle = fopen($file, "a");
    fclose($file_handle);
    $file = U4CRYPTO_PLUGIN_DIR.'tmp/cron.txt';
    $file_handle = fopen($file, "a");
    fclose($file_handle);
    $file = U4CRYPTO_PLUGIN_DIR.'tmp/errors.log';
    $file_handle = fopen($file, "a");
    fclose($file_handle);
}

/**
 * Load the plugin text domain for translation.
 */
function u4crypto_plugin_load_translator() {
    load_plugin_textdomain( 'u4crypto_boleto', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'u4crypto_plugin_load_translator' );

/**
* Register erros in notice
*/
include_once plugin_dir_path(__FILE__).'includes/registerError.php';

/**
* Load Gateways
*/
//include_once plugin_dir_path(__FILE__).'gateways/card.php';
include_once plugin_dir_path(__FILE__).'gateways/billet.php';
include_once plugin_dir_path(__FILE__).'gateways/pix.php';
include_once plugin_dir_path(__FILE__).'gateways/qrcode.php';
include_once plugin_dir_path(__FILE__).'add-ons/dokan/index.php';

/**
* Register Gateway ERP Billet
*/
include_once plugin_dir_path(__FILE__).'includes/gatewayErpBillet.php';

/**
* Operate U4Crypto on the order screen
*/
include_once plugin_dir_path(__FILE__).'includes/customerDisplay.php';

/**
* Display field value on the order edit page
*/
include_once plugin_dir_path(__FILE__).'includes/adminDisplay.php';


/**
* Cron U4Crypto Billet
*/
include_once plugin_dir_path(__FILE__).'includes/cancelErpBillet.php';

/**
 * Menu
 */
include_once plugin_dir_path(__FILE__).'includes/menu.php';

/**
* Card management
*/
//include_once plugin_dir_path(__FILE__).'includes/mycards.php';


/**
* Register settings link
*/
function u4crypto_show_plugin_settings_link($links, $file){
    $new_actions = array();
    if ( basename( plugin_dir_path( __FILE__ ) ) . '/index.php' === $file ) {
        $new_actions['u4_settings'] = sprintf( __( '<a href="%s">Settings</a>', 'comment-limiter' ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=u4cryptoboleto' ) ) );
    }

    return array_merge( $new_actions, $links );

}
add_filter('plugin_action_links', 'u4crypto_show_plugin_settings_link', 10, 2 );

/**
* Register style
*/
function u4crypto_btn_bille() {
    wp_enqueue_style( 'u4crypto-btn-billet', plugin_dir_url(__FILE__).'/assets/css/u4crypto-btn-billet.css' );
}
add_action( 'wp_enqueue_scripts', 'u4crypto_btn_bille' );

function u4crypto_gateway_class_wc_notice()
{
    $urlVerify = site_url( '', '' ).'/wp-admin/plugins.php?action=activate&plugin=woocommerce%2Fwoocommerce.php&plugin_status=active&_wpnonce=958e1b56e9';
    ?>
    <div class="notice notice-warning is-dismissible u4crypto-acf-notice">
        <p><?php  _e( "U4crypto depende da última versão do WooCommerce para funcionar!", "u4crypto" ); ?></p>
        <p><a class="button-primary" href='<?php _e($urlVerify); ?>'>Ativar Woocommerce</a></p>
    </div>
    <?php
}