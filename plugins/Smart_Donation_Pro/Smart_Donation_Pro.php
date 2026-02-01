/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add customizable donation buttons and forms to monetize your WordPress site with PayPal.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // Plugin is set up
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'jQuery(document).ready(function($) { $(".sdp-donate-btn").click(function(e) { e.preventDefault(); $("#sdp-form-" + $(this).data("id")).toggle(); }); });');
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0');
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'currency' => 'USD',
            'goal' => '0',
            'id' => uniqid('donate_')
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email', '');
        if (!$paypal_email) return '<p>Please set up PayPal email in plugin settings.</p>';

        $goal_progress = $atts['goal'] ? '<div class="sdp-progress"><div class="sdp-progress-bar" style="width: 0%;"></div></div><span>Goal: $' . $atts['goal'] . '</span>' : '';

        ob_start();
        ?>
        <div class="sdp-container">
            <button class="sdp-donate-btn" data-id="<?php echo esc_attr($atts['id']); ?>"><?php echo esc_html($atts['label']); ?> ($<?php echo esc_html($atts['amount']); ?>)</button>
            <?php echo $goal_progress; ?>
            <form id="sdp-form-<?php echo esc_attr($atts['id']); ?>" class="sdp-form" style="display:none;">
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="item_name" value="Donation">
                <input type="hidden" name="amount" value="<?php echo esc_attr($atts['amount']); ?>">
                <input type="hidden" name="currency_code" value="<?php echo esc_attr($atts['currency']); ?>">
                <input type="hidden" name="return" value="<?php echo esc_url(home_url()); ?>">
                <input type="submit" value="Pay with PayPal">
            </form>
        </div>
        <script>
        /* Simple progress bar simulation - replace with real tracking in pro */
        jQuery('.sdp-progress-bar').css('width', '60%');
        </script>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $email = get_option('sdp_paypal_email', '');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($email); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Usage:</strong> Use shortcode <code>[smart_donation amount="20" label="Support Us" goal="500"]</code></p>
        </div>
        <?php
    }

    public function activate() {
        if (!get_option('sdp_paypal_email')) {
            update_option('sdp_paypal_email', '');
        }
    }
}

new SmartDonationPro();

/* Inline CSS */
function sdp_inline_css() {
    echo '<style>
    .sdp-container { text-align: center; margin: 20px 0; }
    .sdp-donate-btn { background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
    .sdp-donate-btn:hover { background: #005a87; }
    .sdp-form { margin-top: 15px; }
    .sdp-form input[type="submit"] { background: #ffc107; color: #000; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    .sdp-progress { background: #eee; height: 20px; border-radius: 10px; margin: 10px 0; overflow: hidden; }
    .sdp-progress-bar { height: 100%; background: #28a745; transition: width 0.3s; }
    </style>';
}
add_action('wp_head', 'sdp_inline_css');
?>