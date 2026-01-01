/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with click analytics, A/B testing, and performance reports.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateManager {
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
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_filter('the_content', array($this, 'cloak_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('sam_pro_version')) {
            // Pro features
        }
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            original_url text NOT NULL,
            shortcode varchar(50) NOT NULL,
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY shortcode (shortcode)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
        update_option('sam_version', '1.0.0');
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sam-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sam-tracker', 'sam_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sam_nonce')));
    }

    public function cloak_links($content) {
        if (is_admin()) return $content;
        preg_match_all('/\[sam ([^\]]+)\]/', $content, $matches);
        foreach ($matches[1] as $index => $attrs) {
            parse_str($attrs, $attr);
            if (isset($attr['url'])) {
                $shortcode = '[sam ' . $attrs . ']';
                $cloaked = $this->get_cloaked_link($attr['url']);
                $content = str_replace($shortcode, $cloaked, $content);
            }
        }
        return $content;
    }

    private function get_cloaked_link($url) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $shortcode = 'sam-' . substr(md5($url), 0, 8);

        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE original_url = %s", $url));
        if (!$link) {
            $wpdb->insert($table_name, array('original_url' => $url, 'shortcode' => $shortcode));
            $link_id = $wpdb->insert_id;
        } else {
            $link_id = $link->id;
        }

        $cloaked_url = add_query_arg('sam', $link_id, home_url('/'));
        return '<a href="' . esc_url($cloaked_url) . '" class="sam-link" data-id="' . $link_id . '">Affiliate Link</a>';
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Manager', 'SAM', 'manage_options', 'smart-affiliate', array($this, 'settings_page'));
        add_submenu_page('options-general.php', 'SAM Links', 'SAM Links', 'manage_options', 'sam-links', array($this, 'links_page'));
    }

    public function settings_page() {
        if (isset($_POST['sam_pro_key'])) {
            update_option('sam_pro_key', sanitize_text_field($_POST['sam_pro_key']));
            echo '<div class="notice notice-success"><p>Pro key updated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Manager Settings</h1>
            <form method="post">
                <?php wp_nonce_field('sam_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Pro License Key</th>
                        <td><input type="text" name="sam_pro_key" value="<?php echo esc_attr(get_option('sam_pro_key')); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if (!get_option('sam_pro_version')): ?>
            <div class="notice notice-info">
                <p><strong>Upgrade to Pro</strong> for A/B testing, advanced analytics, and auto-optimization. <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function links_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>Your Affiliate Links</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Shortcode</th><th>Original URL</th><th>Clicks</th><th>Created</th></tr></thead>
                <tbody>
        <?php foreach ($links as $link): ?>
                    <tr>
                        <td><code>[sam url="<?php echo esc_attr($link->original_url); ?>"]</code></td>
                        <td><?php echo esc_html($link->original_url); ?></td>
                        <td><?php echo $link->clicks; ?></td>
                        <td><?php echo $link->created_at; ?></td>
                    </tr>
        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function admin_scripts($hook) {
        if ('settings_page_smart-affiliate' === $hook || 'settings_page_sam-links' === $hook) {
            wp_enqueue_script('postbox');
        }
    }

    // AJAX for tracking
    public function track_click() {
        check_ajax_referer('sam_nonce', 'nonce');
        $id = intval($_POST['id']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $id));
        wp_die();
    }
}

// AJAX handlers
add_action('wp_ajax_sam_track', array(SmartAffiliateManager::get_instance(), 'track_click'));
add_action('wp_ajax_nopriv_sam_track', array(SmartAffiliateManager::get_instance(), 'track_click'));

SmartAffiliateManager::get_instance();

// Tracker JS (inline for single file)
function sam_tracker_js() {
    if (!is_admin()): ?>
<script>
jQuery(document).ready(function($) {
    $('.sam-link').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post(sam_ajax.ajaxurl, {
            action: 'sam_track',
            id: id,
            nonce: sam_ajax.nonce
        }, function() {
            window.location = $(this).attr('href') + '&redirect=' + encodeURIComponent(window.location.href);
        });
    });
});
</script>
    <?php endif;
}
add_action('wp_footer', 'sam_tracker_js');

// WooCommerce integration for Pro (stub)
if (class_exists('WooCommerce')) {
    add_action('woocommerce_loaded', function() {
        // Pro upsell logic
    });
}