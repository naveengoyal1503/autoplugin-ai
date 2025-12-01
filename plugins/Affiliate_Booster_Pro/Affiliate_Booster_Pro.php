<?php
/*
Plugin Name: Affiliate Booster Pro
Description: Create and manage a customizable affiliate marketing program with automated link cloaking and commission tracking.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/

if (!defined('ABSPATH')) { exit; }

class AffiliateBoosterPro {
    private $plugin_slug = 'affiliate_booster_pro';
    private $affiliates_option = 'abp_affiliates';
    private $commissions_option = 'abp_commissions';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_post_abp_add_affiliate', [$this, 'handle_add_affiliate']);
        add_shortcode('abp_affiliate_link', [$this, 'affiliate_link_shortcode']);
        add_action('wp', [$this, 'track_commission']);
        add_action('admin_post_abp_payout_commissions', [$this, 'handle_payout_commissions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_' . $this->plugin_slug) return;
        wp_enqueue_style('abp_admin_css', plugin_dir_url(__FILE__) . 'admin.css');
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Booster Pro', 'Affiliate Booster', 'manage_options', $this->plugin_slug, [$this, 'admin_page'], 'dashicons-megaphone');
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }
        $affiliates = get_option($this->affiliates_option, []);
        $commissions = get_option($this->commissions_option, []);

        echo '<div class="wrap"><h1>Affiliate Booster Pro</h1>';
        echo '<h2>Add New Affiliate</h2>';
        echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
        echo '<input type="hidden" name="action" value="abp_add_affiliate">';
        wp_nonce_field('abp_add_affiliate_nonce', 'abp_nonce');
        echo '<table class="form-table"><tbody>';
        echo '<tr><th><label for="affiliate_name">Name</label></th><td><input name="affiliate_name" type="text" required></td></tr>';
        echo '<tr><th><label for="affiliate_email">Email</label></th><td><input name="affiliate_email" type="email" required></td></tr>';
        echo '</tbody></table>';
        submit_button('Add Affiliate');
        echo '</form>';

        echo '<h2>Affiliates</h2>';
        if (count($affiliates) === 0) {
            echo '<p>No affiliates added yet.</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Name</th><th>Email</th><th>Affiliate ID</th><th>Total Commissions ($)</th></tr></thead><tbody>';
            foreach ($affiliates as $id => $affiliate) {
                $total_commission = 0.0;
                foreach ($commissions as $commission) {
                    if ($commission['affiliate_id'] === $id && !$commission['paid']) {
                        $total_commission += floatval($commission['amount']);
                    }
                }
                echo '<tr><td>' . esc_html($affiliate['name']) . '</td><td>' . esc_html($affiliate['email']) . '</td><td>' . esc_html($id) . '</td><td>' . number_format($total_commission, 2) . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
            echo '<input type="hidden" name="action" value="abp_payout_commissions">';
            wp_nonce_field('abp_payout_commissions_nonce', 'abp_nonce');
            submit_button('Mark All Commissions as Paid');
            echo '</form>';
        }
        echo '</div>';
    }

    public function handle_add_affiliate() {
        if (!current_user_can('manage_options') || !isset($_POST['abp_nonce']) || !wp_verify_nonce($_POST['abp_nonce'], 'abp_add_affiliate_nonce')) {
            wp_die('Unauthorized request');
        }
        $name = sanitize_text_field($_POST['affiliate_name'] ?? '');
        $email = sanitize_email($_POST['affiliate_email'] ?? '');
        if (empty($name) || empty($email)) {
            wp_redirect(admin_url('admin.php?page=' . $this->plugin_slug . '&error=missing_fields'));
            exit;
        }
        $affiliates = get_option($this->affiliates_option, []);
        // Generate unique affiliate ID
        $affiliate_id = 'aff_' . md5($email . time());
        $affiliates[$affiliate_id] = ['name' => $name, 'email' => $email];
        update_option($this->affiliates_option, $affiliates);
        wp_redirect(admin_url('admin.php?page=' . $this->plugin_slug . '&success=affiliate_added'));
        exit;
    }

    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(['id' => ''], $atts, 'abp_affiliate_link');
        $affiliate_id = sanitize_text_field($atts['id']);
        if (empty($affiliate_id)) {
            return 'Affiliate ID not specified';
        }
        $target_url = home_url('/');
        // Build cloaked affiliate link
        $link = add_query_arg('ref', $affiliate_id, $target_url);
        return esc_url($link);
    }

    public function track_commission() {
        if (!isset($_GET['ref'])) return;
        $affiliate_id = sanitize_text_field($_GET['ref']);
        $affiliates = get_option($this->affiliates_option, []);
        if (!isset($affiliates[$affiliate_id])) return;
        // Simulate commission tracking: For demo, add $1 per visit with referral param
        $commissions = get_option($this->commissions_option, []);
        $commissions[] = [
            'affiliate_id' => $affiliate_id,
            'amount' => 1.00,
            'paid' => false,
            'date' => current_time('mysql')
        ];
        update_option($this->commissions_option, $commissions);
    }

    public function handle_payout_commissions() {
        if (!current_user_can('manage_options') || !isset($_POST['abp_nonce']) || !wp_verify_nonce($_POST['abp_nonce'], 'abp_payout_commissions_nonce')) {
            wp_die('Unauthorized request');
        }
        $commissions = get_option($this->commissions_option, []);
        // Mark all unpaid commissions as paid
        foreach ($commissions as &$commission) {
            if (!$commission['paid']) {
                $commission['paid'] = true;
            }
        }
        update_option($this->commissions_option, $commissions);
        wp_redirect(admin_url('admin.php?page=' . $this->plugin_slug . '&success=payout_complete'));
        exit;
    }
}

new AffiliateBoosterPro();
