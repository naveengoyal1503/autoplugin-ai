/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Popup_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Popup Pro
 * Plugin URI: https://example.com/smart-affiliate-popup
 * Description: AI-powered popup plugin that displays personalized affiliate product recommendations to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-popup
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliatePopup {
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
        load_plugin_textdomain('smart-affiliate-popup', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('smart-affiliate-popup-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('smart-affiliate-popup-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('smart-affiliate-popup-script', 'sap_settings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sap_nonce'),
            'is_pro' => false
        ));
    }

    public function render_popup() {
        $settings = get_option('sap_settings', array(
            'enabled' => true,
            'delay' => 5000,
            'affiliate_links' => json_encode(array(
                array('text' => 'Check out this amazing product!', 'url' => '#', 'image' => ''),
                array('text' => 'Recommended for you!', 'url' => '#', 'image' => '')            )),
            'trigger' => 'time'
        ));

        if (!$settings['enabled']) return;
?>
<div id="sap-popup" class="sap-popup" style="display:none;">
    <div class="sap-overlay"></div>
    <div class="sap-content">
        <div class="sap-close">&times;</div>
        <div class="sap-message">Loading recommendation...</div>
        <div class="sap-affiliate"></div>
        <button class="sap-btn">Get It Now</button>
    </div>
</div>
<?php
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Popup', 'Affiliate Popup', 'manage_options', 'smart-affiliate-popup', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('sap_settings_group', 'sap_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Popup Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('sap_settings_group'); do_settings_sections('sap_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Popup</th>
                        <td><input type="checkbox" name="sap_settings[enabled]" value="1" <?php checked(get_option('sap_settings')['enabled'] ?? true); ?> /></td>
                    </tr>
                    <tr>
                        <th>Display Delay (ms)</th>
                        <td><input type="number" name="sap_settings[delay]" value="<?php echo esc_attr(get_option('sap_settings')['delay'] ?? 5000); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON)</th>
                        <td><textarea name="sap_settings[affiliate_links]" rows="10" cols="50"><?php echo esc_textarea(get_option('sap_settings')['affiliate_links'] ?? ''); ?></textarea><br><small>Enter JSON array of objects: {"text":"msg","url":"link","image":"url"}</small></td>
                    </tr>
                    <tr>
                        <th>Trigger</th>
                        <td>
                            <select name="sap_settings[trigger]">
                                <option value="time" <?php selected(get_option('sap_settings')['trigger'] ?? 'time', 'time'); ?>>Time Delay</option>
                                <option value="exit" <?php selected(get_option('sap_settings')['trigger'] ?? 'time', 'exit'); ?>>Exit Intent</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Version:</strong> Unlock A/B testing, geo-targeting, unlimited campaigns for $49/year!</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('sap_settings', array('enabled' => true, 'delay' => 5000));
    }
}

SmartAffiliatePopup::get_instance();

// AJAX handler for dynamic content
add_action('wp_ajax_get_affiliate', 'sap_get_affiliate');
add_action('wp_ajax_nopriv_get_affiliate', 'sap_get_affiliate');

function sap_get_affiliate() {
    check_ajax_referer('sap_nonce', 'nonce');
    $links = json_decode(get_option('sap_settings')['affiliate_links'] ?? '[]', true);
    $random = $links[array_rand($links)] ?? array('text' => 'Great deal!', 'url' => 'https://example.com');
    wp_send_json_success($random);
}

// Inline CSS
add_action('wp_head', 'sap_inline_css');
function sap_inline_css() {
?>
<style>
.sap-popup { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999; }
.sap-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); }
.sap-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; max-width: 400px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
.sap-close { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #999; }
.sap-btn { background: #0073aa; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; font-size: 16px; }
.sap-btn:hover { background: #005a87; }
</style>
<?php
}

// Inline JS
add_action('wp_footer', 'sap_inline_js', 100);
function sap_inline_js() {
?>
<script>
jQuery(document).ready(function($) {
    var popup = $('#sap-popup');
    var settings = sap_settings;
    var shown = false;

    function showPopup() {
        if (shown) return;
        shown = true;
        $.post(settings.ajax_url, {action: 'get_affiliate', nonce: settings.nonce}, function(res) {
            if (res.success) {
                $('.sap-affiliate').html('<img src="' + (res.data.image || '') + '" style="max-width:100%;"><p>' + res.data.text + '</p><a href="' + res.data.url + '" target="_blank">Shop Now</a>');
                $('.sap-message').hide();
            }
        });
        popup.fadeIn();
    }

    setTimeout(showPopup, <?php echo get_option('sap_settings')['delay'] ?? 5000; ?>);

    $(document).on('click', '.sap-close, .sap-overlay, .sap-btn', function() {
        popup.fadeOut();
    });

    // Exit intent
    if ('exit' === '<?php echo get_option('sap_settings')['trigger'] ?? 'time'; ?>') {
        $(document).on('mouseleave', function(e) {
            if (e.clientY < 0) showPopup();
        });
    }
});
</script>
<?php
}
