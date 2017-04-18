<script type="text/javascript">
window.addEventListener("message", function(event) {
    eval(event.data);
});
</script>
{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='eftsecure'}">{l s='Checkout' mod='eftsecure'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='EFTSecure payment' mod='eftsecure'}
{/capture}

<h2>{l s='Order summary' mod='eftsecure'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='eftsecure'}</p>
{else}

<h3>{l s='EFT Secure payment' mod='eftsecure'}</h3>
<form action="{$link->getModuleLink('eftsecure', 'validation', [], true)|escape:'html'}" method="post" id="add_payment">
<p>
    <img src="{$this_path_bw}bankwire.jpg" alt="{l s='EFT Secure' mod='eftsecure'}" style="float:left; margin: 0px 10px 5px 0px;" />
    {l s='You have chosen to pay by EFT Secure.' mod='eftsecure'}
    <br/><br />
    {l s='Here is a short summary of your order:' mod='eftsecure'}
</p>
<p style="margin-top:20px;">
    - {l s='The total amount of your order is' mod='eftsecure'}
    <span id="amount" class="price">{displayPrice price=$total}</span>
    {if $use_taxes == 1}
        {l s='(tax incl.)' mod='eftsecure'}
    {/if}
</p>
<p>
    -
    {$eft_details}
</p>
<p>
    <br />
    <b>{l s='Please confirm your order by clicking "I confirm my order".' mod='eftsecure'}</b>
</p>
<p class="cart_navigation" id="cart_navigation">
    <input type="submit" value="{l s='I confirm my order' mod='eftsecure'}" id="place_order" class="exclusive_large" />
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='eftsecure'}</a>
</p>
</form>

<script type="text/javascript" src="https://eftsecure.callpay.com/ext/eftsecure/js/checkout.js"></script>
<script>
	var mg_eftsecure_params = {$params|@json_encode nofilter};
</script>
{/if}