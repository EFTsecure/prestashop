<?php

/**
 * @since 1.5.0
 */
class EftsecurePaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    /**
    * @see FrontController::initContent()
    */
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }
		
		$eftsecure_username = Configuration::get('EFT_SECURE_USERNAME');
		$eftsecure_password = Configuration::get('EFT_SECURE_PASSWORD');
		
		$curl = curl_init('https://services.callpay.com/api/v1/token');
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_USERPWD, $eftsecure_username . ":" . $eftsecure_password);

		$response = curl_exec($curl);
		curl_close($curl);
		$response_data = json_decode($response);
		
		if(isset($response_data->token)){
			$token = $response_data->token;
			$organisation_id = $response_data->organisation_id;
		} else {
			$token = '';
			$organisation_id = '';
		}
		$amount = $cart->getOrderTotal(true, Cart::BOTH);
		
		$params = array(
			"reference" 		=> 'order_'.$params['cart']->id,
			"organisation_id" 	=> $organisation_id,
			"token" 			=> $token,
			"amount" 			=> number_format($amount, 2),
			"pcolor" 			=> '',
			"scolor" 			=> '',
		);

        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies' => $this->module->getCurrency((int)$cart->id_currency),
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'this_path' => $this->module->getPathUri(),
            'this_path_bw' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
			'eft_details' => Configuration::get('EFT_SECURE_DETAILS'),
			'params'	 => $params,
        ));

        $this->setTemplate('payment_execution.tpl');
    }
}
