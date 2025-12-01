<?php
/*
Plugin Name: WP Revenue Booster
Description: Maximize revenue by rotating affiliate links, displaying targeted ads, and promoting sponsored content.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_content'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-bar'
        );
    }

    public function admin_page() {
        if (isset($_POST['save_settings'])) {
            update_option('wp_revenue_booster_affiliate_links', $_POST['affiliate_links']);
            update_option('wp_revenue_booster_ads', $_POST['ads']);
            update_option('wp_revenue_booster_sponsored', $_POST['sponsored']);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $affiliate_links = get_option('wp_revenue_booster_affiliate_links', []);
        $ads = get_option('wp_revenue_booster_ads', []);
        $sponsored = get_option('wp_revenue_booster_sponsored', []);
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <h2>Affiliate Links</h2>
                <textarea name="affiliate_links" rows="5" cols="50"><?php echo esc_textarea(implode("\n", $affiliate_links)); ?></textarea>
                <h2>Ads</h2>
                <textarea name="ads" rows="5" cols="50"><?php echo esc_textarea(implode("\n", $ads)); ?></textarea>
                <h2>Sponsored Content</h2>
                <textarea name="sponsored" rows="5" cols="50"><?php echo esc_textarea(implode("\n", $sponsored)); ?></textarea>
                <p><input type="submit" name="save_settings" class="button button-primary" value="Save Settings"></p>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster-js', plugin_dir_url(__FILE__) . 'js/script.js', array(), '1.0', true);
    }

    public function inject_monetization_content() {
        $affiliate_links = get_option('wp_revenue_booster_affiliate_links', []);
        $ads = get_option('wp_revenue_booster_ads', []);
        $sponsored = get_option('wp_revenue_booster_sponsored', []);

        if (empty($affiliate_links) && empty($ads) && empty($sponsored)) return;

        $content = '';

        if (!empty($affiliate_links)) {
            $random_link = $affiliate_links[array_rand($affiliate_links)];
            $content .= '<div class="wp-revenue-booster-affiliate"><a href="' . esc_url($random_link) . '" target="_blank">Check out this offer!</a></div>';
        }

        if (!empty($ads)) {
            $random_ad = $ads[array_rand($ads)];
            $content .= '<div class="wp-revenue-booster-ad">' . $random_ad . '</div>';
        }

        if (!empty($sponsored)) {
            $random_sponsored = $sponsored[array_rand($sponsored)];
            $content .= '<div class="wp-revenue-booster-sponsored">' . $random_sponsored . '</div>';
        }

        echo '<div id="wp-revenue-booster-container">' . $content . '</div>';
    }
}

new WP_Revenue_Booster();
?>