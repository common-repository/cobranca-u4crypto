<?php
/**
 *  Dokan Dashboard Template
 *
 *  Dokan Main Dahsboard template for Fron-end
 *
 *  @since 2.4
 *
 *  @package dokan
 */
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    update_post_meta( dokan_get_current_user_id(), 'vendor-cnpj', $_POST['cnpj-split'] );
}

?>
<div class="dokan-dashboard-wrap">
    <?php
        /**
         *  dokan_dashboard_content_before hook
         *
         *  @hooked get_dashboard_side_navigation
         *
         *  @since 2.4
         */
        do_action( 'dokan_dashboard_content_before' );
    ?>

    <div class="dokan-dashboard-content">

        <?php
            /**
             *  dokan_dashboard_content_before hook
             *
             *  @hooked show_seller_dashboard_notice
             *
             *  @since 2.4
             */
            do_action( 'dokan_u4crypto_content_inside_before' );
        ?>

        <article class="dashboard-content-area">
            <div class="dashboard-widget">
                <form action="" method="post">
                    <div class="widget-title">
                        Pagamento por U4crypto
                    </div>
                    <p>CPF/CNPJ U4crypto</p>
                    <input value="<?php echo (isset(get_post_meta(dokan_get_current_user_id(),'vendor-cnpj')[0]))?get_post_meta(dokan_get_current_user_id(),'vendor-cnpj')[0]: ""?>" type="text" name="cnpj-split" class="dokan-form-control">
                    <br>
                    <button class="dokan-update-setting-top-button dokan-btn dokan-btn-theme dokan-right" type="submit">Enviar</button>
                    <br>
                    <br>
                </form>
            </div>
        	

        </article><!-- .dashboard-content-area -->

         <?php
            /**
             *  dokan_dashboard_content_inside_after hook
             *
             *  @since 2.4
             */
            do_action( 'dokan_dashboard_content_inside_after' );
        ?>


    </div><!-- .dokan-dashboard-content -->

    <?php
        /**
         *  dokan_dashboard_content_after hook
         *
         *  @since 2.4
         */
        do_action( 'dokan_dashboard_content_after' );
    ?>

</div><!-- .dokan-dashboard-wrap -->