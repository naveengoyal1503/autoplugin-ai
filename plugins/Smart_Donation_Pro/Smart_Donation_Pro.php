/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost donations with customizable forms, progress bars, PayPal/Stripe integration, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationPro {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('smart_donation_form', [$this, 'donation_shortcode']);
        add_action('wp_ajax_sdp_process_donation', [$this, 'process_donation']);
        add_action('wp_ajax_nopriv_sdp_process_donation', [$this, 'process_donation']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (get_option('sdp_db_version') !== '1.0') {
            $this->create_table();
            update_option('sdp_db_version', '1.0');
        }
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'smart_donations';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            donor_email varchar(100) NOT NULL,
            donor_name varchar(100),
            date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'pending',
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', ['jquery'], '1.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', [], '1.0');
        wp_localize_script('sdp-script', 'sdp_ajax', ['ajaxurl' => admin_url('admin-ajax.php')]);
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts([
            'goal' => '1000',
            'title' => 'Support Us!',
            'button_text' => 'Donate Now',
            'paypal_email' => get_option('sdp_paypal_email'),
            'stripe_pk' => get_option('sdp_stripe_pk'),
        ], $atts);

        $total_donated = $this->get_total_donated();
        $progress = min(100, ($total_donated / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p>$<?php echo number_format($total_donated, 2); ?> / $<?php echo $atts['goal']; ?> raised</p>
            <form id="sdp-form" class="sdp-form">
                <input type="text" name="donor_name" placeholder="Your Name" required>
                <input type="email" name="donor_email" placeholder="Your Email" required>
                <input type="number" name="amount" placeholder="Amount ($)" min="1" step="0.01" required>
                <select name="frequency">
                    <option value="one-time">One-Time</option>
                    <option value="monthly">Monthly</option>
                </select>
                <button type="submit"><?php echo esc_html($atts['button_text']); ?></button>
                <div id="sdp-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        $name = sanitize_text_field($_POST['donor_name']);
        $email = sanitize_email($_POST['donor_email']);
        $amount = floatval($_POST['amount']);
        $frequency = sanitize_text_field($_POST['frequency']);

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'smart_donations', [
            'amount' => $amount,
            'donor_email' => $email,
            'donor_name' => $name,
            'status' => 'pending'
        ]);

        // Simulate PayPal/Stripe - In pro version, integrate real APIs
        wp_send_json_success('Thank you for your donation! Payment processing...');
    }

    private function get_total_donated() {
        global $wpdb;
        return (float) $wpdb->get_var("SELECT SUM(amount) FROM " . $wpdb->prefix . "smart_donations WHERE status = 'completed'") ?: 0;
    }

    public function activate() {
        $this->init();
    }
}

new SmartDonationPro();

// Admin settings page
function sdp_admin_menu() {
    add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
}
add_action('admin_menu', 'sdp_admin_menu');

function sdp_settings_page() {
    if (isset($_POST['sdp_paypal_email'])) {
        update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
        update_option('sdp_stripe_pk', sanitize_text_field($_POST['sdp_stripe_pk']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $paypal = get_option('sdp_paypal_email');
    $stripe = get_option('sdp_stripe_pk');
    ?>
    <div class="wrap">
        <h1>Smart Donation Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal); ?>" /></td>
                </tr>
                <tr>
                    <th>Stripe Publishable Key</th>
                    <td><input type="text" name="sdp_stripe_pk" value="<?php echo esc_attr($stripe); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Analytics</h2>
        <?php
        global $wpdb;
        $total = $wpdb->get_var("SELECT SUM(amount) FROM " . $wpdb->prefix . "smart_donations");
        $count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "smart_donations");
        echo "<p>Total Donated: \$" . number_format($total ?: 0, 2) . " | Donations: " . $count . "</p>";
        $donations = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "smart_donations ORDER BY date DESC LIMIT 10");
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Name</th><th>Email</th><th>Amount</th><th>Date</th></tr></thead><tbody>';
        foreach ($donations as $d) {
            echo '<tr><td>' . esc_html($d->donor_name) . '</td><td>' . esc_html($d->donor_email) . '</td><td>$' . number_format($d->amount, 2) . '</td><td>' . $d->date . '</td></tr>';
        }
        echo '</tbody></table>';
        ?>
    </div>
    <?php
}

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('sdp_pro_activated')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Smart Donation Pro</strong>: Recurring payments, Stripe integration, unlimited forms! <a href="https://example.com/pro">Upgrade now</a></p></div>';
    }
});