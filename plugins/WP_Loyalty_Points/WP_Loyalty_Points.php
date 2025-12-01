<?php
/*
Plugin Name: WP Loyalty Points
Description: Reward users with loyalty points for actions and let them redeem for rewards.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Loyalty_Points.php
*/

define('WP_LOYALTY_POINTS_VERSION', '1.0');

class WPLoyaltyPoints {
    public function __construct() {
        add_action('init', array($this, 'init_plugin'));
        add_action('comment_post', array($this, 'reward_comment'));
        add_action('woocommerce_order_status_completed', array($this, 'reward_purchase'));
        add_action('wp_ajax_redeem_points', array($this, 'redeem_points'));
        add_action('wp_ajax_nopriv_redeem_points', array($this, 'redeem_points'));
        add_shortcode('loyalty_points', array($this, 'points_shortcode'));
    }

    public function init_plugin() {
        if (!get_option('wp_loyalty_points_init')) {
            $this->create_tables();
            update_option('wp_loyalty_points_init', true);
        }
    }

    private function create_tables() {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_points';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            points int NOT NULL,
            reason text,
            date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function reward_comment($comment_id) {
        $comment = get_comment($comment_id);
        if ($comment->user_id) {
            $this->add_points($comment->user_id, 5, 'Comment');
        }
    }

    public function reward_purchase($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        if ($user_id) {
            $points = round($order->get_total() * 10);
            $this->add_points($user_id, $points, 'Purchase');
        }
    }

    public function add_points($user_id, $points, $reason = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_points';
        $wpdb->insert($table, array(
            'user_id' => $user_id,
            'points' => $points,
            'reason' => $reason
        ));
    }

    public function get_points($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_points';
        $points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM $table WHERE user_id = %d",
            $user_id
        ));
        return $points ? $points : 0;
    }

    public function redeem_points() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Login required');
        }
        $points = intval($_POST['points']);
        $current = $this->get_points($user_id);
        if ($points > $current) {
            wp_send_json_error('Not enough points');
        }
        $this->add_points($user_id, -$points, 'Redeemed');
        wp_send_json_success('Points redeemed');
    }

    public function points_shortcode($atts) {
        $user_id = get_current_user_id();
        if (!$user_id) return 'Login to see your points';
        $points = $this->get_points($user_id);
        return "Your points: $points";
    }
}

new WPLoyaltyPoints();
