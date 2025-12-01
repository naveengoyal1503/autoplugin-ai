/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Booster.php
*/
<?php
/**
 * Plugin Name: AffiliateLink Booster
 * Description: Auto-convert product mentions into affiliate links with advanced cloaking and disclaimers.
 * Version: 1.0.0
 * Author: YourName
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class AffiliateLinkBooster {
    private $affiliate_programs = array();
    private $default_affiliate_id = '';
    private $is_premium = false;

    public function __construct() {
        $this->load_settings();
        add_filter('the_content', array($this, 'auto_link_affiliate_products'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    private function load_settings() {
        $this->affiliate_programs = get_option('alb_affiliate_programs', array());
        $this->default_affiliate_id = get_option('alb_default_affiliate_id', '');
        $this->is_premium = defined('ALB_PREMIUM') && ALB_PREMIUM === true;
    }

    public function auto_link_affiliate_products($content) {
        if (empty($this->affiliate_programs) || empty($content))
            return $content;

        // Extract all product keywords from affiliate programs
        $keywords = array_keys($this->affiliate_programs);
        if (empty($keywords))
            return $content;

        // Quickly exit if none of the keywords present
        $found_any = false;
        foreach ($keywords as $kw) {
            if (stripos($content, $kw) !== false) {
                $found_any = true;
                break;
            }
        }
        if (!$found_any) return $content;

        // Create replacement links
        foreach ($this->affiliate_programs as $keyword => $data) {
            $pattern = '/\b(' . preg_quote($keyword, '/') . ')\b/i';

            $affiliate_url = esc_url($data['url']);
            $link_title = esc_attr('Buy ' . $keyword);
            $nofollow = isset($data['nofollow']) && $data['nofollow'] ? ' rel="nofollow"' : '';
            $target = isset($data['new_tab']) && $data['new_tab'] ? ' target="_blank"' : '';

            $replacement = '<a href="' . $affiliate_url . '" title="' . $link_title . '"' . $nofollow . $target . ' class="alb-affiliate-link">$1</a>';

            $content = preg_replace($pattern, $replacement, $content, 1); // Only replace first occurrence per keyword
        }

        // Append affiliate disclaimer if premium enabled
        if ($this->is_premium) {
            $disclaimer = '<p><small><em>Disclosure: Some links on this site are affiliate links that provide us a commission at no extra cost to you.</em></small></p>';
            $content .= $disclaimer;
        }

        return $content;
    }

    public function add_admin_menu() {
        add_options_page('AffiliateLink Booster Settings', 'AffiliateLink Booster', 'manage_options', 'alb-settings', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('alb_settings_group', 'alb_affiliate_programs', array($this, 'validate_affiliate_programs'));
        register_setting('alb_settings_group', 'alb_default_affiliate_id');
    }

    public function validate_affiliate_programs($input) {
        if (!is_array($input)) return array();
        $valid = array();
        foreach ($input as $keyword => $data) {
            $keyword = sanitize_text_field($keyword);
            $url = esc_url_raw($data['url']);
            $nofollow = isset($data['nofollow']) ? boolval($data['nofollow']) : true;
            $new_tab = isset($data['new_tab']) ? boolval($data['new_tab']) : true;
            if ($keyword && $url) {
                $valid[$keyword] = array('url' => $url, 'nofollow' => $nofollow, 'new_tab' => $new_tab);
            }
        }
        return $valid;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AffiliateLink Booster Settings</h1>
            <form method="post" action="options.php">
            <?php settings_fields('alb_settings_group'); ?>
            <?php do_settings_sections('alb_settings_group'); ?>
            <table class="form-table" id="alb-affiliate-table">
                <thead><tr><th>Keyword / Product Name</th><th>Affiliate URL</th><th>NoFollow</th><th>Open in New Tab</th></tr></thead>
                <tbody>
                <?php
                $programs = get_option('alb_affiliate_programs', array());
                if (empty($programs)) {
                    $programs = array('Example Product' => array('url' => 'https://example.com/?ref=affiliate', 'nofollow' => true, 'new_tab' => true));
                }
                foreach ($programs as $keyword => $data) {
                    $k = esc_attr($keyword);
                    $url = esc_url($data['url']);
                    $nofollow_checked = $data['nofollow'] ? 'checked' : '';
                    $tab_checked = $data['new_tab'] ? 'checked' : '';
                    echo '<tr>' .
                         '<td><input type="text" name="alb_affiliate_programs[' . $k . '][keyword]" value="' . $k . '" readonly /></td>' .
                         '<td><input type="url" name="alb_affiliate_programs[' . $k . '][url]" value="' . $url . '" size="50" /></td>' .
                         '<td><input type="checkbox" name="alb_affiliate_programs[' . $k . '][nofollow]" ' . $nofollow_checked . '/></td>' .
                         '<td><input type="checkbox" name="alb_affiliate_programs[' . $k . '][new_tab]" ' . $tab_checked . '/></td>' .
                         '</tr>';
                }
                ?>
                </tbody>
            </table>
            <p>Add new product keywords by editing options manually or extend premium version.</p>
            <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new AffiliateLinkBooster();
