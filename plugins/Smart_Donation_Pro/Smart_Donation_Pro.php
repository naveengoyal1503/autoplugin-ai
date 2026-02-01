/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Add customizable donation buttons with PayPal integration to monetize your site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationPro {
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
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
    }

    public function init() {
        if (!session_id()) {
            session_start();
        }
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            donor_email varchar(100) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=USD', array(), null, true);
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery', 'paypal-sdk'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'button_text' => 'Donate Now',
            'goal' => '500',
            'title' => 'Support Us!'
        ), $atts);

        $total_donations = $this->get_total_donations();
        $progress = min(100, ($total_donations / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p class="sdp-total">$<?php echo number_format($total_donations, 2); ?> / $<?php echo $atts['goal']; ?></p>
            <div id="paypal-button-container-<?php echo uniqid(); ?>"></div>
            <button class="sdp-donate-btn" data-amount="<?php echo $atts['amount']; ?>"><?php echo esc_html($atts['button_text']); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_total_donations() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        return (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_name");
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        $email = sanitize_email($_POST['email']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $wpdb->insert($table_name, array('amount' => $amount, 'donor_email' => $email));

        wp_send_json_success('Thank you for your donation!');
    }
}

SmartDonationPro::get_instance();

// Minimal JS file content (in real plugin, separate file)
/*
$(document).ready(function() {
    $('.sdp-donate-btn').click(function() {
        var amount = $(this).data('amount');
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
                    $.post(sdp_ajax.ajax_url, {
                        action: 'sdp_donate',
                        amount: amount,
                        email: details.payer.email_address,
                        nonce: sdp_ajax.nonce
                    });
                });
            }
        }).render('#paypal-button-container-' + $(this).closest('.sdp-container').find('[id^="paypal-button-container-"]').attr('id'));
    });
});
*/

// Minimal CSS
/*
.sdp-container { max-width: 400px; margin: 20px auto; text-align: center; }
.sdp-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.sdp-progress { background: #4CAF50; height: 100%; transition: width 0.3s; }
.sdp-donate-btn { background: #007cba; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; }
.sdp-donate-btn:hover { background: #005a87; }
*/