<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/../../init.php');

$context = Context::getContext();
$cart = $context->cart;
$eftsecure = Module::getInstanceByName('eftsecure');

if ($cart->id_customer == 0 or $cart->id_address_delivery == 0 or $cart->id_address_invoice == 0 or !$eftsecure->active) {
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
    die($eftsecure->getTranslator()->trans('This payment method is not available.', array(), 'Modules.EftSecure.Shop'));
}

$customer = new Customer((int)$cart->id_customer);

if (!Validate::isLoadedObject($customer)) {
    Tools::redirect('index.php?controller=order&step=1');
}

$currency = $context->currency;
$total = (float)($cart->getOrderTotal(true, Cart::BOTH));

$eftsecure->validateOrder($cart->id, Configuration::get('PS_OS_EFTSECURE'), $total, $eftsecure->displayName, null, array(), (int)$currency->id, false, $customer->secure_key);

$order = new Order($eftsecure->currentOrder);
Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$eftsecure->id.'&id_order='.$eftsecure->currentOrder.'&key='.$customer->secure_key);
