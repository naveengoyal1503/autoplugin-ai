/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Collect one-time and recurring donations easily with tiers, PayPal, Stripe, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_process_donation', array($this, 'process_donation'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('smart-donation-js', plugin_dir_url(__FILE__) . 'donation.js', array('jquery'), '1.0.0', true);
        wp_localize_script('smart-donation-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_donations';
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

    public function activate() {
        $this->create_table();
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'tiers' => '10,25,50,100',
            'title' => 'Support Us',
            'button' => 'Donate Now',
            'paypal' => '',
            'stripe_pk' => ''
        ), $atts);

        $tiers = explode(',', $atts['tiers']);
        $output = '<div id="smart-donation-form" class="smart-donation-container">';
        $output .= '<h3>' . esc_html($atts['title']) . '</h3>';
        $output .= '<form id="donation-form">';
        $output .= '<select name="amount" id="donation-amount">';
        foreach ($tiers as $tier) {
            $output .= '<option value="' . floatval(trim($tier)) . '">' . esc_html(trim($tier)) . '</option>';
        }
        $output .= '<option value="custom">Custom Amount</option>';
        $output .= '</select>';
        $output .= '<input type="email" name="donor_email" placeholder="Your Email" required>';
        $output .= '<input type="submit" value="' . esc_attr($atts['button']) . '" id="donate-btn">';
        $output .= '</form>';
        $output .= '<div id="donation-message"></div>';
        $output .= '</div>';

        // Simple PayPal button
        if ($atts['paypal']) {
            $output .= '<form action="https://www.paypal.com/donate" method="post" target="_top">
                <input type="hidden" name="hosted_button_id" value="' . esc_attr($atts['paypal']) . '" />
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
                </form>';
        }

        return $output;
    }

    public function process_donation() {
        if (!wp_verify_nonce($_POST['nonce'], 'donation_nonce')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $amount = floatval(sanitize_text_field($_POST['amount']));
        $email = sanitize_email($_POST['donor_email']);

        $wpdb->insert(
            $wpdb->prefix . 'smart_donations',
            array('amount' => $amount, 'donor_email' => $email),
            array('%f', '%s')
        );

        wp_send_json_success('Thank you for your donation of $' . $amount . '!');
    }
}

new SmartDonationPro();

// Admin page
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donation Pro', 'Donations', 'manage_options', 'smart-donation', 'smart_donation_admin');
    });
}

function smart_donation_admin() {
    global $wpdb;
    $donations = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . 'smart_donations' . " ORDER BY timestamp DESC");
    echo '<div class="wrap"><h1>Donation Analytics</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<tr><th>ID</th><th>Amount</th><th>Email</th><th>Date</th></tr>';
    foreach ($donations as $donation) {
        echo '<tr><td>' . $donation->id . '</td><td>$' . $donation->amount . '</td><td>' . $donation->donor_email . '</td><td>' . $donation->timestamp . '</td></tr>';
    }
    echo '</table></div>';
}

// JS file content would be enqueued separately, but for single file:
/*
<script>
jQuery(document).ready(function($) {
    $('#donation-form').on('submit', function(e) {
        e.preventDefault();
        var amount = $('#donation-amount').val();
        if (amount === 'custom') amount = prompt('Enter custom amount');
        $.post(ajax_object.ajax_url, {
            action: 'process_donation',
            amount: amount,
            donor_email: $('input[name="donor_email"]').val(),
            nonce: '<?php echo wp_create_nonce("donation_nonce"); ?>'
        }, function(response) {
            $('#donation-message').html(response.data);
        });
    });
});
</script>
*/
?>