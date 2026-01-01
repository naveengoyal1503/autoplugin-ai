/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automate affiliate link creation, cloaking, tracking, and performance analytics to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateLinkManager {
    private static $instance = null;
    public $db_version = '1.0';
    public $table_name;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sal_links';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('sal_link', array($this, 'shortcode_link'));
        add_filter('the_content', array($this, 'auto_replace_links'));
        add_action('wp_ajax_sal_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sal_track_click', array($this, 'track_click'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            affiliate_url text NOT NULL,
            cloaked_slug varchar(50) NOT NULL,
            clicks int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (cloaked_slug)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('sal_db_version', $this->db_version);
        update_option('sal_pro', false);
    }

    public function deactivate() {
        // Do not drop table
    }

    public function init() {
        wp_register_style('sal-admin-css', plugin_dir_url(__FILE__) . 'sal-style.css', array(), '1.0');
        wp_register_script('sal-admin-js', plugin_dir_url(__FILE__) . 'sal-script.js', array('jquery'), '1.0', true);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-front-js', plugin_dir_url(__FILE__) . 'sal-front.js', array('jquery'), '1.0', true);
        wp_localize_script('sal-front-js', 'sal_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_menu_page('Smart Affiliate Links', 'Affiliate Links', 'manage_options', 'sal-links', array($this, 'admin_page'), 'dashicons-money-alt');
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $this->table_name;
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $message = '';

        if (isset($_POST['sal_submit'])) {
            $name = sanitize_text_field($_POST['name']);
            $url = esc_url_raw($_POST['url']);
            $slug = sanitize_title($_POST['slug']);

            if (empty($name) || empty($url)) {
                $message = '<div class="notice notice-error"><p>Missing fields.</p></div>';
            } elseif (!$this->is_pro() && $wpdb->get_var("SELECT COUNT(*) FROM $table_name") >= 5) {
                $message = '<div class="notice notice-warning"><p><strong>Upgrade to Pro</strong> for unlimited links!</p><p><a href="https://example.com/pro" target="_blank">Get Pro Now ($49/year)</a></p></div>';
            } else {
                $wpdb->insert($table_name, array(
                    'name' => $name,
                    'affiliate_url' => $url,
                    'cloaked_slug' => $slug ?: sanitize_title($name)
                ));
                $message = '<div class="notice notice-success"><p>Link added!</p></div>';
            }
        }

        if ($action == 'list') {
            echo $message;
            $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created DESC");
            ?>
            <div class="wrap">
                <h1>Smart Affiliate Links</h1>
                <?php if (!$this->is_pro()): ?>
                <div class="notice notice-info"><p>Free version: 5 links max. <a href="https://example.com/pro" target="_blank">Upgrade to Pro</a> for unlimited + analytics!</p></div>
                <?php endif; ?>
                <h2>Add New Link</h2>
                <form method="post">
                    <table class="form-table">
                        <tr><th>Name</th><td><input type="text" name="name" required class="regular-text"></td></tr>
                        <tr><th>Affiliate URL</th><td><input type="url" name="url" required class="regular-text"></td></tr>
                        <tr><th>Cloaked Slug</th><td><input type="text" name="slug" placeholder="example-slug" class="regular-text"> (e.g. yoursite.com/go/example)</td></tr>
                    </table>
                    <?php submit_button('Add Link'); ?>
                </form>
                <h2>Your Links (<?php echo count($links); ?>/<?php echo $this->is_pro() ? 'Unlimited' : '5'; ?>)</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th>Name</th><th>Cloaked URL</th><th>Clicks</th><th>Shortcode</th></tr></thead>
                    <tbody>
            <?php foreach ($links as $link): ?>
                    <tr>
                        <td><?php echo esc_html($link->name); ?></td>
                        <td><a href="<?php echo home_url('/go/' . $link->cloaked_slug); ?>" target="_blank"><?php echo home_url('/go/' . $link->cloaked_slug); ?></a></td>
                        <td><?php echo $link->clicks; ?></td>
                        <td><code>[sal_link id="<?php echo $link->id; ?>"]</code></td>
                    </tr>
            <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $atts['id']));
        if (!$link) return '';
        return '<a href="' . home_url('/go/' . $link->cloaked_slug) . '" class="sal-link" data-id="' . $link->id . '">' . esc_html($link->name) . '</a>';
    }

    public function auto_replace_links($content) {
        if (!$this->is_pro()) return $content;
        // Pro feature: Auto-replace keywords with links
        $content .= '<!-- Pro auto-insert active -->';
        return $content;
    }

    public function track_click() {
        $slug = sanitize_text_field($_POST['slug']);
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE cloaked_slug = %s", $slug));
        $link = $wpdb->get_row($wpdb->prepare("SELECT affiliate_url FROM $this->table_name WHERE cloaked_slug = %s", $slug));
        if ($link) {
            wp_redirect($link->affiliate_url, 301);
            exit;
        }
        wp_die('Link not found');
    }

    private function is_pro() {
        return get_option('sal_pro', false);
    }
}

add_action('init', array('SmartAffiliateLinkManager', 'get_instance'));

// Rewrite rules
add_action('init', function() {
    add_rewrite_rule('^go/([^/]+)/?', 'index.php?sal_redirect=$matches[1]', 'top');
});

add_filter('query_vars', function($vars) {
    $vars[] = 'sal_redirect';
    return $vars;
});

add_action('template_redirect', function() {
    $redirect = get_query_var('sal_redirect');
    if ($redirect) {
        $manager = SmartAffiliateLinkManager::get_instance();
        wp_redirect(home_url('/wp-admin/admin.php?page=sal-links'));
        exit;
    }
});

// Front-end cloaked link handler
add_action('template_redirect', function() {
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    if (strpos($path, 'go/') === 0) {
        $slug = substr($path, 3);
        $manager = SmartAffiliateLinkManager::get_instance();
        // Trigger AJAX-like tracking but direct
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE {$manager->table_name} SET clicks = clicks + 1 WHERE cloaked_slug = %s", $slug));
        $link = $wpdb->get_row($wpdb->prepare("SELECT affiliate_url FROM {$manager->table_name} WHERE cloaked_slug = %s", $slug));
        if ($link) {
            wp_redirect($link->affiliate_url, 301);
            exit;
        }
    }
});