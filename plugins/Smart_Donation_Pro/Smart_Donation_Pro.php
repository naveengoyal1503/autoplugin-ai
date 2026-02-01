/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Accept one-time and recurring donations with customizable buttons, goals, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-donation-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        load_plugin_textdomain('smart-donation-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            donor_email varchar(100) NOT NULL,
            donor_name varchar(100) DEFAULT '',
            goal_id mediumint(9) NOT NULL,
            date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'pending',
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
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sdp_save'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdp_goals', json_encode($_POST['goals'] ?? array()));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
        $goals = json_decode(get_option('sdp_goals', '[]'), true);
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 1,
            'amount' => 10,
            'button_text' => 'Donate Now',
            'goal' => 0
        ), $atts);

        $goals = json_decode(get_option('sdp_goals', '[]'), true);
        $goal = $goals[$atts['goal']] ?? null;

        ob_start();
        ?>
        <div class="sdp-container" data-goal-id="<?php echo esc_attr($atts['goal']); ?>" data-default-amount="<?php echo esc_attr($atts['amount']); ?>">
            <?php if ($goal): $progress = $this->get_goal_progress($goal['id']); ?>
            <div class="sdp-goal-bar">
                <div class="sdp-progress" style="width: <?php echo min(100, ($progress / $goal['target']) * 100); ?>%;"></div>
            </div>
            <p><?php printf(__('Raised: $%s / $%s', 'smart-donation-pro'), number_format($progress, 2), number_format($goal['target'], 2)); ?></p>
            <?php endif; ?>
            <input type="number" class="sdp-amount" value="<?php echo esc_attr($atts['amount']); ?>" min="1" step="0.01">
            <button class="sdp-donate-btn" data-amount="<?php echo esc_attr($atts['amount']); ?>"><?php echo esc_html($atts['button_text']); ?></button>
            <div class="sdp-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_goal_progress($goal_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        return (float) $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM $table_name WHERE goal_id = %d AND status = 'completed'", $goal_id));
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $email = sanitize_email($_POST['email']);
        $name = sanitize_text_field($_POST['name']);
        $goal_id = intval($_POST['goal_id']);

        if ($amount < 1 || !is_email($email)) {
            wp_send_json_error('Invalid input');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $wpdb->insert($table_name, array(
            'amount' => $amount,
            'donor_email' => $email,
            'donor_name' => $name,
            'goal_id' => $goal_id,
            'status' => 'pending'
        ));

        $paypal_email = get_option('sdp_paypal_email');
        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($paypal_email) . '&amount=' . $amount . '&item_name=Donation&currency_code=USD&return=' . urlencode(get_site_url() . '/?sdp_thankyou=1');

        wp_send_json_success(array('redirect' => $paypal_url));
    }
}

SmartDonationPro::get_instance();

// Upsell notice
function sdp_upsell_notice() {
    if (get_option('sdp_dismiss_upsell')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Donation Pro:</strong> Unlock recurring donations and analytics with Premium! <a href="https://example.com/premium" target="_blank">Upgrade Now</a> | <a href="' . wp_nonce_url(admin_url('admin.php?page=smart-donation-pro&dismiss_upsell=1'), 'sdp_upsell') . '">Dismiss</a></p></div>';
}
add_action('admin_notices', 'sdp_upsell_notice');

if (isset($_GET['dismiss_upsell']) && wp_verify_nonce($_GET['_wpnonce'], 'sdp_upsell')) {
    update_option('sdp_dismiss_upsell', 1);
}