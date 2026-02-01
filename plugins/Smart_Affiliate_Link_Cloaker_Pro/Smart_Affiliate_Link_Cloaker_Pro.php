/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak and track affiliate links with analytics and A/B testing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCloaker {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('sac_link', array($this, 'shortcode_link'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sac_pro') !== 'yes') {
            add_action('wp_footer', array($this, 'free_footer_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'text' => 'Click Here',
            'id' => ''
        ), $atts);

        if (empty($atts['url'])) return '';

        $id = sanitize_text_field($atts['id']);
        $track_id = !empty($id) ? $id : uniqid('sac_');

        $cloaked_url = add_query_arg(array(
            'sac' => $track_id,
            'ref' => $_SERVER['HTTP_REFERER'] ?? ''
        ), home_url('/go/?u=' . urlencode($atts['url'])));

        return '<a href="' . esc_url($cloaked_url) . '" class="sac-link" data-track="' . esc_attr($track_id) . '" target="_blank" rel="nofollow">' . esc_html($atts['text']) . '</a>';
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Cloaker',
            'SAC Cloaker',
            'manage_options',
            'sac-cloaker',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['sac_pro_key'])) {
            update_option('sac_pro', sanitize_text_field($_POST['sac_pro_key']) === 'pro-activated' ? 'yes' : 'no');
        }
        echo '<div class="wrap"><h1>Smart Affiliate Cloaker Settings</h1>';
        echo '<form method="post"><p>Enter Pro Key: <input type="text" name="sac_pro_key" /></p><p><input type="submit" value="Activate Pro" class="button-primary" /></p></form>';
        if (get_option('sac_pro') === 'yes') {
            echo '<p><strong>Pro Activated!</strong> Analytics and A/B testing unlocked.</p>';
        }
        echo '<h2>Usage</h2><p>[sac_link url="https://affiliate.com/product" text="Buy Now" id="link1"]</p>';
        echo '<h2>Analytics</h2><p>Total clicks: ' . get_option('sac_clicks', 0) . '</p>';
        echo '</div>';
    }

    public function free_footer_notice() {
        echo '<div style="position:fixed;bottom:10px;right:10px;background:#0073aa;color:white;padding:10px;"><strong>Upgrade to Pro</strong> for analytics! <a href="' . admin_url('options-general.php?page=sac-cloaker') . '" style="color:#fff;">Upgrade</a></div>';
    }

    public function activate() {
        add_rewrite_rule('go/?([a-z0-9_-]+)?$', 'index.php?sac_redirect=$matches[1]', 'top');
        flush_rewrite_rules();
    }
}

// Track clicks
add_action('init', function() {
    if (isset($_GET['sac'])) {
        $clicks = get_option('sac_clicks', 0) + 1;
        update_option('sac_clicks', $clicks);
        if (isset($_GET['u'])) {
            wp_redirect(esc_url_raw(urldecode($_GET['u'])));
            exit;
        }
    }
});

// JS for tracking
add_action('wp_footer', function() {
    ?><script>document.addEventListener('click', function(e){if(e.target.classList.contains('sac-link')){fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sac_track&track='+e.target.dataset.track);}});</script><?php
});

SmartAffiliateCloaker::get_instance();

// Pro feature stub
add_action('admin_notices', function() {
    if (get_option('sac_pro') !== 'yes') {
        echo '<div class="notice notice-info"><p>Unlock <strong>analytics & A/B testing</strong> with Pro ($9/mo)!</p></div>';
    }
});