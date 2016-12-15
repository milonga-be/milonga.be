<?php
/*
Plugin Name: PF_ExternalUrl
Plugin URI: http://www.willmaster.com/software/WPplugins/
Description: Insert content of any page/file/script at any URL on the Internet into any WordPress post or page. Usage: [pf_external url='']
Version: 1.0
Date: 7 Sept 2012
Author: Peter Forret <peter@forret?com>
Author URI: http://www.forret.com
*/

/*
	Copyright 2012 Peter Forret (email: peter@forret.com)
	Copyright 2011 Will Bontrager Software, LLC (email: will@willmaster.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation. A copy of the license is at
	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
*/

/*
Note: This plugin requires WordPress version 3.0 or higher.

Information about the Local Syndication plugin can be found here:
http://www.willmaster.com/software/WPplugins/go/lshome_lsplugin

Instructions for using the Local Syndication plugin can be found here:
http://www.willmaster.com/software/WPplugins/go/lsinstructions_lsplugin
*/

if( ! function_exists('pf_external_handler') )
{
	function pf_external_handler($atts,$content=null,$code="")
	{
		$url = $atts['url'];
		if( strpos($url,':') === false and strpos($url,'/') !== 0 ) { $url = "/$url"; }
		if( strpos($url,'/') === 0 ) { $url = 'http://' . $_SERVER['HTTP_HOST'] . $url; }
		$pre_result = $post_result = '';
		$js = empty($atts['js']) ? false : true;
		if( $js ) { $pre_result = '<script type="text/javascript">'; }
		$iframe = empty($atts['iframe']) ? false : true;
		$plain = empty($atts['plain']) ? false : true;
		if( $iframe )
		{
			unset($atts['url']);
			unset($atts['iframe']);
			if( $js ) { unset($atts['js']); }
			if( $plain ) { unset($atts['plain']); }
			if( isset($atts['src']) ) { unset($atts['src']); }
			if( isset($atts['wrap_long_lines']) ) { unset($atts['wrap_long_lines']); }
			$s .= "<iframe src='$url' ";
			foreach( $atts as $k => $v ) { $s .= "$k='$v' "; }
			$s .= '">';
			$post_result = '</iframe>';
			if( $js )
			{
				$pre_result .= pf_external_handler_js_line($s);
				$post_result = pf_external_handler_js_line($post_result).'</script>';
			}
			else { $pre_result .= $s; }
			return "$pre_result$post_result";
		}
		$response = wp_remote_get($url);
		if( is_wp_error($response) )
		{
		   $error_string = $response->get_error_message();
			return "WordPress error: $error_string";
		}
		$response_content = $response['body'];
		if( empty($response['body']) and empty($response['response']['code']) )
		{
			return "Unable to obtain content at $url";
		}
		if( $response['response']['code'] != 200 )
		{
			return 'Unable to obtain content at<br>' . 
				"$url<br>" .
				$response['response']['code'] . ' - ' . $response['response']['message'];
		}
		if( $plain )
		{
			if( empty($atts['wrap_long_lines']) ) { $wrapdef = 'pre'; }
			else { $wrapdef = 'pre-wrap'; }
			$s = "<div style=\"white-space:$wrapdef;";
			if( isset($atts['style']) ) { $s .= $atts['style']; }
			$s .= '"';
			if( isset($atts['class']) ) { $s .= 'class="' . $atts['class'] . '" '; }
			$s .= '>';
			$post_result = '</div>';
			if( $js )
			{
				$pre_result .= pf_external_handler_js_line($s);
				$post_result = pf_external_handler_js_line($post_result);
			}
			else { $pre_result .= $s; }
		} # if( $plain )
		elseif( ! $iframe )
		{
			$baseurl = $url;
			$baseurl = preg_replace('/[\#\?].*$/','',$baseurl);
			if( preg_match('/\.\w+$/',$baseurl) )
			{
				$ta = explode('/',$baseurl);
				array_pop($ta);
				$baseurl = implode('/',$ta);
			}
			if( preg_match('/<base\s[^>]*?href=([^>\s]+)/',$response_content,$matches) )
			{
				$ret = preg_replace('!["\']/$!','',$matches[1]);
				$ret = preg_replace('/^["\']*/','',$ret);
				$ret = preg_replace('/["\']*$/','',$ret);
				if( ! empty($ret) ) { $baseurl = $ret; }
			}
			$basepieces = array();
			$http = $domain = '';
			if( ! preg_match('!^\w+://!',$baseurl) ) { $baseurl = ''; }
			else
			{
				$baseurl = preg_replace('!/*$!','',$baseurl);
				$i = strpos($baseurl,'://') + 3;
				$http = substr( $baseurl, 0, $i );
				$basepieces = explode( '/', substr($baseurl,$i) );
				$domain = array_shift($basepieces);
			}
			if( ! empty($baseurl) )
			{
				preg_match_all('/(href=)(["\']?)([^"\'>\s]+)/i',$response_content,$matches);
				pf_external_handler_normalize_urls($basepieces, $http, $domain, $matches, $response_content);
				preg_match_all('/(src=)(["\']?)([^"\'>\s]+)/i',$response_content,$matches);
				pf_external_handler_normalize_urls($basepieces, $http, $domain, $matches, $response_content);
			}
		}
		if( $js )
		{ 
			$post_result .= '</script>';
			$response_content = pf_external_handler_js_block($response_content);
		}
		return "$pre_result$response_content$post_result";
	} # function pf_external_handler()

	function pf_external_handler_normalize_urls($basepieces, $http, $domain, $matches, &$response_content)
	{
		$done = array();
		$count = count($matches[0]);
		for( $indice=0; $indice<$count; $indice++ )
		{
			$relurl = $matches[3][$indice];
			if( empty($relurl) ) { continue; }
			if( isset($done[$relurl]) ) { continue; }
			if( strpos($relurl,':') ) { continue; }
			if( strpos($relurl,'#') === 0 ) { continue; }
			$done[$relurl] = 1;
			$absurl = pf_external_handler_make_absolute($basepieces, $http, $domain, $relurl);
			$matchurl = quotemeta($matches[0][$indice]);
			$matchurl = str_replace('/',"\\".'/',$matchurl);
			$response_content = preg_replace("/$matchurl/i", $matches[1][$indice].$matches[2][$indice].$absurl, $response_content);
		}
	} # function pf_external_handler_normalize_urls()

	function pf_external_handler_make_absolute($basepieces, $http, $domain, $url)
	{
 		$bpieces = $basepieces;
		if( strpos($url,'://') ) { return $url; }
		if( strpos($url,'/') === 0 ) { return "$http$domain$url"; }
		$pieces = $tackle = explode('/',$url);
		foreach( $tackle as $piece )
		{
			if( $piece == '.' ) { array_shift($pieces); }
			elseif( $piece == '..' )
			{
				array_shift($pieces);
				if( is_array($bpieces) and count($bpieces) ) { array_pop($bpieces); }
			}
			else { break; }
		}
		$basepiece = count($bpieces) ? implode('/',$bpieces) . '/' : '';
		return "$http$domain/$basepiece" . implode('/',$pieces);
	}

	function pf_external_handler_js_line($s)
	{
		$s = str_replace("\\","\\"."\\",$s);
		$s = str_replace("'","\\"."'",$s);
		$s = str_replace('</',"<'+'/",$s);
		$s = str_replace('/>',"/'+'>",$s);
		$s = preg_replace('/\b(scr)(ipt)/i',"$1'+'$2",$s);
		$s = preg_replace('/\b(ifr)(ame)/i',"$1'+'$2",$s);
		return "document.writeln('$s');\n";
	}

	function pf_external_handler_js_block(&$s)
	{
		$s2 = '';
		$s = str_replace("\r",'',$s);
		foreach( explode("\n",$s) as $line ) { $s2 .= pf_external_handler_js_line($line); }
		return $s2;
	}

	add_shortcode('pf_external','pf_external_handler');

} # if( ! function_exists('pf_external_handler') )

?>
