/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Add customizable donation buttons and progress bars to monetize your WordPress site.
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
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // Plugin is set up
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'button_text' => 'Donate Now',
            'goal' => '1000',
            'currency' => '$'
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) {
            return '<p>Please set up PayPal email in plugin settings.</p>';
        }

        $current = get_option('sdp_current_amount', 0);
        $progress = min(100, ($current / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p><?php echo $atts['currency']; ?><?php echo number_format($current, 0); ?> / <?php echo $atts['currency']; ?><?php echo $atts['goal']; ?> raised</p>
            <form class="sdp-donate-form" method="post" action="https://www.paypal.com/cgi-bin/webscr">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">
                <input type="hidden" name="item_name" value="Donation to <?php echo get_bloginfo('name'); ?>">
                <input type="hidden" name="currency_code" value="USD">
                <input type="number" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" min="1" step="0.01" required>
                <input type="hidden" name="return" value="<?php echo home_url(); ?>">
                <button type="submit" class="sdp-button"><?php echo esc_html($atts['button_text']); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr(get_option('sdp_paypal_email')); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[smart_donation amount="10" goal="1000"]</code></p>
        </div>
        <?php
    }

    public function handle_donation() {
        if (!wp_verify_nonce($_POST['nonce'], 'sdp_nonce')) {
            wp_die('Security check failed');
        }
        $amount = floatval($_POST['amount']);
        $current = get_option('sdp_current_amount', 0) + $amount;
        update_option('sdp_current_amount', $current);
        wp_send_json_success('Thank you for your donation!');
    }
}

new SmartDonationPro();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
    .sdp-container { max-width: 400px; margin: 20px 0; text-align: center; }
    .sdp-progress-bar { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
    .sdp-progress { background: #28a745; height: 100%; transition: width 0.3s; }
    .sdp-donate-form input[type=number] { padding: 10px; margin-right: 10px; }
    .sdp-button { background: #007cba; color: white; border: none; padding: 12px 24px; cursor: pointer; border-radius: 5px; }
    .sdp-button:hover { background: #005a87; }
    </style>';
});

// Inline JS
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sdp-button').on('click', function() {
            var amount = $(this).siblings('input[name=amount]').val();
            $.post(sdp_ajax.ajax_url, {
                action: 'sdp_donate',
                amount: amount,
                nonce: sdp_ajax.nonce
            }, function(res) {
                if (res.success) alert(res.data);
            });
        });
    });
    </script>
    <?php
});