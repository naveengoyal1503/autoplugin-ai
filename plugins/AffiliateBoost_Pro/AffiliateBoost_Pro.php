<?php
/*
Plugin Name: AffiliateBoost Pro
Plugin URI: https://example.com/affiliateboost-pro
Description: Create and manage affiliate coupon & deal campaigns to boost affiliate marketing earnings.
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateBoost_Pro.php
License: GPL2
Text Domain: affiliateboost-pro
*/

if (!defined('ABSPATH')) { exit; }

class AffiliateBoostPro {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliateboost_coupons', array($this, 'render_coupon_list'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_head', array($this, 'track_affiliate_click'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('AffiliateBoost Pro', 'affiliateboost-pro'),
            __('AffiliateBoost', 'affiliateboost-pro'),
            'manage_options',
            'affiliateboost-pro',
            array($this, 'admin_page')
        );
    }

    public function register_settings() {
        register_setting('affiliateboost_settings_group', 'affiliateboost_coupons');
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'affiliateboost-pro'));
        }

        // Handle form submission
        if (isset($_POST['affiliateboost_add_coupon'])) {
            check_admin_referer('affiliateboost_add_coupon_nonce');
            $coupons = get_option('affiliateboost_coupons', array());

            $new_coupon = array(
                'id' => uniqid('abp_'),
                'title' => sanitize_text_field($_POST['title']),
                'code' => sanitize_text_field($_POST['code']),
                'description' => sanitize_textarea_field($_POST['description']),
                'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
                'expires' => sanitize_text_field($_POST['expires'])
            );
            $coupons[] = $new_coupon;
            update_option('affiliateboost_coupons', $coupons);
            echo '<div class="updated"><p>' . __('Coupon added successfully.', 'affiliateboost-pro') . '</p></div>';
        }

        // Display all coupons
        $coupons = get_option('affiliateboost_coupons', array());

        ?>
        <div class="wrap">
            <h1><?php _e('AffiliateBoost Pro Coupons', 'affiliateboost-pro'); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field('affiliateboost_add_coupon_nonce'); ?>
                <table class="form-table" style="max-width:600px;">
                    <tr>
                        <th scope="row"><label for="title"><?php _e('Title', 'affiliateboost-pro'); ?></label></th>
                        <td><input type="text" id="title" name="title" required class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="code"><?php _e('Coupon Code', 'affiliateboost-pro'); ?></label></th>
                        <td><input type="text" id="code" name="code" required class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="description"><?php _e('Description', 'affiliateboost-pro'); ?></label></th>
                        <td><textarea id="description" name="description" rows="3" required class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="affiliate_url"><?php _e('Affiliate URL', 'affiliateboost-pro'); ?></label></th>
                        <td><input type="url" id="affiliate_url" name="affiliate_url" required class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="expires"><?php _e('Expiry Date (YYYY-MM-DD)', 'affiliateboost-pro'); ?></label></th>
                        <td><input type="date" id="expires" name="expires" /></td>
                    </tr>
                </table>
                <p><input type="submit" name="affiliateboost_add_coupon" class="button button-primary" value="<?php _e('Add Coupon', 'affiliateboost-pro'); ?>" /></p>
            </form>
            <h2><?php _e('Current Coupons', 'affiliateboost-pro'); ?></h2>
            <table class="widefat fixed" cellspacing="0" style="max-width:700px;">
                <thead>
                    <tr>
                        <th><?php _e('Title', 'affiliateboost-pro'); ?></th>
                        <th><?php _e('Code', 'affiliateboost-pro'); ?></th>
                        <th><?php _e('Description', 'affiliateboost-pro'); ?></th>
                        <th><?php _e('Affiliate URL', 'affiliateboost-pro'); ?></th>
                        <th><?php _e('Expires', 'affiliateboost-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($coupons)) : ?>
                        <?php foreach ($coupons as $coupon) :
                            $expired = ($coupon['expires'] && strtotime($coupon['expires']) < time());
                            ?>
                            <tr style="<?php echo $expired ? 'color: #999;' : ''; ?>">
                                <td><?php echo esc_html($coupon['title']); ?></td>
                                <td><strong><?php echo esc_html($coupon['code']); ?></strong></td>
                                <td><?php echo esc_html($coupon['description']); ?></td>
                                <td><a href="<?php echo esc_url($coupon['affiliate_url']); ?>" target="_blank" rel="nofollow noopener noreferrer"><?php echo esc_html($coupon['affiliate_url']); ?></a></td>
                                <td><?php echo esc_html($coupon['expires'] ? $coupon['expires'] : '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="5"><?php _e('No coupons available.', 'affiliateboost-pro'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <h2><?php _e('Usage', 'affiliateboost-pro'); ?></h2>
            <p><?php _e('Use the shortcode [affiliateboost_coupons] to display the coupons on any page or post.', 'affiliateboost-pro'); ?></p>
        </div>
        <?php
    }

    public function enqueue_assets() {
        wp_enqueue_style('affiliateboost-style', plugin_dir_url(__FILE__) . 'affiliateboost-style.css');
    }

    public function render_coupon_list() {
        $coupons = get_option('affiliateboost_coupons', array());
        $output = '<div class="affiliateboost-coupons">';
        if (!empty($coupons)) {
            foreach ($coupons as $coupon) {
                $expired = ($coupon['expires'] && strtotime($coupon['expires']) < time());
                if ($expired) { continue; } // Skip expired coupons

                $url = add_query_arg('affiliateboost_track', urlencode($coupon['id']), $coupon['affiliate_url']);

                $output .= '<div class="affiliateboost-coupon">';
                $output .= '<h3>' . esc_html($coupon['title']) . '</h3>';
                $output .= '<p>' . esc_html($coupon['description']) . '</p>';
                $output .= '<p><strong>' . __('Use Code:', 'affiliateboost-pro') . ' </strong>' . esc_html($coupon['code']) . '</p>';
                $output .= '<p><a href="' . esc_url($url) . '" class="affiliateboost-link" target="_blank" rel="nofollow noopener noreferrer">' . __('Get this Deal', 'affiliateboost-pro') . '</a></p>';
                $output .= '</div>';
            }
        } else {
            $output .= '<p>' . __('No coupons available at the moment.', 'affiliateboost-pro') . '</p>';
        }
        $output .= '</div>';
        return $output;
    }

    public function track_affiliate_click() {
        if (isset($_GET['affiliateboost_track'])) {
            $coupon_id = sanitize_text_field($_GET['affiliateboost_track']);
            $clicks = get_option('affiliateboost_clicks', array());
            if (!isset($clicks[$coupon_id])) {
                $clicks[$coupon_id] = 0;
            }
            $clicks[$coupon_id]++;
            update_option('affiliateboost_clicks', $clicks);

            // Redirect to affiliate URL if coupon ID is valid
            $coupons = get_option('affiliateboost_coupons', array());
            foreach ($coupons as $coupon) {
                if ($coupon['id'] === $coupon_id) {
                    wp_redirect($coupon['affiliate_url']);
                    exit;
                }
            }
        }
    }
}

new AffiliateBoostPro();
