{extends file="{$parentTemplateDir}layout/header_nav_icons.tpl"}

{* Icon-Leiste: Schalter, Glocke, Merkliste, Favoriten, dann NOVA Konto + Warenkorb *}
{block name='layout-header-nav-icons-include-header-shop-nav-account' prepend}
    {assign var='mtModePlace' value=$Einstellungen.template.mt_colors.mt_mode_toggle_place|default:'header'}
    {* 1 Hell/Dunkel, nur Icon, nur wenn Ort = Header *}
    {if $Einstellungen.template.mt_colors.mt_mode_toggle === 'Y' && $mtModePlace === 'header'}
        <li class="nav-item mt-hdr-item mt-mode-item">
            {include file='snippets/mt-mode-toggle.tpl' place='header'}
        </li>
    {/if}
    {* 2 Glocke, nur Plugin aktiv + Platzierung Header + echte Eintraege (Snippet prueft das) *}
    {if $mtNotificationsAvailable === true && $Einstellungen.template.mt_notification.mt_notification_placement === 'header'}
        {include file='snippets/mt-notification-center.tpl' placement='header'}
    {/if}
    {if !($isMobile)}
        {* 3 Merkliste = native JTL Wunschliste, unsichtbar wenn lere *}
        {include file='layout/header_shop_nav_wish.tpl'}
    {/if}
    {* 4 Favoriten (Herz Liste) *}
    {if ($Einstellungen.template.mt_header.mt_favorites|default:'Y') === 'Y'}
        {include file='snippets/mt-fav-header.tpl'}
    {/if}
{/block}

{* Wunschliste sitzt jetzt weiter vorne (vor dem Konto), hier nicht nochmal *}
{block name='layout-header-nav-icons-include-header-shop-nav-wish'}{/block}
