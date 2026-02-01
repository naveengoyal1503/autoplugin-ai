/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages using keyword matching to maximize affiliate earnings.
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
    private $is_pro = false;

    public function __construct() {
        $this->affiliate_id = get_option('saa_affiliate_id', '');
        $this->keywords = get_option('saa_keywords', array());
        $this->is_pro = get_option('saa_is_pro', false);

        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('the_content', array($this, 'insert_affiliate_links'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_saa_upgrade_pro', array($this, 'handle_upgrade'));
    }

    public function init() {
        if (empty($this->affiliate_id)) {
            add_action('admin_notices', array($this, 'affiliate_id_notice'));
        }
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate AutoInserter', 'AffiliateInserter', 'manage_options', 'smart-affiliate-autoinserter', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('saa_settings', 'saa_affiliate_id');
        register_setting('saa_settings', 'saa_keywords');
        register_setting('saa_settings', 'saa_is_pro');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate ID</th>
                        <td><input type="text" name="saa_affiliate_id" value="<?php echo esc_attr($this->affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Keywords (JSON array, e.g. ["laptop","phone"])</th>
                        <td><textarea name="saa_keywords" rows="5" cols="50"><?php echo esc_textarea(json_encode($this->keywords)); ?></textarea><br>
                        <small>Free version: Manual keywords. Pro: AI auto-detection.</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if (!$this->is_pro): ?>
            <div style="background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ffeaa7;">
                <h3>Upgrade to Pro for AI Auto-Matching & Analytics!</h3>
                <p>Get unlimited keywords, link performance tracking, and cloaking. <a href="#" id="saa-upgrade-btn" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none;">Upgrade Now - $29/year</a></p>
            </div>
            <?php endif; ?>
        </div>
        <script>
        jQuery('#saa-upgrade-btn').click(function(e) {
            e.preventDefault();
            jQuery.post(ajaxurl, {action: 'saa_upgrade_pro'}, function() {
                location.reload();
            });
        });
        </script>
        <?php
    }

    public function affiliate_id_notice() {
        echo '<div class="notice notice-warning"><p>Smart Affiliate AutoInserter: Please set your Amazon Affiliate ID in <a href="' . admin_url('options-general.php?page=smart-affiliate-autoinserter') . '">Settings</a>.</p></div>';
    }

    public function insert_affiliate_links($content) {
        if (empty($this->affiliate_id) || empty($this->keywords) || is_admin()) {
            return $content;
        }

        foreach ($this->keywords as $keyword => $product_id) {
            $link = $this->generate_amazon_link($product_id, $keyword);
            $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<a href="' . $link . '" target="_blank" rel="nofollow">$0</a>', $content, 1);
        }
        return $content;
    }

    private function generate_amazon_link($product_id, $keyword) {
        return 'https://www.amazon.com/dp/' . $product_id . '?tag=' . $this->affiliate_id;
    }

    public function handle_upgrade() {
        if (current_user_can('manage_options')) {
            update_option('saa_is_pro', true);
            wp_die('Pro activated!');
        }
    }
}

new SmartAffiliateAutoInserter();