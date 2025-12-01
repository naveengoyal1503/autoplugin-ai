<?php
/*
Plugin Name: Affiliate Booster Pro
Description: An advanced affiliate management plugin for WordPress with WooCommerce integration, customizable commission structures, real-time tracking, and affiliate dashboards.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateBoosterPro {
    private static $instance = null;
    private $commission_rates = array();

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'start_session'), 1);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_register', array($this, 'affiliate_register_form'));
        add_shortcode('affiliate_dashboard', array($this, 'affiliate_dashboard'));

        add_action('wp_ajax_affiliate_register_user', array($this, 'handle_registration'));
        add_action('wp_ajax_nopriv_affiliate_register_user', array($this, 'handle_registration'));

        // Track referrals on WooCommerce order completion
        add_action('woocommerce_thankyou', array($this, 'track_referral_on_order'), 10, 1);

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_save_commissions', array($this, 'save_commission_settings'));
    }

    public function activate() {
        // Create DB table for affiliates
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_booster_affiliates';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            affiliate_code VARCHAR(50) NOT NULL UNIQUE,
            total_earned DECIMAL(10,2) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Create referrals table
        $table_ref = $wpdb->prefix . 'affiliate_booster_referrals';
        $sql_ref = "CREATE TABLE IF NOT EXISTS $table_ref (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            commission DECIMAL(10,2) NOT NULL,
            date DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_ref);
    }

    public function deactivate() {
        // Nothing for now
    }

    public function start_session() {
        if (!session_id()) {
            session_start();
        }
        // Check affiliate code in URL
        if (isset($_GET['ref'])) {
            $_SESSION['affiliate_ref'] = sanitize_text_field($_GET['ref']);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-booster-style', plugins_url('style.css', __FILE__));
        wp_enqueue_script('affiliate-booster-js', plugins_url('affbooster.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('affiliate-booster-js', 'affOptions', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('affiliate_booster_nonce')
        ));
    }

    public function affiliate_register_form() {
        if (is_user_logged_in()) {
            return '<p>You are already registered.</p>';
        }
        ob_start();
        ?>
        <form id="affiliate-register-form">
            <p><label for="aff_name">Name:</label><br>
            <input type="text" id="aff_name" name="aff_name" required></p>
            <p><label for="aff_email">Email:</label><br>
            <input type="email" id="aff_email" name="aff_email" required></p>
            <p><button type="submit">Register as Affiliate</button></p>
            <div id="aff_feedback"></div>
        </form>
        <script>
jQuery(document).ready(function($){
  $('#affiliate-register-form').on('submit', function(e) {
    e.preventDefault();
    var data = {
      action: 'affiliate_register_user',
      aff_name: $('#aff_name').val(),
      aff_email: $('#aff_email').val(),
      security: affOptions.nonce
    };
    $.post(affOptions.ajax_url, data, function(response) {
      $('#aff_feedback').html(response.data);
    });
  });
});
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_registration() {
        check_ajax_referer('affiliate_booster_nonce', 'security');
        $name = sanitize_text_field($_POST['aff_name']);
        $email = sanitize_email($_POST['aff_email']);

        if (!is_email($email)) {
            wp_send_json_error('Invalid email address.');
        }

        if (email_exists($email)) {
            wp_send_json_error('Email already registered.');
        }

        // Create WordPress user
        $password = wp_generate_password(12, false);
        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) {
            wp_send_json_error('Registration failed.');
        }

        wp_update_user(array('ID' => $user_id, 'display_name' => $name));

        // Create affiliate code - unique
        $affiliate_code = sanitize_title($name) . rand(1000, 9999);
        global $wpdb;
        $table = $wpdb->prefix . 'affiliate_booster_affiliates';
        $wpdb->insert($table, array(
            'user_id' => $user_id,
            'affiliate_code' => $affiliate_code,
            'total_earned' => 0
        ));

        wp_new_user_notification($user_id, null, 'user');

        wp_send_json_success('Registration successful. Your affiliate code is: ' . esc_html($affiliate_code));
    }

    public function track_referral_on_order($order_id) {
        if (!isset($_SESSION['affiliate_ref'])) return;
        $ref_code = $_SESSION['affiliate_ref'];
        unset($_SESSION['affiliate_ref']);

        global $wpdb;
        $table_aff = $wpdb->prefix . 'affiliate_booster_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_aff WHERE affiliate_code = %s", $ref_code));
        if (!$affiliate) return;

        $order = wc_get_order($order_id);
        $amount = $order->get_total();

        // Calculate commission (default 10%, can be customized)
        $commission_rate = $this->get_commission_rate();
        $commission = round($amount * $commission_rate, 2);

        // Insert referral
        $table_ref = $wpdb->prefix . 'affiliate_booster_referrals';
        $wpdb->insert($table_ref, array(
            'affiliate_id' => $affiliate->id,
            'order_id' => $order_id,
            'amount' => $amount,
            'commission' => $commission,
            'date' => current_time('mysql')
        ));

        // Update total earned
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_aff SET total_earned = total_earned + %f WHERE id = %d",
            $commission, $affiliate->id
        ));

        // Optional: notify affiliate user
        $user = get_user_by('id', $affiliate->user_id);
        if ($user) {
            wp_mail($user->user_email, 'You earned a commission!', "You earned a commission of $commission for order #$order_id.");
        }
    }

    public function get_commission_rate() {
        // For simplicity, 10% flat rate; this can be enhanced to a settings option
        return 0.10;
    }

    public function affiliate_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your affiliate dashboard.</p>';
        }
        global $wpdb;
        $user_id = get_current_user_id();
        $table_aff = $wpdb->prefix . 'affiliate_booster_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_aff WHERE user_id = %d", $user_id));
        if (!$affiliate) {
            return '<p>You are not registered as an affiliate.</p>';
        }

        $table_ref = $wpdb->prefix . 'affiliate_booster_referrals';
        $referrals = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_ref WHERE affiliate_id = %d ORDER BY date DESC", $affiliate->id));

        ob_start();
        echo '<h3>Your Affiliate Dashboard</h3>';
        echo '<p><strong>Your Affiliate Code:</strong> ' . esc_html($affiliate->affiliate_code) . '</p>';
        echo '<p><strong>Total Earnings:</strong> $' . number_format($affiliate->total_earned, 2) . '</p>';

        echo '<h4>Recent Referrals</h4>';
        if ($referrals) {
            echo '<table><thead><tr><th>Order ID</th><th>Sale Amount</th><th>Commission</th><th>Date</th></tr></thead><tbody>';
            foreach ($referrals as $ref) {
                echo '<tr>';
                echo '<td>' . esc_html($ref->order_id) . '</td>';
                echo '<td>$' . number_format($ref->amount, 2) . '</td>';
                echo '<td>$' . number_format($ref->commission, 2) . '</td>';
                echo '<td>' . esc_html($ref->date) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No referral records found.</p>';
        }
        return ob_get_clean();
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Booster Pro', 'Affiliate Booster', 'manage_options', 'affiliate-booster', array($this, 'admin_settings_page'));
    }

    public function admin_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $commission_rate = $this->get_commission_rate() * 100;
        ?>
        <div class="wrap">
            <h1>Affiliate Booster Pro Settings</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="save_commissions">
                <?php wp_nonce_field('save_commissions_nonce'); ?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="commission_rate">Commission Rate (%)</label></th>
                        <td><input type="number" step="0.1" min="0" max="100" name="commission_rate" value="<?php echo esc_attr($commission_rate); ?>" required></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    public function save_commission_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }
        check_admin_referer('save_commissions_nonce');

        $rate = isset($_POST['commission_rate']) ? floatval($_POST['commission_rate']) : 10;
        if ($rate < 0 || $rate > 100) {
            $rate = 10;
        }

        // Save option
        update_option('affiliate_booster_commission_rate', $rate / 100);

        wp_redirect(admin_url('admin.php?page=affiliate-booster&updated=true'));
        exit;
    }

    public function get_commission_rate_option() {
        $rate = get_option('affiliate_booster_commission_rate', 0.10);
        return floatval($rate);
    }
}

// Initialize plugin
AffiliateBoosterPro::get_instance();
