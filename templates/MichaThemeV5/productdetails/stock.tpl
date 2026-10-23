{extends file="{$parentTemplateDir}productdetails/stock.tpl"}

{* Lieferzeit Ersatz - nur wenn JTL keine liefert *}
{block name='productdetails-stock-estimated-delivery' append}
    {if $Einstellungen.template.mt_legal.mt_legal_shipping_time !== '' && ($Artikel->cEstimatedDelivery|default:'') === ''}
        <span class="mt-delivery-time">{$Einstellungen.template.mt_legal.mt_legal_shipping_time|escape:'html'}</span>
    {/if}
{/block}
