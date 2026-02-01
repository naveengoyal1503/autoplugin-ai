/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site revenue with easy donation buttons, progress bars, and PayPal integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
        add_shortcode('sdp_donate', array($this, 'donate_shortcode'));
        add_shortcode('sdp_goal', array($this, 'goal_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // PayPal ready
        }
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function create_table() {
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

    public function donate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'currency' => '$'
        ), $atts);
        ob_start();
        ?>
        <div class="sdp-donate-btn" data-amount="<?php echo esc_attr($atts['amount']); ?>">
            <button class="sdp-button" onclick="sdpProcessDonation(<?php echo esc_attr($atts['amount']); ?>)"><?php echo esc_html($atts['currency'] . $atts['amount'] . ' - ' . $atts['label']); ?></button>
            <div id="sdp-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function goal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '1000',
            'current' => '250',
            'label' => 'Help us reach our goal!'
        ), $atts);
        $current = get_option('sdp_total_donations', 0) + (float)$atts['current'];
        $percent = min(100, ($current / (float)$atts['goal']) * 100);
        ob_start();
        ?>
        <div class="sdp-goal">
            <h3><?php echo esc_html($atts['label']); ?></h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p><?php echo esc_html($atts['currency'] . number_format($current, 2) . ' / ' . $atts['currency'] . number_format((float)$atts['goal'], 2)); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        $email = sanitize_email($_POST['email']);
        $paypal_email = get_option('sdp_paypal_email');

        if (!$paypal_email) {
            wp_send_json_error('PayPal email not configured.');
            return;
        }

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'sdp_donations', array(
            'amount' => $amount,
            'donor_email' => $email
        ));

        $total = get_option('sdp_total_donations', 0) + (float)$amount;
        update_option('sdp_total_donations', $total);

        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($paypal_email) . '&amount=' . urlencode($amount) . '&item_name=Donation to ' . get_bloginfo('name') . '&currency_code=USD&return=' . urlencode(get_site_url() . '/?donation=success');

        wp_send_json_success(array('redirect' => $paypal_url));
    }

    public function activate() {
        $this->create_table();
        add_option('sdp_total_donations', 0);
    }
}

new SmartDonationPro();

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
    });
}

function sdp_settings_page() {
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
        <h2>Usage</h2>
        <p>Use shortcodes: <code>[sdp_donate amount="20"]</code> or <code>[sdp_goal goal="1000" current="300"]</code></p>
        <h2>Total Donations</h2>
        <p><?php echo esc_html(get_option('sdp_total_donations', 0)); ?></p>
    </div>
    <?php
}

// Minimal JS (inline for single file)
function sdp_inline_js() {
    ?>
    <script>
    function sdpProcessDonation(amount) {
        var email = prompt('Enter your email for receipt:');
        if (!email) return;
        jQuery.post(sdp_ajax.ajax_url, {
            action: 'sdp_process_donation',
            amount: amount,
            email: email,
            nonce: sdp_ajax.nonce
        }, function(response) {
            if (response.success) {
                window.location = response.data.redirect;
            } else {
                alert('Error: ' + response.data);
            }
        });
    }
    </script>
    <style>
    .sdp-button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    .sdp-button:hover { background: #005a87; }
    .sdp-progress-bar { background: #ddd; height: 20px; border-radius: 10px; overflow: hidden; }
    .sdp-progress { background: #007cba; height: 100%; transition: width 0.3s; }
    .sdp-goal { max-width: 400px; }
    </style>
    <?php
}
add_action('wp_footer', 'sdp_inline_js');