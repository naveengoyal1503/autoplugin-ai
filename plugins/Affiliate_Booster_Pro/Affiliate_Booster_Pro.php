<?php
/*
Plugin Name: Affiliate Booster Pro
Description: Automates affiliate link management, coupon integration, and performance tracking.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AffiliateBoosterPro {

    private $version = '1.0';

    public function __construct() {
        add_action('admin_menu', array($this, 'abp_add_admin_menu'));
        add_action('admin_init', array($this, 'abp_settings_init'));
        add_filter('the_content', array($this, 'abp_auto_link_affiliates'));
    }

    public function abp_add_admin_menu() {
        add_menu_page('Affiliate Booster Pro', 'Affiliate Booster', 'manage_options', 'affiliate_booster_pro', array($this, 'abp_options_page'));
    }

    public function abp_settings_init() {
        register_setting('abp_plugin', 'abp_settings');

        add_settings_section(
            'abp_plugin_section',
            __('Settings', 'affiliateboosterpro'),
            null,
            'abp_plugin'
        );

        add_settings_field(
            'abp_affiliate_domains',
            __('Affiliate Domains (comma separated)', 'affiliateboosterpro'),
            array($this, 'abp_affiliate_domains_render'),
            'abp_plugin',
            'abp_plugin_section'
        );

        add_settings_field(
            'abp_coupon_codes',
            __('Coupon Codes (format: code|description per line)', 'affiliateboosterpro'),
            array($this, 'abp_coupon_codes_render'),
            'abp_plugin',
            'abp_plugin_section'
        );
    }

    public function abp_affiliate_domains_render() {
        $options = get_option('abp_settings');
        ?>
        <textarea cols='40' rows='3' name='abp_settings[affiliate_domains]'><?php echo isset($options['affiliate_domains']) ? esc_textarea($options['affiliate_domains']) : ''; ?></textarea>
        <p class='description'>Add affiliate domains for automatic linking, separated by commas</p>
        <?php
    }

    public function abp_coupon_codes_render() {
        $options = get_option('abp_settings');
        ?>
        <textarea cols='40' rows='6' name='abp_settings[coupon_codes]'><?php echo isset($options['coupon_codes']) ? esc_textarea($options['coupon_codes']) : ''; ?></textarea>
        <p class='description'>Each line: code|description</p>
        <?php
    }

    public function abp_options_page() {
        ?>
        <form action='options.php' method='post'>
            <h1>Affiliate Booster Pro Settings</h1>
            <?php
            settings_fields('abp_plugin');
            do_settings_sections('abp_plugin');
            submit_button('Save Settings');
            ?>
        </form>
        <?php
    }

    public function abp_auto_link_affiliates($content) {
        $options = get_option('abp_settings');
        if (empty($options['affiliate_domains'])) return $content;

        $domains = array_map('trim', explode(',', $options['affiliate_domains']));

        $pattern = '/\b(?:https?:\/\/)?(?:www\.)?(' . implode('|', array_map('preg_quote', $domains)) . ')[^\s"\']*/i';

        $content = preg_replace_callback($pattern, function($matches) {
            $url = esc_url($matches);
            // Wrap affiliate URL with tracking parameters if needed
            if (strpos($url, 'aff_id=') === false) {
                $sep = (strpos($url, '?') === false) ? '?' : '&';
                $url .= $sep . 'aff_id=12345'; // Example fixed affiliate ID
            }
            return '<a href="' . $url . '" target="_blank" rel="nofollow noopener">' . $url . '</a>';
        }, $content);

        // Append coupons at end of content
        if (!empty($options['coupon_codes'])) {
            $lines = explode("\n", $options['coupon_codes']);
            $output = '<div class="affiliate-coupons"><h3>Exclusive Coupons:</h3><ul>';
            foreach ($lines as $line) {
                $parts = explode('|', $line);
                if(count($parts) === 2) {
                    $code = esc_html(trim($parts));
                    $desc = esc_html(trim($parts[1]));
                    $output .= '<li><strong>' . $code . '</strong>: ' . $desc . '</li>';
                }
            }
            $output .= '</ul></div>';
            $content .= $output;
        }

        return $content;
    }

}

new AffiliateBoosterPro();
