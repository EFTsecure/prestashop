
{if $status == 'ok'}
    <p>
      {l s='Your order on %s is complete.' sprintf=[$shop_name] mod='eftsecure'}
    </p>
    <p>
      {l s='We\'ve also sent you this information by e-mail.' mod='eftsecure'}
    </p>
    <p>
      {l s='If you have questions, comments or concerns, please contact our [1]expert customer support team[/1].' mod='eftsecure' tags=["<a href='{$contact_url}'>"]}
    </p>
{else}
    <p class="warning">
      {l s='We noticed a problem with your order. If you think this is an error, feel free to contact our [1]expert customer support team[/1].' mod='eftsecure' tags=["<a href='{$contact_url}'>"]}
    </p>
{/if}
