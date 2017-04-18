<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class EftSECURE extends PaymentModule
{
    private $_html = '';
    private $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'eftsecure';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'WCST';
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(array('EFT_SECURE_DETAILS', 'EFT_SECURE_USERNAME', 'EFT_SECURE_PASSWORD'));
        if (!empty($config['EFT_SECURE_USERNAME'])) {
            $this->owner = $config['EFT_SECURE_USERNAME'];
        }
        if (!empty($config['EFT_SECURE_DETAILS'])) {
            $this->details = $config['EFT_SECURE_DETAILS'];
        }
        if (!empty($config['EFT_SECURE_PASSWORD'])) {
            $this->address = $config['EFT_SECURE_PASSWORD'];
        }

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('EFT Secure');
        $this->description = $this->l('Accept payments for your products via EFT Secure transfer.');
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');

        if (!isset($this->owner) || !isset($this->details) || !isset($this->address)) {
            $this->warning = $this->l('Account owner and account details must be configured before using this module.');
        }
        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }

        $this->extra_mail_vars = array(
            '{eftsecure_owner}' => Configuration::get('EFT_SECURE_USERNAME'),
            '{eftsecure_details}' => nl2br(Configuration::get('EFT_SECURE_DETAILS')),
            //'{eftsecure_address}' => nl2br(Configuration::get('EFT_SECURE_ADDRESS'))
        );
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('payment') || ! $this->registerHook('displayPaymentEU') || !$this->registerHook('paymentReturn') || !$this->registerHook('header')) {
            return false;
        }

        // insert new state
        $newState = new OrderState();
        
        $newState->send_email = true;
        $newState->module_name = 'eftsecure';
        $newState->invoice = false;
        $newState->color = "#002F95";
        $newState->unremovable = false;
        $newState->logable = false;
        $newState->delivery = false;
        $newState->hidden = false;
        $newState->shipped = false;
        $newState->paid = false;
        $newState->delete = false;

        $languages = Language::getLanguages(true);
        foreach ($languages as $language) {
            if ($language['iso_code'] == 'id') {
                $newState->name[(int)$language['id_lang']] = 'Menunggu pembayaran via EFT Secure';
            } else {
                $newState->name[(int)$language['id_lang']] = 'Awaiting EFT Secure Payment';
            }

            $newState->template = "eftsecure";
        }

        if ($newState->add()) {
            Configuration::updateValue('PS_OS_EFTSECURE', $newState->id);
            copy(dirname(__FILE__).'/logo.gif', _PS_IMG_DIR_.'os/'.(int)$newState->id.'.gif');
            foreach ($languages as $language) {
                if ($language['iso_code'] == 'id') {
                    copy(dirname(__FILE__).'/mails/id/eftsecure.html', _PS_MAIL_DIR_.'/'.strtolower($language['iso_code']).'/eftsecure.html');
                    copy(dirname(__FILE__).'/mails/id/eftsecure.txt', _PS_MAIL_DIR_.'/'.strtolower($language['iso_code']).'/eftsecure.txt');
                } else {
                    copy(dirname(__FILE__).'/mails/en/eftsecure.html', _PS_MAIL_DIR_.'/'.strtolower($language['iso_code']).'/eftsecure.html');
                    copy(dirname(__FILE__).'/mails/en/eftsecure.txt', _PS_MAIL_DIR_.'/'.strtolower($language['iso_code']).'/eftsecure.txt');
                }
            }
        } else {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!Configuration::deleteByName('EFT_SECURE_DETAILS') || !Configuration::deleteByName('EFT_SECURE_USERNAME') || !Configuration::deleteByName('EFT_SECURE_PASSWORD') || !parent::uninstall()) {
            return false;
        }
        return true;
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('EFT_SECURE_USERNAME')) {
                $this->_postErrors[] = $this->l('API username is required.');
            } elseif (!Tools::getValue('EFT_SECURE_PASSWORD')) {
                $this->_postErrors[] = $this->l('API password is required.');
            } else {
				$eftsecure_username = Tools::getValue('EFT_SECURE_USERNAME');
				$eftsecure_password = Tools::getValue('EFT_SECURE_PASSWORD');
				$response_data = $this->chkAuthorization($eftsecure_username, $eftsecure_password);
				if(!isset($response_data->token)){
					$this->_postErrors[] = $this->l($response_data->message);
				}
			}
        }
    }
	
	public function checkCurrencyzar($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        if ($currency_order->iso_code == 'ZAR') {
			return true;
        }
        return false;
    }
	
	public function chkAuthorization($eftsecure_username, $eftsecure_password)
    {
		$curl = curl_init('https://services.callpay.com/api/v1/token');
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_USERPWD, $eftsecure_username . ":" . $eftsecure_password);

		$response = curl_exec($curl);
		curl_close($curl);
		$response_data = json_decode($response);
		return $response_data;
	}

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('EFT_SECURE_DETAILS', Tools::getValue('EFT_SECURE_DETAILS'));
            Configuration::updateValue('EFT_SECURE_USERNAME', Tools::getValue('EFT_SECURE_USERNAME'));
            Configuration::updateValue('EFT_SECURE_PASSWORD', Tools::getValue('EFT_SECURE_PASSWORD'));
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    private function _displayEftSECURE()
    {
        return $this->display(__FILE__, 'infos.tpl');
    }

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->_displayEftSECURE();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function hookPayment($params)
    {
		$eftsecure_username = Configuration::get('EFT_SECURE_USERNAME');
		$eftsecure_password = Configuration::get('EFT_SECURE_PASSWORD');
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
		if (!$this->checkCurrencyzar($params['cart'])) {
            return;
        }
		if($eftsecure_username == '' AND $eftsecure_password == ''){
			return;
		}
        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_bw' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
        ));
        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookDisplayPaymentEU($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = array(
            'cta_text' => $this->l('Pay by EFT Secure'),
            'logo' => Media::getMediaPath(dirname(__FILE__).'/bankwire.jpg'),
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
        );

        return $payment_options;
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $state = $params['objOrder']->getCurrentState();
        if (in_array($state, array(Configuration::get('PS_OS_EFTSECURE'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID')))) {
            $this->smarty->assign(array(
                'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
                'eftsecureDetails' => Tools::nl2br($this->details),
                'eftsecureAddress' => Tools::nl2br($this->address),
                'eftsecureOwner' => $this->owner,
                'status' => 'ok',
                'id_order' => $params['objOrder']->id
            ));
            if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference)) {
                $this->smarty->assign('reference', $params['objOrder']->reference);
            }
        } else {
            $this->smarty->assign('status', 'failed');
        }
        return $this->display(__FILE__, 'payment_return.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('User details'),
                    'icon' => 'icon-user'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('API Username'),
                        'name' => 'EFT_SECURE_USERNAME',
                        'required' => true
                    ),
					 array(
                        'type' => 'text',
                        'label' => $this->l('API Password'),
                        'name' => 'EFT_SECURE_PASSWORD',
                        'required' => true
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Details'),
                        'name' => 'EFT_SECURE_DETAILS',
                        //'desc' => $this->l('Such as bank branch, IBAN number, BIC, etc.'),
						//'required' => true
                    ),            
                ),
                'submit' => array(
                'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'EFT_SECURE_DETAILS' => Tools::getValue('EFT_SECURE_DETAILS', Configuration::get('EFT_SECURE_DETAILS')),
            'EFT_SECURE_USERNAME' => Tools::getValue('EFT_SECURE_USERNAME', Configuration::get('EFT_SECURE_USERNAME')),
            'EFT_SECURE_PASSWORD' => Tools::getValue('EFT_SECURE_PASSWORD', Configuration::get('EFT_SECURE_PASSWORD')),
        );
    }

    public function hookHeader()
    {
        $this->context->controller->addCSS(($this->_path).'eftsecure.css', 'all');
		$this->context->controller->addJS(__PS_BASE_URI__.'modules/eftsecure/views/js/jquery.blockUI.min.js');
		$this->context->controller->addJS(__PS_BASE_URI__.'modules/eftsecure/views/js/eftsecure_checkout.js');
    }
}
