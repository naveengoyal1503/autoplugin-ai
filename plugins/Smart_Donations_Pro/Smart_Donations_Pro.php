/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Easily add customizable donation buttons with PayPal integration, progress bars, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationsPro {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            wp_localize_script('sdp-frontend', 'sdp_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sdp_nonce')
            ));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-frontend', plugin_dir_url(__FILE__) . 'sdp-frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-frontend', plugin_dir_url(__FILE__) . 'sdp-frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Donations Pro', 'Donations', 'manage_options', 'smart-donations', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            update_option('sdp_button_text', sanitize_text_field($_POST['sdp_button_text']));
            update_option('sdp_goal_amount', floatval($_POST['sdp_goal_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
        $button_text = get_option('sdp_button_text', 'Donate Now');
        $goal_amount = get_option('sdp_goal_amount', 1000);
        $current_amount = get_option('sdp_current_amount', 0);
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'currency' => 'USD'
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) return '<p>Please configure PayPal email in settings.</p>';

        $current = get_option('sdp_current_amount', 0);
        $goal = get_option('sdp_goal_amount', 1000);
        $progress = ($current / $goal) * 100;

        ob_start();
        ?>
        <div class="sdp-container">
            <div class="sdp-progress" style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                <div class="sdp-progress-bar" style="height: 100%; background: #4CAF50; width: <?php echo $progress; ?>%; transition: width 0.3s;"></div>
            </div>
            <p><strong>$<?php echo $current; ?></strong> raised of $<?php echo $goal; ?> goal</p>
            <form id="sdp-form" method="post" action="https://www.paypal.com/donate">
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">
                <input type="hidden" name="item_name" value="<?php bloginfo('name'); ?> Donation">
                <input type="hidden" name="currency_code" value="<?php echo esc_attr($atts['currency']); ?>">
                <input type="number" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" min="1" step="0.01" style="width: 100px;">
                <input type="hidden" name="cmd" value="_xclick">
                <button type="submit" class="sdp-button" style="background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;"><?php echo esc_html(get_option('sdp_button_text', 'Donate Now')); ?></button>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#sdp-form').on('submit', function(e) {
                var amount = $('input[name="amount"]').val();
                $.post(sdp_ajax.ajax_url, {
                    action: 'sdp_donate',
                    amount: amount,
                    nonce: sdp_ajax.nonce
                }, function() {
                    location.reload();
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = get_option('sdp_current_amount', 0);
        update_option('sdp_current_amount', $current + $amount);
        wp_die();
    }

    public function activate() {
        add_option('sdp_current_amount', 0);
    }
}

SmartDonationsPro::get_instance();

// Inline styles
add_action('wp_head', function() {
    echo '<style>
    .sdp-container { max-width: 400px; margin: 20px 0; text-align: center; }
    .sdp-progress { margin-bottom: 10px; }
    .sdp-button:hover { background: #005a87; }
    </style>';
});

// Admin page template
$admin_page_content = '<div class="wrap">
<h1>Smart Donations Pro Settings</h1>
<form method="post">
<table class="form-table">
<tr><th>PayPal Email</th><td><input type="email" name="sdp_paypal_email" value="' . esc_attr($paypal_email) . '" class="regular-text"></td></tr>
<tr><th>Button Text</th><td><input type="text" name="sdp_button_text" value="' . esc_attr($button_text) . '" class="regular-text"></td></tr>
<tr><th>Goal Amount</th><td><input type="number" name="sdp_goal_amount" value="' . esc_attr($goal_amount) . '" step="0.01"></td></tr>
</table>
<?php submit_button(); ?>
</form>
<p>Use shortcode: <code>[smart_donation amount="10" currency="USD"]</code></p>
</div>';
file_put_contents(plugin_dir_path(__FILE__) . 'admin-page.php', $admin_page_content);

// Frontend JS
$js_content = 'jQuery(document).ready(function($) {
    $(".sdp-button").click(function(e) {
        e.preventDefault();
        var form = $(this).closest("form");
        var amount = form.find("input[name=\"amount\"]").val();
        $.post(sdp_ajax.ajax_url, {action: "sdp_donate", amount: amount, nonce: sdp_ajax.nonce}, function() {
            location.reload();
        });
    });
});';
file_put_contents(plugin_dir_path(__FILE__) . 'sdp-frontend.js', $js_content);

// Frontend CSS
$css_content = '.sdp-container { font-family: Arial; } .sdp-progress-bar { box-shadow: 0 0 10px rgba(76,175,80,0.5); }';
file_put_contents(plugin_dir_path(__FILE__) . 'sdp-frontend.css', $css_content);