{assign var='mtItems' value=array_merge($mtNotifications.public|default:[], $mtNotifications.personal|default:[])}
<div class="mt-notification-center mt-notification-center--{$placement|escape:'html'}" data-mt-notification-center>
    <button type="button" aria-expanded="false" aria-label="Benachrichtigungen öffnen" class="mt-notification-button"><span aria-hidden="true">🔔</span><span class="mt-notification-count" {if $mtItems|count === 0}hidden{/if}>{$mtItems|count}</span></button>
    <section class="mt-notification-panel" hidden aria-label="Benachrichtigungen">
        {if $mtItems|count === 0}<p>Keine neuen Benachrichtigungen.</p>{else}<ul>{foreach $mtItems as $item}<li><strong>{$item.title|escape:'html'}</strong><span>{$item.body|escape:'html'}</span>{if $item.target_url}<a href="{$item.target_url|escape:'html'}">Öffnen</a>{/if}</li>{/foreach}</ul>{/if}
    </section>
</div>
