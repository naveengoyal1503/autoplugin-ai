<?php
/*
Plugin Name: Affiliate Coupon Booster
Description: Aggregates affiliate coupons, displays targeted offers, and tracks conversions to maximize affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateCouponBooster {
    private $coupons = array();

    public function __construct() {
        add_shortcode('affiliate_coupons', array($this, 'render_coupons'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_acb_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acb_track_click', array($this, 'track_click'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_acb_save_coupon', array($this, 'save_coupon'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acb-script', plugin_dir_url(__FILE__) . 'acb-script.js', array('jquery'), '1.0', true);
        wp_localize_script('acb-script', 'acbAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    // Admin menu for coupon management
    public function admin_menu() {
        add_menu_page('Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'acb-coupons', array($this, 'admin_page'), 'dashicons-tickets', 60);
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) return;

        $coupons = get_option('acb_coupons', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Booster</h1>
            <h2>Add New Coupon</h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="acb_save_coupon">
                <?php wp_nonce_field('acb_save_coupon_nonce'); ?>
                <table class="form-table">
                    <tr><th><label for="code">Coupon Code</label></th><td><input type="text" name="code" id="code" required></td></tr>
                    <tr><th><label for="description">Description</label></th><td><input type="text" name="description" id="description" required></td></tr>
                    <tr><th><label for="affiliate_url">Affiliate URL</label></th><td><input type="url" name="affiliate_url" id="affiliate_url" required></td></tr>
                    <tr><th><label for="network">Affiliate Network</label></th><td><input type="text" name="network" id="network" placeholder="e.g. Amazon, ShareASale" required></td></tr>
                    <tr><th><label for="categories">Categories (comma separated)</label></th><td><input type="text" name="categories" id="categories"></td></tr>
                    <tr><th><label for="expires">Expiration Date</label></th><td><input type="date" name="expires" id="expires"></td></tr>
                </table>
                <input type="submit" class="button button-primary" value="Add Coupon">
            </form>
            <h2>Existing Coupons</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Code</th><th>Description</th><th>Network</th><th>Expires</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($coupons as $index => $coupon) : ?>
                    <tr>
                        <td><?php echo esc_html($coupon['code']); ?></td>
                        <td><?php echo esc_html($coupon['description']); ?></td>
                        <td><?php echo esc_html($coupon['network']); ?></td>
                        <td><?php echo esc_html($coupon['expires']); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg(array('acb_delete'=>$index))); ?>" onclick="return confirm('Delete this coupon?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php

        // Handle deletion
        if (isset($_GET['acb_delete'])) {
            $index = intval($_GET['acb_delete']);
            if (isset($coupons[$index])) {
                unset($coupons[$index]);
                update_option('acb_coupons', $coupons);
                wp_redirect(admin_url('admin.php?page=acb-coupons'));
                exit;
            }
        }
    }

    public function save_coupon() {
        if (!current_user_can('manage_options') || !check_admin_referer('acb_save_coupon_nonce')) {
            wp_die('Permission denied');
        }

        $coupons = get_option('acb_coupons', array());
        $coupons[] = array(
            'code' => sanitize_text_field($_POST['code']),
            'description' => sanitize_text_field($_POST['description']),
            'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
            'network' => sanitize_text_field($_POST['network']),
            'categories' => sanitize_text_field($_POST['categories']),
            'expires' => sanitize_text_field($_POST['expires'])
        );

        update_option('acb_coupons', $coupons);
        wp_redirect(admin_url('admin.php?page=acb-coupons'));
        exit;
    }

    // Render coupons on frontend via shortcode
    public function render_coupons($atts) {
        $atts = shortcode_atts(array('category' => ''), $atts);
        $coupons = get_option('acb_coupons', array());
        $output = '<div class="acb-coupons">';
        $now = date('Y-m-d');
        $filtered = array_filter($coupons, function($c) use ($atts, $now) {
            $not_expired = empty($c['expires']) || $c['expires'] >= $now;
            $in_category = empty($atts['category']) || stripos($c['categories'], $atts['category']) !== false;
            return $not_expired && $in_category;
        });

        if (empty($filtered)) {
            $output .= '<p>No coupons available currently.</p>';
        } else {
            $output .= '<ul>';
            foreach ($filtered as $idx => $coupon) {
                $esc_url = esc_url(add_query_arg(array('acb_track' => $idx), site_url()));
                // Use JavaScript click tracking with AJAX
                $output .= '<li><strong>' . esc_html($coupon['code']) . '</strong>: ' . esc_html($coupon['description']) . ' - <a href="#" class="acb-aff-link" data-url="' . esc_attr($coupon['affiliate_url']) . '">Get Deal</a></li>';
            }
            $output .= '</ul>';
        }
        $output .= '</div>';
        return $output;
    }

    // Track click via AJAX
    public function track_click() {
        if (isset($_POST['url'])) {
            $url = esc_url_raw($_POST['url']);
            // Could implement real tracking here (database increment etc.)
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
        wp_die();
    }
}

new AffiliateCouponBooster();

// Simple inline JS for click tracking
add_action('wp_footer', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const links = document.querySelectorAll('.acb-aff-link');
        links.forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                const url = this.getAttribute('data-url');
                if (!url) return;
                jQuery.post(acbAjax.ajaxurl, { action: 'acb_track_click', url: url }, function() {
                    window.open(url, '_blank');
                });
            });
        });
    });
    </script>
    <?php
});