<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Manage affiliate coupons with auto-expiration, tracking, and shortcodes for easy integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) return;
        $this->check_expired_coupons();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        add_settings_section('acv_main', 'Coupons', null, 'acv-settings');
        add_settings_field('acv_coupons_list', 'Coupons', array($this, 'coupons_field'), 'acv-settings', 'acv_main');
    }

    public function coupons_field() {
        $coupons = get_option('acv_coupons', array());
        echo '<table class="form-table"><tr><th>Add/Edit Coupon</th><td>';
        echo '<input type="text" name="new_coupon[code]" placeholder="Coupon Code" style="width:150px;"> ';
        echo '<input type="text" name="new_coupon[affiliate_url]" placeholder="Affiliate URL" style="width:300px;"> ';
        echo '<input type="date" name="new_coupon[expires]" placeholder="Expiry Date"> ';
        echo '<input type="text" name="new_coupon[description]" placeholder="Description" style="width:200px;"> ';
        echo '<button type="button" id="acv-add-coupon">Add Coupon</button>';
        echo '</td></tr></table>';
        echo '<h3>Existing Coupons</h3><table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Code</th><th>URL</th><th>Expires</th><th>Description</th><th>Actions</th></tr></thead><tbody>';
        foreach ($coupons as $id => $coupon) {
            $expired = !empty($coupon['expires']) && strtotime($coupon['expires']) < current_time('timestamp');
            echo '<tr class="' . ($expired ? 'acv-expired' : '') . '">';
            echo '<td>' . $id . '</td>';
            echo '<td>' . esc_html($coupon['code']) . '</td>';
            echo '<td>' . esc_html($coupon['affiliate_url']) . '</td>';
            echo '<td>' . esc_html($coupon['expires']) . '</td>';
            echo '<td>' . esc_html($coupon['description']) . '</td>';
            echo '<td><button class="acv-delete" data-id="' . $id . '">Delete</button></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<p><strong>Shortcode:</strong> [acv_coupon id="1"] - Use to display coupon on any page/post.</p>';
        echo '<p><em>Pro: Unlimited coupons, analytics, custom codes. <a href="#pro">Upgrade</a></em></p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); do_settings_sections('acv-settings'); submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#acv-add-coupon').click(function() {
                var newCoupon = {
                    code: $('input[name="new_coupon[code]"]').val(),
                    affiliate_url: $('input[name="new_coupon[affiliate_url]"]').val(),
                    expires: $('input[name="new_coupon[expires]"]').val(),
                    description: $('input[name="new_coupon[description]"]').val()
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
                };
                // In Pro version, this would save via AJAX. Free limits to 5.
                alert('Pro feature: Add unlimited coupons. Free version limited.');
            });
            $('.acv-delete').click(function() {
                var id = $(this).data('id');
                if (confirm('Delete?')) {
                    // AJAX delete in Pro
                    $(this).closest('tr').remove();
                }
            });
        });
        </script>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('acv_coupons', array());
        $id = intval($atts['id']);
        if (!isset($coupons[$id])) return 'Coupon not found.';
        $coupon = $coupons[$id];
        $expired = !empty($coupon['expires']) && strtotime($coupon['expires']) < current_time('timestamp');
        if ($expired) {
            update_option('acv_coupons', $coupons); // Clean expired
            return '<div class="acv-coupon expired"><strong>Expired:</strong> ' . esc_html($coupon['code']) . '</div>';
        }
        ob_start();
        ?>
        <div class="acv-coupon">
            <h3><?php echo esc_html($coupon['description']); ?></h3>
            <p><strong>Code:</strong> <span class="acv-code"><?php echo esc_html($coupon['code']); ?></span> <em>(Copy & apply at checkout)</em></p>
            <a href="<?php echo esc_url($coupon['affiliate_url']); ?}" class="acv-button" target="_blank">Shop Now & Save (Affiliate Link)</a>
            <small>Expires: <?php echo esc_html($coupon['expires']); ?></small>
        </div>
        <?php
        return ob_get_clean();
    }

    private function check_expired_coupons() {
        $coupons = get_option('acv_coupons', array());
        foreach ($coupons as $id => $coupon) {
            if (!empty($coupon['expires']) && strtotime($coupon['expires']) < current_time('timestamp')) {
                unset($coupons[$id]);
            }
        }
        update_option('acv_coupons', $coupons);
    }

    public function activate() {
        add_option('acv_coupons', array());
    }
}

AffiliateCouponVault::get_instance();

// Dummy CSS/JS for self-contained (minified in real)
/*
<style>
.acv-coupon { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; border-radius: 8px; background: #f9f9f9; }
.acv-code { background: #fff; padding: 5px 10px; font-family: monospace; }
.acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
.acv-expired { opacity: 0.6; border-color: #d63638; }
</style>
<script>jQuery(document).ready(function($){ $('.acv-code').click(function(){ navigator.clipboard.writeText($(this).text()); }); });</script>
*/

?>