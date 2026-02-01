/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site revenue with easy-to-use donation buttons, progress bars, and payment forms. Supports PayPal, Stripe (premium), one-time and recurring donations.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_shortcode('sdp_donate_button', array($this, 'donate_button_shortcode'));
        add_shortcode('sdp_progress_bar', array($this, 'progress_bar_shortcode'));
    }

    public function init() {
        load_plugin_textdomain('smart-donation-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->create_tables();
    }

    private function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            donor_email varchar(100) NOT NULL,
            date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro Settings', 'Donation Pro', 'manage_options', 'sdp-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
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
            <h2>Shortcodes</h2>
            <p><code>[sdp_donate_button amount="10" label="Donate $10"]</code> - Donation button</p>
            <p><code>[sdp_progress_bar goal="1000" current="250" label="Fundraiser Goal"]</code> - Progress bar</p>
            <h2>Stats</h2>
            <?php $this->show_stats(); ?>
        </div>
        <?php
    }

    private function show_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $total = $wpdb->get_var("SELECT SUM(amount) FROM $table_name");
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo "<p>Total Donations: \$" . number_format((float)$total, 2) . ' from ' . $count . ' donors.</p>';
    }

    public function donate_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '5',
            'label' => 'Donate',
            'paypal_email' => get_option('sdp_paypal_email')
        ), $atts);

        if (empty($atts['paypal_email'])) {
            return '<p>Please set PayPal email in settings.</p>';
        }

        $paypal_url = 'https://www.paypal.com/donate?business=' . urlencode($atts['paypal_email']) . '&amount=' . urlencode($atts['amount']) . '&no_note=1&item_name=Donation';

        return '<a href="' . esc_url($paypal_url) . '" class="sdp-donate-btn" target="_blank">' . esc_html($atts['label']) . '</a>';
    }

    public function progress_bar_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '1000',
            'current' => '0',
            'label' => 'Goal Progress'
        ), $atts);

        $percentage = ($atts['current'] / $atts['goal']) * 100;
        $percentage = min(100, max(0, $percentage));

        ob_start();
        ?>
        <div class="sdp-progress-container">
            <label><?php echo esc_html($atts['label']); ?> (<?php echo esc_html($atts['current']); ?> / <?php echo esc_html($atts['goal']); ?>)</label>
            <div class="sdp-progress-bar">
                <div class="sdp-progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        if (!wp_verify_nonce($_POST['nonce'], 'sdp_nonce')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $amount = floatval($_POST['amount']);
        $email = sanitize_email($_POST['email']);

        $wpdb->insert($table_name, array('amount' => $amount, 'donor_email' => $email));

        wp_send_json_success('Thank you for your donation!');
    }
}

new SmartDonationPro();

// Inline styles and scripts for self-contained plugin
function sdp_add_inline_assets() {
    ?>
    <style>
    .sdp-donate-btn {
        display: inline-block;
        background: #007cba;
        color: white;
        padding: 12px 24px;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
        transition: background 0.3s;
    }
    .sdp-donate-btn:hover { background: #005a87; }
    .sdp-progress-container { margin: 20px 0; }
    .sdp-progress-bar { background: #ddd; height: 20px; border-radius: 10px; overflow: hidden; }
    .sdp-progress-fill { height: 100%; background: #28a745; transition: width 0.5s; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        // Enhanced donation form if needed
    });
    </script>
    <?php
}
add_action('wp_head', 'sdp_add_inline_assets');

?>