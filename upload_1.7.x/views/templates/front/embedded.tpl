{*
* Eftsecure
*}
{if isset($eft_iframe) && $eft_iframe == 1}
<script type="text/javascript">
window.addEventListener("message", function(event) {
    eval(event.data);
});
</script>
<form name="custompaymentmethod" id="add_payment" method="post" action="{$form_url}">
  <input type="submit" id="place_order" class="button" value="Click Here" style="display:none;" />
</form>

<style>
#add_payment input[type="submit"]{
	background-color: #222;
	color: #fff;
	padding: 1em 2em;
	font-size: 16px;
}
</style>
<script type="text/javascript" src="https://eftsecure.callpay.com/ext/eftsecure/js/checkout.js"></script>
<script>
	var mg_eftsecure_params = {$params|@json_encode nofilter};
</script>
{/if}