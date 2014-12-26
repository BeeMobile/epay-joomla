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
            'transaction_id'                => 'char(64)',
            'kkb_sid'                => 'char(64)',
            'kkb_trx_id'                => 'char(64)',
            'product'                => 'varchar(255)',
            'quantity'                => 'varchar(255)',
            'merchant'                => 'varchar(255)',
            'buyer'                => 'varchar(255)',
            'total'         => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\'',
            'action'            => 'char(255)',
            'comments'        => 'varchar(255)',
            'referer'          => 'varchar(255)',
            'tax_id'            => 'smallint(1)',
            'kkb_trx_id'                => 'varchar(255)',
            'kkb_invoice_number'                => 'varchar(255)',
            'kkb_currency'                => 'varchar(255)',
            'kkb_trx_total'                => 'varchar(255)',
            'kkb_trx_fee'                => 'varchar(255)',
            'kkb_buyer_email'                => 'varchar(255)',
            'kkb_buyer_status'                => 'varchar(255)',
            'kkb_buyer_name'                => 'varchar(255)'

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

        if (!($this->_currentMethod = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }
//        echo '2222';
//        exit();
        if (!$this->selectedThisElement($this->_currentMethod->payment_element)) {
            return FALSE;
        }



        if (!class_exists('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }
        if (!class_exists('VirtueMartModelCurrency')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'currency.php');
        }
        $html='';
        $this->getPaymentCurrency($this->_currentMethod);
        $email_currency = $this->getEmailCurrency($this->_currentMethod);

        $payment_name = $this->renderPluginName($this->_currentMethod, $order);


        // Prepare data that should be stored in the database
        $dbValues['order_number'] = $order['details']['BT']->order_number;
        $dbValues['payment_name'] = $payment_name;
        $dbValues['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;
        $dbValues['cost_per_transaction'] = $this->_currentMethod->cost_per_transaction;
        $dbValues['cost_percent_total'] = $this->_currentMethod->cost_percent_total;
        $dbValues['payment_currency'] = $this->_currentMethod->payment_currency;
        $dbValues['email_currency'] = $email_currency;
        //$dbValues['payment_order_total'] = $paypalInterface->getTotal();
        $dbValues['tax_id'] = $this->_currentMethod->tax_id;
        $this->storePSPluginInternalData($dbValues);
        VmConfig::loadJLang('com_virtuemart_orders', TRUE);


        $html = $this->preparePost($this->_currentMethod, $order, $cart);
        // 	2 = don't delete the cart, don't send email and don't redirect
        $cart->_confirmDone = FALSE;
        $cart->_dataValidated = FALSE;
        $cart->setCartIntoSession();
        vRequest::setVar('html', $html);

    }

    public function plgVmOnSelectCheckPayment (VirtueMartCart $cart, &$msg) {
        return $this->OnSelectCheck ($cart);
    }

    function plgVmOnCheckAutomaticSelectedPayment (VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) {

        return $this->onCheckAutomaticSelected ($cart, $cart_prices, $paymentCounter);
    }



    function plgVmOnShowOrderBEPayment ($virtuemart_order_id, $payment_id) {
        if (!$this->selectedThisByMethodId ($payment_id)) {
            return NULL; // Another method was selected, do nothing
        }

        $db = JFactory::getDBO ();
        $_q = 'SELECT * FROM `' . $this->_tablename . '` '
            . 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
        $db->setQuery ($_q);
        if (!($paymentData = $db->loadObject ())) {
            // JError::raiseWarning(500, $db->getErrorMsg());
        }

        $_html = '<table class="adminlist table">' . "\n";
        $_html .= '	<thead>' . "\n";
        $_html .= '		<tr>' . "\n";
        $_html .= '			<th colspan="2" width="100%">' . vmText::_ ('COM_VIRTUEMART_ORDER_PRINT_PAYMENT_LBL') . '</th>' . "\n";
        $_html .= '		</tr>' . "\n";
        $_html .= '	</thead>' . "\n";
        $_html .= '	<tr>' . "\n";
        $_html .= '		<td>' . vmText::_ ('VMPAYMENT_HEIDELPAY_PAYMENT_RESULT') . '</td>' . "\n";
        if ($paymentData->processing_result == "ACK" AND $paymentData->payment_code == 80) {
            $_html .= '<td style="color: #FC0 ; font-weight:bold ">WAITING</td>';
        } elseif ($paymentData->processing_result == "ACK") {
            $_html .= '<td style="color: #55AA66; font-weight:bold">ACK</td>';
        }
        if ($paymentData->processing_result == "NOK") {
            $_html .= '<td style="color: #F00 ; font-weight:bold ">NOK</td>';
        }
        $_html .= '	</tr>' . "\n";
        $_html .= '	<tr>' . "\n";
        $_html .= '		<td>' . vmText::_ ('VMPAYMENT_HEIDELPAY_PAYMENT_METHOD') . '</td>' . "\n";
        $_html .= '		<td>' . $paymentData->payment_methode . '.' . $paymentData->payment_type . ' (' . $paymentData->payment_name . ')</td>' . "\n";
        $_html .= '	</tr>' . "\n";
        $_html .= '	<tr>' . "\n";
        $_html .= '		<td>UniqeID</td>' . "\n";
        $_html .= '		<td>' . $paymentData->unique_id . '</td>' . "\n";
        $_html .= '	</tr>' . "\n";
        $_html .= '	<tr>' . "\n";
        $_html .= '		<td>Short-ID</td>' . "\n";
        $_html .= '		<td>' . $paymentData->short_id . '</td>' . "\n";
        $_html .= '	</tr>' . "\n";
        $_html .= '	<tr>' . "\n";
        $_html .= '		<td>' . vmText::_ ('VMPAYMENT_HEIDELPAY_COMMENT') . '</td>' . "\n";
        $_html .= '		<td>' . $paymentData->comment . '</td>' . "\n";
        $_html .= '	</tr>' . "\n";
        $_html .= '</table>' . "\n";
        return $_html;
    }

    function plgVmgetPaymentCurrency ($virtuemart_paymentmethod_id, &$paymentCurrencyId) {
        if (!($method = $this->getVmPluginMethod ($virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement ($method->payment_element)) {
            return FALSE;
        }
        $this->getPaymentCurrency ($method);
        $paymentCurrencyId = $method->payment_currency;
    }

    function plgVmOnPaymentResponseReceived (&$html) {
        if (!class_exists ('VirtueMartCart')) {
            require(VMPATH_SITE . DS . 'helpers' . DS . 'cart.php');
        }
        if (!class_exists ('shopFunctionsF')) {
            require(VMPATH_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
        }
        if (!class_exists ('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }

        $virtuemart_paymentmethod_id = JRequest::getInt ('pm', 0);
        $order_number = JRequest::getString ('on', 0);

        if (!($method = $this->getVmPluginMethod ($virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement ($method->payment_element)) {
            return NULL;
        }

        if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ($order_number))) {
            return NULL;
        }
        //TODO code here
        return TRUE;
    }

    function plgVmOnUserPaymentCancel () {
        if (!class_exists ('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }
        $order_number = JRequest::getVar ('on');
        if (!$order_number) {
            return FALSE;
        }
        $virtuemart_paymentmethod_id = vRequest::getInt('pm', '');
        if (empty($order_number) or empty($virtuemart_paymentmethod_id) or !$this->selectedThisByMethodId($virtuemart_paymentmethod_id)) {
            return NULL;
        }
        $db = JFactory::getDBO ();
        $query = 'SELECT ' . $this->_tablename . '.`virtuemart_order_id` FROM ' . $this->_tablename . " WHERE  `order_number`= '" . $order_number . "'";

        $db->setQuery ($query);
        $virtuemart_order_id = $db->loadResult ();

        if (!$virtuemart_order_id) {
            return NULL;
        }
        return TRUE;
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

    public function plgVmOnUpdateOrderPayment ($_formData) {
        return NULL;
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


    public function preparePost($method, $order, $cart) {

        $self = $_SERVER['PHP_SELF'];
        $order_id = 12; //$order['details']['BT']->order_number;
        //TODO work with currencies
        $currency_id = "398";
        $amount = $order['details']['BT']->order_total;

        $helper = $this->getKkbHelper($method);
        $content = $helper->process_request($order_id, $currency_id, $amount);

        $backLink = JURI::root();
        $postLink = JURI::root() . 'index.php?option=com_virtuemart&view=vmplg&task=pluginUserPaymentCancel&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id . '&Itemid=' . vRequest::getInt('Itemid') . '&lang=' . vRequest::getCmd('lang', '');


        $mode = $this->getKkbMode($method);
        $html = '';
        $html .=
        '<form name="SendOrder" method="post" action="' . $method->{$mode . '_URL'} . '">
            <input type="hidden" name="Signed_Order_B64" value="' . $content . '">
            <input type="hidden" name="Language" value="eng">
            <input type="hidden" name="BackLink" value="' . $backLink . '">
            <input type="hidden" name="PostLink" value="'. $postLink . '">
            <input type="submit" name="GotoPay"  value="Go to epay" >
        </form>';

        return $html;
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

        $helper = new KkbHelper($method->{$mode . '_MERCHANT_CERTIFICATE_ID'}, $method->{$mode . '_MERCHANT_NAME'}, $method->{$mode . '_MERCHANT_ID'}, $certificatePath . '/' . $method->{$mode . '_PRIVATE_KEY'}, $method->{$mode . '_PRIVATE_KEY_PASS'}, $certificatePath . '/' . $method->{$mode . '_PUBLIC_KEY'} );
        return $helper;
    }

}
//no close php tag