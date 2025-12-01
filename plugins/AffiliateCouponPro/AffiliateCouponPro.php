<?php
/*
Plugin Name: AffiliateCouponPro
Description: Auto-manage and display affiliate coupons to boost affiliate marketing earnings.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateCouponPro.php
*/

if(!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponPro {
    private $option_name = 'acp_coupons';

    public function __construct() {
        add_action('admin_menu', array($this,'admin_menu'));
        add_action('admin_init', array($this,'register_settings'));
        add_shortcode('affiliate_coupons', array($this,'shortcode_display'));
        add_action('wp_enqueue_scripts', array($this,'enqueue_frontend_styles'));
    }

    public function admin_menu() {
        add_menu_page('AffiliateCouponPro', 'AffiliateCouponPro', 'manage_options', 'affiliatecouponpro', array($this, 'admin_page'), 'dashicons-tickets-alt');
    }

    public function register_settings() {
        register_setting('acp_settings_group', $this->option_name, array($this, 'validate_coupons'));
    }

    public function validate_coupons($input) {
        // Basic validation and sanitization
        if(!is_array($input)) return array();
        foreach($input as $key => &$coupon) {
            $coupon['title'] = sanitize_text_field($coupon['title']);
            $coupon['code'] = sanitize_text_field($coupon['code']);
            $coupon['link'] = esc_url_raw($coupon['link']);
            $coupon['discount'] = sanitize_text_field($coupon['discount']);
            $coupon['expires'] = sanitize_text_field($coupon['expires']);
            $coupon['active'] = isset($coupon['active']) && $coupon['active'] ? 1 : 0;
        }
        return $input;
    }

    public function admin_page() {
        $coupons = get_option($this->option_name, array());
        ?>
        <div class="wrap">
            <h1>AffiliateCouponPro Coupon Manager</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acp_settings_group'); ?>
                <table class="widefat fixed">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Code</th>
                            <th>Link (Affiliate URL)</th>
                            <th>Discount Info</th>
                            <th>Expires (YYYY-MM-DD)</th>
                            <th>Active</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody id="acp-coupon-list">
                    <?php
                    if(empty($coupons)) {
                        echo '<tr><td colspan="7">No coupons added yet.</td></tr>';
                    } else {
                        foreach($coupons as $index => $coupon) {
                            echo '<tr>';
                            echo '<td><input type="text" name="' . $this->option_name . '['.$index.'][title]" value="'.esc_attr($coupon['title']).'" required/></td>';
                            echo '<td><input type="text" name="' . $this->option_name . '['.$index.'][code]" value="'.esc_attr($coupon['code']).'" required/></td>';
                            echo '<td><input type="url" size="40" name="' . $this->option_name . '['.$index.'][link]" value="'.esc_attr($coupon['link']).'" required/></td>';
                            echo '<td><input type="text" name="' . $this->option_name . '['.$index.'][discount]" value="'.esc_attr($coupon['discount']).'"/></td>';
                            echo '<td><input type="date" name="' . $this->option_name . '['.$index.'][expires]" value="'.esc_attr($coupon['expires']).'"/></td>';
                            $checked = $coupon['active'] ? 'checked' : '';
                            echo '<td><input type="checkbox" name="' . $this->option_name . '['.$index.'][active]" value="1" '.$checked.'/></td>';
                            echo '<td><button type="button" class="button acp-remove-coupon">Remove</button></td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <p><button type="button" class="button button-primary" id="acp-add-coupon">Add New Coupon</button></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        (function(){
            var tableBody=document.getElementById('acp-coupon-list');
            var addBtn=document.getElementById('acp-add-coupon');
            addBtn.onclick=function(){
                var count = tableBody.children.length;
                var row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type='text' name='<?php echo $this->option_name; ?>[${count}][title]' required></td>
                    <td><input type='text' name='<?php echo $this->option_name; ?>[${count}][code]' required></td>
                    <td><input type='url' size='40' name='<?php echo $this->option_name; ?>[${count}][link]' required></td>
                    <td><input type='text' name='<?php echo $this->option_name; ?>[${count}][discount]'></td>
                    <td><input type='date' name='<?php echo $this->option_name; ?>[${count}][expires]'></td>
                    <td><input type='checkbox' name='<?php echo $this->option_name; ?>[${count}][active]' value='1' checked></td>
                    <td><button type='button' class='button acp-remove-coupon'>Remove</button></td>
                `;
                tableBody.appendChild(row);
                attachRemoveHandlers();
            };
            function attachRemoveHandlers() {
                var removeBtns=document.querySelectorAll('.acp-remove-coupon');
                removeBtns.forEach(function(btn){
                    btn.onclick=function(){
                        this.closest('tr').remove();
                    };
                });
            }
            attachRemoveHandlers();
        })();
        </script>
        <?php
    }

    public function enqueue_frontend_styles() {
        wp_register_style('acp_front_css', false);
        wp_enqueue_style('acp_front_css');
        $custom_css = ".acp-coupon-list{list-style:none;padding:0;max-width:400px;}
.acp-coupon-list li{background:#f9f9f9;margin:5px 0;padding:10px;border-radius:4px;}
.acp-coupon-code{font-weight:bold;}
.acp-coupon-title{font-size:1.1em;}
.acp-coupon-btn{background:#0073aa;color:#fff;border:none;padding:5px 10px;margin-top:5px;cursor:pointer;border-radius:3px;text-decoration:none;display:inline-block;}
.acp-coupon-btn:hover{background:#005177;}
";
        wp_add_inline_style('acp_front_css', $custom_css);
    }

    public function shortcode_display() {
        $coupons = get_option($this->option_name, array());
        if(empty($coupons)) return '<p>No coupons available.</p>';

        $output = '<ul class="acp-coupon-list">';
        $today = date('Y-m-d');
        foreach($coupons as $coupon) {
            if(!$coupon['active']) continue;
            if(!empty($coupon['expires']) && $coupon['expires'] < $today) continue;
            $title = esc_html($coupon['title']);
            $code = esc_html($coupon['code']);
            $discount = esc_html($coupon['discount']);
            $link = esc_url($coupon['link']);
            $output .= '<li>';
            $output .= '<div class="acp-coupon-title">' . $title . '</div>';
            if($discount) {
                $output .= '<div>Discount: ' . $discount . '</div>';
            }
            $output .= '<div class="acp-coupon-code">Code: <strong>' . $code . '</strong></div>';
            $output .= '<a class="acp-coupon-btn" href="' . $link . '" target="_blank" rel="nofollow noopener">Use Coupon</a>';
            $output .= '</li>';
        }
        $output .= '</ul>';
        return $output;
    }
}

new AffiliateCouponPro();
