<?php

/** Registro de erros */
function u4crypto_registerError($erro) {
    try{
        $content = "[".date("Y-m-d H:i:s")."] - " . $erro.PHP_EOL;
        $file = U4CRYPTO_PLUGIN_DIR.'tmp/errors.json';
        $file_handle = fopen($file, "a");
        fwrite($file_handle, $content);
        fclose($file_handle);
    } catch(Exception $e){
        ?>
        <div class="notice notice-warning is-dismissible u4crypto-acf-notice">
            <p><?php  _e( "Não foi possível escrever o arquivo de erro - U4crypto: ".$e->getMessage(), "u4crypto" ); ?></p>
        </div>
        <?php
    }

}

/**
 * Register erros in notice
 */
function u4crypto_acf_notice() {
    $urlVerify = site_url( '', '' ).'/wp-admin/admin.php?page=wc-settings&tab=checkout&section=u4cryptoboleto';
    ?>
    <div class="notice notice-warning is-dismissible u4crypto-acf-notice">
        <p><?php  _e( "Please, verify if your data's API! <a href=\"".$urlVerify."\">Check my information - U4crypto</a>", "u4crypto_boleto" ); ?></p>
    </div>
    <?php
}
if(file_exists(U4CRYPTO_PLUGIN_DIR.'tmp/errors.log')) {
    add_action ('admin_notices', 'u4crypto_acf_notice');
    /**Delete archive of error */
    if(isset($_GET['section']) AND $_GET['section'] == 'u4cryptoboleto'){
        unlink(U4CRYPTO_PLUGIN_DIR.'tmp/errors.log');
    }
}