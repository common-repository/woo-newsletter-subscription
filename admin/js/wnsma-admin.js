jQuery(document).ready(function(){
    jQuery('[data-toggle="tooltip"]').tooltip();

    jQuery('#alert-close').click(function() {
    jQuery('.alert').css('display','none');
});

jQuery(document).on('change', "#ns_ecommerce",function(){

    if(document.getElementById('ns_ecommerce').checked) {
    jQuery("#mailchimp_store_id_div").show();
      } else {
        jQuery("#mailchimp_store_id_div").hide();
      }
});


jQuery(document).on('change', "#ns_service_provider",function(){

  if(  jQuery('#ns_service_provider').val() == 'mailchimp')
  {
      jQuery('#MailChimp_api_setting').removeClass('display-none');
      jQuery('#CMonitor_api_setting').addClass('display-none');

      jQuery('#MailChimp_fields_setting').removeClass('display-none');
      jQuery('#CMonitor_fields_setting').addClass('display-none');
  }

  if(  jQuery('#ns_service_provider').val() == 'cmonitor')
  {
    jQuery('#MailChimp_api_setting').addClass('display-none');
    jQuery('#CMonitor_api_setting').removeClass('display-none');

    jQuery('#MailChimp_fields_setting').addClass('display-none');
    jQuery('#CMonitor_fields_setting').removeClass('display-none');
  }

});

jQuery(document).on('change', "#ns_mailchimp_list",function(){

  var mailChimpList = jQuery("#ns_mailchimp_list").val();
  var data = "action=ns_load_mail_chimp_list&mailChimpList="+mailChimpList;

  jQuery.ajax({
                        url: ajaxurl,
                        data: data,
                        type: "POST",
                        success: function(result){
                          jQuery( "#MailChimp_fields_setting" ).html(result);
                        }
                    });

});


jQuery(document).on('click', "#mc_add_field",function(){

  var mailChimpList = jQuery("#ns_mailchimp_list").val();

  var data = "action=ns_add_mail_chimp_list&mailChimpList="+mailChimpList;

  jQuery('#mc_fields_list tbody').append("<tr id='temp_row'><td colspan='3' align='center'>Loading...</td></tr>");

  jQuery.ajax({
                        url: ajaxurl,
                        data: data,
                        type: "POST",
                        success: function(result){
                          jQuery('#mc_fields_list tbody').append(result);
                          var ns_mailchimp_merge_fields_length = jQuery("#ns_mailchimp_merge_fields_length").val();
                          jQuery("#ns_mailchimp_merge_fields_length").val(parseInt(ns_mailchimp_merge_fields_length)+1);
                          jQuery('#temp_row').closest('tr').remove();
                        }
                    });


    });

    jQuery(document).on('click', ".mc_remove_list",function(){

      if (confirm('Do you want to remove this field? ')) {
        var ns_mailchimp_merge_fields_length = jQuery("#ns_mailchimp_merge_fields_length").val();
        jQuery("#ns_mailchimp_merge_fields_length").val(parseInt(ns_mailchimp_merge_fields_length)-1);
            jQuery(this).closest('tr').remove();
        }

        });


});




function wnsma_save_settings(e){
        e.preventDefault();

	var ns_service_provider = document.getElementById("ns_service_provider").value;
  var ns_checkbox_status = document.getElementById("ns_checkbox_status").value;

	var ns_checkbox_label = document.getElementById("ns_checkbox_label").value;
  var ns_nonce = document.getElementById("nonce").value;

  var generat_setting_data = "action=ns_save_settings&service_provider="+ns_service_provider+ "&checkbox_status="+ns_checkbox_status+
          "&checkbox_label="+ns_checkbox_label+"&newsletter_nonce="+ns_nonce;

  //mailchimp setting
  var mailCimp_data = '';
	var ns_mailchimp_api = document.getElementById("ns_mailchimp_api").value;
	var ns_mailchimp_list = document.getElementById("ns_mailchimp_list").value;
  var ns_store_id = document.getElementById("ns_store_id").value;

	var ns_double_opt = jQuery("#ns_double_opt").is(':checked')?"yes":"no";
  var ns_ecommerce = jQuery("#ns_ecommerce").is(':checked')?"yes":"no";

  var ns_mailchimp_merge_fields_length = document.getElementById("ns_mailchimp_merge_fields_length").value;
  var merger_tag_with_checkout_name = '';
  for(var i=0; i<ns_mailchimp_merge_fields_length; i++)
  {
    var merge_field_tag = document.getElementsByName("merge_field_tags")[i].value;
    var checkout_field_name = document.getElementsByName("checkout_field_key")[i].value;
    if(merge_field_tag!= '' && checkout_field_name!= '')
    merger_tag_with_checkout_name += merge_field_tag+":"+checkout_field_name+",";
  }

  mailCimp_data = "&mailchimp_api="+ns_mailchimp_api+"&store_id="+ns_store_id+
  "&mailchimp_list="+ns_mailchimp_list+"&double_opt="+ns_double_opt+"&ecommerce="+ns_ecommerce+
  "&merger_tag_with_checkout_name="+merger_tag_with_checkout_name;

  //Campaign Monitor setting
  var cmonitor_data = '';
  if (jQuery('#ns_cmonitor_api').length) {

	  var i = document.getElementById('i');

    var ns_cmonitor_api = document.getElementById("ns_cmonitor_api").value;
    var ns_cmonitor_list = document.getElementById("ns_cmonitor_list").value;
    var ns_cmonitor_double_opt = jQuery("#ns_cmonitor_double_opt").is(':checked')?"yes":"no";

    var ns_cmonitor_custom_fields_length = document.getElementById("ns_cmonitor_custom_fields_length").value;
    var custom_key_with_checkout_name = '';
    for(var i=0; i<ns_cmonitor_custom_fields_length; i++)
    {
      var custom_field_key = document.getElementsByName("custom_field_key")[i].value;
      var cmonitor_checkout_fields = document.getElementsByName("cmonitor_checkout_fields")[i].value;
      if(custom_field_key!= '' && cmonitor_checkout_fields!= '')
      custom_key_with_checkout_name += custom_field_key+":"+cmonitor_checkout_fields+",";
    }

    cmonitor_data = "&cmonitor_api="+ns_cmonitor_api+"&cmonitor_list="+ns_cmonitor_list+
    "&cmonitor_double_opt="+ns_cmonitor_double_opt+"&custom_key_with_checkout_name="+custom_key_with_checkout_name;

  }


	data = generat_setting_data + mailCimp_data + cmonitor_data;

	jQuery.ajax({
                        url: ajaxurl,
                        data: data,
                        type: "POST",
                        security: ns_nonce,
                        success: function(result){
                           location.reload(true);
                        }
                    });
}
