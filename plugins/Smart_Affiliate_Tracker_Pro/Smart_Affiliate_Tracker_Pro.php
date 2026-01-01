/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Tracker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Tracker Pro
 * Plugin URI: https://example.com/smart-affiliate-tracker
 * Description: Automatically tracks, cloaks, and optimizes affiliate links with analytics and A/B testing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateTrackerPro {
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
        add_action('wp_ajax_sat_track_click', array($this, 'track_click'));
        add_shortcode('sat_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Load text domain
        load_plugin_textdomain('smart-affiliate-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sat-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sat-frontend', 'sat_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sat_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Tracker', 'Affiliate Tracker', 'manage_options', 'sat-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sat_save'])) {
            update_option('sat_api_key', sanitize_text_field($_POST['api_key']));
            update_option('sat_ab_testing', isset($_POST['ab_testing']) ? '1' : '0');
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('sat_api_key', '');
        $ab_testing = get_option('sat_ab_testing', '0');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Tracker Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Analytics API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>A/B Testing</th>
                        <td><input type="checkbox" name="ab_testing" <?php checked($ab_testing, '1'); ?> /></td>
                    </tr>
                </table>
                <p><input type="submit" name="sat_save" class="button-primary" value="Save Settings" /></p>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock unlimited links, advanced reports, and priority support for $49/year. <a href="https://example.com/pro" target="_blank">Buy Now</a></p>
        </div>
        <?php
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'text' => 'Click Here',
            'id' => 'default'
        ), $atts);

        $cloaked_url = add_query_arg(array('sat' => $atts['id'], 'ref' => 'shortcode'), $atts['url']);
        return '<a href="' . esc_url($cloaked_url) . '" class="sat-link" data-id="' . esc_attr($atts['id']) . '">' . esc_html($atts['text']) . '</a>';
    }

    public function track_click() {
        check_ajax_referer('sat_nonce', 'nonce');
        $link_id = sanitize_text_field($_POST['link_id']);
        $ref = sanitize_text_field($_POST['ref']);
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        // Simulate tracking (in pro: send to GA or custom API)
        error_log("SAT Click: ID=$link_id, Ref=$ref, IP=$user_ip");

        if (get_option('sat_ab_testing') === '1') {
            // Simple A/B: 50/50 redirect to variant A or B
            $variant = rand(0,1) ? 'a' : 'b';
            $redirect = add_query_arg('variant', $variant, remove_query_arg(array('sat', 'ref'), wp_get_referer()));
            wp_send_json(array('redirect' => $redirect));
        }

        wp_send_json(array('redirect' => remove_query_arg(array('sat', 'ref'), wp_get_referer())));
    }

    public function activate() {
        // Create default options
        add_option('sat_api_key', '');
        add_option('sat_ab_testing', '0');
    }
}

// Auto cloak affiliate links (filter content)
function sat_cloak_links($content) {
    if (is_admin()) return $content;

    // Regex to find affiliate links (customize patterns)
    preg_match_all('/<a[^>]+href=["\']([^"\']*aff=|[^"\']*ref=|[^"\']*tag=)/i', $content, $matches);

    foreach ($matches[1] as $url) {
        $cloaked = add_query_arg('sat', wp_generate_uuid4(), $url);
        $content = str_replace($url, $cloaked, $content);
    }

    return $content;
}
add_filter('the_content', 'sat_cloak_links');

// Frontend JS (inline for single file)
function sat_inline_js() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.sat-link').on('click', function(e) {
            e.preventDefault();
            var $this = $(this);
            $.post(sat_ajax.ajax_url, {
                action: 'sat_track_click',
                nonce: sat_ajax.nonce,
                link_id: $this.data('id'),
                ref: 'link'
            }, function(response) {
                window.location = response.redirect;
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sat_inline_js');

SmartAffiliateTrackerPro::get_instance();

// Free limit: 10 links
if (wp_count_posts('sat_link')->publish > 10) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-warning"><p>Upgrade to Pro for unlimited affiliate links!</p></div>';
    });
}
