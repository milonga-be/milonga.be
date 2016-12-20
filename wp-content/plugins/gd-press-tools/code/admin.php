<?php

class gdPTAdmin {
    public $o;
    public $r;
    public $a;
    public $s;
    public $u;

    public $status = '';
    public $script = '';

    public $admin_plugin;
    public $admin_plugin_page;

    public $page_ids = array();

    function __construct() {
        global $gdpt;

        $this->o = $gdpt->o;
        $this->a = $gdpt->a;
        $this->r = $gdpt->r;
        $this->s = $gdpt->s;

        $this->script = $_SERVER['PHP_SELF'];
        $this->script = end(explode('/', $this->script));

        add_filter('plugin_row_meta', array(&$this, 'plugin_links'), 10, 2);
        add_filter('plugin_action_links', array(&$this, 'plugin_actions'), 10, 2);
        add_action('in_admin_footer', array(&$this, 'in_admin_footer'));
        add_action('admin_footer', array(&$this, 'admin_footer'));

        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));

        add_action('admin_init', array(&$this, 'admin_init'));
        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_action('admin_head', array(&$this, 'admin_head'));

        if ($this->o['integrate_post_options'] == 1) {
            add_filter('post_row_actions', array(&$this, 'post_row_actions'), 10, 2);
            add_filter('page_row_actions', array(&$this, 'post_row_actions'), 10, 2);
        }

        add_action('wp_dashboard_setup', array(&$this, 'add_dashboard_widget'));
        if (!function_exists('wp_add_dashboard_widget')) {
            add_filter('wp_dashboard_widgets', array(&$this, 'add_dashboard_widget_filter'));
        }

        add_filter('manage_edit-category_columns', array(&$this, 'admin_cats_columns'));
        add_filter('manage_edit-post_tag_columns', array(&$this, 'admin_tags_columns'));
        add_filter('manage_category_custom_column', array(&$this, 'admin_columns_data_filter'), 10, 3);

        add_filter('manage_edit-comments_columns', array(&$this, 'admin_comments_columns'));
        add_action('manage_comments_custom_column', array(&$this, 'admin_columns_data_filter_new'), 10, 2);
        add_action('manage_users_columns', array(&$this, 'admin_user_columns'));
        add_filter('manage_users_custom_column', array(&$this, 'admin_columns_data_filter'), 10, 3);
        add_filter('manage_post_tag_custom_column', array(&$this, 'admin_columns_data_filter'), 10, 3);

        add_action('manage_posts_columns', array(&$this, 'admin_post_columns'));
        add_action('manage_pages_columns', array(&$this, 'admin_post_columns'));
        add_action('manage_media_columns', array(&$this, 'admin_media_columns'));
        add_action('manage_link-manager_columns', array(&$this, 'admin_links_columns'));
        add_action('manage_posts_custom_column', array(&$this, 'admin_columns_data'), 10, 2);
        add_action('manage_pages_custom_column', array(&$this, 'admin_columns_data'), 10, 2);
        add_action('manage_media_custom_column', array(&$this, 'admin_columns_data'), 10, 2);
        add_action('manage_link_custom_column', array(&$this, 'admin_columns_data'), 10, 2);
    }

    function get($setting) {
        return $this->o[$setting];
    }

    function global_allow($panel) {
        global $gdpt;
        return $gdpt->global_allow($panel);
    }

    function get_shortlink($post_id) {
        return trailingslashit(get_option("home")).$this->o["shorturl_prefix"].$post_id;
    }

    function admin_media_columns($columns) {
        $new_columns = array();
        if ($this->get("integrate_media_id") == 1) {
            $i = 0;
            foreach ($columns as $key => $value) {
                if ($i == 1) $new_columns["gdpt_mediaid"] = "ID";
                $new_columns[$key] = $value;
                $i++;
            }
        }
        else $new_columns = $columns;
        return $new_columns;
    }

    function admin_links_columns($columns) {
        $new_columns = array();
        if ($this->get("integrate_links_id") == 1) {
            $i = 0;
            foreach ($columns as $key => $value) {
                if ($i == 1) $new_columns["gdpt_linksid"] = "ID";
                $new_columns[$key] = $value;
                $i++;
            }
        }
        else $new_columns = $columns;
        return $new_columns;
    }

    function admin_user_columns($columns) {
        $new_columns = array();
        if ($this->get("integrate_user_id") == 1) {
            $i = 0;
            foreach ($columns as $key => $value) {
                if ($i == 1) $new_columns["gdpt_userid"] = "ID";
                $new_columns[$key] = $value;
                $i++;
            }
        }
        else $new_columns = $columns;
        if ($this->get("integrate_user_comments") == 1) $new_columns["gdpt_usercomments"] = __("Comments", "gd-press-tools");
        if ($this->get("integrate_user_display") == 1) $new_columns["gdpt_displayname"] = __("Display Name", "gd-press-tools");
        return $new_columns;
    }

    function admin_post_columns($columns) {
        $new_columns = array();
        if ($this->get("integrate_post_id") == 1) {
            $i = 0;
            foreach ($columns as $key => $value) {
                if ($i == 1) $new_columns["gdpt_postid"] = "ID";
                $new_columns[$key] = $value;
                $i++;
            }
        } else $new_columns = $columns;

        if ($this->get("integrate_post_sticky") == 1) $new_columns["gdpt_sticky"] = __("Sticky", "gd-press-tools");
        if ($this->get("integrate_post_views") == 1) $new_columns["gdpt_views"] = __("Views", "gd-press-tools");

        return $new_columns;
    }

    function admin_columns_data($column, $id) {
        switch ($column) {
            case "gdpt_sticky":
                if (is_sticky($id)) echo __("Yes", "gd-press-tools");
                break;
            case "gdpt_mediaid":
            case "gdpt_postid":
            case "gdpt_linksid":
                echo $id;
                break;
            case "gdpt_views":
                $data = gd_count_views($id);
                echo sprintf('<div class="gdpt_view_line">%s: <strong class="gdpt_view_value">%s</strong></div>%s: <strong class="gdpt_view_value">%s</strong><br />%s: <strong class="gdpt_view_value">%s</strong><br />',
                    __("total", "gd-press-tools"), intval($data->tot_views),
                    __("users", "gd-press-tools"), intval($data->usr_views),
                    __("visitors", "gd-press-tools"), intval($data->vst_views));
                break;
            case "gdpt_options":
                $url = add_query_arg("pid", $id, $_SERVER['REQUEST_URI']);
                $counter = gd_count_revisions($id);
                echo sprintf('<a style="color: #00008b" href="%s" title="%s">%s</a><br />', add_query_arg("gda", "duplicate", $url), __("Duplicate", "gd-press-tools"), __("Duplicate", "gd-press-tools"));
                if ($counter > 0) echo sprintf('<a style="color: #cc0000" onclick="if (confirm(\'%s\')) { return true; } return false;" href="%s" title="%s">%s (%s)</a>', __("Are you sure that you want to delete revisions for this post?", "gd-press-tools"), add_query_arg("gda", "delrev", $url), __("Delete Revisions", "gd-press-tools"), __("Delete Revisions", "gd-press-tools"), $counter);
                break;
        }
    }

    function admin_comments_columns($columns) {
        if ($this->script != "edit-comments.php" && $this->script != "admin-ajax.php") return $columns;

        $new_columns = array();
        if ($this->get("integrate_comment_id") == 1) {
            $i = 0;
            foreach ($columns as $key => $value) {
                if ($i == 1) $new_columns["gdpt_commentid"] = "ID";
                $new_columns[$key] = $value;
                $i++;
            }
        } else $new_columns = $columns;
        return $new_columns;
    }

    function admin_columns_data_filter_new($column, $id) {
        switch ($column) {
            case "gdpt_commentid":
                echo $id;
                break;
        }
    }

    function admin_columns_data_filter($data, $column, $id) {
        switch ($column) {
            case "gdpt_catid":
            case "gdpt_userid":
            case "gdpt_tagid":
                return $id;
                break;
            case "gdpt_usercomments":
                $cmms = isset($this->u[$id]) ? $this->u[$id] : "0";
                return $cmms;
                break;
            case "gdpt_displayname":
                $user = get_userdata($id);
                return $user->display_name;
                break;
        }
    }

    function admin_cats_columns($columns) {
        $new_columns = array();
        if ($this->get("integrate_cat_id") == 1) {
            $i = 0;
            foreach ($columns as $key => $value) {
                if ($i == 1) $new_columns["gdpt_catid"] = "ID";
                $new_columns[$key] = $value;
                $i++;
            }
        }
        else $new_columns = $columns;
        return $new_columns;
    }

    function admin_tags_columns($columns) {
        $new_columns = array();
        if ($this->get("integrate_tag_id") == 1) {
            $i = 0;
            foreach ($columns as $key => $value) {
                if ($i == 1) $new_columns["gdpt_tagid"] = "ID";
                $new_columns[$key] = $value;
                $i++;
            }
        }
        else $new_columns = $columns;
        return $new_columns;
    }

    function add_dashboard_widget() {
        if (!function_exists('wp_add_dashboard_widget')) {
            if ($this->o["integrate_dashboard"] == 1 && current_user_can("presstools_dashboard")) {
                wp_register_sidebar_widget("dashboard_gdpresstools", "GD Press Tools: ".__("Additional Options", "gd-press-tools"), array(&$this, 'display_dashboard_widget'), array('all_link' => get_bloginfo('wpurl').'/wp-admin/admin.php?page=gd-press-tools/gd-press-tools.php', 'width' => 'half', 'height' => 'single'));
            }
        } else {
            if ($this->o["integrate_dashboard"] == 1 && current_user_can("presstools_dashboard")) {
                wp_add_dashboard_widget("dashboard_gdpresstools", "GD Press Tools: ".__("Additional Options", "gd-press-tools"), array(&$this, 'display_dashboard_widget'));
            }
        }
    }

    function add_dashboard_widget_filter($widgets) {
        global $wp_registered_widgets;

        if (!isset($wp_registered_widgets['dashboard_gdpresstools'])) {
            return $widgets;
        }

        if ($this->o['integrate_dashboard'] == 1 && current_user_can('presstools_dashboard')) {
            array_splice($widgets, 2, 0, 'dashboard_gdpresstools');
        }

        return $widgets;
    }

    function display_dashboard_widget($sidebar_args) {
        if (!function_exists('wp_add_dashboard_widget')) {
            extract($sidebar_args, EXTR_SKIP);
            echo $before_widget.$before_title.$widget_name.$after_title;
        }
        $options = $this->o;
        include(PRESSTOOLS_PATH.'modules/widgets/dashboard.php');
        if (!function_exists('wp_add_dashboard_widget')) echo $after_widget;
    }

    function post_row_actions($actions, $post) {
        $url = add_query_arg("pid", $post->ID, $_SERVER['REQUEST_URI']);
        $actions["duplicate"] = sprintf('<a style="color: #00008b" href="%s" title="%s">%s</a>', add_query_arg("gda", "duplicate", $url), __("Duplicate", "gd-press-tools"), __("Duplicate", "gd-press-tools"));
        $counter = gd_count_revisions($post->ID);
        if ($counter > 0) $actions["revisions"] = sprintf('<a style="color: #cc0000" onclick="if (confirm(\'%s\')) { return true; } return false;" href="%s" title="%s">%s (%s)</a>', __("Are you sure that you want to delete revisions for this post?", "gd-press-tools"), add_query_arg("gda", "delrev", $url), __("Delete Revisions", "gd-press-tools"), __("Delete Revisions", "gd-press-tools"), $counter);
        return $actions;
    }

    function admin_enqueue_scripts() {
        wp_enqueue_script('jquery');

        if ($this->admin_plugin) {
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-tabs');

            wp_enqueue_script('thickbox');
            wp_enqueue_style('thickbox');
        }
    }

    function admin_init() {
        global $gdpt;

        if (isset($_GET['page'])) {
            if (substr($_GET['page'], 0, 14) == 'gd-press-tools') {
                $this->admin_plugin = true;
                $this->admin_plugin_page = substr($_GET['page'], 15);
            }
        }

        if ($this->admin_plugin) {
            if (!$gdpt->global_allow($this->admin_plugin_page)) {
                wp_die(__("You do not have permission to access this page.", "gd-press-tools"));
            }
        }

        global $wp_taxonomies;
        foreach ($wp_taxonomies as $tax => $vals) {
            add_filter('manage_'.$tax.'_custom_column', array(&$this, 'admin_columns_data_filter'), 10, 3);
        }

        $this->dashboard_operations();
        $this->init_operations();
        $this->settings_operations();

        if ($this->o['updates_disable_plugins'] == 1) {
            remove_action('admin_init', 'wp_update_plugins');
        }
    }

    function admin_menu() {
        $this->page_ids[] = add_menu_page('GD Press Tools', 'GD Press Tools', "presstools_front", 'gd-press-tools-front', array(&$this,"admin_tool_front"), plugins_url('gd-press-tools/gfx/menu.png'));

        if ($this->get("integrate_postedit_widget") == 1) {
            add_meta_box("gdpt-meta-box", "GD Press Tools", array(&$this, 'editbox_post'), "post", "side", "high");
            add_meta_box("gdpt-meta-box", "GD Press Tools", array(&$this, 'editbox_post'), "page", "side", "high");
        }

        $this->page_ids[] = add_submenu_page('gd-press-tools-front', 'GD Press Tools: '.__("Front Page", "gd-press-tools"), __("Front Page", "gd-press-tools"), "presstools_front", 'gd-press-tools-front', array(&$this,"admin_tool_front"));

        if (is_multisite() && is_super_admin()) {
            $this->page_ids[] = add_submenu_page('gd-press-tools-front', 'GD Press Tools: '.__("Global Settings", "gd-press-tools"), "Global Settings", "presstools_global", "gd-press-tools-global", array(&$this,"admin_tool_global"));
        }

        if ($this->global_allow("server")) {
            $this->page_ids[] = add_submenu_page('gd-press-tools-front', 'GD Press Tools: '.__("Environment Info", "gd-press-tools"), __("Environment Info", "gd-press-tools"), "presstools_info", "gd-press-tools-server", array(&$this,"admin_tool_server"));
        }

        if ($this->global_allow("hooks")) {
            $this->page_ids[] = add_submenu_page('gd-press-tools-front', 'GD Press Tools: '.__("WP Hooks", "gd-press-tools"), __("WP Hooks", "gd-press-tools"), "presstools_info", "gd-press-tools-hooks", array(&$this,"admin_tool_hooks"));
        }

        $this->page_ids[] = add_submenu_page('gd-press-tools-front', 'GD Press Tools: '.__("Administration", "gd-press-tools"), __("Administration", "gd-press-tools"), "presstools_global", "gd-press-tools-admin", array(&$this,"admin_tool_admin"));
        $this->page_ids[] = add_submenu_page('gd-press-tools-front', 'GD Press Tools: '.__("Posts", "gd-press-tools"), __("Posts", "gd-press-tools"), "presstools_global", "gd-press-tools-posts", array(&$this,"admin_tool_posts"));
        $this->page_ids[] = add_submenu_page('gd-press-tools-front', 'GD Press Tools: '.__("Auto Tagger", "gd-press-tools"), __("Auto Tagger", "gd-press-tools"), "presstools_global", "gd-press-tools-tagger", array(&$this,"admin_tool_tagger"));
        $this->page_ids[] = add_submenu_page('gd-press-tools-front', 'GD Press Tools: '.__("Meta Tags", "gd-press-tools"), __("Meta Tags", "gd-press-tools"), "presstools_global", "gd-press-tools-meta", array(&$this,"admin_tool_meta"));

        if ($this->global_allow("database")) {
            $this->page_ids[] = add_submenu_page('gd-press-tools-front', 'GD Press Tools: '.__("Database", "gd-press-tools"), __("Database", "gd-press-tools"), "presstools_global", "gd-press-tools-database", array(&$this,"admin_tool_database"));
        }

        if ($this->global_allow("cron")) {
            $this->page_ids[] = add_submenu_page('gd-press-tools-front', 'GD Press Tools: '.__("Cron Scheduler", "gd-press-tools"), __("Cron Scheduler", "gd-press-tools"), "presstools_global", "gd-press-tools-cron", array(&$this,"admin_tool_cron"));
        }

        $this->page_ids[] = add_submenu_page('gd-press-tools-front', 'GD Press Tools: '.__("Settings", "gd-press-tools"), __("Settings", "gd-press-tools"), "presstools_global", "gd-press-tools-settings", array(&$this,"admin_tool_settings"));
        $this->page_ids[] = add_submenu_page('gd-press-tools-front', 'GD Press Tools: '.__("Upgrade to Pro", "gd-press-tools"), __("Upgrade to Pro", "gd-press-tools"), "presstools_global", "gd-press-tools-gopro", array(&$this,"admin_tool_gopro"));

        if ($this->o["updates_disable_plugins"] == 1) {
            remove_action('load-plugins.php', 'wp_update_plugins');
        }

        $this->admin_load_hooks();
    }

    function admin_load_hooks() {
        global $gdpt;

        if ($gdpt->wp_version < 33) return;

        foreach ($this->page_ids as $id) {
            add_action('load-'.$id, array(&$this, 'load_admin_page'));
        }
    }

    function load_admin_page() {
        $screen = get_current_screen();

        $screen->set_help_sidebar('
            <p><strong>Dev4Press:</strong></p>
            <p><a target="_blank" href="http://www.dev4press.com/">'.__("Website", "gd-press-tools").'</a></p>
            <p><a target="_blank" href="http://twitter.com/dev4press">'.__("On Twitter", "gd-press-tools").'</a></p>
            <p><a target="_blank" href="http://facebook.com/dev4press">'.__("On Facebook", "gd-press-tools").'</a></p>');

        $screen->add_help_tab(array(
            "id" => "gdpt-screenhelp-help",
            "title" => __("Get Help", "gd-press-tools"),
            "content" => '<h5>'.__("General plugin information", "gd-press-tools").'</h5>
                <p><a href="http://d4p.me/gdpt" target="_blank">'.__("Plugin Website", "gd-press-tools").'</a> | 
                <a href="http://www.dev4press.com/plugins/gd-press-tools/faq/" target="_blank">'.__("Frequently asked questions", "gd-press-tools").'</a> | 
                <a href="http://www.dev4press.com/plugins/gd-press-tools/roadmap/" target="_blank">'.__("Development roadmap", "gd-press-tools").'</a></p>
                <h5>'.__("Support for the plugin on Dev4Press", "gd-press-tools").'</h5>
                <p>'.__("Support is available only for Pro version of this plugin.", "gd-press-tools").'</p>
                <p><a href="http://www.dev4press.com/plugins/gd-press-tools/support/" target="_blank">'.__("Support Overview", "gd-press-tools").'</a> | 
                <a href="http://www.dev4press.com/forums/forum/plugins/gd-press-tools/" target="_blank">'.__("Support Forum", "gd-press-tools").'</a> | 
                <a href="http://www.dev4press.com/documentation/product/plg-gd-press-tools/" target="_blank">'.__("Documentation", "gd-press-tools").'</a> | 
                <a href="http://www.dev4press.com/category/tutorials/plugins/gd-press-tools/" target="_blank">'.__("Tutorials", "gd-press-tools").'</a></p>'));

        $screen->add_help_tab(array(
            "id" => "gdpt-screenhelp-website",
            "title" => "Dev4Press", "sfc",
            "content" => '<p>'.__("On Dev4Press website you can find many useful plugins, themes and tutorials, all for WordPress. Please, take a few minutes to browse some of these resources, you might find some of them very useful.", "gd-press-tools").'</p>
                <p><a href="http://www.dev4press.com/plugins/" target="_blank"><strong>'.__("Plugins", "gd-press-tools").'</strong></a> - '.__("We have more than 10 plugins available, some of them are commercial and some are available for free.", "gd-press-tools").'</p>
                <p><a href="http://www.dev4press.com/themes/" target="_blank"><strong>'.__("Themes", "gd-press-tools").'</strong></a> - '.__("All our themes are based on our own xScape Theme Framework, and only available as premium.", "gd-press-tools").'</p>
                <p><a href="http://www.dev4press.com/category/tutorials/" target="_blank"><strong>'.__("Tutorials", "gd-press-tools").'</strong></a> - '.__("Premium and free tutorials for our plugins themes, and many general and practical WordPress tutorials.", "gd-press-tools").'</p>
                <p><a href="http://www.dev4press.com/documentation/" target="_blank"><strong>'.__("Central Documentation", "gd-press-tools").'</strong></a> - '.__("Growing collection of functions, classes, hooks, constants with examples for our plugins and themes.", "gd-press-tools").'</p>
                <p><a href="http://www.dev4press.com/forums/" target="_blank"><strong>'.__("Support Forums", "gd-press-tools").'</strong></a> - '.__("Premium support forum for all with valid licenses to get help. Also, report bugs and leave suggestions.", "gd-press-tools").'</p>'));
    }

    function editbox_post() {
        global $post;

        $robots = get_post_meta($post->ID, '_gdpt_meta_robots', true);
        $meta_robots = array('active' => 0);

        if (!empty($robots)) {
            $robots = explode(',', $robots);

            if ($robots[0] == 'index' || $robots[0] == 'noindex') {
                $meta_robots['standard'] = $robots[0].','.$robots[1];
                unset($robots[0]);
                unset($robots[0]);
            }

            foreach ($robots as $robot) {
                $meta_robots[$robot] = 1;
            }
        } else {
            $meta_robots['active'] = 1;
        }

        include(PRESSTOOLS_PATH.'modules/integrate/postedit.php');
    }

    function admin_head() {
        global $gdpt;

        if ($this->o['updates_disable_core'] == 1) {
            remove_action('admin_notices', 'update_nag', 3 );
            remove_action('admin_notices', 'maintenance_nag');
        }

        if ($this->script == 'users.php') {
            $this->u = GDPTDB::get_all_users_comments_count();
        }

        $gdpt->used_memory['admin_head'] = function_exists('memory_get_usage') ? gdFunctionsGDPT::size_format(memory_get_usage()) : 0;
        $gdpt->time_marker['admin_head'] = microtime();

        print_robots_tag(get_robots_value($this->r, 'admin'));

        if ($this->admin_plugin) {
            echo('<link rel="stylesheet" href="'.PRESSTOOLS_URL.'css/jquery_ui17.css" type="text/css" media="screen" />');
            echo('<link rel="stylesheet" href="'.PRESSTOOLS_URL.'css/admin_main.css" type="text/css" media="screen" />');

            include(PRESSTOOLS_PATH."code/js.php");
            echo('<script type="text/javascript" src="'.PRESSTOOLS_URL.'js/press-tools.js"></script>');
        }

        include(PRESSTOOLS_PATH."code/corrections.php");

        echo('<link rel="stylesheet" href="'.PRESSTOOLS_URL.'css/admin.css" type="text/css" media="screen" />');
        echo('<script type="text/javascript" src="'.PRESSTOOLS_URL.'js/dashboard.js"></script>');
        echo('<!--[if IE]><link rel="stylesheet" href="'.PRESSTOOLS_URL.'css/admin_ie.css" type="text/css" media="screen" /><![endif]-->');
    }

    function dashboard_operations() {
        if (isset($_GET['gdpt'])) {
            $opr = $_GET['gdpt'];

            switch ($opr) {
                case 'delspam':
                    GDPTDB::delete_all_spam();
                    break;
                case 'delrev':
                    $counter = gd_delete_all_revisions();

                    $this->o['counter_total_revisions']+= $counter;
                    $this->o['tool_revisions_removed'] = date('r');

                    update_option('gd-press-tools', $this->o);
                    break;
                case 'cledtb':
                    $size = GDPTDB::get_tables_overhead_simple();

                    $this->o['counter_total_overhead']+= $size;

                    update_option('gd-press-tools', $this->o);

                    gd_optimize_db();
                    break;
            }

            wp_redirect('index.php');
            exit();
        }
    }

    function init_operations() {
        global $gdpt;

        if (isset($_GET['proupgrade']) && $_GET['proupgrade'] == 'hide') {
            $this->o['upgrade_to_pro_44'] = 0;
            update_option('gd-press-tools', $this->o);
            wp_redirect(remove_query_arg('proupgrade'));
            exit;
        }

        if (isset($_GET["gda"])) {
            $gd_action = $_GET["gda"];

            if ($gd_action != '') {
                switch ($gd_action) {
                    case "unsevt":
                        gd_unschedule_event($_GET['time'], $_GET['job'], $_GET['key']);
                        wp_redirect(remove_query_arg(array('time', 'job', 'gda', 'key'), stripslashes($_SERVER['REQUEST_URI'])));
                        exit();
                        break;
                    case "runevt":
                        $job = $_GET['job'];
                        if ($job == "wp_update_plugins") delete_transient("update_plugins");
                        if ($job == "wp_update_themes") delete_transient("update_themes");
                        if ($job == "wp_version_check") delete_transient("update_core");
                        do_action($job);
                        wp_redirect(remove_query_arg(array('job', 'gda'), stripslashes($_SERVER['REQUEST_URI'])));
                        exit();
                        break;
                    case "delrev":
                        $post_id = $_GET["pid"];
                        $counter = gd_delete_revisions($post_id);
                        $this->o["counter_total_revisions"]+= $counter;
                        update_option('gd-press-tools', $this->o);
                        wp_redirect(remove_query_arg(array('pid', 'gda'), stripslashes($_SERVER['REQUEST_URI'])));
                        exit();
                        break;
                    case "duplicate":
                        $post_id = $_GET["pid"];
                        $new_id = GDPTDB::duplicate_post($post_id);
                        if ($new_id > 0) wp_redirect(sprintf("post.php?action=edit&post=%s", $new_id));
                        else wp_redirect(remove_query_arg(array('pid', 'gda'), stripslashes($_SERVER['REQUEST_URI'])));
                        exit();
                        break;
                    case "tpldrp":
                        $table = $_GET["name"];
                        gd_db_table_drop($table);
                        wp_redirect(remove_query_arg(array('name', 'gda'), stripslashes($_SERVER['REQUEST_URI'])));
                        exit();
                        break;
                    case "tblemp":
                        $table = $_GET["name"];
                        gd_db_table_empty($table);
                        wp_redirect(remove_query_arg(array('name', 'gda'), stripslashes($_SERVER['REQUEST_URI'])));
                        exit();
                        break;
                }
            }
        }

        if (isset($_POST['gdpt_tagger_forced'])) {
            $this->o["tagger_abort"] = 1;
            $this->s["tagger"]["status"] = "idle";
            $this->s["tagger"]["ended"] = time();

            update_option("gd-press-tools", $this->o);
            update_option("gd-press-tools-status", $this->s);
            wp_redirect_self();
            exit;
        }

        if (isset($_POST['gdpt_tagger_stop'])) {
            $this->o["tagger_abort"] = 1;
            update_option("gd-press-tools", $this->o);
            wp_redirect_self();
            exit;
        }

        if (isset($_POST['gdpt_dbbackup_delete'])) {
            $files = gdFunctionsGDPT::scan_dir(WP_CONTENT_DIR."/gdbackup/");
            foreach ($files as $fl) {
                if (substr($fl, 0, 10) == "db_backup_") {
                    unlink(WP_CONTENT_DIR."/gdbackup/".$fl);
                }
            }
            wp_redirect_self();
            exit;
        }

        if (isset($_POST['gdpt_backup_run'])) {
            $gziped = isset($_POST["backup_compressed"]);
            $backup = new gdMySQLBackup(GDPTDB::get_tables_names(), WP_CONTENT_DIR."/gdbackup/", $gziped);
            $backup->drop_tables = isset($_POST["backup_drop_exists"]);
            $backup->structure_only = isset($_POST["backup_structure_only"]);
            $backup->backup();
            wp_redirect_self();
            exit;
        }

        if (isset($_POST['gdpt_tagger_run'])) {
            if (!isset($this->s["tagger"]) || $this->s["tagger"]["status"]  == "idle") {
                $this->s["tagger"]["status"] = "scheduled";
                $this->s["tagger"]["limit"] = $_POST['gdpt_tagger_limit'];
                $this->s["tagger"]["posts"] = isset($_POST['gdpt_tagger_post']) ? 1 : 0;
                $this->s["tagger"]["pages"] = isset($_POST['gdpt_tagger_page']) ? 1 : 0;
                $this->s["tagger"]["start"] = $_POST['gdpt_tagger_start'];
                $this->s["tagger"]["end"] = $_POST['gdpt_tagger_end'];
                $this->o["tagger_abort"] = 0;

                update_option("gd-press-tools-status", $this->s);
                update_option("gd-press-tools", $this->o);
                wp_schedule_single_event(time() + 20, 'gdpt_auto_tagger');
                wp_redirect_self();
                exit;
            }
        }

        if (isset($_POST['gdpt_revisions_delete'])) {
            $counter = gd_delete_all_revisions();
            $this->o["counter_total_revisions"]+= $counter;
            $this->o["tool_revisions_removed"] = date("r");
            update_option("gd-press-tools", $this->o);
            wp_redirect_self();
            exit;
        }

        if (isset($_POST['gdpt_cmm_set'])) {
            $cmm_date = $_POST["gdpt_cmm_date"];
            $cmm_comments = isset($_POST["gdpt_cmm_comments"]) ? 1 : 0;
            $cmm_pings = isset($_POST["gdpt_cmm_pings"]) ? 1 : 0;
            GDPTDB::set_posts_comments_status($cmm_date, $cmm_comments, $cmm_pings);
            wp_redirect_self();
            exit;
        }

        if (isset($_POST['gdpt_db_clean'])) {
            $size = GDPTDB::get_tables_overhead_simple();
            $this->o["counter_total_overhead"]+= $size;
            update_option("gd-press-tools", $this->o);
            gd_optimize_db();
            wp_redirect_self();
            exit;
        }

        if (isset($_POST['gdpt_admin_rss_cache_reset'])) {
            gd_clear_rss_cache_transient();
            wp_redirect_self();
            exit;
        }

        if (isset($_POST['gdpt_admin_widget_reset'])) {
            gd_reset_widgets();
            wp_redirect_self();
            exit;
        }

        if (isset($_POST['gdpt_admin_avatar_scan'])) {
            update_option('gd-press-tools-avatars', $gdpt->gravatar_folder($gdpt->g));
            wp_redirect_self();
            exit;
        }

        if (isset($_POST['gdpt_admin_rename'])) {
            $this->status = GDPTDB::rename_account($_POST['gdpt_admin_username']);

            if ($this->status == "OK") {
                wp_redirect_self();
                exit;
            }
        }

        if (isset($_POST['gdpt_admin_folder_protect'])) {
            gd_create_protection_files();
            wp_redirect_self();
            exit;
        }

        if (isset($_POST['gdpt_posts_delete'])) {
            $results = GDPTDB::delete_posts($_POST['gdpt_delposts_date']);
            $this->status = sprintf(__("Deleted %s posts and %s comments.", "gd-press-tools"), $results["posts"], $results["comments"]);
        }
    }

    function settings_operations() {
        global $gdpt;

        if (isset($_POST['gdpt_default_meta'])) {
            update_option('gd-press-tools-robots', $gdpt->default_robots);
            wp_redirect(add_query_arg('settings', 'saved'));
            exit();
        }

        if (isset($_POST['gdpt_saving_meta_general'])) {
            $this->o['meta_wp_noindex'] = isset($_POST['meta_wp_noindex']) ? 1 : 0;
            $this->o['meta_language_active'] = isset($_POST['meta_language_active']) ? 1 : 0;
            $this->o['meta_language_values'] = $_POST['meta_language_values'];
            update_option('gd-press-tools', $this->o);

            wp_redirect(add_query_arg('settings', 'saved'));
            exit();
        }

        if (isset($_POST['gdpt_saving_meta'])) {
            $meta_active = $_POST['gdpt_meta_active'];
            $meta_robots = $_POST['gdpt_meta'];
            $this->r = get_default_meta_robots();

            if (is_array($meta_active)) {
                foreach ($meta_active as $active) $this->r[$active]['active'] = 1;
            }

            if (is_array($meta_robots)) {
                foreach ($meta_robots as $meta) {
                    $parts = explode('|', $meta);
                    $this->r[$parts[0]][$parts[1]] = 1;
                }
            }

            update_option('gd-press-tools-robots', $this->r);
            wp_redirect(add_query_arg('settings', 'saved'));
            exit();
        }

        if (isset($_POST['gdpt_saving_global'])) {
            $this->a['access_server'] = isset($_POST['access_server']) ? 1 : 0;
            $this->a['access_hooks'] = isset($_POST['access_hooks']) ? 1 : 0;
            $this->a['access_database'] = isset($_POST['access_database']) ? 1 : 0;
            $this->a['access_cron'] = isset($_POST['access_cron']) ? 1 : 0;

            update_site_option('gd-press-tools-global', $this->a);

            wp_redirect(add_query_arg('settings', 'saved'));
            exit();
        }

        if (isset($_POST['gdpt_saving'])) {
            global $wp_rewrite;
            $wp_rewrite->flush_rules();

            $this->o['integrate_dashboard'] = isset($_POST['integrate_dashboard']) ? 1 : 0;

            $this->o['html_desc_terms'] = isset($_POST['html_desc_terms']) ? 1 : 0;
            $this->o['html_desc_users'] = isset($_POST['html_desc_users']) ? 1 : 0;
            $this->o['html_desc_links'] = isset($_POST['html_desc_links']) ? 1 : 0;
            $this->o['html_note_links'] = isset($_POST['html_note_links']) ? 1 : 0;

            $this->o['real_capital_p_filter'] = isset($_POST['real_capital_p_filter']) ? 1 : 0;
            $this->o['remove_capital_p_filter'] = isset($_POST['remove_capital_p_filter']) ? 1 : 0;
            $this->o['footer_stats'] = isset($_POST['footer_stats']) ? 1 : 0;
            $this->o['update_report_usage'] = isset($_POST['update_report_usage']) ? 1 : 0;
            $this->o['integrate_post_options'] = isset($_POST['integrate_post_options']) ? 1 : 0;
            $this->o['integrate_comment_id'] = isset($_POST['integrate_comment_id']) ? 1 : 0;
            $this->o['integrate_cat_id'] = isset($_POST['integrate_cat_id']) ? 1 : 0;
            $this->o['integrate_tag_id'] = isset($_POST['integrate_tag_id']) ? 1 : 0;
            $this->o['integrate_user_id'] = isset($_POST['integrate_user_id']) ? 1 : 0;
            $this->o['integrate_user_comments'] = isset($_POST['integrate_user_comments']) ? 1 : 0;
            $this->o['integrate_post_id'] = isset($_POST['integrate_post_id']) ? 1 : 0;
            $this->o['integrate_post_views'] = isset($_POST['integrate_post_views']) ? 1 : 0;
            $this->o['integrate_post_sticky'] = isset($_POST['integrate_post_sticky']) ? 1 : 0;
            $this->o['rss_disable'] = isset($_POST['rss_disable']) ? 1 : 0;
            $this->o['updates_disable_core'] = isset($_POST['updates_disable_core']) ? 1 : 0;
            $this->o['updates_disable_themes'] = isset($_POST['updates_disable_themes']) ? 1 : 0;
            $this->o['updates_disable_plugins'] = isset($_POST['updates_disable_plugins']) ? 1 : 0;
            $this->o['auth_require_login'] = isset($_POST['auth_require_login']) ? 1 : 0;
            $this->o['remove_wp_version'] = isset($_POST['remove_wp_version']) ? 1 : 0;
            $this->o['remove_rds'] = isset($_POST['remove_rds']) ? 1 : 0;
            $this->o['remove_wlw'] = isset($_POST['remove_wlw']) ? 1 : 0;
            $this->o['integrate_media_id'] = isset($_POST['integrate_media_id']) ? 1 : 0;
            $this->o['integrate_links_id'] = isset($_POST['integrate_links_id']) ? 1 : 0;
            $this->o['disable_flash_uploader'] = isset($_POST['disable_flash_uploader']) ? 1 : 0;
            $this->o['remove_login_error'] = isset($_POST['remove_login_error']) ? 1 : 0;
            $this->o['disable_auto_save'] = isset($_POST['disable_auto_save']) ? 1 : 0;
            $this->o['urlfilter_wpadmin_active'] = isset($_POST['urlfilter_wpadmin_active']) ? 1 : 0;
            $this->o['urlfilter_sqlqueries_active'] = isset($_POST['urlfilter_sqlqueries_active']) ? 1 : 0;
            $this->o['urlfilter_requestlength_active'] = isset($_POST['urlfilter_requestlength_active']) ? 1 : 0;
            $this->o['urlfilter_requestlength_value'] = intval($_POST['urlfilter_requestlength_value']);

            $this->o['admin_interface_remove_help'] = isset($_POST['admin_interface_remove_help']) ? 1 : 0;
            $this->o['admin_interface_remove_favorites'] = isset($_POST['admin_interface_remove_favorites']) ? 1 : 0;
            $this->o['admin_interface_remove_logo'] = isset($_POST['admin_interface_remove_logo']) ? 1 : 0;
            $this->o['admin_interface_remove_turbo'] = isset($_POST['admin_interface_remove_turbo']) ? 1 : 0;

            $this->o['debug_sql'] = isset($_POST['debug_sql']) ? 1 : 0;
            $this->o['debug_queries_admin'] = isset($_POST['debug_queries_admin']) ? 1 : 0;
            $this->o['debug_queries_blog'] = isset($_POST['debug_queries_blog']) ? 1 : 0;
            $this->o['debug_queries_global'] = isset($_POST['debug_queries_global']) ? 1 : 0;
            $this->o['admin_bar_disable'] = isset($_POST['admin_bar_disable']) ? 1 : 0;

            $this->o['shorturl_active'] = isset($_POST['shorturl_active']) ? 1 : 0;
            $this->o['shorturl_prefix'] = $_POST['shorturl_prefix'];

            $this->o['posts_views_tracking'] = isset($_POST['posts_views_tracking']) ? 1 : 0;
            $this->o['posts_views_tracking_posts'] = isset($_POST['posts_views_tracking_posts']) ? 1 : 0;
            $this->o['posts_views_tracking_pages'] = isset($_POST['posts_views_tracking_pages']) ? 1 : 0;
            $this->o['posts_views_tracking_visitors'] = isset($_POST['posts_views_tracking_visitors']) ? 1 : 0;
            $this->o['posts_views_tracking_users'] = isset($_POST['posts_views_tracking_users']) ? 1 : 0;
            $this->o['users_tracking'] = isset($_POST['users_tracking']) ? 1 : 0;
            $this->o['users_tracking_posts'] = isset($_POST['users_tracking_posts']) ? 1 : 0;
            $this->o['users_tracking_pages'] = isset($_POST['users_tracking_pages']) ? 1 : 0;
            $this->o['posts_views_tracking_ignore'] = $_POST['posts_views_tracking_ignore'];
            $this->o['users_tracking_ignore'] = $_POST['users_tracking_ignore'];

            $this->o['enable_db_autorepair'] = isset($_POST['enable_db_autorepair']) ? 1 : 0;
            $this->o['revisions_to_save'] = $_POST['revisions_to_save'];

            $this->o['php_memory_limit'] = $_POST['php_memory_limit'];
            $this->o['php_memory_limit_enabled'] = isset($_POST['php_memory_limit_enabled']) ? 1 : 0;

            $this->o['integrate_postedit_widget'] = isset($_POST['integrate_postedit_widget']) ? 1 : 0;

            $this->o['rss_header_enable'] = isset($_POST['rss_header_enable']) ? 1 : 0;
            $this->o['rss_footer_enable'] = isset($_POST['rss_footer_enable']) ? 1 : 0;
            $this->o['rss_header_contents'] = stripslashes(htmlentities($_POST['rss_header_contents'], ENT_QUOTES, get_option('blog_charset')));
            $this->o['rss_footer_contents'] = stripslashes(htmlentities($_POST['rss_footer_contents'], ENT_QUOTES, get_option('blog_charset')));
            $this->o['rss_delay_active'] = isset($_POST['rss_delay_active']) ? 1 : 0;
            $this->o['rss_delay_time'] = $_POST['rss_delay_time'];

            update_option('gd-press-tools', $this->o);

            wp_redirect(add_query_arg('settings', 'saved'));
            exit();
        }
    }

    function plugin_links($links, $file) {
        static $this_plugin;
        global $gdpt;

        if (!$this_plugin) {
            $this_plugin = $gdpt->plugin_name;
        }

        if ($file == $this_plugin){
            $links[] = '<a href="admin.php?page=gd-press-tools-database">'.__("Database Tools", "gd-press-tools").'</a>';
            $links[] = '<a href="http://www.dev4press.com/plugins/gd-press-tools/faq/">'.__("FAQ", "gd-press-tools").'</a>';
            $links[] = '<a target="_blank" style="color: #cc0000; font-weight: bold;" href="http://d4p.me/gdpt">'.__("Upgrade to PRO", "gd-press-tools").'</a>';
        }

        return $links;
    }

    function plugin_actions($links, $file) {
        static $this_plugin;
        global $gdpt;

        if (!$this_plugin) {
            $this_plugin = $gdpt->plugin_name;
        }

        if ($file == $this_plugin ){
            $settings_link = '<a href="admin.php?page=gd-press-tools-settings">'.__("Settings", "gd-press-tools").'</a>';
            array_unshift($links, $settings_link);
        }

        return $links;
    }

    function in_admin_footer() {
        global $gdpt;

        $gdpt->time_marker["footer"] = microtime();
        $gdpt->footer_stats();
        $gdpt->used_memory["footer"] = $gdpt->load_memory;
        if ($gdpt->o["footer_stats"] == 1) {
            echo __("Executed Queries", "gd-press-tools").': ';
            echo '<strong>'.$gdpt->load_query.'</strong> | ';
            echo __("Used memory", "gd-press-tools").': ';
            echo __("Init", "gd-press-tools").': <strong>'.$gdpt->used_memory["init"].'</strong> | ';
            echo __("Header", "gd-press-tools").': <strong>'.$gdpt->used_memory["admin_head"].'</strong> | ';
            echo __("Footer", "gd-press-tools").': <strong>'.$gdpt->used_memory["footer"].'</strong> | ';
            echo __("Page generated in", "gd-press-tools").': <strong>';
            echo $gdpt->load_timer.' '.__("seconds.", "gd-press-tools");
            echo '</strong><br/>';
        }
        _e("Thank you for using", "gd-press-tools");
        echo ' <a target="_blank" href="http://www.dev4press.com/plugins/gd-press-tools/">GD Press Tools '.$gdpt->o["version"].' '.($gdpt->o["edition"] == "lite" ? "Lite" : "Pro").'</a> ';
        _e("administration addon plugin", "gd-press-tools");
        if (defined("GDSIMPLEWIDGETS_INSTALLED")) {
            $gdsw_ver = substr(GDSIMPLEWIDGETS_INSTALLED, strpos(GDSIMPLEWIDGETS_INSTALLED, "_") + 1, 5);
            echo ' and <a target="_blank" href="http://www.dev4press.com/plugins/gd-simple-widgets/">GD Simple Widgets '.$gdsw_ver.'</a> ';
            _e("collection of widgets", "gd-press-tools");
        }
        echo '.<br/>';
    }

    function admin_footer() {
        global $gdpt;

        if ($gdpt->o["debug_queries_global"] == 1 && $gdpt->o["debug_queries_admin"] == 1 && current_user_can("presstools_debug")) {
            echo $gdpt->generate_queries_log();
        }
    }

    function admin_tool_front() {
        $options = $this->o;
        $status = $this->status;
        include(PRESSTOOLS_PATH.'modules/front.php');
    }

    function admin_tool_server() {
        $options = $this->o;
        $status = $this->status;
        include(PRESSTOOLS_PATH.'modules/server.php');
    }

    function admin_tool_hooks() {
        $options = $this->o;
        $status = $this->status;
        include(PRESSTOOLS_PATH.'modules/wp_hooks.php');
    }

    function admin_tool_cron() {
        $options = $this->o;
        $status = $this->status;
        include(PRESSTOOLS_PATH.'modules/cron.php');
    }

    function admin_tool_admin() {
        $options = $this->o;
        $status = $this->status;
        include(PRESSTOOLS_PATH.'modules/admin.php');
    }

    function admin_tool_posts() {
        $options = $this->o;
        $status = $this->status;
        include(PRESSTOOLS_PATH.'modules/posts.php');
    }

    function admin_tool_tagger() {
        global $gdpt;

        $options = $this->o;
        $status = $this->status;
        $s = $gdpt->s;
        include(PRESSTOOLS_PATH.'modules/tagger.php');
    }

    function admin_tool_gopro() {
        $load = "http://www.dev4press.com/wp-content/plugins/gd-product-central/get_lite.php?name=gdpt";
        $response = get_site_transient('gdpresstools_gopro');

        if ($response == '') {
            $response = wp_remote_retrieve_body(wp_remote_get($load));
            set_site_transient('gdpresstools_gopro', $response, 1204800);
        }

        echo($response);
    }

    function admin_tool_meta() {
        $options = $this->o;
        $status = $this->status;
        $meta = $this->r;
        include(PRESSTOOLS_PATH.'modules/meta.php');
    }

    function admin_tool_post_custom() {
        $options = $this->o;
        $status = $this->status;
        include(PRESSTOOLS_PATH.'modules/custom_post.php');
    }

    function admin_tool_user_custom() {
        $options = $this->o;
        $status = $this->status;
        include(PRESSTOOLS_PATH.'modules/custom_user.php');
    }

    function admin_tool_database() {
        $options = $this->o;
        $status = $this->status;
        include(PRESSTOOLS_PATH.'modules/db.php');
    }

    function admin_tool_rss() {
        $options = $this->o;
        $status = $this->status;
        include(PRESSTOOLS_PATH.'modules/rss.php');
    }

    function admin_tool_global() {
        $options = $this->o;
        $global = $this->a;
        $status = $this->status;
        include(PRESSTOOLS_PATH.'modules/global.php');
    }

    function admin_tool_settings() {
        $options = $this->o;
        $status = $this->status;
        include(PRESSTOOLS_PATH.'modules/settings.php');
    }
}

?>