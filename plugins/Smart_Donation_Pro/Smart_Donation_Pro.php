/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Create customizable donation forms with tiers and PayPal integration for easy site monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        $this->options = get_option('sdp_options', array(
            'paypal_email' => '',
            'tiers' => json_encode(array(
                array('name' => 'Coffee', 'amount' => 5),
                array('name' => 'Lunch', 'amount' => 20),
                array('name' => 'Dinner', 'amount' => 50)
            )),
            'custom_amount' => 'yes',
            'thankyou_message' => 'Thank you for your donation!'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('sdp_options', $_POST['sdp_options']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $options = $this->options;
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_options[paypal_email]" value="<?php echo esc_attr($options['paypal_email']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Donation Tiers (JSON)</th>
                        <td><textarea name="sdp_options[tiers]" rows="5" cols="50"><?php echo esc_textarea($options['tiers']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Allow Custom Amount</th>
                        <td><input type="checkbox" name="sdp_options[custom_amount]" <?php checked($options['custom_amount'], 'yes'); ?> value="yes" /></td>
                    </tr>
                    <tr>
                        <th>Thank You Message</th>
                        <td><textarea name="sdp_options[thankyou_message]"><?php echo esc_textarea($options['thankyou_message']); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $tiers = json_decode($this->options['tiers'], true);
        ob_start();
        ?>
        <div id="sdp-form-<?php echo esc_attr($atts['id']); ?>" class="sdp-donation-form">
            <h3>Support Us!</h3>
            <form id="sdp-form">
                <div class="sdp-tiers">
                    <?php foreach ($tiers as $tier): ?>
                        <label><input type="radio" name="amount" value="<?php echo $tier['amount']; ?>" data-name="<?php echo esc_attr($tier['name']); ?>"> <?php echo esc_html($tier['name']); ?> ($<?php echo $tier['amount']; ?>)</label><br>
                    <?php endforeach; ?>
                    <?php if ($this->options['custom_amount'] === 'yes'): ?>
                        <label>Custom: $<input type="number" name="custom_amount" min="1" step="0.01"></label>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="action" value="sdp_process_donation">
                <?php wp_nonce_field('sdp_nonce'); ?>
                <button type="submit">Donate via PayPal</button>
            </form>
            <div id="sdp-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = isset($_POST['custom_amount']) && $_POST['custom_amount'] > 0 ? $_POST['custom_amount'] : $_POST['amount'];
        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($this->options['paypal_email']) .
                      '&item_name=Donation&item_number=' . time() . '&amount=' . urlencode($amount) . '&currency_code=USD&return=' . urlencode(get_site_url() . '?sdp_thanks=1');
        wp_send_json_success(array('redirect' => $paypal_url));
    }
}

new SmartDonationPro();

// Minimal CSS
$sdp_css = '#sdp-style { display: none; }
.sdp-donation-form { max-width: 400px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
.sdp-tiers label { display: block; margin: 10px 0; }
#sdp-message { margin-top: 10px; padding: 10px; }';
wp_add_inline_style('sdp-style', $sdp_css);

// Minimal JS
$sdp_js = "jQuery(document).ready(function($) {
    $('#sdp-form').on('submit', function(e) {
        e.preventDefault();
        $.post(sdp_ajax.ajax_url, $(this).serialize(), function(res) {
            if (res.success) {
                window.location = res.data.redirect;
            } else {
                $('#sdp-message').html('<p>Error processing donation.</p>');
            }
        });
    });
});";
wp_add_inline_script('sdp-script', $sdp_js);