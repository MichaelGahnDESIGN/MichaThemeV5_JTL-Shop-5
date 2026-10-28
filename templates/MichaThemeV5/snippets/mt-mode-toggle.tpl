{* Hell/Dunkel Schalter - place = header (nur Icon) | footer | account (mit Label) *}
{* Ort wird auch serverseitig geprüft, data-mt-place ist nur die Absicherung im CSS *}
{assign var='mtModeIcon' value=$place === 'header'}
<button type="button" class="mt-mode-toggle{if $mtModeIcon} mt-mode-toggle--icon nav-link{/if}" data-mt-mode-toggle data-mt-place="{$place|escape:'html'}" aria-label="Farbmodus: Wie das Gerät. Zum Wechseln klicken." title="Farbmodus: Wie das Gerät. Zum Wechseln klicken.">
    <svg class="mt-mode-ico mt-mode-ico--system" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/></svg>
    <svg class="mt-mode-ico mt-mode-ico--light" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    <svg class="mt-mode-ico mt-mode-ico--dark" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
    {if !$mtModeIcon}<span class="mt-mode-toggle__text">Farbmodus: <span data-mt-mode-label>Wie das Gerät</span></span>{/if}
</button>
