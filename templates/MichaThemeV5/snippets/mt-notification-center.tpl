{if $mtNotificationsAvailable === true}
{assign var='mtItems' value=[]}
{foreach from=$mtNotifications.public|default:[] item=mtPublicItem}{append var='mtItems' value=$mtPublicItem}{/foreach}
{foreach from=$mtNotifications.personal|default:[] item=mtPersonalItem}{append var='mtItems' value=$mtPersonalItem}{/foreach}
<div class="mt-notification-center mt-notification-center--{$placement|escape:'html'}" data-mt-notification-center>
    <button type="button" aria-expanded="false" aria-label="Benachrichtigungen öffnen" class="mt-notification-button"><span aria-hidden="true">🔔</span><span class="mt-notification-count" {if $mtItems|count === 0}hidden{/if}>{$mtItems|count}</span></button>
    <section class="mt-notification-panel" hidden aria-label="Benachrichtigungen">
        {if $mtItems|count === 0}<p>Keine neuen Benachrichtigungen.</p>{else}<ul>{foreach $mtItems as $item}<li><strong>{$item.title|escape:'html'}</strong><span>{$item.body|escape:'html'}</span>{if $item.target_url}<a href="{$item.target_url|escape:'html'}">Öffnen</a>{/if}</li>{/foreach}</ul>{/if}
    </section>
</div>
{/if}
