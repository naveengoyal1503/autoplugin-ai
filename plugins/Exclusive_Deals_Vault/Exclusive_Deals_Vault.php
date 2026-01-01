/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Deals_Vault.php
*/
<?php
/**
 * Plugin Name: Exclusive Deals Vault
 * Plugin URI: https://example.com/deals-vault
 * Description: Automatically generates and displays exclusive affiliate coupon deals from top brands, boosting conversions with personalized discounts and tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        add_shortcode('deals_vault', array($this, 'deals_shortcode'));
        add_action('wp_ajax_get_deals', array($this, 'ajax_get_deals'));
        add_action('wp_ajax_nopriv_get_deals', array($this, 'ajax_get_deals'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('deals-vault-js', plugin_dir_url(__FILE__) . 'deals-vault.js', array('jquery'), '1.0.0', true);
        wp_localize_script('deals-vault-js', 'dealsAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
        wp_enqueue_style('deals-vault-css', plugin_dir_url(__FILE__) . 'deals-vault.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Deals Vault Settings', 'Deals Vault', 'manage_options', 'deals-vault', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('deals_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('deals_api_key', '');
        ?>
        <div class="wrap">
            <h1>Deals Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Pro upgrade unlocks unlimited deals and analytics. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function deals_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'category' => 'all'
        ), $atts);

        ob_start();
        ?>
        <div id="deals-vault-container" data-limit="<?php echo esc_attr($atts['limit']); ?>" data-category="<?php echo esc_attr($atts['category']); ?>">
            <div class="deals-loading">Loading exclusive deals...</div>
            <div class="deals-grid"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_get_deals() {
        // Free version demo deals (Pro fetches real API)
        $deals = array(
            array('title' => '50% Off Hosting', 'code' => 'HOST50', 'link' => 'https://example.com/hosting?ref=123', 'expires' => '2026-03-01'),
            array('title' => 'Free Trial VPN', 'code' => 'VPNFREE', 'link' => 'https://example.com/vpn?ref=123', 'expires' => '2026-02-15'),
            array('title' => '20% Email Marketing', 'code' => 'EMAIL20', 'link' => 'https://example.com/email?ref=123', 'expires' => '2026-04-01'),
            array('title' => 'Pro Theme Discount', 'code' => 'THEMEPRO', 'link' => 'https://example.com/theme?ref=123', 'expires' => '2026-01-31'),
            array('title' => 'SEO Tool 30% Off', 'code' => 'SEOTOOL30', 'link' => 'https://example.com/seo?ref=123', 'expires' => '2026-02-28')
        );

        if (get_option('deals_api_key')) {
            // Pro: Real API integration here
        }

        wp_send_json_success($deals);
    }

    public function activate() {
        add_option('deals_api_key', '');
    }
}

ExclusiveDealsVault::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
#deals-vault-container { max-width: 800px; margin: 20px 0; }
.deals-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.deal-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; }
.deal-title { font-size: 1.5em; font-weight: bold; margin-bottom: 10px; }
.deal-code { background: #ff6b35; color: white; padding: 8px 16px; border-radius: 4px; font-family: monospace; display: inline-block; margin: 10px 0; }
.deal-link { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; }
.deal-link:hover { background: #005a87; }
.deals-expires { font-size: 0.9em; color: #666; margin-top: 10px; }
.deals-pro { text-align: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; margin-top: 20px; }
.deals-pro a { color: #ffd700; text-decoration: none; font-weight: bold; }
.deals-loading { text-align: center; padding: 40px; font-style: italic; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('#deals-vault-container').each(function() {
        var $container = $(this);
        var limit = $container.data('limit');
        var category = $container.data('category');

        $.post(dealsAjax.ajaxurl, {
            action: 'get_deals',
            limit: limit,
            category: category
        }, function(response) {
            if (response.success) {
                var html = '';
                response.data.forEach(function(deal) {
                    html += '<div class="deal-card">';
                    html += '<div class="deal-title">' + deal.title + '</div>';
                    html += '<div class="deal-code">' + deal.code + '</div>';
                    html += '<a href="' + deal.link + '" class="deal-link" target="_blank">Grab Deal</a>';
                    html += '<div class="deals-expires">Expires: ' + deal.expires + '</div>';
                    html += '</div>';
                });
                html += '<div class="deals-pro">Unlock unlimited real-time deals & analytics with <a href="https://example.com/pro" target="_blank">Pro Upgrade ($49/yr)</a></div>';
                $container.find('.deals-grid').html(html);
                $container.find('.deals-loading').hide();
            }
        });
    });
});
</script>
<?php });