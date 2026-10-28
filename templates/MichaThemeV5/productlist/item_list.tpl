{extends file="{$parentTemplateDir}productlist/item_list.tpl"}

{* Herz in der Listenansicht (Bildbereich) *}
{block name='productlist-item-list-images' append}
    {if ($Einstellungen.template.mt_header.mt_favorites|default:'Y') === 'Y'}
        {include file='snippets/mt-fav-toggle.tpl' favId=$Artikel->kArtikel favTitle=$Artikel->cName favUrl=$Artikel->cURLFull favCard=true}
    {/if}
{/block}
