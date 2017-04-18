<?php
class EftsecureEftsuccessModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function initContent()
    {
        $cart = $this->context->cart;
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
			$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
			$customer = new Customer($cart->id_customer);
			$this->module->validateOrder(
				$cart->id,
				Configuration::get('PS_OS_EFTSECURE'),
				$total,
				$this->module->displayName,
				false,
				$extra_vars,
				(int)$cart->id_currency,
				false,
				$customer->secure_key
            );
			Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
		} else {
			Tools::redirect('404');
		}
    }
}
