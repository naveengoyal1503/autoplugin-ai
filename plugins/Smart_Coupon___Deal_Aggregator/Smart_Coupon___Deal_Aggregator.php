/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Coupon___Deal_Aggregator.php
*/
<?php
/**
 * Plugin Name: Smart Coupon & Deal Aggregator
 * Description: Aggregates coupon codes and deals; monetizes via affiliate links and premium deals.
 * Version: 1.0
 * Author: Perplexity AI
 */

if (!defined('ABSPATH')) { exit; }

class SmartCouponDealAggregator {
    private $coupons_key = 'scda_coupons';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_scda_add_coupon', array($this, 'handle_add_coupon'));
        add_shortcode('scda_display_coupons', array($this, 'render_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('scda-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function admin_menu() {
        add_menu_page('Coupon Deals', 'Coupon Deals', 'manage_options', 'scda_coupons', array($this, 'admin_page'), 'dashicons-tickets-alt');
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $coupons = get_option($this->coupons_key, array());
        ?>
        <div class="wrap">
            <h1>Smart Coupon & Deal Aggregator</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <h2>Add New Coupon / Deal</h2>
                <input type="hidden" name="action" value="scda_add_coupon">
                <?php wp_nonce_field('scda_add_coupon_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="scda_title">Title</label></th>
                        <td><input type="text" name="title" id="scda_title" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="scda_description">Description</label></th>
                        <td><textarea name="description" id="scda_description" class="large-text" rows="3" required></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="scda_code">Coupon Code</label></th>
                        <td><input type="text" name="code" id="scda_code" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="scda_affiliate_url">Affiliate URL</label></th>
                        <td><input type="url" name="affiliate_url" id="scda_affiliate_url" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="scda_expiry">Expiry Date</label></th>
                        <td><input type="date" name="expiry" id="scda_expiry"></td>
                    </tr>
                    <tr>
                        <th><label for="scda_featured">Featured</label></th>
                        <td><input type="checkbox" name="featured" id="scda_featured" value="1"></td>
                    </tr>
                </table>
                <?php submit_button('Add Coupon'); ?>
            </form>

            <h2>Existing Coupons / Deals</h2>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Coupon Code</th>
                        <th>Expiry</th>
                        <th>Featured</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($coupons) {
                    foreach ($coupons as $coupon) {
                        $expiry = !empty($coupon['expiry']) ? esc_html($coupon['expiry']) : 'N/A';
                        echo '<tr><td>' . esc_html($coupon['title']) . '</td><td>' . esc_html($coupon['code']) . '</td><td>' . $expiry . '</td><td>' . ($coupon['featured'] ? 'Yes' : 'No') . '</td></tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No coupons added yet.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_add_coupon() {
        if (!current_user_can('manage_options') || !check_admin_referer('scda_add_coupon_nonce')) {
            wp_die('Unauthorized or invalid request');
        }

        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $code = sanitize_text_field($_POST['code'] ?? '');
        $affiliate_url = esc_url_raw($_POST['affiliate_url'] ?? '');
        $expiry = sanitize_text_field($_POST['expiry'] ?? '');
        $featured = !empty($_POST['featured']) ? 1 : 0;

        if (!$title || !$code || !$affiliate_url) {
            wp_die('Missing required fields');
        }

        $coupons = get_option($this->coupons_key, array());

        $coupons[] = array(
            'title' => $title,
            'description' => $description,
            'code' => $code,
            'affiliate_url' => $affiliate_url,
            'expiry' => $expiry,
            'featured' => $featured
        );

        update_option($this->coupons_key, $coupons);

        wp_redirect(admin_url('admin.php?page=scda_coupons&added=1'));
        exit;
    }

    public function render_coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'featured_only' => 'no',
            'limit' => 10
        ), $atts, 'scda_display_coupons');

        $coupons = get_option($this->coupons_key, array());

        if ($atts['featured_only'] === 'yes') {
            $coupons = array_filter($coupons, function ($coupon) {
                return !empty($coupon['featured']);
            });
        }

        // Remove expired coupons
        $today = date('Y-m-d');
        $coupons = array_filter($coupons, function ($coupon) use ($today) {
            return empty($coupon['expiry']) || $coupon['expiry'] >= $today;
        });

        if (!$coupons) {
            return '<p>No coupons available.</p>';
        }

        $output = '<div class="scda-coupons">';
        $count = 0;

        foreach ($coupons as $coupon) {
            if ($count >= (int)$atts['limit']) break;

            $title = esc_html($coupon['title']);
            $desc = esc_html($coupon['description']);
            $code = esc_html($coupon['code']);
            $url = esc_url($coupon['affiliate_url']);
            $output .= "<div class='scda-coupon'>";
            $output .= "<h3><a href='{$url}' target='_blank' rel='nofollow noopener'>{$title}</a></h3>";
            $output .= "<p>{$desc}</p>";
            $output .= "<p><strong>Code: <span class='scda-code'>{$code}</span></strong> <button class='scda-copy-btn' data-code='{$code}'>Copy</button></p>";
            $output .= "</div>";
            $count++;
        }

        $output .= '</div>';

        $output .= "<script>document.addEventListener('click', function(e) { if(e.target.classList.contains('scda-copy-btn')) { var code = e.target.getAttribute('data-code'); navigator.clipboard.writeText(code).then(function() { alert('Coupon code copied!'); }); } });</script>";

        return $output;
    }
}

new SmartCouponDealAggregator();