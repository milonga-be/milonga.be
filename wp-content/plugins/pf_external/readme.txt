=== Local Syndication ===
Contributors: WillBontrager
Donate link: http://www.willmaster.com/plugindonate.php
Tags: syndication, URL insert, file insert, insert web page, script output, rotate content, shortcode
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: trunk

Insert content of any page/file/script at any URL on the Internet into any WordPress post or page.

== Description ==

Insert the content of any page/file/script output on the Internet into any WordPress post or page.

Content is published directly onto the post or page or, optionally, published in an iframe. Content can be converted into JavaScript in an attempt to hide it from spiders and robots.

Examples of use:

   * Frequently updated or time-sensitive content.

   * Automated content generated from software.

The plugin is especially useful when you have more than one WordPress installation, perhaps on several domains.

One file/script can provide content for each WordPress installation. Contact information, special sales, political notices, daily quotes, any content can be provided by that one central file or script.

Advanced features include the ability to insert the content as JavaScript (to discourage spiders/robots from reading the content) and the ability to insert plain text content with optional CSS formatting.

More information about the Local Syndication plugin can be found here: http://www.willmaster.com/software/WPplugins/go/lshome_lsplugin

== Installation ==

1. Download local-syndication-plugin.zip from http://www.willmaster.com/download/DownloadHandlerLink.php?file=local-syndication.zip (direct download link)

1. Decompress the file contents.

1. Upload the local-syndication folder to your WordPress plugins directory (/wp-content/plugins/).

1. Activate the Local Syndication plugin from the WordPress Dashboard.

1. See instructions for using the plugin.

== Instructions ==

Note: This plugin requires WordPress version 3.0 or higher.

After uploading the Local Syndication plugin to the plugins directory and activating it:

Insert the following shortcode on a line by itself at the point where you want the content to be inserted.

[syndicate_local url="http://example.com/page.html"]

Replace http://example.come/page.html with the URL to the content.

That is the minimum you need to know to use the Local Syndication plugin.

But there is more to know, including what to do when a page does not publish correctly.

Instructions for using the Local Syndication plugin can be found here: http://www.willmaster.com/software/WPplugins/go/lsinstructions_lsplugin

Advanced features include:

* The ability to insert the content as JavaScript (to discourage spiders/robots from reading the content).

* The ability to insert the content into an iframe instead of publishing it directly on the post or page.

* The ability to insert plain text content with optional CSS formatting.

== Frequently Asked Questions ==

= What if the web page I'm syndicating has relative URLs? =

The plugin does its best to convert relative URLs into absolute http://... URLs so images will display and links will work and relevant CSS and JavaScript files will be imported.

= I see an advanced feature is to publish with JavaScript. Why would I want to publish content with JavaScript? =

To discourage robots and spiders from reading the syndicated content. It may be desirable to keep ads out of search engine indexes, for example, and perhaps also off-topic content so SE's do not come to wrong conclusions regarding a page's topic.

== Screenshots ==

No screenshots available.

== Changelog ==

= 1.3 =
Changed to use wp_remote_get() instead of creating new instance of WP_Http

= 1.2 =
Added the ability to publish syndicated content in an iframe.

= 1.1 =
First public distribution version.

== Upgrade Notice ==

= 1.3 =
Changed to use wp_remote_get() instead of creating new instance of WP_Http

= 1.2 =
Added the ability to publish syndicated content in an iframe.

= 1.1 =
First public distribution version.

