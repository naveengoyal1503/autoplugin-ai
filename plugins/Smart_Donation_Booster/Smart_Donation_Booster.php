/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with progress bars, goals, and easy payment buttons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationBooster {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('donation_goal', array($this, 'donation_goal_shortcode'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'handle_donation'));
    }

    public function init() {
        if (get_option('sdb_paypal_email')) {
            // PayPal integration ready
        }
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'sdb_donations';
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
        wp_enqueue_script('sdb-js', plugin_dir_url(__FILE__) . 'sdb.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-css', plugin_dir_url(__FILE__) . 'sdb.css', array(), '1.0.0');
        wp_localize_script('sdb-js', 'sdb_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function admin_menu() {
        add_options_page('Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdb_paypal_email'])) {
            update_option('sdb_paypal_email', sanitize_email($_POST['sdb_paypal_email']));
            update_option('sdb_goal_amount', floatval($_POST['sdb_goal_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal = get_option('sdb_paypal_email', '');
        $goal = get_option('sdb_goal_amount', 1000);
        echo '<div class="wrap"><h1>Smart Donation Booster Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr><th>PayPal Email</th><td><input type="email" name="sdb_paypal_email" value="' . esc_attr($paypal) . '" /></td></tr>
                <tr><th>Monthly Goal ($)</th><td><input type="number" name="sdb_goal_amount" value="' . esc_attr($goal) . '" step="0.01" /></td></tr>
            </table>
            ' . wp_nonce_field('sdb_settings') . '<p><input type="submit" class="button-primary" value="Save" /></p>
        </form></div>';
    }

    public function donation_goal_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $goal = get_option('sdb_goal_amount', 1000);
        global $wpdb;
        $table = $wpdb->prefix . 'sdb_donations';
        $total = $wpdb->get_var("SELECT SUM(amount) FROM $table WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())") ?: 0;
        $percent = min(100, ($total / $goal) * 100);
        $paypal = get_option('sdb_paypal_email');
        ob_start();
        ?>
        <div class="sdb-container" id="sdb-<?php echo esc_attr($atts['id']); ?>">
            <h3>Support Us! Goal: $<?php echo number_format($goal); ?> <span id="sdb-current"><?php echo number_format($total, 2); ?></span></h3>
            <div class="sdb-progress" style="width: 100%; height: 30px; background: #f0f0f0; border-radius: 15px; overflow: hidden;">
                <div class="sdb-bar" style="width: <?php echo $percent; ?>%; height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.5s;"></div>
            </div>
            <p><?php echo $percent; ?>% reached this month!</p>
            <?php if ($paypal): ?>
            <form action="https://www.paypal.com/donate" method="post" target="_top">
                <input type="hidden" name="hosted_button_id" value="YOUR_BUTTON_ID" />
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal); ?>" />
                <input type="hidden" name="currency_code" value="USD" />
                <input type="hidden" name="amount" value="5.00" />
                <input type="submit" class="sdb-donate-btn" value="Donate $5" />
            </form>
            <?php endif; ?>
            <p><small>Custom amount: $<input type="number" id="sdb-amount" min="1" step="0.01" style="width:60px;"> <button id="sdb-custom-donate" class="button">Donate</button></small></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        if ($amount < 1) wp_die('Invalid amount');
        global $wpdb;
        $table = $wpdb->prefix . 'sdb_donations';
        $wpdb->insert($table, array('amount' => $amount, 'donor_email' => sanitize_email($_POST['email'] ?? 'anonymous')));
        wp_send_json_success('Thank you for your donation!');
    }
}

new SmartDonationBooster();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
    .sdb-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 10px; background: #fff; }
    .sdb-donate-btn { background: #007cba; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
    .sdb-donate-btn:hover { background: #005a87; }
    </style>';
});

// Inline JS
add_action('wp_footer', function() {
    ?><script>
    jQuery(document).ready(function($) {
        $('#sdb-custom-donate').click(function(e) {
            e.preventDefault();
            var amount = $('#sdb-amount').val();
            $.post(sdb_ajax.ajaxurl, {
                action: 'sdb_donate',
                amount: amount,
                nonce: sdb_ajax.nonce
            }, function(res) {
                if (res.success) alert('Thank you! (Demo mode)');
            });
        });
    });
    </script><?php
});