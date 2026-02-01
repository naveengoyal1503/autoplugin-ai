/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_AutoLink_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate AutoLink Pro
 * Plugin URI: https://example.com/affiliate-autolink-pro
 * Description: Automatically converts keywords in posts to affiliate links. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-autolink-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateAutoLinkPro {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_head', array($this, 'inject_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-autolink-pro');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aalp-script', plugin_dir_url(__FILE__) . 'aalp.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate AutoLink Pro Settings',
            'Affiliate AutoLink',
            'manage_options',
            'aalp-settings',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('aalp_settings', 'aalp_keywords');
        register_setting('aalp_settings', 'aalp_amazon_id');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Affiliate AutoLink Pro Settings', 'affiliate-autolink-pro'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('aalp_settings'); ?>
                <?php do_settings_sections('aalp_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Amazon Affiliate ID', 'affiliate-autolink-pro'); ?></th>
                        <td><input type="text" name="aalp_amazon_id" value="<?php echo esc_attr(get_option('aalp_amazon_id')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><?php _e('Keywords (JSON format: {"keyword":"product_asin"})', 'affiliate-autolink-pro'); ?></th>
                        <td><textarea name="aalp_keywords" rows="10" cols="50"><?php echo esc_textarea(get_option('aalp_keywords')); ?></textarea>
                        <p class="description">Example: {"WordPress":"B08N5WRWNW","Plugin":"B01M4P6RRA"}</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock A/B testing, analytics, and more networks for $49/year. <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function inject_links() {
        if (is_admin()) return;
        $keywords = json_decode(get_option('aalp_keywords', '{}'), true);
        $amazon_id = get_option('aalp_amazon_id', '');
        if (empty($keywords) || empty($amazon_id)) return;

        $script = '<script>var aalp_keywords = ' . json_encode($keywords) . '; var aalp_affid = \'' . esc_js($amazon_id) . '\'; </script>';
        echo $script;
    }

    public function activate() {
        add_option('aalp_keywords', '{}');
    }

    public function deactivate() {}
}

AffiliateAutoLinkPro::get_instance();

// JavaScript for link replacement
function aalp_replace_links() {
    jQuery(document).ready(function($) {
        Object.keys(aalp_keywords).forEach(function(keyword) {
            var asin = aalp_keywords[keyword];
            var link = 'https://amazon.com/dp/' + asin + '?tag=' + aalp_affid;
            $('*').html(function(_, html) {
                return html.replace(new RegExp('\\b' + keyword + '\\b', 'gi'), '<a href="' + link + '" target="_blank" rel="nofollow">$&</a>');
            });
        });
    });
}
?>