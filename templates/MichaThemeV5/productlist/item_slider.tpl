{extends file="{$parentTemplateDir}productlist/item_slider.tpl"}

{* Herz im Slider, steht neben dem Link (Button darf nicht im a sitzen) *}
{block name='productlist-item-slider' append}
    {if ($Einstellungen.template.mt_header.mt_favorites|default:'Y') === 'Y'}
        {include file='snippets/mt-fav-toggle.tpl' favId=$Artikel->kArtikel favTitle=$Artikel->cName favUrl=$Artikel->cURLFull favCard=true}
    {/if}
{/block}
