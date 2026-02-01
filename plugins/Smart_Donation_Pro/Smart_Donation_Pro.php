/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add donation buttons, progress bars, and payment options to monetize your WordPress site.
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
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // Plugin is set up
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'goal' => '1000',
            'currency' => '$',
            'paypal_email' => get_option('sdp_paypal_email', ''),
        ), $atts);

        $current = get_option('sdp_total_donated', 0);
        $progress = min(100, ($current / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <h3>Support This Site</h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p><?php echo $atts['currency']; ?><?php echo number_format($current); ?> / <?php echo $atts['currency']; ?><?php echo $atts['goal']; ?> raised</p>
            <div class="sdp-buttons">
                <button class="sdp-donate-btn" data-amount="5"><?php echo $atts['currency']; ?>5</button>
                <button class="sdp-donate-btn" data-amount="<?php echo $atts['amount']; ?>"><?php echo $atts['currency']; ?><?php echo $atts['amount']; ?></button>
                <button class="sdp-donate-btn custom" data-amount="10">Custom</button>
            </div>
            <form class="sdp-custom-form" style="display:none;">
                <input type="number" class="sdp-custom-amount" placeholder="Enter amount" min="1">
                <button type="button" class="sdp-pay-btn">Pay with PayPal</button>
            </form>
            <p class="sdp-message"></p>
        </div>
        <script>
        jQuery(function($) {
            $('.sdp-donate-btn').click(function() {
                var amount = $(this).data('amount');
                if ($(this).hasClass('custom')) {
                    $('.sdp-custom-form').show();
                } else {
                    window.location = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=<?php echo $atts['paypal_email']; ?>&item_name=Donation&amount=' + amount + '&currency_code=USD';
                }
            });
            $('.sdp-pay-btn').click(function() {
                var amount = $('.sdp-custom-amount').val();
                if (amount >= 1) {
                    window.location = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=<?php echo $atts['paypal_email']; ?>&item_name=Custom Donation&amount=' + amount + '&currency_code=USD';
                }
            });
        });
        </script>
        <style>
        .sdp-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; }
        .sdp-progress-bar { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
        .sdp-progress { background: #28a745; height: 100%; transition: width 0.3s; }
        .sdp-donate-btn { background: #007cba; color: white; border: none; padding: 10px 20px; margin: 5px; border-radius: 5px; cursor: pointer; }
        .sdp-donate-btn:hover { background: #005a87; }
        .sdp-custom-form { margin-top: 10px; }
        .sdp-custom-amount { padding: 8px; width: 100px; margin-right: 10px; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        // Simulate donation tracking (in pro version, integrate webhooks)
        $amount = floatval($_POST['amount']);
        $total = get_option('sdp_total_donated', 0) + $amount;
        update_option('sdp_total_donated', $total);
        wp_die('Thank you!');
    }
}

new SmartDonationPro();

// Admin settings
add_action('admin_menu', function() {
    add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
});

function sdp_settings_page() {
    if (isset($_POST['sdp_paypal_email'])) {
        update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $email = get_option('sdp_paypal_email', '');
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
        <p>Use shortcode: <code>[smart_donation amount="10" goal="1000"]</code></p>
    </div>
    <?php
}

// Reset total (admin only)
add_action('admin_init', function() {
    if (isset($_GET['sdp_reset']) && current_user_can('manage_options')) {
        update_option('sdp_total_donated', 0);
    }
});