/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages using keyword matching to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $affiliate_id = '';
    private $keywords = [];
    private $products = [];

    public function __construct() {
        add_action('init', [$this, 'init});
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_filter('the_content', [$this, 'insert_affiliate_links'], 99);
        add_action('wp_ajax_save_settings', [$this, 'save_settings']);
    }

    public function init() {
        $this->affiliate_id = get_option('saa_affiliate_id', '');
        $this->keywords = get_option('saa_keywords', ['laptop', 'phone', 'book', 'camera']);
        $this->products = get_option('saa_products', [
            'laptop' => 'https://amazon.com/dp/B08N5WRWNW?tag=YOURAFFID',
            'phone' => 'https://amazon.com/dp/B0BHHV8N8Z?tag=YOURAFFID',
            'book' => 'https://amazon.com/dp/0596008273?tag=YOURAFFID',
            'camera' => 'https://amazon.com/dp/B08G9J44JW?tag=YOURAFFID'
        ]);
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate',
            [$this, 'options_page']
        );
    }

    public function settings_init() {
        register_setting('saa_plugin', 'saa_affiliate_id');
        register_setting('saa_plugin', 'saa_keywords');
        register_setting('saa_plugin', 'saa_products');
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('saa_plugin');
                do_settings_sections('saa_plugin');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Amazon Affiliate ID</th>
                        <td><input type="text" name="saa_affiliate_id" value="<?php echo esc_attr(get_option('saa_affiliate_id')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Keywords (comma-separated)</th>
                        <td><input type="text" name="saa_keywords" value="<?php echo esc_attr(implode(',', get_option('saa_keywords', []))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Products JSON (keyword: url)</th>
                        <td><textarea name="saa_products" rows="10" cols="50"><?php echo esc_textarea(wp_json_encode(get_option('saa_products', []))); ?></textarea><br><small>Example: {"laptop":"https://amazon.com/dp/B08N5WRWNW?tag=YOURID"}</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if (get_option('saa_affiliate_id')): ?>
            <h2>Pro Upgrade</h2>
            <p>Unlock AI-powered matching, analytics, and unlimited links for $49/year. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || empty($this->affiliate_id)) {
            return $content;
        }

        global $post;
        if (!$post || in_array($post->post_status, ['auto-draft', 'draft'])) {
            return $content;
        }

        $words = explode(' ', strip_tags($content));
        $inserted = 0;
        $max_inserts = 3; // Free limit

        foreach ($words as $index => &$word) {
            foreach ($this->keywords as $keyword) {
                if (stripos($word, $keyword) !== false && $inserted < $max_inserts) {
                    if (isset($this->products[$keyword])) {
                        $link = '<a href="' . esc_url($this->products[$keyword]) . '" target="_blank" rel="nofollow sponsored">' . esc_html($keyword) . '</a>';
                        $word = str_ireplace($keyword, $link, $word);
                        $inserted++;
                    }
                    break;
                }
            }
        }

        return implode(' ', $words);
    }
}

new SmartAffiliateAutoInserter();