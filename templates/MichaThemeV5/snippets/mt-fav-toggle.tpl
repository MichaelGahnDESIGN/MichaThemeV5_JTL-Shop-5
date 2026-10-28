{* Herz Button fuer Favoriten (localStorage) - favId/favTitle/favUrl sind Pflicht, favCard = auf Produktkarte *}
{if $favId !== '' && $favTitle !== '' && $favUrl !== ''}
<button type="button" class="mt-fav-toggle{if $favCard === true} mt-fav-toggle--card{/if}" data-mt-fav-toggle data-mt-fav-id="{$favId|escape:'html'}" data-mt-fav-title="{$favTitle|escape:'html'}" data-mt-fav-url="{$favUrl|escape:'html'}" aria-pressed="false" aria-label="Zu Favoriten hinzufügen: {$favTitle|escape:'html'}" data-label-add="Zu Favoriten hinzufügen: %s" data-label-remove="Aus Favoriten entfernen: %s"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg></button>
{/if}
