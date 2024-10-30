<?php

/**
 * Custom Payment Gateway.
 *
 * Provides a Custom Payment Gateway, mainly for testing purposes.
 */
add_action('plugins_loaded', 'init_u4crypto_gateway_class', 0);
function init_u4crypto_gateway_class(){
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        add_action ('admin_notices', 'u4crypto_gateway_class_wc_notice');
        return;
    }

    class WC_Gateway_U4Crypto extends WC_Payment_Gateway {

        public $domain;
        public $u4cryptoenvironment;


        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            $this->domain = 'u4crypto_boleto';

            $this->id                 = 'u4cryptoboleto';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'U4Crypto ', $this->domain );
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
            $this->u4cryptoenvironment = $this->get_option( 'u4cryptoenvironment' );
            $this->u4cryptovencimento = $this->get_option( 'u4cryptovencimento' );

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

            $plugin = plugin_basename( __FILE__ );
            // Initialize settings.

            /**CallBack
             * wc_u4crypto
             * http://yoursite.com/?wc-api=wc_u4crypto
             * http://yoursite.com/wc-api/wc_u4crypto/
            */
            add_action( 'woocommerce_api_wc_u4crypto', array( $this, 'webhook') );

            add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, [ $this, 'scheduled_subscription_payment' ], 10, 2 );

            /**
             * https://wisdmlabs.com/blog/custom-status-woocommerce-subscription/
            */
            //  First step it to register new status in subscription using the woocommerce_subscriptions_registered_statuses action as shown below:
            //add_filter('woocommerce_subscriptions_registered_statuses', array( $this, 'register_new_post_status'), 100, 1);

            //In order to make this status visible in the status drop-down on the subscription page, you need to add it to the subscription statuses list using the ‘wcs_subscription_statuses’ filter as shown below:
            //add_filter('wcs_subscription_statuses', array( $this, 'add_new_subscription_statuses'), 100, 1);

            //For the visibility of the new status in the drop-down you need to specify if the subscription with a particular status can be updated to the new status or not. This can be done using the ‘woocommerce_subscription_status_updated’.
            //add_filter('woocommerce_can_subscription_be_updated_to', array( $this, 'extends_can_be_updated'), 100, 3);

            // Now, in order to perform an action when admin selects `Like On Hold` from the drop-down and updates the subscription we have to extend update_status function using ‘woocommerce_subscription_status_updated’ action. Here we will just copy paste the same code as on-hold status.
            //add_action('woocommerce_subscription_status_updated', array( $this, 'extends_update_status'), 100, 3);

            // Here ends the basic code to set up a new status called `Like On Hold` in WooCommerce subscription.
            //add_filter('woocommerce_can_subscription_be_updated_to_active', array( $this, 'enable_active_in_new_statuses'), 100, 2);
            //add_filter('woocommerce_can_subscription_be_updated_to_on-hold', array( $this, 'enable_on_hold_in_new_statuses'), 100, 2);

            // Adding the new status to bulk action drop-down on the subscription list page using ‘woocommerce_subscription_bulk_actions’ filter.
            //add_filter('woocommerce_subscription_bulk_actions', array( $this, 'add_new_status_bulk_actions'), 100, 1);

            // The following code deals with handling bulk actions. The style is similar to what WooCommerce is doing. Extensions will have to define their own logic by copying the concept behind the method written in woocommerce subscription plugin. The function is hooked to wordpress core’s ‘load-edit.php’ action.
            //add_action('load-edit.php', array( $this, 'parse_bulk_actions'));

            //Woo
            // Register new status in WooCommerce
            //add_action('init', array($this, 'register_on_hold_order_statuses'));
            // Add this new status to the order statuses list using the ‘wc_order_statuses’ filter
            //add_filter('wc_order_statuses', array($this, 'on_hold_wc_order_statuses'), 100, 1);
            // Once the order status is changed to the new status we want the status of all the subscription within the order to be changed to the new status. We can do this using the ‘woocommerce_order_status_’. $new_status action as shown below.
            //add_action('woocommerce_order_status_on-hold', array($this, 'put_subscription_on_hold_for_order'), 100);

            //$order_id = 1350;

			// Get the order object
			//$order = new WC_Order( $order_id );


 			try{
 			    // $subscription = wcs_get_subscription("15891");
 			    // $dates['start_date'] = gmdate( 'Y-m-d H:i:s' );
 			    // $subscription->update_dates( $dates );
 			    // $subscription->update_status('active');
 			    // woocommerce_subscription_status_active
 			    // $order = wc_get_order("15891");
 			    // $order->update_status('processing');

 			    // $subscription = new WC_Subscription( "15891" );
 			    // $subscription = new WC_Order( "15891" );
 			    // $subscription->update_dates( $dates );
 			    // $subscription->update_status('wc-active');

 			}catch (Exception $e) {
 			    echo 'Exceção capturada: ',  $e->getMessage(), "\n";
 			}
 			// $subscription->update_status( 'active' );
            // $subscription->can_be_updated_to( 'on-hold' );
            // $order = wc_get_order( "10496" );
            //var_dump($order); exit;
            // try{
            //     do_action( 'woocommerce_order_action_wcs_process_renewal', $order );
            // }catch (Exception $e) {
            //     echo 'Exceção capturada: ',  $e->getMessage(), "\n";
            // }
            // woocommerce_scheduled_subscription_payment("15891");
            // WC_Subscriptions_Manager::prepare_renewal(15891);
            // add_action('', 15891);

        }


        /**
        * CallBack
        * wc_u4crypto/
        */
        public function webhook(){
			$data = json_decode(file_get_contents("php://input"),true);
			$headers = getallheaders();
			$id = "";
			$externalId = "";
			(isset($headers['Partner']))? $token = sanitize_text_field($headers['Partner']) : $token = '';
            if(($token == "" OR $token == NULL) AND isset($headers['partner'])){
                $token = sanitize_text_field($headers['partner']);
            }

			if(isset($data["id"])){
				$id = sanitize_text_field($data["id"]);
			}
			if(isset($data["externalId"])){
				$externalId = sanitize_text_field($data["externalId"]);
			}
			if(isset($data["token"])){
                /**Verificar se token é uma string */
                if(is_string($data['token'])){
                    $token = sanitize_text_field($data['token']);
                }
			}

            /**Pegar as informações do Gateway*/
            ($id != "")? $confereId = $id : $confereId = $externalId;
            $setings = wc_get_payment_gateway_by_order( $confereId );
            $this->u4crypto_registro_logs_billet('CallBack Recebido DATA:'.json_encode($data).' HEADERS:'.json_encode($headers), $confereId);

            $this->u4crypto_registro_logs_billet('Precisa de ID:'.$externalId.' Token ou Patner:'.$token, $confereId);

            /**Existe o post com os dados?
             * PIX
            */
            if( isset($data["externalId"]) ){

				/**Remover os ! desse IF*/
                if( isset($setings->settings['partnerToken']) AND $setings->settings['partnerToken'] == $token ){
                    $this->u4crypto_registro_logs_billet('Token enviado aceito: '.$token, $confereId);
                    /**Validar valores com a U4Crypto*/
					'https://prd-api.u4cdev.com/pix/brcode/erp/dynamic/a155465c-5440-413a-bfdd-fc992f7f779b';
					($setings->settings['u4cryptoenvironment'] === "0")? $url = U4CYPTO_PRO : $url = U4CYPTO_DEV;
					$api = $setings->settings['apiToken'];
					$partner = $setings->settings['partnerToken'];
					$customer = $setings->settings['customerToken'];
					$u4cryptoItemId =   get_post_meta( $confereId, 'u4cryptoItemId', true );
					$urlBillet = $url.'/pix/brcode/erp/dynamic/'.$u4cryptoItemId;

					$argsBillet = array(
						'timeout'       => 30,
						'method'        => 'GET',
						'httpversion'   => '1.1',
						'headers'       => [
							"partner" => $partner,
							"token" => $api,
							"Content-Type" => "application/json"
						],
					);

                    /** Fazer o update */
                    try{
						$http = _wp_http_get_object();
						$tuBillet = $http->get($urlBillet, $argsBillet);

						$http = _wp_http_get_object();
						$tuBillet = $http->get($urlBillet, $argsBillet);
						if(isset($tuBillet['body'])){
							$dados = json_decode($tuBillet['body'], true);
                            $this->u4crypto_registro_logs_billet('PIX Status '.$dados['status'], $confereId);
							if($dados['status'] == 'paid'){
								$order = wc_get_order( $confereId );

								if($order->get_status() != "processing"){
									$order->payment_complete();
									wc_reduce_stock_levels( $confereId );
                                    $this->u4crypto_registro_logs_billet('CallBack Recebido e Status alterado', $order->get_id());
								}
							}

						}else{
                            $this->u4crypto_registro_logs_billet('Não foi possível validar: '.json_encode($tuBillet['body']), $confereId);
                        }


                    }catch (Exception $e) {
                        echo 'Exceção capturada: ',  $e->getMessage(), "\n";
                    }

                }else{
                    $this->u4crypto_registro_logs_billet('Token enviado rejeitado: '.$token, $confereId);
                    _e("error:Token Invalido");
                }

            }
            /** Cartão e Boleto */
            elseif(isset($data["id"])){
                if( isset($setings->settings['partnerToken']) AND $setings->settings['partnerToken'] == $token ){
                    $order = wc_get_order( $confereId );
                    $order->payment_complete();
                    $this->u4crypto_registro_logs_billet('CallBack Recebido e Status alterado', $order->get_id());
                    wc_reduce_stock_levels( $confereId );

                }
            }else{
                $res = "Entre em contato com os desenvolvedores.";
                if(!isset($_POST["id"])){
                    $res = "Não foi localizado a propriedade 'id'";
                }elseif(!isset($_POST["externalId"])){
                    $res = "Não foi localizado a propriedade 'externalId'";
                }
                _e("error: ".$res);
            }
           	exit();
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
                'u4cryptovencimento' => array(
                    'title'       => __( 'Prazo para vencimento do boleto (dias)', $this->domain ),
                    'type'        => 'number',
                    'description' => __( 'Tempo em dias até que o sistema cancelo o boleto e a venda', $this->domain ),
                    'default'     => '3',
                    'desc_tip'    => true,
                ),
                'u4cryptolimitepagamento' => array(
                    'title'       => __( 'Prazo máximo para pagamento do boleto (dias)', $this->domain ),
                    'type'        => 'number',
                    'description' => __( 'Tempo em dias para aceitar que o boleto seja pago', $this->domain ),
                    'default'     => '3',
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
			$link = get_post_meta( $order->get_id(), 'boletolink', true );
			$base    = get_option( 'woocommerce_email_base_color' );
            if ( ! $sent_to_admin && 'u4cryptoboleto' == $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
				$this->u4crypto_registro_logs_billet('Envio de E-mail do Pedido: ', $order->get_id());
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
							text-align: center !important;" target="_blank" href="'.$link.'">'.__( 'Baixar boleto').'</a></p><br>'. PHP_EOL
                    );

            }


        }

        /**
		 * Get templates path.
		 *
		 * @return string
		 */
		public static function get_templates_path() {
			return plugin_dir_path( __FILE__ ) . 'templates/';
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
                        <input type="text" name="vat_u4c_billet" required="required">
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
                WHERE option_name = 'woocommerce_u4cryptoboleto_settings'
            ");

            return [
                "settings" => [
                    unserialize( $results[0]->option_value )
                ]
            ];
        }

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
            if($data_order['payment_method'] != 'u4cryptoboleto'){
                return;
            }
            $this->u4crypto_registro_logs_billet('scheduled_subscription_payment Inicio: ', $order->get_id());
            $result = $this->process_subscription_payment( $order, $amount_to_charge );
            $this->u4crypto_registro_logs_billet($result, $order->get_id());

            // if ( is_wp_error( $result ) ) {
            //     WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
            // } else {
            //     WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
            // }
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
            $dueDate = $setings->settings['u4cryptovencimento']*86400;
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
            * Generator billet API
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
                $vendor_id = dokan_get_seller_id_by_order( $order_id );
            }
            else {
                $vendor_id = NULL;
            }
            $split = '';

            $postData = [
                "runAsync"=> false,
                "skipPdf"=> false,
                "skipNotification"=> true,
                "message"=> "Não receber após o vencimento",
                "amount"=> floatval($data_order['total']),
                "due_date"=> date('Y-m-d', (time()+$dueDate)),
                "type"=> "billing",
                "payer"=> [
                  "documentNumber"=> preg_replace("/[^0-9]/", "", $vat),
                  "name"=> $nameCustomer,
                  "street_address"=>  $end->bairro,
                  "number"=> $number,
                  "complement"=> $end->logradouro,
                  "neighborhood"=> $end->bairro,
                  "cep"=>  preg_replace("/[^0-9]/", "", $bpcode),
                  "city"=> $end->localidade,
                  "state"=> $end->uf,
                  "saveContact"=> false
                ],
                "externalId"=> "$order_id"
            ];

            if($vendor_id != NULL){
                $split = [
                    "documentNumber"=> get_post_meta($vendor_id, 'vendor-cnpj')[0],
                    "percentValue"=> true,
                    "taxValue"=> get_option( 'dokan_selling', array() )["admin_percentage"],
                    "value"=> floatval($data_order['total'])
                ];
                $postData = [
                    "runAsync"=> false,
                    "skipPdf"=> false,
                    "skipNotification"=> true,
                    "message"=> "Não receber após o vencimento",
                    "amount"=> floatval($data_order['total']),
                    "due_date"=> date('Y-m-d', (time()+$dueDate)),
                    "type"=> "billing",
                    "payer"=> [
                      "documentNumber"=> preg_replace("/[^0-9]/", "", $vat),
                      "name"=> $nameCustomer,
                      "street_address"=>  $end->bairro,
                      "number"=> $number,
                      "complement"=> $end->logradouro,
                      "neighborhood"=> $end->bairro,
                      "cep"=>  preg_replace("/[^0-9]/", "", $bpcode),
                      "city"=> $end->localidade,
                      "state"=> $end->uf,
                      "saveContact"=> false
                    ],
                    "externalId"=> "$order_id", //"01"
                    "split" => [
                        $split
                    ]
                ];
            }


            /**Trata a url de Produção e de Desenvolvimento */
            ($setings->settings['u4cryptoenvironment'] === "0")? $url = U4CYPTO_PRO : $url = U4CYPTO_DEV;
            $urlBillet = $url.'/boleto/erp/create/'.$customer;

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

            $this->u4crypto_registro_logs_billet('U4crypto Request: ', $order->get_id());
            $http = _wp_http_get_object();
            $tuBillet = $http->post($urlBillet, $argsBillet);


            $response = json_decode( sanitize_text_field( $tuBillet['body'] ) );
            if(isset($response->id)){

                update_post_meta( $order_id, 'boleto', $response->digitableLine );
                update_post_meta( $order_id, 'boletovencimento', $response->dueDate );
                update_post_meta( $order_id, 'boletoid', $response->id );
                update_post_meta( $order_id, 'boletolink', $response->billet );
                update_post_meta( $order_id, 'documentNumber', $response->documentNumber );
                update_post_meta( $order_id, 'transaction', $response->processorTransactionNumber );
                $this->u4crypto_registro_logs_billet('U4crypto Item ID: '.$response->id, $order->get_id());
                return 'boletoid: '. $response->id;
            }else{
                update_post_meta( $order_id, 'boleto', '' );
                update_post_meta( $order_id, 'boletoid', '' );
                update_post_meta( $order_id, 'boletolink', '' );
                update_post_meta( $order_id, 'documentNumber', '' );
                update_post_meta( $order_id, 'transaction', '' );
                update_post_meta( $order_id, 'boletovencimento', '' );
                /**Creat archive of error */
                $content = json_encode( ["error" => $response, "data" => $postData], true );
                /** Registrar o error */
                u4crypto_registerError($tuBillet["response"]["message"].": ".$tuBillet["response"]["code"]." - Item: Boleto de Subscrição - Subscription "." - Resposta: ".$content);
                $this->u4crypto_registro_logs_billet('U4crypto Erro: '.$content, $order->get_id());
                return $content;
            }
        }

        /** Registro de Logs Passo a Passo*/
		public static function u4crypto_registro_logs_billet($content, $ar){
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

    }
}

/**
* Add U4Crypto as a gateway method
*/
add_filter( 'woocommerce_payment_gateways', 'add_u4crypto_gateway_class' );
function add_u4crypto_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_U4Crypto';
    return $methods;
}