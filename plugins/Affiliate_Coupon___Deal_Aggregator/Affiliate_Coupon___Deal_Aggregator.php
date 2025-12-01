<?php
/*
Plugin Name: Affiliate Coupon & Deal Aggregator
Description: Aggregate affiliate coupons and deals with tracking to monetize your WordPress site.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon___Deal_Aggregator.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateCouponAggregator {
    private static $instance = null;
    private $coupons = [];
    private $option_name = 'aca_coupons_data';

    public static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Load coupons on init
        add_action('init', [$this, 'load_coupons']);
        // Shortcode to display coupons
        add_shortcode('affiliate_coupons', [$this, 'render_coupons_shortcode']);
        // Admin menu
        add_action('admin_menu', [$this, 'admin_menu']);
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function load_coupons() {
        $stored = get_option($this->option_name, []);
        if (!empty($stored)) {
            $this->coupons = $stored;
        }
    }

    public function admin_menu() {
        add_menu_page('Coupon Aggregator', 'Coupon Aggregator', 'manage_options', 'aca-settings', [$this, 'settings_page'], 'dashicons-tickets-alt');
    }

    public function register_settings() {
        register_setting('aca_settings_group', $this->option_name, [$this, 'validate_coupons']);
    }

    public function validate_coupons($input) {
        // Sanitize coupons array
        if (!is_array($input)) return [];
        $clean = [];
        foreach ($input as $coupon) {
            $c = [];
            $c['title'] = sanitize_text_field($coupon['title'] ?? '');
            $c['code'] = sanitize_text_field($coupon['code'] ?? '');
            $c['link'] = esc_url_raw($coupon['link'] ?? '');
            $c['description'] = sanitize_text_field($coupon['description'] ?? '');
            if ($c['title'] && $c['link']) {
                $clean[] = $c;
            }
        }
        return $clean;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon & Deal Aggregator Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('aca_settings_group'); ?>
                <?php $coupons = get_option($this->option_name, []); ?>
                <table class="form-table" id="aca-coupon-table">
                    <thead><tr><th>Title</th><th>Code</th><th>Link</th><th>Description</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (!empty($coupons)) : ?>
                        <?php foreach ($coupons as $index => $coupon) : ?>
                            <tr>
                                <td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $index; ?>][title]" value="<?php echo esc_attr($coupon['title']); ?>" required></td>
                                <td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>"></td>
                                <td><input type="url" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $index; ?>][link]" value="<?php echo esc_attr($coupon['link']); ?>" required></td>
                                <td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $index; ?>][description]" value="<?php echo esc_attr($coupon['description']); ?>"></td>
                                <td><button class="button aca-remove-row" type="button">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button button-primary" id="aca-add-row">Add Coupon</button></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tableBody = document.querySelector('#aca-coupon-table tbody');
            document.getElementById('aca-add-row').addEventListener('click', function () {
                var index = tableBody.rows.length;
                var row = document.createElement('tr');
                row.innerHTML =
                    '<td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[' + index + '][title]" required></td>' +
                    '<td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[' + index + '][code]"></td>' +
                    '<td><input type="url" name="<?php echo esc_attr($this->option_name); ?>[' + index + '][link]" required></td>' +
                    '<td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[' + index + '][description]"></td>' +
                    '<td><button class="button aca-remove-row" type="button">Remove</button></td>';
                tableBody.appendChild(row);
            });

            tableBody.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('aca-remove-row')) {
                    e.target.closest('tr').remove();
                }
            });
        });
        </script>
        <?php
    }

    public function render_coupons_shortcode() {
        if (empty($this->coupons)) {
            return '<p>No coupons available at the moment. Please check back later.</p>';
        }
        $output = '<div class="aca-coupons">
<ul style="list-style:none;padding:0;">';
        foreach ($this->coupons as $coupon) {
            $title = esc_html($coupon['title']);
            $code = esc_html($coupon['code']);
            $desc = esc_html($coupon['description']);
            $link = esc_url($coupon['link']);

            $output .= '<li style="margin-bottom:15px;padding:10px;border:1px solid #ccc;border-radius:5px;">
<strong>' . $title . '</strong><br />';
            if ($desc) {
                $output .= '<em>' . $desc . '</em><br />';
            }
            if ($code) {
                $output .= 'Coupon Code: <code>' . $code . '</code><br />';
            }
            $output .= '<a href="' . $link . '" target="_blank" rel="nofollow noopener noreferrer" style="color:#0073aa;">Use this deal</a>
</li>';
        }
        $output .= '</ul></div>';
        return $output;
    }

}

AffiliateCouponAggregator::instance();
