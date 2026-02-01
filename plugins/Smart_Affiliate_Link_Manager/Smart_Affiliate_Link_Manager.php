/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate-link-manager
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with A/B testing and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-link-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateLinkManager {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sal_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sal_track_click', array($this, 'track_click'));
        add_shortcode('sal_link', array($this, 'sal_link_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('sal_enabled') !== 'yes') return;

        // Auto-replace affiliate links
        add_filter('the_content', array($this, 'replace_affiliate_links'));
        add_filter('widget_text', array($this, 'replace_affiliate_links'));
    }

    public function enqueue_scripts() {
        if (!get_option('sal_enabled')) return;
        wp_enqueue_script('sal-tracker', plugin_dir_url(__FILE__) . 'sal-tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sal-tracker', 'sal_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sal_nonce')));
    }

    public function replace_affiliate_links($content) {
        if (!get_option('sal_enabled')) return $content;

        $patterns = array(
            '/\bhttps?:\/\/(?:[a-zA-Z0-9-\.]+\.)?(amazon|clickbank|shareasale|commissionjunction|impact|partnerstack)\b[^\s<>"\']*/i',
            '/\b(?:aff|ref|tag)=[a-zA-Z0-9-]+/i'
        );

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            foreach ($matches as $match) {
                $shortcode = '[sal_link url="' . esc_attr($match) . '"]';
                $content = str_replace($match, $shortcode, $content);
            }
        }
        return $content;
    }

    public function sal_link_shortcode($atts) {
        $atts = shortcode_atts(array('url' => ''), $atts);
        if (empty($atts['url'])) return '';

        $id = uniqid('sal_');
        $cloaked = add_query_arg('sal', $id, home_url('/go/'));

        ob_start();
        ?>
        <a href="<?php echo esc_url($cloaked); ?>" class="sal-link" data-sal-original="<?php echo esc_attr($atts['url']); ?>" data-sal-id="<?php echo esc_attr($id); ?>"><?php echo esc_html($atts['url']); ?></a>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('sal_nonce', 'nonce');
        $original = sanitize_url($_POST['original'] ?? '');
        $id = sanitize_text_field($_POST['id'] ?? '');

        // Log click (free version: simple count)
        $clicks = get_option('sal_clicks', array());
        $clicks[$id] = ($clicks[$id] ?? 0) + 1;
        update_option('sal_clicks', $clicks);

        // Premium: advanced analytics
        if (get_option('sal_premium') === 'yes') {
            // Simulate A/B testing redirect
            $variants = explode(',', get_option('sal_ab_variants', $original));
            $redirect = $variants[array_rand($variants)];
        } else {
            $redirect = $original;
        }

        wp_redirect($redirect, 302);
        exit;
    }

    public function activate() {
        add_option('sal_enabled', 'yes');
        add_option('sal_clicks', array());
    }

    public function deactivate() {
        // Do not delete data
    }
}

// Asset files would be separate, but for single-file, inline JS
add_action('wp_footer', function() {
    if (!get_option('sal_enabled')) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sal-link').on('click', function(e) {
            e.preventDefault();
            var $this = $(this);
            var original = $this.data('sal-original');
            var id = $this.data('sal-id');
            $.post(sal_ajax.ajax_url, {
                action: 'sal_track_click',
                nonce: sal_ajax.nonce,
                original: original,
                id: id
            }, function() {
                window.location = $this.attr('href');
            });
        });
    });
    </script>
    <?php
});

SmartAffiliateLinkManager::get_instance();

// Admin settings page
add_action('admin_menu', function() {
    add_options_page('Smart Affiliate Link Manager', 'SAL Manager', 'manage_options', 'sal-manager', 'sal_admin_page');
});

function sal_admin_page() {
    if (isset($_POST['sal_save'])) {
        update_option('sal_enabled', sanitize_text_field($_POST['sal_enabled']));
        update_option('sal_premium', sanitize_text_field($_POST['sal_premium']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }

    $clicks = get_option('sal_clicks', array());
    $total_clicks = array_sum($clicks);
    ?>
    <div class="wrap">
        <h1>Smart Affiliate Link Manager</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Enable Plugin</th>
                    <td><input type="checkbox" name="sal_enabled" value="yes" <?php checked(get_option('sal_enabled'), 'yes'); ?> /></td>
                </tr>
                <tr>
                    <th>Premium Mode (Simulated)</th>
                    <td><input type="checkbox" name="sal_premium" value="yes" <?php checked(get_option('sal_premium'), 'yes'); ?> /> Unlock A/B testing & analytics</td>
                </tr>
            </table>
            <p><strong>Total Clicks Tracked: <?php echo $total_clicks; ?></strong></p>
            <?php submit_button('Save Settings', 'primary', 'sal_save'); ?>
        </form>
        <h2>Upgrade to Premium</h2>
        <p>Get advanced features like A/B testing, detailed analytics, auto-optimization for $49/year.</p>
    </div>
    <?php
}

?>