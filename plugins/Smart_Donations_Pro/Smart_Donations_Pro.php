/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Collect donations easily with customizable buttons, goals, and payments.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationsPro {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('smart_donation', [$this, 'donation_shortcode']);
        add_action('wp_ajax_process_donation', [$this, 'process_donation']);
        add_action('wp_ajax_nopriv_process_donation', [$this, 'process_donation']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (get_option('smart_donations_db_version') != '1.0') {
            $this->create_table();
        }
    }

    public function activate() {
        $this->create_table();
        update_option('smart_donations_db_version', '1.0');
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'smart_donations';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            donor_email varchar(100) NOT NULL,
            date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-donations', plugin_dir_url(__FILE__) . 'smart-donations.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('smart-donations', plugin_dir_url(__FILE__) . 'smart-donations.css', [], '1.0.0');
        wp_localize_script('smart-donations', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => 'default',
            'goal' => 1000,
            'title' => 'Support Us',
            'button_text' => 'Donate Now',
            'paypal_email' => get_option('smart_donations_paypal_email', ''),
        ], $atts);

        global $wpdb;
        $table = $wpdb->prefix . 'smart_donations';
        $total = $wpdb->get_var("SELECT SUM(amount) FROM $table");
        $total = $total ? floatval($total) : 0;
        $progress = min(100, ($total / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="smart-donation" data-id="<?php echo esc_attr($atts['id']); ?>" data-paypal="<?php echo esc_attr($atts['paypal_email']); ?>">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="progress-bar">
                <div class="progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p>$<?php echo number_format($total, 2); ?> / $<?php echo number_format($atts['goal'], 2); ?> raised</p>
            <input type="number" class="donation-amount" min="1" step="0.01" placeholder="Enter amount">
            <button class="donate-btn"><?php echo esc_html($atts['button_text']); ?></button>
            <div class="donation-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        if (!wp_verify_nonce($_POST['nonce'], 'smart_donation_nonce')) {
            wp_die('Security check failed');
        }

        $amount = floatval(sanitize_text_field($_POST['amount']));
        $email = sanitize_email($_POST['email']);

        if ($amount < 1 || !is_email($email)) {
            wp_send_json_error('Invalid amount or email');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'smart_donations';
        $wpdb->insert($table, ['amount' => $amount, 'donor_email' => $email]);

        // PayPal integration (simplified - use PayPal API in pro version)
        $paypal = get_option('smart_donations_paypal_email');
        if ($paypal) {
            $paypal_url = "https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=" . urlencode($paypal) . "&amount=" . $amount . "&item_name=Donation";
            wp_send_json_success(['redirect' => $paypal_url]);
        } else {
            wp_send_json_success('Thank you for your donation!');
        }
    }
}

new SmartDonationsPro();

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donations', 'Smart Donations', 'manage_options', 'smart-donations', 'smart_donations_admin');
    });
}

function smart_donations_admin() {
    if (isset($_POST['paypal_email'])) {
        update_option('smart_donations_paypal_email', sanitize_email($_POST['paypal_email']));
    }
    $paypal_email = get_option('smart_donations_paypal_email', '');
    echo '<div class="wrap"><h1>Smart Donations Settings</h1><form method="post"><p>PayPal Email: <input type="email" name="paypal_email" value="' . esc_attr($paypal_email) . '"></p><p><input type="submit" value="Save"></p></form></div>';
}

// Note: Add smart-donations.js and smart-donations.css files to plugin directory for full functionality.
// JS example: jQuery('.donate-btn').click(function() { /* AJAX call */ });
// CSS example: .progress-bar { background: #eee; height: 20px; } .progress { background: green; height: 100%; }
?>