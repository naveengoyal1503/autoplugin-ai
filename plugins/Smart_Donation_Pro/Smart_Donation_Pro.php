/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your site's revenue with easy donation buttons, progress bars, and payment integrations.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

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
        if (get_option('sdp_paypal_email')) {
            // PayPal integration ready
        }
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'sdp_donations';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            donor_email varchar(100) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Recent Donations</h2>
            <?php $this->show_donations(); ?>
        </div>
        <?php
    }

    private function show_donations() {
        global $wpdb;
        $table = $wpdb->prefix . 'sdp_donations';
        $donations = $wpdb->get_results("SELECT * FROM $table ORDER BY timestamp DESC LIMIT 10");
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Amount</th><th>Email</th><th>Date</th></tr></thead><tbody>';
        foreach ($donations as $don) {
            echo '<tr><td>$' . $don->amount . '</td><td>' . esc_html($don->donor_email) . '</td><td>' . $don->timestamp . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '1000',
            'title' => 'Support Us!',
            'button_text' => 'Donate Now',
        ), $atts);

        $total_donated = $this->get_total_donated();
        $goal = floatval($atts['goal']);
        $progress = min(100, ($total_donated / $goal) * 100);

        ob_start();
        ?>
        <div class="sdp-widget">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sdp-progress-container">
                <div class="sdp-progress-bar" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p class="sdp-progress-text">$<?php echo number_format($total_donated, 2); ?> / $<?php echo $atts['goal']; ?> raised</p>
            <div class="sdp-amounts">
                <button class="sdp-donate-btn" data-amount="5">$5</button>
                <button class="sdp-donate-btn" data-amount="10">$10</button>
                <button class="sdp-donate-btn" data-amount="25">$25</button>
                <button class="sdp-donate-btn" data-amount="50">$50</button>
                <input type="number" class="sdp-custom-amount" placeholder="Custom" step="1" min="1" />
                <button class="sdp-pay-btn" style="display:none;"><?php echo esc_html($atts['button_text']); ?></button>
            </div>
            <div class="sdp-form" style="display:none;">
                <input type="email" class="sdp-email" placeholder="Your email" required />
                <div id="sdp-paypal-button-container"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_total_donated() {
        global $wpdb;
        $table = $wpdb->prefix . 'sdp_donations';
        return floatval($wpdb->get_var("SELECT SUM(amount) FROM $table")) ?: 0;
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $email = sanitize_email($_POST['email']);
        if ($amount > 0 && is_email($email)) {
            global $wpdb;
            $table = $wpdb->prefix . 'sdp_donations';
            $wpdb->insert($table, array('amount' => $amount, 'donor_email' => $email));
            wp_send_json_success('Thank you for your donation!');
        }
        wp_send_json_error('Invalid donation.');
    }
}

new SmartDonationPro();

// Inline styles
add_action('wp_head', function() { ?>
<style>
.sdp-widget { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; }
.sdp-progress-container { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.sdp-progress-bar { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s; }
.sdp-progress-text { font-weight: bold; color: #333; }
.sdp-amounts { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin: 20px 0; }
.sdp-donate-btn, .sdp-custom-amount, .sdp-pay-btn { padding: 10px 15px; border: none; border-radius: 5px; background: #007cba; color: white; cursor: pointer; }
.sdp-custom-amount { background: white; border: 1px solid #ddd; color: #333; width: 80px; }
.sdp-donate-btn:hover, .sdp-pay-btn:hover { background: #005a87; }
.sdp-form { margin-top: 20px; }
.sdp-email { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
</style>
<script src="https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=USD" async></script>
<?php });

// Note: Replace YOUR_PAYPAL_CLIENT_ID with actual sandbox/live client ID from PayPal Developer Dashboard
// JS file content (sdp-script.js - would be enqueued, but inline for single file):
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.sdp-donate-btn, .sdp-custom-amount').on('click change', function() {
        var amount = $(this).data('amount') || $('.sdp-custom-amount').val();
        if (amount >= 1) {
            $('.sdp-pay-btn').show().data('amount', amount);
        }
    });

    $('.sdp-pay-btn').on('click', function() {
        var amount = $(this).data('amount');
        var email = $('.sdp-email').val();
        if (!email) return alert('Email required');

        $.post(sdp_ajax.ajaxurl, {
            action: 'sdp_process_donation',
            amount: amount,
            email: email,
            nonce: sdp_ajax.nonce
        }, function(res) {
            if (res.success) {
                $('.sdp-form').html('<p>Thank you! Processing via PayPal...</p><div id="sdp-paypal-button-container"></div>');
                paypal.Buttons({
                    createOrder: function(data, actions) {
                        return actions.order.create({
                            purchase_units: [{
                                amount: { value: amount }
                            }]
                        });
                    },
                    onApprove: function(data, actions) {
                        return actions.order.capture().then(function(details) {
                            alert('Transaction completed by ' + details.payer.name.given_name);
                        });
                    }
                }).render('#sdp-paypal-button-container');
            }
        });
    });
});
</script>
<?php });