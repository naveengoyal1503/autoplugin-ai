/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost donations with customizable buttons, progress bars, and goal tracking.
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
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // Plugin active
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '1000',
            'title' => 'Support Us!',
            'button_text' => 'Donate Now',
            'amounts' => '5,10,25,50',
        ), $atts);

        $goal = floatval($goal);
        $amounts = explode(',', $atts['amounts']);
        $current = get_option('sdp_current_donations', 0);
        $percent = min(100, ($current / $goal) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sdp-progress">
                <div class="sdp-progress-bar" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p>$<?php echo number_format($current, 0); ?> / $<?php echo number_format($goal, 0); ?> raised (<?php echo round($percent); ?>%)</p>
            <div class="sdp-amounts">
                <?php foreach ($amounts as $amount): ?>
                    <button class="sdp-amount-btn" data-amount="<?php echo trim($amount); ?>">$<?php echo trim($amount); ?></button>
                <?php endforeach; ?>
            </div>
            <div id="sdp-paypal-button"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = get_option('sdp_current_donations', 0);
        update_option('sdp_current_donations', $current + $amount);
        wp_send_json_success('Thank you for your donation!');
    }
}

new SmartDonationPro();

// Settings page
add_action('admin_menu', function() {
    add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', function() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $email = get_option('sdp_paypal_email');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($email); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    });
});

// Minimal JS (inline for single file)
add_action('wp_footer', function() {
    if (!is_admin()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.sdp-amount-btn').click(function() {
                var amount = $(this).data('amount');
                $('#sdp-paypal-button').html('<form action="https://www.paypal.com/donate" method="post" target="_blank">\n<input type="hidden" name="hosted_button_id" value="YOUR_BUTTON_ID">\n<input type="hidden" name="amount" value="' + amount + '">\n<button type="submit" class="sdp-donate-btn">Donate $' + amount + '</button>\n</form>');
                $.post(sdp_ajax.ajax_url, {
                    action: 'sdp_donate',
                    amount: amount,
                    nonce: sdp_ajax.nonce
                });
            });
        });
        </script>
        <style>
        .sdp-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; }
        .sdp-progress { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
        .sdp-progress-bar { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s; }
        .sdp-amounts { margin: 20px 0; }
        .sdp-amount-btn { background: #007cba; color: white; border: none; padding: 10px 15px; margin: 5px; border-radius: 5px; cursor: pointer; }
        .sdp-amount-btn:hover { background: #005a87; }
        .sdp-donate-btn { background: #ffc107; color: #000; padding: 12px 24px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        </style>
        <?php
    }
});