<?php

/**
 * @since 1.5.0
 */
class EftsecureValidationModuleFrontController extends ModuleFrontController
{
    /**
    * @see FrontController::postProcess()
    */
    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'eftsecure') {
                $authorized = true;
                break;
            }
        }
        
        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        /* $mailVars = array(
            '{eftsecure_owner}' => Configuration::get('EFT_SECURE_OWNER'),
            '{eftsecure_details}' => nl2br(Configuration::get('EFT_SECURE_DETAILS')),
            '{eftsecure_address}' => nl2br(Configuration::get('EFT_SECURE_ADDRESS'))
        ); */
		
		$gateway_reference = Tools::getValue('eftsecure_transaction_id');
		
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
		
		$headers = array(
			'X-Token: '.$token,
		);
		$curl = curl_init('https://services.callpay.com/api/v1/gateway-transaction/'.$gateway_reference);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$response = curl_exec($curl);
		curl_close($curl);
			
		$response_data = json_decode($response);
		
		if($response_data->id == $gateway_reference && $response_data->successful == 1) {
			$extra_vars = array(
				'transaction_id' => $gateway_reference
            );
			$this->module->validateOrder($cart->id, Configuration::get('PS_OS_EFTSECURE'), $total, $this->module->displayName, null, $extra_vars, (int)$currency->id, false, $customer->secure_key);
			Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
		} else {
			Tools::redirect('404');
		}
    }
}
