/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Link Booster
 * Description: Automatically cloak, track, and optimize your affiliate links for better conversions.
 * Version: 1.0.0
 * Author: YourName
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Affiliate_Link_Booster {
    private $option_name = 'alb_options';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'auto_cloak_links'));
        add_action('wp_ajax_alb_track_click', array($this, 'track_click')); 
        add_action('init', array($this, 'redirect_affiliate'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Link Booster', 'Affiliate Booster', 'manage_options', 'affiliate_link_booster', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('alb_settings', $this->option_name);

        add_settings_section('alb_section', __('Settings', 'alb'), null, 'alb_settings');

        add_settings_field(
            'alb_affiliate_domains',
            __('Affiliate Domains (comma separated)', 'alb'),
            array($this, 'affiliate_domains_render'),
            'alb_settings',
            'alb_section'
        );

        add_settings_field(
            'alb_enable_ab_testing',
            __('Enable A/B Testing', 'alb'),
            array($this, 'enable_ab_testing_render'),
            'alb_settings',
            'alb_section'
        );

        add_settings_field(
            'alb_geotargeting',
            __('Enable Geotargeting', 'alb'),
            array($this, 'geotargeting_render'),
            'alb_settings',
            'alb_section'
        );
    }

    public function affiliate_domains_render() {
        $options = get_option($this->option_name);
        ?>
        <input type='text' name='<?php echo $this->option_name; ?>[affiliate_domains]' value='<?php echo esc_attr($options['affiliate_domains'] ?? ''); ?>' style='width: 300px;'>
        <p class='description'>Enter affiliate domains to cloak, e.g. example.com,affiliateprogram.com</p>
        <?php
    }

    public function enable_ab_testing_render() {
        $options = get_option($this->option_name);
        ?>
        <input type='checkbox' name='<?php echo $this->option_name; ?>[enable_ab_testing]' <?php checked(isset($options['enable_ab_testing']) ? $options['enable_ab_testing'] : 0, 1); ?> value='1'> Enable
        <p class='description'>Split traffic between multiple affiliate URLs for conversion optimization</p>
        <?php
    }

    public function geotargeting_render() {
        $options = get_option($this->option_name);
        ?>
        <input type='checkbox' name='<?php echo $this->option_name; ?>[geotargeting]' <?php checked(isset($options['geotargeting']) ? $options['geotargeting'] : 0, 1); ?> value='1'> Enable
        <p class='description'>Redirect users based on their country to region-specific affiliate URLs</p>
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Link Booster Settings</h2>
            <?php
            settings_fields('alb_settings');
            do_settings_sections('alb_settings');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function auto_cloak_links($content) {
        $options = get_option($this->option_name);
        if (empty($options['affiliate_domains'])) return $content;

        $domains = array_map('trim', explode(',', $options['affiliate_domains']));
        if (empty($domains)) return $content;

        // Regex to find links
        $pattern = '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i';

        $content = preg_replace_callback($pattern, function($matches) use ($domains, $options) {
            $url = $matches[1];
            foreach ($domains as $domain) {
                if (stripos($url, $domain) !== false) {
                    // Generate cloaked link
                    $cloaked_url = site_url('/alb-r/?url=' . urlencode(base64_encode($url)));
                    $full_link = str_replace($url, esc_attr($cloaked_url), $matches);
                    return $full_link;
                }
            }
            return $matches;
        }, $content);

        return $content;
    }

    public function redirect_affiliate() {
        if (isset($_GET['alb-r']) || isset($_GET['url'])) {
            $encoded_url = $_GET['url'] ?? '';
            if ($encoded_url) {
                $dest_url = base64_decode(urldecode($encoded_url));
                if ($this->is_valid_url($dest_url)) {
                    // Track the click
                    $this->record_click($dest_url);

                    // Geotargeting and A/B testing could be implemented here if enabled

                    wp_redirect($dest_url, 301);
                    exit;
                }
            }
            wp_die('Invalid affiliate redirect URL');
        }
    }

    private function record_click($url) {
        global $wpdb;
        $table = $wpdb->prefix . 'alb_clicks';
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (affiliate_url, clicked_at, ip_address) VALUES (%s, NOW(), %s)",
            $url,
            $_SERVER['REMOTE_ADDR']
        ));
    }

    private function is_valid_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function install() {
        global $wpdb;
        $table = $wpdb->prefix . 'alb_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            affiliate_url TEXT NOT NULL,
            clicked_at DATETIME NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

$affiliate_link_booster = new Affiliate_Link_Booster();
register_activation_hook(__FILE__, array($affiliate_link_booster, 'install'));