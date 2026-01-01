<?php
/**
 * Plugin Name: Smart Coupon Vault
 * Plugin URI: https://example.com/smart-coupon-vault
 * Description: AI-powered coupon management for affiliate marketing. Generate, manage, and display exclusive coupons to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartCouponVault {
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
        add_action('wp_ajax_delete_coupon', array($this, 'ajax_delete_coupon'));
        add_shortcode('scv_coupon_display', array($this, 'coupon_display_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scv-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('scv-admin-js', 'scv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('scv_nonce')));
        wp_enqueue_style('scv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Coupon Vault', 'Coupon Vault', 'manage_options', 'smart-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        $coupons = get_option('scv_coupons', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Coupon Vault', 'smart-coupon-vault'); ?></h1>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, AI generation, analytics & more for $49/year!</p>
            <form id="scv-form">
                <?php wp_nonce_field('scv_nonce', 'scv_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>Code</th>
                        <td><input type="text" name="code" id="coupon_code" maxlength="20" required /></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><textarea name="description" id="coupon_desc" rows="3" cols="50"></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="link" id="coupon_link" required /></td>
                    </tr>
                    <tr>
                        <th>Discount (%)</th>
                        <td><input type="number" name="discount" id="coupon_discount" min="0" max="100" /></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Add Coupon', 'smart-coupon-vault'); ?>" />
                </p>
            </form>
            <h2><?php _e('Your Coupons (Free: Max 5)', 'smart-coupon-vault'); ?></h2>
            <div id="coupons-list">
                <?php $this->list_coupons($coupons); ?>
            </div>
            <p>Use shortcode <code>[scv_coupon_display]</code> to display coupons on any page/post.</p>
        </div>
        <?php
    }

    private function list_coupons($coupons) {
        if (empty($coupons)) {
            echo '<p>No coupons yet.</p>';
            return;
        }
        foreach ($coupons as $id => $coupon) {
            echo '<div class="coupon-item">';
            echo '<strong>' . esc_html($coupon['code']) . '</strong>: ' . esc_html($coupon['description']);
            echo ' | <a href="#" class="delete-coupon" data-id="' . $id . '">Delete</a>';
            echo '</div>';
        }
    }

    public function ajax_save_coupon() {
        check_ajax_referer('scv_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $coupons = get_option('scv_coupons', array());
        if (count($coupons) >= 5) {
            wp_send_json_error('Free version limited to 5 coupons. Upgrade to Pro!');
        }

        $coupon = array(
            'code' => sanitize_text_field($_POST['code']),
            'description' => sanitize_textarea_field($_POST['description']),
            'link' => esc_url_raw($_POST['link']),
            'discount' => intval($_POST['discount']),
        );

        $coupons[] = $coupon;
        update_option('scv_coupons', $coupons);

        ob_start();
        $this->list_coupons($coupons);
        $list = ob_get_clean();

        wp_send_json_success(array('list' => $list));
    }

    public function ajax_delete_coupon() {
        check_ajax_referer('scv_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $id = intval($_POST['id']);
        $coupons = get_option('scv_coupons', array());
        if (isset($coupons[$id])) {
            unset($coupons[$id]);
            $coupons = array_values($coupons);
            update_option('scv_coupons', $coupons);
        }

        ob_start();
        $this->list_coupons($coupons);
        $list = ob_get_clean();

        wp_send_json_success(array('list' => $list));
    }

    public function coupon_display_shortcode($atts) {
        $coupons = get_option('scv_coupons', array());
        if (empty($coupons)) {
            return '<p>No coupons available.</p>';
        }

        $output = '<div class="scv-coupons">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="coupon-card">';
            $output .= '<h3>' . esc_html($coupon['code']) . '</h3>';
            $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            if (!empty($coupon['discount'])) {
                $output .= '<span class="discount">' . $coupon['discount'] . '% OFF</span>';
            }
            $output .= '<a href="' . esc_url($coupon['link']) . '" class="coupon-btn" target="_blank">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        if (!get_option('scv_coupons')) {
            add_option('scv_coupons', array());
        }
    }
}

SmartCouponVault::get_instance();

// Inline styles and scripts
add_action('wp_head', 'scv_styles');
function scv_styles() {
    echo '<style>
        .scv-coupons { display: flex; flex-wrap: wrap; gap: 20px; }
        .coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; flex: 1 1 300px; }
        .coupon-card h3 { margin: 0 0 10px; color: #333; }
        .discount { background: #ff6b35; color: white; padding: 5px 10px; border-radius: 20px; font-weight: bold; }
        .coupon-btn { display: inline-block; background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px; }
        .coupon-btn:hover { background: #005a87; }
        .coupon-item { background: #f0f0f0; padding: 10px; margin: 5px 0; border-left: 4px solid #0073aa; }
        .delete-coupon { color: #d63638; }
    </style>';
}

// Minimal admin JS
add_action('admin_footer', 'scv_admin_js');
function scv_admin_js() {
    if (isset($_GET['page']) && $_GET['page'] === 'smart-coupon-vault') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#scv-form').on('submit', function(e) {
                e.preventDefault();
                $.post(scv_ajax.ajax_url, {
                    action: 'save_coupon',
                    nonce: scv_ajax.nonce,
                    code: $('#coupon_code').val(),
                    description: $('#coupon_desc').val(),
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Coupon_Vault.php
                    link: $('#coupon_link').val(),
                    discount: $('#coupon_discount').val()
                }, function(res) {
                    if (res.success) {
                        $('#coupons-list').html(res.data.list);
                        $('#scv-form').reset();
                    } else {
                        alert(res.data);
                    }
                });
            });

            $(document).on('click', '.delete-coupon', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                $.post(scv_ajax.ajax_url, {
                    action: 'delete_coupon',
                    nonce: scv_ajax.nonce,
                    id: id
                }, function(res) {
                    if (res.success) {
                        $('#coupons-list').html(res.data.list);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
