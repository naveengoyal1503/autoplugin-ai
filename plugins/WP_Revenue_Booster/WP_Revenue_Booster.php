/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Maximize revenue by rotating and optimizing affiliate links, ads, and sponsored content.
 * Version: 1.0
 * Author: RevenueBoost Team
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('revenue_booster', array($this, 'revenue_booster_shortcode'));
        add_action('wp_ajax_save_conversion', array($this, 'save_conversion'));
        add_action('wp_ajax_nopriv_save_conversion', array($this, 'save_conversion'));
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
                        <th scope="row">Affiliate Links</th>
                        <td><textarea name="affiliate_links" rows="5" cols="50"><?php echo esc_textarea(get_option('affiliate_links')); ?></textarea><br />
                        One link per line. Format: URL|Description</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Ad Codes</th>
                        <td><textarea name="ad_codes" rows="5" cols="50"><?php echo esc_textarea(get_option('ad_codes')); ?></textarea><br />
                        One ad code per line.</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Sponsored Content</th>
                        <td><textarea name="sponsored_content" rows="5" cols="50"><?php echo esc_textarea(get_option('sponsored_content')); ?></textarea><br />
                        One content block per line.</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <div id="conversion-stats">
                <h2>Conversion Stats</h2>
                <div id="stats-content"></div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $.post(ajaxurl, {action: 'get_conversion_stats'}, function(response) {
                    $('#stats-content').html(response);
                });
            });
        </script>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }

    public function revenue_booster_shortcode($atts) {
        $options = shortcode_atts(array(
            'type' => 'affiliate',
        ), $atts);

        $output = '';
        if ($options['type'] === 'affiliate') {
            $links = explode('\n', get_option('affiliate_links'));
            $link = $this->get_best_performing($links);
            $output = '<a href="' . esc_url($link['url']) . '" target="_blank" onclick="recordConversion(\'' . $link['url'] . '\')">' . esc_html($link['desc']) . '</a>';
        } elseif ($options['type'] === 'ad') {
            $ads = explode('\n', get_option('ad_codes'));
            $ad = $this->get_best_performing($ads);
            $output = $ad;
        } elseif ($options['type'] === 'sponsored') {
            $contents = explode('\n', get_option('sponsored_content'));
            $content = $this->get_best_performing($contents);
            $output = $content;
        }

        return $output;
    }

    private function get_best_performing($items) {
        // Simple round-robin for free version
        $index = array_rand($items);
        $item = $items[$index];
        if (strpos($item, '|') !== false) {
            list($url, $desc) = explode('|', $item, 2);
            return array('url' => $url, 'desc' => $desc);
        }
        return $item;
    }

    public function save_conversion() {
        $url = sanitize_text_field($_POST['url']);
        $conversions = get_option('conversion_stats', array());
        if (!isset($conversions[$url])) {
            $conversions[$url] = 0;
        }
        $conversions[$url]++;
        update_option('conversion_stats', $conversions);
        wp_die('Conversion recorded');
    }
}

function recordConversion(url) {
    jQuery.post(ajaxurl, {action: 'save_conversion', url: url});
}

new WP_Revenue_Booster();
?>