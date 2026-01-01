/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons, tracks clicks, and boosts conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
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
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->create_table();
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_enqueue($hook) {
        if (strpos($hook, 'acv') !== false) {
            wp_enqueue_script('jquery');
        }
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('acv_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="80" placeholder='[{"name":"10% Off","code":"SAVE10","affiliate_url":"https://affiliate.link","description":"Exclusive deal for our readers","uses":0,"max_uses":1000,"expires":"2026-12-31"}]'><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">JSON array of coupons: name, code, affiliate_url, description, uses, max_uses, expires (YYYY-MM-DD).</p>
                <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics dashboard, auto-generation ($49/year).</p>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'acv_clicks';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            coupon_name varchar(100) NOT NULL,
            ip varchar(45) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons_json = get_option('acv_coupons', '[]');
        $coupons = json_decode($coupons_json, true);
        if (!isset($coupons[$atts['id']])) return 'Coupon not found.';
        $coupon = $coupons[$atts['id']];
        if ($coupon['uses'] >= $coupon['max_uses']) return 'Coupon expired.';
        $date = date('Y-m-d');
        if ($date > $coupon['expires']) return 'Coupon expired.';
        $id = $atts['id'];
        ob_start();
        ?>
        <div class="acv-coupon" data-id="<?php echo $id; ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['code']); ?></p>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <button class="acv-btn">Get Deal & Track</button>
            <a href="<?php echo esc_url($coupon['affiliate_url']); ?>" class="acv-link" style="display:none;" target="_blank">Shop Now</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $coupon_id = intval($_POST['id']);
        $coupons_json = get_option('acv_coupons', '[]');
        $coupons = json_decode($coupons_json, true);
        if (!isset($coupons[$coupon_id])) wp_die('Invalid coupon');
        global $wpdb;
        $table = $wpdb->prefix . 'acv_clicks';
        $wpdb->insert($table, array(
            'coupon_name' => $coupons[$coupon_id]['name'],
            'ip' => $_SERVER['REMOTE_ADDR']
        ));
        // Update uses
        $coupons[$coupon_id]['uses']++;
        update_option('acv_coupons', json_encode($coupons));
        wp_send_json_success();
    }

    public function activate() {
        $this->create_table();
    }

    public function deactivate() {}
}

AffiliateCouponVault::get_instance();

// Frontend JS (inline for single file)
function acv_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-btn').click(function() {
            var $container = $(this).closest('.acv-coupon');
            var id = $container.data('id');
            $.post(acv_ajax.ajax_url, {
                action: 'acv_track_click',
                id: id,
                nonce: acv_ajax.nonce
            }, function() {
                $container.find('.acv-link').show();
                $container.find('.acv-btn').text('Tracked! Redirecting...').prop('disabled', true);
                window.open($container.find('.acv-link').attr('href'), '_blank');
            });
        });
    });
    </script>
    <style>
    .acv-coupon { border: 1px solid #ddd; padding: 20px; margin: 10px 0; background: #f9f9f9; }
    .acv-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; }
    .acv-btn:hover { background: #005a87; }
    .acv-btn:disabled { background: #ccc; }
    </style>
    <?php
}
add_action('wp_footer', 'acv_inline_js');

// Pro upsell notice
add_action('admin_notices', function() {
    if (get_option('acv_coupons') && json_decode(get_option('acv_coupons'), true) && count(json_decode(get_option('acv_coupons'), true)) > 5) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons and analytics for $49/year. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
});