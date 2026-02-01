/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site revenue with customizable donation buttons, progress bars, and analytics.
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
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('smart_donation_goal') === false) {
            update_option('smart_donation_goal', 1000);
        }
        if (get_option('smart_donation_amount') === false) {
            update_option('smart_donation_amount', 5);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-donation-js', plugin_dir_url(__FILE__) . 'donation.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-donation-css', plugin_dir_url(__FILE__) . 'donation.css', array(), '1.0.0');
        wp_localize_script('smart-donation-js', 'smartDonation', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_donation_nonce')
        ));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Settings', 'manage_options', 'smart-donation', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_donation_goal', sanitize_text_field($_POST['goal']));
            update_option('smart_donation_amount', floatval($_POST['amount']));
            update_option('smart_donation_text', sanitize_text_field($_POST['text']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $goal = get_option('smart_donation_goal', 1000);
        $amount = get_option('smart_donation_amount', 5);
        $text = get_option('smart_donation_text', 'Support this site!');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Donation Goal ($)</th>
                        <td><input type="number" name="goal" value="<?php echo $goal; ?>" /></td>
                    </tr>
                    <tr>
                        <th>Default Amount ($)</th>
                        <td><input type="number" step="0.01" name="amount" value="<?php echo $amount; ?>" /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="text" value="<?php echo $text; ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[smart_donation]</code></p>
            <p>Total Donations: $<?php echo get_option('smart_donation_total', 0); ?></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => get_option('smart_donation_goal'),
            'amount' => get_option('smart_donation_amount'),
            'text' => get_option('smart_donation_text')
        ), $atts);

        $total = get_option('smart_donation_total', 0);
        $percent = min(100, ($total / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="smart-donation-container">
            <p><?php echo esc_html($atts['text']); ?></p>
            <div class="progress-bar">
                <div class="progress" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p>$<?php echo $total; ?> / $<?php echo $atts['goal']; ?> raised (<?php echo $percent; ?>%)</p>
            <input type="number" id="donation-amount" value="<?php echo $atts['amount']; ?>" min="1" step="0.01" />
            <button id="donate-btn">Donate Now</button>
            <p id="donation-message"></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_donate() {
        check_ajax_referer('smart_donation_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $total = get_option('smart_donation_total', 0) + $amount;
        update_option('smart_donation_total', $total);
        wp_send_json_success(array('total' => $total));
    }

    public function activate() {
        add_option('smart_donation_total', 0);
    }
}

SmartDonationPro::get_instance();

// AJAX handlers
add_action('wp_ajax_smart_donate', array(SmartDonationPro::get_instance(), 'ajax_donate'));
add_action('wp_ajax_nopriv_smart_donate', array(SmartDonationPro::get_instance(), 'ajax_donate'));

// Inline CSS and JS for single file
add_action('wp_head', function() {
    echo '<style>
.smart-donation-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; }
.progress-bar { width: 100%; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.progress { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s; }
#donation-amount { padding: 8px; width: 100px; margin: 10px; }
#donate-btn { background: #4CAF50; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
#donate-btn:hover { background: #45a049; }
#donation-message { margin-top: 10px; font-weight: bold; }
</style>';
});

add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '#donate-btn', function() {
            var amount = $('#donation-amount').val();
            $.post(smartDonation.ajaxurl, {
                action: 'smart_donate',
                amount: amount,
                nonce: smartDonation.nonce
            }, function(response) {
                if (response.success) {
                    $('#donation-message').html('Thank you for your $' + amount + ' donation! New total: $' + response.data.total.toFixed(2));
                    location.reload();
                }
            });
        });
    });
    </script>
    <?php
});