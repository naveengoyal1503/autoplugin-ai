/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Accelerator.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Accelerator
 * Description: Automated affiliate coupon creation and intelligent display to increase conversions and affiliate commissions.
 * Version: 1.0
 * Author: Generated Plugin
 */

// Prevent direct access
defined('ABSPATH') or die('No script kiddies please!');

class AffiliateCouponAccelerator {
    private $coupons_option_name = 'aca_coupons_data';
    private $max_coupons_display = 5;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliate_coupons', array($this, 'render_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }

    public function activate_plugin() {
        if (false === get_option($this->coupons_option_name)) {
            $default_coupons = array();
            update_option($this->coupons_option_name, $default_coupons);
        }
    }

    public function deactivate_plugin() {
        // Optionally cleanup or leave data
    }

    public function add_admin_page() {
        add_menu_page(
            'Affiliate Coupons',
            'Affiliate Coupons',
            'manage_options',
            'affiliate-coupon-accelerator',
            array($this, 'admin_page_html'),
            'dashicons-tickets',
            58
        );
    }

    public function register_settings() {
        register_setting('aca_coupons_group', $this->coupons_option_name, array($this, 'validate_coupons'));
    }

    public function validate_coupons($input) {
        if (!is_array($input)) {
            return array();
        }

        // Validate each coupon data
        $validated = array();
        foreach ($input as $key => $coupon) {
            if (empty($coupon['title']) || empty($coupon['code']) || empty($coupon['url'])) {
                continue;
            }
            $validated[] = array(
                'title' => sanitize_text_field($coupon['title']),
                'code' => sanitize_text_field($coupon['code']),
                'url' => esc_url_raw($coupon['url']),
                'expiry' => sanitize_text_field($coupon['expiry'])
            );
        }
        return $validated;
    }

    public function admin_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $coupons = get_option($this->coupons_option_name, array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Accelerator</h1>
            <form method="post" action="options.php">
                <?php settings_fields('aca_coupons_group'); ?>
                <table class="form-table" id="coupons-table">
                    <thead>
                        <tr>
                            <th>Coupon Title</th>
                            <th>Coupon Code</th>
                            <th>Affiliate URL</th>
                            <th>Expiry Date (YYYY-MM-DD)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if(empty($coupons)) {
                            $coupons = array(array('title'=>'', 'code'=>'', 'url'=>'', 'expiry'=>''));
                        }
                        foreach ($coupons as $index => $coupon) : ?>
                        <tr>
                            <td><input type="text" name="<?php echo $this->coupons_option_name; ?>[<?php echo $index; ?>][title]" value="<?php echo esc_attr($coupon['title']); ?>" required></td>
                            <td><input type="text" name="<?php echo $this->coupons_option_name; ?>[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" required></td>
                            <td><input type="url" name="<?php echo $this->coupons_option_name; ?>[<?php echo $index; ?>][url]" value="<?php echo esc_attr($coupon['url']); ?>" required></td>
                            <td><input type="date" name="<?php echo $this->coupons_option_name; ?>[<?php echo $index; ?>][expiry]" value="<?php echo esc_attr($coupon['expiry']); ?>" ></td>
                            <td><button type="button" class="button aca-remove-row">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button button-primary" id="aca-add-coupon">Add Coupon</button></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addCouponBtn = document.getElementById('aca-add-coupon');
            const couponsTable = document.getElementById('coupons-table').getElementsByTagName('tbody');

            addCouponBtn.addEventListener('click', function() {
                const rowCount = couponsTable.rows.length;
                const row = couponsTable.insertRow();

                row.innerHTML = `
                    <td><input type="text" name="<?php echo $this->coupons_option_name; ?>[${rowCount}][title]" required></td>
                    <td><input type="text" name="<?php echo $this->coupons_option_name; ?>[${rowCount}][code]" required></td>
                    <td><input type="url" name="<?php echo $this->coupons_option_name; ?>[${rowCount}][url]" required></td>
                    <td><input type="date" name="<?php echo $this->coupons_option_name; ?>[${rowCount}][expiry]"></td>
                    <td><button type="button" class="button aca-remove-row">Remove</button></td>
                `;

                updateRemoveButtons();
            });

            function updateRemoveButtons() {
                const removeButtons = document.querySelectorAll('.aca-remove-row');
                removeButtons.forEach(btn => {
                    btn.removeEventListener('click', removeRowHandler);
                    btn.addEventListener('click', removeRowHandler);
                });
            }

            function removeRowHandler(e) {
                const row = e.target.closest('tr');
                row.parentNode.removeChild(row);
                reindexRows();
            }

            function reindexRows() {
                Array.from(couponsTable.rows).forEach(function(row, index){
                    const inputs = row.querySelectorAll('input');
                    inputs.forEach(function(input) {
                        const name = input.name;
                        const newName = name.replace(/\[\d+\]/, '['+index+']');
                        input.name = newName;
                    });
                });
            }

            updateRemoveButtons();
        });
        </script>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('aca-styles', plugin_dir_url(__FILE__) . 'css/aca-styles.css', array(), '1.0');
    }

    public function render_coupons_shortcode() {
        $coupons = get_option($this->coupons_option_name, array());
        if (empty($coupons)) {
            return '<p>No coupons available at this time.</p>';
        }

        $today = date('Y-m-d');

        // Filter out expired coupons
        $valid_coupons = array();
        foreach ($coupons as $coupon) {
            if (empty($coupon['expiry']) || $coupon['expiry'] >= $today) {
                $valid_coupons[] = $coupon;
            }
        }

        if (empty($valid_coupons)) {
            return '<p>No valid coupons available right now. Please check back soon!</p>';
        }

        // Limit the number of displayed coupons
        $valid_coupons = array_slice($valid_coupons, 0, $this->max_coupons_display);

        // Display coupons in a styled list
        ob_start();
        ?>
        <div class="aca-coupon-list" style="max-width:400px; border:1px solid #ccc; padding:15px; background:#f9f9f9;">
            <h3>Hot Deals & Coupons</h3>
            <ul style="list-style-type:none; padding-left:0;">
                <?php foreach ($valid_coupons as $coupon) : ?>
                    <li style="margin-bottom:15px; border-bottom:1px dotted #bbb; padding-bottom:10px;">
                        <strong><?php echo esc_html($coupon['title']); ?></strong><br>
                        <span style="background:#e2e2e2; font-weight:bold; padding:3px 8px; letter-spacing:1.5px;"><?php echo esc_html($coupon['code']); ?></span><br>
                        <a href="<?php echo esc_url($coupon['url']); ?>" target="_blank" rel="nofollow noopener" style="color:#0066cc;">Get this deal &raquo;</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
}

new AffiliateCouponAccelerator();
