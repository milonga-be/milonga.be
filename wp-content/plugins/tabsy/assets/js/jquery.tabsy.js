/**
*
* Tabsy - jQuery Responsive Tabs Plugin
* URL: http://github.com/phpbits
* Version: 1.0
* Author: phpbits
* Author URL: http://github.com/phpbits
*
*/

// Utility
if ( typeof Object.create !== 'function' ) {
	Object.create = function( obj ) {
		function F() {};
		F.prototype = obj;
		return new F();
	};
}

(function( $, window, document, undefined ) {

	var construct_tabsy = {
		init: function( options, elem ) {
			var self = this;

			self.elem = elem;
			self.$elem = $( elem );

			self.options = $.extend( {}, $.fn.tabsy.options, options );
			
			/**--------------
			GET DATA ATTRIBUTES
			-----------------**/
			self.data = $( elem ).data();
			if (typeof self.data.position  !== "undefined"){
				self.options.position = self.data.position;
			}
			if (typeof self.data.alignment  !== "undefined"){
				self.options.alignment = self.data.alignment;
			}
			if (typeof self.data.event  !== "undefined"){
				self.options.event = self.data.event;
			}

			/**--------------
			DEFAULT CLASSES
			-----------------**/
			self.tabs = 'tabsy';
			self.bordered = 'tabsy-bordered';
			self.shadow = 'tabsy-shadow';
			self.active = 'tabsy-active';
			self.nav = 'tabsy-nav';
			self.container = 'tabsy-container';
			self.content = 'tabsy-content' ;
			self.desktop = 'tabsy-desktop';
			self.responsive = 'tabsy-responsive';
			self.r_tabs = 'tabsy-mobile';
			self.clicked = 'tabsy-clicked';
			self.toResponsive = 'tabsy-switch';
			self.small = 'tabsy-small';

			/**--------------
			INLINE CSS
			-----------------**/
			self.activeCss = {};
			self.activeCss['position'] = 'relative'; //set position from absolute
			self.activeCss['opacity'] = '1';
			self.activeCss['height'] = 'auto';
			self.inactiveCss = {};
			self.inactiveCss['position'] = 'absolute'; //set position from absolute

			//run functions
			self.buildDisplay();
			self.buildNav();
			self.buildAlignment();
			if(self.options.responsive){
				self.buildResponsive();
			}
			self.buildControls();
		},
		buildDisplay: function(){
			var self = this;
			/** add default classes **/
			self.$elem.addClass(self.tabs + ' ' + self.tabs + '-' + self.options.position + ' ' + self.tabs + '-align-' + self.options.alignment);
			self.$elem.find( self.options.tabContainer ).addClass(self.container);
			self.$elem.find( self.options.tabContent ).addClass(self.content);
			if(self.options.style.bordered){
				self.$elem.addClass(self.bordered);
			}
			if(self.options.style.shadow){
				self.$elem.addClass(self.shadow);
			}

			// container = self.$elem.find( self.options.tabContainer )[0].outerHTML;
			// self.$elem.find( self.options.tabContainer ).remove();
			
		},
		buildNav: function(){
			var self = this;
			var clone = self.$elem.find( self.options.tabNav );
			self.$elem.find( self.options.tabNav ).remove();
			count = clone.find('li').length;
			mod = count % 3;

			
			if(mod > 0){
				for (var i = mod; i >= 1; i--) {
					a = (count - i) + 1;
					clone.find(' li:nth-child('+ a +')').addClass('tabsy-nav-' + mod);
				};
			}

			clone.find(' li:first-child').addClass('tabsy-nav-first');
			clone.find(' li:last-child').addClass('tabsy-nav-last');
			clone.addClass(self.nav + ' '+ self.desktop);

			var mobileNav = '<ul class="'+ self.nav +' '+ self.r_tabs +'"><li><a href=""><span class="tabsy-mobile-tab">'+ clone.find(' li:nth-child('+ self.options.defaultTab +') a').html() +'</span><span class="tabsy-mobile-menu"></span></a></li></ul>';
			if(self.$elem.hasClass('tabsy-bottom')){
				self.options.position = 'bottom';
			}
			switch( self.options.position ){
				case 'bottom':
					self.$elem.append(mobileNav);
					self.$elem.append(clone);
				break;
				default:
					self.$elem.prepend(clone);
					self.$elem.prepend(mobileNav);
				break;
			}

			self.setActive();
		},
		setActive: function(){
			var self = this;
			self.$elem.find( '.' + self.nav + '.'+ self.desktop + ' li:nth-child('+ self.options.defaultTab +')' ).addClass(self.active);
			self.$elem.find( '.' + self.container + ' .' + self.content + ':nth-child('+ self.options.defaultTab +')' ).addClass(self.active).css(self.activeCss);

			var iframe = $('.' + self.container + ' .' + self.content + ':nth-child('+ self.options.defaultTab +')').find('iframe');
			iframe.attr('src', iframe.attr('src'));
		},
		buildControls: function(){
			var self = this;
			var active = '';
			var tablist = '.'+ self.nav +' li';
			var tabNav = '';
			self.$elem.find(tablist + ' > a').on( self.options.event ,function(e){
				active = $(this).attr('href');
				activeEl =  '.' + self.content + active;
				activeH = self.$elem.find(activeEl).css('height', 'auto').height();
				tabNav = $(this).parent('li').parent('.' + self.nav);
				if(!tabNav.hasClass(self.r_tabs)){
					self.$elem.find(tablist).removeClass( self.active );
					self.$elem.find(self.content).removeClass( self.active );
	
					self.$elem.find('.' + self.content).animate({'opacity':0, 'height' : activeH + 'px'}, self.options.animation.duration,function(){
						self.$elem.find( activeEl ).addClass( self.active );
						self.$elem.find('.' + self.content).css(self.inactiveCss);
						self.$elem.find(activeEl).css(self.activeCss);
					});
					$(this).parent('li').addClass(self.active);
				}

				if(tabNav.hasClass(self.r_tabs)){
					if(self.$elem.find( '.' + self.nav + '.' + self.r_tabs ).hasClass( self.clicked )){
						self.$elem.find( '.' + self.nav + '.' + self.r_tabs ).removeClass( self.clicked );
						self.$elem.find( '.' + self.nav + '.' + self.desktop ).hide();
					}else{
						self.$elem.find( '.' + self.nav + '.' + self.desktop ).show();
						self.$elem.find( '.' + self.nav + '.' + self.r_tabs ).addClass( self.clicked );
					}
				}else if(!tabNav.hasClass(self.r_tabs) && self.$elem.hasClass( self.responsive )){
					self.$elem.find( '.' + self.nav + '.' + self.desktop ).hide();
					self.$elem.find( '.' + self.nav + '.' + self.r_tabs ).removeClass( self.clicked );
				}
				if( $(this).find('.tabsy-mobile-tab').length == 0 ){
					self.$elem.find('.'+ self.nav + '.' +self.r_tabs +' li .tabsy-mobile-tab').html( $(this).html() );
				}
				
				var iframe = $(activeEl).find('iframe');
				iframe.attr('src', iframe.attr('src'));

				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();
			});
		},
		buildResponsive: function(){
			var self = this; var width = 10;
			var clone = self.$elem.clone();
			clone.css({ 'display' : 'block' ,'visibility' : 'hidden'});
    		$('body').append(clone);
			clone.find('.'+ self.nav + '.' +self.desktop +' li').each(function() {
			    var $this = $(this);
			    width += $this.outerWidth();
			});
			clone.remove();
			$( window ).resize(function() {
				self.buildResize(width);
			});

			self.buildResize(width);
		},
		buildResize: function(width){
			var self = this;
			var contentW = self.$elem.width();
			
			if( contentW < width && contentW < self.options.toResponsive){
				self.$elem.removeClass(self.small);
				self.$elem.addClass(self.responsive);
				self.resetBlock();
				self.$elem.find('.'+ self.nav + '.' +self.desktop).hide();
			}else if( contentW < width){
				self.$elem.removeClass(self.small);
				self.$elem.addClass(self.responsive);
				self.resetBlock();
				self.$elem.find('.'+ self.nav + '.' +self.desktop).hide();
			}else{
				self.$elem.removeClass(self.responsive);
				self.$elem.removeClass(self.small);
				if(self.options.alignment == 'justify'){
					self.eqBlock(true);
				}
				self.$elem.find('.'+ self.nav + '.' +self.desktop).show();
			}
		},
		buildAlignment: function(){
			var self = this;
			if(self.options.alignment == 'justify'){
				self.eqBlock(true);
			}
		},
		eqBlock: function(w){
			var self = this;var h = 0;
			self.resetBlock();
			var count = self.$elem.find('.'+ self.nav + '.' +self.desktop +' li').length;
			eq_w = 100/count;
			eq_w = eq_w.toFixed(6);
			if(w){
				self.$elem.find('.'+ self.nav + '.' +self.desktop +' li').css({ width : eq_w + '%'});
			}
			self.$elem.find('.'+ self.nav + '.' +self.desktop +' li').each(function() {
			    var $this = $(this);
			   	ch = $this.outerHeight();
			   	if(ch > h){
			   		h = ch;
			   	}
			});
			self.$elem.find('.'+ self.nav + '.' +self.desktop +' li a').css({ height : h + 'px' });
		},
		resetBlock: function(){
			var self = this;
			self.$elem.find('.'+ self.nav + '.' +self.desktop +' li a').css({ height : 'auto' });
			self.$elem.find('.'+ self.nav + '.' +self.desktop +' li').css({ width : 'auto'});
		}
	};

	$.fn.tabsy = function( options ) {
		return this.each(function() {
			var tabsy = Object.create( construct_tabsy );
			
			tabsy.init( options, this );

			$.data( this, 'tabsy', tabsy );
		}).promise().done(function(){
			$(this).css({ 'visibility': 'visible' });
			if (typeof options.complete  !== "undefined"){
				options.complete.call(this);
			}
		});
	};

	/**--------------
	SET DEFAULTS
	-----------------**/
	$.fn.tabsy.options = {
		defaultTab			: 1,
		tabNav 				: '.tabsy-nav',
		mobileNav			: true,
		tabContainer 		: '.tabsy-container',
		tabContent	 		: '.tabsy-content',
		animation			: {
								duration	: 200,
								effects		: 'toggle'
							},
		style				: {
								bordered	: true,
								shadow		: false
							},
		alignment			: 'left',
		event				: 'click',
		responsive 			: true,
		position			: 'top',
		toResponsive		: 490,
		complete			: null

	};

})( jQuery, window, document );