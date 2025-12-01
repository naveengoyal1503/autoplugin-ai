<?php
/*
Plugin Name: AffiliateBoostPro
Description: Create and manage affiliate programs with real-time tracking and commissions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateBoostPro.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateBoostPro {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'affboost_affiliates';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        add_action('init', array($this, 'track_affiliate_referral'));
        add_shortcode('affboost_referral_link', array($this, 'referral_link_shortcode'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_name}` (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) NOT NULL,
            user_id BIGINT(20) DEFAULT 0,
            clicks INT(10) DEFAULT 0,
            conversions INT(10) DEFAULT 0,
            commission DECIMAL(10,2) DEFAULT 0.00,
            last_click DATETIME DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivate() {
        // Optional: cleanup or keep data
    }

    public function admin_menu() {
        add_menu_page('AffiliateBoostPro', 'AffiliateBoostPro', 'manage_options', 'affiliateboostpro', array($this, 'admin_dashboard'), 'dashicons-groups');
    }

    public function admin_dashboard() {
        global $wpdb;
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        echo '<div class="wrap"><h1>AffiliateBoostPro Dashboard</h1>';

        $affiliates = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY conversions DESC LIMIT 20");

        echo '<table class="widefat fixed"><thead><tr><th>Affiliate ID</th><th>User ID</th><th>Clicks</th><th>Conversions</th><th>Commission ($)</th></tr></thead><tbody>';
        foreach ($affiliates as $aff) {
            echo '<tr>' .
                '<td>' . esc_html($aff->affiliate_id) . '</td>' .
                '<td>' . esc_html($aff->user_id) . '</td>' .
                '<td>' . esc_html($aff->clicks) . '</td>' .
                '<td>' . esc_html($aff->conversions) . '</td>' .
                '<td>' . esc_html(number_format($aff->commission, 2)) . '</td>' .
                '</tr>';
        }
        echo '</tbody></table></div>';
    }

    public function enqueue_scripts() {
        // Enqueue JS/CSS if needed
    }

    public function track_affiliate_referral() {
        if (isset($_GET['affid']) && is_numeric($_GET['affid'])) {
            $affid = intval($_GET['affid']);
            global $wpdb;

            $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE affiliate_id = %d", $affid));

            if ($record) {
                $wpdb->update(
                    $this->table_name,
                    array('clicks' => $record->clicks + 1, 'last_click' => current_time('mysql')),
                    array('affiliate_id' => $affid)
                );
            } else {
                $wpdb->insert(
                    $this->table_name,
                    array('affiliate_id' => $affid, 'clicks' => 1, 'last_click' => current_time('mysql')),
                    array('%d', '%d', '%s')
                );
            }

            setcookie('affboost_affid', $affid, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        }

        // On purchase detection (this example just simulates)
        if (isset($_GET['affboost_purchase'])) {
            $affid = isset($_COOKIE['affboost_affid']) ? intval($_COOKIE['affboost_affid']) : 0;
            if ($affid > 0) {
                $commission_rate = 0.10; // 10% commission
                $sale_amount = floatval($_GET['affboost_purchase']);
                $commission = $sale_amount * $commission_rate;

                global $wpdb;
                $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE affiliate_id = %d", $affid));

                if ($record) {
                    $wpdb->update(
                        $this->table_name,
                        array(
                            'conversions' => $record->conversions + 1,
                            'commission' => $record->commission + $commission
                        ),
                        array('affiliate_id' => $affid)
                    );
                }
            }
        }
    }

    public function referral_link_shortcode($atts) {
        if (!is_user_logged_in()) return '';

        $user_id = get_current_user_id();
        $affiliate_id = $user_id; // Simple affiliate ID mapping
        $url = add_query_arg('affid', $affiliate_id, site_url('/'));

        return '<input readonly style="width:100%;padding:8px;" value="' . esc_attr($url) . '" onclick="this.select();" />';
    }
}

new AffiliateBoostPro();