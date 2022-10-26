jQuery( function ( $ ) {
  init_senangpay_meta();
  $(".senangpay_customize_senangpay_donations_field input:radio").on("change", function() {
    init_senangpay_meta();
  });

  function init_senangpay_meta(){
    if ("enabled" === $(".senangpay_customize_senangpay_donations_field input:radio:checked").val()){
      $(".senangpay_secret_key_field").show();
      $(".senangpay_merchant_id_field").show();
      $(".senangpay_description_field").show();
    } else {
      $(".senangpay_secret_key_field").hide();
      $(".senangpay_merchant_id_field").hide();
      $(".senangpay_description_field").hide();
    }
  }
});