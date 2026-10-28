{extends file="{$parentTemplateDir}layout/header_shop_nav_account.tpl"}

{* Konto Menue (eingeloggt): Eintrag Farbmodus vor Abmelden, nur wenn Ort = Benutzerkonto *}
{* Block aus NOVA 1.8.0 uebernommen + ein Eintrag, sonst gleich *}
{block name='layout-header-shop-nav-account-logged-in'}
    <div id="account-dropdown-menu">
        {get_static_route id='jtl.php' secure=true assign='secureAccountURL'}
        {dropdownitem href=$secureAccountURL title="{lang key='myAccount'}"}
            {lang key='myAccount'}
        {/dropdownitem}
        {dropdownitem href="{$secureAccountURL}?bestellungen=1" title="{lang key='myAccount'}"}
            {lang key='myOrders'}
        {/dropdownitem}
        {dropdownitem href="{$secureAccountURL}?editRechnungsadresse=1" title="{lang key='myAccount'}"}
            {lang key='myPersonalData'}
        {/dropdownitem}
        {dropdownitem href="{$secureAccountURL}?editLieferadresse=1" title="{lang key='myAccount'}"}
            {lang key='myShippingAddresses'}
        {/dropdownitem}
        {if $Einstellungen.global.global_rma_enabled === 'Y'}
            {dropdownitem href="{$secureAccountURL}?returns=1" title="{lang key='myAccount'}"}
                {lang key='myReturns' section='rma'}
            {/dropdownitem}
        {/if}
        {if $Einstellungen.global.global_wunschliste_anzeigen === 'Y'}
            {dropdownitem href="{$secureAccountURL}#my-wishlists" title="{lang key='myAccount'}"}
                {lang key='myWishlists'}
            {/dropdownitem}
        {/if}
        {if $Einstellungen.template.mt_colors.mt_mode_toggle === 'Y' && $Einstellungen.template.mt_colors.mt_mode_toggle_place === 'account'}
            <div class="mt-mode-row mt-mode-row--menu">
                {include file='snippets/mt-mode-toggle.tpl' place='account'}
            </div>
        {/if}
        {dropdowndivider}
        {dropdownitem href="{$secureAccountURL}?logout=1" title="{lang key='logOut'}" class="account-icon-dropdown-logout"}
            {lang key='logOut'}
        {/dropdownitem}
    </div>
{/block}
