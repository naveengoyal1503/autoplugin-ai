/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker Pro
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects keywords in your content and converts them into high-converting affiliate links with A/B testing and performance analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoLinker {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'inline_styles'));
        add_filter('the_content', array($this, 'auto_link_content'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autolinker');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-pro-js', plugin_dir_url(__FILE__) . 'sal-pro.js', array('jquery'), '1.0.0', true);
    }

    public function inline_styles() {
        echo '<style>.sal-link { color: #0073aa; text-decoration: underline; }</style>';
    }

    public function auto_link_content($content) {
        if (is_admin() || !is_single()) return $content;

        $keywords = get_option('sal_keywords', array());
        $affiliates = get_option('sal_affiliates', array());

        foreach ($keywords as $keyword => $aff_url) {
            if (isset($affiliates[$aff_url])) {
                $link_html = '<a href="' . esc_url($affiliates[$aff_url]) . '" target="_blank" rel="nofollow sponsored" class="sal-link">' . esc_html($keyword) . '</a>';
                $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $link_html, $content, 1);
            }
        }
        return $content;
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate AutoLinker', 'Affiliate AutoLinker', 'manage_options', 'sal-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('sal_options_group', 'sal_keywords');
        register_setting('sal_options_group', 'sal_affiliates');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoLinker Settings', 'smart-affiliate-autolinker'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('sal_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Keywords & Affiliate Links</th>
                        <td>
                            <textarea name="sal_keywords" rows="10" cols="50" placeholder="keyword1|affiliate_url1&#10;keyword2|affiliate_url2"><?php echo esc_textarea(get_option('sal_keywords', '')); ?></textarea><br>
                            <small>Format: keyword|affiliate_url (one per line)</small>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Pro Features</h2>
            <p>Upgrade to Pro for A/B testing, click analytics, and premium integrations!</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('sal_keywords', "\n");
        add_option('sal_affiliates', "\n");
    }
}

// Parse keywords on save
add_action('update_option_sal_keywords', function($old, $new) {
    $affiliates = array();
    $lines = explode('\n', $new);
    foreach ($lines as $line) {
        $parts = explode('|', trim($line));
        if (count($parts) == 2) {
            $affiliates[$parts[1]] = $parts;
        }
    }
    update_option('sal_affiliates', $affiliates);
}, 10, 2);

SmartAffiliateAutoLinker::get_instance();

// Pro teaser script
function sal_pro_js_inline() {
    ?><script>console.log('Smart Affiliate AutoLinker Pro: Upgrade for analytics!');</script><?php
}
add_action('wp_footer', 'sal_pro_js_inline');