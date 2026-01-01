/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupon codes, tracks clicks and conversions, and displays personalized deals to boost WordPress site revenue.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('acv_db_version') !== '1.0') {
            $this->create_tables();
        }
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_coupons = $wpdb->prefix . 'acv_coupons';
        $sql = "CREATE TABLE $table_coupons (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            affiliate_url varchar(500) NOT NULL,
            description text,
            discount varchar(50),
            expiry date,
            clicks int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $table_clicks = $wpdb->prefix . 'acv_clicks';
        $sql = "CREATE TABLE $table_clicks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            coupon_id mediumint(9) NOT NULL,
            ip varchar(45),
            user_agent text,
            clicked datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        dbDelta($sql);
        update_option('acv_db_version', '1.0');
    }

    public function activate() {
        $this->create_tables();
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['acv_add_coupon'])) {
            global $wpdb;
            $code = sanitize_text_field($_POST['code']);
            $url = esc_url_raw($_POST['affiliate_url']);
            $desc = sanitize_textarea_field($_POST['description']);
            $discount = sanitize_text_field($_POST['discount']);
            $expiry = sanitize_text_field($_POST['expiry']);
            $wpdb->insert($wpdb->prefix . 'acv_coupons', array(
                'code' => $code,
                'affiliate_url' => $url,
                'description' => $desc,
                'discount' => $discount,
                'expiry' => $expiry
            ));
            echo '<div class="notice notice-success"><p>Coupon added!</p></div>';
        }
        echo '<div class="wrap"><h1>Affiliate Coupon Vault</h1><form method="post">';
        echo '<table class="form-table"><tr><th>Code</th><td><input type="text" name="code" required /></td></tr>';
        echo '<tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width:400px;" required /></td></tr>';
        echo '<tr><th>Description</th><td><textarea name="description"></textarea></td></tr>';
        echo '<tr><th>Discount</th><td><input type="text" name="discount" placeholder="e.g. 20% OFF" /></td></tr>';
        echo '<tr><th>Expiry</th><td><input type="date" name="expiry" /></td></tr>';
        echo '</table><p><input type="submit" name="acv_add_coupon" class="button-primary" value="Add Coupon" /></p></form>';

        // List coupons
        global $wpdb;
        $coupons = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}acv_coupons ORDER BY created DESC");
        echo '<h2>Coupons</h2><table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Code</th><th>URL</th><th>Clicks</th><th>Created</th></tr></thead><tbody>';
        foreach ($coupons as $coupon) {
            echo '<tr><td>' . $coupon->id . '</td><td>' . esc_html($coupon->code) . '</td><td>' . esc_html($coupon->affiliate_url) . '</td><td>' . $coupon->clicks . '</td><td>' . $coupon->created . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        global $wpdb;
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}acv_coupons WHERE id = %d", $atts['id']));
        if (!$coupon) return 'Coupon not found.';

        $expiry = $coupon->expiry ? 'Expires: ' . date('M j, Y', strtotime($coupon->expiry)) : '';
        ob_start();
        echo '<div class="acv-coupon" data-id="' . $coupon->id . '">';
        echo '<h3>' . esc_html($coupon->code) . ' - ' . esc_html($coupon->discount) . '</h3>';
        echo '<p>' . esc_html($coupon->description) . '</p>';
        echo '<p>' . $expiry . ' | Clicks: <span class="acv-clicks">' . $coupon->clicks . '</span></p>';
        echo '<a href="#" class="button acv-redeem">Redeem Now</a>';
        echo '</div>';
        return ob_get_clean();
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        global $wpdb;
        $coupon_id = intval($_POST['coupon_id']);
        $wpdb->insert($wpdb->prefix . 'acv_clicks', array(
            'coupon_id' => $coupon_id,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}acv_coupons SET clicks = clicks + 1 WHERE id = %d", $coupon_id));
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT affiliate_url FROM {$wpdb->prefix}acv_coupons WHERE id = %d", $coupon_id));
        wp_redirect($coupon->affiliate_url);
        exit;
    }
}

AffiliateCouponVault::get_instance();

// Inline JS for tracking
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-redeem').click(function(e) {
            e.preventDefault();
            var couponId = $(this).closest('.acv-coupon').data('id');
            $.post(acv_ajax.ajaxurl, {
                action: 'acv_track_click',
                coupon_id: couponId,
                nonce: '<?php echo wp_create_nonce('acv_nonce'); ?>'
            }, function() {
                window.location.href = $(this).closest('.acv-coupon').find('a').attr('href');
            });
        });
    });
    </script>
    <style>
    .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }
    .acv-coupon h3 { color: #0073aa; margin-top: 0; }
    .acv-redeem { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    .acv-redeem:hover { background: #005a87; }
    </style>
    <?php
});

// Premium notice
add_action('admin_notices', function() {
    if (!get_option('acv_premium_activated')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock premium features like unlimited coupons, analytics dashboard, and auto-generation for <a href="https://example.com/premium">$49/year</a>!</p></div>';
    }
});