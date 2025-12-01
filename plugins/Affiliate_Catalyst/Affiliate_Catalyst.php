/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Catalyst.php
*/
<?php
/**
 * Plugin Name: Affiliate Catalyst
 * Description: Create and manage powerful affiliate programs with tiered commissions and detailed analytics.
 * Version: 1.0
 * Author: Your Company
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Affiliate_Catalyst {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_ac_add_affiliate', array($this, 'handle_add_affiliate'));
        add_action('init', array($this, 'track_affiliate_referral'));
        add_shortcode('ac_affiliate_link', array($this, 'affiliate_link_shortcode'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Catalyst', 'Affiliate Catalyst', 'manage_options', 'affiliate-catalyst', array($this, 'admin_dashboard'), 'dashicons-admin-users');
    }

    public function admin_dashboard() {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap"><h1>Affiliate Catalyst</h1>';

        // Display existing affiliates
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_catalyst_affiliates';

        $affiliates = $wpdb->get_results("SELECT * FROM $table_name");

        echo '<h2>Existing Affiliates</h2>';
        echo '<table class="widefat"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Commission Rate (%)</th></tr></thead><tbody>';

        foreach ($affiliates as $affiliate) {
            echo '<tr><td>' . esc_html($affiliate->id) . '</td><td>' . esc_html($affiliate->name) . '</td><td>' . esc_html($affiliate->email) . '</td><td>' . esc_html($affiliate->commission_rate) . '</td></tr>';
        }

        echo '</tbody></table>';

        // Add affiliate form
        echo '<h2>Add New Affiliate</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="ac_add_affiliate">';
        wp_nonce_field('ac_add_affiliate_nonce');
        echo '<table class="form-table">';
        echo '<tr><th><label for="name">Name</label></th><td><input name="name" type="text" id="name" class="regular-text" required></td></tr>';
        echo '<tr><th><label for="email">Email</label></th><td><input name="email" type="email" id="email" class="regular-text" required></td></tr>';
        echo '<tr><th><label for="commission_rate">Commission Rate (%)</label></th><td><input name="commission_rate" type="number" id="commission_rate" class="small-text" min="0" max="100" step="0.01" required></td></tr>';
        echo '</table>';
        submit_button('Add Affiliate');
        echo '</form></div>';
    }

    public function handle_add_affiliate() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }

        check_admin_referer('ac_add_affiliate_nonce');

        if (empty($_POST['name']) || empty($_POST['email']) || !isset($_POST['commission_rate'])) {
            wp_redirect(admin_url('admin.php?page=affiliate-catalyst&message=error'));
            exit;
        }

        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $commission_rate = floatval($_POST['commission_rate']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_catalyst_affiliates';

        $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'email' => $email,
                'commission_rate' => $commission_rate,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%f', '%s')
        );

        wp_redirect(admin_url('admin.php?page=affiliate-catalyst&message=added'));
        exit;
    }

    public function track_affiliate_referral() {
        if (isset($_GET['ac_ref'])) {
            $affiliate_id = intval($_GET['ac_ref']);
            setcookie('ac_affiliate_id', $affiliate_id, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        }
    }

    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'text' => 'Affiliate Link'
        ), $atts, 'ac_affiliate_link');

        $affiliate_id = intval($atts['id']);
        $text = esc_html($atts['text']);

        if ($affiliate_id <= 0) {
            return '';
        }

        $url = esc_url(add_query_arg('ac_ref', $affiliate_id, home_url('/')));
        return '<a href="' . $url . '" target="_blank" rel="nofollow noopener noreferrer">' . $text . '</a>';
    }

}

register_activation_hook(__FILE__, function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_catalyst_affiliates';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        commission_rate FLOAT NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY email (email)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

Affiliate_Catalyst::get_instance();
