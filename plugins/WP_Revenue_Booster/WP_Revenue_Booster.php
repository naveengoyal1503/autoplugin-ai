/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Maximize your WordPress site's revenue with smart affiliate link rotation, targeted ads, and exclusive offer promotion.
 * Version: 1.0
 * Author: WP Revenue Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'display_revenue_elements'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'assets/style.css');
        wp_enqueue_script('wp-revenue-booster', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0', true);
    }

    public function display_revenue_elements() {
        $options = get_option('wp_revenue_booster_settings');
        $affiliate_links = isset($options['affiliate_links']) ? explode('\n', $options['affiliate_links']) : array();
        $ads = isset($options['ads']) ? explode('\n', $options['ads']) : array();
        $offers = isset($options['offers']) ? explode('\n', $options['offers']) : array();

        if (!empty($affiliate_links)) {
            $random_link = $affiliate_links[array_rand($affiliate_links)];
            echo '<div class="wp-revenue-affiliate"><a href="' . esc_url($random_link) . '" target="_blank">Check out this deal!</a></div>';
        }

        if (!empty($ads)) {
            $random_ad = $ads[array_rand($ads)];
            echo '<div class="wp-revenue-ad">' . wp_kses_post($random_ad) . '</div>';
        }

        if (!empty($offers)) {
            $random_offer = $offers[array_rand($offers)];
            echo '<div class="wp-revenue-offer">' . wp_kses_post($random_offer) . '</div>';
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp_revenue_booster',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('wp_revenue_booster', 'wp_revenue_booster_settings');

        add_settings_section(
            'wp_revenue_booster_section',
            'Revenue Booster Settings',
            null,
            'wp_revenue_booster'
        );

        add_settings_field(
            'affiliate_links',
            'Affiliate Links (one per line)',
            array($this, 'affiliate_links_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'ads',
            'Ad Codes (one per line)',
            array($this, 'ads_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'offers',
            'Exclusive Offers (one per line)',
            array($this, 'offers_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <textarea cols='40' rows='5' name='wp_revenue_booster_settings[affiliate_links]'>
            <?php echo isset($options['affiliate_links']) ? esc_textarea($options['affiliate_links']) : ''; ?>
        </textarea>
        <?php
    }

    public function ads_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <textarea cols='40' rows='5' name='wp_revenue_booster_settings[ads]'>
            <?php echo isset($options['ads']) ? esc_textarea($options['ads']) : ''; ?>
        </textarea>
        <?php
    }

    public function offers_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <textarea cols='40' rows='5' name='wp_revenue_booster_settings[offers]'>
            <?php echo isset($options['offers']) ? esc_textarea($options['offers']) : ''; ?>
        </textarea>
        <?php
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('wp_revenue_booster');
                do_settings_sections('wp_revenue_booster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new WP_Revenue_Booster();
?>