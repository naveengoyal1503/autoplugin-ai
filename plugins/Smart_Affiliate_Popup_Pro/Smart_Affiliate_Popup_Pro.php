/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Popup_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Popup Pro
 * Plugin URI: https://example.com/smart-affiliate-popup
 * Description: AI-powered popup plugin that displays personalized affiliate product recommendations to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliatePopupPro {
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
        add_action('wp_footer', array($this, 'render_popup'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        $this->load_settings();
    }

    private $settings = array();

    private function load_settings() {
        $this->settings = get_option('smart_affiliate_popup_settings', array(
            'enabled' => 'yes',
            'affiliate_links' => json_encode(array(
                array('keyword' => 'wordpress', 'link' => 'https://example.com/aff/wp', 'text' => 'Best WordPress Hosting'),
                array('keyword' => 'plugin', 'link' => 'https://example.com/aff/plugin', 'text' => 'Top Plugins')
            )),
            'trigger' => 'scroll',
            'delay' => 5000,
            'pro' => 'no'
        ));
    }

    public function enqueue_scripts() {
        if (!$this->is_enabled()) return;
        wp_enqueue_script('smart-affiliate-popup', plugin_dir_url(__FILE__) . 'popup.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-affiliate-popup', plugin_dir_url(__FILE__) . 'popup.css', array(), '1.0.0');
        wp_localize_script('smart-affiliate-popup', 'sap_settings', array(
            'links' => json_decode($this->settings['affiliate_links'], true),
            'trigger' => $this->settings['trigger'],
            'delay' => intval($this->settings['delay']),
            'pro' => $this->settings['pro'] === 'yes'
        ));
    }

    public function render_popup() {
        if (!$this->is_enabled()) return;
        ?>
        <div id="sap-popup" style="display:none;">
            <div class="sap-overlay"></div>
            <div class="sap-content">
                <span class="sap-close">&times;</span>
                <div class="sap-message">Discover the perfect product for you!</div>
                <a href="#" class="sap-aff-link" target="_blank">Shop Now</a>
            </div>
        </div>
        <?php
    }

    private function is_enabled() {
        return $this->settings['enabled'] === 'yes' && !is_admin();
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Popup', 'Affiliate Popup', 'manage_options', 'smart-affiliate-popup', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('sap_settings_group', 'smart_affiliate_popup_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Popup Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('sap_settings_group'); ?>
                <?php do_settings_sections('sap_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Popup</th>
                        <td><input type="checkbox" name="smart_affiliate_popup_settings[enabled]" value="yes" <?php checked($this->settings['enabled'], 'yes'); ?> /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON)</th>
                        <td><textarea name="smart_affiliate_popup_settings[affiliate_links]" rows="10" cols="50"><?php echo esc_textarea($this->settings['affiliate_links']); ?></textarea><br>
                        Format: [{ "keyword": "word", "link": "url", "text": "text" }]</td>
                    </tr>
                    <tr>
                        <th>Trigger</th>
                        <td>
                            <select name="smart_affiliate_popup_settings[trigger]">
                                <option value="time" <?php selected($this->settings['trigger'], 'time'); ?>>Time Delay</option>
                                <option value="scroll" <?php selected($this->settings['trigger'], 'scroll'); ?>>50% Scroll</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Delay (ms)</th>
                        <td><input type="number" name="smart_affiliate_popup_settings[delay]" value="<?php echo esc_attr($this->settings['delay']); ?>" /></td>
                    </tr>
                    <?php if ($this->settings['pro'] !== 'yes') : ?>
                    <tr>
                        <td colspan="2"><strong>Upgrade to Pro for A/B testing, analytics, and geo-targeting! <a href="https://example.com/pro">Get Pro ($49/year)</a></strong></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <style>
        #sap-popup { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999; }
        .sap-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .sap-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; text-align: center; max-width: 400px; }
        .sap-close { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; }
        .sap-aff-link { display: inline-block; background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            var popupShown = localStorage.getItem('sap_shown');
            if (popupShown) return;
            var settings = sap_settings;
            var timeout;
            if (settings.trigger === 'time') {
                timeout = setTimeout(showPopup, settings.delay);
            } else {
                $(window).on('scroll', function() {
                    if ($(window).scrollTop() / ($(document).height() - $(window).height()) > 0.5) {
                        showPopup();
                        $(window).off('scroll');
                    }
                });
            }
            function showPopup() {
                $('#sap-popup').fadeIn();
                localStorage.setItem('sap_shown', '1');
                clearTimeout(timeout);
            }
            $('.sap-close, .sap-overlay').on('click', function() {
                $('#sap-popup').fadeOut();
            });
            // Simple keyword matching
            var bodyText = $('body').text().toLowerCase();
            for (var i = 0; i < settings.links.length; i++) {
                if (bodyText.includes(settings.links[i].keyword.toLowerCase())) {
                    $('.sap-aff-link').attr('href', settings.links[i].link).text(settings.links[i].text);
                    break;
                }
            }
        });
        </script>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_popup_settings', array('enabled' => 'yes'));
    }
}

SmartAffiliatePopupPro::get_instance();