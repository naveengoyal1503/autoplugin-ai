/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add customizable donation buttons and forms to monetize your WordPress site.
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
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'goal' => '1000',
            'currency' => '$',
            'paypal_email' => get_option('sdp_paypal_email'),
        ), $atts);

        $current = get_option('sdp_total_donations', 0);
        $progress = min(100, ($current / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container" style="max-width: 400px; margin: 20px 0;">
            <?php if ($atts['goal']): ?>
            <div class="sdp-progress" style="background: #f0f0f0; border-radius: 10px; height: 20px; margin-bottom: 20px;">
                <div class="sdp-progress-bar" style="background: #4CAF50; height: 100%; width: <?php echo $progress; ?>%; border-radius: 10px; transition: width 0.3s;"></div>
            </div>
            <div style="text-align: center; margin-bottom: 10px;">
                Raised: <?php echo $atts['currency'] . number_format($current); ?> / <?php echo $atts['currency'] . number_format($atts['goal']); ?>
            </div>
            <?php endif; ?>
            <form class="sdp-form" data-paypal="<?php echo esc_attr($atts['paypal_email']); ?>">
                <input type="number" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" min="1" step="0.01" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 5px;">
                <button type="submit" class="sdp-button" style="background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 5px; width: 100%; cursor: pointer; font-size: 16px;">
                    <?php echo esc_html($atts['label']); ?> <?php echo $atts['currency']; ?>
                </button>
            </form>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">Secure payment via PayPal. No account required.</p>
        </div>
        <script>
        jQuery(function($) {
            $('.sdp-form').on('submit', function(e) {
                e.preventDefault();
                var amount = $(this).find('input[name="amount"]').val();
                var paypal = $(this).data('paypal');
                window.open('https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' + paypal + '&amount=' + amount + '&currency_code=USD&item_name=Donation', '_blank');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        $amount = floatval($_POST['amount']);
        $total = get_option('sdp_total_donations', 0) + $amount;
        update_option('sdp_total_donations', $total);
        wp_die('Thank you for your donation!');
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
        <p>Use shortcode: <code>[smart_donation amount="10" goal="1000" label="Support Us"]</code></p>
    </div>
    <?php
}

// Add CSS
add_action('wp_head', function() {
    echo '<style>.sdp-progress-bar { box-shadow: 0 2px 5px rgba(76,175,80,0.3); }</style>';
});