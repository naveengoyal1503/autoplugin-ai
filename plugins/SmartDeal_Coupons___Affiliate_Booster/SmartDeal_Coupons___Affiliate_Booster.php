/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartDeal_Coupons___Affiliate_Booster.php
*/
<?php
/**
 * Plugin Name: SmartDeal Coupons & Affiliate Booster
 * Description: Automatically display and manage affiliate coupons and deals with tracking to increase conversions.
 * Version: 1.0
 * Author: AI Plugin Generator
 */

if (!defined('ABSPATH')) exit;

class SmartDealAffiliateBooster {
    private $option_name = 'smartdeal_affiliate_coupons';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('smartdeal_coupons', array($this, 'display_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_smartdeal_track_click', array($this, 'track_click_ajax'));
        add_action('wp_ajax_nopriv_smartdeal_track_click', array($this, 'track_click_ajax'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smartdeal-script', plugin_dir_url(__FILE__) . 'smartdeal.js', array('jquery'), '1.0', true);
        wp_localize_script('smartdeal-script', 'smartdeal_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
        wp_enqueue_style('smartdeal-style', plugin_dir_url(__FILE__) . 'smartdeal.css');
    }

    public function add_admin_menu() {
        add_menu_page('SmartDeal Coupons', 'SmartDeal Coupons', 'manage_options', 'smartdeal-coupons', array($this, 'settings_page'), 'dashicons-tickets-alt');
    }

    public function register_settings() {
        register_setting('smartdeal_options_group', $this->option_name, array($this, 'validate_settings'));
    }

    public function validate_settings($input) {
        if (!is_array($input)) return array();
        $valid = array();
        foreach($input as $coupon) {
            if (empty($coupon['title']) || empty($coupon['link'])) continue;
            $valid[] = array(
                'title' => sanitize_text_field($coupon['title']),
                'description' => sanitize_textarea_field($coupon['description']),
                'link' => esc_url_raw($coupon['link']),
                'code' => sanitize_text_field($coupon['code']),
                'expiry' => sanitize_text_field($coupon['expiry'])
            );
        }
        return $valid;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>SmartDeal Coupons & Affiliate Booster</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smartdeal_options_group');
                $coupons = get_option($this->option_name, array());
                ?>
                <table class="widefat" id="smartdeal-coupons-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Affiliate Link</th>
                            <th>Coupon Code</th>
                            <th>Expiry Date</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if(empty($coupons)) {
                        $coupons = array(array('title'=>'','description'=>'','link'=>'','code'=>'','expiry'=>''));
                    }
                    foreach ($coupons as $index => $c) {
                        ?>
                        <tr>
                            <td><input type="text" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][title]" value="<?php echo esc_attr($c['title']); ?>" required/></td>
                            <td><textarea name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][description]" rows="2"><?php echo esc_textarea($c['description']); ?></textarea></td>
                            <td><input type="url" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][link]" value="<?php echo esc_url($c['link']); ?>" required/></td>
                            <td><input type="text" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][code]" value="<?php echo esc_attr($c['code']); ?>"/></td>
                            <td><input type="date" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][expiry]" value="<?php echo esc_attr($c['expiry']); ?>"/></td>
                            <td><button type="button" class="button button-danger smartdeal-delete-row">Delete</button></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                <button type="button" class="button button-primary" id="smartdeal-add-row">Add New Coupon</button>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#smartdeal-add-row').on('click', function(){
                var index = $('#smartdeal-coupons-table tbody tr').length;
                var newRow = '<tr>' +
                    '<td><input type="text" name="<?php echo $this->option_name; ?>['+index+'][title]" required></td>' +
                    '<td><textarea name="<?php echo $this->option_name; ?>['+index+'][description]" rows="2"></textarea></td>' +
                    '<td><input type="url" name="<?php echo $this->option_name; ?>['+index+'][link]" required></td>' +
                    '<td><input type="text" name="<?php echo $this->option_name; ?>['+index+'][code]"></td>' +
                    '<td><input type="date" name="<?php echo $this->option_name; ?>['+index+'][expiry]"></td>' +
                    '<td><button type="button" class="button button-danger smartdeal-delete-row">Delete</button></td>' +
                    '</tr>';
                $('#smartdeal-coupons-table tbody').append(newRow);
            });
            $(document).on('click', '.smartdeal-delete-row', function(){
                $(this).closest('tr').remove();
            });
        });
        </script>
        <style>
        .button-danger { color: #a00; border-color: #a00; }
        </style>
        <?php
    }

    public function display_coupons_shortcode() {
        $coupons = get_option($this->option_name, array());
        if(empty($coupons)) return '<p>No coupons found.</p>';
        $output = '<div class="smartdeal-coupons-container">';
        $now = date('Y-m-d');
        foreach($coupons as $c) {
            if(!empty($c['expiry']) && $c['expiry'] < $now) continue;// skip expired
            $title = esc_html($c['title']);
            $desc = esc_html($c['description']);
            $code = !empty($c['code']) ? esc_html($c['code']) : '';
            $link = esc_url($c['link']);
            $output .= '<div class="smartdeal-coupon">';
            $output .= '<h3>'. $title .'</h3>';
            if($desc) $output .= '<p>'. $desc .'</p>';
            if($code) $output .= '<p><strong>Coupon Code: </strong> <span class="smartdeal-code">'. $code .'</span></p>';
            $encoded_link = esc_attr(add_query_arg(array('smartdeal_click'=>rawurlencode($link)), home_url()));
            $output .= '<a href="#" class="smartdeal-claim-btn" data-link="'. $link .'" >Claim Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        $output .= '<style>
.smartdeal-coupons-container{display:flex;flex-wrap:wrap;gap:20px;}
.smartdeal-coupon{border:1px solid #ccc;padding:15px;width:280px;border-radius:6px;background:#fafafa;}
.smartdeal-coupon h3{margin-top:0;color:#0073aa;}
.smartdeal-claim-btn{display:inline-block;padding:10px 15px;background:#0073aa;color:#fff;text-decoration:none;border-radius:4px;cursor:pointer;}
.smartdeal-claim-btn:hover{background:#005177;}
</style>';
        return $output;
    }

    public function track_click_ajax() {
        if(empty($_POST['link'])) wp_send_json_error('Missing link');
        $link = esc_url_raw($_POST['link']);
        // Here you can add code to log clicks in DB or send event to analytics
        wp_send_json_success(array('redirect' => $link));
    }
}

new SmartDealAffiliateBooster();

// JS file smartdeal.js embedded here since single file plugin
add_action('wp_footer', function() {
    ?>
<script>
jQuery(document).ready(function($){
    $('.smartdeal-claim-btn').on('click', function(e){
        e.preventDefault();
        var link = $(this).data('link');
        $.post(smartdeal_ajax.ajax_url, {action:'smartdeal_track_click', link: link}, function(response){
            if(response.success && response.data.redirect){
                window.open(response.data.redirect, '_blank');
            } else {
                alert('Failed to track click. Redirecting anyway.');
                window.open(link, '_blank');
            }
        });
    });
});
</script>
    <?php
});

// CSS file smartdeal.css embedded here since single file plugin
add_action('wp_head', function(){
    ?>
<style>
/* Additional styling for coupon codes */
.smartdeal-code { background: #e2e2e2; padding: 3px 6px; border-radius: 3px; font-family: monospace; }
</style>
    <?php
});
