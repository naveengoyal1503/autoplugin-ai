<?php
/*
Plugin Name: Affiliate Booster Pro
Description: Create and manage affiliate campaigns with tracking and commission features.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateBoosterPro {
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliate_links', array($this, 'affiliate_links_shortcode'));
        add_action('template_redirect', array($this, 'track_click'));
        $this->init_db();
    }

    private function init_db() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_booster_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            affiliate_id VARCHAR(100) NOT NULL,
            referer VARCHAR(255) DEFAULT NULL,
            ip_address VARCHAR(100) NOT NULL,
            click_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function admin_menu() {
        add_menu_page('Affiliate Booster', 'Affiliate Booster', 'manage_options', 'affiliate-booster', array($this, 'admin_page'), 'dashicons-cart', 100);
    }

    public function register_settings() {
        register_setting('affiliate_booster_settings', 'affiliate_booster_affiliates');
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) return;

        if (isset($_POST['save_affiliates'])) {
            check_admin_referer('affiliate_booster_save', 'affiliate_booster_nonce');
            $affiliates_raw = isset($_POST['affiliate_booster_affiliates']) ? sanitize_textarea_field(trim($_POST['affiliate_booster_affiliates'])) : '';
            update_option('affiliate_booster_affiliates', $affiliates_raw);
            echo '<div class="updated"><p>Affiliates saved.</p></div>';
        }

        $affiliates_raw = get_option('affiliate_booster_affiliates', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Booster Settings</h1>
            <form method="post">
                <?php wp_nonce_field('affiliate_booster_save', 'affiliate_booster_nonce'); ?>
                <textarea name="affiliate_booster_affiliates" rows="10" cols="50" placeholder="Enter affiliate_id|url, one per line"><?php echo esc_textarea($affiliates_raw); ?></textarea>
                <p>Format: <code>affiliate_id|https://affiliate-url.com</code> one per line</p>
                <input type="submit" name="save_affiliates" class="button button-primary" value="Save Affiliates">
            </form>
            <h2>Clicks Log</h2>
            <?php $this->display_clicks_log(); ?>
        </div>
        <?php
    }

    private function get_affiliates() {
        $raw = get_option('affiliate_booster_affiliates', '');
        $lines = explode("\n", $raw);
        $affiliates = array();
        foreach($lines as $line) {
            $line = trim($line);
            if (!$line) continue;
            $parts = explode('|', $line);
            if(count($parts) === 2) {
                $affiliates[$parts] = esc_url_raw(trim($parts[1]));
            }
        }
        return $affiliates;
    }

    public function affiliate_links_shortcode($atts) {
        $affiliates = $this->get_affiliates();
        if (empty($affiliates)) return '<p>No affiliates configured.</p>';

        $output = '<ul class="affiliate-booster-list">';
        foreach ($affiliates as $id => $url) {
            $link = esc_url(add_query_arg('aff_id', $id, home_url()));
            $output .= '<li><a href="' . $link . '" target="_blank" rel="nofollow noopener">Affiliate: ' . esc_html($id) . '</a> - Destination: ' . esc_html($url) . '</li>';
        }
        $output .= '</ul>';
        return $output;
    }

    public function track_click() {
        if (!isset($_GET['aff_id'])) return;
        $aff_id = sanitize_text_field($_GET['aff_id']);

        $affiliates = $this->get_affiliates();
        if (!array_key_exists($aff_id, $affiliates)) return;

        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_booster_clicks';

        $wpdb->insert($table_name, array(
            'affiliate_id' => $aff_id,
            'referer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'click_time' => current_time('mysql')
        ));

        wp_redirect($affiliates[$aff_id]);
        exit;
    }

    private function display_clicks_log($limit = 20) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_booster_clicks';

        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY click_time DESC LIMIT %d", $limit));
        if (empty($rows)) {
            echo '<p>No clicks recorded yet.</p>';
            return;
        }

        echo '<table class="widefat fixed" cellspacing="0"><thead><tr>' .
            '<th>Affiliate ID</th><th>Referer</th><th>IP Address</th><th>Click Time</th></tr></thead><tbody>';

        foreach ($rows as $row) {
            echo '<tr>' .
                 '<td>' . esc_html($row->affiliate_id) . '</td>' .
                 '<td>' . esc_html($row->referer) . '</td>' .
                 '<td>' . esc_html($row->ip_address) . '</td>' .
                 '<td>' . esc_html($row->click_time) . '</td>' .
                 '</tr>';
        }
        echo '</tbody></table>';
    }
}

new AffiliateBoosterPro();
