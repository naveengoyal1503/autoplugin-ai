<?php
/*
Plugin Name: AffiliateCoupon Booster
Description: Automates affiliate coupon deal management with personalized notifications.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateCoupon_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateCouponBooster {
    private $option_name = 'affcouponbooster_coupons';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_post_save_coupon', array($this, 'save_coupon'));
        add_shortcode('affcouponbooster', array($this, 'display_coupons'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'show_popup'));
        add_action('wp_ajax_affcoupon_cloak', array($this, 'handle_cloak')); 
        add_action('wp_ajax_nopriv_affcoupon_cloak', array($this, 'handle_cloak'));
    }

    public function add_admin_page() {
        add_menu_page('AffiliateCoupon Booster', 'AffiliateCoupon Booster', 'manage_options', 'affcouponbooster', array($this, 'admin_page'), 'dashicons-tickets-alt');
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) { wp_die('Permission denied.'); }

        $coupons = get_option($this->option_name, array());
        $edit_coupon = null;
        if (isset($_GET['edit'])) {
            $id = sanitize_text_field($_GET['edit']);
            foreach ($coupons as $coupon) {
                if ($coupon['id'] === $id) {
                    $edit_coupon = $coupon;
                    break;
                }
            }
        }
        ?>
        <div class='wrap'>
            <h1>AffiliateCoupon Booster Coupons</h1>
            <form method='post' action='<?php echo admin_url('admin-post.php'); ?>'>
                <input type='hidden' name='action' value='save_coupon'>
                <input type='hidden' name='id' value='<?php echo esc_attr($edit_coupon['id'] ?? ''); ?>'>
                <?php wp_nonce_field('affcouponbooster_save_coupon'); ?>
                <table class='form-table'>
                    <tr><th><label for='title'>Title</label></th><td><input type='text' id='title' name='title' required class='regular-text' value='<?php echo esc_attr($edit_coupon['title'] ?? ''); ?>'></td></tr>
                    <tr><th><label for='description'>Description</label></th><td><textarea id='description' name='description' class='large-text' rows='3'><?php echo esc_textarea($edit_coupon['description'] ?? ''); ?></textarea></td></tr>
                    <tr><th><label for='affiliate_url'>Affiliate URL</label></th><td><input type='url' id='affiliate_url' name='affiliate_url' required class='regular-text' value='<?php echo esc_url($edit_coupon['affiliate_url'] ?? ''); ?>'></td></tr>
                    <tr><th><label for='code'>Coupon Code</label></th><td><input type='text' id='code' name='code' required class='regular-text' value='<?php echo esc_attr($edit_coupon['code'] ?? ''); ?>'></td></tr>
                    <tr><th><label for='start_date'>Start Date</label></th><td><input type='date' id='start_date' name='start_date' value='<?php echo esc_attr($edit_coupon['start_date'] ?? ''); ?>'></td></tr>
                    <tr><th><label for='end_date'>End Date</label></th><td><input type='date' id='end_date' name='end_date' value='<?php echo esc_attr($edit_coupon['end_date'] ?? ''); ?>'></td></tr>
                    <tr><th><label for='popup_message'>Popup Message</label></th><td><textarea id='popup_message' name='popup_message' class='large-text' rows='2'><?php echo esc_textarea($edit_coupon['popup_message'] ?? ''); ?></textarea><br><small>Message shown in discount popup.</small></td></tr>
                </table>
                <?php submit_button($edit_coupon ? 'Update Coupon' : 'Add Coupon'); ?>
            </form>

            <h2>Existing Coupons</h2>
            <table class='wp-list-table widefat fixed striped'>
                <thead><tr><th>Title</th><th>Code</th><th>Validity</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($coupons as $coupon) :
                    $id = $coupon['id'];
                    $validity = ($coupon['start_date'] ?? '') . ' to ' . ($coupon['end_date'] ?? '');
                ?>
                <tr>
                    <td><?php echo esc_html($coupon['title']); ?></td>
                    <td><?php echo esc_html($coupon['code']); ?></td>
                    <td><?php echo esc_html($validity); ?></td>
                    <td><a href='<?php echo admin_url('admin.php?page=affcouponbooster&edit=' . urlencode($id)); ?>'>Edit</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function save_coupon() {
        if (!current_user_can('manage_options') || !check_admin_referer('affcouponbooster_save_coupon')) {
            wp_die('Permission denied or nonce check failed.');
        }

        $coupons = get_option($this->option_name, array());
        $id = sanitize_text_field($_POST['id'] ?? '');
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $affiliate_url = esc_url_raw($_POST['affiliate_url']);
        $code = sanitize_text_field($_POST['code']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $popup_message = sanitize_textarea_field($_POST['popup_message']);

        if ($id) {
            foreach ($coupons as &$coupon) {
                if ($coupon['id'] === $id) {
                    $coupon['title'] = $title;
                    $coupon['description'] = $description;
                    $coupon['affiliate_url'] = $affiliate_url;
                    $coupon['code'] = $code;
                    $coupon['start_date'] = $start_date;
                    $coupon['end_date'] = $end_date;
                    $coupon['popup_message'] = $popup_message;
                    break;
                }
            }
        } else {
            $id = uniqid('c');
            $coupons[] = array(
                'id' => $id,
                'title' => $title,
                'description' => $description,
                'affiliate_url' => $affiliate_url,
                'code' => $code,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'popup_message' => $popup_message
            );
        }

        update_option($this->option_name, $coupons);
        wp_redirect(admin_url('admin.php?page=affcouponbooster&saved=1'));
        exit;
    }

    public function display_coupons() {
        $coupons = get_option($this->option_name, array());
        $now = current_time('Y-m-d');
        $output = '<div class="affcouponbooster-list">';

        foreach ($coupons as $coupon) {
            if (($coupon['start_date'] && $coupon['start_date'] > $now) || ($coupon['end_date'] && $coupon['end_date'] < $now)) {
                continue; // skip expired or inactive
            }
            $url = esc_url(add_query_arg('affcoupon_redirect', $coupon['id'], home_url()));
            $output .= '<div class="affcouponbooster-item" style="margin-bottom:15px; padding:10px; border:1px solid #ddd;">';
            $output .= '<h4>' . esc_html($coupon['title']) . '</h4>';
            $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            $output .= '<p><strong>Coupon Code:</strong> <code>' . esc_html($coupon['code']) . '</code></p>';
            $output .= '<p><a href="' . esc_url($url) . '" target="_blank" class="affcouponbooster-link">Get Deal</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affcouponbooster-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('affcouponbooster-js', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
    }

    public function show_popup() {
        $coupons = get_option($this->option_name, array());
        $now = current_time('Y-m-d');

        foreach ($coupons as $coupon) {
            if (($coupon['start_date'] && $coupon['start_date'] > $now) || ($coupon['end_date'] && $coupon['end_date'] < $now)) {
                continue; // skip expired
            }
            if (!empty($coupon['popup_message'])) {
                echo "<div id='affcouponbooster-popup' style='display:none; position:fixed; bottom:20px; right:20px; background:#fff; border:1px solid #ccc; padding:15px; box-shadow:0 0 15px rgba(0,0,0,0.3); z-index:9999;'>";
                echo "<p>" . esc_html($coupon['popup_message']) . "</p>";
                $link = esc_url(add_query_arg('affcoupon_redirect', $coupon['id'], home_url()));
                echo "<p><a href='$link' target='_blank' style='color:#fff; background:#0073aa; padding:8px 12px; text-decoration:none;'>Get Coupon " . esc_html($coupon['code']) . "</a></p>";
                echo "<button id='affcouponbooster-close' style='position:absolute; top:5px; right:5px; border:none; background:none; font-size:16px; cursor:pointer;'>&times;</button>";
                echo "</div>";
                break; // show popup for first valid coupon
            }
        }
    }

    public function handle_cloak() {
        $id = sanitize_text_field($_GET['coupon_id'] ?? '');
        $coupons = get_option($this->option_name, array());
        foreach ($coupons as $coupon) {
            if ($coupon['id'] === $id) {
                // Redirect to affiliate URL with 302 status for cloaking
                wp_redirect($coupon['affiliate_url']);
                exit;
            }
        }
        wp_die('Invalid coupon ID.');
    }
}

new AffiliateCouponBooster();

// JS and CSS inline to keep single file plugin
add_action('wp_head', function() {
    echo "<style>#affcouponbooster-popup {font-family:arial,sans-serif;} #affcouponbooster-popup p {margin: 0 0 10px;} #affcouponbooster-popup a:hover {background:#005177;}</style>";
});

add_action('wp_footer', function() {
    ?>
    <script>
        (function($){
            $(document).ready(function(){
                var popup = $('#affcouponbooster-popup');
                if (popup.length) {
                    setTimeout(function(){popup.fadeIn();}, 3000);
                    $('#affcouponbooster-close').on('click', function(e){
                        e.preventDefault();
                        popup.fadeOut();
                    });
                }
            });
        })(jQuery);
    </script>
    <?php
});

// Handle redirect for affiliate links
add_action('template_redirect', function() {
    if (isset($_GET['affcoupon_redirect'])) {
        $id = sanitize_text_field($_GET['affcoupon_redirect']);
        $coupons = get_option('affcouponbooster_coupons', array());
        foreach ($coupons as $coupon) {
            if ($coupon['id'] === $id) {
                wp_redirect($coupon['affiliate_url']);
                exit;
            }
        }
    }
});
