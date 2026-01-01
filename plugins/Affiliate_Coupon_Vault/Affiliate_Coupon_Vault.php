/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons, tracks clicks, and boosts conversions for bloggers and e-commerce sites.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Coupon Code: SAVE20\nAffiliate Link: https://example.com/aff\nDescription: 20% off on all products");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Coupons (one per line: Code|Link|Description):</label></p>
                <textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Settings"></p>
            </form>
            <p>Use shortcode: <code>[affiliate_coupon id="1"]</code> or <code>[affiliate_coupon]</code> for random.</p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics dashboard, A/B testing ($49/year).</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'random'), $atts);
        $coupons_str = get_option('acv_coupons', '');
        if (empty($coupons_str)) return '<p>No coupons configured. Go to Settings > Coupon Vault.</p>';

        $coupons = explode('\n', $coupons_str);
        $coupon = null;
        if ($atts['id'] !== 'random') {
            $coupon = $coupons[(int)$atts['id'] - 1] ?? $coupons;
        } else {
            $coupon = $coupons[array_rand($coupons)];
        }

        list($code, $link, $desc) = explode('|', $coupon . '|||', 3);
        $id = $atts['id'] === 'random' ? uniqid() : $atts['id'];

        ob_start();
        ?>
        <div class="acv-coupon" data-id="<?php echo esc_attr($id); ?>">
            <h3><?php echo esc_html($code); ?></h3>
            <p><?php echo esc_html($desc); ?></p>
            <a href="#" class="acv-button button" data-link="<?php echo esc_url($link); ?>">Get Deal (Affiliate)</a>
            <small>Tracked clicks: <span class="acv-clicks">0</span></small>
        </div>
        <style>
        .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; }
        .acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
        .acv-button:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        $id = sanitize_text_field($_POST['id']);
        $clicks = get_option('acv_clicks_' . $id, 0) + 1;
        update_option('acv_clicks_' . $id, $clicks);
        wp_redirect(sanitize_url($_POST['link']));
        exit;
    }

    public function activate() {
        if (!wp_next_scheduled('acv_cron')) {
            wp_schedule_event(time(), 'daily', 'acv_cron');
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook('acv_cron');
    }
}

AffiliateCouponVault::get_instance();

add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-button').click(function(e) {
            e.preventDefault();
            var $this = $(this);
            var link = $this.data('link');
            var id = $this.closest('.acv-coupon').data('id');
            $.post(acv_ajax.ajax_url, {
                action: 'acv_track_click',
                id: id,
                link: link,
                nonce: '<?php echo wp_create_nonce('acv_nonce'); ?>'
            }, function() {
                window.location.href = link;
            });
        });
    });
    </script>
    <?php
});

// Pro teaser
add_action('admin_notices', function() {
    if (!get_option('acv_pro')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock analytics, unlimited coupons & more! <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p></div>';
    }
});