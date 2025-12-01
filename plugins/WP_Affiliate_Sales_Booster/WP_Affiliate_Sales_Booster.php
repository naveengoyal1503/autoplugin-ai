<?php
/*
Plugin Name: WP Affiliate Sales Booster
Description: Generate dynamic affiliate coupon and deal pages to boost conversions and revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Affiliate_Sales_Booster.php
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WPAffiliateSalesBooster {
    private $plugin_slug = 'wp-affiliate-sales-booster';

    public function __construct() {
        add_action('init', array($this, 'register_shortcode'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'insert_affiliate_deal_link'));
    }

    public function register_shortcode() {
        add_shortcode('affiliate_deals', array($this, 'affiliate_deals_shortcode'));
    }

    public function affiliate_deals_shortcode($atts) {
        $atts = shortcode_atts(array('category' => ''), $atts, 'affiliate_deals');
        $category = sanitize_text_field($atts['category']);

        $coupons = get_option('wp_affiliate_coupons', array());

        $filtered = array();
        if (!empty($category)) {
            foreach ($coupons as $coupon) {
                if (strcasecmp($coupon['category'], $category) === 0) {
                    $filtered[] = $coupon;
                }
            }
        } else {
            $filtered = $coupons;
        }

        if (empty($filtered)) {
            return '<p>No deals available at the moment. Please check back soon.</p>';
        }

        $output = '<div class="wp-affiliate-deals">';
        foreach ($filtered as $coupon) {
            $output .= '<div class="wp-affiliate-deal" style="border:1px solid #ddd;padding:12px;margin-bottom:10px;">';
            $output .= '<h3 style="margin:0 0 8px 0;">' . esc_html($coupon['title']) . '</h3>';
            $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            $output .= '<p><strong>Discount:</strong> ' . esc_html($coupon['discount']) . '</p>';
            $output .= '<p><a href="' . esc_url($coupon['affiliate_link']) . '" target="_blank" rel="nofollow noopener noreferrer" style="color:#0073aa;text-decoration:none;font-weight:bold;">Grab this deal</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Deals', 'Affiliate Deals', 'manage_options', $this->plugin_slug, array($this, 'admin_page'), 'dashicons-tickets-alt', 60);
    }

    public function register_settings() {
        register_setting('wp_affiliate_sales_booster_group', 'wp_affiliate_coupons', array($this, 'validate_coupons'));
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Affiliate Sales Booster - Manage Coupons</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_affiliate_sales_booster_group');
                $coupons = get_option('wp_affiliate_coupons', array());
                ?>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width:20%;">Title</th>
                            <th style="width:30%;">Description</th>
                            <th style="width:15%;">Discount</th>
                            <th style="width:25%;">Affiliate Link</th>
                            <th style="width:10%;">Category</th>
                        </tr>
                    </thead>
                    <tbody id="coupons-table-body">
                        <?php
                        if (empty($coupons)) {
                            $coupons = array(
                                array('title' => '', 'description' => '', 'discount' => '', 'affiliate_link' => '', 'category' => '')
                            );
                        }
                        foreach ($coupons as $index => $coupon) {
                            echo '<tr>';
                            echo '<td><input type="text" name="wp_affiliate_coupons[' . $index . '][title]" value="' . esc_attr($coupon['title']) . '" style="width:100%"></td>';
                            echo '<td><input type="text" name="wp_affiliate_coupons[' . $index . '][description]" value="' . esc_attr($coupon['description']) . '" style="width:100%"></td>';
                            echo '<td><input type="text" name="wp_affiliate_coupons[' . $index . '][discount]" value="' . esc_attr($coupon['discount']) . '" style="width:100%"></td>';
                            echo '<td><input type="url" name="wp_affiliate_coupons[' . $index . '][affiliate_link]" value="' . esc_attr($coupon['affiliate_link']) . '" style="width:100%"></td>';
                            echo '<td><input type="text" name="wp_affiliate_coupons[' . $index . '][category]" value="' . esc_attr($coupon['category']) . '" style="width:100%"></td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="add-coupon">Add New Coupon</button></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            document.getElementById('add-coupon').addEventListener('click', function() {
                var tbody = document.getElementById('coupons-table-body');
                var index = tbody.children.length;
                var tr = document.createElement('tr');

                tr.innerHTML =
                    '<td><input type="text" name="wp_affiliate_coupons[' + index + '][title]" style="width:100%"></td>' +
                    '<td><input type="text" name="wp_affiliate_coupons[' + index + '][description]" style="width:100%"></td>' +
                    '<td><input type="text" name="wp_affiliate_coupons[' + index + '][discount]" style="width:100%"></td>' +
                    '<td><input type="url" name="wp_affiliate_coupons[' + index + '][affiliate_link]" style="width:100%"></td>' +
                    '<td><input type="text" name="wp_affiliate_coupons[' + index + '][category]" style="width:100%"></td>';

                tbody.appendChild(tr);
            });
        </script>
        <?php
    }

    public function validate_coupons($input) {
        $valid = array();
        if (!is_array($input)) {
            return $valid;
        }
        foreach ($input as $coupon) {
            if (empty($coupon['title']) || empty($coupon['affiliate_link'])) {
                continue;
            }
            $valid[] = array(
                'title' => sanitize_text_field($coupon['title']),
                'description' => sanitize_text_field($coupon['description']),
                'discount' => sanitize_text_field($coupon['discount']),
                'affiliate_link' => esc_url_raw($coupon['affiliate_link']),
                'category' => sanitize_text_field($coupon['category'])
            );
        }
        return $valid;
    }

    // Optional: Automatically insert a random deal link at the end of posts
    public function insert_affiliate_deal_link($content) {
        if (is_singular('post')) {
            $coupons = get_option('wp_affiliate_coupons', array());
            if (!empty($coupons)) {
                $coupon = $coupons[array_rand($coupons)];
                $deal_html = '<div class="wp-affiliate-inline-deal" style="border:1px solid #ddd;padding:10px;margin-top:20px;background:#f9f9f9;">';
                $deal_html .= '<h4>Special Deal: ' . esc_html($coupon['title']) . '</h4>';
                $deal_html .= '<p>' . esc_html($coupon['description']) . '</p>';
                $deal_html .= '<p><a href="' . esc_url($coupon['affiliate_link']) . '" target="_blank" rel="nofollow noopener noreferrer" style="color:#0073aa;font-weight:bold;">Grab this offer</a></p>';
                $deal_html .= '</div>';
                $content .= $deal_html;
            }
        }
        return $content;
    }
}

new WPAffiliateSalesBooster();
