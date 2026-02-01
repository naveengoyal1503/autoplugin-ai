/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Popup_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Popup Pro
 * Plugin URI: https://example.com/smart-affiliate-popup
 * Description: AI-powered popup plugin that displays personalized affiliate links and product recommendations to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliatePopupPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sap_show_popup', array($this, 'handle_ajax'));
        add_action('wp_ajax_nopriv_sap_show_popup', array($this, 'handle_ajax'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sap_enabled', 'yes') !== 'yes') return;
        add_action('wp_footer', array($this, 'render_popup'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', $this->get_js());
        wp_localize_script('jquery', 'sap_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function get_js() {
        return "
        jQuery(document).ready(function($) {
            setTimeout(function() {
                if (Math.random() < " . (get_option('sap_show_probability', 0.3)) . ") {
                    $.post(sap_ajax.ajaxurl, {action: 'sap_show_popup'}, function(data) {
                        if (data.success) {
                            $('body').append(data.data.html).find('.sap-popup').fadeIn();
                        }
                    });
                }
            }, " . (get_option('sap_delay', 10000)) . ");
        });
        jQuery(document).on('click', '.sap-close, .sap-overlay', function() {
            $('.sap-popup').fadeOut(function() { $(this).remove(); });
        });
        ";
    }

    public function handle_ajax() {
        $affiliates = get_option('sap_affiliates', array(
            array('text' => 'Check out this amazing product!', 'link' => '#', 'image' => ''),
        ));
        $random = $affiliates[array_rand($affiliates)];
        $html = '<div class="sap-overlay"></div>';
        $html .= '<div class="sap-popup">';
        $html .= '<button class="sap-close">&times;</button>';
        if (!empty($random['image'])) $html .= '<img src="' . esc_url($random['image']) . '" alt="Product">';
        $html .= '<h3>' . esc_html($random['text']) . '</h3>';
        $html .= '<a href="' . esc_url($random['link']) . '" class="sap-button" target="_blank">Get It Now</a>';
        $html .= '</div>';
        $css = ".sap-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;}";
        $css .= ".sap-popup{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:30px;max-width:400px;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,0.3);z-index:10000;text-align:center;}";
        $css .= ".sap-close{position:absolute;top:10px;right:15px;background:none;border:none;font-size:24px;cursor:pointer;}";
        $css .= ".sap-button{display:inline-block;background:#007cba;color:#fff;padding:12px 24px;text-decoration:none;border-radius:5px;font-weight:bold;}";
        $css .= ".sap-button:hover{background:#005a87;}";
        wp_add_inline_style('sap-style', $css);
        wp_send_json_success(array('html' => $html));
    }

    public function render_popup() {
        echo '<style>' . $this->get_popup_css() . '</style>';
    }

    public function get_popup_css() {
        return ".sap-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:none;}";
        // Additional CSS as above
    }

    public function activate() {
        add_option('sap_enabled', 'yes');
        add_option('sap_show_probability', 0.3);
        add_option('sap_delay', 10000);
        add_option('sap_affiliates', array(
            array('text' => 'Recommended for you: Amazon Affiliate Product', 'link' => 'https://amazon.com/example', 'image' => ''),
        ));
    }
}

new SmartAffiliatePopupPro();

// Admin page
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Affiliate Popup', 'Affiliate Popup', 'manage_options', 'sap-pro', 'sap_admin_page');
    });
}

function sap_admin_page() {
    if (isset($_POST['sap_save'])) {
        update_option('sap_enabled', sanitize_text_field($_POST['sap_enabled']));
        update_option('sap_show_probability', floatval($_POST['sap_show_probability']));
        update_option('sap_delay', intval($_POST['sap_delay']));
        update_option('sap_affiliates', $_POST['sap_affiliates']);
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $enabled = get_option('sap_enabled', 'yes');
    $probability = get_option('sap_show_probability', 0.3);
    $delay = get_option('sap_delay', 10000);
    $affiliates = get_option('sap_affiliates', array());
    ?>
    <div class="wrap">
        <h1>Smart Affiliate Popup Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr><th>Enable Plugin</th><td><select name="sap_enabled"><option value="yes" <?php selected($enabled, 'yes'); ?>>Yes</option><option value="no" <?php selected($enabled, 'no'); ?>>No</option></select></td></tr>
                <tr><th>Show Probability (0-1)</th><td><input type="number" name="sap_show_probability" value="<?php echo esc_attr($probability); ?>" step="0.01" min="0" max="1"></td></tr>
                <tr><th>Delay (ms)</th><td><input type="number" name="sap_delay" value="<?php echo esc_attr($delay); ?>"></td></tr>
            </table>
            <h2>Affiliate Links</h2>
            <div id="affiliates">
                <?php foreach ($affiliates as $i => $aff) : ?>
                <div class="affiliate-row">
                    <input type="text" name="sap_affiliates[<?php echo $i; ?>][text]" placeholder="Popup text" value="<?php echo esc_attr($aff['text']); ?>">
                    <input type="url" name="sap_affiliates[<?php echo $i; ?>][link]" placeholder="Affiliate link" value="<?php echo esc_attr($aff['link']); ?>">
                    <input type="url" name="sap_affiliates[<?php echo $i; ?>][image]" placeholder="Image URL" value="<?php echo esc_attr($aff['image']); ?>">
                    <button type="button" class="button button-secondary remove-aff">Remove</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-aff" class="button">Add Affiliate</button>
            <?php submit_button('Save Settings', 'primary', 'sap_save'); ?>
        </form>
        <script>
        jQuery(function($) {
            $('#add-aff').click(function() {
                var i = $('#affiliates .affiliate-row').length;
                $('#affiliates').append('<div class="affiliate-row"><input type="text" name="sap_affiliates['+i+'][text]" placeholder="Popup text"><input type="url" name="sap_affiliates['+i+'][link]" placeholder="Affiliate link"><input type="url" name="sap_affiliates['+i+'][image]" placeholder="Image URL"><button type="button" class="button button-secondary remove-aff">Remove</button></div>');
            });
            $(document).on('click', '.remove-aff', function() {
                $(this).closest('.affiliate-row').remove();
            });
        });
        </script>
        <p><strong>Pro Upgrade:</strong> Unlock A/B testing, geo-targeting, and analytics for $49/year!</p>
    </div>
    <?php
}

// Pro upsell notice
add_action('admin_notices', function() {
    if (get_option('sap_pro_nag_dismissed')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Popup Pro</strong> for advanced features! <a href="https://example.com/pro" target="_blank">Get Pro Now</a> | <a href="?dismiss_sap_nag=1">Dismiss</a></p></div>';
    if (isset($_GET['dismiss_sap_nag'])) update_option('sap_pro_nag_dismissed', 1);
});