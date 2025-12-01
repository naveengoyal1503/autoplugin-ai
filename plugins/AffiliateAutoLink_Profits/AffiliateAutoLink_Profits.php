/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateAutoLink_Profits.php
*/
<?php
/**
 * Plugin Name: AffiliateAutoLink Profits
 * Description: Automatically convert product mentions to optimized affiliate links with revenue-driven A/B testing.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateAutoLinkProfits {
    private $option_name = 'aalp_options';
    private $default_options = [
        'enabled' => 1,
        'affiliate_keywords' => "exampleproduct1,exampleproduct2", // comma separated keywords
        'affiliate_urls' => "https://affiliatenetwork.com/ref1,https://affiliatenetwork.com/ref2", // comma separated urls
        'test_mode' => 1
    ];
    
    public function __construct() {
        add_filter('the_content', [$this, 'auto_link_affiliate']);
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_init', [$this, 'page_init']);
    }

    public function get_options() {
        $opts = get_option($this->option_name);
        if (!$opts) {
            update_option($this->option_name, $this->default_options);
            return $this->default_options;
        }
        return wp_parse_args($opts, $this->default_options);
    }

    public function auto_link_affiliate($content) {
        $options = $this->get_options();
        if (empty($options['enabled'])) {
            return $content;
        }

        $keywords = explode(',', str_replace(' ', '', $options['affiliate_keywords']));
        $urls = explode(',', str_replace(' ', '', $options['affiliate_urls']));

        if (count($keywords) !== count($urls) || empty($keywords) || empty($urls)) {
            return $content; // misconfiguration fallback
        }

        // For A/B test: rotate affiliate url each viewing (simple example)
        $user_key = 'aalp_url_index';
        if (!isset($_COOKIE[$user_key])) {
            $index = rand(0, count($urls)-1);
            setcookie($user_key, $index, time()+3600*24*30, COOKIEPATH, COOKIE_DOMAIN);
        } else {
            $index = intval($_COOKIE[$user_key]);
            if ($index < 0 || $index >= count($urls)) {
                $index = 0;
            }
        }

        $chosen_url = $urls[$index];

        // Escape keywords for regex
        $escaped_keywords = array_map('preg_quote', $keywords);

        // Replace only first occurrence of each keyword in post content outside HTML tags
        foreach ($escaped_keywords as $i => $kw) {
            $pattern = '/(?<!<a[^>]*>)(\b' . $kw . '\b)(?![^<]*<\/a>)/i';
            $replacement = '<a href="' . esc_url($chosen_url) . '" target="_blank" rel="nofollow noopener">$1</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    public function add_plugin_page() {
        add_options_page(
            'AffiliateAutoLink Profits Settings',
            'AffiliateAutoLink Profits',
            'manage_options',
            'affiliateautolink-profits',
            [$this, 'create_admin_page']
        );
    }

    public function create_admin_page() {
        $options = $this->get_options();
        ?>
        <div class="wrap">
            <h1>AffiliateAutoLink Profits Settings</h1>
            <form method="post" action="options.php">
            <?php
                settings_fields('aalp_option_group');
                do_settings_sections('affiliateautolink-profits');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'aalp_option_group',
            $this->option_name,
            [$this, 'sanitize']
        );

        add_settings_section(
            'setting_section_id',
            'Settings',
            null,
            'affiliateautolink-profits'
        );

        add_settings_field(
            'enabled',
            'Enable Auto-Linking',
            [$this, 'enabled_callback'],
            'affiliateautolink-profits',
            'setting_section_id'
        );

        add_settings_field(
            'affiliate_keywords',
            'Affiliate Keywords (comma separated)',
            [$this, 'keywords_callback'],
            'affiliateautolink-profits',
            'setting_section_id'
        );

        add_settings_field(
            'affiliate_urls',
            'Affiliate URLs (comma separated, matched by order)',
            [$this, 'urls_callback'],
            'affiliateautolink-profits',
            'setting_section_id'
        );

        add_settings_field(
            'test_mode',
            'Enable Simple A/B Test Mode',
            [$this, 'testmode_callback'],
            'affiliateautolink-profits',
            'setting_section_id'
        );
    }

    public function sanitize($input) {
        $new_input = [];
        $new_input['enabled'] = isset($input['enabled']) && $input['enabled'] == 1 ? 1 : 0;

        if (isset($input['affiliate_keywords'])) {
            $new_input['affiliate_keywords'] = sanitize_text_field($input['affiliate_keywords']);
        }

        if (isset($input['affiliate_urls'])) {
            $urls_arr = explode(',', $input['affiliate_urls']);
            $safe_urls = [];
            foreach ($urls_arr as $url) {
                $safe_urls[] = esc_url_raw(trim($url));
            }
            $new_input['affiliate_urls'] = implode(',', $safe_urls);
        }

        $new_input['test_mode'] = isset($input['test_mode']) && $input['test_mode'] == 1 ? 1 : 0;

        return $new_input;
    }

    public function enabled_callback() {
        $options = $this->get_options();
        printf('<input type="checkbox" id="enabled" name="%s[enabled]" value="1" %s /> Enable', $this->option_name, checked(1, $options['enabled'], false));
    }

    public function keywords_callback() {
        $options = $this->get_options();
        printf('<input type="text" id="affiliate_keywords" name="%s[affiliate_keywords]" value="%s" size="50" />', $this->option_name, esc_attr($options['affiliate_keywords']));
    }

    public function urls_callback() {
        $options = $this->get_options();
        printf('<input type="text" id="affiliate_urls" name="%s[affiliate_urls]" value="%s" size="50" />', $this->option_name, esc_attr($options['affiliate_urls']));
    }

    public function testmode_callback() {
        $options = $this->get_options();
        printf('<input type="checkbox" id="test_mode" name="%s[test_mode]" value="1" %s /> Enable A/B Test Mode (rotates links by cookie)', $this->option_name, checked(1, $options['test_mode'], false));
    }

}

new AffiliateAutoLinkProfits();
