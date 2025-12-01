<?php
/*
Plugin Name: WP Affiliate Deals Tracker
Description: Create and display exclusive affiliate coupons with link tracking to improve monetization.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Affiliate_Deals_Tracker.php
*/

if (!defined('ABSPATH')) { exit; }

class WPAffiliateDealsTracker {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'affiliate_deals';

        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_add_affiliate_deal', array($this, 'handle_form'));
        add_shortcode('affiliate_deals', array($this, 'display_deals'));
        add_action('init', array($this, 'track_click'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(200) NOT NULL,
            description text NOT NULL,
            coupon_code varchar(100) NOT NULL,
            affiliate_url varchar(255) NOT NULL,
            clicks bigint(20) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function admin_menu() {
        add_menu_page('Affiliate Deals', 'Affiliate Deals', 'manage_options', 'affiliate-deals', array($this, 'admin_page'), 'dashicons-tickets-alt', 26);
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $deals = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");

        echo '<div class="wrap"><h1>Affiliate Deals Manager</h1>';
        
        if (isset($_GET['message']) && $_GET['message'] == 'added') {
            echo '<div class="notice notice-success is-dismissible"><p>Deal added successfully.</p></div>';
        }

        echo '<h2>Add New Deal</h2>';
        echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
        echo '<input type="hidden" name="action" value="add_affiliate_deal">';
        wp_nonce_field('add_affiliate_deal_nonce', '_wpnonce_add_affiliate_deal');

        echo '<table class="form-table"><tbody>';
        echo '<tr><th><label for="title">Title</label></th><td><input name="title" type="text" required class="regular-text"></td></tr>';
        echo '<tr><th><label for="description">Description</label></th><td><textarea name="description" required rows="4" class="large-text"></textarea></td></tr>';
        echo '<tr><th><label for="coupon_code">Coupon Code</label></th><td><input name="coupon_code" type="text" required class="regular-text"></td></tr>';
        echo '<tr><th><label for="affiliate_url">Affiliate URL</label></th><td><input name="affiliate_url" type="url" required class="regular-text"></td></tr>';
        echo '</tbody></table>';

        submit_button('Add Deal');
        echo '</form>';

        echo '<h2>Existing Deals</h2>';
        if ($deals) {
            echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Title</th><th>Coupon Code</th><th>Clicks</th><th>Affiliate Link</th></tr></thead><tbody>';
            foreach ($deals as $deal) {
                $link = esc_url(add_query_arg('wpadt', $deal->id, home_url('/')));
                echo '<tr>';
                echo '<td>' . esc_html($deal->title) . '</td>';
                echo '<td><strong>' . esc_html($deal->coupon_code) . '</strong></td>';
                echo '<td>' . intval($deal->clicks) . '</td>';
                echo '<td><a href="' . $link . '" target="_blank" rel="noopener noreferrer">Track Link</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No deals found.</p>';
        }
        echo '</div>';
    }

    public function handle_form() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }
        check_admin_referer('add_affiliate_deal_nonce', '_wpnonce_add_affiliate_deal');

        if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['coupon_code']) || empty($_POST['affiliate_url'])) {
            wp_die('All fields are required.');
        }

        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $coupon_code = sanitize_text_field($_POST['coupon_code']);
        $affiliate_url = esc_url_raw($_POST['affiliate_url']);

        global $wpdb;
        $wpdb->insert(
            $this->table_name,
            array(
                'title' => $title,
                'description' => $description,
                'coupon_code' => $coupon_code,
                'affiliate_url' => $affiliate_url,
                'clicks' => 0
            ),
            array('%s','%s','%s','%s','%d')
        );

        wp_redirect(admin_url('admin.php?page=affiliate-deals&message=added'));
        exit;
    }

    public function display_deals() {
        global $wpdb;
        $deals = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");
        if (!$deals) {
            return '<p>No affiliate deals available at this time.</p>';
        }

        $output = '<div class="wp-affiliate-deals">';
        foreach ($deals as $deal) {
            $link = esc_url(add_query_arg('wpadt', $deal->id, home_url('/')));
            $output .= '<div class="affiliate-deal" style="border:1px solid #ddd;padding:10px;margin-bottom:10px;">';
            $output .= '<h3>' . esc_html($deal->title) . '</h3>';
            $output .= '<p>' . esc_html($deal->description) . '</p>';
            $output .= '<p><strong>Coupon Code: </strong><span style="background:#f0f0f0;padding:2px 6px;border-radius:4px;">' . esc_html($deal->coupon_code) . '</span></p>';
            $output .= '<p><a href="' . $link . '" target="_blank" rel="nofollow noopener noreferrer" style="color:#0073aa;">Grab This Deal</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }

    public function track_click() {
        if (isset($_GET['wpadt'])) {
            $id = intval($_GET['wpadt']);
            global $wpdb;
            $deal = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id));
            if ($deal) {
                $wpdb->update(
                    $this->table_name,
                    array('clicks' => $deal->clicks + 1),
                    array('id' => $id),
                    array('%d'),
                    array('%d')
                );
                wp_redirect($deal->affiliate_url);
                exit;
            } else {
                wp_die('Invalid deal');
            }
        }
    }
}

new WPAffiliateDealsTracker();
