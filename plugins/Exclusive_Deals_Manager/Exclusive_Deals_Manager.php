/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Deals_Manager.php
*/
<?php
/**
 * Plugin Name: Exclusive Deals Manager
 * Plugin URI: https://example.com/exclusive-deals-manager
 * Description: Automatically generates and displays exclusive affiliate coupon deals to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveDealsManager {
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
        add_shortcode('exclusive_deals', array($this, 'deals_shortcode'));
        add_action('wp_footer', array($this, 'deals_widget'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_post_save_deals', array($this, 'save_deals'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('edm-script', plugin_dir_url(__FILE__) . 'edm.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('edm-style', plugin_dir_url(__FILE__) . 'edm.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Deals', 'Deals Manager', 'manage_options', 'exclusive-deals', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['deals'])) {
            update_option('edm_deals', sanitize_textarea_field($_POST['deals']));
        }
        $deals = get_option('edm_deals', 'Deal 1|https://affiliate.link1.com|10% off first purchase|Brand A|active
Deal 2|https://affiliate.link2.com|Free shipping|Brand B|active');
        echo '<div class="wrap"><h1>Manage Exclusive Deals</h1><form method="post" action="'.admin_url('admin-post.php').'"><input type="hidden" name="action" value="save_deals">';
        echo '<textarea name="deals" rows="10" cols="80" placeholder="Title|Affiliate URL|Discount|Brand|status">' . esc_textarea($deals) . '</textarea><p>Format: Title|Affiliate URL|Discount|Brand|active/inactive (one per line)</p>';
        echo '<p><input type="submit" class="button-primary" value="Save Deals"></p></form>';
        echo '<p><strong>Shortcode:</strong> [exclusive_deals]</p>';
        echo '<p><strong>Free vs Pro:</strong> Upgrade to Pro for unlimited deals, analytics, and auto-expiration.</p></div>';
    }

    public function save_deals() {
        if (!current_user_can('manage_options')) wp_die();
        wp_redirect(admin_url('options-general.php?page=exclusive-deals'));
        exit;
    }

    public function deals_shortcode($atts) {
        $deals = explode('\n', get_option('edm_deals', ''));
        $output = '<div class="edm-deals-container">';
        foreach ($deals as $deal) {
            $parts = explode('|', trim($deal));
            if (count($parts) == 5 && $parts[4] == 'active') {
                $output .= '<div class="edm-deal"><h3>' . esc_html($parts) . '</h3><p>' . esc_html($parts[2]) . ' from ' . esc_html($parts[3]) . '</p><a href="' . esc_url($parts[1]) . '" class="edm-btn" target="_blank">Get Deal <span class="edm-track" data-url="' . esc_url($parts[1]) . '">→</span></a></div>';
            }
        }
        $output .= '</div>';
        return $output;
    }

    public function deals_widget() {
        if (get_option('edm_show_widget', 1)) {
            echo $this->deals_shortcode(array());
        }
    }

    public function activate() {
        update_option('edm_deals', 'Deal 1|https://affiliate.link1.com|10% off|Brand A|active');
        update_option('edm_show_widget', 1);
    }

    public function deactivate() {
        // Cleanup optional
    }
}

ExclusiveDealsManager::get_instance();

// Inline styles and scripts for self-contained
function edm_inline_assets() {
    ?>
    <style>
    .edm-deals-container { max-width: 400px; margin: 20px 0; }
    .edm-deal { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #0073aa; }
    .edm-deal h3 { margin: 0 0 5px; font-size: 16px; }
    .edm-btn { display: inline-block; background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; }
    .edm-btn:hover { background: #005a87; }
    </style>
    <script>jQuery(document).ready(function($){ $('.edm-track').click(function(){ var url=$(this).data('url'); gtag('event', 'deal_click', {'event_category':'affiliate','event_label':url}); }); });</script>
    <?php
}
add_action('wp_head', 'edm_inline_assets');

// Pro upsell nag
function edm_pro_nag() {
    if (!get_option('edm_pro_activated')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Deals Manager Pro</strong> for analytics & more! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'edm_pro_nag');
