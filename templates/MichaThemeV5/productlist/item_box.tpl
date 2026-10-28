{extends file="{$parentTemplateDir}productlist/item_box.tpl"}

{* Ausverkauft Badge + Herz in der Liste (Listing, Slider, Suche) *}
{block name='productlist-item-box-image' append}
    {if ($Einstellungen.template.mt_header.mt_favorites|default:'Y') === 'Y'}
        {include file='snippets/mt-fav-toggle.tpl' favId=$Artikel->kArtikel favTitle=$Artikel->cName favUrl=$Artikel->cURLFull favCard=true}
    {/if}
    {if $Einstellungen.template.mt_listing.mt_soldout_display === 'mark' && $Artikel->inWarenkorbLegbar != 1 && $Artikel->nErscheinendesProdukt != 1 && $Artikel->cLagerBeachten === 'Y' && $Artikel->fLagerbestand <= 0}
        <span class="mt-soldout">Ausverkauft</span>
    {/if}
{/block}

{* Preis Hinweis + Link Versandkosten in der Liste *}
{block name='productlist-index-include-price' append}
    {if $Einstellungen.template.mt_legal.mt_legal_price_note !== ''}
        <p class="mt-price-note">{$Einstellungen.template.mt_legal.mt_legal_price_note|escape:'html'}
            {if isset($oSpezialseiten_arr[$smarty.const.LINKTYP_VERSAND])}<a href="{$oSpezialseiten_arr[$smarty.const.LINKTYP_VERSAND]->getURL()|escape:'html'}" rel="nofollow">Versandkosten</a>{/if}
        </p>
    {/if}
{/block}
