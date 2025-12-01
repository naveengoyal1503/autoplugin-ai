/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Manager
 * Description: Manage affiliate-linked coupons with tracking and automated expiration.
 * Version: 1.0
 * Author: Generated
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SmartAffiliateCouponManager {
    private $option_name = 'sacm_coupons';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('sacm_coupons', array($this, 'display_coupons_shortcode'));
        add_action('init', array($this, 'handle_coupon_click'));
    }

    public function add_admin_menu() {
        add_menu_page('Coupon Manager', 'Affiliate Coupons', 'manage_options', 'sacm_coupon_manager', array($this, 'admin_page'), 'dashicons-tickets', 80);
    }

    public function register_settings() {
        register_setting('sacm_settings_group', $this->option_name, array($this, 'validate_coupons'));
    }

    public function validate_coupons($input) {
        // sanitize coupons array
        if (!is_array($input)) {
            return array();
        }
        $clean = array();
        foreach ($input as $coupon) {
            $code = sanitize_text_field($coupon['code']);
            $desc = sanitize_text_field($coupon['description']);
            $url = esc_url_raw($coupon['affiliate_url']);
            $exp = sanitize_text_field($coupon['expiration']);
            $clean[] = array(
                'code' => $code,
                'description' => $desc,
                'affiliate_url' => $url,
                'expiration' => $exp
            );
        }
        return $clean;
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }
        $coupons = get_option($this->option_name, array());
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Manager</h1>
            <form method="post" action="options.php">
                <?php settings_fields('sacm_settings_group'); ?>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Coupon Code</th>
                            <th>Description</th>
                            <th>Affiliate URL</th>
                            <th>Expiration Date (YYYY-MM-DD)</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody id="coupon-table-body">
                        <?php
                        if (empty($coupons)) {
                            $coupons = array();
                        }
                        foreach ($coupons as $index => $coupon) {
                            echo '<tr>' .
                                '<td><input type="text" name="' . $this->option_name . '[' . $index . '][code]" value="' . esc_attr($coupon['code']) . '" required></td>' .
                                '<td><input type="text" name="' . $this->option_name . '[' . $index . '][description]" value="' . esc_attr($coupon['description']) . '" required></td>' .
                                '<td><input type="url" name="' . $this->option_name . '[' . $index . '][affiliate_url]" value="' . esc_attr($coupon['affiliate_url']) . '" required></td>' .
                                '<td><input type="date" name="' . $this->option_name . '[' . $index . '][expiration]" value="' . esc_attr($coupon['expiration']) . '"></td>' .
                                '<td><button type="button" class="button sacm-remove-row">Remove</button></td>' .
                                '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <p><button type="button" class="button button-primary" id="sacm-add-row">Add Coupon</button></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        (function(){
            const body = document.getElementById('coupon-table-body');
            const addBtn = document.getElementById('sacm-add-row');
            addBtn.addEventListener('click', function(){
                const rows = body.querySelectorAll('tr');
                const index = rows.length;
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="text" name="<?php echo $this->option_name ?>[${index}][code]" required></td>
                    <td><input type="text" name="<?php echo $this->option_name ?>[${index}][description]" required></td>
                    <td><input type="url" name="<?php echo $this->option_name ?>[${index}][affiliate_url]" required></td>
                    <td><input type="date" name="<?php echo $this->option_name ?>[${index}][expiration]"></td>
                    <td><button type="button" class="button sacm-remove-row">Remove</button></td>
                `;
                body.appendChild(row);
            });
            body.addEventListener('click', function(e){
                if(e.target.classList.contains('sacm-remove-row')) {
                    e.target.closest('tr').remove();
                }
            });
        })();
        </script>
        <?php
    }

    public function display_coupons_shortcode() {
        $coupons = get_option($this->option_name, array());
        if (empty($coupons)) {
            return '<p>No coupons available at the moment.</p>';
        }
        $html = '<div class="sacm-coupon-list">';
        $today = date('Y-m-d');
        foreach ($coupons as $coupon) {
            // check expiration
            if (!empty($coupon['expiration']) && $coupon['expiration'] < $today) {
                continue; // skip expired
            }
            $code = esc_html($coupon['code']);
            $desc = esc_html($coupon['description']);
            $url = esc_url(add_query_arg('sacm_redirect', urlencode($coupon['affiliate_url']), home_url('/')));
            $html .= '<div class="sacm-coupon-item" style="margin-bottom:1em;padding:0.5em;border:1px solid #ccc;">';
            $html .= '<strong>Coupon:</strong> ' . $code . '<br>';
            $html .= '<span>' . $desc . '</span><br>';
            $html .= '<a href="' . $url . '" target="_blank" rel="nofollow noopener">Get Deal</a>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function handle_coupon_click() {
        if (isset($_GET['sacm_redirect'])) {
            $target_url = esc_url_raw(urldecode($_GET['sacm_redirect']));

            // Optional: Track clicks using transient or update user meta, not included here for brevity

            wp_redirect($target_url);
            exit;
        }
    }
}

new SmartAffiliateCouponManager();