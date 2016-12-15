<?php
/*
 * Create and Display Via Shortcode
 * Jan. 16, 2015
 */

if(!class_exists('WPTABSY_SHORTCODE')){
	class WPTABSY_SHORTCODE{
		/**
		* $shortcode_tag 
		* holds the name of the shortcode tag
		* @var string
		*/
	    public $shortcode_tag = 'wptabsy';

	    /**
		* __construct 
		* class constructor will set the needed filter and action hooks
		* 
		* @param array $args 
		*/
		function __construct($args = array()){
			add_action('init', array($this, 'register_shortcode'));
			add_filter( 'wptabsy_shortcode', array( $this,'shortcode_handler'), 999, 4 );
		}


		/**
		* shortcode_handler
		* @param  array  $atts shortcode attributes
		* @param  string $content shortcode content
		* @return string
		*/
		function shortcode_handler($atts , $content = null){
			$pre_atts = $atts;
			$atts = shortcode_atts(array(
				'id'			=> uniqid(),
	            'skin' 			=> 	null,
	            'responsive' 	=> 	true
	        ), $atts);
			$classes = array('wptabsy', 'wptabsy-' . $atts['id']);
			$data = '';

			if(function_exists('wptabsy_customizer')){
		    	wptabsy_shortcode_start($pre_atts, $atts);
		    	$classes = wptabsy_shortcode_classes( $classes, $pre_atts, $atts );
		    	$data = wptabsy_shortcode_data( $data );
		    }

		    if( isset( $pre_atts['shortcode_id'] ) && !empty( $pre_atts['shortcode_id'] ) && intval( $pre_atts['shortcode_id'] ) > 0 ){
		    	$content = get_post_field('post_content', $pre_atts['shortcode_id']);
		    	$content = wpautop( $content );
		    }

		    $return = '<div class="'. join(' ', $classes);;
		    if(!empty($atts['skin'])){
		    	$return .= ' wptabsy-skin-' . $atts['skin'];
		    }
		    $return .='" '. $data .'>';
		    $return .= $this->extract_tab($content) ;
		    $return .= $this->extract_tabcontent($content) ;
		    $return .= '</div>';

		    if(function_exists('wptabsy_customizer')){
		    	wptabsy_shortcode_end($pre_atts, $atts);
		    }

		    return $return;
		}

		/**
		* extract all [tab] content
		*/

		function extract_tab($content){
			$count = 1;
			$pattern_tabs = '{(\[tab.*?\[/tab\])}is';
	        $pattern_tab = '{\[tab(.*?)\[/tab\]}is';
	        $tabs = preg_split($pattern_tabs, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
	        // echo $content;
	        // echo '<pre>';print_r($tabs); echo '</pre>';
	        $tabs = array_filter($tabs);
	        // $a = shortcode_parse_atts($tabs[0]);
	        $navs = '<ul class="wptabsy-nav">';
	        foreach ($tabs as $tab) {
	        	if (preg_match($pattern_tab, $tab, $matches)) {
	        		$cl = str_replace('[/tab]', '', $matches[0]);
	        		preg_match_all("#\[[^\]]*\]#", $cl, $tag, PREG_PATTERN_ORDER);
	        		
	        		$label = '';
	        		$atts = '';
	        		$icon = '';
	        		if(isset($tag[0][0])){
	        			$label = str_replace($tag[0][0], '', $cl);
	        			$sc = str_replace('[tab', '', $tag[0][0]);
	        			$sc = str_replace(']', '', $sc);
	        			$atts = shortcode_parse_atts($sc);
	        			if(isset($atts['icon'])){
	        				$icon = '<i class="fa '. preg_replace("/&#?[a-z0-9]+;/i","",$atts['icon']) . '"></i> ';
	        			}
	        		}
	        		
	        		$navs .= '<li><a href="#wptabsy-content-'. $count .'">'. $icon . strip_tags($label, '<i><span>') .'</a></li>';
	        		$count++;
	        	}
	        }
	        $navs .= '</ul>';

	        return $navs;
		}

		/**
		* extract all [tabcontent] content
		*/

		function extract_tabcontent($content){
			$count = 1;
			$pattern_tabs = '{(\[tabcontent\].*?\[/tabcontent\])}is';
	        $pattern_tab = '{\[tabcontent\](.*?)\[/tabcontent\]}is';
	        $contents = preg_split($pattern_tabs, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
	        $contents = array_filter($contents);
	        $return = '<div class="wptabsy-inner">';
	        foreach ($contents as $content) {
	        	if (preg_match($pattern_tab, $content, $matches)) {
	        		$return .= '<div class="wptabsy-content" id="wptabsy-content-'. $count .'">'. do_shortcode( $matches[1] ) .'</div>';
	        		$count++;
	        	}
	        }
	        $return .= '</div>';

	        return $return;
		}

		/**
		* register_shortcode
		*/
		function register_shortcode(){
			add_shortcode($this->shortcode_tag, array($this, 'shortcode_handler'));
		}

		}
		new WPTABSY_SHORTCODE();
}
?>