/*
 * jQuery UI Accordion 1.8.1
 *
 * Copyright (c) 2010 AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://docs.jquery.com/UI/Accordion
 *
 * Depends:
 *	jquery.ui.core.js
 *	jquery.ui.widget.js
 */

(function(c){c.widget("ui.accordion",{options:{active:0,animated:"slide",autoHeight:!0,clearStyle:!1,collapsible:!1,event:"click",fillSpace:!1,header:"> li > :first-child,> :not(li):even",icons:{header:"ui-icon-triangle-1-e",headerSelected:"ui-icon-triangle-1-s"},navigation:!1,navigationFilter:function(){return this.href.toLowerCase()==location.href.toLowerCase()}},_create:function(){var a=this.options,d=this;this.running=0;this.element.addClass("ui-accordion ui-widget ui-helper-reset");this.element[0].nodeName==
"UL"&&this.element.children("li").addClass("ui-accordion-li-fix");this.headers=this.element.find(a.header).addClass("ui-accordion-header ui-helper-reset ui-state-default ui-corner-all").bind("mouseenter.accordion",function(){c(this).addClass("ui-state-hover")}).bind("mouseleave.accordion",function(){c(this).removeClass("ui-state-hover")}).bind("focus.accordion",function(){c(this).addClass("ui-state-focus")}).bind("blur.accordion",function(){c(this).removeClass("ui-state-focus")});this.headers.next().addClass("ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom");
if(a.navigation){var b=this.element.find("a").filter(a.navigationFilter);if(b.length){var f=b.closest(".ui-accordion-header");this.active=f.length?f:b.closest(".ui-accordion-content").prev()}}this.active=this._findActive(this.active||a.active).toggleClass("ui-state-default").toggleClass("ui-state-active").toggleClass("ui-corner-all").toggleClass("ui-corner-top");this.active.next().addClass("ui-accordion-content-active");this._createIcons();this.resize();this.element.attr("role","tablist");this.headers.attr("role",
"tab").bind("keydown",function(a){return d._keydown(a)}).next().attr("role","tabpanel");this.headers.not(this.active||"").attr("aria-expanded","false").attr("tabIndex","-1").next().hide();this.active.length?this.active.attr("aria-expanded","true").attr("tabIndex","0"):this.headers.eq(0).attr("tabIndex","0");c.browser.safari||this.headers.find("a").attr("tabIndex","-1");a.event&&this.headers.bind(a.event+".accordion",function(a){d._clickHandler.call(d,a,this);a.preventDefault()})},_createIcons:function(){var a=
this.options;a.icons&&(c("<span/>").addClass("ui-icon "+a.icons.header).prependTo(this.headers),this.active.find(".ui-icon").toggleClass(a.icons.header).toggleClass(a.icons.headerSelected),this.element.addClass("ui-accordion-icons"))},_destroyIcons:function(){this.headers.children(".ui-icon").remove();this.element.removeClass("ui-accordion-icons")},destroy:function(){var a=this.options;this.element.removeClass("ui-accordion ui-widget ui-helper-reset").removeAttr("role").unbind(".accordion").removeData("accordion");
this.headers.unbind(".accordion").removeClass("ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ui-state-active ui-corner-top").removeAttr("role").removeAttr("aria-expanded").removeAttr("tabIndex");this.headers.find("a").removeAttr("tabIndex");this._destroyIcons();var c=this.headers.next().css("display","").removeAttr("role").removeClass("ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content ui-accordion-content-active");(a.autoHeight||a.fillHeight)&&c.css("height",
"");return this},_setOption:function(a,d){c.Widget.prototype._setOption.apply(this,arguments);a=="active"&&this.activate(d);a=="icons"&&(this._destroyIcons(),d&&this._createIcons())},_keydown:function(a){var d=c.ui.keyCode;if(!this.options.disabled&&!a.altKey&&!a.ctrlKey){var b=this.headers.length,f=this.headers.index(a.target),g=!1;switch(a.keyCode){case d.RIGHT:case d.DOWN:g=this.headers[(f+1)%b];break;case d.LEFT:case d.UP:g=this.headers[(f-1+b)%b];break;case d.SPACE:case d.ENTER:this._clickHandler({target:a.target},
a.target),a.preventDefault()}return g?(c(a.target).attr("tabIndex","-1"),c(g).attr("tabIndex","0"),g.focus(),!1):!0}},resize:function(){var a=this.options,d;if(a.fillSpace){if(c.browser.msie){var b=this.element.parent().css("overflow");this.element.parent().css("overflow","hidden")}d=this.element.parent().height();c.browser.msie&&this.element.parent().css("overflow",b);this.headers.each(function(){d-=c(this).outerHeight(!0)});this.headers.next().each(function(){c(this).height(Math.max(0,d-c(this).innerHeight()+
c(this).height()))}).css("overflow","auto")}else a.autoHeight&&(d=0,this.headers.next().each(function(){d=Math.max(d,c(this).height())}).height(d));return this},activate:function(a){this.options.active=a;a=this._findActive(a)[0];this._clickHandler({target:a},a);return this},_findActive:function(a){return a?typeof a=="number"?this.headers.filter(":eq("+a+")"):this.headers.not(this.headers.not(a)):a===!1?c([]):this.headers.filter(":eq(0)")},_clickHandler:function(a,d){var b=this.options;if(!b.disabled)if(a.target){var f=
c(a.currentTarget||d),g=f[0]==this.active[0];b.active=b.collapsible&&g?!1:c(".ui-accordion-header",this.element).index(f);if(!(this.running||!b.collapsible&&g))this.active.removeClass("ui-state-active ui-corner-top").addClass("ui-state-default ui-corner-all").find(".ui-icon").removeClass(b.icons.headerSelected).addClass(b.icons.header),g||(f.removeClass("ui-state-default ui-corner-all").addClass("ui-state-active ui-corner-top").find(".ui-icon").removeClass(b.icons.header).addClass(b.icons.headerSelected),
f.next().addClass("ui-accordion-content-active")),h=f.next(),e=this.active.next(),i={options:b,newHeader:g&&b.collapsible?c([]):f,oldHeader:this.active,newContent:g&&b.collapsible?c([]):h,oldContent:e},b=this.headers.index(this.active[0])>this.headers.index(f[0]),this.active=g?c([]):f,this._toggle(h,e,i,g,b)}else if(b.collapsible){this.active.removeClass("ui-state-active ui-corner-top").addClass("ui-state-default ui-corner-all").find(".ui-icon").removeClass(b.icons.headerSelected).addClass(b.icons.header);
this.active.next().addClass("ui-accordion-content-active");var e=this.active.next(),i={options:b,newHeader:c([]),oldHeader:b.active,newContent:c([]),oldContent:e},h=this.active=c([]);this._toggle(h,e,i)}},_toggle:function(a,d,b,f,g){var e=this.options,i=this;this.toShow=a;this.toHide=d;this.data=b;var h=function(){return!i?void 0:i._completed.apply(i,arguments)};this._trigger("changestart",null,this.data);this.running=d.size()===0?a.size():d.size();if(e.animated){b={};b=e.collapsible&&f?{toShow:c([]),
toHide:d,complete:h,down:g,autoHeight:e.autoHeight||e.fillSpace}:{toShow:a,toHide:d,complete:h,down:g,autoHeight:e.autoHeight||e.fillSpace};if(!e.proxied)e.proxied=e.animated;if(!e.proxiedDuration)e.proxiedDuration=e.duration;e.animated=c.isFunction(e.proxied)?e.proxied(b):e.proxied;e.duration=c.isFunction(e.proxiedDuration)?e.proxiedDuration(b):e.proxiedDuration;var f=c.ui.accordion.animations,k=e.duration,j=e.animated;j&&!f[j]&&!c.easing[j]&&(j="slide");f[j]||(f[j]=function(a){this.slide(a,{easing:j,
duration:k||700})});f[j](b)}else e.collapsible&&f?a.toggle():(d.hide(),a.show()),h(!0);d.prev().attr("aria-expanded","false").attr("tabIndex","-1").blur();a.prev().attr("aria-expanded","true").attr("tabIndex","0").focus()},_completed:function(a){var c=this.options;this.running=a?0:--this.running;this.running||(c.clearStyle&&this.toShow.add(this.toHide).css({height:"",overflow:""}),this.toHide.removeClass("ui-accordion-content-active"),this._trigger("change",null,this.data))}});c.extend(c.ui.accordion,
{version:"1.8.1",animations:{slide:function(a,d){a=c.extend({easing:"swing",duration:300},a,d);if(a.toHide.size())if(a.toShow.size()){var b=a.toShow.css("overflow"),f=0,g={},e={},i,h=a.toShow;i=h[0].style.width;h.width(parseInt(h.parent().width(),10)-parseInt(h.css("paddingLeft"),10)-parseInt(h.css("paddingRight"),10)-(parseInt(h.css("borderLeftWidth"),10)||0)-(parseInt(h.css("borderRightWidth"),10)||0));c.each(["height","paddingTop","paddingBottom"],function(b,d){e[d]="hide";var f=(""+c.css(a.toShow[0],
d)).match(/^([\d+-.]+)(.*)$/);g[d]={value:f[1],unit:f[2]||"px"}});a.toShow.css({height:0,overflow:"hidden"}).show();a.toHide.filter(":hidden").each(a.complete).end().filter(":visible").animate(e,{step:function(c,b){b.prop=="height"&&(f=b.end-b.start===0?0:(b.now-b.start)/(b.end-b.start));a.toShow[0].style[b.prop]=f*g[b.prop].value+g[b.prop].unit},duration:a.duration,easing:a.easing,complete:function(){a.autoHeight||a.toShow.css("height","");a.toShow.css("width",i);a.toShow.css({overflow:b});a.complete()}})}else a.toHide.animate({height:"hide"},
a);else a.toShow.animate({height:"show"},a)},bounceslide:function(a){this.slide(a,{easing:a.down?"easeOutBounce":"swing",duration:a.down?1E3:200})}}})})(jQuery);
