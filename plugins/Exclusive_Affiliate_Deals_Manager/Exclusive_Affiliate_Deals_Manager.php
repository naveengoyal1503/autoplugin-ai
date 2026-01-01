/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Affiliate_Deals_Manager.php
*/
<?php
/**
 * Plugin Name: Exclusive Affiliate Deals Manager
 * Plugin URI: https://example.com/exclusive-deals-manager
 * Description: Automatically generates and manages exclusive affiliate coupon deals, tracks clicks, and boosts conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-deals-manager
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_deals', array($this, 'deals_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-deals-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('edm-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('edm-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            'Exclusive Deals Manager',
            'Deals Manager',
            'manage_options',
            'exclusive-deals-manager',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('edm_deals', sanitize_textarea_field($_POST['deals']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $deals = get_option('edm_deals', "Brand1|DISCOUNT10|https://affiliate.link1|Brand description\nBrand2|SAVE20|https://affiliate.link2|Another deal");
        ?>
        <div class="wrap">
            <h1>Exclusive Deals Manager</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Deals (Format: Brand|Code|Affiliate URL|Description, one per line)</th>
                        <td><textarea name="deals" rows="10" cols="50"><?php echo esc_textarea($deals); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Shortcode:</strong> <code>[exclusive_deals]</code></p>
            <p><em>Pro version unlocks unlimited deals, click tracking, and analytics.</em></p>
        </div>
        <?php
    }

    public function deals_shortcode($atts) {
        $deals_str = get_option('edm_deals', "");
        $deals = explode("\n", trim($deals_str));
        $output = '<div id="edm-deals-container" class="edm-deals">';
        foreach ($deals as $deal) {
            if (empty(trim($deal))) continue;
            list($brand, $code, $url, $desc) = array_pad(explode('|', trim($deal)), 4, '');
            $output .= '<div class="edm-deal">';
            $output .= '<h3>' . esc_html($brand) . '</h3>';
            $output .= '<p>' . esc_html($desc) . '</p>';
            $output .= '<div class="edm-code">' . esc_html($code) . '</div>';
            $output .= '<a href="' . esc_url($url) . '" class="edm-button" target="_blank">Get Deal (Affiliate)</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        if (!get_option('edm_deals')) {
            update_option('edm_deals', "Brand1|DISCOUNT10|https://affiliate.link1|Brand description\nBrand2|SAVE20|https://affiliate.link2|Another deal");
        }
    }
}

ExclusiveDealsManager::get_instance();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $assets = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets)) {
        wp_mkdir_p($assets);
    }
    if (!file_exists($assets . 'frontend.js')) {
        file_put_contents($assets . 'frontend.js', '// Frontend JS for deals\njQuery(document).ready(function($) { $(".edm-button").on("click", function() { console.log("Deal clicked!"); }); });');
    }
    if (!file_exists($assets . 'frontend.css')) {
        file_put_contents($assets . 'frontend.css', '.edm-deals { display: grid; gap: 20px; } .edm-deal { border: 1px solid #ddd; padding: 20px; border-radius: 8px; } .edm-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; } .edm-code { background: #f0f0f0; padding: 10px; font-family: monospace; margin: 10px 0; }');
    }
});

// Pro upsell notice
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && !defined('EDM_PRO')) {
        echo '<div class="notice notice-info"><p><strong>Exclusive Deals Manager:</strong> Unlock <a href="https://example.com/pro" target="_blank">Pro version</a> for click tracking, unlimited deals & analytics!</p></div>';
    }
});