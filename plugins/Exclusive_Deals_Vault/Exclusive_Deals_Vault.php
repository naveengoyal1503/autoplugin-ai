/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Deals_Vault.php
*/
<?php
/**
 * Plugin Name: Exclusive Deals Vault
 * Plugin URI: https://example.com/exclusive-deals-vault
 * Description: Automatically generates and displays personalized, trackable coupon codes and exclusive deals for your WordPress site, boosting affiliate conversions and reader loyalty.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-deals-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveDealsVault {
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
        add_shortcode('edv_deals', array($this, 'deals_shortcode'));
        add_action('wp_ajax_edv_generate_code', array($this, 'ajax_generate_code'));
        add_action('wp_ajax_nopriv_edv_generate_code', array($this, 'ajax_generate_code'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-deals-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('edv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('edv-frontend', 'edv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('edv_nonce')));
    }

    public function admin_enqueue_scripts($hook) {
        if ('toplevel_page_edv-dashboard' !== $hook) return;
        wp_enqueue_script('edv-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Deals Vault',
            'Deals Vault',
            'manage_options',
            'edv-dashboard',
            array($this, 'admin_page'),
            'dashicons-cart',
            30
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) return;
        $deals = get_option('edv_deals', array());
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function deals_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts, 'edv_deals');
        $deals = get_option('edv_deals', array());
        ob_start();
        echo '<div id="edv-deals-container" class="edv-deals">';
        foreach (array_slice($deals, 0, intval($atts['limit'])) as $deal) {
            $code = $this->generate_unique_code($deal['id']);
            echo '<div class="edv-deal">';
            echo '<h3>' . esc_html($deal['title']) . '</h3>';
            echo '<p>' . esc_html($deal['description']) . '</p>';
            echo '<div class="edv-code">Code: <strong>' . esc_html($code) . '</strong></div>';
            echo '<a href="' . esc_url($deal['affiliate_link']) . '" target="_blank" class="edv-btn">Get Deal</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    private function generate_unique_code($deal_id) {
        $user_id = get_current_user_id() ?: wp_generate_uuid4();
        return strtoupper(substr(md5($user_id . $deal_id . time()), 0, 8));
    }

    public function ajax_generate_code() {
        check_ajax_referer('edv_nonce', 'nonce');
        $deal_id = intval($_POST['deal_id']);
        $deals = get_option('edv_deals', array());
        if (isset($deals[$deal_id])) {
            $code = $this->generate_unique_code($deal_id);
            wp_send_json_success(array('code' => $code));
        }
        wp_send_json_error();
    }
}

ExclusiveDealsVault::get_instance();

// Pro upgrade nag
function edv_pro_nag() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock unlimited deals and analytics with <a href="https://example.com/pro" target="_blank">Exclusive Deals Vault Pro</a> for $49/year!</p></div>';
}
add_action('admin_notices', 'edv_pro_nag');

// Create assets directories if missing
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
        file_put_contents($assets_dir . '/frontend.js', '// Frontend JS placeholder');
        file_put_contents($assets_dir . '/admin.js', '// Admin JS placeholder');
    }
    $admin_page = '<?php if (!defined("ABSPATH")) exit; ?><div class="wrap"><h1>Exclusive Deals Vault</h1><form method="post" action="options.php"><table class="form-table">';
    $admin_page .= '<tr><th>Title</th><td><input type="text" name="edv_deal_title[]" /></td></tr>';
    $admin_page .= '<tr><th>Description</th><td><textarea name="edv_deal_desc[]"></textarea></td></tr>';
    $admin_page .= '<tr><th>Affiliate Link</th><td><input type="url" name="edv_deal_link[]" /></td></tr>';
    $admin_page .= '<tr><td colspan="2"><input type="submit" value="Add Deal" class="button-primary" /></td></tr></table>';
    $admin_page .= '</form></div>';
    file_put_contents(plugin_dir_path(__FILE__) . 'admin-page.php', $admin_page);
});