/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages using keyword matching.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $api_key;
    private $affiliate_tag;

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->api_key = get_option('saai_api_key', '');
        $this->affiliate_tag = get_option('saai_affiliate_tag', '');
        if ($this->is_pro()) {
            add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'settings_init'));
        } else {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function activate() {
        add_option('saai_enabled', true);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    private function is_pro() {
        return get_option('saai_pro_activated', false);
    }

    public function insert_affiliate_links($content) {
        if (!is_single() || empty($this->api_key) || empty($this->affiliate_tag)) {
            return $content;
        }

        // Simple keyword-based matching (demo products)
        $products = array(
            'laptop' => 'https://www.amazon.com/dp/B08N5WRWNW?tag=YOURTAG',
            'phone' => 'https://www.amazon.com/dp/B0CWLWMQ68?tag=YOURTAG',
            'book' => 'https://www.amazon.com/dp/0596008279?tag=YOURTAG',
            'coffee' => 'https://www.amazon.com/dp/B07H585Q71?tag=YOURTAG'
        );

        $keywords = array_keys($products);
        foreach ($keywords as $keyword) {
            if (stripos($content, $keyword) !== false && rand(1, 3) === 1) { // 33% chance to insert
                $link = '<p><strong>Recommended:</strong> Check out this <a href="' . $products[$keyword] . '" target="_blank" rel="nofollow">' . ucfirst($keyword) . '</a> on Amazon.</p>';
                $content = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '$1' . $link, $content, 1);
            }
        }

        return $content;
    }

    public function add_admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate AutoInserter', 'manage_options', 'saai', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('saai_plugin', 'saai_api_key');
        register_setting('saai_plugin', 'saai_affiliate_tag');
        register_setting('saai_plugin', 'saai_pro_activated');
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saai_plugin'); ?>
                <?php do_settings_sections('saai_plugin'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="saai_affiliate_tag" value="<?php echo esc_attr(get_option('saai_affiliate_tag')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Amazon API Key (Pro)</th>
                        <td><input type="text" name="saai_api_key" value="<?php echo esc_attr(get_option('saai_api_key')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Version:</strong> Upgrade for real-time product search, A/B testing, and analytics. <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function pro_notice() {
        if (current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate AutoInserter Pro</strong> to enable auto-insertion and settings.</p></div>';
        }
    }
}

new SmartAffiliateAutoInserter();