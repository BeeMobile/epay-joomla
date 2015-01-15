<?php
defined ('_JEXEC') or die('Restricted access');

if (!class_exists ('vmPSPlugin')) {
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}


if (!class_exists ('KkbHelper')) {
    require(VMPATH_ROOT . DS.'plugins'.DS.'vmpayment'.DS.'kkb'.DS.'kkb'.DS.'helpers'.DS.'KkbHelper.php');
}

class plgVmPaymentKkb extends vmPSPlugin {
    function __construct (& $subject, $config) {
        parent::__construct ($subject, $config);
        $varsToPush = $this->getVarsToPush ();
        $this->setConfigParameterable ($this->_configTableFieldName, $varsToPush);

        $this->tableFields = array_keys ($this->getTableSQLFields ());
        $this->_tablepkey = 'id';
        $this->_tableId = 'id';
    }


    function plgVmDeclarePluginParamsPayment ($name, $id, &$data) {

        return $this->declarePluginParams ('payment', $name, $id, $data);
    }

    function plgVmSetOnTablePluginParamsPayment ($name, $id, &$table) {

        return $this->setOnTablePluginParams ($name, $id, $table);
    }


    /**
     * Create the table for this plugin if it does not yet exist.
     *
     * @author ValÃ©rie Isaksen
     */
    public function getVmPluginCreateTableSQL () {

        return $this->createTableSQL ('Kkb Payment Table');
    }

    /**
     * Fields to create the payment table
     *
     * @return string SQL Fileds
     */
    function getTableSQLFields () {
        $SQLfields = array(
            'id'                          => 'int(1) UNSIGNED NOT NULL AUTO_INCREMENT',
            'virtuemart_order_id'         => 'int(1) UNSIGNED',

            'order_number' => 'char(64)',
            'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED',
            'payment_name' => 'varchar(5000)',
            'payment_order_total' => 'decimal(15,5) NOT NULL',
            'payment_currency' => 'smallint(1)',
            'email_currency' => 'smallint(1)',
            'cost_per_transaction' => 'decimal(10,2)',
            'cost_percent_total' => 'decimal(10,2)',
            'success' => 'smallint(1)',


            'kkb_fullresponse' => 'text',


        );

        return $SQLfields;
    }

    /**
     * Create the table for this plugin if it does not yet exist.
     * This functions checks if the called plugin is active one.
     * When yes it is calling the standard method to create the tables
     *
     * @author ValÃ©rie Isaksen
     *
     */
    function plgVmOnStoreInstallPaymentPluginTable ($jplugin_id) {

        return $this->onStoreInstallPluginTable ($jplugin_id);
    }


    /**
     * plgVmDisplayListFEPayment
     * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
     *
     * @param object  $cart Cart object
     * @param integer $selected ID of the method selected
     * @return boolean True on succes, false on failures, null when this plugin was not selected.
     * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
     *
     * @author Valerie Isaksen
     * @author Max Milbers
     */
    public function plgVmDisplayListFEPayment (VirtueMartCart $cart, $selected = 0, &$htmlIn) {

        return $this->displayListFE ($cart, $selected, $htmlIn);
    }


    /**
     * Check if the payment conditions are fulfilled for this payment method
     *
     * @author: Valerie Isaksen
     *
     * @param $cart_prices: cart prices
     * @param $payment
     * @return true: if the conditions are fulfilled, false otherwise
     *
     */
    protected function checkConditions ($cart, $method, $cart_prices) {

       return true;
    }


    function plgVmOnConfirmedOrderStorePaymentData ($virtuemart_order_id, $orderData, $priceData) {
        if (!$this->selectedThisPayment ($this->_pelement, $orderData->virtuemart_paymentmethod_id)) {
            return NULL; // Another method was selected, do nothing
        }
        return FALSE;
    }

    /**
     *
     * @param $cart
     * @param $order
     * @return bool|null|void
     */
    function plgVmConfirmedOrder($cart, $order) {

        if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }

        $lang     = JFactory::getLanguage();
        $filename = 'com_virtuemart';
        $lang->load($filename, JPATH_ADMINISTRATOR);
        $vendorId = 0;

        $session        = JFactory::getSession();
        $return_context = $session->getId();
        $this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');

        $html = "";

        if (!class_exists('VirtueMartModelOrders'))
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');

        //if (!$method->payment_currency)
        $this->getPaymentCurrency($method);

//        $paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
//        $amount = ceil($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, FALSE) * 100)/100;

        $payment_currency_id = shopFunctions::getCurrencyIDByName('KZT');
        $totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($order['details']['BT']->order_total, $payment_currency_id);
        $amount = $totalInPaymentCurrency['value'];
        $amount = 5;

        $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order['details']['BT']->order_number);

        $success_url = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=orders&layout=details&order_number=' . $order['details']['BT']->order_number . '&order_pass=' . $order['details']['BT']->order_pass);
        $fail_url    = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id);
        $result_url  = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&pelement=epay_kkb&order_number=' . $virtuemart_order_id);


        $jmlThisDocument = JFactory::getDocument();
        switch ($jmlThisDocument->language) {
            case 'en-gb':
                $language = 'eng';
                break;
            case 'ru-ru':
                $language = 'rus';
                break;
            case 'kz-kz':
                $language = 'rus';
                break;
            default:
                $language = 'eng';
        }

        $currency_id = 398; //KZT

        $helper = $this->getKkbHelper($method);
        //$amount = 600;
        //$virtuemart_order_id = 19154;
        $content = $helper->process_request($virtuemart_order_id, $currency_id, $amount);

        $email   = $order['details']['BT']->email;

        $this->_virtuemart_paymentmethod_id      = $order['details']['BT']->virtuemart_paymentmethod_id;
        $dbValues['payment_name']                = $this->renderPluginName($method);
        $dbValues['order_number']                = $order['details']['BT']->order_number;
        $dbValues['virtuemart_paymentmethod_id'] = $this->_virtuemart_paymentmethod_id;
        $dbValues['payment_currency']            = $payment_currency_id;
        $dbValues['payment_order_total']         = $amount;
        $this->storePSPluginInternalData($dbValues);

        $html = '<form name="vm_epay_kkb_form" id="vm_epay_kkb_form" method="post" action="' . $helper->getActionUrl() . '">
		   <input type="hidden" name="Signed_Order_B64" value="' . $content . '">
		   <input type="hidden" name="email" value="' . $email . '">
		   <input type="hidden" name="Language" value="' . $language . '">
		   <input type="hidden" name="BackLink" value="' . $success_url . '">
		   <input type="hidden" name="FailureBackLink" value="' . $fail_url . '">
		   <input type="hidden" name="PostLink" value="' . $result_url . '">
		   <input type="submit" value="Pay now">
		</form>';
        //$html .= '<script type="text/javascript">';
        //$html .= 'document.forms.vm_epay_kkb_form.submit();';
        //$html .= '</script>';

        $cart->_confirmDone = FALSE;
        $cart->_dataValidated = FALSE;
        $cart->setCartIntoSession();
        JRequest::setVar('html', $html);

    }




    public function plgVmOnSelectCheckPayment (VirtueMartCart $cart, &$msg) {
        return $this->OnSelectCheck ($cart);
    }

    function plgVmOnCheckAutomaticSelectedPayment (VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) {

        return $this->onCheckAutomaticSelected ($cart, $cart_prices, $paymentCounter);
    }

    /**
     * Display stored payment data for an order
     *
     * @see components/com_virtuemart/helpers/vmPSPlugin::plgVmOnShowOrderBEPayment()
     */
    function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id) {

        if (!$this->selectedThisByMethodId($payment_method_id)) {
            return NULL; // Another method was selected, do nothing
        }
        if (!($this->_currentMethod = $this->getVmPluginMethod($payment_method_id))) {
            return FALSE;
        }
        if (!($payments = $this->_getKkbInternalData($virtuemart_order_id))) {
            // JError::raiseWarning(500, $db->getErrorMsg());
            return '';
        }

        //$html = $this->renderByLayout('orderbepayment', array($payments, $this->_psType));
        $html = '<table class="adminlist table" >' . "\n";
        $html .= $this->getHtmlHeaderBE();
        $first = TRUE;
        foreach ($payments as $payment) {
            $html .= ' <tr class="row1"><td><strong>' . vmText::_('VMPAYMENT_KKB_DATE') . '</strong></td><td align="left"><strong>' . $payment->created_on . '</strong></td></tr> ';
            // Now only the first entry has this data when creating the order
            if ($first) {
                $html .= $this->getHtmlRowBE('COM_VIRTUEMART_PAYMENT_NAME', $payment->payment_name);
                // keep that test to have it backwards compatible. Old version was deleting that column  when receiving an IPN notification
                if ($payment->payment_order_total and  $payment->payment_order_total != 0.00) {
                    $html .= $this->getHtmlRowBE('COM_VIRTUEMART_TOTAL', $payment->payment_order_total . " " . shopFunctions::getCurrencyByID($payment->payment_currency, 'currency_code_3'));
                }

                $first = FALSE;
            } else {

                if (isset($payment->kkb_fullresponse) and !empty($payment->kkb_fullresponse)) {
                    $kkb_data = (array)json_decode($payment->kkb_fullresponse);

                    $html .= '<tr><td></td><td>
    <a href="#" class="KkbLogOpener" rel="' . $payment->id . '" >
        <div style="width: 900px;overflow: scroll;background-color: white; z-index: 100; right:0; display: none; border:solid 2px; padding:10px;" class="vm-absolute" id="KkbLog_' . $payment->id . '">';

                    if (is_array($kkb_data))
                    {
                        foreach ($kkb_data as $key => $value) {
                            $html .= ' <b>' . $key . '</b>:&nbsp;' . $value . '<br />';
                        }
                    } else {
                        $html .= htmlspecialchars($kkb_data);
                    }


                    $html .= ' </div>
        <span class="icon-nofloat vmicon vmicon-16-xml"></span>&nbsp;';
                    $html .= vmText::_('VMPAYMENT_KKB_VIEW_TRANSACTION_LOG');
                    $html .= '  </a>';
                    $html .= ' </td></tr>';
                }
            }


        }
        $html .= '</table>' . "\n";

        $doc = JFactory::getDocument();
        $js = "
	jQuery().ready(function($) {
		$('.KkbLogOpener').click(function() {
			var logId = $(this).attr('rel');
			$('#KkbLog_'+logId).toggle();
			return false;
		});
	});";
        $doc->addScriptDeclaration($js);
        return $html;

    }


    /**
     *  Order status changed
     * @param $order
     * @param $old_order_status
     * @return bool|null
     */
    public function plgVmOnUpdateOrderPayment(&$order, $old_order_status) {

        //Load the method
        if (!($this->_currentMethod = $this->getVmPluginMethod($order->virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }

        if (!$this->selectedThisElement($this->_currentMethod -> payment_element)) {
            return NULL;
        }

        //Load only when updating status to shipped
        if ($order->order_status != $this->_currentMethod->status_capture AND $order->order_status != $this->_currentMethod->status_refunded) {
            //return null;
        }

        //Load the payments
        if (!($payments = $this->_getKkbInternalData($order->virtuemart_order_id))) {
            // JError::raiseWarning(500, $db->getErrorMsg());
            return null;
        }


        $payments = array_reverse($payments);
        //$this->_currentMethod->paypalproduct = $this->($this->_currentMethod);
        foreach ($payments as $_payment)
        {
            if ($_payment->success)
            {
                $payment = $_payment;
                break;
            }
        }


        if (isset($payment->kkb_fullresponse) and !empty($payment->kkb_fullresponse)) {
            $result = (array)json_decode($payment->kkb_fullresponse);

            if ($this->_currentMethod->payment_approve == 'manual' and $order->order_status == $this->_currentMethod->status_capture) {
                $helper = $this->getKkbHelper($this->_currentMethod);
                if (isset($result['PAYMENT_REFERENCE']) && isset($result['PAYMENT_APPROVAL_CODE']) && isset($result['ORDER_ORDER_ID']) && isset($result['ORDER_CURRENCY']) && isset($result['PAYMENT_AMOUNT']))
                {
                    $ok = $this->approvePayment($this->_currentMethod, $payment, $result);

                }

            } elseif ($this->_currentMethod->payment_approve == 'manual' and ($order->order_status == $this->_currentMethod->status_refunded OR $order->order_status == $this->_currentMethod->status_canceled)) {
                $ok = $this->refundPayment($this->_currentMethod, $payment, $result);
            }
        }



        return true;

    }


    /**
     * Callback from E-Pay to notify store about processed payment
     */
    public function plgVmOnPaymentNotification() {
        if (JRequest::getVar('pelement') != 'epay_kkb') {
            return null;
        }
        if (!class_exists('VirtueMartModelOrders'))
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');

        $orderid = JRequest::getVar('order_number');
        $payment = $this->getDataByOrderId($orderid);
        $method  = $this->getVmPluginMethod($payment->virtuemart_paymentmethod_id);
        if ($method) {
            $helper = $this->getKkbHelper($method);

            $result = 0;
            if(isset($_POST["response"])) { $response = $_POST["response"]; };
            $result = $helper->process_response(stripslashes($response));




            $err = false;
            $err_message = date('d.m.Y H:i:s').' ';
            if (is_array($result)) {
                if (in_array("ERROR",$result)){
                    $err = true;
                    if ($result["ERROR_TYPE"]=="ERROR"){
                        $err_message.= "System error:".$result["ERROR"]."\n";
                    } elseif ($result["ERROR_TYPE"]=="system"){
                        $err_message.= "Bank system error > Code: '".$result["ERROR_CODE"]."' Text: '".$result["ERROR_CHARDATA"]."' Time: '".$result["ERROR_TIME"]."' Order_ID: '".$result["RESPONSE_ORDER_ID"]."'";
                    } elseif ($result["ERROR_TYPE"]=="auth"){
                        $err_message.= "Bank system user autentication error > Code: '".$result["ERROR_CODE"]."' Text: '".$result["ERROR_CHARDATA"]."' Time: '".$result["ERROR_TIME"]."' Order_ID: '".$result["RESPONSE_ORDER_ID"]."'";
                    };
                };
                if (in_array("DOCUMENT", $result)){
                    $order_id = ltrim($result['ORDER_ORDER_ID'], '0');
                };
            }
            else {
                $err = true;
                $err_message.= "System error: ".$result;
            };


            if ($err)
            {
                $response_fields['success'] = 0;
            } else
            {
                $response_fields['success'] = 1;
            }
            //$response_fields['id'] = $payment->id;
            if ($result) {
                $response_fields['kkb_fullresponse'] = json_encode($result);
            }
            $response_fields['order_number'] = $payment->order_number;

            $response_fields['virtuemart_order_id'] = $payment->virtuemart_order_id;
            $response_fields['virtuemart_paymentmethod_id'] = $payment->virtuemart_paymentmethod_id;


            //$preload=true   preload the data here too preserve not updated data
            $this->storePSPluginInternalData($response_fields, $this->_tablepkey, 0);




            if ($err) {
                $fp = fopen(dirname(__FILE__) . DS . 'error.txt', 'a');
                fwrite($fp, $err_message."\n");
                fclose($fp);
                $status = $method->status_cancel;
            }
            else {
                $message = date('d.m.Y H:i:s').' Payment successfull. Order ID:'.$order_id;
                $fp = fopen(dirname(__FILE__) . DS .'success.txt', 'a');
                fwrite($fp, $message."\n");
                fclose($fp);

                if ($method->payment_approve == 'automatic')
                {

                    $success = $this->approvePayment($method, $payment, $result);

                    if ($success)
                    {
                        $status = $method->status_success;
                    } else
                    {
                        $status = $method->status_pending;
                    }


                } else {
                    $status = $method->status_pending;
                }
            }

            $order = array();
            $order['order_status'] = $status;

            if ($status == $method->status_pending) {
                $order['customer_notified'] = 1;
                $order['comments'] = JTExt::sprintf('VMPAYMENT_KKB_STATUS_PENDING_COMMENT', $orderid);

            } else if ($status == $method->status_success)
            {
                $order['customer_notified'] = 1;
                $order['comments'] = JTExt::sprintf('VMPAYMENT_KKB_STATUS_CONFIRMED_COMMENT', $orderid);

            } else {
                $order['customer_notified'] = 0;
                $order['comments'] = $err_message;
            }
            if (!class_exists('VirtueMartModelOrders'))
                require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
            $modelOrder = new VirtueMartModelOrders();
            ob_start();
            $modelOrder->updateStatusForOneOrder($order_id, $order, true);
            ob_end_clean();
        }
        exit;
        return null;
    }

    function plgVmOnPaymentResponseReceived(&$html) {
        // the payment itself should send the parameter needed;

        $virtuemart_paymentmethod_id = JRequest::getInt('pm', 0);

        $vendorId = 0;
        if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }

        if (!class_exists('VirtueMartModelOrders'))
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');

        $order_number        = JRequest::getVar('on');
        $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
        $payment_name        = $this->renderPluginName($method);
        $html                = '<table>' . "\n";
        $html .= $this->getHtmlRow('EPAY_KKB_PAYMENT_NAME', $payment_name);
        $html .= $this->getHtmlRow('EPAY_KKB_ORDER_NUMBER', $virtuemart_order_id);
        $html .= $this->getHtmlRow('EPAY_KKB_STATUS', JText::_('VMPAYMENT_EPAY_KKB_STATUS_SUCCESS'));

        $html .= '</table>' . "\n";

        if ($virtuemart_order_id) {
            if (!class_exists('VirtueMartCart'))
                require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
            // get the correct cart / session
            $cart = VirtueMartCart::getCart();

            // send the email ONLY if payment has been accepted
            if (!class_exists('VirtueMartModelOrders'))
                require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
            $order      = new VirtueMartModelOrders();
            $orderitems = $order->getOrder($virtuemart_order_id);
            $cart->sentOrderConfirmedEmail($orderitems);
            $cart->emptyCart();
        }

        return true;
    }

    function plgVmOnUserPaymentCancel() {
        if (!class_exists('VirtueMartModelOrders'))
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');

        $order_number = JRequest::getVar('on');
        if (!$order_number)
            return false;
        $db    = JFactory::getDBO();
        $query = 'SELECT ' . $this->_tablename . '.`virtuemart_order_id` FROM ' . $this->_tablename . " WHERE  `order_number`= '" . $order_number . "'";

        $db->setQuery($query);
        $virtuemart_order_id = $db->loadResult();

        if (!$virtuemart_order_id) {
            return null;
        }
        $this->handlePaymentUserCancel($virtuemart_order_id);

        return true;
    }

    private function notifyCustomer($order, $order_info) {
        $lang     = JFactory::getLanguage();
        $filename = 'com_virtuemart';
        $lang->load($filename, JPATH_ADMINISTRATOR);
        if (!class_exists('VirtueMartControllerVirtuemart'))
            require(JPATH_VM_SITE . DS . 'controllers' . DS . 'virtuemart.php');

        if (!class_exists('shopFunctionsF'))
            require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
        $controller = new VirtueMartControllerVirtuemart();
        $controller->addViewPath(JPATH_VM_ADMINISTRATOR . DS . 'views');

        $view = $controller->getView('orders', 'html');
        if (!$controllerName)
            $controllerName = 'orders';
        $controllerClassName = 'VirtueMartController' . ucfirst($controllerName);
        if (!class_exists($controllerClassName))
            require(JPATH_VM_SITE . DS . 'controllers' . DS . $controllerName . '.php');

        $view->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/views/orders/tmpl');

        $db = JFactory::getDBO();
        $q  = "SELECT CONCAT_WS(' ',first_name, middle_name , last_name) AS full_name, email, order_status_name
			FROM #__virtuemart_order_userinfos
			LEFT JOIN #__virtuemart_orders
			ON #__virtuemart_orders.virtuemart_user_id = #__virtuemart_order_userinfos.virtuemart_user_id
			LEFT JOIN #__virtuemart_orderstates
			ON #__virtuemart_orderstates.order_status_code = #__virtuemart_orders.order_status
			WHERE #__virtuemart_orders.virtuemart_order_id = '" . $order['virtuemart_order_id'] . "'
			AND #__virtuemart_orders.virtuemart_order_id = #__virtuemart_order_userinfos.virtuemart_order_id";
        $db->setQuery($q);
        $db->query();
        $view->user  = $db->loadObject();
        $view->order = $order;
        JRequest::setVar('view', 'orders');
        $user = $this->sendVmMail($view, $order_info['details']['BT']->email, false);
        if (isset($view->doVendor)) {
            $this->sendVmMail($view, $view->vendorEmail, true);
        }
    }

    private function sendVmMail(&$view, $recipient, $vendor = false) {
        ob_start();
        $view->renderMailLayout($vendor, $recipient);
        $body = ob_get_contents();
        ob_end_clean();

        $subject = (isset($view->subject)) ? $view->subject : JText::_('COM_VIRTUEMART_DEFAULT_MESSAGE_SUBJECT');
        $mailer  = JFactory::getMailer();
        $mailer->addRecipient($recipient);
        $mailer->setSubject($subject);
        $mailer->isHTML(VmConfig::get('order_mail_html', true));
        $mailer->setBody($body);

        if (!$vendor) {
            $replyto[0] = $view->vendorEmail;
            $replyto[1] = $view->vendor->vendor_name;
            $mailer->addReplyTo($replyto);
        }

        if (isset($view->mediaToSend)) {
            foreach ((array) $view->mediaToSend as $media) {
                $mailer->addAttachment($media);
            }
        }
        return $mailer->Send();
    }


    public function plgVmonSelectedCalculatePricePayment (VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
        return $this->onSelectedCalculatePrice ($cart, $cart_prices, $cart_prices_name);
    }


    public function plgVmOnShowOrderFEPayment ($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
        $this->onShowOrderFE ($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    function plgVmonShowOrderPrintPayment ($order_number, $method_id) {
        return $this->onShowOrderPrint ($order_number, $method_id);
    }


    public function plgVmDeclarePluginParamsPaymentVM3( &$data) {
        return $this->declarePluginParams('payment', $data);
    }


    public function plgVmOnUpdateOrderLine ($_formData) {
        return NULL;
    }


    public function plgVmOnEditOrderLineBE ($_orderId, $_lineId) {
        return NULL;
    }


    public function plgVmOnShowOrderLineFE ($_orderId, $_lineId) {
        return NULL;
    }

    private function getKkbMode($method)
    {
        $sandbox = $method->sandbox;
        if ($sandbox)
        {
            $mode = 'SANDBOX';
        } else
        {
            $mode = 'LIVE';
        }

        return $mode;
    }



    private function getKkbHelper($method)
    {
        $mode = $this->getKkbMode($method);

        $safePath = VmConfig::get('forSale_path', '');
        $certificatePath = $safePath . 'kkb';

        $helper = new KkbHelper($method->{$mode . '_MERCHANT_CERTIFICATE_ID'}, $method->{$mode . '_MERCHANT_NAME'}, $method->{$mode . '_MERCHANT_ID'}, $certificatePath . '/' . $method->{$mode . '_PRIVATE_KEY'}, $method->{$mode . '_PRIVATE_KEY_PASS'}, $certificatePath . '/' . $method->{$mode . '_PUBLIC_KEY'}, $method->{$mode . '_URL'} );
        return $helper;
    }


    /**
     * @param $virtuemart_paymentmethod_id
     * @param $paymentCurrencyId
     * @return bool|null
     */
    function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {

        if (!($this->_currentMethod = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($this->_currentMethod->payment_element)) {
            return FALSE;
        }
        $this->_currentMethod->payment_currency = 'KZT';

        if (!class_exists('VirtueMartModelVendor')) {
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'vendor.php');
        }
        $vendorId = 1; //VirtueMartModelVendor::getLoggedVendor();
        $db = JFactory::getDBO();

        $q = 'SELECT   `virtuemart_currency_id` FROM `#__virtuemart_currencies` WHERE `currency_code_3`= "' . $this->_currentMethod->payment_currency . '"';
        $db->setQuery($q);
        $paymentCurrencyId = $db->loadResult();
    }


    /**
     * @param   int $virtuemart_order_id
     * @param string $order_number
     * @return mixed|string
     */
    private function _getKKbInternalData($virtuemart_order_id, $order_number = '') {
        if (empty($order_number)) {
            $orderModel = VmModel::getModel('orders');
            $order_number = $orderModel->getOrderNumber($virtuemart_order_id);
        }
        $db = JFactory::getDBO();
        $q = 'SELECT * FROM `' . $this->_tablename . '` WHERE ';
        $q .= " `order_number` = '" . $order_number . "'";

        $db->setQuery($q);
        if (!($payments = $db->loadObjectList())) {
            // JError::raiseWarning(500, $db->getErrorMsg());
            return '';
        }
        return $payments;
    }

    private function approvePayment($method, $payment, $result)
    {
        $helper = $this->getKkbHelper($method);
        $xml = $helper->process_complete($result['PAYMENT_REFERENCE'], $result['PAYMENT_APPROVAL_CODE'], (int)$result['ORDER_ORDER_ID'], $result['ORDER_CURRENCY'], $result['PAYMENT_AMOUNT']);

        $url = 'https://epay.kkb.kz/jsp/remote/control.jsp?'. urlencode($xml);

        $response = $helper->request($url);
        if ($response) {
            $response_fields['kkb_fullresponse'] = json_encode($response);
        }

        if (strpos(strtolower($response), 'error'))
        {
            $success =  0;
        } else {
            $success = 1;
            }

        $response_fields['success'] = $success;
        $response_fields['order_number'] = $payment->order_number;

        $response_fields['virtuemart_order_id'] = $payment->virtuemart_order_id;
        $response_fields['virtuemart_paymentmethod_id'] = $payment->virtuemart_paymentmethod_id;


        //$preload=true   preload the data here too preserve not updated data
        $this->storePSPluginInternalData($response_fields, $this->_tablepkey, 0);

        return $success;
    }

    private function refundPayment($method, $payment, $result)
    {
        $helper = $this->getKkbHelper($method);
        $xml = $helper->process_refund($result['PAYMENT_REFERENCE'], $result['PAYMENT_APPROVAL_CODE'], (int)$result['ORDER_ORDER_ID'], $result['ORDER_CURRENCY'], $result['PAYMENT_AMOUNT'], '');

        $url = 'https://epay.kkb.kz/jsp/remote/control.jsp?'. urlencode($xml);

        $response = $helper->request($url);
        if ($response) {
            $response_fields['kkb_fullresponse'] = json_encode($response);
        }

        if (strpos(strtolower($response), 'error'))
        {
            $success =  0;
        } else {
            $success = 1;
        }

        $response_fields['success'] = $success;
        $response_fields['order_number'] = $payment->order_number;

        $response_fields['virtuemart_order_id'] = $payment->virtuemart_order_id;
        $response_fields['virtuemart_paymentmethod_id'] = $payment->virtuemart_paymentmethod_id;


        //$preload=true   preload the data here too preserve not updated data
        $this->storePSPluginInternalData($response_fields, $this->_tablepkey, 0);

        return $success;
    }
}
//no close php tag