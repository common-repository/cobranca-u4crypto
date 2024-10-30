<?php

/**
 * Custom Payment Gateway PIX.
 * @example https://hml-api.u4cdev.com/pix/docs#/
 * Provides a Custom Payment Gateway, mainly for testing purposes.
 */
add_action('plugins_loaded', 'init_u4crypto_pix_gateway_class', 0);
function init_u4crypto_pix_gateway_class(){

    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        add_action ('admin_notices', 'u4crypto_gateway_class_wc_notice');
        return;
    }

    class WC_Gateway_u4crypto_Pix extends WC_Payment_Gateway {

        public $domain;
        public $u4cryptoenvironment;

        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            $this->domain = 'u4crypto_pix';

            $this->id                 = 'u4cryptopix';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'U4Crypto PIX', $this->domain );
            $this->method_description = __( 'Aceite pagamentos utilizando o U4cripto.', $this->domain );

            // https://woocommerce.com/document/subscriptions/develop/payment-gateway-integration/
            $this->supports = array(
                'products',
                'subscriptions',
                'subscription_cancellation',
                'subscription_suspension',
                'subscription_reactivation',
                'subscription_amount_changes',
                'subscription_date_changes',
                'subscription_payment_method_change',
                'subscription_payment_method_change_customer',
                'subscription_payment_method_change_admin',
                'multiple_subscriptions',
             );

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

            // Sobcriptions
            add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, [ $this, 'scheduled_subscription_payment' ], 10, 2 );

        }


        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable U4crypto', $this->domain ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', $this->domain ),
                    'default'     => __( 'U4crypto', $this->domain ),
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
                'imediato' => array(
                    'title'       => __( 'Pix Imediato' ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Se sim, o Pix tem duração de 24h', $this->domain ),
                    'default'     => 'Sim',
                    'desc_tip'    => true,
                    'options'     => array('Sim', 'Não')
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

            if ( ! $sent_to_admin && 'u4cryptopix' == $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
				$this->u4crypto_registro_logs_pix('Envio de E-mail do Pedido: ', $order->get_id());
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
                        <input type="text" name="vat_u4c_pix" required="required">
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

        /**https://wordpress.stackexchange.com/questions/188481/callback-url-in-wordpress */
        public function callback_handler(){
            $raw_post = file_get_contents( 'php://input' );
            //print_r($raw_post);
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

        /**
         * Subscription
         */

        /**
         * Recurring Payments Processed by your Extension
         * Alguns gateways de pagamento podem ter pouco ou nenhum suporte para pagamentos recorrentes, mas podem permitir a cobrança de um cartão de crédito armazenado. Esses gateways ainda podem ser integrados ao WC Subscriptions para oferecer cobrança recorrente automática.
         * @param [type] $amount_to_charge
         * @param [type] $order
         * @param [type] $product_id
         * @return void
         */
        function scheduled_subscription_payment( $amount_to_charge, $order ) {

            $data_order = $order->get_data();
            if($data_order['payment_method'] != 'u4cryptopix'){
                return;
            }
            $this->u4crypto_registro_logs_pix('scheduled_subscription_payment Inicio: ', $order->get_id());
            $result = $this->process_subscription_payment( $order, $amount_to_charge );
            $this->u4crypto_registro_logs_pix($result, $order->get_id());

            // if ( is_wp_error( $result ) ) {
            //     WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
            // } else {
            //     WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
            // }
        }

        /** Registro de Logs Passo a Passo*/
		public static function u4crypto_registro_logs_pix($content, $ar){
			$archive = fopen(U4CRYPTO_PLUGIN_DIR.'pedidos/'.$ar.'.txt', 'a');
            try {
                fwrite($archive, '['.date("Y-m-d H:i:s").'] : '.$content . PHP_EOL);
                fclose($archive);
            } catch (\Throwable $th) {
                echo 'Sem permissões para escrever no arquivo.' . PHP_EOL;
                echo "Arquivo: ".$archive . PHP_EOL;
                echo "Mensagem: ".$content . PHP_EOL;
                throw $th;
                exit;
            }
		}

        /**
         * Recurring Payments Processed by your Extension - Exec
         * faz a cobrança recorrente automática.
         * @param [type] $order
         * @param [type] $amount_to
         * @return void
         */
        public function process_subscription_payment( $order, $amount_to)
        {
            $order_id = $order->get_id();
            $data_order = $order->get_data();

            $setings = wc_get_payment_gateway_by_order( $order_id );
            $api = $setings->settings['apiToken'];
            $partner = $setings->settings['partnerToken'];
            $customer = $setings->settings['customerToken'];

            // $order = wc_get_order( $order_id );
            if($order->get_meta('_billing_cpf') != "" AND strlen($order->get_meta('_billing_cpf')) != 0){
                $vat = filter_var($order->get_meta('_billing_cpf'), FILTER_SANITIZE_STRING);
                $nameCustomer = $order->get_billing_first_name().' '.$order->get_billing_last_name();
            }
            if($order->get_meta('_billing_cnpj') != "" AND strlen($order->get_meta('_billing_cnpj')) != 0){
                if($order->get_meta('_billing_persontype') == 2){
                    $vat = filter_var($order->get_meta('_billing_cnpj'), FILTER_SANITIZE_STRING);
                    $nameCustomer = filter_var($order->get_meta('_billing_company'), FILTER_SANITIZE_STRING);
                    // wc_add_notice( sprintf( '<strong>Nome da empresa</strong> é obrigatório.' ), 'error' );
                }
            }


            /**Tratar o número do endereço com o Plugin Brazilian e sem ele */
            if($order->get_meta('_billing_number') != ""){
                $number = $order->get_meta('_billing_number');
            }elseif(isset($data_order['billing']['address_1'])){
                if(is_int(preg_replace("/[^0-9]/", "", $data_order['billing']['address_1']))){
                    $number = preg_replace("/[^0-9]/", "", $data_order['billing']['address_1']);
                }else{
                    $number = "0";
                }
            }else{
                $number = "0";
            }

            /**
            * Generator Pix API
            */
            $bpcode = filter_var($data_order['billing']['postcode'], FILTER_SANITIZE_STRING);
            $urlCep = "https://viacep.com.br/ws/".$bpcode."/json/";
            $argsCep = array(
                'timeout'       => 30,
                'method'        => 'GET'
            );
            $http = _wp_http_get_object();
            $end = $http->get($urlCep, $argsCep);
            $end = json_decode($end['body']);

            if ( class_exists( 'WeDevs_Dokan' ) ) {
                $vendor_id = dokan_get_seller_id_by_order( $order->get_id() );
            }
            else {
                $vendor_id = NULL;
            }
            if($vendor_id != NULL){
                $split = [
                    "documentNumber"=> get_post_meta($vendor_id, 'vendor-cnpj')[0],
                    "percentValue"=> true,
                    "taxValue"=> get_option( 'dokan_selling', array() )["admin_percentage"],
                    "value"=> floatval($data_order['total'])
                ];
            }

            $postData = [
                "customerId"=>$customer,
                "externalId" => "$order_id",
                "additionalInformation"=> [
                    [
                        "content"=> "$order_id"
                    ]
                ],
                "dynamicQRCodeType"=> "BILLING_DUE_DATE",
                "billingDueDate"=>[
                    "dueDate"=>date('Y-m-d'),
                    "daysAfterDueDate"=>102,
                    "payerInformation"=>[
                        "name"=>$nameCustomer,
                        "cpfCnpj"=>preg_replace("/[^0-9]/", "", $vat),
                        "addressing"=>[
                            "street"=>$end->logradouro.' '.$number.' '.$end->bairro,
                            "city"=>$end->localidade,
                            "uf"=> $end->uf,
                            "cep"=>preg_replace("/[^0-9]/", "", $bpcode)
                        ]
                    ],
                    "paymentValue"=>[
                        "documentValue"=>floatval($order->get_total()),
                        "discounts"=>[
                            "modality"=>1,
                            "fixedDateDiscounts"=>[

                            ]
                        ],
                        "fines"=>[
                            "modality"=>1,
                            "valuePerc"=>1
                        ],
                        "interests"=>[
                            "modality"=>2,
                            "valuePerc"=>0.5
                        ]
                    ]
                ]
            ];

            /**Trata a url de Produção e de Desenvolvimento
             * https://hml-api.u4cdev.com/pix/docs#!/brcode/postPixBrcodeErpDynamic
             * https://hml-api.u4cdev.com/pix/brcode/erp/dynamic
            */
            ($setings->settings['u4cryptoenvironment'] === "0")? $url = U4CYPTO_PRO : $url = U4CYPTO_DEV;
            $urlBillet = $url.'/pix/brcode/erp/dynamic';

            $argsBillet = array(
                'timeout'       => 30,
                'method'        => 'POST',
                'httpversion'   => '1.1',
                'body'          => json_encode( $postData ),
                'headers'       => [
                    "partner" => $partner,
                    "token" => $api,
                    "Content-Type" => "application/json"
                ],
            );

            $this->u4crypto_registro_logs_pix('Request U4Crypto: ', $order->get_id());
            $http = _wp_http_get_object();
            $tuBillet = $http->post($urlBillet, $argsBillet);

            $response = json_decode( sanitize_text_field( $tuBillet['body'] ) );
            if(isset($response->message) AND $response->message == "Ocorreu um erro inesperado."){
                $this->u4crypto_registro_logs_pix('U4crypto Erro: '.$response->message, $order->get_id());
                // wc_add_notice( sprintf( '<strong>PIX:</strong> sistema indisponível, tente mais tarde ou escolha outro método de pagamento.' ), 'error' );
            }

            if(isset($response->data)){
                update_post_meta( $order_id, 'u4cryptoItemId', $response->itemId );
                /**textContent = O código do Pix*/
                update_post_meta( $order_id, 'u4cryptoTextContent', $response->data->textContent );
                update_post_meta( $order_id, 'u4cryptoImageContent', $response->data->generatedImage->imageContent );
                update_post_meta( $order_id, 'u4cryptoQrcodeURL', $response->data->qrcodeURL );
                update_post_meta( $order_id, 'u4cryptoReference', $response->data->reference );

                /**
                * informativo para atualizar o status inicial no envio de e-mail
                * if(0) atualiza o u4cryptoPaymentStart para 1 e atualiza o status do pedido para aguardando pagamento.
                */
                update_post_meta( $order_id, 'u4cryptoPaymentStart', 0 );
                //var_dump($response); exit;
                $this->u4crypto_registro_logs_pix('U4crypto Item ID: '.$response->itemId, $order->get_id());
                return "u4cryptoItemId: ".$response->itemId;
            }else{
                update_post_meta( $order_id, 'u4cryptoItemId', '' );
                /**textContent = O código do Pix*/
                update_post_meta( $order_id, 'u4cryptoTextContent', '' );
                update_post_meta( $order_id, 'u4cryptoImageContent', '' );
                update_post_meta( $order_id, 'u4cryptoQrcodeURL', '' );
                update_post_meta( $order_id, 'u4cryptoReference', '' );

                /**Creat archive of error */
                $content = json_encode( ["error" => $response, "data" => $postData], true );
                u4crypto_registerError($tuBillet["response"]["message"].": ".$tuBillet["response"]["code"]." - Item: Pix de Subscrição - Subscription "." - Resposta: ".$content);
                $this->u4crypto_registro_logs_pix($content, $order->get_id());
                return $content;
            }


        }


    }
}

/**
* Add U4Crypto as a gateway method
*/
add_filter( 'woocommerce_payment_gateways', 'add_u4crypto_gateway_pix_class' );
function add_u4crypto_gateway_pix_class( $methods ) {
    $methods[] = 'WC_Gateway_u4crypto_Pix';
    return $methods;
}