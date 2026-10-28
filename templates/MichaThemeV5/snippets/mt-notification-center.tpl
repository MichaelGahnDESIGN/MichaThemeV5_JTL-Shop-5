{* Glocke - nur sichtbar wenn es echte Eintraege gibt (ohne Eintrag kommt gar kein Markup) *}
{if $mtNotificationsAvailable === true}
{assign var='mtItems' value=[]}
{foreach from=$mtNotifications.public|default:[] item=mtPublicItem}{append var='mtItems' value=$mtPublicItem}{/foreach}
{foreach from=$mtNotifications.personal|default:[] item=mtPersonalItem}{append var='mtItems' value=$mtPersonalItem}{/foreach}
{if $mtItems|count > 0}
{if $placement === 'header'}<li class="nav-item mt-hdr-item mt-hdr-item--bell">{/if}
<div class="mt-notification-center mt-notification-center--{$placement|escape:'html'}" data-mt-notification-center>
    <button type="button" aria-expanded="false" aria-label="Benachrichtigungen öffnen, {$mtItems|count} {if $mtItems|count === 1}Eintrag{else}Einträge{/if}" class="mt-notification-button"><svg class="mt-hdr-ico" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg><span class="mt-list-count mt-notification-count" aria-hidden="true">{$mtItems|count}</span></button>
    <section class="mt-notification-panel" hidden aria-label="Benachrichtigungen">
        <ul>{foreach $mtItems as $item}<li><strong>{$item.title|escape:'html'}</strong><span>{$item.body|escape:'html'}</span>{if $item.target_url}<a href="{$item.target_url|escape:'html'}">Öffnen</a>{/if}</li>{/foreach}</ul>
    </section>
</div>
{if $placement === 'header'}</li>{/if}
{/if}
{/if}
