<?php
/*
Plugin Name: Affiliate Coupon Booster
Plugin URI: https://example.com/affiliate-coupon-booster
Description: Auto-create and display tailored affiliate coupons to boost revenue.
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
License: GPL2
Text Domain: affiliate-coupon-booster
*/

if (!defined('ABSPATH')) exit;

class AffiliateCouponBooster {
    private static $instance = null;
    private $coupons = [];
    private $option_name = 'acb_coupons_data';

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('affiliate_coupons', [$this, 'render_coupons']);
        $this->coupons = get_option($this->option_name, []);
    }

    public function admin_menu() {
        add_menu_page('Affiliate Coupon Booster', 'Coupon Booster', 'manage_options', 'affiliate-coupon-booster', [$this, 'settings_page'], 'dashicons-tickets-alt', 56);
    }

    public function register_settings() {
        register_setting('acb_settings_group', $this->option_name, [$this, 'validate_coupons']);
    }

    public function validate_coupons($input) {
        // Basic validation/filtering of coupon array
        if (!is_array($input)) {
            return [];
        }
        $clean = [];
        foreach ($input as $coupon) {
            if (!empty($coupon['code']) && !empty($coupon['description']) && !empty($coupon['affiliate_url'])) {
                $clean[] = [
                    'code' => sanitize_text_field($coupon['code']),
                    'description' => sanitize_text_field($coupon['description']),
                    'affiliate_url' => esc_url_raw($coupon['affiliate_url']),
                    'expires' => isset($coupon['expires']) ? sanitize_text_field($coupon['expires']) : ''
                ];
            }
        }
        return $clean;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Booster</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acb_settings_group');
                $coupons = $this->coupons;
                ?>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Coupon Code</th>
                            <th>Description</th>
                            <th>Affiliate URL</th>
                            <th>Expires (YYYY-MM-DD)</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody id="acb-coupons-list">
                    <?php if (!empty($coupons)) : ?>
                        <?php foreach ($coupons as $index => $coupon) : ?>
                            <tr>
                                <td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" required></td>
                                <td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $index; ?>][description]" value="<?php echo esc_attr($coupon['description']); ?>" required></td>
                                <td><input type="url" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $index; ?>][affiliate_url]" value="<?php echo esc_attr($coupon['affiliate_url']); ?>" required></td>
                                <td><input type="date" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $index; ?>][expires]" value="<?php echo esc_attr($coupon['expires']); ?>"></td>
                                <td><button class="button acb-remove-row" type="button">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr class="acb-no-data"><td colspan="5">No coupons added yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <p><button id="acb-add-coupon" class="button button-primary" type="button">Add Coupon</button></p>
                <?php submit_button('Save Coupons'); ?>
            </form>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tbody = document.getElementById('acb-coupons-list');
                const addBtn = document.getElementById('acb-add-coupon');

                addBtn.addEventListener('click', function() {
                    const index = tbody.querySelectorAll('tr').length;
                    if(tbody.querySelector('.acb-no-data')) {
                        tbody.querySelector('.acb-no-data').remove();
                    }

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[${index}][code]" required></td>
                        <td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[${index}][description]" required></td>
                        <td><input type="url" name="<?php echo esc_attr($this->option_name); ?>[${index}][affiliate_url]" required></td>
                        <td><input type="date" name="<?php echo esc_attr($this->option_name); ?>[${index}][expires]"></td>
                        <td><button class="button acb-remove-row" type="button">Remove</button></td>
                    `;
                    tbody.appendChild(row);
                });

                tbody.addEventListener('click', function(e) {
                    if(e.target.classList.contains('acb-remove-row')) {
                        const tr = e.target.closest('tr');
                        tr.remove();
                        if(tbody.children.length === 0) {
                            const trEmpty = document.createElement('tr');
                            trEmpty.classList.add('acb-no-data');
                            trEmpty.innerHTML = '<td colspan="5">No coupons added yet.</td>';
                            tbody.appendChild(trEmpty);
                        }
                    }
                });
            });
        </script>
        <?php
    }

    public function render_coupons() {
        if (empty($this->coupons)) {
            return '<p>No coupons available at the moment.</p>';
        }

        $today = strtotime(current_time('Y-m-d'));
        $output = '<div class="acb-coupon-list">';

        foreach ($this->coupons as $coupon) {
            $expires = !empty($coupon['expires']) ? strtotime($coupon['expires']) : null;

            if ($expires && $expires < $today) {
                continue; // Skip expired coupons
            }

            $code = esc_html($coupon['code']);
            $desc = esc_html($coupon['description']);
            $url = esc_url($coupon['affiliate_url']);

            $output .= "<div class='acb-coupon' style='border:1px solid #ccc;padding:10px;margin:10px 0;'>";
            $output .= "<p><strong>Coupon Code:</strong> <code>$code</code></p>";
            $output .= "<p>$desc</p>";
            $output .= "<p><a href='$url' target='_blank' rel='nofollow noopener' style='background:#0073aa;color:#fff;padding:6px 12px;text-decoration:none;border-radius:3px;'>Activate Coupon</a></p>";
            $output .= "</div>";
        }

        $output .= '</div>';
        return $output;
    }
}

add_action('plugins_loaded', function() {
    AffiliateCouponBooster::instance();
});
