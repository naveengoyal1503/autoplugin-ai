<?php
/*
Plugin Name: Affiliate Deal Booster
Description: Automates creation and display of personalized affiliate coupons with tracking and user incentives.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
License: GPL2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateDealBooster {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliate_deal_booster', array($this, 'render_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_adb_track_click', array($this, 'track_click')); 
        add_action('wp_ajax_nopriv_adb_track_click', array($this, 'track_click'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', 'affiliate-deal-booster', array($this, 'admin_page'), 'dashicons-megaphone');
    }

    public function register_settings() {
        register_setting('adb_settings_group', 'adb_deals');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Deal Booster Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('adb_settings_group'); ?>
                <?php $deals = get_option('adb_deals', array()); ?>
                <table class="form-table" id="adb-deals-table">
                    <thead>
                        <tr><th>Title</th><th>Aff. Link</th><th>Coupon Code</th><th>Expiry Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php
                    if (is_array($deals)) {
                        foreach ($deals as $index => $deal) {
                            echo '<tr>';
                            echo '<td><input type="text" name="adb_deals['.$index.'][title]" value="'.esc_attr($deal['title']).'" required></td>';
                            echo '<td><input type="url" name="adb_deals['.$index.'][link]" value="'.esc_url($deal['link']).'" required></td>';
                            echo '<td><input type="text" name="adb_deals['.$index.'][coupon]" value="'.esc_attr($deal['coupon']).'"></td>';
                            echo '<td><input type="date" name="adb_deals['.$index.'][expiry]" value="'.esc_attr($deal['expiry']).'"></td>';
                            echo '<td><button type="button" class="button adb-remove-row">Remove</button></td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <p><button type="button" class="button button-primary" id="adb-add-row">Add New Deal</button></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            (function($) {
                $('#adb-add-row').on('click', function() {
                    var now = Date.now();
                    var newRow = '<tr>' +
                        '<td><input type="text" name="adb_deals['+now+'][title]" required></td>' +
                        '<td><input type="url" name="adb_deals['+now+'][link]" required></td>' +
                        '<td><input type="text" name="adb_deals['+now+'][coupon]"></td>' +
                        '<td><input type="date" name="adb_deals['+now+'][expiry]"></td>' +
                        '<td><button type="button" class="button adb-remove-row">Remove</button></td>' +
                        '</tr>';
                    $('#adb-deals-table tbody').append(newRow);
                });

                $(document).on('click', '.adb-remove-row', function(){
                    $(this).closest('tr').remove();
                });
            })(jQuery);
        </script>
        <?php
    }

    public function enqueue_assets() {
        wp_enqueue_style('adb-styles', plugins_url('style.css', __FILE__));
        wp_enqueue_script('adb-script', plugins_url('script.js', __FILE__), array('jquery'), false, true);
        wp_localize_script('adb-script', 'adb_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function render_deals() {
        $deals = get_option('adb_deals', array());
        if (!$deals || empty($deals)) {
            return '<p>No affiliate deals available at the moment.</p>';
        }
        $output = '<div class="adb-deals-list">';
        foreach ($deals as $id => $deal) {
            $title = esc_html($deal['title']);
            $link = esc_url($deal['link']);
            $coupon = isset($deal['coupon']) ? esc_html($deal['coupon']) : '';
            $expiry = isset($deal['expiry']) ? sanitize_text_field($deal['expiry']) : '';

            // Check expiry
            if ($expiry && strtotime($expiry) < time()) continue;

            $coupon_html = $coupon ? '<span class="adb-coupon">Coupon: <strong>' . $coupon . '</strong></span>' : '';

            $output .= '<div class="adb-deal" data-id="' . esc_attr($id) . '">';
            $output .= '<a href="#" class="adb-deal-link" data-url="' . $link . '">' . $title . '</a> ' . $coupon_html;
            $output .= '</div>';
        }
        $output .= '</div>';
        $output .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
          document.querySelectorAll(".adb-deal-link").forEach(function(el){
            el.addEventListener("click", function(e){
              e.preventDefault();
              var url = this.getAttribute("data-url");
              var dealDiv = this.closest(".adb-deal");
              var dealId = dealDiv.getAttribute("data-id");
              fetch("' . admin_url('admin-ajax.php') . '?action=adb_track_click&deal_id=" + dealId, {method: "POST"});
              window.open(url, "_blank");
            });
          });
        });
        </script>';
        return $output;
    }

    public function track_click() {
        if (!isset($_POST['deal_id'])) wp_send_json_error();
        $deal_id = sanitize_text_field($_POST['deal_id']);
        $counts = get_option('adb_click_counts', array());
        if (!isset($counts[$deal_id])) $counts[$deal_id] = 0;
        $counts[$deal_id]++;
        update_option('adb_click_counts', $counts);
        wp_send_json_success();
    }
}

new AffiliateDealBooster();
