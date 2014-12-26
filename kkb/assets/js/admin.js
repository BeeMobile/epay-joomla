jQuery().ready(function ($) {

    /************/
    /* Handlers */
    /************/
    handleCredentials = function () {
        var sandbox = $("input[name='params[sandbox]']:checked").val();
        if (sandbox == 1) {
            var sandboxmode = 'sandbox';
        } else {
            var sandboxmode = 'production';
        }


        $('.std,.api,.live,.sandbox,.sandbox_warning, .accelerated_onboarding').parents('.control-group').hide();
        $('.get_sandbox_credentials').hide();
        $('.get_paypal_credentials').hide();

        if ( sandboxmode == 'production') {
            $('.live').parents('.control-group').show();
        } else if (sandboxmode == 'sandbox') {
            $('.sandbox').parents('.control-group').show();
        }

        if (sandboxmode == 'sandbox') {
            $('.sandbox_warning').parents('.control-group').show();
        }
    }



    /**********/
    /* Events */
    /**********/
    $("input[name='params[sandbox]']").change(function () {
        handleCredentials();
    });


    /*****************/
    /* Initial calls */
    /*****************/
    handleCredentials();

});
