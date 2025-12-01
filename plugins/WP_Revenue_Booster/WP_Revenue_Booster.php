<?php
/*
Plugin Name: WP Revenue Booster
Description: Automates and optimizes monetization for WordPress sites.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_code'));
        add_shortcode('premium_content', array($this, 'premium_content_shortcode'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-bar'
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_revenue_booster'); ?>
                <?php do_settings_sections('wp_revenue_booster'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">AdSense Code</th>
                        <td><textarea name="adsense_code" rows="5" cols="50"><?php echo esc_textarea(get_option('adsense_code')); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Affiliate Link</th>
                        <td><input type="text" name="affiliate_link" value="<?php echo esc_attr(get_option('affiliate_link')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Premium Content Message</th>
                        <td><input type="text" name="premium_message" value="<?php echo esc_attr(get_option('premium_message')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function inject_monetization_code() {
        $adsense = get_option('adsense_code');
        $affiliate = get_option('affiliate_link');
        if ($adsense) {
            echo '<div class="wp-revenue-adsense">' . $adsense . '</div>';
        }
        if ($affiliate) {
            echo '<div class="wp-revenue-affiliate">Check out this <a href="' . esc_url($affiliate) . '" target="_blank">affiliate link</a>.</div>';
        }
    }

    public function premium_content_shortcode($atts, $content = null) {
        $message = get_option('premium_message', 'Upgrade to premium to view this content.');
        if (is_user_logged_in()) {
            return '<div class="wp-revenue-premium">' . $content . '</div>';
        } else {
            return '<div class="wp-revenue-premium-message">' . $message . '</div>';
        }
    }
}

new WP_Revenue_Booster();

// Register settings
add_action('admin_init', function() {
    register_setting('wp_revenue_booster', 'adsense_code');
    register_setting('wp_revenue_booster', 'affiliate_link');
    register_setting('wp_revenue_booster', 'premium_message');
});

// Enqueue styles
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('wp-revenue-booster', plugins_url('style.css', __FILE__));
});
?>