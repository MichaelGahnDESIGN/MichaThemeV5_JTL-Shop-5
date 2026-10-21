{extends file="{$parentTemplateDir}layout/footer.tpl"}

{block name='layout-footer' prepend}
    {if $Einstellungen.template.mt_footer.mt_footer_trust === 'Y'}
        {assign var='mtTrust' value=[]}
        {foreach from=['mt_trust_1', 'mt_trust_2', 'mt_trust_3'] item=k}
            {if $Einstellungen.template.mt_footer.$k !== ''}{append var='mtTrust' value=$Einstellungen.template.mt_footer.$k}{/if}
        {/foreach}
        {if $mtTrust|count > 0}
            <section class="container mt-section" aria-label="Unsere Versprechen">
                <ul class="mt-trust">{foreach $mtTrust as $t}<li>{$t|escape:'html'}</li>{/foreach}</ul>
            </section>
        {/if}
    {/if}
{/block}
