/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Booster Pro
 * Description: Auto-convert product URLs to affiliate links, track clicks, and insert coupons dynamically.
 * Version: 1.0
 * Author: Your Name
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateBoosterPro {
    private $option_name = 'affbp_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'convert_links_and_insert_coupons'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_affbp_track_click', array($this, 'track_click')); 
        add_action('wp_ajax_nopriv_affbp_track_click', array($this, 'track_click'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affbp-main-js', plugin_dir_url(__FILE__) . 'affbp-main.js', array('jquery'), '1.0', true);
        wp_localize_script('affbp-main-js', 'affbp_ajax_obj', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function add_admin_menu() {
        add_options_page('Affiliate Booster Pro Settings', 'Affiliate Booster Pro', 'manage_options', 'affbp', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('affbp_settings_group', $this->option_name);

        add_settings_section(
            'affbp_section_main',
            'Affiliate Booster Pro Settings',
            null,
            'affbp'
        );

        add_settings_field(
            'affbp_affiliate_prefix', 
            'Affiliate ID or Prefix', 
            array($this, 'affiliate_prefix_render'), 
            'affbp', 
            'affbp_section_main'
        );

        add_settings_field(
            'affbp_coupon_code', 
            'Global Coupon Code (optional)', 
            array($this, 'coupon_code_render'), 
            'affbp', 
            'affbp_section_main'
        );
    }

    public function affiliate_prefix_render() {
        $options = get_option($this->option_name);
        ?>
        <input type='text' name='<?php echo $this->option_name; ?>[affiliate_prefix]' value='<?php echo esc_attr($options['affiliate_prefix'] ?? ''); ?>' placeholder='e.g. ref=12345'/>
        <p class="description">Enter your affiliate ID or URL parameter to be appended.</p>
        <?php
    }

    public function coupon_code_render() {
        $options = get_option($this->option_name);
        ?>
        <input type='text' name='<?php echo $this->option_name; ?>[coupon_code]' value='<?php echo esc_attr($options['coupon_code'] ?? ''); ?>' placeholder='e.g. SAVE10'/>
        <p class="description">Enter a coupon code to append or display near affiliate links.</p>
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Booster Pro</h2>
            <?php
            settings_fields('affbp_settings_group');
            do_settings_sections('affbp');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function convert_links_and_insert_coupons($content) {
        $options = get_option($this->option_name);
        $affiliate_prefix = isset($options['affiliate_prefix']) ? trim($options['affiliate_prefix']) : '';
        $coupon_code = isset($options['coupon_code']) ? trim($options['coupon_code']) : '';

        if (empty($affiliate_prefix)) return $content;

        // Use DOMDocument to parse content safely
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            // Skip if already contains affiliate param
            if (strpos($href, $affiliate_prefix) !== false) continue;
            // Append affiliate ID to supported URLs only
            if (preg_match('/https?:\/\/([\w.-]+)\/(.+)/', $href)) {
                $delimiter = (strpos($href, '?') !== false) ? '&' : '?';
                $new_href = $href . $delimiter . $affiliate_prefix;
                // Modify link href
                $link->setAttribute('href', $new_href);
                // Add tracking onclick
                $link->setAttribute('onclick', 'affbpTrackClick(this.href);');

                // Insert coupon badge if coupon_code set
                if (!empty($coupon_code)) {
                    $coupon_span = $dom->createElement('span', ' (Use Coupon: ' . htmlspecialchars($coupon_code) . ')');
                    $coupon_span->setAttribute('style', 'color:#D35400; font-weight:bold;');
                    if ($link->nextSibling) {
                        $link->parentNode->insertBefore($coupon_span, $link->nextSibling);
                    } else {
                        $link->parentNode->appendChild($coupon_span);
                    }
                }
            }
        }

        return $this->save_dom_inner_html($dom);
    }

    private function save_dom_inner_html($dom) {
        $body = $dom->getElementsByTagName('body')->item(0);
        $html = '';
        foreach ($body->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }
        return $html;
    }

    public function track_click() {
        if (isset($_POST['url'])) {
            $url = esc_url_raw($_POST['url']);
            $count = (int) get_option('affbp_click_count_' . md5($url), 0);
            update_option('affbp_click_count_' . md5($url), $count + 1);
            wp_send_json_success(['message' => 'Click recorded']);
        } else {
            wp_send_json_error(['message' => 'Invalid request']);
        }
    }
}

new AffiliateBoosterPro();

// Inline JS for click tracking (for illustration, enqueue in real)
add_action('wp_footer', function() {
    ?>
    <script type="text/javascript">
    function affbpTrackClick(url) {
        if (!url) return;
        jQuery.post(ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'affbp_track_click',
            url: url
        });
    }
    </script>
    <?php
});