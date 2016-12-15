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
	$('.wptabsy').tabsy({
		tabNav 				: '.wptabsy-nav',
		mobileNav			: true,
		tabContainer 		: '.wptabsy-inner',
		tabContent	 		: '.wptabsy-content',
	});
})( jQuery, window, document );