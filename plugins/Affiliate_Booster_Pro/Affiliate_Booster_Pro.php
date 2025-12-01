/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Booster Pro
 * Description: Auto-replace product mentions with affiliate links and optimize your affiliate revenue.
 * Version: 1.0
 * Author: PluginDev
 */

if (!defined('ABSPATH')) exit;

class AffiliateBoosterPro {
    private $affiliate_keywords = array(
        'camera' => 'https://example-affiliate.com/product/camera?affid=123',
        'laptop' => 'https://example-affiliate.com/product/laptop?affid=123'
        // Add more keywords and affiliate URLs here or via settings
    );

    public function __construct() {
        add_filter('the_content', array($this, 'replace_affiliate_links'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_abp_save_settings', array($this, 'save_settings'));
    }

    public function replace_affiliate_links($content) {
        $keywords = get_option('abp_affiliate_keywords', $this->affiliate_keywords);

        foreach ($keywords as $keyword => $url) {
            // Use regex to replace first occurrence of keyword with affiliate link
            $pattern = '/\b(' . preg_quote($keyword, '/') . ')\b/i';
            $replacement = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener">$1</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    public function admin_menu() {
        add_options_page('Affiliate Booster Pro', 'Affiliate Booster Pro', 'manage_options', 'affiliate-booster-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $keywords = get_option('abp_affiliate_keywords', $this->affiliate_keywords);

        echo '<div class="wrap"><h2>Affiliate Booster Pro Settings</h2>';
        echo '<form method="post" action="options.php">';
        settings_fields('abp_settings_group');
        do_settings_sections('abp_settings_group');

        echo '<table class="form-table" id="keywords-table">';
        echo '<tr><th>Keyword</th><th>Affiliate URL</th></tr>';
        foreach ($keywords as $keyword => $url) {
            echo '<tr><td><input type="text" name="abp_keywords[' . esc_attr($keyword) . ']" value="' . esc_attr($keyword) . '" readonly style="background:#eee"></td>';
            echo '<td><input type="url" name="abp_urls[' . esc_attr($keyword) . ']" value="' . esc_attr($url) . '" style="width:100%"></td></tr>';
        }
        echo '</table>';
        echo '<p><input type="submit" class="button-primary" value="Save Changes"></p>';
        echo '</form></div>';
    }

    public function save_settings() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        if (!isset($_POST['abp_urls'])) wp_die('No data');

        $keywords = array_keys($this->affiliate_keywords);
        $urls = $_POST['abp_urls'];
        $new_data = array();
        foreach ($keywords as $keyword) {
            if (!empty($urls[$keyword]) && filter_var($urls[$keyword], FILTER_VALIDATE_URL)) {
                $new_data[$keyword] = esc_url_raw($urls[$keyword]);
            } else {
                $new_data[$keyword] = $this->affiliate_keywords[$keyword];
            }
        }

        update_option('abp_affiliate_keywords', $new_data);
        wp_redirect(admin_url('options-general.php?page=affiliate-booster-pro&status=1'));
        exit;
    }
}

new AffiliateBoosterPro();
