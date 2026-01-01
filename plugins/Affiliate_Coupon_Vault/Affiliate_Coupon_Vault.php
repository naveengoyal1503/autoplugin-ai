/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons, tracks clicks, and displays personalized discount deals to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('acv_options', 'acv_coupons');
        add_settings_section('acv_section', 'Coupons', null, 'acv');
        add_settings_field('coupons', 'Add Coupons', array($this, 'coupons_field'), 'acv', 'acv_section');
    }

    public function coupons_field() {
        $coupons = get_option('acv_coupons', array());
        echo '<textarea name="acv_coupons" rows="10" cols="50">' . esc_textarea(json_encode($coupons)) . '</textarea>';
        echo '<p>Add JSON array of coupons: [{"name":"Coupon1","code":"SAVE10","afflink":"https://aff.link","desc":"10% off"}]</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_options');
                do_settings_sections('acv');
                submit_button();
                ?>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[acv_coupons]</code> to display coupons on any page/post.</p>
            <?php if (get_option('acv_pro') !== 'activated') : ?>
            <div class="notice notice-info"><p><strong>Pro Version:</strong> Unlimited coupons, analytics, auto-expire. <a href="https://example.com/pro" target="_blank">Upgrade for $49</a></p></div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'acv_clicks';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            coupon varchar(50) NOT NULL,
            ip varchar(45) NOT NULL,
            time datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons = get_option('acv_coupons', array());
        if (empty($coupons)) {
            return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Set up now</a>.</p>';
        }
        shuffle($coupons); // Randomize for freshness
        $coupons = array_slice($coupons, 0, intval($atts['limit']));
        ob_start();
        echo '<div class="acv-vault">';
        foreach ($coupons as $coupon) {
            $clicks = $this->get_clicks($coupon['code']);
            echo '<div class="acv-coupon">';
            echo '<h3>' . esc_html($coupon['name']) . '</h3>';
            echo '<p>' . esc_html($coupon['desc']) . '</p>';
            echo '<p><strong>Code:</strong> ' . esc_html($coupon['code']) . '</p>';
            echo '<a href="' . esc_url($coupon['afflink']) . '" class="acv-btn" data-coupon="' . esc_attr($coupon['code']) . '">Grab Deal & Track</a>';
            echo '<small>Used ' . intval($clicks) . ' times</small>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    private function get_clicks($coupon_code) {
        global $wpdb;
        $table = $wpdb->prefix . 'acv_clicks';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE coupon = %s", $coupon_code));
    }
}

// AJAX for tracking
add_action('wp_ajax_acv_track', array(AffiliateCouponVault::get_instance(), 'track_click'));
add_action('wp_ajax_nopriv_acv_track', array(AffiliateCouponVault::get_instance(), 'track_click'));

function acv_ajax_track() {
    $coupon = sanitize_text_field($_POST['coupon']);
    $ip = $_SERVER['REMOTE_ADDR'];
    global $wpdb;
    $table = $wpdb->prefix . 'acv_clicks';
    $wpdb->insert($table, array('coupon' => $coupon, 'ip' => $ip));
    wp_die();
}

AffiliateCouponVault::get_instance();

// Pro nag (demo)
add_action('admin_notices', function() {
    if (get_option('acv_pro') !== 'activated' && current_user_can('manage_options')) {
        echo '<div class="notice notice-upgrade"><p>Affiliate Coupon Vault Pro: Unlock unlimited features for $49! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
});

// Minimal JS/CSS (inline for single file)
function acv_inline_assets() {
    if (is_admin()) return;
    ?>
    <style>
    .acv-vault { display: grid; gap: 20px; max-width: 600px; }
    .acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; }
    .acv-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
    .acv-btn:hover { background: #005a87; }
    </style>
    <script>jQuery(document).ready(function($){ $('.acv-btn').click(function(e){ e.preventDefault(); var coupon = $(this).data('coupon'); $.post(ajaxurl, {action:'acv_track', coupon:coupon}); window.open($(this).attr('href')); }); });</script>
    <?php
}
add_action('wp_head', 'acv_inline_assets');