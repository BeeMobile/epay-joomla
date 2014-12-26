<?php
defined('JPATH_BASE') or die();

jimport('joomla.form.formfield');
class JFormFieldGetKkb extends JFormField {

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 */
	var $type = 'getKkb';

	protected function getInput() {

		JHtml::_('behavior.colorpicker');

		vmJsApi::addJScript( '/plugins/vmpayment/kkb/kkb/assets/js/admin.js');
		vmJsApi::css('kkb', 'plugins/vmpayment/kkb/kkb/assets/css/');


        return 'Epay.kkb.kz';
	}

}