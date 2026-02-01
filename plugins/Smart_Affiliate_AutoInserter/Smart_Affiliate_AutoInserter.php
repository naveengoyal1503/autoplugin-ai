/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your WordPress posts and pages to boost commissions effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $affiliate_id;
    private $keywords;

    public function __construct() {
        $this->affiliate_id = get_option('saa_affiliate_id', '');
        $this->keywords = get_option('saa_keywords', array());
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (!$this->affiliate_id) {
            add_action('admin_notices', array($this, 'affiliate_id_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saa-script', plugin_dir_url(__FILE__) . 'saa-script.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (!is_single() || empty($this->affiliate_id) || empty($this->keywords)) {
            return $content;
        }

        global $post;
        $post_content = $post->post_content;

        foreach ($this->keywords as $keyword => $product_url) {
            $link = '<a href="' . esc_url($product_url . '?tag=' . $this->affiliate_id) . '" target="_blank" rel="nofollow sponsored">' . esc_html($keyword) . '</a>';
            $regex = '/\b' . preg_quote($keyword, '/') . '\b/ui';
            if (preg_match_all($regex, $post_content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches as $match) {
                    $offset = $match[1];
                    $length = strlen($match);
                    $content = substr_replace($content, $link, $offset, $length);
                    // Adjust offset for next replacement
                    $offset += strlen($link) - $length;
                }
            }
        }
        return $content;
    }

    public function affiliate_id_notice() {
        echo '<div class="notice notice-warning"><p><strong>Smart Affiliate AutoInserter:</strong> Please set your Amazon Affiliate ID in <a href="' . admin_url('options-general.php?page=saa-settings') . '">Settings</a>.</p></div>';
    }

    public function add_admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate AutoInserter', 'manage_options', 'saa-settings', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('saa_plugin', 'saa_affiliate_id');
        register_setting('saa_plugin', 'saa_keywords');
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_plugin'); ?>
                <?php do_settings_sections('saa_plugin'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Amazon Affiliate ID (Tag)</th>
                        <td><input type="text" name="saa_affiliate_id" value="<?php echo esc_attr(get_option('saa_affiliate_id')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Keywords & Product Links</th>
                        <td>
                            <p>Enter keywords (one per line) followed by | and Amazon product URL.</p>
                            <textarea name="saa_keywords" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(implode("\n", get_option('saa_keywords', array()))); ?></textarea>
                            <p class="description">Example:<br>best laptop|https://amazon.com/dp/B08N5WRWNW<br>coffee maker|https://amazon.com/dp/B07H585Q71</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Pro Upgrade</h2>
            <p>Unlock AI suggestions, analytics, and more for $29/year. <a href="https://example.com/pro" target="_blank">Learn More</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('saa_keywords', array());
    }
}

new SmartAffiliateAutoInserter();

// Simple JS for preview
function saa_preview_links() {
    jQuery(document).ready(function($) {
        $('#saa_keywords').on('input', function() {
            // Preview logic here if needed
        });
    });
}
?>