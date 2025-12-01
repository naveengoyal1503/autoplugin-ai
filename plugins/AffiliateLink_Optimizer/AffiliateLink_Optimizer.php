<?php
/*
Plugin Name: AffiliateLink Optimizer
Plugin URI: https://example.com/affiliatelink-optimizer
Description: Automatically detects and converts regular URLs into affiliate links and provides performance tracking.
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Optimizer.php
License: GPLv2 or later
Text Domain: afflinkopt
*/
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateLinkOptimizer {
    private $option_name = 'afflinkopt_settings';
    private $default_affiliate_network = 'amazon';
    private $networks = ['amazon', 'ebay'];

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_filter('the_content', [$this, 'convert_links_to_affiliate']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_afflinkopt_track_click', [$this, 'track_click']);
        add_action('wp_ajax_nopriv_afflinkopt_track_click', [$this, 'track_click']);
    }

    public function admin_menu() {
        add_options_page('AffiliateLink Optimizer', 'AffiliateLink Optimizer', 'manage_options', 'afflinkopt', [$this, 'settings_page']);
    }

    public function settings_init() {
        register_setting('afflinkopt', $this->option_name);

        add_settings_section(
            'afflinkopt_section',
            __('Affiliate Networks Settings', 'afflinkopt'),
            null,
            'afflinkopt'
        );

        add_settings_field(
            'default_network',
            __('Default Affiliate Network', 'afflinkopt'),
            [$this, 'default_network_render'],
            'afflinkopt',
            'afflinkopt_section'
        );

        add_settings_field(
            'amazon_tag',
            __('Amazon Affiliate Tag', 'afflinkopt'),
            [$this, 'amazon_tag_render'],
            'afflinkopt',
            'afflinkopt_section'
        );

        add_settings_field(
            'ebay_cid',
            __('eBay Affiliate Campaign ID', 'afflinkopt'),
            [$this, 'ebay_cid_render'],
            'afflinkopt',
            'afflinkopt_section'
        );
    }

    public function default_network_render() {
        $options = get_option($this->option_name);
        ?>
        <select name="<?php echo $this->option_name; ?>[default_network]">
            <?php foreach ($this->networks as $network): ?>
                <option value="<?php echo esc_attr($network); ?>" <?php selected($options['default_network'] ?? $this->default_affiliate_network, $network); ?>><?php echo ucfirst($network); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function amazon_tag_render() {
        $options = get_option($this->option_name);
        ?>
        <input type="text" name="<?php echo $this->option_name; ?>[amazon_tag]" value="<?php echo esc_attr($options['amazon_tag'] ?? ''); ?>" placeholder="yourtag-20" />
        <?php
    }

    public function ebay_cid_render() {
        $options = get_option($this->option_name);
        ?>
        <input type="text" name="<?php echo $this->option_name; ?>[ebay_cid]" value="<?php echo esc_attr($options['ebay_cid'] ?? ''); ?>" placeholder="1234567890" />
        <?php
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('AffiliateLink Optimizer Settings', 'afflinkopt'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('afflinkopt');
                do_settings_sections('afflinkopt');
                submit_button();
                ?>
            </form>
            <h2><?php esc_html_e('Affiliate Link Performance', 'afflinkopt'); ?></h2>
            <p><?php esc_html_e('Click tracking statistics will be built here in future updates.', 'afflinkopt'); ?></p>
        </div>
        <?php
    }

    public function convert_links_to_affiliate($content) {
        $options = get_option($this->option_name);
        if (empty($options)) return $content;

        $amazon_tag = $options['amazon_tag'] ?? '';
        $ebay_cid = $options['ebay_cid'] ?? '';

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
        $anchors = $dom->getElementsByTagName('a');

        foreach ($anchors as $a) {
            $href = $a->getAttribute('href');
            if (strpos($href, 'amazon.com') !== false && !empty($amazon_tag)) {
                if (strpos($href, 'tag=') === false) {
                    $separator = strpos($href, '?') === false ? '?' : '&';
                    $new_href = $href . $separator . 'tag=' . urlencode($amazon_tag);
                    $a->setAttribute('href', $new_href);
                    $a->setAttribute('data-afflinkopt', 'amazon');
                }
            } elseif (strpos($href, 'ebay.com') !== false && !empty($ebay_cid)) {
                if (strpos($href, 'campid=') === false) {
                    $separator = strpos($href, '?') === false ? '?' : '&';
                    $new_href = $href . $separator . 'campid=' . urlencode($ebay_cid);
                    $a->setAttribute('href', $new_href);
                    $a->setAttribute('data-afflinkopt', 'ebay');
                }
            }
        }

        $html = $dom->saveHTML();
        // Remove the added html/body tags
        $body_start = strpos($html, '<body>');
        $body_end = strpos($html, '</body>');
        if ($body_start !== false && $body_end !== false) {
            $html = substr($html, $body_start + 6, $body_end - $body_start - 6);
        }
        return $html;
    }

    public function enqueue_scripts() {
        wp_enqueue_script('afflinkopt-script', plugin_dir_url(__FILE__) . 'afflinkopt.js', ['jquery'], '1.0', true);
        wp_localize_script('afflinkopt-script', 'afflinkopt', [
            'ajaxurl' => admin_url('admin-ajax.php'),
        ]);
    }

    public function track_click() {
        if (!isset($_POST['link'])) {
            wp_send_json_error('No link provided');
        }
        $link = sanitize_text_field($_POST['link']);

        // Here you would add database records or logging for clicks.
        // For this simple version, we just acknowledge.

        wp_send_json_success(['message' => 'Click recorded for: ' . $link]);
    }
}

new AffiliateLinkOptimizer();

/**
 * JavaScript part: inline in PHP for self-contained file
 */
add_action('wp_footer', function () {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('a[data-afflinkopt]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                var href = this.href;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                xhr.send('action=afflinkopt_track_click&link=' + encodeURIComponent(href));
            });
        });
    });
    </script>
    <?php
});