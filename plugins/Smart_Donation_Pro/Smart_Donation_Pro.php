/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Effortlessly collect donations and tips with customizable buttons and payment integrations.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
        add_shortcode('sdp_donate', array($this, 'donate_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            add_filter('script_loader_tag', array($this, 'add_defer_attribute'), 10, 2);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
    }

    public function donate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Buy Me a Coffee',
            'button_text' => 'Donate Now',
            'currency' => 'USD'
        ), $atts);

        ob_start();
        ?>
        <div class="sdp-donate-container">
            <button class="sdp-donate-btn" data-amount="<?php echo esc_attr($atts['amount']); ?>" data-label="<?php echo esc_attr($atts['label']); ?>">
                <?php echo esc_html($atts['button_text']); ?> $<?php echo esc_html($atts['amount']); ?> <?php echo esc_html($atts['currency']); ?>
            </button>
            <div id="sdp-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $email = get_option('sdp_paypal_email');
        $amount = sanitize_text_field($_POST['amount']);
        $label = sanitize_text_field($_POST['label']);

        if (!$email) {
            wp_send_json_error('PayPal email not configured.');
            return;
        }

        $paypal_url = 'https://www.paypal.com/donate/?business=' . urlencode($email) . '&item_name=' . urlencode($label) . '&amount=' . urlencode($amount) . '&currency_code=USD';

        // Log donation attempt
        $log = array(
            'time' => current_time('mysql'),
            'amount' => $amount,
            'label' => $label,
            'ip' => $_SERVER['REMOTE_ADDR']
        );
        $logs = get_option('sdp_logs', array());
        $logs[] = $log;
        update_option('sdp_logs', array_slice($logs, -50)); // Keep last 50

        wp_send_json_success(array('redirect' => $paypal_url));
    }

    public function activate() {
        add_option('sdp_paypal_email', '');
        add_option('sdp_logs', array());
    }

    public function add_defer_attribute($tag, $handle) {
        return str_replace(' src', ' defer src', $tag);
    }
}

// Admin page
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
    $email = get_option('sdp_paypal_email');
    $logs = get_option('sdp_logs', array());
    ?>
    <div class="wrap">
        <h1>Smart Donation Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($email); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Recent Donations (50 latest)</h2>
        <table class="widefat">
            <thead><tr><th>Time</th><th>Amount</th><th>Label</th><th>IP</th></tr></thead>
            <tbody>
                <?php foreach (array_reverse($logs) as $log): ?>
                <tr>
                    <td><?php echo esc_html($log['time']); ?></td>
                    <td>$<?php echo esc_html($log['amount']); ?></td>
                    <td><?php echo esc_html($log['label']); ?></td>
                    <td><?php echo esc_html($log['ip']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><strong>Usage:</strong> Use shortcode <code>[sdp_donate amount="20" label="Support My Blog"]</code></p>
    </div>
    <?php
}

new SmartDonationPro();

// Inline JS and CSS for single file
add_action('wp_head', function() {
    echo '<script> jQuery(document).ready(function($){ $(".sdp-donate-btn").click(function(){ var btn=$(this), amount=btn.data("amount"), label=btn.data("label"); $.post(sdp_ajax.ajax_url, {action:"sdp_process_donation", amount:amount, label:label, nonce:sdp_ajax.nonce}, function(res){ if(res.success){window.location.href=res.data.redirect;}else{ $("#sdp-message").html("<p class=\"error\">"+res.data+"</p>");} }); }); }); </script>';
    echo '<style>.sdp-donate-container{text-align:center;margin:20px 0;}.sdp-donate-btn{background:#007cba;color:white;border:none;padding:12px 24px;font-size:16px;border-radius:5px;cursor:pointer;transition:background 0.3s;}.sdp-donate-btn:hover{background:#005a87;}.sdp-donate-btn:active{transform:translateY(1px);}#sdp-message{margin-top:10px;}</style>';
});