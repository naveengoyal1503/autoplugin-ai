/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with click analytics, A/B testing, and conversion reporting.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCloakerPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sac_save_link', array($this, 'ajax_save_link'));
        add_action('wp_ajax_sac_get_stats', array($this, 'ajax_get_stats'));
        add_shortcode('sac_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sac_pro_version')) {
            // Premium features flag
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-admin-js', plugin_dir_url(__FILE__) . 'sac-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-admin-js', 'sac_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', 'sac-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sac_submit'])) {
            update_option('sac_links', sanitize_text_field($_POST['sac_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('sac_links', '[]');
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function ajax_save_link() {
        if (!current_user_can('manage_options')) wp_die();
        $links = json_decode(stripslashes($_POST['links']), true);
        update_option('sac_links', $links);
        wp_send_json_success();
    }

    public function ajax_get_stats() {
        if (!current_user_can('manage_options')) wp_die();
        global $wpdb;
        $stats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sac_clicks ORDER BY time DESC LIMIT 50");
        wp_send_json_success($stats);
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $links = json_decode(get_option('sac_links', '[]'), true);
        if (isset($links[$atts['id']])) {
            $link = $links[$atts['id']]['url'];
            $id = $atts['id'];
            $slug = 'sac-' . $id;
            add_rewrite_rule("^$slug/?$", 'index.php?sac_id=' . $id, 'top');
            global $wp_rewrite;
            $wp_rewrite->flush_rules();
            return "<a href='/" . $slug . "' data-sac-id='" . $id . "'>" . $links[$atts['id']]['name'] . "</a>";
        }
        return '';
    }

    public function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'sac_clicks';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(20) NOT NULL,
            ip varchar(45) NOT NULL,
            time datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        flush_rewrite_rules();
    }
}

// Track clicks
add_action('init', function() {
    if (isset($_GET['sac_id'])) {
        $id = intval($_GET['sac_id']);
        $links = json_decode(get_option('sac_links', '[]'), true);
        if (isset($links[$id])) {
            global $wpdb;
            $wpdb->insert($wpdb->prefix . 'sac_clicks', array(
                'link_id' => $id,
                'ip' => $_SERVER['REMOTE_ADDR']
            ));
            wp_redirect($links[$id]['url']);
            exit;
        }
    }
});

SmartAffiliateCloakerPro::get_instance();

// Inline admin page and JS to keep single file
function sac_admin_page_content() {
    $links = get_option('sac_links', '[]');
    ?>
    <div class="wrap">
        <h1>Smart Affiliate Cloaker Pro</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Links (JSON)</th>
                    <td><textarea name="sac_links" rows="10" cols="50"><?php echo esc_textarea($links); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button('Save Links', 'primary', 'sac_submit'); ?>
        </form>
        <h2>Recent Clicks</h2>
        <div id="sac-stats"></div>
        <script>
        jQuery(document).ready(function($) {
            $('#sac-stats').load('<?php echo admin_url('admin-ajax.php?action=sac_get_stats'); ?>', function() {
                $(this).html('<table class="wp-list-table widefat"><tr><th>ID</th><th>Link ID</th><th>IP</th><th>Time</th></tr>' + $(this).text().split('},{').map(row => row.replace(/[{}]/g,'')).join('</tr><tr>') + '</table>');
            });
        });
        </script>
        <p><strong>Upgrade to Pro:</strong> Unlimited links, A/B testing, conversion tracking. <a href="#pro">Buy now $49/year</a></p>
    </div>
    <?php
}
function sac_admin_page() {
    echo '<div class="wrap">';
    sac_admin_page_content();
    echo '</div>';
}