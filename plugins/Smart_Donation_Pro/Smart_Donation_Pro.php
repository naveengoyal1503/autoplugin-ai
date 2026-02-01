/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Add customizable donation buttons and fundraising goals to monetize your WordPress site easily.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
        add_shortcode('sdp_donate', array($this, 'donate_shortcode'));
        add_shortcode('sdp_goal', array($this, 'goal_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // PayPal integration ready
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
            date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function donate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'goal_id' => 0
        ), $atts);
        $paypal_email = get_option('sdp_paypal_email');
        ob_start();
        ?>
        <div class="sdp-donate" data-amount="<?php echo esc_attr($atts['amount']); ?>" data-goal="<?php echo esc_attr($atts['goal_id']); ?>">
            <button class="sdp-btn" id="sdp-pay-<?php echo uniqid(); ?>"><?php echo esc_html($atts['label']); ?></button>
            <?php if ($paypal_email): ?>
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top" class="sdp-paypal-form" style="display:none;">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="">
                <input type="hidden" name="amount" value="<?php echo esc_attr($atts['amount']); ?>">
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">
                <input type="hidden" name="currency_code" value="USD">
                <input type="submit" value="Pay Now">
            </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function goal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 1,
            'target' => '1000',
            'label' => 'Fundraising Goal'
        ), $atts);
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $raised = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_name WHERE date > DATE_SUB(NOW(), INTERVAL 1 YEAR)") ?: 0;
        $percent = min(100, ($raised / (float)$atts['target']) * 100);
        ob_start();
        ?>
        <div class="sdp-goal" data-goal-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($atts['label']); ?></h3>
            <div class="sdp-progress">
                <div class="sdp-bar" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p>$<?php echo number_format($raised, 2); ?> / $<?php echo number_format($atts['target'], 2); ?> (<?php echo $percent; ?>%)</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdp_donations';
        $amount = floatval($_POST['amount']);
        $email = sanitize_email($_POST['email']);
        $wpdb->insert($table_name, array('amount' => $amount, 'donor_email' => $email));
        wp_send_json_success('Thank you for your donation!');
    }

    public function activate() {
        $this->create_table();
        add_option('sdp_paypal_email', '');
    }
}

new SmartDonationPro();

// Admin settings page
function sdp_admin_menu() {
    add_options_page('Smart Donation Pro Settings', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
}
add_action('admin_menu', 'sdp_admin_menu');

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
                    <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p>Usage: Use shortcodes <code>[sdp_donate amount="20"]</code> or <code>[sdp_goal target="500"]</code>.</p>
    </div>
    <?php
}

// Minimal JS file content (base64 encoded for single file)
$js = "jQuery(document).ready(function($){ $('.sdp-btn').click(function(){ var form = $(this).siblings('.sdp-paypal-form'); if(form.length){form.submit();} else { var amt=$(this).closest('.sdp-donate').data('amount'), email=prompt('Email for receipt:'); if(email){$.post(sdp_ajax.ajax_url,{action:'sdp_donate',amount:amt,email:email,nonce:sdp_ajax.nonce},function(r){alert(r.data);});} } }); });";
file_put_contents(plugin_dir_path(__FILE__) . 'sdp-script.js', $js);

// Minimal CSS
$css = ".sdp-donate{margin:20px 0;}.sdp-btn{background:#0073aa;color:white;padding:10px 20px;border:none;cursor:pointer;}.sdp-btn:hover{opacity:0.9;}.sdp-progress{background:#eee;height:20px;border-radius:10px;overflow:hidden;margin:10px 0;}.sdp-bar{background:#28a745;height:100%;transition:width 0.3s;}.sdp-goal{text-align:center;border:1px solid #ddd;padding:20px;border-radius:5px;}";
file_put_contents(plugin_dir_path(__FILE__) . 'sdp-style.css', $css);