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

{* Links Pflichtseiten: Widerruf (ab 5.7 gibt es die JTL Funktion im Footer) + Barrierefreiheit *}
{block name='layout-footer-copyright' prepend}
    {assign var='mtLinks' value=[]}
    {assign var='mtWd' value=$Einstellungen.template.mt_legal.mt_legal_withdrawal_page|replace:' ':''}
    {if ($mtWd|truncate:1:'':true === '/' && $mtWd|truncate:2:'':true !== '//') || $mtWd|truncate:8:'':true === 'https://' || $mtWd|truncate:7:'':true === 'http://'}
        {append var='mtLinks' value=['label' => 'Vertrag widerrufen', 'url' => $mtWd, 'cls' => 'mt-withdraw-link']}
    {/if}
    {assign var='mtA11y' value=$Einstellungen.template.mt_a11y.mt_a11y_statement_page|replace:' ':''}
    {if ($mtA11y|truncate:1:'':true === '/' && $mtA11y|truncate:2:'':true !== '//') || $mtA11y|truncate:8:'':true === 'https://' || $mtA11y|truncate:7:'':true === 'http://'}
        {append var='mtLinks' value=['label' => 'Erklärung zur Barrierefreiheit', 'url' => $mtA11y, 'cls' => '']}
    {/if}
    {if $mtLinks|count > 0}
        <nav class="container mt-legal-links" aria-label="Pflichtlinks">
            <ul class="list-unstyled d-flex flex-wrap">
                {foreach $mtLinks as $l}<li class="mr-3"><a{if $l.cls !== ''} class="{$l.cls|escape:'html'}"{/if} href="{$l.url|escape:'html'}">{$l.label|escape:'html'}</a></li>{/foreach}
            </ul>
        </nav>
    {/if}
{/block}

{block name='layout-footer' append}
    {assign var='mtNotificationPlacement' value=$Einstellungen.template.mt_notification.mt_notification_placement|default:'floating-bottom-right'}
    {if $mtNotificationPlacement === 'footer'}
        {include file='snippets/mt-notification-center.tpl' placement='footer'}
    {elseif $mtNotificationPlacement === 'floating-top-left' || $mtNotificationPlacement === 'floating-top-right' || $mtNotificationPlacement === 'floating-bottom-left' || $mtNotificationPlacement === 'floating-bottom-right'}
        {include file='snippets/mt-notification-center.tpl' placement=$mtNotificationPlacement}
    {/if}
{/block}
