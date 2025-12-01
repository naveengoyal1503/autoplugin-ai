/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Hub.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Hub
 * Description: Manage exclusive affiliate coupons and deals to increase monetization.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

class SmartAffiliateCouponHub {
    private $coupons_option = 'sach_coupons';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('sach_coupons', array($this, 'render_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Affiliate Coupons', 
            'Affiliate Coupons', 
            'manage_options', 
            'sach-affiliate-coupons', 
            array($this, 'admin_page'), 
            'dashicons-tickets', 
            60
        );
    }

    public function register_settings() {
        register_setting('sach_settings_group', $this->coupons_option);
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) return;

        if (isset($_POST['sach_delete_coupon'])) {
            $this->handle_delete_coupon(intval($_POST['coupon_index']));
        }

        if (isset($_POST['sach_add_coupon'])) {
            $this->handle_add_coupon();
        }

        $coupons = get_option($this->coupons_option, array());
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Hub</h1>
            <form method="post" action="">
                <h2>Add New Coupon</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="coupon_code">Coupon Code</label></th>
                        <td><input name="coupon_code" type="text" id="coupon_code" value="" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="coupon_description">Description</label></th>
                        <td><input name="coupon_description" type="text" id="coupon_description" value="" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="coupon_url">Affiliate URL</label></th>
                        <td><input name="coupon_url" type="url" id="coupon_url" value="" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="coupon_expiry">Expiry Date (optional)</label></th>
                        <td><input name="coupon_expiry" type="date" id="coupon_expiry" value="" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button('Add Coupon', 'primary', 'sach_add_coupon'); ?>
            </form>
            <h2>Saved Coupons</h2>
            <?php if (!empty($coupons)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Coupon Code</th>
                            <th>Description</th>
                            <th>Affiliate URL</th>
                            <th>Expiry Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($coupons as $index => $coupon) : ?>
                        <tr>
                            <td><?php echo esc_html($coupon['code']); ?></td>
                            <td><?php echo esc_html($coupon['description']); ?></td>
                            <td><a href="<?php echo esc_url($coupon['url']); ?>" target="_blank" rel="nofollow noopener noreferrer"><?php echo esc_html($coupon['url']); ?></a></td>
                            <td><?php echo !empty($coupon['expiry']) ? esc_html($coupon['expiry']) : '—'; ?></td>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this coupon?');">
                                    <input type="hidden" name="coupon_index" value="<?php echo intval($index); ?>">
                                    <?php submit_button('Delete', 'delete', 'sach_delete_coupon', false); ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No coupons added yet.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function handle_add_coupon() {
        $code = sanitize_text_field($_POST['coupon_code']);
        $description = sanitize_text_field($_POST['coupon_description']);
        $url = esc_url_raw($_POST['coupon_url']);
        $expiry = sanitize_text_field($_POST['coupon_expiry']);

        if (empty($code) || empty($description) || empty($url)) {
            add_settings_error('sach_messages', 'sach_error', 'Please fill all required fields.', 'error');
            return;
        }

        $coupons = get_option($this->coupons_option, array());

        $coupons[] = array(
            'code' => $code,
            'description' => $description,
            'url' => $url,
            'expiry' => $expiry
        );

        update_option($this->coupons_option, $coupons);

        add_settings_error('sach_messages', 'sach_success', 'Coupon added successfully.', 'updated');
        // To show updated messages
        settings_errors('sach_messages');
    }

    private function handle_delete_coupon($index) {
        $coupons = get_option($this->coupons_option, array());
        if (isset($coupons[$index])) {
            unset($coupons[$index]);
            $coupons = array_values($coupons);
            update_option($this->coupons_option, $coupons);
            add_settings_error('sach_messages', 'sach_success', 'Coupon deleted.', 'updated');
            settings_errors('sach_messages');
        }
    }

    public function render_coupons_shortcode($atts) {
        $coupons = get_option($this->coupons_option, array());
        if (empty($coupons)) return '<p>No coupons available at the moment.</p>';

        $output = '<div class="sach-coupons">
        <ul>';
        $today = date('Y-m-d');
        foreach ($coupons as $coupon) {
            if (!empty($coupon['expiry']) && $coupon['expiry'] < $today) continue; // Skip expired
            $code = esc_html($coupon['code']);
            $desc = esc_html($coupon['description']);
            $url = esc_url($coupon['url']);
            $output .= "<li><strong>$code</strong>: $desc &mdash; <a href='$url' target='_blank' rel='nofollow noopener noreferrer'>Get Deal</a></li>";
        }
        $output .= '</ul></div>';
        return $output;
    }

    public function enqueue_scripts() {
        wp_register_style('sach_styles', false);
        wp_enqueue_style('sach_styles');
        // Inline CSS for simple coupon styling
        wp_add_inline_style('sach_styles', ".sach-coupons ul{list-style:none;padding:0;} .sach-coupons li{background:#f9f9f9;margin:0 0 8px;padding:10px;border:1px solid #ddd;border-radius:3px;} .sach-coupons a{color:#0073aa;text-decoration:none;} .sach-coupons a:hover{color:#005177;text-decoration:underline;}");
    }
}

new SmartAffiliateCouponHub();