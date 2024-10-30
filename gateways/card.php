<?php

/**
 * Custom Payment Gateway Cards.
 *
 * Provides a Custom Payment Gateway, mainly for testing purposes.
 */
add_action('plugins_loaded', 'init_u4crypto_card_gateway_class', 0);
function init_u4crypto_card_gateway_class(){
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        add_action ('admin_notices', 'u4crypto_gateway_class_wc_notice');
        return;
    }

    class WC_Gateway_U4Crypto_Card extends WC_Payment_Gateway {

        public $domain;

        /**
         * Constructor for the gateway.
        */
        public function __construct() {

            $this->domain = 'u4crypto_card';

            $this->id                 = 'u4cryptocard';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'U4Crypto Card', $this->domain );
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
                    'label'   => __( 'Enable U4crypto Card', $this->domain ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', $this->domain ),
                    'default'     => __( 'U4crypto Card', $this->domain ),
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __( 'Order Status', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', $this->domain ),
                    'default'     => 'on-hold',
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
                'customerId' => array(
                    'title'       => __( 'Customer id', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'Para integração com a API da U4Crypto deve ser utilizada o Customerid', $this->domain ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'nameInvoice' => array(
                    'title'       => __( 'nameInvoice', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'Para integração com a API da U4Crypto deve ser utilizada o Customerid', $this->domain ),
                    'default'     => 'Nome do e-commerce',
                    'desc_tip'    => true,
                ),
                'interest' => array(
                    'title'       => __( 'interest', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'Juros ao mês ex: 1.2 será aplicado 1.2% ao mês, se uma compra no valor de R$100,00 for parcelada em 12x seria 12*1.2=14.4%, Totalizando R$114,40', $this->domain ),
                    'default'     => '0',
                    'desc_tip'    => true,
                ),
                'installmentMax' => array(
                    'title'       => __( 'installmentMax', $this->domain ),
                    'type'        => 'number',
                    'description' => __( 'Quantidade máxima de parcelas aceita', $this->domain ),
                    'default'     => '12',
                    'desc_tip'    => true,
                ),
                'u4cryptoenvironment' => array(
                    'title'       => __( 'Ambiente de produção' ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Should this payment use the Production route?', $this->domain ),
                    'default'     => 'Desativado',
                    'desc_tip'    => true,
                    'options'     => array('Ativado', 'Desativado')
                ),
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

            if ( $setings->settings['instructions'] && ! $sent_to_admin && 'u4cryptocard' == $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
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
							text-align: center !important;" target="_blank" href="'.$order->get_checkout_payment_url( $on_checkout = false ).'">'.__( 'Realizar o pagamento').'</a></p><br>'. PHP_EOL
                    );

            }


        }

        /**
		 * Get templates path.
		 *
		 * @return string
		 */
		public static function get_templates_path() {
			return plugin_dir_path( __FILE__ ) . '../templates/';
		}

        public function payment_fields(){

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }

            $cart_total = $this->get_order_total();
            $sets = WC_Gateway_U4Crypto_Card::getSettingsU4crypto();


            if($sets['settings'][0]['installmentMax'] != 0){
                for ($i=0; $i < $sets['settings'][0]['installmentMax']; $i++) {
                    if($i == 0){
                        $installmentMax[] = array(
                            "installment" => 1,
                            "installment_amount" => $cart_total * 100,
                            "amount" => $cart_total * 100
                        );
                    }elseif($i > 0){
                        $insta = $i+1;
                        if(
                            $sets['settings'][0]['interest'] != "0"
                        ){
                            $cal = ( (($cart_total / 100 * str_replace(',', '.',$sets['settings'][0]['interest']))*100) + ($cart_total * 100) ) / $insta;
                            $calAmount = ( (($cart_total / 100 * str_replace(',', '.',$sets['settings'][0]['interest']))*100) + ($cart_total * 100) );
                        }else{
                            $cal = $cart_total / $insta * 100;
                            $calAmount = $cart_total * 100;
                        }
                        $installmentMax[] = array(
                            "installment" => $insta,
                            "installment_amount" => $cal,
                            "amount" => $calAmount
                        );
                    }
                }
            }else{
                $installmentMax[] = array(
                    "installment" => 1,
                    "installment_amount" => $cart_total * 100,
                    "amount" => $cart_total * 100
                );
            }

            wc_get_template(
				'credit-card/payment-form.php',
				array(
					'cart_total'           => $cart_total,
					'max_installment'      => "3",
					'smallest_installment' => "1",
					'installments'         => $installmentMax,
				),
				'woocommerce/u4crypto/',
				WC_Gateway_U4Crypto_Card::get_templates_path()
			);


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
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }

        public function callback_handler(){
            $raw_post = file_get_contents( 'php://input' );
            //print_r($raw_post);
        }

        public static function getSettingsU4crypto(){
            global $wpdb;
            $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options
                WHERE option_name = 'woocommerce_u4cryptocard_settings'
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
add_filter( 'woocommerce_payment_gateways', 'add_u4crypto_card_gateway_class' );
function add_u4crypto_card_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_U4Crypto_Card';
    return $methods;
}

add_action('woocommerce_checkout_process', 'process_wc_u4crypto_card_payment', 10);
function process_wc_u4crypto_card_payment(){
    /** u4cryptocard */
    if($_POST['payment_method'] != 'u4cryptocard'){
        return;
    }

    if(isset($_POST['cardname']) && $_POST['cardname'] != ''){

    }else{
        // wc_add_notice( sprintf( '<strong>Nome no cartão</strong>, é obrigatório.' ), 'error' );
    }

    if(isset($_POST['cardnumber']) && $_POST['cardnumber'] != ''){

    }else{
        // wc_add_notice( sprintf( '<strong>Número do cartão</strong>, é obrigatório.' ), 'error' );
    }

    if(isset($_POST['carddate']) && $_POST['carddate'] != ''){

    }else{
        // wc_add_notice( sprintf( '<strong>Data de vencimento do cartão</strong>, é obrigatório.' ), 'error' );
    }

    if(isset($_POST['cardcode']) && $_POST['cardcode'] != ''){

    }else{
        // wc_add_notice( sprintf( '<strong>Código CVV do cartão</strong>, é obrigatório.' ), 'error' );
    }


}