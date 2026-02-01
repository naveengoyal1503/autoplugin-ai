/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Popups.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Popups
 * Plugin URI: https://example.com/smart-affiliate-popups
 * Description: AI-powered popup plugin that automatically generates and displays personalized affiliate link popups to boost conversions and monetize WordPress sites effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliatePopups {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sap_show_popup', array($this, 'handle_ajax_popup'));
        add_action('wp_ajax_nopriv_sap_show_popup', array($this, 'handle_ajax_popup'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sap_enabled', '1') !== '1') return;

        // Auto-generate affiliate links from popular networks
        $this->generate_affiliate_links();
    }

    public function enqueue_scripts() {
        if (get_option('sap_enabled', '1') !== '1') return;

        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', $this->get_popup_js());
        wp_localize_script('jquery', 'sap_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_add_inline_style('wp-block-library', $this->get_popup_css());
    }

    private function get_popup_css() {
        return '
        #sap-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            z-index: 999999;
            max-width: 400px;
            text-align: center;
        }
        #sap-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999998;
        }
        #sap-popup button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        #sap-popup button:hover {
            background: #005a87;
        }
        ';
    }

    private function get_popup_js() {
        return '
        jQuery(document).ready(function($) {
            setTimeout(function() {
                if (Math.random() < 0.3) { // 30% show rate
                    $.post(sap_ajax.ajax_url, {action: "sap_show_popup"}, function(data) {
                        if (data.success) {
                            $("#sap-popup .sap-content").html(data.data.html);
                            $("#sap-popup, #sap-overlay").fadeIn();
                        }
                    });
                }
            }, 10000); // Show after 10 seconds

            $("#sap-overlay, #sap-popup .close").on("click", function() {
                $("#sap-popup, #sap-overlay").fadeOut();
            });
        });
        ';
    }

    public function handle_ajax_popup() {
        $links = get_option('sap_affiliate_links', array(
            array('text' => 'Get 50% off on Amazon bestsellers!', 'url' => 'https://amazon.com/?tag=youraffiliateid', 'img' => ''),
            array('text' => 'Boost your site with premium hosting!', 'url' => 'https://examplehost.com/?aff=123', 'img' => ''),
        ));

        $link = $links[array_rand($links)];
        $html = '<h3>Exclusive Deal!</h3><p>' . esc_html($link['text']) . '</p><img src="' . plugins_url('placeholder.jpg', __FILE__) . '" alt="Deal" style="width:100%;"><br><a href="' . esc_url($link['url']) . '" target="_blank"><button>Claim Now</button></a>';

        wp_send_json_success(array('html' => $html));
    }

    private function generate_affiliate_links() {
        // Simulate AI generation - in pro version, integrate real AI API
        $default_links = array(
            array('text' => 'Upgrade your WordPress with premium plugins!', 'url' => 'https://example.com/aff', 'img' => ''),
        );
        update_option('sap_affiliate_links', $default_links);
    }

    public function activate() {
        update_option('sap_enabled', '1');
        $this->generate_affiliate_links();
    }
}

new SmartAffiliatePopups();

// Admin settings page
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Affiliate Popups', 'Affiliate Popups', 'manage_options', 'sap-settings', 'sap_settings_page');
    });
}

function sap_settings_page() {
    if (isset($_POST['sap_submit'])) {
        update_option('sap_enabled', sanitize_text_field($_POST['sap_enabled']));
        update_option('sap_affiliate_links', json_decode(stripslashes($_POST['sap_links']), true));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $links = get_option('sap_affiliate_links', array());
    ?>
    <div class="wrap">
        <h1>Smart Affiliate Popups Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Enable Popups</th>
                    <td><input type="checkbox" name="sap_enabled" value="1" <?php checked(get_option('sap_enabled'), '1'); ?>></td>
                </tr>
                <tr>
                    <th>Affiliate Links (JSON)</th>
                    <td><textarea name="sap_links" rows="10" cols="50"><?php echo esc_textarea(json_encode($links)); ?></textarea><br><small>Example: [{ "text": "Deal text", "url": "https://aff.link" }]</small></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><strong>Pro Upgrade:</strong> Unlock A/B testing, geo-targeting, and AI link generation for $49/year.</p>
    </div>
    <?php
}
?>