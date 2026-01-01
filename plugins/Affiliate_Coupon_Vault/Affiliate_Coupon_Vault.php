/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons, tracks clicks, and displays personalized deals to boost conversions and commissions.
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
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_scripts($hook) {
        if (strpos($hook, 'affiliate-coupon-vault') !== false) {
            wp_enqueue_script('acv-admin', plugin_dir_url(__FILE__) . 'acv-admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('acv-admin', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
        }
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        $coupons = get_option('acv_coupons', array());
        $stats = get_option('acv_stats', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, detailed analytics, and auto-generation for $49/year!</p>
            <h2>Add New Coupon</h2>
            <form id="acv-form">
                <table class="form-table">
                    <tr>
                        <th>Brand</th>
                        <td><input type="text" name="brand" required></td>
                    </tr>
                    <tr>
                        <th>Coupon Code</th>
                        <td><input type="text" name="code" required></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="link" required style="width: 300px;"></td>
                    </tr>
                    <tr>
                        <th>Discount</th>
                        <td><input type="text" name="discount" placeholder="e.g., 20% OFF"></td>
                    </tr>
                    <tr>
                        <th>Expires</th>
                        <td><input type="date" name="expires"></td>
                    </tr>
                </table>
                <?php wp_nonce_field('acv_nonce'); ?>
                <p class="submit"><input type="submit" class="button-primary" value="Add Coupon"></p>
            </form>
            <h2>Coupons (<?php echo count($coupons); ?>/10 Free Limit)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Brand</th><th>Code</th><th>Link</th><th>Clicks</th></tr></thead>
                <tbody>
                <?php foreach ($coupons as $id => $coupon): $clicks = isset($stats[$id]) ? $stats[$id] : 0; ?>
                    <tr><td><?php echo esc_html($coupon['brand']); ?></td><td><?php echo esc_html($coupon['code']); ?></td><td><a href="<?php echo esc_url($coupon['link']); ?>" target="_blank">View</a></td><td><?php echo $clicks; ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#acv-form').on('submit', function(e) {
                e.preventDefault();
                $.post(acv_ajax.ajax_url, {
                    action: 'save_coupon',
                    nonce: acv_ajax.nonce,
                    data: $(this).serializeArray()
                }, function(res) {
                    if (res.success) location.reload();
                    else alert(res.data);
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_save_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        $coupons = get_option('acv_coupons', array());
        if (count($coupons) >= 10) {
            wp_send_json_error('Upgrade to Pro for unlimited coupons!');
        }

        $id = time();
        $coupons[$id] = array(
            'brand' => sanitize_text_field($_POST['brand']),
            'code' => sanitize_text_field($_POST['code']),
            'link' => esc_url_raw($_POST['link']),
            'discount' => sanitize_text_field($_POST['discount']),
            'expires' => sanitize_text_field($_POST['expires'])
        );

        update_option('acv_coupons', $coupons);
        wp_send_json_success();
    }

    public function track_click() {
        $id = intval($_POST['id']);
        $coupons = get_option('acv_coupons', array());
        if (!isset($coupons[$id])) wp_die();

        $stats = get_option('acv_stats', array());
        $stats[$id] = isset($stats[$id]) ? $stats[$id] + 1 : 1;
        update_option('acv_stats', $stats);

        $coupon = $coupons[$id];
        wp_redirect($coupon['link']);
        exit;
    }

    public function activate() {
        add_option('acv_coupons', array());
        add_option('acv_stats', array());
    }
}

// Shortcode [affiliate_coupons]
function acv_shortcode($atts) {
    $atts = shortcode_atts(array('limit' => 5), $atts);
    $coupons = get_option('acv_coupons', array());
    $stats = get_option('acv_stats', array());
    $output = '<div class="acv-vault">';
    $count = 0;
    foreach ($coupons as $id => $coupon) {
        if ($count >= $atts['limit']) break;
        $clicks = isset($stats[$id]) ? $stats[$id] : 0;
        $output .= '<div class="acv-coupon">
            <h4>' . esc_html($coupon['brand']) . '</h4>
            <p><strong>Code:</strong> ' . esc_html($coupon['code']) . '</p>';
        if (!empty($coupon['discount'])) $output .= '<p><em>' . esc_html($coupon['discount']) . '</em></p>';
        $output .= '<a href="' . admin_url('admin-ajax.php?action=acv_track_click&id=' . $id) . '" class="button" target="_blank">Get Deal (' . $clicks . ' clicks)</a>
        </div>';
        $count++;
    }
    $output .= '<p style="text-align:center;"><a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Manage Coupons</a> | <strong>Pro: Unlimited + Analytics</strong></p></div>';
    return $output;
}
add_shortcode('affiliate_coupons', 'acv_shortcode');

// Widget support
add_action('widgets_init', function() {
    register_widget('ACV_Widget');
});

class ACV_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct('acv_widget', 'Affiliate Coupon Vault');
    }

    public function widget($args, $instance) {
        echo do_shortcode('[affiliate_coupons limit="' . ($instance['limit'] ?? 3) . '"]');
    }

    public function form($instance) {
        $limit = $instance['limit'] ?? 3;
        echo '<p><label>Limit: <input type="number" name="' . $this->get_field_name('limit') . '" value="' . esc_attr($limit) . '" min="1" max="10"></label></p>';
    }

    public function update($new, $old) {
        $instance = array();
        $instance['limit'] = intval($new['limit']);
        return $instance;
    }
}

// CSS
add_action('wp_head', function() {
    echo '<style>
    .acv-vault { max-width: 400px; }
    .acv-coupon { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9f9f9; }
    .acv-coupon h4 { margin: 0 0 10px; color: #333; }
    .acv-coupon .button { background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; }
    </style>';
});

AffiliateCouponVault::get_instance();

// Pro upsell notice
add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'affiliate-coupon-vault') {
        echo '<div class="notice notice-info"><p><strong>Go Pro!</strong> Unlimited coupons, click analytics, auto-expiry, and more for $49/year. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
});