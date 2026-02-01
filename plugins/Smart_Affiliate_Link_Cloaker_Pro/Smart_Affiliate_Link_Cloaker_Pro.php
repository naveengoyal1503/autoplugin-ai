/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak affiliate links, track clicks, and boost earnings. Free version with Pro upgrade.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
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

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('sac_link', array($this, 'cloak_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sac_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sac_track_click', array($this, 'track_click'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-tracker', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function cloak_shortcode($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'text' => 'Click Here',
            'id' => uniqid('sac_'),
        ), $atts);

        if (empty($atts['url'])) {
            return 'Invalid link';
        }

        $pretty_url = add_query_arg('sac', $atts['id'], home_url('/go/'));

        return '<a href="' . esc_url($pretty_url) . '" class="sac-link" data-url="' . esc_url($atts['url']) . '" data-id="' . esc_attr($atts['id']) . '">' . esc_html($atts['text']) . '</a>';
    }

    public function track_click() {
        check_ajax_referer('sac_nonce', 'nonce');
        $link_id = sanitize_text_field($_POST['id']);
        $url = esc_url_raw($_POST['url']);
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $log = get_option('sac_clicks', array());
        $log[] = array(
            'id' => $link_id,
            'url' => $url,
            'ip' => $ip,
            'ua' => substr($user_agent, 0, 100),
            'time' => current_time('mysql'),
        );
        update_option('sac_clicks', $log);

        wp_redirect($url);
        exit;
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Cloaker',
            'Affiliate Cloaker',
            'manage_options',
            'sac-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('sac_options', 'sac_clicks');
        if (isset($_POST['sac_clear_logs'])) {
            update_option('sac_clicks', array());
        }
    }

    public function admin_page() {
        $logs = get_option('sac_clicks', array());
        $is_pro = false; // Check for pro license in real version
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Cloaker Dashboard</h1>
            <?php if (!$is_pro): ?>
                <div class="notice notice-warning"><p><strong>Upgrade to Pro</strong> for advanced analytics, conversion tracking, A/B testing & more! <a href="https://example.com/pro">Get Pro Now ($49/year)</a></p></div>
            <?php endif; ?>
            <p><strong>Usage:</strong> Use shortcode <code>[sac_link url="https://affiliate.com/?ref=123" text="Buy Now"]</code></p>
            <h2>Click Logs (<?php echo count($logs); ?> total)</h2>
            <form method="post">
                <?php submit_button('Clear Logs', 'secondary', 'sac_clear_logs'); ?>
            </form>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>URL</th><th>IP</th><th>Time</th></tr></thead>
                <tbody>
                <?php foreach (array_slice(array_reverse($logs), 0, 50) as $log): ?>
                    <tr>
                        <td><?php echo esc_html($log['id']); ?></td>
                        <td><a href="<?php echo esc_url($log['url']); ?>" target="_blank"><?php echo esc_html($log['url']); ?></a></td>
                        <td><?php echo esc_html($log['ip']); ?></td>
                        <td><?php echo esc_html($log['time']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function activate() {
        if (!get_option('sac_clicks')) {
            add_option('sac_clicks', array());
        }
        // Add rewrite rule for pretty URLs
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Init tracker JS (inline for single file)
function sac_inline_tracker() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.sac-link').on('click', function(e) {
            e.preventDefault();
            var $this = $(this);
            var url = $this.data('url');
            var id = $this.data('id');
            $.post(sac_ajax.ajax_url, {
                action: 'sac_track_click',
                nonce: sac_ajax.nonce,
                id: id,
                url: url
            }, function() {
                window.location.href = url;
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sac_inline_tracker');

// Pretty URL rewrite
add_rewrite_rule('^go/?$', 'index.php?sac_go=1', 'top');
add_filter('query_vars', function($vars) {
    $vars[] = 'sac_go';
    return $vars;
});

SmartAffiliateCloaker::get_instance();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id !== 'settings_page_sac-pro') return;
    echo '<div class="notice notice-success"><p>Unlock Pro features: Detailed analytics, conversion tracking, link rotation, and more! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
});