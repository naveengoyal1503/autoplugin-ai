<?php
/*
Plugin Name: WP Revenue Booster
Description: Maximize revenue by rotating and optimizing affiliate links, ads, and sponsored content.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

if (!defined('ABSPATH')) {
    exit;
}

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_content'));
        add_action('wp_ajax_save_revenue_data', array($this, 'save_revenue_data'));
        add_action('wp_ajax_nopriv_save_revenue_data', array($this, 'save_revenue_data'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            6
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_revenue_booster'); ?>
                <?php do_settings_sections('wp_revenue_booster'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Affiliate Links</th>
                        <td><textarea name="affiliate_links" rows="5" cols="50"><?php echo esc_textarea(get_option('affiliate_links')); ?></textarea><br />
                        <small>Enter affiliate links (one per line)</small></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Ad Codes</th>
                        <td><textarea name="ad_codes" rows="5" cols="50"><?php echo esc_textarea(get_option('ad_codes')); ?></textarea><br />
                        <small>Enter ad codes (one per line)</small></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Sponsored Content</th>
                        <td><textarea name="sponsored_content" rows="5" cols="50"><?php echo esc_textarea(get_option('sponsored_content')); ?></textarea><br />
                        <small>Enter sponsored content (one per line)</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function inject_content() {
        $affiliate_links = get_option('affiliate_links', '');
        $ad_codes = get_option('ad_codes', '');
        $sponsored_content = get_option('sponsored_content', '');

        $links = explode('\n', $affiliate_links);
        $ads = explode('\n', $ad_codes);
        $content = explode('\n', $sponsored_content);

        $all_items = array();
        foreach ($links as $link) {
            if (!empty($link)) {
                $all_items[] = array('type' => 'affiliate', 'content' => $link);
            }
        }
        foreach ($ads as $ad) {
            if (!empty($ad)) {
                $all_items[] = array('type' => 'ad', 'content' => $ad);
            }
        }
        foreach ($content as $item) {
            if (!empty($item)) {
                $all_items[] = array('type' => 'sponsored', 'content' => $item);
            }
        }

        if (!empty($all_items)) {
            $random_item = $all_items[array_rand($all_items)];
            echo '<div class="wp-revenue-booster-item" data-type="' . $random_item['type'] . '">' . $random_item['content'] . '</div>';
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var item = document.querySelector(".wp-revenue-booster-item");
                    item.addEventListener("click", function() {
                        var data = {
                            action: "save_revenue_data",
                            type: this.getAttribute("data-type")
                        };
                        jQuery.post(ajaxurl, data);
                    });
                });
            </script>';
        }
    }

    public function save_revenue_data() {
        $type = sanitize_text_field($_POST['type']);
        $count_key = 'revenue_booster_clicks_' . $type;
        $count = get_option($count_key, 0);
        update_option($count_key, $count + 1);
        wp_die();
    }
}

new WP_Revenue_Booster();
?>