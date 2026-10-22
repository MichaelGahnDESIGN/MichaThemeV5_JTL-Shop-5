{extends file="{$parentTemplateDir}productdetails/price.tpl"}

{* Grundpreis ein/aus, Pflichtangabe nach PAngV (Standard an) *}
{block name='productdetails-price-detail-base-price'}
    {if $Einstellungen.template.mt_legal.mt_legal_unit_price === 'Y'}{$smarty.block.parent}{/if}
{/block}
{block name='productdetails-price-list-base-price'}
    {if $Einstellungen.template.mt_legal.mt_legal_unit_price === 'Y'}{$smarty.block.parent}{/if}
{/block}

{* niedrigster Preis der letzten 30 Tage aus Attribut mt_niedrigster_preis_30, nur bei Streichpreis *}
{block name='productdetails-price-special-prices-detail' append}
    {if $Einstellungen.template.mt_legal.mt_legal_lowest_price === 'attribute' && !empty($Artikel->Preise->alterVKLocalized[$NettoPreise])}
        {assign var='mtLow' value=$Artikel->AttributeAssoc['mt_niedrigster_preis_30']|default:$Artikel->FunktionsAttribute['mt_niedrigster_preis_30']|default:''}
        {if $mtLow !== ''}<span class="mt-lowest-price">Niedrigster Preis der letzten 30 Tage: {$mtLow|escape:'html'}</span>{/if}
    {/if}
{/block}
