/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Affiliate_Manager.php
*/
<?php
/**
 * Plugin Name: WP Smart Affiliate Manager
 * Plugin URI: https://example.com/wp-smart-affiliate-manager
 * Description: Automate affiliate program management, tracking, payouts, and reporting.
 * Version: 1.0.0
 * Author: Your Company
 * Author URI: https://example.com
 * License: GPL2
 */

define('WP_SMART_AFFILIATE_MANAGER_VERSION', '1.0.0');

class WPSmartAffiliateManager {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_signup', array($this, 'affiliate_signup_shortcode'));
        add_shortcode('affiliate_dashboard', array($this, 'affiliate_dashboard_shortcode'));
    }

    public function init() {
        $this->create_tables();
    }

    private function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_affiliates';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            affiliate_code varchar(50) NOT NULL,
            earnings decimal(10,2) NOT NULL DEFAULT '0.00',
            paid decimal(10,2) NOT NULL DEFAULT '0.00',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY affiliate_code (affiliate_code)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function admin_menu() {
        add_menu_page(
            'Smart Affiliate Manager',
            'Affiliate Manager',
            'manage_options',
            'smart-affiliate-manager',
            array($this, 'admin_page'),
            'dashicons-groups'
        );
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_affiliates';
        $affiliates = $wpdb->get_results("SELECT * FROM $table_name");
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Manager</h1>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Affiliate Code</th>
                        <th>Earnings</th>
                        <th>Paid</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($affiliates as $affiliate): ?>
                    <tr>
                        <td><?php echo esc_html($affiliate->id); ?></td>
                        <td><?php echo esc_html($affiliate->user_id); ?></td>
                        <td><?php echo esc_html($affiliate->affiliate_code); ?></td>
                        <td>$<?php echo esc_html($affiliate->earnings); ?></td>
                        <td>$<?php echo esc_html($affiliate->paid); ?></td>
                        <td><?php echo esc_html($affiliate->created_at); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('smart-affiliate-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function affiliate_signup_shortcode($atts) {
        $atts = shortcode_atts(array('redirect' => ''), $atts);
        ob_start();
        ?>
        <form method="post" action="">
            <p><label for="affiliate_name">Name:</label><input type="text" name="affiliate_name" required></p>
            <p><label for="affiliate_email">Email:</label><input type="email" name="affiliate_email" required></p>
            <p><input type="submit" name="affiliate_signup" value="Sign Up"></p>
        </form>
        <?php
        if (isset($_POST['affiliate_signup'])) {
            $name = sanitize_text_field($_POST['affiliate_name']);
            $email = sanitize_email($_POST['affiliate_email']);
            $user_id = email_exists($email);
            if (!$user_id) {
                $user_id = wp_create_user($email, wp_generate_password(), $email);
            }
            $affiliate_code = substr(md5($email . time()), 0, 10);
            global $wpdb;
            $table_name = $wpdb->prefix . 'smart_affiliates';
            $wpdb->insert($table_name, array(
                'user_id' => $user_id,
                'affiliate_code' => $affiliate_code,
                'earnings' => 0,
                'paid' => 0
            ));
            echo '<p>Thank you! Your affiliate code is: ' . $affiliate_code . '</p>';
        }
        return ob_get_clean();
    }

    public function affiliate_dashboard_shortcode($atts) {
        ob_start();
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your dashboard.</p>';
        }
        $user_id = get_current_user_id();
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));
        if (!$affiliate) {
            return '<p>You are not an affiliate.</p>';
        }
        ?>
        <div class="affiliate-dashboard">
            <h2>Affiliate Dashboard</h2>
            <p>Your Affiliate Code: <?php echo esc_html($affiliate->affiliate_code); ?></p>
            <p>Earnings: $<?php echo esc_html($affiliate->earnings); ?></p>
            <p>Paid: $<?php echo esc_html($affiliate->paid); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
}

new WPSmartAffiliateManager();
