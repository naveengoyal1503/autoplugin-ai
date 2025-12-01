/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Optimizer.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Optimizer
 * Description: Aggregate and display affiliate coupons dynamically to maximize conversion.
 * Version: 1.0
 * Author: Generated Plugin
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AffiliateCouponOptimizer {
    private $coupons_option = 'aco_coupons_data';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_coupons', array($this, 'render_coupons_shortcode'));
        add_action('wp_ajax_aco_refresh_coupons', array($this, 'ajax_refresh_coupons'));
        add_action('wp_ajax_nopriv_aco_refresh_coupons', array($this, 'ajax_refresh_coupons'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Coupon Optimizer', 'Coupon Optimizer', 'manage_options', 'aco_settings', array($this, 'settings_page'), 'dashicons-tickets', 61);
    }

    public function settings_init() {
        register_setting('aco_settings_group', $this->coupons_option, array($this, 'sanitize_coupons'));

        add_settings_section('aco_section', 'Manage Coupons', null, 'aco_settings');

        add_settings_field(
            'aco_coupons',
            'Coupons JSON',
            array($this, 'coupons_field_render'),
            'aco_settings',
            'aco_section'
        );
    }

    public function sanitize_coupons($input) {
        $data = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            // Filter coupons: require keys 'title', 'code', 'link', 'expires'
            $filtered = array_filter($data, function($c) {
                return isset($c['title'], $c['code'], $c['link'], $c['expires']);
            });
            return json_encode(array_values($filtered));
        }
        return get_option($this->coupons_option);
    }

    public function coupons_field_render() {
        $value = get_option($this->coupons_option, '[]');
        echo '<textarea name="' . esc_attr($this->coupons_option) . '" rows="10" cols="50" style="width:100%;">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Enter an array of coupons in JSON format. Each coupon needs title, code, link & expires (YYYY-MM-DD).</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Optimizer Settings</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('aco_settings_group');
                do_settings_sections('aco_settings');
                submit_button();
                ?>
            </form>
            <button id="aco-refresh-btn" class="button button-secondary">Refresh Expired Coupons</button>
            <p id="aco-refresh-msg" style="margin-top:10px;"></p>
        </div>
        <script>
        document.getElementById('aco-refresh-btn').addEventListener('click', function() {
            var btn = this;
            btn.disabled = true;
            btn.textContent = 'Refreshing...';
            fetch('<?php echo admin_url("admin-ajax.php"); ?>?action=aco_refresh_coupons', {
                method: 'POST',
                credentials: 'same-origin'
            }).then(response => response.json()).then(data => {
                btn.disabled = false;
                btn.textContent = 'Refresh Expired Coupons';
                var msgEl = document.getElementById('aco-refresh-msg');
                if (data.success) {
                    msgEl.textContent = 'Coupons refreshed, expired coupons removed.';
                    location.reload();
                } else {
                    msgEl.textContent = 'Failed to refresh coupons.';
                }
            });
        });
        </script>
        <?php
    }

    // Remove expired coupons
    public function ajax_refresh_coupons() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }

        $coupons_raw = get_option($this->coupons_option, '[]');
        $coupons = json_decode($coupons_raw, true);
        if (!is_array($coupons)) {
            wp_send_json_error();
        }

        $today = date('Y-m-d');
        $filtered = array_filter($coupons, function($c) use ($today) {
            return $c['expires'] >= $today;
        });

        update_option($this->coupons_option, json_encode(array_values($filtered)));
        wp_send_json_success();
    }

    public function render_coupons_shortcode() {
        $coupons_raw = get_option($this->coupons_option, '[]');
        $coupons = json_decode($coupons_raw, true);
        if (!is_array($coupons) || empty($coupons)) {
            return '<p>No coupons available at this time.</p>';
        }

        $today = date('Y-m-d');
        $valid_coupons = array_filter($coupons, function($c) use ($today) {
            return $c['expires'] >= $today;
        });
        if (empty($valid_coupons)) {
            return '<p>No valid coupons available currently.</p>';
        }

        // Sort coupons by expiry ascending
        usort($valid_coupons, function($a, $b) {
            return strcmp($a['expires'], $b['expires']);
        });

        ob_start();
        echo '<div class="aco-coupons-list" style="border:1px solid #ccc;padding:10px;border-radius:5px;">
            <h3>Current Affiliate Coupons</h3><ul style="list-style:none;padding-left:0;">';
        foreach ($valid_coupons as $coupon) {
            $title = esc_html($coupon['title']);
            $code = esc_html($coupon['code']);
            $link = esc_url($coupon['link']);
            $expires = esc_html($coupon['expires']);

            echo "<li style='margin-bottom:10px; padding:8px; border-bottom:1px solid #eee;'>";
            echo "<strong>$title</strong> - <code style='background:#eee;padding:2px 4px;border-radius:3px;'>$code</code><br>";
            echo "Expires on: $expires <br>";
            echo "<a href='$link' target='_blank' rel='nofollow noopener' style='color:#0073aa;'>Use Coupon</a>";
            echo '</li>';
        }
        echo '</ul></div>';
        return ob_get_clean();
    }
}

new AffiliateCouponOptimizer();
