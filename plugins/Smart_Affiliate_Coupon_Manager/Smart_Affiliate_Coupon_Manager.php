<?php
/*
Plugin Name: Smart Affiliate Coupon Manager
Description: Manage and display affiliate coupons directly in posts or widgets to increase affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Manager.php
License: GPL2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class SmartAffiliateCouponManager {
    private $coupons_option_name = 'sacm_coupons';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'sacm_coupons', array($this, 'admin_page'), 'dashicons-tickets-alt');
    }

    public function register_settings() {
        register_setting('sacm_settings_group', $this->coupons_option_name, array($this, 'validate_coupons'));
    }

    public function validate_coupons($input) {
        // Validate each coupon entry
        $valid = array();
        if (is_array($input)) {
            foreach ($input as $key => $coupon) {
                $code = sanitize_text_field($coupon['code'] ?? '');
                $desc = sanitize_text_field($coupon['description'] ?? '');
                $url = esc_url_raw($coupon['affiliate_url'] ?? '');
                if ($code && $url) {
                    $valid[$key] = array('code' => $code, 'description' => $desc, 'affiliate_url' => $url);
                }
            }
        }
        return $valid;
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupons</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sacm_settings_group');
                $coupons = get_option($this->coupons_option_name, array());
                ?>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Coupon Code</th>
                            <th>Description</th>
                            <th>Affiliate URL</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody id="coupons-table-body">
                    <?php foreach($coupons as $index => $coupon) : ?>
                        <tr>
                            <td><input type="text" name="<?php echo esc_attr($this->coupons_option_name); ?>[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" required /></td>
                            <td><input type="text" name="<?php echo esc_attr($this->coupons_option_name); ?>[<?php echo $index; ?>][description]" value="<?php echo esc_attr($coupon['description']); ?>" /></td>
                            <td><input type="url" name="<?php echo esc_attr($this->coupons_option_name); ?>[<?php echo $index; ?>][affiliate_url]" value="<?php echo esc_attr($coupon['affiliate_url']); ?>" required /></td>
                            <td><button class="button sacm-remove-row" type="button">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p>
                    <button id="add-coupon" class="button button-primary">Add Coupon</button>
                </p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        (function(){
            let addBtn = document.getElementById('add-coupon');
            let tbody = document.getElementById('coupons-table-body');
            addBtn.addEventListener('click', function(e) {
                e.preventDefault();
                let rowCount = tbody.children.length;
                let row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="text" name="<?php echo esc_js($this->coupons_option_name); ?>[${rowCount}][code]" required /></td>
                    <td><input type="text" name="<?php echo esc_js($this->coupons_option_name); ?>[${rowCount}][description]" /></td>
                    <td><input type="url" name="<?php echo esc_js($this->coupons_option_name); ?>[${rowCount}][affiliate_url]" required /></td>
                    <td><button class="button sacm-remove-row" type="button">Remove</button></td>
                `;
                tbody.appendChild(row);
            });

            tbody.addEventListener('click', function(e) {
                if(e.target && e.target.classList.contains('sacm-remove-row')) {
                    e.preventDefault();
                    e.target.closest('tr').remove();
                    // Re-index names to keep order
                    Array.from(tbody.children).forEach(function(tr, idx) {
                        tr.querySelectorAll('input').forEach(function(input) {
                            let name = input.getAttribute('name');
                            if(name) {
                                name = name.replace(/\[\d+\]/, '['+idx+']');
                                input.setAttribute('name', name);
                            }
                        });
                    });
                }
            });
        })();
        </script>
        <style>
            table.widefat input[type="text"], table.widefat input[type="url"] {
                width: 100%;
            }
        </style>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        $coupons = get_option($this->coupons_option_name, array());
        if (!$atts['code']) return '';

        foreach ($coupons as $coupon) {
            if (strcasecmp($coupon['code'], $atts['code']) === 0) {
                $desc = esc_html($coupon['description']);
                $url = esc_url($coupon['affiliate_url']);
                $code = esc_html($coupon['code']);

                return "<div class='sacm-coupon' style='border:1px solid #4CAF50;padding:10px;margin:10px 0;background:#e8f5e9;'>".
                       "<strong>Coupon:</strong> $code<br>".
                       ($desc ? "<em>$desc</em><br>" : '') .
                       "<a href='$url' target='_blank' rel='nofollow noopener' style='background:#4CAF50;color:#fff;padding:8px 12px;text-decoration:none;border-radius:4px;'>Use Coupon</a>".
                       "</div>";
            }
        }
        return '';
    }

    public function enqueue_styles() {
        wp_register_style('sacm_styles', false);
        wp_enqueue_style('sacm_styles');
        wp_add_inline_style('sacm_styles', ".sacm-coupon a:hover{background:#388E3C;} .sacm-coupon {font-family:Arial,sans-serif;}");
    }
}

new SmartAffiliateCouponManager();
