/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateBoost_Pro.php
*/
<?php
/**
 * Plugin Name: AffiliateBoost Pro
 * Plugin URI: https://example.com
 * Description: Dynamically insert and manage affiliate links and coupons with analytics and conversion optimization.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class AffiliateBoostPro {
    private static $instance = null;
    private $option_name = 'affiliateboost_options';

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new AffiliateBoostPro();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
        add_filter('the_content', array($this, 'inject_affiliate_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_affiliateboost_track_click', array($this, 'track_click'));
    }

    public function enqueue_scripts() {
        if (is_singular()) {
            wp_enqueue_script('affiliateboost-js', plugin_dir_url(__FILE__) . 'affiliateboost.js', array('jquery'), '1.0', true);
            wp_localize_script('affiliateboost-js', 'AffiliateBoostAjax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('affiliateboost_nonce')
            ));
        }
    }

    public function register_shortcodes() {
        add_shortcode('affiliatelink', array($this, 'affiliate_link_shortcode'));
        add_shortcode('affiliatecoupon', array($this, 'affiliate_coupon_shortcode'));
    }

    public function affiliate_link_shortcode($atts, $content = null) {
        $a = shortcode_atts(array(
            'url' => '',
            'id' => '',
            'text' => '',
        ), $atts);

        if (empty($a['url']) && !empty($a['id'])) {
            $links = get_option($this->option_name, array());
            if (isset($links['links'][$a['id']])) {
                $a['url'] = esc_url($links['links'][$a['id']]['url']);
            }
        }

        if (!$a['url']) return $content;
        $link_text = $a['text'] ? esc_html($a['text']) : ($content ? do_shortcode($content) : $a['url']);
        return '<a href="' . $a['url'] . '" class="affiliateboost-link" data-url="' . esc_url($a['url']) . '" target="_blank" rel="nofollow noopener noreferrer">' . $link_text . '</a>';
    }

    public function affiliate_coupon_shortcode($atts) {
        $a = shortcode_atts(array('code' => '', 'discount' => '', 'url' => ''), $atts);
        $code = sanitize_text_field($a['code']);
        $discount = sanitize_text_field($a['discount']);
        $url = esc_url($a['url']);

        $html = '<div class="affiliateboost-coupon">';
        if ($url) {
            $html .= '<a href="' . $url . '" class="affiliateboost-coupon-link" target="_blank" rel="nofollow noopener noreferrer">';
            $html .= '<strong>Use Code: ' . esc_html($code) . '</strong> '; 
            if ($discount) {
                $html .= '(' . esc_html($discount) . ' OFF)';
            }
            $html .= '</a>';
        } else {
            $html .= '<strong>Coupon Code: ' . esc_html($code) . '</strong>'; 
            if ($discount) {
                $html .= ' (' . esc_html($discount) . ' OFF)';
            }
        }
        $html .= '</div>';
        return $html;
    }

    public function inject_affiliate_links($content) {
        $links = get_option($this->option_name, array());
        if (empty($links['keywords'])) return $content;

        foreach ($links['keywords'] as $keyword => $link) {
            if (stripos($content, $keyword) !== false) {
                $escaped_link = esc_url($link['url']);
                $replacement = '<a href="' . $escaped_link . '" class="affiliateboost-link" target="_blank" rel="nofollow noopener noreferrer">' . esc_html($keyword) . '</a>';
                $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
                $content = preg_replace($pattern, $replacement, $content, 1);
            }
        }
        return $content;
    }

    public function admin_menu() {
        add_options_page('AffiliateBoost Pro Settings', 'AffiliateBoost Pro', 'manage_options', 'affiliateboost-pro', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('affiliateboost_options_group', $this->option_name);
        add_settings_section('affiliateboost_main_section', 'Main Settings', null, 'affiliateboost-pro');
        add_settings_field('affiliate_links', 'Affiliate Links & Keywords', array($this, 'affiliate_links_field_html'), 'affiliateboost-pro', 'affiliateboost_main_section');
    }

    public function affiliate_links_field_html() {
        $options = get_option($this->option_name, array());
        $links = isset($options['links']) ? $options['links'] : array();
        $keywords = isset($options['keywords']) ? $options['keywords'] : array();
        ?>
        <p>Manage your affiliate links (ID, URL) and keywords that will be replaced automatically in post content.</p>
        <table style="width:100%; max-width:600px;">
            <thead>
                <tr><th>ID</th><th>URL</th><th>Keyword</th></tr>
            </thead>
            <tbody id="affiliateboost-links-body">
            <?php
            if ($links) :
                foreach ($links as $id => $info):
                    $keyword = isset($keywords) && is_array($keywords) ? array_search($info['url'], array_column($keywords, null, 'url')) : '';
                    ?>
                    <tr>
                        <td><input type="text" name="affiliateboost_options[links][<?php echo esc_attr($id); ?>][id]" value="<?php echo esc_attr($id); ?>" readonly style="width:100px;" /></td>
                        <td><input type="url" name="affiliateboost_options[links][<?php echo esc_attr($id); ?>][url]" value="<?php echo esc_url($info['url']); ?>" style="width:100%;" /></td>
                        <td><input type="text" name="affiliateboost_options[keywords][<?php echo esc_attr($id); ?>]" value="<?php echo esc_attr($keywords[$id] ?? ''); ?>" style="width:100%;" /></td>
                    </tr>
                <?php endforeach;
            else: ?>
                <tr><td colspan="3">No affiliate links added yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <p><em>Example: Add ID 'amazon1' with URL, and associate keyword 'Amazon Kindle' to auto-link that keyword.</em></p>
        <?php
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AffiliateBoost Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliateboost_options_group');
                do_settings_sections('affiliateboost-pro');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function track_click() {
        check_ajax_referer('affiliateboost_nonce', 'nonce');
        if (!empty($_POST['url'])) {
            $url = sanitize_text_field($_POST['url']);
            $count_key = '_affiliateboost_click_count_' . md5($url);
            $count = (int)get_option($count_key, 0);
            update_option($count_key, $count + 1);
            wp_send_json_success(array('count' => $count + 1));
        }
        wp_send_json_error('Missing URL');
    }
}

AffiliateBoostPro::instance();
