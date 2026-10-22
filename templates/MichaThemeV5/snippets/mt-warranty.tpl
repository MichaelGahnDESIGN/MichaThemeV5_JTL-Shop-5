{* Gewährleistung Hinweis - Text aus Template-Einstellungen, EU-Label ab 5.8 bleibt von NOVA *}
{if $Einstellungen.template.mt_legal.mt_legal_warranty === 'Y' && $Einstellungen.template.mt_legal.mt_legal_warranty_text !== ''}
    <p class="mt-warranty" role="note">{$Einstellungen.template.mt_legal.mt_legal_warranty_text|escape:'html'}</p>
{/if}
