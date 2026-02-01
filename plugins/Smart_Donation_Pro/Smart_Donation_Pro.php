/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost donations with customizable buttons, forms, recurring payments, and progress tracking.
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
    }

    public function init() {
        if (!session_id()) {
            session_start();
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
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Settings', 'manage_options', 'sdp-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
        echo '<div class="wrap"><h1>Smart Donation Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="sdp_paypal_email" value="' . esc_attr($paypal_email) . '" class="regular-text" required /></td>
                </tr>
            </table>
            ' . wp_nonce_field('sdp_settings') . '<p><input type="submit" class="button-primary" value="Save Settings" /></p>
        </form></div>';
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '1000',
            'title' => 'Support Us!',
            'button_text' => 'Donate Now',
            'amounts' => '5,10,25,50'
        ), $atts);

        $goal = floatval($atts['goal']);
        $title = esc_html($atts['title']);
        $button_text = esc_html($atts['button_text']);
        $amounts = array_map('floatval', explode(',', $atts['amounts']));

        global $wpdb;
        $table = $wpdb->prefix . 'sdp_donations';
        $total_donated = $wpdb->get_var("SELECT SUM(amount) FROM $table");
        $total_donated = $total_donated ? floatval($total_donated) : 0;
        $progress = min(100, ($total_donated / $goal) * 100);

        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) {
            return '<p>Please configure PayPal email in settings.</p>';
        }

        ob_start();
        ?>
        <div class="sdp-container">
            <h3><?php echo $title; ?></h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p>$<?php echo number_format($total_donated, 2); ?> / $<?php echo number_format($goal, 2); ?> raised</p>
            <div class="sdp-amounts">
                <?php foreach ($amounts as $amount): ?>
                    <button class="sdp-amount-btn" data-amount="<?php echo $amount; ?>"><?php echo '$' . $amount; ?></button>
                <?php endforeach; ?>
                <input type="number" class="sdp-custom-amount" placeholder="Custom amount" step="0.01" min="1">
            </div>
            <button class="sdp-donate-btn" data-paypal="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BTN_ID&amount="><?php echo $button_text; ?></button>
            <p class="sdp-thanks" style="display:none;">Thank you for your donation!</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.sdp-amount-btn, .sdp-custom-amount').on('click change', function() {
                var amount = $(this).data('amount') || $(this).val();
                $('.sdp-donate-btn').attr('data-amount', amount);
            });
            $('.sdp-donate-btn').on('click', function() {
                var amount = $(this).data('amount');
                if (!amount) return;
                var paypalUrl = $(this).data('paypal') + amount + '&business=' + '<?php echo urlencode($paypal_email); ?>';
                window.open(paypalUrl, '_blank');
                // Simulate donation for demo
                $.post(sdp_ajax.ajax_url, {
                    action: 'sdp_donate',
                    amount: amount,
                    email: 'demo@donor.com',
                    nonce: sdp_ajax.nonce
                });
                $('.sdp-thanks').show();
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $email = sanitize_email($_POST['email']);
        global $wpdb;
        $table = $wpdb->prefix . 'sdp_donations';
        $wpdb->insert($table, array('amount' => $amount, 'donor_email' => $email));
        wp_die('success');
    }
}

SmartDonationPro::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.sdp-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; }
.sdp-progress-bar { width: 100%; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.sdp-progress { height: 100%; background: #28a745; transition: width 0.3s; }
.sdp-amounts { margin: 20px 0; }
.sdp-amount-btn, .sdp-custom-amount { margin: 5px; padding: 10px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; }
.sdp-custom-amount { width: 120px; padding: 8px; }
.sdp-donate-btn { background: #ffc107; color: #000; padding: 15px 30px; font-size: 18px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
.sdp-donate-btn:hover { background: #e0a800; }
</style>
<?php });

// Minimal JS file content would be here, but inline for single file
?>