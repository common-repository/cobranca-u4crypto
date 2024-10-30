<?php

define("U4CYPTO_DEV", "https://hml-api.u4cdev.com");
define("U4CYPTO_PRO", "https://prd-api.u4cdev.com");

/**
 * Register card
 * @paymentmethod u4cryptoCard Register Card
 */
add_action('woocommerce_checkout_process', 'process_u4crypto_payment', 10);
function process_u4crypto_payment()
{
    /** u4cryptocard */
    if ($_POST['payment_method'] != 'u4cryptocard') {
        return;
    }

    if (isset($_POST['u4cripto_installments'])) {
        if ($_POST['u4cripto_installments'] != 0) {
            $installment = filter_var($_POST['u4cripto_installments'], FILTER_SANITIZE_STRING);
        } else {return;}
    } else {return;}

    $setU4 = WC_Gateway_U4Crypto_Card::getSettingsU4crypto();
    $setings = $setU4['settings'][0];
    $partner = $setings['partnerToken'];
    $api = $setings['apiToken'];
    $customer = $setings['customerId'];

    $cardname = $_POST['cardname'];
    $cardnumber = $_POST['cardnumber'];
    $carddate = $_POST['carddate'];
    $cardcode = $_POST['cardcode'];
    $billing_persontype = filter_var($_POST['billing_persontype'], FILTER_SANITIZE_STRING);
    $mes = substr($carddate, 0, 2);
    $ano = '20' . substr($carddate, 2, 2);

    ($setings->settings['u4cryptoenvironment'] === "0") ? $url = U4CYPTO_PRO : $url = U4CYPTO_DEV;
    $urlCard = $url . '/creditcard/erp/register/' . $customer;

    $registerCart = array(
        "now" => date("Y-m-d"),
        "holderName" => $cardname,
        "number" => $cardnumber,
        "expirationMonth" => $mes,
        "expirationYear" => $ano,
        "securityCode" => $cardcode,
        "master" => "false",
    );

    $argsCard = array(
        'timeout' => 30,
        'method' => 'POST',
        'httpversion' => '1.1',
        'body' => json_encode($registerCart),
        'headers' => [
            "partner" => $partner,
            "token" => $api,
            "Content-Type" => "application/json",
        ],
    );

    $http = _wp_http_get_object();
    $tuBillet = $http->post($urlCard, $argsCard);
    $response = json_decode(sanitize_text_field($tuBillet['body']));
    if (!isset($response->card_id)) {
        // wc_add_notice( sprintf( 'O cartão foi recusado.' ), 'error' );
    }

}

/**
 * Operate U4Crypto at checkout
 * @paymentmethod u4cryptoCard
 */
add_action('woocommerce_checkout_update_order_meta', 'process_u4crypto_payment_card');
function process_u4crypto_payment_card($order_id)
{

    /** u4cryptocard */
    if ($_POST['payment_method'] != 'u4cryptocard') {
        return;
    }
    if (isset($_POST['u4cripto_installments'])) {
        if ($_POST['u4cripto_installments'] != 0) {
            $installment = filter_var($_POST['u4cripto_installments'], FILTER_SANITIZE_STRING);
        } else {return;}
    } else {return;}

    $setU4 = WC_Gateway_U4Crypto_Card::getSettingsU4crypto();
    $setings = $setU4['settings'][0];
    $partner = $setings['partnerToken'];
    $api = $setings['apiToken'];
    $customer = $setings['customerId'];

    $cardname = filter_var($_POST['cardname'], FILTER_SANITIZE_STRING);
    $cardnumber = filter_var($_POST['cardnumber'], FILTER_SANITIZE_STRING);
    $carddate = filter_var($_POST['carddate'], FILTER_SANITIZE_STRING);
    $cardcode = filter_var($_POST['cardcode'], FILTER_SANITIZE_STRING);
    $billing_persontype = filter_var($_POST['billing_persontype'], FILTER_SANITIZE_STRING);
    $mes = substr($carddate, 0, 2);
    $ano = '20' . substr($carddate, 2, 2);

    ($setings->settings['u4cryptoenvironment'] === "0") ? $url = U4CYPTO_PRO : $url = U4CYPTO_DEV;
    $urlCard = $url . '/creditcard/erp/register/' . $customer;

    $order = wc_get_order($order_id);
    if (class_exists('Extra_Checkout_Fields_For_Brazil')) {
        if (isset($_POST['billing_cpf']) and strlen($_POST['billing_cpf']) != 0) {
            $vat = filter_var($_POST['billing_cpf'], FILTER_SANITIZE_STRING);
        }
        if (isset($_POST['billing_cnpj']) and strlen($_POST['billing_cnpj']) != 0) {
            if ($_POST['billing_persontype'] == 2) {
                $vat = filter_var($_POST['billing_cnpj'], FILTER_SANITIZE_STRING);
            }
        }
    } else {
        if(isset($data_order['billing']['vat_u4'])
            and strlen($data_order['billing']['vat_u4']) != 0
        ){
            $vat = $data_order['billing']['vat_u4'];
        }elseif(isset($_POST['vat_u4'])){
            $vat = sanitize_text_field($_POST['vat_u4']);
        }
    }

    $registerCart = array(
        "document" => "$vat",
        "now" => date("Y-m-d"),
        "holderName" => $cardname,
        "number" => $cardnumber,
        "expirationMonth" => $mes,
        "expirationYear" => $ano,
        "securityCode" => $cardcode,
        "master" => "false",
    );

    $argsCard = array(
        'timeout' => 30,
        'method' => 'POST',
        'httpversion' => '1.1',
        'body' => json_encode($registerCart),
        'headers' => [
            "partner" => $partner,
            "token" => $api,
            "Content-Type" => "application/json",
        ],
    );

    //var_dump($urlCard);
    $http = _wp_http_get_object();
    $tuBillet = $http->post($urlCard, $argsCard);
    //var_dump($tuBillet);
    $response = json_decode(sanitize_text_field($tuBillet['body']));
    // print_r($response);
    // echo $response->card_id;
    //exit();
    if (!isset($response->card_id)) {
        $error = 'Cartão não pode ser registrado.';
        exit();
    }

    $order = wc_get_order($order_id);

    /**
     * Generator Card API
     */
    /**Trata a url de Produção e de Desenvolvimento */
    ($setings->settings['u4cryptoenvironment'] === "0") ? $url = U4CYPTO_PRO : $url = U4CYPTO_DEV;
    $urlPay = $url . '/creditcard/erp/cash-in/' . $customer;

    $registerPay = array(
        "document" => "$vat",
        "now" => date("Y-m-d"),
        "cardId" => $response->card_id,
        "amount" => $order->get_total(),
        "amountToBeCredited" => $installment,
        "description" => substr("Compra de nº $order_id, realizada no e-commerce " . $setings['nameInvoice'], 0, 500),
        "externalId" => "$order_id",
        "softDescriptor" => substr($setings['nameInvoice'], 0, 20),
    );

    $argsPay = array(
        'timeout' => 30,
        'method' => 'POST',
        'httpversion' => '1.1',
        'body' => json_encode($registerPay),
        'headers' => [
            "partner" => $partner,
            "token" => $api,
            "Content-Type" => "application/json",
        ],
    );
    $http = _wp_http_get_object();
    $tuPay = $http->post($urlPay, $argsPay);
    //var_dump($tuPay);
    $responsePay = json_decode(sanitize_text_field($tuPay['body']));

    if (isset($responsePay->transactionId)) {
        update_post_meta($order_id, 'transaction', $responsePay->transactionId);
        if ($responsePay->status == "Approved") {
            $order->update_status('processing', 'Pagamento aprovado.');
        } else {
            $order->update_status('canceled', 'O pagamento não foi aprovado.');
        }

    } else {
        // wc_add_notice( sprintf( '<strong>Sistema indisponível</strong>, tente mais tarde ou escolha outro método de pagamento.' ), 'error' );
        /**Creat archive of error */
        $content = json_encode(["error" => $responsePay], true);
        u4crypto_registerError($tuBillet["response"]["message"] . ": " . $tuBillet["response"]["code"] . " - Item: Novo pedido por cartão - Usuário " . " - Resposta: " . $content);

    }

}

/**
 * Update the order meta with field value
 * @paymentmethod u4cryptoBillet
 */
add_action('woocommerce_checkout_update_order_meta', 'u4crypto_payment_update_order_meta');
function u4crypto_payment_update_order_meta($order_id)
{

    if ($_POST['payment_method'] != 'u4cryptoboleto') {
        return;
    }

    WC_Gateway_U4Crypto::u4crypto_registro_logs_billet('Pedido gerado: ' . $order_id, $order_id);

    $setings = wc_get_payment_gateway_by_order($order_id);
    $dueDate = $setings->settings['u4cryptovencimento'] * 86400;
    $api = $setings->settings['apiToken'];
    $partner = $setings->settings['partnerToken'];
    $customer = $setings->settings['customerToken'];
    $u4cryptolimitepagamento = $setings->settings['u4cryptolimitepagamento'];

    $order = wc_get_order($order_id);
    $data_order = $order->get_data();
    if (class_exists('Extra_Checkout_Fields_For_Brazil')) {
        if (isset($_POST['billing_cpf']) and strlen($_POST['billing_cpf']) != 0) {
            $vat = filter_var($_POST['billing_cpf'], FILTER_SANITIZE_STRING);
            $nameCustomer = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        }
        if (isset($_POST['billing_cnpj']) and strlen($_POST['billing_cnpj']) != 0) {
            if ($_POST['billing_persontype'] == 2) {
                $vat = filter_var($_POST['billing_cnpj'], FILTER_SANITIZE_STRING);
                $nameCustomer = filter_var($_POST['billing_company'], FILTER_SANITIZE_STRING);
                // wc_add_notice( sprintf( '<strong>Nome da empresa</strong> é obrigatório.' ), 'error' );
            }
        }
    } else {
        if(isset($data_order['billing']['vat_u4c_billet'])
            and strlen($data_order['billing']['vat_u4c_billet']) != 0
        ){
            $vat = $data_order['billing']['vat_u4c_billet'];
        }elseif(isset($_POST['vat_u4c_billet'])){
            $vat = sanitize_text_field($_POST['vat_u4c_billet']);
        }

    }

    if(!$nameCustomer){
        $nameCustomer = sanitize_text_field($_POST['billing_first_name'] . ' ' . $_POST['billing_last_name']);
    }

    /**Tratar o número do endereço com o Plugin Brazilian e sem ele */
    if (isset($_POST['billing_number'])) {
        $number = $_POST['billing_number'];
    } elseif (isset($data_order['billing']['address_1'])) {
        if (is_int(preg_replace("/[^0-9]/", "", $data_order['billing']['address_1']))) {
            $number = preg_replace("/[^0-9]/", "", $data_order['billing']['address_1']);
        } else {
            $number = "0";
        }
    } else {
        $number = "0";
    }

    /**
     * Generator billet API
     */
    $bpcode = filter_var($_POST['billing_postcode'], FILTER_SANITIZE_STRING);
    if (
        isset($_POST['billing_address_1'])
        and isset($_POST['billing_neighborhood'])
        and isset($_POST['billing_city'])
        and isset($_POST['billing_state'])
    ) {
        $bairro = sanitize_text_field($_POST['billing_neighborhood']);
        $cidade = sanitize_text_field($_POST['billing_city']);
        $estado = sanitize_text_field($_POST['billing_state']);
        $endereco = sanitize_text_field($_POST['billing_address_1']);
    } else {
        $urlCep = "https://viacep.com.br/ws/" . $bpcode . "/json/";
        $argsCep = array(
            'timeout' => 30,
            'method' => 'GET',
        );
        $http = _wp_http_get_object();
        $end = $http->get($urlCep, $argsCep);
        $end = json_decode($end['body']);
        $bairro = $end->bairro;
        $cidade = $end->localidade;
        $estado = $end->uf;
        $endereco = $end->logradouro;
    }

    if (class_exists('WeDevs_Dokan')) {
        $vendor_id = dokan_get_seller_id_by_order($order_id);
    } else {
        $vendor_id = null;
    }
    $split = '';

    $postData = [
        "runAsync" => false,
        "skipPdf" => false,
        "skipNotification" => true,
        "message" => "Não receber após o vencimento",
        "amount" => floatval($data_order['total']),
        "due_date" => date('Y-m-d', (time() + $dueDate)),
        "type" => "billing",
        "payer" => [
            "documentNumber" => preg_replace("/[^0-9]/", "", $vat),
            "name" => $nameCustomer,
            "street_address" => $bairro,
            "number" => $number,
            "complement" => $endereco,
            "neighborhood" => $bairro,
            "cep" => preg_replace("/[^0-9]/", "", $bpcode),
            "city" => $cidade,
            "state" => $estado,
            "saveContact" => false,
        ],
        "billing_instructions" => [
            "payment_limit_date" => date('Y-m-d', (time() + $u4cryptolimitepagamento)),
        ],
        "externalId" => "$order_id",
    ];

    if ($vendor_id != null) {
        $split = [
            "documentNumber" => get_post_meta($vendor_id, 'vendor-cnpj')[0],
            "percentValue" => true,
            "taxValue" => get_option('dokan_selling', array())["admin_percentage"],
            "value" => floatval($data_order['total']),
        ];
        $postData = [
            "runAsync" => false,
            "skipPdf" => false,
            "skipNotification" => true,
            "message" => "Não receber após o vencimento",
            "amount" => floatval($data_order['total']),
            "due_date" => date('Y-m-d', (time() + $dueDate)),
            "type" => "billing",
            "payer" => [
                "documentNumber" => preg_replace("/[^0-9]/", "", $vat),
                "name" => $nameCustomer,
                "street_address" => $bairro,
                "number" => $number,
                "complement" => $endereco,
                "neighborhood" => $bairro,
                "cep" => preg_replace("/[^0-9]/", "", $bpcode),
                "city" => $cidade,
                "state" => $estado,
                "saveContact" => false,
            ],
            "billing_instructions" => [
                "payment_limit_date" => date('Y-m-d', (time() + $u4cryptolimitepagamento)),
            ],
            "externalId" => "$order_id", //"01"
            "split" => [
                $split,
            ],
        ];
    }

    $postData = json_encode($postData);

    /**Trata a url de Produção e de Desenvolvimento */
    ($setings->settings['u4cryptoenvironment'] === "0") ? $url = U4CYPTO_PRO : $url = U4CYPTO_DEV;
    $urlBillet = $url . '/boleto/erp/create/' . $customer;

    $argsBillet = array(
        'timeout' => 30,
        'method' => 'POST',
        'httpversion' => '1.1',
        'body' => $postData,
        'headers' => [
            "partner" => $partner,
            "token" => $api,
            "Content-Type" => "application/json",
        ],
    );

    WC_Gateway_U4Crypto::u4crypto_registro_logs_billet('Request para U4Crypto: ' . $order_id, $order_id);
    $http = _wp_http_get_object();
    $tuBillet = $http->post($urlBillet, $argsBillet);
    $response = json_decode(sanitize_text_field($tuBillet['body']));
    //print_r(json_encode( $response ));
    //var_dump($argsBillet, $response); exit;
    if (isset($response->id)) {
        update_post_meta($order_id, 'boleto', $response->digitableLine);
        update_post_meta($order_id, 'boletovencimento', $response->dueDate);
        update_post_meta($order_id, 'boletoid', $response->id);
        update_post_meta($order_id, 'boletolink', $response->billet);
        update_post_meta($order_id, 'documentNumber', $response->documentNumber);
        update_post_meta($order_id, 'transaction', $response->processorTransactionNumber);
        WC_Gateway_U4Crypto::u4crypto_registro_logs_billet('Cobrança gerada: ' . $response->id, $order_id);

    } else {
        update_post_meta($order_id, 'boletoid', '');
        /**Creat archive of error */
        $content = json_encode(["error" => $response, "data" => json_decode($postData)], true);
        u4crypto_registerError($tuBillet["response"]["message"] . ": " . $tuBillet["response"]["code"] . " - Item: Novo Pedido por Boleto - Usuário " . " - Resposta: " . $content);
        WC_Gateway_U4Crypto::u4crypto_registro_logs_billet('Cobrança com erro: ', $order_id);
        WC_Gateway_U4Crypto::u4crypto_registro_logs_billet($content, $order_id);
    }
}

/**
 * Update the order meta with field value
 * @paymentmethod u4cryptopix
 */
add_action('woocommerce_checkout_update_order_meta', 'u4crypto_pix_payment_update_order_meta');
function u4crypto_pix_payment_update_order_meta($order_id)
{

    if ($_POST['payment_method'] != 'u4cryptopix') {
        return;
    }
    WC_Gateway_u4crypto_Pix::u4crypto_registro_logs_pix('Pedido criado: ', $order_id);
    $setings = wc_get_payment_gateway_by_order($order_id);
    $dueDate = $setings->settings['u4cryptovencimento'] * 86400;
    $api = $setings->settings['apiToken'];
    $partner = $setings->settings['partnerToken'];
    $customer = $setings->settings['customerToken'];

    /***Data of customer */
    $order = wc_get_order($order_id);
    if (class_exists('Extra_Checkout_Fields_For_Brazil')) {
        if (isset($_POST['billing_cpf']) and strlen($_POST['billing_cpf']) != 0) {
            $vat = filter_var($_POST['billing_cpf'], FILTER_SANITIZE_STRING);
            $nameCustomer = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        }
        if (isset($_POST['billing_cnpj']) and strlen($_POST['billing_cnpj']) != 0) {
            if ($_POST['billing_persontype'] == 2) {
                $vat = filter_var($_POST['billing_cnpj'], FILTER_SANITIZE_STRING);
                $nameCustomer = $_POST['billing_company'];
                // wc_add_notice( sprintf( '<strong>Nome da empresa</strong> é obrigatório.' ), 'error' );
            }
        }
    } else {
        if(isset($data_order['billing']['vat_u4c_pix'])
            and strlen($data_order['billing']['vat_u4c_pix']) != 0
        ){
            $vat = $data_order['billing']['vat_u4c_pix'];
        }elseif(isset($_POST['vat_u4c_pix'])){
            $vat = sanitize_text_field($_POST['vat_u4c_pix']);
        }
    }

    if(!$nameCustomer){
        $nameCustomer = sanitize_text_field($_POST['billing_first_name'] . ' ' . $_POST['billing_last_name']);
    }

    /**Tratar o número do endereço com o Plugin Brazilian e sem ele */
    if (isset($_POST['billing_number'])) {
        $number = filter_var($_POST['billing_number'], FILTER_SANITIZE_STRING);
    } elseif (isset($order->data['billing']['address_1'])) {
        if (is_int(preg_replace("/[^0-9]/", "", $order->data['billing']['address_1']))) {
            $number = preg_replace("/[^0-9]/", "", $order->data['billing']['address_1']);
        } else {
            $number = "0";
        }
    } else {
        $number = "0";
    }

    /**
     * Generator billet API
     */
    $bpcode = filter_var($_POST['billing_postcode'], FILTER_SANITIZE_STRING);
    if (
        isset($_POST['billing_number'])
        and isset($_POST['billing_postcode'])
        and isset($_POST['billing_address_1'])
        and isset($_POST['billing_address_2'])
        and isset($_POST['billing_neighborhood'])
        and isset($_POST['billing_city'])
        and isset($_POST['billing_state'])
    ) {
        $bairro = sanitize_text_field($_POST['billing_neighborhood']);
        $cidade = sanitize_text_field($_POST['billing_city']);
        $estado = sanitize_text_field($_POST['billing_state']);
        $estado = sanitize_text_field($_POST['billing_state']);
        $endereco = sanitize_text_field($_POST['billing_address_1']);
    } else {
        $urlCep = "https://viacep.com.br/ws/" . $bpcode . "/json/";
        $argsCep = array(
            'timeout' => 30,
            'method' => 'GET',
        );
        $http = _wp_http_get_object();
        $end = $http->get($urlCep, $argsCep);
        $end = json_decode($end['body']);
        $bairro = $end->bairro;
        $cidade = $end->localidade;
        $estado = $end->uf;
        $endereco = $end->logradouro;
    }

    if($setings->settings['imediato'] != 'Sim'){
        /**Schema API
        * billingDueDate = 'BILLING_DUE_DATE' (QRCode com data de vencimento, juros, multa e mora)
        */
        $postData = [
            "customerId" => $customer,
            "externalId" => "$order_id",
            "additionalInformation" => [
                [
                    "content" => "$order_id",
                ],
            ],
            "dynamicQRCodeType" => "BILLING_DUE_DATE",
            "billingDueDate" => [
                "dueDate" => date('Y-m-d'),
                "daysAfterDueDate" => 102,
                "payerInformation" => [
                    "name" => $nameCustomer,
                    "cpfCnpj" => preg_replace("/[^0-9]/", "", $vat),
                    "addressing" => [
                        "street" => $endereco . ' ' . $number . ' ' . $bairro,
                        "city" => $cidade,
                        "uf" => $estado,
                        "cep" => preg_replace("/[^0-9]/", "", $bpcode),
                    ],
                ],
                "paymentValue" => [
                    "documentValue" => floatval($order->get_total()),
                    "discounts" => [
                        "modality" => 1,
                        "fixedDateDiscounts" => [

                        ],
                    ],
                    "fines" => [
                        "modality" => 1,
                        "valuePerc" => 1,
                    ],
                    "interests" => [
                        "modality" => 2,
                        "valuePerc" => 0.5,
                    ],
                ],
            ],
        ];
    }else{
        $postData = [
            "customerId" => $customer,
            "externalId" => "$order_id",
            "additionalInformation" => [
                [
                    "content" => "$order_id",
                ],
            ],
            "dynamicQRCodeType" => "IMMEDIATE",
            "immediate" => [
                "payerInformation" => [
                    "name" => $nameCustomer,
                    "cpfCnpj" => preg_replace("/[^0-9]/", "", $vat),
                ],
                "paymentValue" => [
                    "documentValue" => floatval($order->get_total()),
                ],
            ],
        ];
    }
    /**Trata a url de Produção e de Desenvolvimento
     * https://hml-api.u4cdev.com/pix/docs#!/brcode/postPixBrcodeErpDynamic
     * https://hml-api.u4cdev.com/pix/brcode/erp/dynamic
     */
    ($setings->settings['u4cryptoenvironment'] === "0") ? $url = U4CYPTO_PRO : $url = U4CYPTO_DEV;
    $urlBillet = $url . '/pix/brcode/erp/dynamic';

    $argsBillet = array(
        'timeout' => 30,
        'method' => 'POST',
        'httpversion' => '1.1',
        'body' => json_encode($postData),
        'headers' => [
            "partner" => $partner,
            "token" => $api,
            "Content-Type" => "application/json",
        ],
    );

    WC_Gateway_u4crypto_Pix::u4crypto_registro_logs_pix('Request para U4Crypto: ', $order_id);
    $http = _wp_http_get_object();
    $tuBillet = $http->post($urlBillet, $argsBillet);

    $response = json_decode(sanitize_text_field($tuBillet['body']));
    if (isset($response->message) and $response->message == "Ocorreu um erro inesperado.") {
        // wc_add_notice( sprintf( '<strong>PIX:</strong> sistema indisponível, tente mais tarde ou escolha outro método de pagamento.' ), 'error' );
        WC_Gateway_u4crypto_Pix::u4crypto_registro_logs_pix($response->message, $order_id);
    }

    if (isset($response->data)) {
        update_post_meta($order_id, 'u4cryptoItemId', $response->itemId);
        /**textContent = O código do Pix*/
        update_post_meta($order_id, 'u4cryptoTextContent', $response->data->textContent);
        update_post_meta($order_id, 'u4cryptoImageContent', $response->data->generatedImage->imageContent);
        update_post_meta($order_id, 'u4cryptoQrcodeURL', $response->data->qrcodeURL);
        update_post_meta($order_id, 'u4cryptoReference', $response->data->reference);

        /**
         * informativo para atualizar o status inicial no envio de e-mail
         * if(0) atualiza o u4cryptoPaymentStart para 1 e atualiza o status do pedido para aguardando pagamento.
         */
        update_post_meta($order_id, 'u4cryptoPaymentStart', 0);
        //var_dump($response); exit;
        WC_Gateway_u4crypto_Pix::u4crypto_registro_logs_pix('Cobrança gerada: ' . $response->itemId, $order_id);
    } else {

        /**Creat archive of error */
        $content = json_encode(["error" => $response, "data" => $postData], true);
        u4crypto_registerError($tuBillet["response"]["message"] . ": " . $tuBillet["response"]["code"] . " - Item: Novo pedido por Pix - Usuário " . " - Resposta: " . $content);
        WC_Gateway_u4crypto_Pix::u4crypto_registro_logs_pix('Cobrança com erro: ' . $response->itemId, $order_id);
        WC_Gateway_u4crypto_Pix::u4crypto_registro_logs_pix($content, $order_id);
    }

}

add_action('woocommerce_checkout_update_order_meta', 'u4crypto_qrcode_payment_update_order_meta');
function u4crypto_qrcode_payment_update_order_meta($order_id)
{

    if ($_POST['payment_method'] != 'u4cryptoqrcode') {
        return;
    }

    $setings = wc_get_payment_gateway_by_order($order_id);
    $sales_security = ($setings->settings['sales_security'] == '1') ? true : false;
    $api = $setings->settings['apiToken'];
    $partner = $setings->settings['partnerToken'];
    $customer = $setings->settings['customerToken'];

    $order = wc_get_order($order_id);

    if (class_exists('Extra_Checkout_Fields_For_Brazil')) {
        if (isset($_POST['billing_cpf']) and strlen($_POST['billing_cpf']) != 0) {
            $vat = filter_var($_POST['billing_cpf'], FILTER_SANITIZE_STRING);
            $nameCustomer = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        }
        if (isset($_POST['billing_cnpj']) and strlen($_POST['billing_cnpj']) != 0) {
            if ($_POST['billing_persontype'] == 2) {
                $vat = filter_var($_POST['billing_cnpj'], FILTER_SANITIZE_STRING);
                $nameCustomer = $_POST['billing_company'];
                // wc_add_notice( sprintf( '<strong>Nome da empresa</strong> é obrigatório.' ), 'error' );
            }
        }
    } else {
        if(isset($data_order['billing']['vat_u4c_qrcode'])
            and strlen($data_order['billing']['vat_u4c_qrcode']) != 0
        ){
            $vat = $data_order['billing']['vat_u4c_qrcode'];
        }elseif(isset($_POST['vat_u4c_qrcode'])){
            $vat = sanitize_text_field($_POST['vat_u4c_qrcode']);
        }
    }

    if(!$nameCustomer){
        $nameCustomer = sanitize_text_field($_POST['billing_first_name'] . ' ' . $_POST['billing_last_name']);
    }

    $dataQrcode = [
        'customerDocument' => preg_replace("/[^0-9]/", "", $vat),
        'amount' => floatval($order->get_total()),
        'salesSecurity' => $sales_security,
        'externalId' => "$order_id",
    ];

    ($setings->settings['u4cryptoenvironment'] === "0") ? $url = U4CYPTO_PRO : $url = U4CYPTO_DEV;
    $urlBillet = $url . '/erp/qr-code-payment/dynamic';

    $argsBillet = array(
        'timeout' => 30,
        'method' => 'POST',
        'httpversion' => '1.1',
        'body' => json_encode($dataQrcode),
        'headers' => [
            "partner" => $partner,
            "token" => $api,
            "Content-Type" => "application/json",
        ],
    );

    $http = _wp_http_get_object();
    $tuBillet = $http->post($urlBillet, $argsBillet);
    $response = json_decode(sanitize_text_field($tuBillet['body']));

    if ($response->hash) {
        update_post_meta($order_id, 'u4cryptoQRcodePayment', $response->hash);
    } else {

        /**Creat archive of error */
        $content = json_encode(["error" => $response, "data" => $dataQrcode], true);
        u4crypto_registerError($tuBillet["response"]["message"] . ": " . $tuBillet["response"]["code"] . " - Item: Novo pedido por QrCode - Usuário " . " - Resposta: " . $content);
        WC_Gateway_u4crypto_Pix::u4crypto_registro_logs_pix('Cobrança com erro: ' . $response->itemId, $order_id);
        WC_Gateway_u4crypto_Pix::u4crypto_registro_logs_pix($content, $order_id);
    }
    //var_dump($response->hash, $tuBillet['body']); exit;

}
