{* Favoriten Icon im Header - bleibt hidden bis mt-theme.js Eintraege findet *}
<li class="nav-item mt-hdr-item mt-hdr-item--favs" data-mt-fav-item hidden>
    <button type="button" class="nav-link mt-hdr-btn" data-mt-fav-open aria-expanded="false" aria-controls="mt-fav-panel" data-label-count="Favoriten, %s Einträge" data-label-count-one="Favoriten, %s Eintrag" aria-label="Favoriten">
        <svg class="mt-hdr-ico" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg>
        <span class="mt-list-count" data-mt-fav-count aria-hidden="true">0</span>
    </button>
    <section id="mt-fav-panel" class="mt-fav-panel" data-mt-fav-panel data-label-remove="Aus Favoriten entfernen: %s" aria-label="Favoriten" hidden><ul class="mt-fav-list" data-mt-fav-list></ul></section>
</li>
