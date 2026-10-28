{extends file="{$parentTemplateDir}layout/header_shop_nav_wish.tpl"}

{* Merkliste als Lesezeichen Icon (Herz gehoert den Favoriten), leer = unsichtbar *}
{* Block aus NOVA 1.8.0 uebernommen, Ajax Zaehler (#badge-wl-count) bleibt gleich *}
{block name='layout-header-shop-nav-wish'}
    {if !empty($wishlists) && $Einstellungen.global.global_wunschliste_anzeigen === 'Y'}
        {$wlCount = 0}
        {if \JTL\Session\Frontend::getWishlist()->getID() > 0}
            {$wlCount = \JTL\Session\Frontend::getWishlist()->getItems()|count}
        {/if}
        {capture assign='mtWishName'}{lang key='wishlist'}{/capture}
        {capture assign='mtWishUnit'}{lang key='products'}{/capture}
        <li id='shop-nav-wish'
            class="nav-item dropdown mt-hdr-item mt-hdr-item--wish{if $wlCount === 0} mt-hdr-item--empty{/if}{if $nSeitenTyp === $smarty.const.PAGE_WUNSCHLISTE} active{/if}"
            data-mt-wish-item data-mt-wish-name="{$mtWishName|escape:'html'}" data-mt-wish-unit="{$mtWishUnit|escape:'html'}">
            {block name='layout-header-shop-nav-wish-link'}
                {link class='nav-link mt-hdr-btn' aria=['expanded' => 'false', 'label' => "{$mtWishName}, {$wlCount} {$mtWishUnit}", 'controls'=>'nav-wishlist-collapse'] data=['toggle' => 'dropdown'] role='button'}
                    <svg class="mt-hdr-ico" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><path d="M6 3h12v18l-6-4-6 4z"/></svg>
                    <span id="badge-wl-count" class="mt-list-count{if $wlCount === 0} d-none{/if}" aria-hidden="true">{$wlCount}</span>
                {/link}
            {/block}
            {block name='layout-header-shop-nav-wish-dropdown'}
                <div id="nav-wishlist-collapse" class="dropdown-menu dropdown-menu-right lg-min-w-lg">
                    <div id="wishlist-dropdown-container">
                        {block name='layout-header-shop-nav-wish-include-wishlist-dropdown'}
                            {include file='snippets/wishlist_dropdown.tpl'}
                        {/block}
                    </div>
                </div>
            {/block}
        </li>
    {/if}
{/block}
