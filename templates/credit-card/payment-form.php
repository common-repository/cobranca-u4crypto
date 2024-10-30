<?php
/**
 * Cobrança U4crypto - Checkout form.
 *
 * @author  Diletec
 * @package WooCommerce_Pagarme/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<fieldset id="u4cripto-credit-cart-form">
	<p class="form-row">
		<label for="u4cripto-card-holder-name"><?php esc_html_e( 'Card Holder Name', 'u4crypto_boleto' ); ?><span class="required">*</span></label>
		<input name="cardname" id="u4cripto-card-holder-name" class="input-text" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<p class="form-row">
		<label for="u4cripto-card-number"><?php esc_html_e( 'Card Number', 'u4crypto_boleto' ); ?> <span class="required">*</span></label>
		<input name="cardnumber" id="u4cripto-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<div class="clear"></div>
	<p class="form-row form-row-first">
		<label for="u4cripto-card-expiry"><?php esc_html_e( 'Expiry (MM/YY)', 'u4crypto_boleto' ); ?> <span class="required">*</span></label>
		<input name="carddate" id="u4cripto-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="<?php esc_html_e( 'MM / YY', 'u4crypto_boleto' ); ?>" style="font-size: 1.5em; padding: 8px;" required />
	</p>
	<p class="form-row form-row-last">
		<label for="u4cripto-card-cvc"><?php esc_html_e( 'Card Code', 'u4crypto_boleto' ); ?> <span class="required">*</span></label>
		<input name="cardcode" id="u4cripto-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="<?php esc_html_e( 'CVV', 'u4crypto_boleto' ); ?>" style="font-size: 1.5em; padding: 8px;" required />
	</p>
	<div class="clear"></div>
	<?php if ( apply_filters( 'wc_u4cripto_allow_credit_card_installments', 1 < $max_installment ) ) : ?>
		<p class="form-row form-row-wide">
			<label for="u4cripto-card-installments"><?php esc_html_e( 'Installments', 'u4crypto_boleto' ); ?> <span class="required">*</span></label>
			<select name="u4cripto_installments" id="u4cripto-installments" style="font-size: 1.5em; padding: 8px; width: 100%;">
				<option value="0"><?php printf( esc_html__( 'Please, select the number of installments', 'u4crypto_boleto' ) ); ?></option>
				<?php
				foreach ( $installments as $number => $installment ) :
					if ( 1 !== $number && $smallest_installment > $installment['installment_amount'] ) {
						//break;
					}

					$interest           = ( ( $cart_total * 100 ) < $installment['amount'] ) ? sprintf( __( '(total of %s)', 'u4crypto_boleto' ), strip_tags( wc_price( $installment['amount'] / 100 ) ) ) : __( '(interest-free)', 'u4crypto_boleto' );
					$installment_amount = strip_tags( wc_price( $installment['installment_amount'] / 100 ) );
					?>
				<option value="<?php echo absint( $installment['installment'] ); ?>">
                    <?php
                        printf( esc_html__( '%1$dx of %2$s %3$s', 'u4crypto_boleto' ), absint( $installment['installment'] ), esc_html( $installment_amount ), esc_html( $interest ) );
                    ?>
                </option>
				<?php endforeach; ?>
			</select>
		</p>
	<?php endif; ?>
</fieldset>
<script src="<?php echo plugin_dir_url(__DIR__).'../assets/js/u4crypto-mask.js?ver=1.0.1'; ?>" type="text/javascript" defer=""></script>