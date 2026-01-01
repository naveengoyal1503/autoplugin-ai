/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically fetches, displays, and tracks exclusive affiliate coupons from major networks, boosting conversions with personalized deals and performance analytics.
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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_shortcode('affiliate_coupons', array($this, 'coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_track_coupon_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_track_coupon_click', array($this, 'track_click'));
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function activate() {
        add_option('acv_api_keys', array());
        add_option('acv_coupons_limit', 5);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_api_keys', sanitize_text_field($_POST['api_keys']));
            update_option('acv_coupons_limit', intval($_POST['coupons_limit']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_keys = get_option('acv_api_keys', '');
        $limit = get_option('acv_coupons_limit', 5);
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Network API Keys (JSON)</th>
                        <td><textarea name="api_keys" rows="5" cols="50"><?php echo esc_textarea($api_keys); ?></textarea><br>
                        Example: {"amazon":"yourkey","cj":"yourkey"}</td>
                    </tr>
                    <tr>
                        <th>Coupons Limit (Free: max 5)</th>
                        <td><input type="number" name="coupons_limit" value="<?php echo $limit; ?>" max="20" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics dashboard, auto-fetch from 20+ networks. <a href="https://example.com/pro">Get Pro ($49/yr)</a></p>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('category' => 'all'), $atts);
        $limit = get_option('acv_coupons_limit', 5);
        if ($limit > 5 && !defined('ACV_PRO')) {
            $limit = 5;
        }

        // Simulated coupon data (in Pro, fetch from APIs)
        $coupons = $this->get_sample_coupons($limit, $atts['category']);

        ob_start();
        echo '<div class="acv-coupons">';
        foreach ($coupons as $coupon) {
            $click_url = add_query_arg('acv_track', base64_encode($coupon['id']), $coupon['url']);
            echo '<div class="acv-coupon">';
            echo '<h4>' . esc_html($coupon['title']) . '</h4>';
            echo '<p>' . esc_html($coupon['description']) . '</p>';
            echo '<p><strong>Code:</strong> ' . esc_html($coupon['code']) . ' | <strong>Ends:</strong> ' . esc_html($coupon['expires']) . '</p>';
            echo '<a href="' . esc_url($click_url) . '" class="button acv-btn" data-coupon-id="' . esc_attr($coupon['id']) . '">Get Deal (Track)</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    private function get_sample_coupons($limit, $category) {
        $samples = array(
            array('id' => 1, 'title' => '50% Off Hosting', 'description' => 'Bluehost exclusive deal', 'code' => 'AFF50', 'url' => 'https://bluehost.com', 'expires' => '2026-03-01'),
            array('id' => 2, 'title' => 'Amazon Prime 30 Days Free', 'description' => 'Prime membership trial', 'code' => 'PRIME30', 'url' => 'https://amazon.com/prime', 'expires' => '2026-02-15'),
            array('id' => 3, 'title' => '20% Off WordPress Themes', 'description' => 'ThemeForest discount', 'code' => 'WP20', 'url' => 'https://themeforest.net', 'expires' => '2026-01-31'),
        );
        return array_slice($samples, 0, $limit);
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        $coupon_id = sanitize_text_field($_POST['coupon_id']);
        // In Pro: Log to DB, integrate with analytics
        error_log('Coupon click tracked: ' . $coupon_id);
        wp_send_json_success('Tracked');
    }
}

// Enqueue JS inline for single file
add_action('wp_footer', function() {
    if (is_singular() || is_page()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.acv-btn').click(function(e) {
                var btn = $(this);
                $.post(acv_ajax.ajax_url, {
                    action: 'track_coupon_click',
                    coupon_id: btn.data('coupon-id'),
                    nonce: '<?php echo wp_create_nonce('acv_nonce'); ?>'
                }, function() {
                    console.log('Coupon clicked');
                });
            });
        });
        </script>
        <style>
        .acv-coupons { display: grid; gap: 20px; }
        .acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        .acv-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
        .acv-btn:hover { background: #005a87; }
        </style>
        <?php
    }
});

AffiliateCouponVault::get_instance();

// Pro check simulation
define('ACV_PRO', false); // Set to true in pro version

// Hook for pro upsell notice
add_action('admin_notices', function() {
    if (!defined('ACV_PRO') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons & analytics for $49/yr. <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }
});