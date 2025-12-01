/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Generates and displays exclusive coupon codes for affiliate products to boost conversions and earnings.
 * Version: 1.0
 * Author: YourName
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateDealBooster {
    private $option_name = 'adb_coupons_data';

    public function __construct() {
        add_action('admin_menu', array($this, 'create_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliate_deal_booster', array($this, 'display_coupon_shortcode'));
    }

    public function create_admin_menu() {
        add_menu_page(
            'Affiliate Deal Booster',
            'Affiliate Deals',
            'manage_options',
            'affiliate-deal-booster',
            array($this, 'admin_page'),
            'dashicons-tickets'
        );
    }

    public function register_settings() {
        register_setting('adb_options_group', $this->option_name);
    }

    public function admin_page() {
        // Save coupon if posted
        if (isset($_POST['adb_submit']) && check_admin_referer('adb_save_coupon')) {
            $coupons = get_option($this->option_name, array());
            $new_coupon = array(
                'id' => time(),
                'title' => sanitize_text_field($_POST['adb_title']),
                'code' => sanitize_text_field($_POST['adb_code']),
                'description' => sanitize_textarea_field($_POST['adb_description']),
                'url' => esc_url_raw($_POST['adb_url'])
            );
            $coupons[] = $new_coupon;
            update_option($this->option_name, $coupons);
            echo '<div class="updated"><p>Coupon added successfully.</p></div>';
        }

        $coupons = get_option($this->option_name, array());
        ?>
        <div class="wrap">
            <h1>Affiliate Deal Booster</h1>
            <form method="post" action="">
                <?php wp_nonce_field('adb_save_coupon'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="adb_title">Coupon Title</label></th>
                        <td><input type="text" id="adb_title" name="adb_title" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="adb_code">Coupon Code</label></th>
                        <td><input type="text" id="adb_code" name="adb_code" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="adb_description">Description</label></th>
                        <td><textarea id="adb_description" name="adb_description" rows="4" cols="50" required></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="adb_url">Affiliate URL</label></th>
                        <td><input type="url" id="adb_url" name="adb_url" class="regular-text" placeholder="https://" required></td>
                    </tr>
                </table>
                <p><input type="submit" name="adb_submit" class="button button-primary" value="Add Coupon"></p>
            </form>

            <h2>Existing Coupons</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Affiliate URL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($coupons)) {
                        foreach ($coupons as $coupon) {
                            echo '<tr>' .
                                '<td>' . esc_html($coupon['title']) . '</td>' .
                                '<td><strong>' . esc_html($coupon['code']) . '</strong></td>' .
                                '<td>' . esc_html($coupon['description']) . '</td>' .
                                '<td><a href="' . esc_url($coupon['url']) . '" target="_blank" rel="nofollow noopener">Link</a></td>' .
                                '</tr>';
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

    public function display_coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts, 'affiliate_deal_booster');
        $coupons = get_option($this->option_name, array());
        if (empty($coupons)) {
            return '<p>No coupons available.</p>';
        }

        $coupon = null;
        if ($atts['id']) {
            foreach ($coupons as $c) {
                if ($c['id'] == intval($atts['id'])) {
                    $coupon = $c;
                    break;
                }
            }
        } else {
            // Pick a random coupon if id not specified
            $coupon = $coupons[array_rand($coupons)];
        }

        if (!$coupon) {
            return '<p>Coupon not found.</p>';
        }

        ob_start();
        ?>
        <div class="adb-coupon" style="border:1px solid #ddd;padding:10px;margin:10px 0;background:#f9f9f9;">
            <h3 style="margin:0 0 5px 0;"><?php echo esc_html($coupon['title']); ?></h3>
            <p style="margin:0 0 10px 0;"><?php echo esc_html($coupon['description']); ?></p>
            <p style="font-weight:bold;font-size:1.2em;margin:0 0 10px 0;">Use Code: <span style="color:#d9534f;"><?php echo esc_html($coupon['code']); ?></span></p>
            <p><a href="<?php echo esc_url($coupon['url']); ?>" target="_blank" rel="nofollow noopener" style="background:#0275d8;color:#fff;padding:8px 12px;text-decoration:none;border-radius:3px;">Shop Now</a></p>
        </div>
        <?php
        return ob_get_clean();
    }
}

new AffiliateDealBooster();
