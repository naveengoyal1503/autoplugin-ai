/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Collect one-time and recurring donations easily with customizable forms, progress bars, and analytics.
 * Version: 1.0.0
 * Author: Your Name
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
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
        add_shortcode('sdp_donation_form', array($this, 'donation_form_shortcode'));
        add_shortcode('sdp_progress_bar', array($this, 'progress_bar_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_stripe_key') && get_option('sdp_stripe_secret')) {
            // Stripe integration ready
        }
        // Create donations table
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            donor_email varchar(100) NOT NULL,
            date datetime DEFAULT CURRENT_TIMESTAMP,
            goal_id varchar(50) DEFAULT '',
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('stripe', 'https://js.stripe.com/v3/', array(), '3.0', true);
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery', 'stripe'), '1.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donation_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '1000',
            'button_text' => 'Donate Now',
            'amounts' => '5,10,25,50,100',
        ), $atts);

        $amounts = explode(',', $atts['amounts']);
        $options = '';
        foreach ($amounts as $amount) {
            $options .= '<option value="' . trim($amount) . '">' . trim($amount) . '</option>';
        }

        ob_start();
        ?>
        <div class="sdp-form-wrapper">
            <form class="sdp-donation-form" data-goal="<?php echo esc_attr($atts['goal']); ?>">
                <select class="sdp-amount" name="amount"><?php echo $options; ?></select>
                <input type="email" class="sdp-email" name="email" placeholder="Your email" required>
                <div class="sdp-recurring">
                    <label><input type="checkbox" name="recurring"> Make this recurring monthly</label>
                </div>
                <button type="submit" class="sdp-button"><?php echo esc_html($atts['button_text']); ?></button>
                <div class="sdp-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function progress_bar_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default', 'goal' => '1000'), $atts);
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $total = (float) $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM $table_name WHERE goal_id = %s", $atts['id']));
        $percent = min(100, ($total / (float)$atts['goal']) * 100);
        ob_start();
        ?>
        <div class="sdp-progress" data-goal-id="<?php echo esc_attr($atts['id']); ?>">
            <div class="sdp-progress-bar" style="width: <?php echo $percent; ?>%;">
                $<span class="sdp-total"><?php echo number_format($total, 2); ?></span> / $<?php echo number_format((float)$atts['goal'], 2); ?> (<?php echo round($percent); ?>%)
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        $email = sanitize_email($_POST['email']);
        $recurring = isset($_POST['recurring']) ? 'yes' : 'no';
        $goal_id = sanitize_text_field($_POST['goal_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $wpdb->insert($table_name, array(
            'amount' => $amount,
            'donor_email' => $email,
            'goal_id' => $goal_id
        ));

        wp_send_json_success('Thank you for your donation!');
    }

    public function activate() {
        $this->init();
        add_option('sdp_stripe_key', '');
        add_option('sdp_stripe_secret', '');
    }
}

new SmartDonationPro();

// Admin settings page
add_action('admin_menu', function() {
    add_options_page('Smart Donation Pro', 'SDP Settings', 'manage_options', 'sdp-settings', function() {
        if (isset($_POST['sdp_stripe_key'])) {
            update_option('sdp_stripe_key', sanitize_text_field($_POST['sdp_stripe_key']));
            update_option('sdp_stripe_secret', sanitize_text_field($_POST['sdp_stripe_secret']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Stripe Publishable Key</th>
                        <td><input type="text" name="sdp_stripe_key" value="<?php echo get_option('sdp_stripe_key'); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Stripe Secret Key</th>
                        <td><input type="password" name="sdp_stripe_secret" value="<?php echo get_option('sdp_stripe_secret'); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    });
});

// Minimal JS (inline for single file)
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.sdp-donation-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var amount = form.find('.sdp-amount').val();
        var email = form.find('.sdp-email').val();
        var recurring = form.find('input[name="recurring"]').is(':checked');
        var goalId = form.data('goal');

        $.post(sdp_ajax.ajax_url, {
            action: 'sdp_process_donation',
            nonce: sdp_ajax.nonce,
            amount: amount,
            email: email,
            recurring: recurring,
            goal_id: goalId
        }, function(res) {
            form.find('.sdp-message').html('<p style="color:green;">' + res.data + '</p>');
            // Update progress bars
            $('.sdp-progress[data-goal-id="' + goalId + '"] .sdp-total').text(parseFloat($('.sdp-total').text()) + parseFloat(amount));
        });
    });
});
</script>
<style>
.sdp-form-wrapper { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
.sdp-amount, .sdp-email { width: 100%; padding: 10px; margin: 10px 0; }
.sdp-button { background: #0073aa; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
.sdp-progress { background: #f0f0f0; padding: 10px; border-radius: 5px; }
.sdp-progress-bar { background: linear-gradient(90deg, #4CAF50, #45a049); color: white; padding: 10px; border-radius: 5px; font-weight: bold; }
</style>
<?php });