/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site revenue with smart donation prompts, progress bars, and easy payment integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
        add_shortcode('sdp_donation', array($this, 'donation_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_paypal_email') || get_option('sdp_stripe_key')) {
            add_filter('the_content', array($this, 'inject_donation_prompt'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function inject_donation_prompt($content) {
        if (is_single() && !is_admin()) {
            $prompt = '<div id="sdp-prompt" style="display:none; position:fixed; bottom:20px; right:20px; background:#007cba; color:white; padding:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.3); max-width:300px; z-index:9999;">
                <h3>Support This Content!</h3>
                <p>Buy me a coffee or donate any amount.</p>
                <input type="number" id="sdp-amount" placeholder="5" min="1" step="0.01" style="width:100%; padding:8px; margin:5px 0;">
                <select id="sdp-method" style="width:100%; padding:8px; margin:5px 0;">
                    <option value="paypal">PayPal</option>
                    <option value="stripe">Stripe</option>
                </select>
                <button id="sdp-donate-btn" class="button button-primary" style="width:100%; padding:10px;">Donate Now</button>
                <button id="sdp-close" style="float:right; background:none; border:none; color:white; font-size:20px; cursor:pointer;">&times;</button>
            </div>';
            $content .= $prompt;
        }
        return $content;
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('goal' => '1000', 'current' => '250'), $atts);
        $progress = ($atts['current'] / $atts['goal']) * 100;
        return '<div class="sdp-progress-container" style="margin:20px 0; background:#f0f0f0; border-radius:10px; overflow:hidden;">
            <p><strong>Donation Goal: $' . $atts['goal'] . '</strong> (Current: $' . $atts['current'] . ')</p>
            <div class="sdp-progress-bar" style="background:#007cba; height:20px; width:' . $progress . '%; transition:width 0.5s;"></div>
            ' . do_shortcode('[sdp_donation_button]') . '
        </div>';
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        $method = sanitize_text_field($_POST['method']);
        if ($method === 'paypal') {
            $paypal_email = get_option('sdp_paypal_email');
            if ($paypal_email) {
                $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($paypal_email) . '&amount=' . urlencode($amount) . '&item_name=Donation&currency_code=USD&return=' . urlencode(get_site_url());
                wp_send_json_success(array('redirect' => $paypal_url));
            }
        } elseif ($method === 'stripe') {
            // Stripe integration placeholder (requires pro version for full setup)
            wp_send_json_error('Stripe requires Pro version.');
        }
        wp_die();
    }

    public function activate() {
        add_option('sdp_show_prompt', '1');
    }
}

new SmartDonationPro();

// Admin settings page
function sdp_admin_menu() {
    add_options_page('Smart Donation Pro Settings', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
}
add_action('admin_menu', 'sdp_admin_menu');

function sdp_settings_page() {
    if (isset($_POST['sdp_paypal_email'])) {
        update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Smart Donation Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr(get_option('sdp_paypal_email')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Show Floating Prompt</th>
                    <td><input type="checkbox" name="sdp_show_prompt" <?php checked(get_option('sdp_show_prompt'), '1'); ?>></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><strong>Pro Features:</strong> Stripe integration, analytics, custom prompts, goal tracking. <a href="https://example.com/pro">Upgrade Now</a></p>
    </div>
    <?php
}

// Inline scripts and styles
add_action('wp_head', function() {
    echo '<style>
    #sdp-prompt { font-family: Arial, sans-serif; }
    #sdp-prompt button { cursor: pointer; }
    .sdp-progress-bar { border-radius: 10px; }
    </style>';
    echo '<script>
    jQuery(document).ready(function($) {
        $("#sdp-close").click(function() { $("#sdp-prompt").fadeOut(); });
        setTimeout(function() { $("#sdp-prompt").fadeIn(); }, 5000);
        $("#sdp-donate-btn").click(function() {
            var amount = $("#sdp-amount").val();
            var method = $("#sdp-method").val();
            $.post(sdp_ajax.ajax_url, {action: "sdp_donate", amount: amount, method: method, nonce: sdp_ajax.nonce}, function(res) {
                if (res.success) { window.location = res.data.redirect; }
                else { alert(res.data); }
            });
        });
    });
    </script>';
});