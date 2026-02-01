/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Accept one-time and recurring donations via PayPal with customizable forms and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
    }

    public function init() {
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
            donor_name varchar(100),
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'pending',
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=USD', array(), '1.0', true);
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery', 'paypal-sdk'), '1.0', true);
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donations Pro', 'Donations', 'manage_options', 'smart-donations-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $donations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $wpdb->insert($table_name, array('amount' => $amount, 'donor_name' => $name, 'donor_email' => $email, 'status' => 'completed'));

        wp_send_json_success('Donation recorded!');
    }

    public static function shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'button_text' => 'Donate Now',
            'tiers' => '5,10,25,50,100'
        ), $atts);

        ob_start();
        ?>
        <div id="sdp-donation-form" class="sdp-container">
            <h3>Support Us!</h3>
            <div class="sdp-tiers">
                <?php foreach(explode(',', $atts['tiers']) as $tier): $tier = trim($tier); ?>
                    <button class="sdp-tier-btn" data-amount="<?php echo $tier; ?>"><?php echo $tier; ?>$</button>
                <?php endforeach; ?>
            </div>
            <input type="number" id="sdp-amount" placeholder="Custom amount" step="0.01" min="1">
            <input type="text" id="sdp-name" placeholder="Your Name">
            <input type="email" id="sdp-email" placeholder="Your Email">
            <div id="paypal-button-container"></div>
        </div>
        <script>
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: document.getElementById('sdp-amount').value || '10.00'
                        }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    jQuery.post(sdp_ajax.ajax_url, {
                        action: 'sdp_process_donation',
                        nonce: sdp_ajax.nonce,
                        amount: details.purchase_units.amount.value,
                        name: jQuery('#sdp-name').val(),
                        email: jQuery('#sdp-email').val()
                    });
                    alert('Thank you for your donation!');
                });
            }
        }).render('#paypal-button-container');
        </script>
        <style>
        .sdp-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .sdp-tiers { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .sdp-tier-btn { padding: 10px 15px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .sdp-tier-btn:hover { background: #005a87; }
        .sdp-tier-btn.active { background: #00a32a; }
        input { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        </style>
        <?php
        return ob_get_clean();
    }
}

new SmartDonationsPro();

// Register shortcode
add_shortcode('smart_donations', array('SmartDonationsPro', 'shortcode'));

// Admin page template (save as admin-page.php in plugin dir)
/*
<div class="wrap">
    <h1>Donations Analytics</h1>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>ID</th><th>Amount</th><th>Name</th><th>Email</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
<?php foreach($donations as $donation): ?>
            <tr><td><?php echo $donation->id; ?></td><td>$<?php echo $donation->amount; ?></td><td><?php echo esc_html($donation->donor_name); ?></td><td><?php echo esc_html($donation->donor_email); ?></td><td><?php echo $donation->timestamp; ?></td><td><?php echo $donation->status; ?></td></tr>
<?php endforeach; ?>
        </tbody>
    </table>
    <p><strong>Total Donations:</strong> $<?php echo array_sum(array_column($donations, 'amount')); ?></p>
</div>
*/

// Note: Replace YOUR_PAYPAL_CLIENT_ID with your actual PayPal Client ID. Create admin-page.php with the table code above.
?>