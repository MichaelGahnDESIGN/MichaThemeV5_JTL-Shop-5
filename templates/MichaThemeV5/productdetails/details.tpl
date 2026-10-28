{extends file="{$parentTemplateDir}productdetails/details.tpl"}

{* Herz neben dem Titel *}
{block name='productdetails-details-info-product-title' append}
    {if ($Einstellungen.template.mt_header.mt_favorites|default:'Y') === 'Y'}
        {include file='snippets/mt-fav-toggle.tpl' favId=$Artikel->kArtikel favTitle=$Artikel->cName favUrl=$Artikel->cURLFull favCard=false}
    {/if}
{/block}

{* Recht: Ausverkauft, Gewährleistung, GPSR unter dem Kaufbereich *}
{block name='productdetails-details-include-basket' append}
    {include file='snippets/mt-legal-product.tpl'}
{/block}
