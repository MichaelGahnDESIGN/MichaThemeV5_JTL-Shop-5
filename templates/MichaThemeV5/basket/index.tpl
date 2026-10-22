{extends file="{$parentTemplateDir}basket/index.tpl"}

{* Gewährleistung Hinweis im Warenkrob *}
{block name='basket-index-proceed-button' prepend}
    {include file='snippets/mt-warranty.tpl'}
{/block}
