<?php

/**
 * Custom Payment Gateway U4Crypto.
 * Provides a Custom Payment Gateway, mainly for testing purposes.
 */

add_action('plugins_loaded', 'init_u4crypto_qrcode_gateway_class', 0);
function init_u4crypto_qrcode_gateway_class(){
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        add_action ('admin_notices', 'u4crypto_gateway_class_wc_notice');
        return;
    }

    class WC_Gateway_u4crypto_Qrcode extends WC_Payment_Gateway {

        public $domain;
        public $u4cryptoenvironment;

        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            $this->domain = 'u4crypto_qrcode';

            $this->id                 = 'u4cryptoqrcode';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'U4Crypto QRCode', $this->domain );
            $this->method_description = __( 'Aceite pagamentos utilizando o U4cripto.', $this->domain );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->instructions = $this->get_option( 'instructions', $this->description );
            $this->order_status = $this->get_option( 'order_status', 'on-hold' );

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

            $plugin = plugin_basename( __FILE__ );
            // Initialize settings.
        }

        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable U4crypto QRCode', $this->domain ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', $this->domain ),
                    'default'     => __( 'U4crypto QRcode', $this->domain ),
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __( 'Order Status', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', $this->domain ),
                    'default'     => 'wc-on-hold',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                    'title'       => __( 'Description', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', $this->domain ),
                    'default'     => __('Payment Information', $this->domain),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __( 'Instructions', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', $this->domain ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sales_security' => array(
                    'title'       => __( 'Sales Security', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Segurança de vendas da U4Crypto', $this->domain ),
                    'default'     => 'false',
                    'desc_tip'    => true,
                    'options'     => array('false', 'true')
                ),
                'apiToken' => array(
                    'title'       => __( 'API Token', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'Para integração com a API da U4Crypto deve ser utilizada a chave API Token', $this->domain ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'partnerToken' => array(
                    'title'       => __( 'Partner Token', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'Para integração com a API da U4Crypto deve ser utilizada a chave Partner Token', $this->domain ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'customerToken' => array(
                    'title'       => __( 'Customer Token', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'Para integração com a API da U4Crypto deve ser utilizada a chave Customer Token', $this->domain ),
                    'default'     => '',
                    'desc_tip'    => true,
                )
            );
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page() {
            if ( $this->instructions )
                return wpautop( wptexturize( $this->instructions ) );
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

            $setings = wc_get_payment_gateway_by_order( $order->get_id() );
            $base    = get_option( 'woocommerce_email_base_color' );

            if ( ! $sent_to_admin && 'u4cryptoqrcode' == $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
                echo wp_kses_post(
                        /**Instruções configuradas pelo Adm */
                        wpautop(
                            wptexturize( $setings->settings['instructions'] )
                        ) . PHP_EOL
                        /**Link do boleto */
                        .'<br><p class="billetdownload"><a style="
							font-weight: 700 !important;
							color: white !important;
							font-size: larger !important;
							padding: 15px !important;
							background-color: '.$base.' !important;
							margin: 10px 0px 10px 0px !important;
							display: block !important;
							text-decoration: none !important;
							max-width: 50% !important;
							text-align: center !important;"
							target="_blank" href="'.$order->get_view_order_url().'">'.__( 'Realizar o pagamento').'</a></p><br>'. PHP_EOL
                    );

            }

        }

        /**
         * Add field Vat (cpf, cnpj)
         */
        public function payment_fields(){

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }
            if (!class_exists( 'Extra_Checkout_Fields_For_Brazil' )){
                ?>
                <div id="custom_input_u4">
                    <div class="form-group">
                        <label>
                            CPF/CNPJ
                            <abbr class="required" title="obrigatório">*</abbr>
                        </label><br/>
                        <input type="text" name="vat_u4c_qrcode" required="required">
                    </div>
                </div>
                <?php
            }

        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {

            $order = wc_get_order( $order_id );

            $status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;

            // Set order status
            $order->update_status( $status, __( 'Checkout with U4crypto. ', $this->domain ) );

            // Reduce stock levels
            wc_reduce_stock_levels( $order_id );

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }

        public function getSettingsU4crypto(){
            global $wpdb;
            $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options
                WHERE option_name = 'woocommerce_u4cryptopix_settings'
            ");

            return [
                "settings" => [
                    unserialize( $results[0]->option_value )
                ]
            ];
        }


    }


}

/**
* Add U4Crypto as a gateway method
*/
add_filter( 'woocommerce_payment_gateways', 'add_u4crypto_gateway_qrcode_class' );
function add_u4crypto_gateway_qrcode_class( $methods ) {
    $methods[] = 'WC_Gateway_u4crypto_Qrcode';
    return $methods;
}