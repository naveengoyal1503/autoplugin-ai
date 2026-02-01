/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak and track affiliate links with powerful analytics. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCloaker {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('sac_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        } else {
            add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        }
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', 'smart-affiliate-cloaker', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sac_save'])) {
            update_option('sac_api_key', sanitize_text_field($_POST['sac_api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('sac_api_key', '');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Cloaker Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Analytics API Key (Premium)</th>
                        <td><input type="text" name="sac_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'sac_save'); ?>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock advanced analytics, A/B testing, and more for $49/year. <a href="https://example.com/pro" target="_blank">Get Pro Now</a></p>
        </div>
        <?php
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'url' => '',
            'text' => 'Click Here'
        ), $atts);

        if (empty($atts['id']) || empty($atts['url'])) {
            return 'Missing ID or URL';
        }

        $clicks = get_option('sac_clicks_' . $atts['id'], 0);
        update_option('sac_clicks_' . $atts['id'], $clicks + 1);

        $link = add_query_arg(array(
            'sac' => $atts['id'],
            'ref' => 'free'
        ), home_url('/'));

        return '<a href="' . esc_url($link) . '" class="sac-link">' . esc_html($atts['text']) . '</a>';
    }

    public function admin_scripts($hook) {
        if ($hook !== 'settings_page') return;
        wp_enqueue_script('sac-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0');
    }

    public function frontend_scripts() {
        wp_enqueue_script('sac-track', plugin_dir_url(__FILE__) . 'track.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-track', 'sac_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Track clicks
add_action('init', function() {
    if (isset($_GET['sac'])) {
        $id = sanitize_text_field($_GET['sac']);
        $clicks = get_option('sac_clicks_' . $id, 0);
        update_option('sac_clicks_' . $id, $clicks + 1);

        // Premium: Advanced tracking
        $api_key = get_option('sac_api_key', '');
        if ($api_key && isset($_GET['ref']) && $_GET['ref'] === 'pro') {
            // Simulate API call
            error_log('Premium track: ' . $id);
        }

        $redirect_url = get_option('sac_url_' . $id, 'https://example.com');
        if (empty($redirect_url)) {
            $redirect_url = 'https://example.com';
        }
        wp_redirect($redirect_url);
        exit;
    }
});

// AJAX for stats (free basic)
add_action('wp_ajax_sac_stats', function() {
    if (!current_user_can('manage_options')) wp_die();
    $stats = array();
    for ($i = 1; $i <= 10; $i++) { // Free limit
        $stats[$i] = get_option('sac_clicks_' . $i, 0);
    }
    wp_send_json($stats);
});

SmartAffiliateCloaker::get_instance();

// Enqueue JS files (inline for single file)
function sac_inline_scripts() {
    if (is_admin()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Basic admin JS
            console.log('SAC Admin loaded');
        });
        </script>
        <?php
    } else {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.sac-link').on('click', function(e) {
                // Track click
                $.post(sac_ajax.ajaxurl, {action: 'sac_stats', id: $(this).data('id')});
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'sac_inline_scripts');
add_action('wp_footer', 'sac_inline_scripts');