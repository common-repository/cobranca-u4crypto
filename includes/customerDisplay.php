<?php
require_once __DIR__ . '/../vendor/autoload.php';
use chillerlan\QRCode\QRCode;

/**
 * Operate U4Crypto on the order screen
 */
add_action('woocommerce_order_details_after_order_table', 'u4crypto_custom_field_display_cust_order_meta', 10, 1);
function u4crypto_custom_field_display_cust_order_meta($order)
{
    $method = get_post_meta($order->get_id(), '_payment_method', true);
    if ($method != 'u4cryptoboleto') {
        return;
    }

    /** Gerar um novo boleto com a data nova, ou gerar o boleto quando não existe um boleto */
    if (
        (get_post_meta($order->get_id(), 'boletovencimento', true) < date('Y-m-d') and $order->get_status() == 'on-hold')
        or get_post_meta($order->get_id(), 'boletoid', true) == ""
    ) {
        /**
         * Regerar o boleto com uma data de vencimento
         */
        if (function_exists('wc_u4crypto_update_boleto_data')) {
            wc_u4crypto_update_boleto_data($order);
        }
    }

    $boleto = get_post_meta($order->get_id(), 'boleto', true);
    $link = get_post_meta($order->get_id(), 'boletolink', true);

    $setings = wc_get_payment_gateway_by_order($order->get_id());
    echo '<p><strong>' . __($setings->settings['instructions'], 'u4crypto_boleto') . '</strong></p>';

    if ($boleto != '') {echo '<p><strong>' . __('Billet digitable line', 'u4crypto_boleto') . ':</strong> ' . $boleto . '</p>';}
    if ($link != '') {echo '<p class="billetdownload"><a target="_blank" href="' . $link . '">' . __('Download the billet', 'u4crypto_boleto') . '</a></p>';}

}

function wc_u4crypto_update_boleto_data($order)
{
    $order_id = $order->get_id();
    $data_order = $order->get_data();

    $setings = wc_get_payment_gateway_by_order($order_id);
    $dueDate = $setings->settings['u4cryptovencimento'] * 86400;
    $api = $setings->settings['apiToken'];
    $partner = $setings->settings['partnerToken'];
    $customer = $setings->settings['customerToken'];

    // $order = wc_get_order( $order_id );
    if ($order->get_meta('_billing_cpf') != "" and strlen($order->get_meta('_billing_cpf')) != 0) {
        $vat = htmlspecialchars($order->get_meta('_billing_cpf'));
        $nameCustomer = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    }
    // var_dump($order->get_meta('_billing_persontype')); exit;
    if ($order->get_meta('_billing_cnpj') != "" and strlen($order->get_meta('_billing_cnpj')) != 0) {
        if ($order->get_meta('_billing_persontype') == 2) {
            $vat = htmlspecialchars($order->get_meta('_billing_cnpj'));
            $nameCustomer = htmlspecialchars($order->get_billing_company());
            // wc_add_notice( sprintf( '<strong>Nome da empresa</strong> é obrigatório.' ), 'error' );
        }
    }

    /**Tratar o número do endereço com o Plugin Brazilian e sem ele */
    if ($order->get_meta('_billing_number') != "") {
        $number = $order->get_meta('_billing_number');
    } elseif ($order->get_billing_address_1()) {
        if (is_int(preg_replace("/[^0-9]/", "", $order->get_billing_address_1()))) {
            $number = preg_replace("/[^0-9]/", "", $order->get_billing_address_1());
        } else {
            $number = "0";
        }
    } else {
        $number = "0";
    }

    /**
     * Generator billet API
     */
    $bpcode = $order->get_billing_postcode();
    if (
        $order->get_billing_address_1() != ""
        and $order->get_meta('_billing_neighborhood') != ""
        and $order->get_billing_city() != ""
        and $order->get_billing_state() != ""
    ) {
        $bairro = $order->get_meta('_billing_neighborhood');
        $cidade = $order->get_billing_city();
        $estado = $order->get_billing_state();
        $endereco = $order->get_billing_address_1();
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
            "street_address" => $endereco,
            "number" => $number,
            "complement" => $endereco,
            "neighborhood" => $bairro,
            "cep" => preg_replace("/[^0-9]/", "", $bpcode),
            "city" => $cidade,
            "state" => $estado,
            "saveContact" => false,
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
                "street_address" => $endereco,
                "number" => $number,
                "complement" => $endereco,
                "neighborhood" => $bairro,
                "cep" => preg_replace("/[^0-9]/", "", $bpcode),
                "city" => $cidade,
                "state" => $estado,
                "saveContact" => false,
            ],
            "externalId" => "$order_id", //"01"
            "split" => [
                $split,
            ],
        ];
    }

    /**Trata a url de Produção e de Desenvolvimento */
    ($setings->settings['u4cryptoenvironment'] === "0") ? $url = U4CYPTO_PRO : $url = U4CYPTO_DEV;
    $urlBillet = $url . '/boleto/erp/create/' . $customer;

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

    $http = _wp_http_get_object();
    $tuBillet = $http->post($urlBillet, $argsBillet);

    $response = json_decode(sanitize_text_field($tuBillet['body']));
    if (isset($response->id)) {

        update_post_meta($order_id, 'boleto', $response->digitableLine);
        update_post_meta($order_id, 'boletovencimento', $response->dueDate);
        update_post_meta($order_id, 'boletoid', $response->id);
        update_post_meta($order_id, 'boletolink', $response->billet);
        update_post_meta($order_id, 'documentNumber', $response->documentNumber);
        update_post_meta($order_id, 'transaction', $response->processorTransactionNumber);

    } else {
        update_post_meta($order_id, 'boleto', '');
        update_post_meta($order_id, 'boletoid', '');
        update_post_meta($order_id, 'boletolink', '');
        update_post_meta($order_id, 'documentNumber', '');
        update_post_meta($order_id, 'transaction', '');
        update_post_meta($order_id, 'boletovencimento', '');
        /**Creat archive of error */
        $content = json_encode(["error" => $response, "data" => $postData], true);
        u4crypto_registerError($tuBillet["response"]["message"] . ": " . $tuBillet["response"]["code"] . " - Item: Atualizar Boleto - Painel Admin " . " - Resposta: " . $content);
    }
}

/**
 * Operate U4Crypto on the order screen
 */
add_action('woocommerce_order_details_after_order_table', 'u4crypto_pix_field_display_cust_order_meta', 10, 1);
function u4crypto_pix_field_display_cust_order_meta($order)
{
    $method = get_post_meta($order->get_id(), '_payment_method', true);
    if ($method != 'u4cryptopix') {
        return;
    }

    $setings = wc_get_payment_gateway_by_order($order->get_id());

    _e('<p><strong>' . __($setings->settings['instructions'], 'u4crypto_boleto') . '</strong></p>');

    $pix = get_post_meta($order->get_id(), 'u4cryptoTextContent', true);
    if (get_post_meta($order->get_id(), 'u4cryptoImageContent', true) != '') {
        _e('<div>
            <input class="search-input" style="cursor: default; width:275px;float: left; height: 40px;" type="text" id="pix" name="pix" value="' . $pix . '">
            &emsp;
            <a class="button" id="pixbtn" style="cursor: pointer;" onClick="copiarTexto()">Copiar Código do Pix</a></div>
            <script>
                function copiarTexto() {
                    var textoCopiado = document.getElementById("pix");
                    textoCopiado.select();
                    document.execCommand("Copy");
                    alert("Texto Copiado: " + textoCopiado.value);
                }
            </script>
        ');
        _e('<img src="data:image/png;base64, ' . get_post_meta($order->get_id(), 'u4cryptoImageContent', true) . '">');
    } else {
        /** Regerar o Pix */
        $pix = wc_u4crypto_update_pix_data($order);
    }

}

function wc_u4crypto_update_pix_data($order)
{
    $order_id = $order->get_id();
    $data_order = $order->get_data();
    WC_Gateway_u4crypto_Pix::u4crypto_registro_logs_pix('Regerar o pix: ', $order_id);
    $setings = wc_get_payment_gateway_by_order($order_id);
    $api = $setings->settings['apiToken'];
    $partner = $setings->settings['partnerToken'];
    $customer = $setings->settings['customerToken'];

    // $order = wc_get_order( $order_id );
    if ($order->get_meta('_billing_cpf') != "" and strlen($order->get_meta('_billing_cpf')) != 0) {
        $vat = htmlspecialchars($order->get_meta('_billing_cpf'));
        $nameCustomer = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    }
    // var_dump($order->get_meta('_billing_persontype')); exit;
    if ($order->get_meta('_billing_cnpj') != "" and strlen($order->get_meta('_billing_cnpj')) != 0) {
        if ($order->get_meta('_billing_persontype') == 2) {
            $vat = htmlspecialchars($order->get_meta('_billing_cnpj'));
            $nameCustomer = htmlspecialchars($order->get_billing_company());
            // wc_add_notice( sprintf( '<strong>Nome da empresa</strong> é obrigatório.' ), 'error' );
        }
    }

    /**Tratar o número do endereço com o Plugin Brazilian e sem ele */
    if ($order->get_meta('_billing_number') != "") {
        $number = $order->get_meta('_billing_number');
    } elseif ($order->get_billing_address_1()) {
        if (is_int(preg_replace("/[^0-9]/", "", $order->get_billing_address_1()))) {
            $number = preg_replace("/[^0-9]/", "", $order->get_billing_address_1());
        } else {
            $number = "0";
        }
    } else {
        $number = "0";
    }

    /**
     * Generator billet API
     */
    $bpcode = $order->get_billing_postcode();
    if (
        isset($_POST['billing_number'])
        and isset($_POST['billing_postcode'])
        and isset($_POST['billing_address_1'])
        and isset($_POST['billing_address_2'])
        and isset($_POST['billing_neighborhood'])
        and isset($_POST['billing_city'])
        and isset($_POST['billing_state'])
    ) {
        $bairro = htmlspecialchars($_POST['billing_neighborhood']);
        $cidade = htmlspecialchars($_POST['billing_city']);
        $estado = htmlspecialchars($_POST['billing_state']);
        $endereco = htmlspecialchars($_POST['billing_address_1']);

    } elseif (
        $order->get_billing_address_1() != ""
        and $order->get_meta('_billing_neighborhood') != ""
        and $order->get_billing_city() != ""
        and $order->get_billing_state() != ""
    ) {
        $bairro = $order->get_meta('_billing_neighborhood');
        $cidade = $order->get_billing_city();
        $estado = $order->get_billing_state();
        $endereco = $order->get_billing_address_1();
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
                        "street"=>$endereco.' '.$number.' '.$bairro,
                        "city"=>$cidade,
                        "uf"=> $estado,
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
        u4crypto_registerError($tuBillet["response"]["message"] . ": " . $tuBillet["response"]["code"] . " - Item: Novo pedido por Pix - Regerar " . " - Resposta: " . $content);
        WC_Gateway_u4crypto_Pix::u4crypto_registro_logs_pix('Cobrança com erro: ' . $response->message, $order_id);
        WC_Gateway_u4crypto_Pix::u4crypto_registro_logs_pix($content, $order_id);
    }
}

/**
 * Operate U4Crypto on the order screen
 */
add_action('woocommerce_order_details_after_order_table', 'u4crypto_qrcode_field_display_cust_order_meta', 10, 1);
function u4crypto_qrcode_field_display_cust_order_meta($order)
{
    $method = get_post_meta($order->get_id(), '_payment_method', true);
    if ($method != 'u4cryptoqrcode') {
        return;
    }

    $link = get_post_meta($order->get_id(), 'u4cryptoQRcodePayment', true);

    $setings = wc_get_payment_gateway_by_order($order->get_id());
    echo '<p><strong>' . __($setings->settings['instructions'], 'u4crypto_qrcode') . '</strong></p>';
    if ($link != '') {
        echo '<img src="' . (new QRCode)->render($link) . '" />';
    }

}
