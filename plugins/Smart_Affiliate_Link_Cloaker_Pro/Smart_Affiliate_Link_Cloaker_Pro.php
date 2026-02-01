/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with click analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SALC_VERSION', '1.0.0');
define('SALC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SALC_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Free features flag
$premium_key = get_option('salc_premium_key', '');
$is_premium = !empty($premium_key) && strlen($premium_key) > 10;

class SmartAffiliateLinkCloaker {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_salc_track_click', array($this, 'track_click'));
        add_shortcode('salc_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Auto-cloak affiliate links (free feature)
        if (get_option('salc_auto_cloak', 1)) {
            add_filter('the_content', array($this, 'auto_cloak_links'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('salc-js', SALC_PLUGIN_URL . 'salc.js', array('jquery'), SALC_VERSION, true);
        wp_localize_script('salc-js', 'salc_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function auto_cloak_links($content) {
        $patterns = array(
            '/https?:\/\/(?:www\.)?(amazon|clickbank|shareasale|cj\.com)\/[^\s<>"\']+/i',
            '/\b(?:aff|ref|tag)=[a-z0-9]+/i'
        );
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            foreach ($matches as $match) {
                $shortcode = $this->generate_shortcode($match);
                $content = str_replace($match, $shortcode, $content);
            }
        }
        return $content;
    }

    public function generate_shortcode($url) {
        $id = uniqid('salc_');
        $hash = md5($url);
        return '[salc_link id="' . $id . '" url="' . urlencode($url) . '" hash="' . $hash . '"]';
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('id' => '', 'url' => '', 'hash' => ''), $atts);
        $url = urldecode($atts['url']);
        $link_text = get_option('salc_link_text', 'Click Here');
        ob_start();
        ?>
        <a href="#" class="salc-link" data-url="<?php echo esc_url($url); ?>" data-id="<?php echo esc_attr($atts['id']); ?>" data-hash="<?php echo esc_attr($atts['hash']); ?>"><?php echo esc_html($link_text); ?></a>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'salc_nonce')) {
            wp_die('Security check failed');
        }
        $id = sanitize_text_field($_POST['id']);
        $url = esc_url_raw($_POST['url']);
        $hash = sanitize_text_field($_POST['hash']);
        
        // Log click (free feature)
        $clicks = get_option('salc_clicks', array());
        $clicks[$id] = isset($clicks[$id]) ? $clicks[$id] + 1 : 1;
        update_option('salc_clicks', $clicks);
        
        // Premium: A/B testing
        if ($is_premium && get_option('salc_ab_testing', 0)) {
            $variants = get_option('salc_ab_variants', array($url));
            $variant_index = rand(0, count($variants) - 1);
            $url = $variants[$variant_index];
        }
        
        wp_redirect($url);
        exit;
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', 'salc-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('salc_auto_cloak', isset($_POST['auto_cloak']) ? 1 : 0);
            update_option('salc_link_text', sanitize_text_field($_POST['link_text']));
            if (isset($_POST['premium_key'])) {
                update_option('salc_premium_key', sanitize_text_field($_POST['premium_key']));
            }
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $is_premium_local = $is_premium;
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Cloaker Settings</h1>
            <form method="post">
                <?php wp_nonce_field('salc_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Auto-cloak affiliate links</th>
                        <td><input type="checkbox" name="auto_cloak" value="1" <?php checked(get_option('salc_auto_cloak', 1)); ?>></td>
                    </tr>
                    <tr>
                        <th>Link text</th>
                        <td><input type="text" name="link_text" value="<?php echo esc_attr(get_option('salc_link_text', 'Click Here')); ?>" class="regular-text"></td>
                    </tr>
                    <?php if (!$is_premium_local) : ?>
                    <tr>
                        <th>Enter Premium Key</th>
                        <td><input type="text" name="premium_key" placeholder="Unlock premium features" class="regular-text"><br>
                        <em>Upgrade to premium for A/B testing, analytics dashboard, and more!</em></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($is_premium_local) : ?>
                    <tr>
                        <th>A/B Testing</th>
                        <td><input type="checkbox" name="ab_testing" value="1" <?php checked(get_option('salc_ab_testing', 0)); ?>></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Click Stats (<?php echo count(get_option('salc_clicks', array())); ?> links tracked)</h2>
            <ul>
                <?php foreach (get_option('salc_clicks', array()) as $id => $count) : ?>
                <li><?php echo esc_html($id); ?>: <?php echo (int)$count; ?> clicks</li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    public function activate() {
        add_option('salc_auto_cloak', 1);
        add_option('salc_link_text', 'Click Here');
    }
}

new SmartAffiliateLinkCloaker();

// Inline JS for simplicity (self-contained)
function salc_inline_js() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.salc-link').on('click', function(e) {
            e.preventDefault();
            var $this = $(this);
            var data = {
                action: 'salc_track_click',
                id: $this.data('id'),
                url: $this.data('url'),
                hash: $this.data('hash'),
                nonce: '<?php echo wp_create_nonce('salc_nonce'); ?>'
            };
            $.post(salc_ajax.ajaxurl, data, function() {
                window.location = $this.data('url');
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'salc_inline_js');

?>