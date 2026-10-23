{extends file="{$parentTemplateDir}productdetails/details.tpl"}

{* Recht: Ausverkauft, Gewährleistung, GPSR unter dem Kaufbereich *}
{block name='productdetails-details-include-basket' append}
    {include file='snippets/mt-legal-product.tpl'}
{/block}
