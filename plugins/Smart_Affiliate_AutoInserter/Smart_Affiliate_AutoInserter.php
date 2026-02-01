/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your content to boost earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $api_key = '';
    private $affiliates = [];
    private $max_links = 3;

    public function __construct() {
        add_action('init', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_filter('the_content', [$this, 'insert_affiliate_links'], 99);
        add_action('admin_init', [$this, 'admin_init']);
    }

    public function init() {
        $this->api_key = get_option('saai_api_key', '');
        $this->affiliates = get_option('saai_affiliates', []);
        $this->max_links = get_option('saai_max_links', 3);

        if (empty($this->affiliates)) {
            $this->affiliates = [
                'amazon' => ['keyword' => 'amazon', 'url' => 'https://amazon.com/', 'id' => 'amazon'],
                'clickbank' => ['keyword' => 'clickbank', 'url' => 'https://clickbank.com/', 'id' => 'clickbank']
            ];
            update_option('saai_affiliates', $this->affiliates);
        }
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('saai-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate Inserter', 'manage_options', 'saai-settings', [$this, 'settings_page']);
    }

    public function admin_init() {
        register_setting('saai_settings', 'saai_api_key');
        register_setting('saai_settings', 'saai_affiliates');
        register_setting('saai_settings', 'saai_max_links');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saai_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>API Key (Premium)</th>
                        <td><input type="text" name="saai_api_key" value="<?php echo esc_attr(get_option('saai_api_key')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON)</th>
                        <td><textarea name="saai_affiliates" rows="10" cols="50"><?php echo esc_textarea(json_encode(get_option('saai_affiliates', []))); ?></textarea><br><small>Enter JSON array of affiliates: {"keyword":"url"}</small></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post (Free: max 3)</th>
                        <td><input type="number" name="saai_max_links" value="<?php echo esc_attr(get_option('saai_max_links', 3)); ?>" max="10" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Premium:</strong> Unlimited links, AI keyword matching, analytics. <a href="https://example.com/premium">Get Premium</a></p>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || empty($this->affiliates)) return $content;

        $words = explode(' ', $content);
        $inserted = 0;
        $new_content = [];

        foreach ($words as $word) {
            $new_content[] = $word;
            if ($inserted >= $this->max_links) continue;

            foreach ($this->affiliates as $aff) {
                if (stripos($word, $aff['keyword']) !== false) {
                    $link = '<a href="' . esc_url($aff['url']) . '" target="_blank" rel="nofollow sponsored">' . $word . '</a> ';
                    $new_content[count($new_content) - 1] = $link;
                    $inserted++;
                    break;
                }
            }
        }

        return implode(' ', $new_content);
    }
}

new SmartAffiliateAutoInserter();

// Premium teaser
add_action('admin_notices', function() {
    if (!get_option('saai_api_key')) {
        echo '<div class="notice notice-info"><p>Unlock unlimited features with <a href="options-general.php?page=saai-settings">Smart Affiliate Premium</a>!</p></div>';
    }
});