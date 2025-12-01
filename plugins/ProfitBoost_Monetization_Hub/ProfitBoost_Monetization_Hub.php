<?php
/*
Plugin Name: ProfitBoost Monetization Hub
Description: All-in-one monetization plugin for WordPress.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ProfitBoost_Monetization_Hub.php
*/

class ProfitBoostMonetizationHub {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_ad_code'));
        add_shortcode('profitboost_affiliate', array($this, 'affiliate_shortcode'));
        add_shortcode('profitboost_membership', array($this, 'membership_shortcode'));
        add_shortcode('profitboost_product', array($this, 'product_shortcode'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'ProfitBoost Monetization',
            'ProfitBoost',
            'manage_options',
            'profitboost',
            array($this, 'admin_page'),
            'dashicons-chart-line'
        );
    }

    public function admin_page() {
        if (isset($_POST['profitboost_save'])) {
            update_option('profitboost_ad_code', sanitize_textarea_field($_POST['ad_code']));
            update_option('profitboost_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            update_option('profitboost_products', sanitize_textarea_field($_POST['products']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $ad_code = get_option('profitboost_ad_code', '');
        $affiliate_links = get_option('profitboost_affiliate_links', '');
        $products = get_option('profitboost_products', '');
        ?>
        <div class="wrap">
            <h1>ProfitBoost Monetization Hub</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Ad Code</th>
                        <td><textarea name="ad_code" rows="5" cols="50"><?php echo esc_textarea($ad_code); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (one per line: URL|Label)</th>
                        <td><textarea name="affiliate_links" rows="5" cols="50"><?php echo esc_textarea($affiliate_links); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Digital Products (one per line: URL|Label)</th>
                        <td><textarea name="products" rows="5" cols="50"><?php echo esc_textarea($products); ?></textarea></td>
                    </tr>
                </table>
                <input type="submit" name="profitboost_save" class="button button-primary" value="Save Settings">
            </form>
        </div>
        <?php
    }

    public function inject_ad_code() {
        $ad_code = get_option('profitboost_ad_code', '');
        echo $ad_code;
    }

    public function affiliate_shortcode($atts) {
        $links = get_option('profitboost_affiliate_links', '');
        $lines = explode("\n", $links);
        $output = '<ul class="profitboost-affiliate-links">';
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) === 2) {
                $output .= '<li><a href="' . esc_url($parts) . '" target="_blank">' . esc_html($parts[1]) . '</a></li>';
            }
        }
        $output .= '</ul>';
        return $output;
    }

    public function membership_shortcode($atts) {
        return '<div class="profitboost-membership">Membership content goes here. Upgrade for premium access.</div>';
    }

    public function product_shortcode($atts) {
        $products = get_option('profitboost_products', '');
        $lines = explode("\n", $products);
        $output = '<ul class="profitboost-products">';
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) === 2) {
                $output .= '<li><a href="' . esc_url($parts) . '" target="_blank">' . esc_html($parts[1]) . '</a></li>';
            }
        }
        $output .= '</ul>';
        return $output;
    }
}

new ProfitBoostMonetizationHub();
