
=== Cobrança U4crypto ===
Contributors: Diletec, u4crypto
Donate link: www.diletec.com.br
Tags: U4crypto, woocommerce, e-commerce, boleto, pix, shop, cart, checkout, payments, paypal, storefront, stripe, woo commerce, pagseguro, PicPay, Nubank
Requires at least: 4.3
Tested up to: 6.3.1
Requires PHP: 7.3
Stable tag: 1.5.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

# Cobrança U4crypto!

![enter image description here](https://www.diletec.com.br/wp-content/uploads/2019/04/logo-diletec-cor-01.png)
Olá! Esse Plugin foi desenvolvido pela **[www.diletec.com.br](www.diletec.com.br)** para adicionar o metodo de pagamento da U4crypto ao Wordpress Woocommerce.


# Configure

O plugin utiliza a rota de integração de billet erp, onde é necessário fornecer ao plugin os 3 tokens, sendo eles: **API Token, Partner Token e Customer Token.**



## U4crypto Tokens

Acesso o Painel da sua conta U4crypto e navegue até a Aba de Integrações, ao acessar a Aba você terá acesso aos **Tokens**



# Synchronization

A sincronização é por meio de callback, onde a U4crypto fará o envio de atualização do boleto para o seu e-commerce.


## Boleto PDF

Os boletos em PDF ficam hospedados no CDN da U4crypto e disponibilizados por links em cada venda.
Esses links são presentes tanto para os administradores do e-commerce quanto para os clientes



## Publication

Esse plugin tem a sua publicação oficial no diretório de plugins do  **WordPress** e em nosso **Git**. Certifique de que está utilizando a versão oficial.



## Fields

Os campos exigidos pelo plugin são campos obrigatórios para a geração de boleto e ou obrigatórios pelo Woocommerce.

|                |ASCII                          |HTML                         |
|----------------|-------------------------------|-----------------------------|
|Enable/Disable|`'false true'`            |checkbox            |
|Title          |`text`            |text            |
|Order Status          |`text`|text|
|Description          |`text`|textarea|
|Instructions          |`text`|textarea|
|API Token          |`text`|text|
|Partner Token          |`text`|text|
|Customer Token		|`pass`|text|

## Compatibility

Compatibilidade com os seguintes plugins

|Plugin|Compatibilidade|
|------|---------------|
|Woocommerce|Sim|
|Woocommerce Multistore|Sim|
|Woocommerce Subscriptions|Sim|
|Woocommerce Subscriptions Pro|Sim|
|Woocommerce Subscriptions Multistore|Sim|
|Enhancer for WooCommerce Subscriptions|Sim|
|Dokan|Sim|
|Dokan Multistore|Sim|
|Brazilian Market on WooCommerce|Sim|
|Claudio Sanches - PagSeguro for WooCommerce|Sim|

## Erros
**Erros comuns:** reporte-os em nosso Git ou na distribuição do Wordpress para que possamos realizar a correção.

**Falhas de segurança:** Envie para o nosso e-mail, não faça divulgação dessas informações e não as utilize para prejudicar terceiros.

[developers@diletec.com.br](mailto:developers@diletec.com.br)
[www.diletec.com.br](www.diletec.com.br)



== Changelog ==

= 1.0 =
* Lançamento do plugin.

== Changelog ==

= 1.2 =
* Lançamento da funcionalidade de cartão de credito.

= 1.2.1 =
* Correção de rota de produção.

= 1.2.2 =
* Correção de obrigatóriedade de cartão de credito mesmo quando se utiliza o boleto.

= 1.2.3 =
* Correções

= 1.2.4 =
* Correções

= 1.2.5 =
* Liberação do PIX
* Melhoria no e-mail

= 1.2.6 =
* Correção do botão de copiar

= 1.2.7 =
* Nome e CPF/CNPJ no Boleto e informações do PIX

= 1.2.8 =
* FIX: CNPJ sem dados de pessoa fisica

= 1.2.9 =
* FIX: Modificações no Webhook

= 1.3 =
* FIX: Token do Webhook

= 1.3.1 =
* FIX: CPF no cartão
* Rota de Cartão e boletos para Callback com modificações

= 1.3.2 =
* FIX: Class Woocommerce

= 1.3.3 =
* FIX: Class Woocommerce Alert

= 1.3.4 =
* FIX: Class Woocommerce Veryfication

= 1.4.0 =
* Feature: Woocommerce Subscription Billet
* Feature: Woocommerce Subscription Pix
* Feature: Dokan Billet

= 1.4.1 =
* FIX: Class WeDevs_Dokan

= 1.4.2 =
* Feature: QRCode U4Crypto
* Feature: Gerar novo boleto quando vencido

= 1.4.3 =
* Feature: Gerar boletos com falhas

= 1.4.4 =
* Feature: Gerar boleto quando não há um
* Feature: Gerar boleto quando a data de vencimento for menor que a data atual e o status for "on-hold"
* Feature: Tela de status
* Feature: Tela de Log de erros
* Feature: Menu do Plugin
* Feature: Link fácil para configurações da API de boleto
* Feature: Link fácil para configurações da API de Pix
* Feature: Link fácil para configurações da API de QRCode

= 1.4.5 =
* FIX: wc_add_notice
* FIX: $order->get_meta('_billing_company'); Mudar para $order->get_billing_company();

= 1.4.6 =
* Feature: Diretório de plugins em wp-content
* Fix: Correção de tela de log
* Fix: Chamada do QRCode quando não existe os dados, ou o pedido deu erro

= 1.4.7 =
* Feature: Adicionado o campo de Prazo máximo para pagamento do boleto
* Fix: Adeuquação para a versão 6.1.1 do Wordpress