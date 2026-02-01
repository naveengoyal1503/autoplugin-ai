/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your content using keyword matching and basic AI-like analysis.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $api_key;
    private $affiliate_id;

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->api_key = get_option('saai_api_key', '');
        $this->affiliate_id = get_option('saai_affiliate_id', '');
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        } else {
            add_filter('the_content', array($this, 'insert_affiliate_links'));
        }
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate AutoInserter', 'Affiliate Inserter', 'manage_options', 'smart-affiliate-autoinserter', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('saai_settings', 'saai_api_key');
        register_setting('saai_settings', 'saai_affiliate_id');
        register_setting('saai_settings', 'saai_keywords');
        register_setting('saai_settings', 'saai_max_links');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saai_settings'); ?>
                <?php do_settings_sections('saai_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate ID (tag)</th>
                        <td><input type="text" name="saai_affiliate_id" value="<?php echo esc_attr(get_option('saai_affiliate_id')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Keywords & Products (JSON: {"keyword":"asin"})</th>
                        <td><textarea name="saai_keywords" rows="10" cols="50"><?php echo esc_textarea(get_option('saai_keywords', '{"phone":"B08N5WRWNW","laptop":"B09G9FPGT6","headphones":"B07ZPC9QD4"}')); ?></textarea><br><small>Example: {"coffee":"B07H484SNZ"}</small></td>
                    </tr>
                    <tr>
                        <th>Max links per post</th>
                        <td><input type="number" name="saai_max_links" value="<?php echo esc_attr(get_option('saai_max_links', 3)); ?>" min="1" max="10" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Advanced AI product matching, analytics, unlimited keywords. <a href="#">Get Pro</a></p>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (empty($this->affiliate_id) || !is_single()) {
            return $content;
        }

        $keywords = json_decode(get_option('saai_keywords', '{}'), true);
        if (empty($keywords)) {
            return $content;
        }

        $max_links = intval(get_option('saai_max_links', 3));
        $inserted = 0;

        foreach ($keywords as $keyword => $asin) {
            if ($inserted >= $max_links) break;

            $link = $this->create_amazon_link($asin);
            $content = preg_replace(
                '|\b' . preg_quote($keyword, '|') . '\b|i',
                '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">$0</a><sup>*</sup>',
                $content,
                1,
                $count
            );
            $inserted += $count;
        }

        return $content;
    }

    private function create_amazon_link($asin) {
        return "https://www.amazon.com/dp/" . $asin . "?tag=" . $this->affiliate_id;
    }

    public function activate() {
        if (!get_option('saai_max_links')) {
            update_option('saai_max_links', 3);
        }
    }

    public function deactivate() {}
}

new SmartAffiliateAutoInserter();

// Pro nag
add_action('admin_notices', function() {
    if (!get_option('saai_pro') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Smart Affiliate AutoInserter Pro</strong>: AI matching, analytics & more! <a href="https://example.com/pro">Upgrade now</a></p></div>';
    }
});