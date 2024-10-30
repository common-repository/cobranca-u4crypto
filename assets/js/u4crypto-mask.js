function u4cryptoMask(){
    if(jQuery('#u4cripto-card-number') !== undefined){
        jQuery('#u4cripto-card-number').mask('0000 0000 0000 0000');
        jQuery('#u4cripto-card-expiry').mask('00/00');
        jQuery('#u4cripto-card-cvc').mask('000');
    }
}
window.onload = function() {
    setTimeout(function(){
        u4cryptoMask();
    }, 3000);
};

window.onload = function() {
	jQuery('#billing_persontype').on('change', function() {
		if(this.value === "2"){
			jQuery('#billing_company_field')[0].childNodes[0].childNodes[1].innerHTML="*";
			jQuery('#billing_company').attr('required','true');
			jQuery('#billing_company_field')[0].childNodes[0].childNodes[1].className="required";
		}else{
			jQuery('#billing_company').attr('required','false');
		}
	});
	if(jQuery('#billing_persontype') !== undefined){
		if(jQuery('#billing_persontype').val() === "2"){
			jQuery('#billing_company_field')[0].childNodes[0].childNodes[1].innerHTML="*";
			jQuery('#billing_company').attr('required','true');
			jQuery('#billing_company_field')[0].childNodes[0].childNodes[1].className="required";
		}
	}
}