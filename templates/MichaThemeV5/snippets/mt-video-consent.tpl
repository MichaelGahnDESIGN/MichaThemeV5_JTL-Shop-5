{* Video 2-Klick YouTube/Vimeo - Einbinden: {include file='snippets/mt-video-consent.tpl' provider='youtube' videoId='XXXXXXXXXXX' title='Titel'}
   iframe baut erst mt/mt-legal.js nach Klick (youtube-nocookie bzw. Vimeo dnt=1). Vorher wird nichts von Dritten geladen. *}
{if $provider === 'vimeo'}{assign var='mtHost' value='Vimeo'}{else}{assign var='mtHost' value='YouTube'}{/if}
<div class="mt-video" data-mt-video data-mt-video-provider="{$provider|escape:'html'}" data-mt-video-id="{$videoId|escape:'html'}" data-mt-video-title="{$title|default:''|escape:'html'}">
    <div class="mt-video__consent">
        <p>Dieses Video wird erst nach Klick von {$mtHost} geladen. Dabei werden Daten an den Anbieter übertragen.</p>
        {if isset($oSpezialseiten_arr[$smarty.const.LINKTYP_DATENSCHUTZ])}<p><a href="{$oSpezialseiten_arr[$smarty.const.LINKTYP_DATENSCHUTZ]->getURL()|escape:'html'}">Datenschutzerklärung</a></p>{/if}
        <button type="button" class="btn btn-primary" data-mt-video-load>Video von {$mtHost} laden<span class="sr-only">: {$title|default:''|escape:'html'}</span></button>
    </div>
</div>
