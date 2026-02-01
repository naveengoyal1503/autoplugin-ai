/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages based on keyword matching.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $api_key;
    private $affiliate_tag;
    private $keywords;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->api_key = get_option('saa_api_key', '');
        $this->affiliate_tag = get_option('saa_affiliate_tag', '');
        $this->keywords = get_option('saa_keywords', array());
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saa-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || empty($this->api_key) || empty($this->affiliate_tag)) {
            return $content;
        }

        global $post;
        if (!$post || in_array($post->post_status, array('draft', 'private'))) {
            return $content;
        }

        foreach ($this->keywords as $keyword => $asin) {
            if (stripos($content, $keyword) !== false && rand(1, 3) === 1) { // Insert ~33% of the time to avoid spam
                $link = $this->get_amazon_link($asin);
                $replacement = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">' . esc_html($keyword) . '</a> ';
                $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $replacement, $content, 1);
            }
        }
        return $content;
    }

    private function get_amazon_link($asin) {
        $url = 'https://www.amazon.com/dp/' . $asin . '?tag=' . $this->affiliate_tag;
        return $url;
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('saa_plugin', 'saa_api_key');
        register_setting('saa_plugin', 'saa_affiliate_tag');
        register_setting('saa_plugin', 'saa_keywords');
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('saa_plugin');
                do_settings_sections('saa_plugin');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Amazon Affiliate Tag</th>
                        <td><input type="text" name="saa_affiliate_tag" value="<?php echo esc_attr($this->affiliate_tag); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Keywords (JSON: {"keyword":"ASIN"})</th>
                        <td><textarea name="saa_keywords" rows="10" cols="50"><?php echo esc_textarea(json_encode($this->keywords)); ?></textarea><br>
                        Example: {"best laptop":"B08N5WRWNW","coffee maker":"B07D2ND99K"}</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function activate() {
        add_option('saa_keywords', json_decode('{"smartphone":"B0C9V5Y2SJ","headphones":"B09X4B2T4F"}', true));
    }
}

new SmartAffiliateAutoInserter();

// Create assets directory placeholder (in real plugin, include files)
// Note: For full deployment, create /assets/script.js with empty object {}