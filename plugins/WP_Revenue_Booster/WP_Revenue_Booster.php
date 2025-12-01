/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Boost revenue by rotating affiliate offers, coupons, and sponsored content based on user context.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin main class
class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('wp_revenue_booster', array($this, 'render_shortcode'));
        add_action('wp_ajax_save_offer_click', array($this, 'save_offer_click'));
        add_action('wp_ajax_nopriv_save_offer_click', array($this, 'save_offer_click'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line'
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        $offers = get_option('wp_revenue_booster_offers', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="">
                <?php wp_nonce_field('wp_revenue_booster_save'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="offer_title">Offer Title</label></th>
                        <td><input type="text" id="offer_title" name="offer_title" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="offer_url">Offer URL</label></th>
                        <td><input type="url" id="offer_url" name="offer_url" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="offer_type">Offer Type</label></th>
                        <td>
                            <select id="offer_type" name="offer_type">
                                <option value="affiliate">Affiliate</option>
                                <option value="coupon">Coupon</option>
                                <option value="sponsored">Sponsored</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="offer_content">Offer Content</label></th>
                        <td><textarea id="offer_content" name="offer_content" rows="5" class="large-text"></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_offer" id="submit" class="button button-primary" value="Add Offer" />
                </p>
            </form>
            <h2>Existing Offers</h2>
            <table class="widefat fixed">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offers as $key => $offer): ?>
                    <tr>
                        <td><?php echo esc_html($offer['title']); ?></td>
                        <td><?php echo esc_html($offer['type']); ?></td>
                        <td><a href="<?php echo esc_url($offer['url']); ?>" target="_blank">Link</a></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('wp_revenue_booster_delete'); ?>
                                <input type="hidden" name="delete_key" value="<?php echo $key; ?>" />
                                <input type="submit" class="button" value="Delete" />
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php

        if (isset($_POST['submit_offer']) && wp_verify_nonce($_POST['_wpnonce'], 'wp_revenue_booster_save')) {
            $new_offer = array(
                'title' => sanitize_text_field($_POST['offer_title']),
                'url' => esc_url_raw($_POST['offer_url']),
                'type' => sanitize_text_field($_POST['offer_type']),
                'content' => wp_kses_post($_POST['offer_content'])
            );
            $offers[] = $new_offer;
            update_option('wp_revenue_booster_offers', $offers);
            wp_redirect(admin_url('admin.php?page=wp-revenue-booster'));
            exit;
        }

        if (isset($_POST['delete_key']) && wp_verify_nonce($_POST['_wpnonce'], 'wp_revenue_booster_delete')) {
            $key = intval($_POST['delete_key']);
            if (isset($offers[$key])) {
                unset($offers[$key]);
                $offers = array_values($offers);
                update_option('wp_revenue_booster_offers', $offers);
                wp_redirect(admin_url('admin.php?page=wp-revenue-booster'));
                exit;
            }
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster-js', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-booster-js', 'wp_revenue_booster_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    public function render_shortcode($atts) {
        $offers = get_option('wp_revenue_booster_offers', array());
        if (empty($offers)) return '';

        $offer = $offers[array_rand($offers)];
        $output = '<div class="wp-revenue-booster-offer">
            <h3>' . esc_html($offer['title']) . '</h3>
            <p>' . wp_kses_post($offer['content']) . '</p>
            <a href="#" class="wp-revenue-booster-link" data-offer-id="' . $offer['url'] . '" target="_blank">Click Here</a>
        </div>';
        return $output;
    }

    public function save_offer_click() {
        $url = esc_url_raw($_POST['url']);
        $clicks = get_option('wp_revenue_booster_clicks', array());
        $clicks[$url] = isset($clicks[$url]) ? $clicks[$url] + 1 : 1;
        update_option('wp_revenue_booster_clicks', $clicks);
        wp_die();
    }
}

new WP_Revenue_Booster();

// JavaScript for tracking clicks
function wp_revenue_booster_enqueue_script() {
    wp_enqueue_script('wp-revenue-booster-js', plugins_url('js/script.js', __FILE__), array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'wp_revenue_booster_enqueue_script');

// Script.js content
function wp_revenue_booster_script() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.wp-revenue-booster-link').on('click', function(e) {
            e.preventDefault();
            var url = $(this).data('offer-id');
            $.post(wp_revenue_booster_ajax.ajax_url, {
                action: 'save_offer_click',
                url: url
            }, function() {
                window.open(url, '_blank');
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'wp_revenue_booster_script');
