<?php
/*
Plugin Name: WP SmartPaywall
Description: Smart paywall for content monetization with adaptive offers.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_SmartPaywall.php
*/

if (!defined('ABSPATH')) {
    exit;
}

class WPSmartPaywall {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('the_content', array($this, 'apply_paywall'));
        add_shortcode('smartpaywall', array($this, 'shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_smartpaywall_track', array($this, 'track_conversion'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smartpaywall-js', plugin_dir_url(__FILE__) . 'smartpaywall.js', array('jquery'), '1.0', true);
        wp_localize_script('smartpaywall-js', 'smartpaywall_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function apply_paywall($content) {
        if (is_admin() || is_user_logged_in()) {
            return $content;
        }

        $settings = get_option('smartpaywall_settings', array());
        $threshold = isset($settings['threshold']) ? intval($settings['threshold']) : 3;
        $offer_type = isset($settings['offer_type']) ? $settings['offer_type'] : 'subscription';

        $visited_pages = isset($_COOKIE['smartpaywall_pages']) ? explode(',', $_COOKIE['smartpaywall_pages']) : array();
        $visited_pages[] = get_the_ID();
        $visited_pages = array_unique($visited_pages);
        setcookie('smartpaywall_pages', implode(',', $visited_pages), time() + 3600, '/');

        if (count($visited_pages) >= $threshold) {
            $offer = $this->generate_offer($offer_type);
            return $offer . '<div class="smartpaywall-content">' . $content . '</div>';
        }

        return $content;
    }

    public function generate_offer($type) {
        switch ($type) {
            case 'subscription':
                return '<div class="smartpaywall-offer">Subscribe for full access to all content!</div>';
            case 'one_time':
                return '<div class="smartpaywall-offer">Pay once for lifetime access!</div>';
            case 'affiliate':
                return '<div class="smartpaywall-offer">Earn by referring friends to unlock content!</div>';
            default:
                return '<div class="smartpaywall-offer">Unlock content with our flexible options!</div>';
        }
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array('type' => 'subscription'), $atts, 'smartpaywall');
        return $this->generate_offer($atts['type']);
    }

    public function admin_menu() {
        add_options_page('SmartPaywall Settings', 'SmartPaywall', 'manage_options', 'smartpaywall', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['smartpaywall_save'])) {
            update_option('smartpaywall_settings', array(
                'threshold' => intval($_POST['threshold']),
                'offer_type' => sanitize_text_field($_POST['offer_type'])
            ));
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
        $settings = get_option('smartpaywall_settings', array());
        ?>
        <div class="wrap">
            <h1>SmartPaywall Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Pages before paywall</th>
                        <td><input type="number" name="threshold" value="<?php echo esc_attr($settings['threshold'] ?? 3); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Offer Type</th>
                        <td>
                            <select name="offer_type">
                                <option value="subscription" <?php selected($settings['offer_type'] ?? 'subscription', 'subscription'); ?>>Subscription</option>
                                <option value="one_time" <?php selected($settings['offer_type'] ?? 'subscription', 'one_time'); ?>>One-time Payment</option>
                                <option value="affiliate" <?php selected($settings['offer_type'] ?? 'subscription', 'affiliate'); ?>>Affiliate</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="smartpaywall_save" class="button-primary" value="Save Changes" />
                </p>
            </form>
        </div>
        <?php
    }

    public function track_conversion() {
        if (isset($_POST['conversion_type'])) {
            $log = get_option('smartpaywall_conversions', array());
            $log[] = array(
                'type' => sanitize_text_field($_POST['conversion_type']),
                'time' => current_time('mysql')
            );
            update_option('smartpaywall_conversions', $log);
            wp_die('Conversion tracked');
        }
    }
}

new WPSmartPaywall;

// smartpaywall.js
// (This would be a separate file, but for single-file requirement, it's included as comment)
/*
jQuery(document).ready(function($) {
    $('.smartpaywall-offer').on('click', function() {
        $.post(smartpaywall_ajax.ajax_url, {
            action: 'smartpaywall_track',
            conversion_type: 'offer_click'
        });
    });
});
*/
?>