{* AUTOMATISCH ERZEUGT aus themes/core/options.json (php bin/build-themes.php). Nicht von Hand ändern. *}
{assign var='mtAttrs' value=[]}{assign var='mtVars' value=[]}
{capture assign='mtV'}{$Einstellungen.template.mt_colors.mt_color_mode}{/capture}{append var='mtAttrs' value=$mtV index='data-theme'}
{capture assign='mtV'}{$Einstellungen.template.mt_colors.mt_accent}{/capture}{append var='mtAttrs' value=$mtV index='data-accent'}
{capture assign='mtV'}{$Einstellungen.template.mt_typography.mt_font_body}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-font-body'}
{capture assign='mtV'}{$Einstellungen.template.mt_typography.mt_font_heading}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-font-heading'}
{capture assign='mtV'}{$Einstellungen.template.mt_typography.mt_font_size}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-size'}
{capture assign='mtV'}{$Einstellungen.template.mt_shape.mt_radius}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-radius'}
{capture assign='mtV'}{$Einstellungen.template.mt_shape.mt_density}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-density'}
{capture assign='mtV'}{$Einstellungen.template.mt_shape.mt_shadows}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-shadow'}
{capture assign='mtV'}{$Einstellungen.template.mt_shape.mt_buttons}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-buttons'}
{capture assign='mtV'}{$Einstellungen.template.mt_header.mt_header_layout}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-header'}
{capture assign='mtV'}{if $Einstellungen.template.mt_header.mt_header_sticky === 'Y'}1{else}0{/if}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-sticky'}
{capture assign='mtV'}{$Einstellungen.template.mt_header.mt_header_search}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-search'}
{capture assign='mtV'}{if $Einstellungen.template.mt_header.mt_announcement === 'Y'}1{else}0{/if}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-announcement'}
{capture assign='mtV'}{$Einstellungen.template.mt_footer.mt_footer_layout}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-footer'}
{capture assign='mtV'}{$Einstellungen.template.mt_hero.mt_hero_style}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-hero'}
{capture assign='mtV'}{$Einstellungen.template.mt_hero.mt_hero_height}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-hero-height'}
{capture assign='mtV'}{$Einstellungen.template.mt_listing.mt_listing_layout}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-listing'}
{capture assign='mtV'}{$Einstellungen.template.mt_listing.mt_listing_ratio}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-ratio'}
{capture assign='mtV'}{$Einstellungen.template.mt_listing.mt_listing_hover}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-hover'}
{capture assign='mtV'}{if $Einstellungen.template.mt_listing.mt_listing_badges === 'Y'}1{else}0{/if}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-badges'}
{capture assign='mtV'}{$Einstellungen.template.mt_listing.mt_listing_filter}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-filter'}
{capture assign='mtV'}{$Einstellungen.template.mt_detail.mt_detail_layout}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-detail'}
{capture assign='mtV'}{$Einstellungen.template.mt_detail.mt_detail_gallery}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-gallery'}
{capture assign='mtV'}{if $Einstellungen.template.mt_detail.mt_detail_sticky_cart === 'Y'}1{else}0{/if}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-sticky-cart'}
{capture assign='mtV'}{$Einstellungen.template.mt_detail.mt_detail_tabs}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-tabs'}
{capture assign='mtV'}{$Einstellungen.template.mt_a11y.mt_a11y_focus}{/capture}{append var='mtAttrs' value=$mtV index='data-mt-focus'}
{capture assign='mtV'}{if $Einstellungen.template.mt_colors.mt_accent_custom !== ''}{$Einstellungen.template.mt_colors.mt_accent_custom}{/if}{/capture}{append var='mtVars' value=$mtV index='--mt-accent-custom'}
{capture assign='mtV'}{if $Einstellungen.template.mt_logo.mt_logo_height !== ''}{$Einstellungen.template.mt_logo.mt_logo_height}px{/if}{/capture}{append var='mtVars' value=$mtV index='--mt-logo-height'}
{capture assign='mtV'}{if $Einstellungen.template.mt_listing.mt_listing_columns !== ''}{$Einstellungen.template.mt_listing.mt_listing_columns}{/if}{/capture}{append var='mtVars' value=$mtV index='--mt-listing-cols'}
<meta name="mt-config" content="{ldelim}&quot;attrs&quot;:{$mtAttrs|json_encode|escape:'html'},&quot;vars&quot;:{$mtVars|json_encode|escape:'html'}{rdelim}">
