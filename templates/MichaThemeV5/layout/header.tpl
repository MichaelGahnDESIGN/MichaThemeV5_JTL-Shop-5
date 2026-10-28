{extends file="{$parentTemplateDir}layout/header.tpl"}

{block name='layout-header-head-meta' append}
    {include file='snippets/mt-config.tpl'}
    <script src="{$ShopURL}/{$currentTemplateDir}mt/mt-init.js"></script>
    {if $Einstellungen.template.mt_performance.mt_perf_preload_fonts === 'Y'}
        <link rel="preload" href="{$ShopURL}/{$currentTemplateDir}mt/fonts/inter-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    {/if}
    <script src="{$ShopURL}/{$currentTemplateDir}mt/mt-theme.js" defer></script>
    <script src="{$ShopURL}/{$currentTemplateDir}mt/mt-legal.js" defer></script>
    {* Glocke: Skript nur mit aktivem Plugin und gewählter Platzierung *}
    {if $mtNotificationsAvailable === true && ($Einstellungen.template.mt_notification.mt_notification_placement|default:'none') !== 'none'}
        <script src="{$ShopURL}/{$currentTemplateDir}mt/mt-notification-center.js" defer></script>
    {/if}
{/block}

{* CSS erst nach NOVA, sonst gewinnt dessen Body-Farbe (gleiche Spezifitaet, spaeter geladen) *}
{block name='layout-header-head-resources' append}
    <link rel="stylesheet" href="{$ShopURL}/{$currentTemplateDir}mt/mt-fonts.css">
    <link rel="stylesheet" href="{$ShopURL}/{$currentTemplateDir}mt/mt-tokens.css">
    <link rel="stylesheet" href="{$ShopURL}/{$currentTemplateDir}mt/mt-base.css">
    <link rel="stylesheet" href="{$ShopURL}/{$currentTemplateDir}mt/mt-jtl.css">
    <link rel="stylesheet" href="{$ShopURL}/{$currentTemplateDir}mt/mt-legal.css">
    {if $mtNotificationsAvailable === true && ($Einstellungen.template.mt_notification.mt_notification_placement|default:'none') !== 'none'}
        <link rel="stylesheet" href="{$ShopURL}/{$currentTemplateDir}mt/mt-notification-center.css">
    {/if}
{/block}

{block name='layout-header-header' prepend}
    <a class="mt-skip" href="#mt-main">Zum Inhalt springen</a>
    {if $Einstellungen.template.mt_header.mt_announcement === 'Y' && $Einstellungen.template.mt_header.mt_announcement_text !== ''}
        <div class="mt-announcement" role="note">{$Einstellungen.template.mt_header.mt_announcement_text|escape:'html'}</div>
    {/if}
    {* Schalter sitzt im Header bei den Icons, nur im Checkout (ohne Icons) bleibt die eigene Zeile *}
    {if $Einstellungen.template.mt_colors.mt_mode_toggle === 'Y'}
        <div class="mt-mode-wrap">
            <button type="button" class="mt-mode-toggle" data-mt-mode-toggle aria-label="Farbmodus wechseln">
                <span aria-hidden="true">◐</span> <span data-mt-mode-label>Wie das Gerät</span>
            </button>
        </div>
    {/if}
    {if $mtNotificationsAvailable === true && $Einstellungen.template.mt_notification.mt_notification_placement === 'menu'}
        {include file='snippets/mt-notification-center.tpl' placement='menu'}
    {/if}
{/block}
