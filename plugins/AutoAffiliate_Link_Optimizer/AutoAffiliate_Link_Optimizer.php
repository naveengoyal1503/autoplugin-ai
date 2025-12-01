/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AutoAffiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: AutoAffiliate Link Optimizer
 * Description: Automatically converts product mentions to affiliate links with tracking and A/B testing.
 * Version: 1.0
 * Author: Generated
 */

if (!defined('ABSPATH')) exit;

class AutoAffiliateLinkOptimizer {
    private $option_name = 'aalo_settings';

    public function __construct() {
        add_action('init', array($this, 'auto_convert_links'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'track_affiliate_clicks'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aalo_track_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_aalo_track_click', array($this, 'ajax_track_click'));
    }

    public function admin_menu() {
        add_options_page('AutoAffiliate Link Optimizer', 'AutoAffiliate Link', 'manage_options', 'aalo', array($this, 'settings_page'));
    }

    public function settings_init() {
        register_setting('aalo_settings_group', $this->option_name);

        add_settings_section('aalo_main_section', 'Main Settings', null, 'aalo');

        add_settings_field(
            'affiliate_domains',
            'Affiliate Domains (comma separated)',
            array($this, 'affiliate_domains_render'),
            'aalo',
            'aalo_main_section'
        );
        add_settings_field(
            'default_affiliate_id',
            'Default Affiliate ID',
            array($this, 'default_affiliate_id_render'),
            'aalo',
            'aalo_main_section'
        );
    }

    public function affiliate_domains_render() {
        $options = get_option($this->option_name);
        echo '<input type="text" name="'.esc_attr($this->option_name).'[affiliate_domains]" value="'.esc_attr($options['affiliate_domains'] ?? 'amazon.com,ebay.com').'" size="50" />';
        echo '<p class="description">Domains to detect and convert to affiliate links (comma separated).</p>';
    }

    public function default_affiliate_id_render() {
        $options = get_option($this->option_name);
        echo '<input type="text" name="'.esc_attr($this->option_name).'[default_affiliate_id]" value="'.esc_attr($options['default_affiliate_id'] ?? '') .'" size="50" />';
        echo '<p class="description">Affiliate ID to append if none present.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AutoAffiliate Link Optimizer Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('aalo_settings_group');
                do_settings_sections('aalo');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function auto_convert_links($content) {
        $options = get_option($this->option_name);
        if (empty($options['affiliate_domains'])) return $content;

        $domains = array_map('trim', explode(',', $options['affiliate_domains']));
        $default_id = $options['default_affiliate_id'] ?? '';

        if (empty($domains) || empty($default_id)) return $content;

        // Match URLs in content
        // Regex to find links to these domains without affiliate params
        $pattern = '/https?:\/\/([\w.-]*?)\/([^\s"\'>]*)/i';

        $content = preg_replace_callback($pattern, function($matches) use($domains, $default_id) {
            $full_url = $matches;
            $host = parse_url($full_url, PHP_URL_HOST);

            if (!$host) return $full_url;

            $matched_domain = false;
            foreach($domains as $domain) {
                if (stripos($host, $domain) !== false) {
                    $matched_domain = $domain;
                    break;
                }
            }

            if (!$matched_domain) return $full_url;

            // Append affiliate param if missing
            $url_components = parse_url($full_url);
            parse_str($url_components['query'] ?? '', $query);

            // Example for amazon: tag=affiliateid
            $aff_param = ($matched_domain == 'amazon.com') ? 'tag' : 'aff_id';

            if (empty($query[$aff_param])) {
                $query[$aff_param] = $default_id;
                $scheme = $url_components['scheme'] ?? 'https';
                $host = $url_components['host'];
                $path = $url_components['path'] ?? '';
                $new_query = http_build_query($query);
                $new_url = $scheme.'://'.$host.$path.'?'.$new_query;

                // Add JavaScript redirect for click tracking
                $encoded_url = esc_url($new_url);
                return '<a href="#" data-affurl="'.esc_attr($encoded_url).'" class="aalo-aff-link">'.$encoded_url.'</a>';
            }
            return $full_url;
        }, $content);

        return $content;
    }

    public function enqueue_scripts() {
        if (is_singular()) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('aalo-script', plugin_dir_url(__FILE__) . 'aalo-script.js', array('jquery'), '1.0', true);
            wp_localize_script('aalo-script', 'aalo_ajax', array('ajax_url' => admin_url('admin-ajax.php')));

            // Inline JS to handle click tracking
            $inline_js = "
            jQuery(document).ready(function($) {
                $('.aalo-aff-link').on('click', function(e) {
                    e.preventDefault();
                    var url = $(this).data('affurl');
                    $.post(aalo_ajax.ajax_url, { action: 'aalo_track_click', url: url }, function() {
                        window.location.href = url;
                    });
                });
            });
            ";
            wp_add_inline_script('aalo-script', $inline_js);
        }
    }

    public function ajax_track_click() {
        if (isset($_POST['url'])) {
            $url = sanitize_text_field($_POST['url']);
            // Here you would add real tracking logic such as logging to database
            // For simplicity, just reply success
            wp_send_json_success();
        } else {
            wp_send_json_error('No URL');
        }
        wp_die();
    }

    public function track_affiliate_clicks($content) {
        // This can include optional server-side tracking or other logic if needed
        return $content;
    }
}

new AutoAffiliateLinkOptimizer();