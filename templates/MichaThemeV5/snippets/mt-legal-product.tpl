{* Recht Artikelseite: Ausverkauft Button, Gewährleistung Hinweis, GPSR aus Attributen *}
{if $Einstellungen.template.mt_listing.mt_soldout_display === 'mark' && $Artikel->inWarenkorbLegbar != 1 && $Artikel->nErscheinendesProdukt != 1 && $Artikel->cLagerBeachten === 'Y' && $Artikel->fLagerbestand <= 0}
    <p><button type="button" class="btn btn-primary btn-block mt-soldout-btn" disabled aria-disabled="true">Ausverkauft</button></p>
{/if}
{include file='snippets/mt-warranty.tpl'}
{if $Einstellungen.template.mt_legal.mt_legal_gpsr === 'Y'}
    {assign var='mtMaker' value=$Artikel->AttributeAssoc['mt_gpsr_manufacturer']|default:$Artikel->FunktionsAttribute['mt_gpsr_manufacturer']|default:$Artikel->cHersteller|default:''}
    {assign var='mtPerson' value=$Artikel->AttributeAssoc['mt_gpsr_responsible_person']|default:$Artikel->FunktionsAttribute['mt_gpsr_responsible_person']|default:''}
    {assign var='mtContact' value=$Artikel->AttributeAssoc['mt_gpsr_contact']|default:$Artikel->FunktionsAttribute['mt_gpsr_contact']|default:''}
    {assign var='mtSafety' value=$Artikel->AttributeAssoc['mt_gpsr_safety_info']|default:$Artikel->FunktionsAttribute['mt_gpsr_safety_info']|default:''}
    {if $mtMaker !== '' || $mtPerson !== '' || $mtContact !== '' || $mtSafety !== ''}
        <details class="mt-gpsr">
            <summary>Produktsicherheit</summary>
            <dl>
                {if $mtMaker !== ''}<dt>Hersteller</dt><dd>{$mtMaker|escape:'html'|nl2br}</dd>{/if}
                {if $mtPerson !== ''}<dt>Verantwortliche Person in der EU</dt><dd>{$mtPerson|escape:'html'|nl2br}</dd>{/if}
                {if $mtContact !== ''}<dt>Kontakt</dt><dd>{$mtContact|escape:'html'|nl2br}</dd>{/if}
                {if $mtSafety !== ''}<dt>Sicherheitshinweise</dt><dd>{$mtSafety|escape:'html'|nl2br}</dd>{/if}
            </dl>
        </details>
    {/if}
{/if}
