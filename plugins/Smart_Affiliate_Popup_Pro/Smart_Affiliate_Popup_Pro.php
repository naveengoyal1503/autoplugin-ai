/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Popup_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Popup Pro
 * Plugin URI: https://example.com/smart-affiliate-popup
 * Description: AI-powered popup plugin that displays personalized affiliate links and products to boost conversions and automate WordPress monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliatePopupPro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sap_show_popup', array($this, 'handle_ajax_popup'));
        add_action('wp_ajax_nopriv_sap_show_popup', array($this, 'handle_ajax_popup'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('sap_pro_version') !== '1.0') {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        if (!is_admin()) {
            wp_enqueue_script('jquery');
            wp_add_inline_script('jquery', $this->get_popup_js());
            wp_localize_script('jquery', 'sap_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
        }
    }

    private function get_popup_js() {
        return "
        jQuery(document).ready(function($) {
            setTimeout(function() {
                $.post(sap_ajax.ajax_url, {action: 'sap_show_popup'}, function(data) {
                    if (data.success && data.html) {
                        $('body').append(data.html);
                        $('.sap-popup-overlay').fadeIn();
                    }
                });
            }, 10000); // Show after 10 seconds

            $(document).on('click', '.sap-popup-close, .sap-popup-overlay', function() {
                $('.sap-popup-overlay').fadeOut();
            });
        });
        ";
    }

    public function handle_ajax_popup() {
        $affiliates = get_option('sap_affiliates', array(
            array('name' => 'Sample Product', 'link' => 'https://example.com/aff-link-1', 'image' => ''),
            array('name' => 'Another Deal', 'link' => 'https://example.com/aff-link-2', 'image' => '')
        ));

        if (empty($affiliates)) {
            wp_send_json_error('No affiliates configured');
        }

        // Simple 'AI' personalization: random for demo
        $rand = array_rand($affiliates);
        $aff = $affiliates[$rand];

        $html = '<div class="sap-popup-overlay">
            <div class="sap-popup">
                <span class="sap-popup-close">&times;</span>
                <h3>Recommended for You: ' . esc_html($aff['name']) . '</h3>
                ' . ($aff['image'] ? '<img src="' . esc_url($aff['image']) . '" alt="Product">' : '') . '
                <p>Check out this great deal!</p>
                <a href="' . esc_url($aff['link']) . '" target="_blank" class="sap-button">Get It Now (Affiliate Link)</a>
            </div>
        </div>';

        wp_send_json_success(array('html' => $html));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Popup', 'Affiliate Popup', 'manage_options', 'sap-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('sap_options', 'sap_affiliates');
        register_setting('sap_options', 'sap_pro_version');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('sap_affiliates', sanitize_text_field_deep($_POST['sap_affiliates']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Popup Pro Settings</h1>
            <form method="post" action="">
                <h2>Add Affiliate Links</h2>
                <table class="form-table">
                    <tr>
                        <th>Affiliates (JSON array: [{"name":"Product","link":"url","image":"url"}])</th>
                        <td><textarea name="sap_affiliates" rows="10" cols="50"><?php echo esc_textarea(json_encode(get_option('sap_affiliates', array()))); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock A/B testing, geo-targeting, and more for $49/year!</p>
        </div>
        <style>
        .sap-popup-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: none; }
        .sap-popup { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; max-width: 400px; }
        .sap-popup-close { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; }
        .sap-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        </style>
        <?php
    }

    public function activate() {
        update_option('sap_pro_version', '1.0');
        if (!get_option('sap_affiliates')) {
            update_option('sap_affiliates', array(
                array('name' => 'Sample Product', 'link' => '#', 'image' => ''),
                array('name' => 'Another Deal', 'link' => '#', 'image' => '')
            ));
        }
    }

    public function deactivate() {}
}

SmartAffiliatePopupPro::get_instance();
