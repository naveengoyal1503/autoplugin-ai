/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with progress bars, goals, and one-click PayPal buttons. Pro version available.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationBooster {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_shortcode('sdb_donation', array($this, 'donation_shortcode'));
        add_shortcode('sdb_progress', array($this, 'progress_shortcode'));
    }

    public function init() {
        if (get_option('sdb_goal_amount') === false) {
            update_option('sdb_goal_amount', 1000);
        }
        if (get_option('sdb_current_amount') === false) {
            update_option('sdb_current_amount', 0);
        }
        if (get_option('sdb_paypal_email') === false) {
            update_option('sdb_paypal_email', '');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function admin_menu() {
        add_options_page('Donation Booster Settings', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdb_submit'])) {
            update_option('sdb_goal_amount', sanitize_text_field($_POST['goal_amount']));
            update_option('sdb_current_amount', sanitize_text_field($_POST['current_amount']));
            update_option('sdb_paypal_email', sanitize_email($_POST['paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $goal = get_option('sdb_goal_amount', 1000);
        $current = get_option('sdb_current_amount', 0);
        $paypal = get_option('sdb_paypal_email', '');
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="goal_amount" value="<?php echo esc_attr($goal); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount</th>
                        <td><input type="number" name="current_amount" value="<?php echo esc_attr($current); ?>" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcodes: <code>[sdb_donation]</code> or <code>[sdb_progress]</code></p>
            <p><strong>Pro Upgrade:</strong> Stripe, analytics, unlimited goals - <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('amount' => '10'), $atts);
        $paypal = get_option('sdb_paypal_email');
        if (!$paypal) return 'Please set PayPal email in settings.';
        $url = 'https://www.paypal.com/donate?hosted_button_id=DUMMY&amount=' . $atts['amount'];
        return '<a href="' . esc_url($url) . '" class="sdb-donate-btn" target="_blank">Donate $' . $atts['amount'] . '</a>';
    }

    public function progress_shortcode($atts) {
        $goal = get_option('sdb_goal_amount', 1000);
        $current = get_option('sdb_current_amount', 0);
        $percent = ($current / $goal) * 100;
        ob_start();
        ?>
        <div class="sdb-progress-container">
            <div class="sdb-progress-bar">
                <div class="sdb-progress-fill" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p>$<?php echo $current; ?> / $<?php echo $goal; ?> raised (<?php echo round($percent); ?>%)</p>
            <p>Support us! <?php echo do_shortcode('[sdb_donation]'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = get_option('sdb_current_amount', 0);
        update_option('sdb_current_amount', $current + $amount);
        wp_send_json_success('Thank you for your donation!');
    }
}

new SmartDonationBooster();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
    .sdb-progress-container { background: #f9f9f9; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0; }
    .sdb-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
    .sdb-progress-fill { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s ease; }
    .sdb-donate-btn { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; }
    .sdb-donate-btn:hover { background: #005a87; }
    </style>';
});

// Dummy JS file content (in real plugin, separate file)
add_action('wp_footer', function() {
    echo '<script>
    jQuery(document).ready(function($) {
        $(".sdb-donate-btn").click(function(e) {
            e.preventDefault();
            var amount = prompt("Enter donation amount:");
            if (amount) {
                $.post(sdb_ajax.ajax_url, {
                    action: "sdb_donate",
                    amount: amount,
                    nonce: sdb_ajax.nonce
                }, function(res) {
                    if (res.success) {
                        location.reload();
                    }
                });
            }
        });
    });
    </script>';
});