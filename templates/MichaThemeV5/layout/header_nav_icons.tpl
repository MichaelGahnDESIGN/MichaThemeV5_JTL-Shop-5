{extends file="{$parentTemplateDir}layout/header_nav_icons.tpl"}

{* Hell/Dunkel Schalter bei den Werkzeugen rechts (vor dem Konto) *}
{block name='layout-header-nav-icons-include-header-shop-nav-account' prepend}
    {if $Einstellungen.template.mt_colors.mt_mode_toggle === 'Y'}
        <li class="nav-item mt-mode-item">
            <button type="button" class="mt-mode-toggle nav-link" data-mt-mode-toggle aria-label="Farbmodus wechseln">
                <span aria-hidden="true">◐</span><span class="mt-mode-toggle__label" data-mt-mode-label>Wie das Gerät</span>
            </button>
        </li>
    {/if}
{/block}
