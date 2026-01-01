/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Deals_Vault.php
*/
<?php
/**
 * Plugin Name: Exclusive Deals Vault
 * Plugin URI: https://example.com/exclusive-deals-vault
 * Description: Automatically curates and displays exclusive affiliate coupons and deals from top brands, boosting conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-deals-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveDealsVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('edv_deals', array($this, 'deals_shortcode'));
        add_action('wp_ajax_edv_dismiss_premium', array($this, 'dismiss_premium_notice'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-deals-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('edv-style', plugins_url('style.css', __FILE__), array(), '1.0.0');
        wp_enqueue_script('edv-script', plugins_url('script.js', __FILE__), array('jquery'), '1.0.0', true);
        wp_localize_script('edv-script', 'edv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('edv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Exclusive Deals Vault', 'Deals Vault', 'manage_options', 'edv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['edv_settings'])) {
            update_option('edv_api_key', sanitize_text_field($_POST['api_key']));
            update_option('edv_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('edv_api_key', '');
        $affiliate_id = get_option('edv_affiliate_id', '');
        ?>
        <div class="wrap">
            <h1>Exclusive Deals Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Premium)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Your Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <div id="edv-premium-upsell" style="background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ffeaa7;">
                <h3>Unlock Premium Features</h3>
                <p>Auto-fetch 1000+ deals, custom branding, analytics & more for <strong>$49/year</strong>. <button id="edv-upgrade" class="button button-primary">Upgrade Now</button></p>
                <button id="edv-dismiss" class="button">Dismiss</button>
            </div>
        </div>
        <?php
    }

    public function deals_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'category' => 'all'
        ), $atts);

        $deals = $this->get_sample_deals($atts['limit']);
        ob_start();
        ?>
        <div class="edv-deals-vault">
            <h3>Exclusive Deals <span class="edv-badge">Save Now!</span></h3>
            <div class="edv-grid">
                <?php foreach ($deals as $deal): ?>
                <div class="edv-deal-card">
                    <img src="<?php echo esc_url($deal['image']); ?>" alt="<?php echo esc_attr($deal['title']); ?>">
                    <h4><?php echo esc_html($deal['title']); ?></h4>
                    <p class="edv-discount"><?php echo esc_html($deal['discount']); ?> OFF</p>
                    <p class="edv-code"><?php echo esc_html($deal['code']); ?></p>
                    <a href="<?php echo esc_url($deal['link']); ?}" class="edv-button" target="_blank" rel="nofollow">Get Deal <?php echo get_option('edv_affiliate_id') ? '(ID: ' . esc_html(get_option('edv_affiliate_id')) . ')' : ''; ?></a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <style>
        .edv-deals-vault { max-width: 1200px; margin: 0 auto; }
        .edv-badge { background: #ff4757; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; }
        .edv-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .edv-deal-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; text-align: center; transition: box-shadow 0.3s; }
        .edv-deal-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .edv-deal-card img { max-width: 100%; height: 120px; object-fit: cover; border-radius: 4px; }
        .edv-discount { color: #ff4757; font-weight: bold; font-size: 18px; margin: 10px 0; }
        .edv-code { background: #f8f9fa; padding: 8px; border-radius: 4px; font-family: monospace; }
        .edv-button { display: inline-block; background: #00b0ff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 10px; }
        .edv-button:hover { background: #0090d0; }
        </style>
        <?php
        return ob_get_clean();
    }

    private function get_sample_deals($limit) {
        return array_slice([
            [
                'title' => 'Hostinger Premium Hosting',
                'image' => 'https://via.placeholder.com/250x120/00b0ff/ffffff?text=Hostinger',
                'discount' => '75%',
                'code' => 'EDV75OFF',
                'link' => 'https://hostinger.com/?ref=edv123'
            ],
            [
                'title' => 'Elementor Pro Design Kit',
                'image' => 'https://via.placeholder.com/250x120/ffc107/000?text=Elementor',
                'discount' => '50%',
                'code' => 'EDVPRO50',
                'link' => 'https://elementor.com/?ref=edv456'
            ],
            [
                'title' => 'SEMrush SEO Tools',
                'image' => 'https://via.placeholder.com/250x120/00d2d3/fff?text=SEMrush',
                'discount' => '30 Days Free',
                'code' => 'EDVSEM',
                'link' => 'https://semrush.com/?ref=edv789'
            ],
            [
                'title' => 'WP Rocket Speed Booster',
                'image' => 'https://via.placeholder.com/250x120/e74c3c/fff?text=WPRocket',
                'discount' => '10%',
                'code' => 'EDVROCKET',
                'link' => 'https://wp-rocket.me/?ref=edv101'
            ],
            [
                'title' => 'Bluehost WordPress Hosting',
                'image' => 'https://via.placeholder.com/250x120/3498db/fff?text=Bluehost',
                'discount' => '66%',
                'code' => 'EDVBLUE',
                'link' => 'https://bluehost.com/?ref=edv202'
            ]
        ], 0, $limit);
    }

    public function dismiss_premium_notice() {
        check_ajax_referer('edv_nonce', 'nonce');
        update_option('edv_dismissed_premium', true);
        wp_die('success');
    }
}

new ExclusiveDealsVault();

// Premium upsell logic
if (!get_option('edv_dismissed_premium') && !get_option('edv_api_key')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-info"><p>Unlock <strong>Exclusive Deals Vault Premium</strong> for auto-deal fetching & analytics! <a href="' . admin_url('options-general.php?page=edv-settings') . '" class="button button-primary">Upgrade Now</a></p></div>';
    });
}

/*
Premium features (commented for demo):
- Real API integration for 1000+ deals
- Click tracking & analytics dashboard
- Custom categories & scheduling
- White-label branding
*/
?>