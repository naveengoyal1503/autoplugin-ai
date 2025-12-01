<?php
/*
Plugin Name: WP Revenue Booster
Description: Automatically suggests and inserts high-converting affiliate links, coupons, and sponsored content into your posts.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('save_post', array($this, 'process_post_content'));
        add_action('wp_head', array($this, 'insert_tracking_code'));
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (isset($_POST['submit'])) {
            update_option('wp_revenue_booster_affiliate_links', $_POST['affiliate_links']);
            update_option('wp_revenue_booster_coupons', $_POST['coupons']);
            update_option('wp_revenue_booster_sponsored', $_POST['sponsored']);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $affiliate_links = get_option('wp_revenue_booster_affiliate_links', '');
        $coupons = get_option('wp_revenue_booster_coupons', '');
        $sponsored = get_option('wp_revenue_booster_sponsored', '');
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label>Affiliate Links (JSON)</label></th>
                        <td><textarea name="affiliate_links" rows="5" cols="50"><?php echo esc_textarea($affiliate_links); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Coupons (JSON)</label></th>
                        <td><textarea name="coupons" rows="5" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Sponsored Content (JSON)</label></th>
                        <td><textarea name="sponsored" rows="5" cols="50"><?php echo esc_textarea($sponsored); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function process_post_content($post_id) {
        if (wp_is_post_revision($post_id)) return;
        $post = get_post($post_id);
        if ($post->post_type != 'post') return;

        $affiliate_links = json_decode(get_option('wp_revenue_booster_affiliate_links', '[]'), true);
        $coupons = json_decode(get_option('wp_revenue_booster_coupons', '[]'), true);
        $sponsored = json_decode(get_option('wp_revenue_booster_sponsored', '[]'), true);

        $content = $post->post_content;
        foreach ($affiliate_links as $keyword => $link) {
            $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<a href="' . esc_url($link) . '" target="_blank">' . $keyword . '</a>', $content, 1);
        }
        foreach ($coupons as $keyword => $coupon) {
            $content = str_replace($keyword, '<strong>' . $keyword . '</strong> (Use code: ' . $coupon . ')', $content);
        }
        foreach ($sponsored as $keyword => $content_text) {
            $content .= '<div class="sponsored-content">' . $content_text . '</div>';
        }

        remove_action('save_post', array($this, 'process_post_content'));
        wp_update_post(array('ID' => $post_id, 'post_content' => $content));
        add_action('save_post', array($this, 'process_post_content'));
    }

    public function insert_tracking_code() {
        echo '<script>console.log("WP Revenue Booster tracking active");</script>';
    }
}

new WP_Revenue_Booster();
?>