{extends file="{$parentTemplateDir}account/my_account.tpl"}

{* Farbmodus in der Konto-Uebersicht, nur wenn Ort = Benutzerkonto *}
{block name='account-my-account-actions' prepend}
    {if $Einstellungen.template.mt_colors.mt_mode_toggle === 'Y' && $Einstellungen.template.mt_colors.mt_mode_toggle_place === 'account'}
        <div class="mt-mode-row mb-3">
            {include file='snippets/mt-mode-toggle.tpl' place='account'}
        </div>
    {/if}
{/block}
