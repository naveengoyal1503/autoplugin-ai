/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking and analytics to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
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
        add_action('wp_ajax_ecp_save_coupon', array($this, 'ajax_save_coupon'));
        add_shortcode('ecp_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ecp-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons Pro', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['ecp_coupon_data'])) {
            $this->save_coupon($_POST['ecp_coupon_data']);
        }
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function ajax_save_coupon() {
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $data = $_POST['data'];
        $id = $this->save_coupon($data);
        wp_send_json_success(array('id' => $id));
    }

    private function save_coupon($data) {
        $coupons = get_option('ecp_coupons', array());
        $id = uniqid();
        $data['id'] = $id;
        $data['created'] = current_time('mysql');
        $data['uses'] = 0;
        $data['expires'] = date('Y-m-d H:i:s', strtotime($data['expires']));
        $coupons[$id] = $data;
        update_option('ecp_coupons', $coupons);
        return $id;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        if (empty($atts['id'])) {
            return '';
        }
        $coupons = get_option('ecp_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return '<p>Coupon not found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        if (strtotime($coupon['expires']) < current_time('timestamp')) {
            return '<div class="ecp-expired">Coupon expired.</div>';
        }
        ob_start();
        ?>
        <div class="ecp-coupon" data-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
            <p>Discount: <?php echo esc_html($coupon['discount']); ?></p>
            <a href="<?php echo esc_url($coupon['affiliate_link']); ?>" target="_blank" class="ecp-use-btn">Use Coupon</a>
            <span class="ecp-uses"><?php echo intval($coupon['uses']); ?> uses</span>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('ecp_pro_activated', false);
    }
}

ExclusiveCouponsPro::get_instance();

// Pro Upsell Notice
function ecp_pro_notice() {
    if (!get_option('ecp_pro_activated') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Exclusive Coupons Pro</strong>: Unlimited coupons, analytics & integrations for $49/year. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'ecp_pro_notice');

// Frontend Assets (inline for single file)
function ecp_inline_assets() {
    if (is_admin()) return;
    ?>
    <style>
    .ecp-coupon { border: 2px solid #007cba; padding: 20px; border-radius: 10px; background: #f9f9f9; text-align: center; max-width: 400px; margin: 20px auto; }
    .ecp-coupon h3 { color: #007cba; margin: 0 0 10px; }
    .ecp-use-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .ecp-use-btn:hover { background: #005a87; }
    .ecp-expired { background: #ffebee; color: #d32f2f; padding: 20px; border-radius: 5px; }
    .ecp-uses { display: block; margin-top: 10px; font-size: 0.9em; color: #666; }
    </style>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.ecp-coupon .ecp-use-btn').on('click', function(e) {
            e.preventDefault();
            var $coupon = $(this).closest('.ecp-coupon');
            var id = $coupon.data('id');
            $.post(ajaxurl || '/wp-admin/admin-ajax.php', {action: 'ecp_track_use', id: id}, function() {
                $coupon.find('.ecp-uses').text('Used! Thanks!');
            });
        });
    });
    </script>
    <?php
}
add_action('wp_head', 'ecp_inline_assets');

// Track uses
add_action('wp_ajax_ecp_track_use', 'ecp_track_use');
add_action('wp_ajax_nopriv_ecp_track_use', 'ecp_track_use');
function ecp_track_use() {
    $id = sanitize_text_field($_POST['id']);
    $coupons = get_option('ecp_coupons', array());
    if (isset($coupons[$id])) {
        $coupons[$id]['uses']++;
        update_option('ecp_coupons', $coupons);
    }
    wp_die();
}

// Admin page template (inline)
function ecp_admin_template() {
    $coupons = get_option('ecp_coupons', array());
    echo '<div class="wrap"><h1>Exclusive Coupons Pro</h1><form method="post"><table class="form-table">';
    echo '<tr><th>Title</th><td><input type="text" name="ecp_coupon_data[title]" /></td></tr>';
    echo '<tr><th>Code</th><td><input type="text" name="ecp_coupon_data[code]" /></td></tr>';
    echo '<tr><th>Affiliate Link</th><td><input type="url" name="ecp_coupon_data[affiliate_link]" style="width:100%;" /></td></tr>';
    echo '<tr><th>Discount</th><td><input type="text" name="ecp_coupon_data[discount]" placeholder="50% off" /></td></tr>';
    echo '<tr><th>Expires</th><td><input type="datetime-local" name="ecp_coupon_data[expires]" /></td></tr>';
    echo '</table><p><input type="submit" class="button-primary" value="Add Coupon" /></p></form>';
    echo '<h2>Your Coupons</h2><ul>';
    foreach ($coupons as $id => $c) {
        echo '<li>' . esc_html($c['title']) . ' - Code: ' . esc_html($c['code']) . ' <small>(' . $c['uses'] . ' uses, expires ' . $c['expires'] . ')</small> <code>[ecp_coupon id="' . $id . '"] </code></li>';
    }
    echo '</ul><p><em>Pro: Advanced analytics, bulk import, API. <a href="https://example.com/pro">Upgrade</a></em></p></div>';
}
// Note: In full plugin, extract to admin-page.php; here inline via hook if needed.