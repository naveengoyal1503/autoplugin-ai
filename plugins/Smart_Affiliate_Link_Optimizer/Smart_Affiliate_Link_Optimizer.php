/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Optimizer
 * Plugin URI: https://example.com/smart-affiliate-optimizer
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with click analytics and A/B testing to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateOptimizer {
    private static $instance = null;
    private $db_version = '1.0';
    private $table_name;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'saol_links';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'cloak_links'));
        add_shortcode('saol_link', array($this, 'shortcode_link'));
        add_action('wp_ajax_saol_track_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_saol_track_click', array($this, 'ajax_track_click'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            original_url text NOT NULL,
            cloaked_url varchar(255) NOT NULL,
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('saol_db_version', $this->db_version);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function init() {
        wp_register_style('saol-admin-css', plugins_url('style.css', __FILE__), array(), '1.0');
        wp_register_script('saol-admin-js', plugins_url('admin.js', __FILE__), array('jquery'), '1.0', true);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saol-frontend', plugins_url('frontend.js', __FILE__), array('jquery'), '1.0', true);
        wp_localize_script('saol-frontend', 'saol_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function cloak_links($content) {
        if (is_admin() || !is_singular()) return $content;

        preg_match_all('/href=["\'](https?:\/\/[^\s<>"\']+)["\']/i', $content, $matches);
        $urls = $matches[1];

        foreach ($urls as $url) {
            if (strpos($url, 'affiliate') !== false || strpos($url, '?ref=') !== false) {
                $cloaked = $this->get_or_create_cloaked_url($url);
                $content = str_replace($url, $cloaked, $content);
            }
        }
        return $content;
    }

    private function get_or_create_cloaked_url($original_url) {
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare("SELECT cloaked_url FROM $this->table_name WHERE original_url = %s", $original_url));
        if ($existing) return $existing;

        $cloaked = home_url('/go/' . md5($original_url));
        $wpdb->insert($this->table_name, array(
            'original_url' => $original_url,
            'cloaked_url' => $cloaked
        ));
        return $cloaked;
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('url' => ''), $atts);
        $cloaked = $this->get_or_create_cloaked_url($atts['url']);
        return '<a href="' . esc_url($cloaked) . '" class="saol-link">Click Here</a>';
    }

    public function ajax_track_click() {
        if (!isset($_POST['link_id'])) wp_die();

        global $wpdb;
        $link_id = intval($_POST['link_id']);
        $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE id = %d", $link_id));

        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $link_id));
        if ($link) {
            wp_redirect($link->original_url, 301);
            exit;
        }
        wp_die();
    }

    public function admin_menu() {
        add_options_page('SAOL Settings', 'Affiliate Optimizer', 'manage_options', 'saol', array($this, 'admin_page'));
    }

    public function admin_enqueue($hook) {
        if ('settings_page_saol' !== $hook) return;
        wp_enqueue_style('saol-admin-css');
        wp_enqueue_script('saol-admin-js');
    }

    public function admin_page() {
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Optimizer</h1>
            <p><strong>Free Features:</strong> Auto-cloaking, basic tracking. <a href="https://example.com/premium">Upgrade to Premium for A/B testing & analytics ($49/year)</a></p>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Original URL</th><th>Cloaked URL</th><th>Clicks</th></tr></thead>
                <tbody>
        <?php foreach ($links as $link): ?>
                    <tr>
                        <td><?php echo $link->id; ?></td>
                        <td><?php echo esc_html($link->original_url); ?></td>
                        <td><?php echo esc_html($link->cloaked_url); ?></td>
                        <td><?php echo $link->clicks; ?></td>
                    </tr>
        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

// Handle cloaked URLs
add_action('init', function() {
    if (strpos($_SERVER['REQUEST_URI'], '/go/') === 0) {
        global $wpdb;
        $slug = basename($_SERVER['REQUEST_URI']);
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}saol_links WHERE cloaked_url LIKE %s", "%/$slug"));
        if ($link) {
            // AJAX track would be here, but for direct hit, simple redirect with param
            $track_url = add_query_arg('saol_track', $link->id, admin_url('admin-ajax.php?action=saol_track_click&link_id=' . $link->id));
            wp_redirect($track_url);
            exit;
        }
    }
});

SmartAffiliateOptimizer::get_instance();

// Note: Create empty style.css, admin.js, frontend.js files in plugin dir for full functionality
// Premium features would extend this with add-ons
?>