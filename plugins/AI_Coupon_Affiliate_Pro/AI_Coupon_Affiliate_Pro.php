/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator and affiliate link manager that creates personalized coupons, tracks clicks, and boosts affiliate commissions automatically.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Prevent direct access
define('AI_COUPON_PRO_VERSION', '1.0.0');
define('AI_COUPON_PRO_PATH', plugin_dir_path(__FILE__));
define('AI_COUPON_PRO_URL', plugin_dir_url(__FILE__));

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_ai_coupon_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', AI_COUPON_PRO_URL . 'assets/script.js', array('jquery'), AI_COUPON_PRO_VERSION, true);
        wp_enqueue_style('ai-coupon-css', AI_COUPON_PRO_URL . 'assets/style.css', array(), AI_COUPON_PRO_VERSION);
    }

    public function admin_menu() {
        add_menu_page(
            'AI Coupon Pro',
            'AI Coupons',
            'manage_options',
            'ai-coupon-pro',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="wrap"><h1>' . esc_html(get_admin_page_title()) . '</h1>';
        echo '<div id="ai-coupon-admin">';
        if (isset($_POST['add_coupon'])) {
            $this->save_coupon($_POST);
        }
        $coupons = get_option('ai_coupon_coupons', array());
        echo '<form method="post">';
        echo '<h2>Add New Coupon</h2>';
        echo '<input type="text" name="title" placeholder="Coupon Title" required><br>';
        echo '<input type="text" name="code" placeholder="Coupon Code" required><br>';
        echo '<input type="url" name="affiliate_url" placeholder="Affiliate Link" required><br>';
        echo '<textarea name="description" placeholder="Description"></textarea><br>';
        echo '<input type="submit" name="add_coupon" value="Add Coupon" class="button-primary">';
        echo '</form>';
        echo '<h2>Your Coupons</h2><ul>';
        foreach ($coupons as $id => $coupon) {
            echo '<li>' . esc_html($coupon['title']) . ' - <a href="' . esc_url($coupon['affiliate_url']) . '" target="_blank">' . esc_html($coupon['code']) . '</a> ';
            echo '<a href="#" onclick="deleteCoupon(' . $id . ')" style="color:red;">Delete</a></li>';
        }
        echo '</ul>';
        echo '<p><strong>Pro Upgrade:</strong> Unlock AI generation, analytics, unlimited coupons for $49/year!</p>';
        echo '</div></div>';
        echo '<script>function deleteCoupon(id) { if(confirm("Delete?")) { location.href="?page=ai-coupon-pro&delete="+id; } }</script>';
        if (isset($_GET['delete'])) {
            unset($coupons[$_GET['delete']]);
            update_option('ai_coupon_coupons', $coupons);
        }
    }

    private function save_coupon($data) {
        $coupons = get_option('ai_coupon_coupons', array());
        $coupons[] = array(
            'title' => sanitize_text_field($data['title']),
            'code' => sanitize_text_field($data['code']),
            'affiliate_url' => esc_url_raw($data['affiliate_url']),
            'description' => sanitize_textarea_field($data['description']),
            'clicks' => 0
        );
        update_option('ai_coupon_coupons', $coupons);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('ai_coupon_coupons', array());
        if (empty($atts['id']) || !isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $track_url = admin_url('admin-ajax.php?action=ai_coupon_track_click&id=' . $atts['id']);
        ob_start();
        ?>
        <div class="ai-coupon-box">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
            <?php if (!empty($coupon['description'])): ?>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <?php endif; ?>
            <a href="<?php echo esc_url($track_url); ?>" class="coupon-btn" data-url="<?php echo esc_url($coupon['affiliate_url']); ?>" onclick="trackClick(this)">Get Deal & Save Now!</a>
            <small>Clicks: <?php echo intval($coupon['clicks']); ?></small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        $id = intval($_GET['id']);
        $coupons = get_option('ai_coupon_coupons', array());
        if (isset($coupons[$id])) {
            $coupons[$id]['clicks']++;
            update_option('ai_coupon_coupons', $coupons);
            wp_redirect($coupons[$id]['affiliate_url']);
            exit;
        }
    }

    public function activate() {
        add_option('ai_coupon_coupons', array());
    }
}

new AICouponAffiliatePro();

// Frontend Assets (inline for single file)
function ai_coupon_pro_assets() {
    echo '<style>
    .ai-coupon-box { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }
    .coupon-code { font-size: 2em; font-weight: bold; color: #e74c3c; background: #fff; padding: 10px; display: inline-block; margin: 10px 0; }
    .coupon-btn { display: inline-block; background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .coupon-btn:hover { background: #219a52; }
    </style>';
    echo '<script>jQuery(document).ready(function($) { window.trackClick = function(btn) { var url = $(btn).data("url"); window.location.href = url; return false; } });</script>';
}
add_action('wp_head', 'ai_coupon_pro_assets');

// Pro Notice
function ai_coupon_pro_notice() {
    if (!is_admin()) return;
    echo '<div class="notice notice-info"><p><strong>AI Coupon Pro:</strong> Upgrade to Pro for AI coupon generation, advanced analytics & more! <a href="https://example.com/pro" target="_blank">Get Pro ($49/yr)</a></p></div>';
}
add_action('admin_notices', 'ai_coupon_pro_notice');