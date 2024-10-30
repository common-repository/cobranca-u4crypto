<?php
/** Registra o Menu no Admin do Wordpress */

function u4crypto_menu() {
    add_menu_page( 'U4crypto', 'U4crypto', 'manage_options', 'u4crypto', 'u4crypto_page', 'dashicons-bank', '7' );
    /** Submenu de Status */
    add_submenu_page( 'u4crypto', 'Status', 'Status', 'manage_options', 'u4crypto-status', 'u4crypto_page_status' );
    /** Submenu de Configurações */
    add_submenu_page( 'u4crypto', 'Configurações Boleto', 'Configurações Boleto', 'manage_options', site_url('', '').'/wp-admin/admin.php?page=wc-settings&tab=checkout&section=u4cryptoboleto' );
    /** Submenu de Configurações */
    add_submenu_page( 'u4crypto', 'Configurações Pix', 'Configurações Pix', 'manage_options', site_url('', '').'/wp-admin/admin.php?page=wc-settings&tab=checkout&section=u4cryptopix' );
    /** Submenu de Configurações */
    add_submenu_page( 'u4crypto', 'Configurações QRCode', 'Configurações QRCode', 'manage_options', site_url('', '').'/wp-admin/admin.php?page=wc-settings&tab=checkout&section=u4cryptoqrcode' );
    /** Submenu de Logs */
    add_submenu_page( 'u4crypto', 'Logs', 'Logs', 'manage_options', 'u4crypto-logs', 'u4crypto_page_logs' );
}
add_action( 'admin_menu', 'u4crypto_menu' );

/** Inclui os arquivos de Menus */
include_once(plugin_dir_path(__DIR__)."/admin/logs.php");
include_once(plugin_dir_path(__DIR__)."/admin/status.php");
include_once(plugin_dir_path(__DIR__)."/admin/u4crypto.php");