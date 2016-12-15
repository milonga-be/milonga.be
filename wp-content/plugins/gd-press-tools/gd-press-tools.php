<?php

/*
Plugin Name: GD Press Tools
Plugin URI: http://www.dev4press.com/gd-press-tools/
Description: GD Press Tools is a collection of various administration, seo, maintenance and security related tools that can help with everyday blog tasks and blog optimizations.
Version: 2.7
Author: Milan Petrovic
Author URI: http://www.dev4press.com/

== Copyright ==
Copyright 2008 - 2013 Milan Petrovic (email: milan@gdragon.info)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$gdpt_dirname_basic = dirname(__FILE__);

require_once($gdpt_dirname_basic.'/config.php');

require_once($gdpt_dirname_basic.'/code/defaults.php');
require_once($gdpt_dirname_basic.'/gdragon/gd_debug.php');
require_once($gdpt_dirname_basic.'/gdragon/gd_db_install.php');
require_once($gdpt_dirname_basic.'/gdragon/gd_functions.php');
require_once($gdpt_dirname_basic.'/gdragon/gd_wordpress.php');
require_once($gdpt_dirname_basic.'/code/classes.php');
require_once($gdpt_dirname_basic.'/code/functions.php');
require_once($gdpt_dirname_basic.'/code/db.php');
require_once($gdpt_dirname_basic.'/code/meta.php');

define('PRESSTOOLS_WP_ADMIN', defined('WP_ADMIN') && WP_ADMIN);

class GDPressTools {
    var $load_time = '0';
    var $load_timer = '0';
    var $load_memory = '0';
    var $load_query = '0';

    var $avatar_extensions = array('gif', 'png', 'jpg', 'jpeg');
    var $used_memory = array();
    var $time_marker = array();
    var $wp_version;
    var $plugin_url;
    var $plugin_path;
    var $plugin_name;

    var $admin_plugin;
    var $admin_plugin_page;
    var $u;
    var $a;
    var $o;
    var $r;
    var $g;
    var $s;
    var $l;

    var $status = '';
    var $script = '';

    var $default_global;
    var $default_options;
    var $default_robots;
    var $default_caps;

    function __construct() {
        $this->plugin_name = plugin_basename(__FILE__);

        $this->used_memory['load'] = function_exists('memory_get_usage') ? gdFunctionsGDPT::size_format(memory_get_usage()) : 0;
        $this->time_marker['load'] = microtime();

        $gdd = new GDPTDefaults();
        $this->default_global = $gdd->default_global;
        $this->default_options = $gdd->default_options;
        $this->default_robots = $gdd->default_robots;
        $this->default_caps = $gdd->default_caps;

        define('PRESSTOOLS_INSTALLED', $this->default_options['version'].' '.($this->default_options['edition'] == 'lite' ? 'Lite' : 'Pro'));

        $this->plugin_path_url();
        $this->install_plugin();
        $this->actions_filters();
        $this->updates_checks();
        $this->remove_actions();
        $this->initialize_caps();

        if (PRESSTOOLS_PHP_SETTINGS) $this->php_ini();

        if ($this->o['revisions_to_save'] != -1 && !defined('WP_POST_REVISIONS')) define('WP_POST_REVISIONS', $this->o['revisions_to_save']);
        if ($this->o['debug_queries_global'] == 1 && !defined('SAVEQUERIES')) define('SAVEQUERIES', true);
        if ($this->o['enable_db_autorepair'] == 1 && !defined('WP_ALLOW_REPAIR')) define('WP_ALLOW_REPAIR', true);

        define('PRESSTOOLS_DEBUG_SQL', $this->o['debug_sql'] == 1);
    }

    private function plugin_path_url() {
        $this->plugin_url = WP_PLUGIN_URL.'/gd-press-tools/';
        $this->plugin_path = dirname(__FILE__).'/';

        define('PRESSTOOLS_URL', $this->plugin_url);
        define('PRESSTOOLS_PATH', $this->plugin_path);
    }

    private function install_plugin() {
        global $wp_version;
        $this->wp_version = substr(str_replace('.', '', $wp_version), 0, 2);

        $this->o = get_option('gd-press-tools');
        $this->r = get_option('gd-press-tools-robots');
        $this->g = get_option('gd-press-tools-avatars');
        $this->s = get_option('gd-press-tools-status');

        $this->a = get_site_option('gd-press-tools-global');

        if (!is_array($this->a)) {
            update_site_option('gd-press-tools-global', $this->default_global);
            $this->a = get_site_option('gd-press-tools-global');
        }

        if (!is_array($this->s)) {
            update_option('gd-press-tools-status', array());
            $this->s = get_option('gd-press-tools-status');
        }

        if (!is_array($this->r)) {
            update_option('gd-press-tools-robots', $this->default_robots);
            $this->r = get_option('gd-press-tools-robots');
        }

        if (!is_array($this->g)) {
            update_option('gd-press-tools-avatars', array());
            $this->g = get_option('gd-press-tools-avatars');
        }

        $installed = false;
        if (!is_array($this->o)) {
            $this->default_options['memory_limit'] = ini_get('memory_limit');
            update_option('gd-press-tools', $this->default_options);
            $this->o = get_option('gd-press-tools');
            $installed = true;
        }

        if ($this->o['build'] != $this->default_options['build'] ||
            $this->o['edition'] != $this->default_options['edition'] ||
            $installed) {

            $this->o = gdFunctionsGDPT::upgrade_settings($this->o, $this->default_options);
            $this->a = gdFunctionsGDPT::upgrade_settings($this->a, $this->default_global);

            gdDBInstallGDPT::delete_tables(PRESSTOOLS_PATH);
            gdDBInstallGDPT::create_tables(PRESSTOOLS_PATH);
            gdDBInstallGDPT::upgrade_tables(PRESSTOOLS_PATH);
            gdDBInstallGDPT::alter_tables(PRESSTOOLS_PATH);
            $this->o['database_upgrade'] = date('r');

            $this->o['version'] = $this->default_options['version'];
            $this->o['date'] = $this->default_options['date'];
            $this->o['status'] = $this->default_options['status'];
            $this->o['build'] = $this->default_options['build'];
            $this->o['revision'] = $this->default_options['revision'];
            $this->o['edition'] = $this->default_options['edition'];

            update_option('gd-press-tools', $this->o);
            update_site_option('gd-press-tools-global', $this->a);

            $this->fix_folders();
            $this->backup_folder();
            update_option('gd-press-tools-avatars', $this->gravatar_folder($this->g));
            $this->g = get_option('gd-press-tools-avatars');

            gd_create_protection_file(WP_CONTENT_DIR.'/avatars/');
            gd_create_protection_file(WP_CONTENT_DIR.'/gdbackup/');
        }

        $this->script = $_SERVER['PHP_SELF'];
        $this->script = end(explode('/', $this->script));
    }

    private function actions_filters() {
        if (is_admin()) {
            if ($this->get('integrate_postedit_widget') == 1) {
                add_action('save_post', array(&$this, 'saveedit_post'));
            }
        } else {
            add_action('wp_head', array(&$this, 'wp_head'));
            add_action('wp_footer', array(&$this, 'blog_footer'));
            add_filter('the_content', array(&$this, 'count_views'));
            add_filter('the_excerpt_rss', array(&$this, 'expand_rss'));
            add_filter('the_content_rss', array(&$this, 'expand_rss'));
        }

        add_action('login_head', array(&$this, 'login_head'));
        add_action('init', array(&$this, 'init'));
        add_filter('avatar_defaults', array(&$this, 'add_avatars'));
        add_filter('login_redirect', array(&$this, 'login_redirect'), 10, 3);
        add_action('gdpt_auto_tagger', array(&$this, 'auto_tagger_cron'));

        if ($this->get('remove_capital_p_filter') == 1) {
            remove_filter('the_content', 'capital_P_dangit');
            remove_filter('the_title', 'capital_P_dangit');
            remove_filter('comment_text', 'capital_P_dangit');
        }

        if ($this->get('real_capital_p_filter') == 1) {
            add_filter('the_content', 'presstools_capital_p');
            add_filter('the_title', 'presstools_capital_p');
            add_filter('comment_text', 'presstools_capital_p');
        }

        if ($this->get('shorturl_active') == 1) {
            add_filter('query_vars', array(&$this, 'rewrite_variables'));
            add_action('generate_rewrite_rules', array(&$this, 'rewrite_rules'));
            add_action('parse_request', array(&$this, 'rewrite_parse'));
        }

        if ($this->o['auth_require_login'] == 1)
            add_action('get_header', array(&$this, 'require_login'));
        if ($this->o['disable_auto_save'] == 1 && is_admin())
            add_action('wp_print_scripts', array(&$this, 'disable_auto_save'));
        if ($this->get('rss_delay_active') == 1 && $this->get('rss_delay_time') > 0)
            add_filter('posts_where', array(&$this, 'delayed_rss_publish'));
        if ($this->o['remove_login_error'] == 1)
            add_filter('login_errors', create_function('$gdptloginerror', 'return null;'));
        if ($this->o['disable_flash_uploader'] == 1)
            add_filter('flash_uploader', array(&$this, 'disable_flash_uploader'), 5);
    }

    private function updates_checks() {
        if ($this->o['updates_disable_core'] == 1) {
            remove_action('wp_version_check', 'wp_version_check');
            remove_action('admin_init', '_maybe_update_core');
            add_filter('pre_transient_update_core', create_function('$update_core', "return null;"));
        }

        if ($this->o['updates_disable_plugins'] == 1) {
            remove_action('load-plugins.php', 'wp_update_plugins');
            remove_action('load-update.php', 'wp_update_plugins');
            remove_action('admin_init', '_maybe_update_plugins');
            remove_action('wp_update_plugins', 'wp_update_plugins');
            add_filter('pre_transient_update_plugins', create_function('$update_plugin', "return null;"));
        }

        if ($this->o['updates_disable_themes'] == 1) {
            remove_action('load-themes.php', 'wp_update_themes');
            remove_action('load-update.php', 'wp_update_themes');
            remove_action('admin_init', '_maybe_update_themes');
            remove_action('wp_update_themes', 'wp_update_themes');
            add_filter('pre_transient_update_themes', create_function('$update_theme', "return null;"));
        }
    }

    private function remove_actions() {
        if ($this->o['admin_bar_disable'] == 1) add_filter('show_admin_bar', '__return_false');
        if ($this->o['html_desc_terms'] == 1) remove_filter('pre_term_description', 'wp_filter_kses');
        if ($this->o['html_desc_links'] == 1) remove_filter('pre_link_description', 'wp_filter_kses');
        if ($this->o['html_note_links'] == 1) remove_filter('pre_link_notes', 'wp_filter_kses');
        if ($this->o['html_desc_users'] == 1) remove_filter('pre_user_description', 'wp_filter_kses');

        if ($this->o['meta_wp_noindex'] == 1) {
            remove_action('login_head', 'noindex');
            remove_action('wp_head', 'noindex');
        }
    }

    private function initialize_caps() {
        presstools_add_caps_to_role('administrator', $this->default_caps);
    }

    function get($setting) {
        return $this->o[$setting];
    }

    function php_ini() {
        if ($this->o['php_memory_limit_enabled'] == 1)
            @ini_set('memory_limit', $this->o["php_memory_limit"]);
    }

    function backup_folder() {
        if (!is_dir(WP_CONTENT_DIR.'/gdbackup')) {
            mkdir(WP_CONTENT_DIR.'/gdbackup', 0755);
        }
    }

    function fix_folders() {
        if (is_dir(WP_CONTENT_DIR.'/gdbackup')) {
            if (gdFunctionsGDPT::file_permission(WP_CONTENT_DIR.'/gdbackup') != '0755')
                chmod(WP_CONTENT_DIR.'/gdbackup', 0755);
        }
        if (is_dir(WP_CONTENT_DIR.'/avatars')) {
            if (gdFunctionsGDPT::file_permission(WP_CONTENT_DIR.'/avatars') != '0755')
                chmod(WP_CONTENT_DIR.'/avatars', 0755);
        }
    }

    function gravatar_folder($gravatars) {
        if (!is_dir(WP_CONTENT_DIR.'/avatars')) {
            mkdir(WP_CONTENT_DIR.'/avatars', 0755);
        }

        if (is_dir(WP_CONTENT_DIR.'/avatars/')) {
            $files = gdFunctionsGDPT::scan_dir(WP_CONTENT_DIR.'/avatars');
            foreach ($files as $file) {
                $ext = end(explode('.', $file));
                if (in_array($ext, $this->avatar_extensions)) {
                    $nme = substr($file, 0, strlen($file) - 1 - strlen($ext));
                    $found = false;
                    foreach ($gravatars as $gr) {
                        if ($gr->file == $file) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $gr = new gdptAvatar();
                        $gr->name = $nme;
                        $gr->file = $file;
                        $gravatars[] = $gr;
                    }
                }
            }
        }

        return $gravatars;
    }

    function add_avatars($avatars) {
        foreach ($this->g as $gravatar) {
            if ($gravatar->include) {
                $avatars[$gravatar->get_url()] = $gravatar->name;
            }
        }
        return $avatars;
    }

    function disable_flash_uploader(){
        return false;
    }

    function get_tags_yahoo($title, $content) {
        if(!function_exists('curl_init')) {
            return array();
        }

        $content = $title."\r\n".strip_tags($content);

        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, 'http://search.yahooapis.com/ContentAnalysisService/V1/termExtraction');
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($crl, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($crl, CURLOPT_TIMEOUT, 3600);
        curl_setopt($crl, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($crl, CURLOPT_POST, 1);
        curl_setopt($crl, CURLOPT_POSTFIELDS, array('appid' => 'GDPressToolsLite', 'context' => $content, 'query' => $title, 'output' => 'php'));

        $response = curl_exec($crl);

        if (curl_errno($crl)) {
            return curl_error($crl);
        }

        curl_close($crl);
        $results = unserialize($response);
        $tags = is_array($results['ResultSet']['Result']) ? $results['ResultSet']['Result'] : array();
        return $tags;
    }

    function auto_tagger_cron() {
        $this->s['tagger']['memory_init'] = memory_get_usage();
        $posts = GDPTDB::get_cron_elements($this->s['tagger']);

        $this->s['tagger']['status'] = 'running';
        $this->s['tagger']['total'] = count($posts);
        $this->s['tagger']['started'] = time();
        $this->s['tagger']['processed'] = 0;
        $this->s['tagger']['tags_found'] = 0;
        $this->s['tagger']['last_id'] = 0;
        $this->s['tagger']['last_error'] = '';

        update_option('gd-press-tools-status', $this->s);
        set_time_limit(10800);
        $i = $abort = 0;

        foreach ($posts as $p) {
            if ($i%5) {
                $gl_o = gd_get_option_force('gd-press-tools');
                $abort = $gl_o['tagger_abort'];
            }
            if ($abort == 0) {
                $tags = $this->get_tags_yahoo($p->post_title, $p->post_content);
                if (is_array($tags)) {
                    if (count($tags) > 0) {
                        $this->s['tagger']['tags_found']+= count($tags);
                        $tags = array_slice($tags, 0, $this->s['tagger']['limit']);
                        wp_add_post_tags($p->ID, strtolower(join(', ', $tags)));
                    }
                } else {
                    $this->s['tagger']['last_error'] = $tags;
                }

                $this->s['tagger']['processed']++;
                $this->s['tagger']['last_id'] = $p->ID;
                $this->s['tagger']['latest'] = time();
                $this->s['tagger']['memory'] = memory_get_usage();
                update_option('gd-press-tools-status', $this->s);
            } else {
                break;
            }

            $i++;
        }

        $this->s['tagger']['status'] = 'idle';
        $this->s['tagger']['ended'] = time();
        update_option('gd-press-tools-status', $this->s);
    }

    function upgrade_notice() {
        if ($this->o['upgrade_to_pro_44'] == 1) {
            $no_thanks = add_query_arg('proupgrade', 'hide');

            echo '<div class="updated">';
                echo __("Thank you for using this plugin. Please, take a few minutes and check out the Pro version of this plugin with many new and improved features.", "gd-press-tools");
                echo '<br/>'.__("Buy plugin Pro version or Dev4Press Plugins Pack and get 15% discount using this coupon:", "gd-press-tools");
                echo ' <strong style="color: #c00;">GDPRESSLITETOPRO</strong><br/>';
                echo '<strong><a href="http://d4p.me/gdpt" target="_blank">'.__("Overview on Dev4Press", "gd-press-tools")."</a></strong> | ";
                echo '<strong><a href="http://d4p.me/247" target="_blank">'.__("Dev4Press Plugins Pack", "gd-press-tools")."</a></strong> | ";
                echo '<a href="'.$no_thanks.'">'.__("Don't display this message anymore", "gd-press-tools")."</a>.";
            echo '</div>';
        }
    }

    function login_redirect($redirect, $request, $user) {
        if (strtolower(get_class($user)) == "wp_user") {
            update_user_meta($user->ID, "gdpt_last_login", time());
        }
        return $redirect;
    }

    function get_shortlink($post_id) {
        return trailingslashit(get_option("home")).$this->o["shorturl_prefix"].$post_id;
    }

    function rewrite_variables($qv) {
        $qv[] = "gdshlink";
        return $qv;
    }

    function rewrite_rules($wp_rewrite) {
        $rules = array(
            $this->o["shorturl_prefix"].'([0-9]{1,})$' => 'index.php?gdshlink='.$wp_rewrite->preg_index(1)
        );

        $wp_rewrite->rules = $rules + $wp_rewrite->rules;
        return $wp_rewrite;
    }

    function rewrite_parse($wpq) {
        if (isset($wpq->query_vars["gdshlink"])) {
            $post_id = $wpq->query_vars["gdshlink"];

            if ($post_id > 0) {
                $location = get_permalink($post_id);
                wp_redirect($location);
                exit;
            }
        }
    }

    function global_allow($panel) {
        if (is_multisite() && !is_super_admin()) {
            $pcode = "access_".$panel;
            $panels = array_keys($this->a);
            if (in_array($pcode, $panels)) {
                $allow = $this->a[$pcode];
                return $allow == 1;
            } else return true;
        } else return true;
    }

    function disable_auto_save() {
        wp_deregister_script('autosave');
    }

    function delayed_rss_publish($content) {
        if (is_feed()) {
            global $wpdb;
            $content.= sprintf(" AND timestampdiff(minute, %s.post_date_gmt, '%s') > %s",
                $wpdb->posts, gmdate('Y-m-d H:i:s'), intval($this->get("rss_delay_time")));
        }
        return $content;
    }

    function wp_head() {
        if ($this->o["debug_queries_global"] == 1 && $this->o["debug_queries_blog"] == 1 && current_user_can("presstools_debug")) {
            echo('<link rel="stylesheet" href="'.PRESSTOOLS_URL.'css/blog_debug.css" type="text/css" media="screen" />');
        }

        if ($this->o["meta_language_active"] == 1) {
            echo sprintf('<meta http-equiv="content-language" content="%s">', $this->o["meta_language_values"]);
        }

        add_meta_tag_robots($this->r);

        $this->used_memory["blog_head"] = function_exists("memory_get_usage") ? gdFunctionsGDPT::size_format(memory_get_usage()) : 0;
        $this->time_marker["blog_head"] = microtime();
    }

    function login_head() {
        print_robots_tag(get_robots_value($this->r, "login"));
    }

    function url_scanner() {
        if (!current_user_can('manage_options')) {
            $filter = true;

            if (is_admin()) {
                $filter = $this->o['urlfilter_wpadmin_active'] == 1;
            }

            if ($filter) {
                $fail = false;
                $url = $_SERVER['REQUEST_URI'];

                if ($this->o['urlfilter_requestlength_active'] == 1) {
                    $fail = strlen($url) > intval($this->o['urlfilter_requestlength_value']);
                }

                if (!$fail && $this->o['urlfilter_sqlqueries_active'] == 1) {
                    $fail = stripos($_SERVER['REQUEST_URI'], 'eval(') ||
                            stripos($_SERVER['REQUEST_URI'], 'CONCAT') ||
                            stripos($_SERVER['REQUEST_URI'], 'UNION+SELECT') ||
                            stripos($_SERVER['REQUEST_URI'], 'base64');
                }

                if ($fail) {
                    wp_gdpt_dump('URL_RESTRICTED', $url);

                    @header("HTTP/1.1 414 Request-URI Too Long");
                    @header("Status: 414 Request-URI Too Long");
                    @header("Connection: Close");
                    @exit;
                }
            }
        }
    }

    function init() {
        $this->url_scanner();

        $this->used_memory['init'] = function_exists('memory_get_usage') ? gdFunctionsGDPT::size_format(memory_get_usage()) : 0;
        $this->time_marker['init'] = microtime();

        $this->l = get_locale();

        if(!empty($this->l)) {
            load_plugin_textdomain('gd-press-tools', false, 'gd-press-tools/languages');
        }

        if ($this->o['rss_disable'] == 1) {
            add_action('do_feed', 'gd_disable_feed', 1);
            add_action('do_feed_rdf', 'gd_disable_feed', 1);
            add_action('do_feed_rss', 'gd_disable_feed', 1);
            add_action('do_feed_rss2', 'gd_disable_feed', 1);
            add_action('do_feed_atom', 'gd_disable_feed', 1);
        }

        if ($this->o['remove_wp_version'] == 1)
            add_filter('the_generator', create_function('$wpv', "return null;"));

        if ($this->o['remove_rds'] == 1 && function_exists('rsd_link') && !is_admin())
            remove_action('wp_head', 'rsd_link');

        if ($this->o['remove_wlw'] == 1 && function_exists('wlwmanifest_link') && !is_admin())
            remove_action('wp_head', 'wlwmanifest_link');
    }

    function recalculate_load_time($endtime, $starttime) {
        $startarray = explode(" ", $starttime);
        $starttime = $startarray[1] + $startarray[0];
        $endarray = explode(" ", $endtime);
        $endtime = $endarray[1] + $endarray[0];
        $totaltime = $endtime - $starttime; 
        return round($totaltime, 5);
    }

    function footer_stats() {
        $this->load_time = $this->recalculate_load_time($this->time_marker['footer'], $this->time_marker['load']);
        $this->load_memory = function_exists('memory_get_usage') ? gdFunctionsGDPT::size_format(memory_get_usage()) : 0;
        $this->load_query = get_num_queries();
        $this->load_timer = timer_stop();
    }

    function blog_footer() {
        if ($this->o['debug_queries_global'] == 1 && $this->o['debug_queries_blog'] == 1 && current_user_can('presstools_debug')) {
            echo $this->generate_queries_log(true);
        }
    }

    function generate_queries_log($info = false) {
        global $wpdb;
        $result = $queries = '';

        if ($wpdb->queries) {
            $queries.= '<div class="gdptdebugq"><ol>';
            $total_time = 0;
            foreach ($wpdb->queries as $q) {
                $queries.= "<li>";

                $queries.= "<strong>".__("Call originated from", "gd-press-tools").":</strong> ".$q[2]."<br />";
                $queries.= "<strong>".__("Execution time", "gd-press-tools").":</strong> ".$q[1]."<br />";
                $queries.= "<em>".$q[0]."</em>";
                $total_time+= $q[1];

                $queries.= "</li>";
            }
            $queries.= '</ol></div>';

            $result.= '<div class="gdptdebugq">';
            if ($info) {
                $result.= '<p class="gdptinfoq">';
                $result.= __("Thank you for using", "gd-press-tools");
                $result.= ' <a target="_blank" href="http://www.dev4press.com/plugins/gd-press-tools/">GD Press Tools '.$this->o["version"].'</a> ';
                $result.= __("administration addon plugin", "gd-press-tools");
                $result.= '.</p>';
            }
            $result.= "<strong>".__("Page generated in", "gd-press-tools").":</strong> ".(timer_stop(0)).' '.__("seconds.", "gd-press-tools")."<br />";
            $result.= "<strong>".__("Total memory used", "gd-press-tools").":</strong> ".(function_exists("memory_get_usage") ? gdFunctionsGDPT::size_format(memory_get_usage()) : 0)."<br />";
            $result.= "<strong>".__("Total number of queries", "gd-press-tools").":</strong> ".count($wpdb->queries)."<br />";
            $result.= "<strong>".__("Total execution time", "gd-press-tools").":</strong> ".round($total_time, 5)." ".__("seconds", "gd-press-tools");
            $result.= '</div>';
            $result.= $queries;
        }

        return $result;
    }

    function saveedit_post($post_id) {
        if (isset($_POST['post_ID']) && $_POST['post_ID'] > 0) {
            $post_id = $_POST['post_ID'];
        }

        if (isset($_POST['gdpt_post_edit']) && $_POST['gdpt_post_edit'] == 'edit') {
            if (isset($_POST['gdpt_meta_robots'])) {
                delete_post_meta($post_id, '_gdpt_meta_robots');
            } else {
                $robots = array();
                $raw = $_POST['gdpt_meta_robots_extra'];

                if ($_POST['gdpt_meta_robots_standard'] != '') {
                    $robots = explode(',', $_POST['gdpt_meta_robots_standard']);
                }

                if (is_array($raw)) {
                    foreach ($raw as $value => $status) {
                        $robots[] = $value;
                    }
                }

                update_post_meta($post_id, '_gdpt_meta_robots', join(',', $robots));
            }
        }
    }

    function expand_rss($content) {
        if (is_feed()) {
            global $post;
            if ($this->get('rss_header_enable') == 1) {
                $header = '<p>'.html_entity_decode($this->get("rss_header_contents")).'</p>';
                $header = apply_filters("gdpt_expandrss_header", $header, $post);
                $content = $header.$content;
            }
            if ($this->get('rss_footer_enable') == 1) {
                $footer = '<p>'.html_entity_decode($this->get("rss_footer_contents")).'</p>';
                $footer = apply_filters("gdpt_expandrss_footer", $footer, $post);
                $content.= $footer;
            }
        }
        return $content;
    }

    function count_views($content) {
        global $post, $userdata;
        $user_id = isset($userdata) ? $userdata->ID : 0;

        if ($post->post_status == 'publish' && !is_feed() && !is_admin()) {
            if ($this->o['posts_views_tracking'] == 1) {
                if ((is_single() && $this->o['posts_views_tracking_posts'] == 1) ||
                    (is_page() && $this->o['posts_views_tracking_pages'] == 1)) {
                    $users = explode(",", $this->o['posts_views_tracking_ignore']);
                    if (($user_id == 0 && $this->o['posts_views_tracking_visitors'] == 1) ||
                        ($user_id > 0 && $this->o['posts_views_tracking_users'] == 1 && !in_array($user_id, $users))) {
                        GDPTDB::insert_posts_views($post->ID, $user_id > 0);
                    }
                }
            }

            if ($this->o['users_tracking'] == 1 && $user_id > 0) {
                if ((is_single() && $this->o['users_tracking_posts'] == 1) ||
                    (is_page() && $this->o['users_tracking_pages'] == 1)) {
                    $users = explode(',', $this->o['users_tracking_ignore']);
                    if (!in_array($user_id, $users)) {
                        GDPTDB::insert_users_tracking($post->ID, $user_id);
                    }
                }
            }
        }

        return $content;
    }

    function require_login() {
        if (!is_user_logged_in() && strpos($_SERVER['PHP_SELF'], 'wp-login.php') === false && strpos($_SERVER['PHP_SELF'], 'wp-register.php') === false)
            auth_redirect();
    }
}

$gdpt_debug = new gdDebugGDPT(PRESSTOOLS_LOG_PATH);
$gdpt = new GDPressTools();

if (PRESSTOOLS_WP_ADMIN) {
    require_once($gdpt_dirname_basic.'/code/admin.php');

    $gdpt_admin = new gdPTAdmin();

    function gdpt_upgrade_notice() {
        global $gdpt;
        $gdpt->upgrade_notice();
    }
}

/**
* Writes a object dump into the log file
*
* @param string $msg log entry message
* @param mixed $object object to dump
* @param string $block adds start or end dump limiters { none | start | end }
* @param string $mode file open mode
*/
function wp_gdpt_dump($msg, $obj, $block = "none", $mode = "a+") {
    if (PRESSTOOLS_DEBUG_ACTIVE) {
        global $gdpt_debug;
        $gdpt_debug->dump($msg, $obj, $block, $mode);
    }
}

/**
* Writes a object dump into the log file if the sql logging is active
*
* @param string $msg log entry message
* @param mixed $object object to dump
* @param string $block adds start or end dump limiters { none | start | end }
* @param string $mode file open mode
*/
function wp_gdpt_log_sql($msg, $obj, $block = "none", $mode = "a+") {
    if (PRESSTOOLS_DEBUG_SQL) wp_gdpt_dump($msg, $obj, $block, $mode);
}

?>