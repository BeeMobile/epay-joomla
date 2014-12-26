jQuery().ready(function($) {

	$('.cc_type_sandbox').change(function() {
		var pmid = $(this).attr('rel');
		var cc_type = $('#cc_type_'+pmid).val();
		switch (cc_type) {
			case 'Visa':
				$('#cc_number_'+pmid).val('4007000000027');
				$('#cc_cvv_'+pmid).val('123');
				break;
			case 'Mastercard':
				$('#cc_number_'+pmid).val('6011000000000012');
				$('#cc_cvv_'+pmid).val('123');
				break;
			case 'Amex':
				$('#cc_number_'+pmid).val('370000000000002');
				$('#cc_cvv_'+pmid).val('1234');
				break;
			case 'Discover':
				$('#cc_number_'+pmid).val('5424000000000015');
				$('#cc_cvv_'+pmid).val('123');
                break;
            case 'Maestro':
                $('#cc_number_'+pmid).val('6763318282526706');
                $('#cc_cvv_'+pmid).val('123');
				break;
			default:
				$('#cc_number_'+pmid).val('');
				$('#cc_cvv_'+pmid).val('');
		}
	});
	
	$('.cc_type_sandbox').trigger('change');

	$('input[name=virtuemart_paymentmethod_id]').change(function() {
		var selectedMethod = $('input[name=virtuemart_paymentmethod_id]:checked').val();
		//$('.paymentMethodOptions').hide();
		$('#paymentMethodOptions_'+selectedMethod).show();
	});

	$('input[name=virtuemart_paymentmethod_id]').trigger('change');
	
});
