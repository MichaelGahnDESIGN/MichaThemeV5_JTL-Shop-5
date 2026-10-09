{extends file="{$parentTemplateDir}layout/header.tpl"}

{block name='layout-header-head-meta' append}
    {include file='snippets/mt-config.tpl'}
    <script src="{$ShopURL}/{$currentTemplateDir}mt/mt-init.js"></script>
    {if $Einstellungen.template.mt_performance.mt_perf_preload_fonts === 'Y'}
        <link rel="preload" href="{$ShopURL}/{$currentTemplateDir}mt/fonts/inter-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    {/if}
    <link rel="stylesheet" href="{$ShopURL}/{$currentTemplateDir}mt/mt-fonts.css">
    <link rel="stylesheet" href="{$ShopURL}/{$currentTemplateDir}mt/mt-tokens.css">
    <link rel="stylesheet" href="{$ShopURL}/{$currentTemplateDir}mt/mt-base.css">
    <link rel="stylesheet" href="{$ShopURL}/{$currentTemplateDir}mt/mt-jtl.css">
    <script src="{$ShopURL}/{$currentTemplateDir}mt/mt-theme.js" defer></script>
    {* Eigenes CSS/JS: nur mit Pro-Modul "custom-code" ($mtPro kommt aus Bootstrap.php, fehlt es, bleibt alles aus). *}
    {if isset($mtPro['custom-code']) && $mtPro['custom-code']}
        {if $Einstellungen.template.mt_advanced.mt_custom_css !== ''}<style id="mt-custom-css">{$Einstellungen.template.mt_advanced.mt_custom_css|strip_tags}</style>{/if}
    {/if}
{/block}

{block name='layout-header-header' prepend}
    <a class="mt-skip" href="#mt-main">Zum Inhalt springen</a>
    {if $Einstellungen.template.mt_header.mt_announcement === 'Y' && $Einstellungen.template.mt_header.mt_announcement_text !== ''}
        <div class="mt-announcement" role="note">{$Einstellungen.template.mt_header.mt_announcement_text|escape:'html'}</div>
    {/if}
    {if $Einstellungen.template.mt_colors.mt_mode_toggle === 'Y'}
        <div class="mt-mode-wrap">
            <button type="button" class="mt-mode-toggle" data-mt-mode-toggle aria-label="Farbmodus wechseln">
                <span aria-hidden="true">◐</span> <span data-mt-mode-label>Wie das Gerät</span>
            </button>
        </div>
    {/if}
{/block}
