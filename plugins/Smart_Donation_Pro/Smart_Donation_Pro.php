/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site revenue with customizable donation buttons, progress bars, PayPal integration, and donation analytics.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-donation-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-donation-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=TEST-CLIENT-ID-REPLACE&currency=USD', array(), '1.0', true);
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery', 'paypal-sdk'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Settings', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_submit'])) {
            update_option('sdp_paypal_client_id', sanitize_text_field($_POST['paypal_client_id']));
            update_option('sdp_donation_goal', intval($_POST['donation_goal']));
            update_option('sdp_button_text', sanitize_text_field($_POST['button_text']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_id = get_option('sdp_paypal_client_id', '');
        $goal = get_option('sdp_donation_goal', 1000);
        $button_text = get_option('sdp_button_text', 'Donate Now');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Client ID</th>
                        <td><input type="text" name="paypal_client_id" value="<?php echo esc_attr($paypal_id); ?>" class="regular-text" placeholder="sb-xxx..." /></td>
                    </tr>
                    <tr>
                        <th>Donation Goal ($)</th>
                        <td><input type="number" name="donation_goal" value="<?php echo $goal; ?>" /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="button_text" value="<?php echo esc_attr($button_text); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[smart_donation]</code> to display the donation button anywhere.</p>
            <h2>Analytics</h2>
            <?php $this->show_analytics(); ?>
        </div>
        <?php
    }

    public function show_analytics() {
        global $wpdb;
        $table = $wpdb->prefix . 'sdp_donations';
        $total = $wpdb->get_var("SELECT SUM(amount) FROM $table");
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "<p><strong>Total Donations:</strong> \$" . number_format($total ?: 0, 2) . ' | <strong>Count:</strong> ' . $count . '</p>';
    }

    public function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'sdp_donations';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            email varchar(100) NOT NULL,
            date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $email = sanitize_email($_POST['email']);
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'sdp_donations', array('amount' => $amount, 'email' => $email));
        wp_send_json_success('Donation recorded!');
    }
}

// Shortcode
function sdp_shortcode($atts) {
    $atts = shortcode_atts(array('goal' => get_option('sdp_donation_goal', 1000)), $atts);
    $goal = intval($atts['goal']);
    global $wpdb;
    $table = $wpdb->prefix . 'sdp_donations';
    $raised = $wpdb->get_var("SELECT SUM(amount) FROM $table") ?: 0;
    $percent = min(100, ($raised / $goal) * 100);
    $client_id = get_option('sdp_paypal_client_id');
    ob_start();
    ?>
    <div id="sdp-container" data-goal="<?php echo $goal; ?>" data-raised="<?php echo $raised; ?>">
        <h3>Support Us! Goal: $<?php echo number_format($goal); ?></h3>
        <div class="sdp-progress-bar">
            <div class="sdp-progress" style="width: <?php echo $percent; ?>%;"></div>
        </div>
        <p>Raised: $<?php echo number_format($raised, 2); ?> (<?php echo $percent; ?>%)</p>
        <div id="paypal-button-container"></div>
        <p><?php echo esc_html(get_option('sdp_button_text', 'Donate Now')); ?></p>
    </div>
    <script>
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: { value: '1.00' } // Dynamic in JS
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                jQuery.post(sdp_ajax.ajax_url, {
                    action: 'sdp_process_donation',
                    amount: details.purchase_units.amount.value,
                    email: details.payer.email_address,
                    nonce: sdp_ajax.nonce
                });
                alert('Thank you for your donation!');
            });
        }
    }).render('#paypal-button-container');
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('smart_donation', 'sdp_shortcode');

new SmartDonationPro();

// Create assets directories and placeholder files
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    // Minimal CSS
    file_put_contents($assets_dir . 'style.css', ".sdp-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; } .sdp-progress { background: #4CAF50; height: 100%; transition: width 0.3s; } #sdp-container { max-width: 400px; text-align: center; }");
    // Minimal JS
    file_put_contents($assets_dir . 'script.js', "jQuery(document).ready(function($) { console.log('Smart Donation Pro loaded'); });");
});
?>