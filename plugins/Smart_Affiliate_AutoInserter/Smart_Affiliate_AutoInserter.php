/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content to boost earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private static $instance = null;
    public $options;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->options = get_option('smart_affiliate_options', array(
            'enabled' => '1',
            'keywords' => "amazon, buy, best, review, top\nclickbank, earn, affiliate",
            'affiliate_links' => "amazon:https://amazon.com/?tag=yourtag\nclickbank:https://hop.clickbank.net/?affiliate=yourid",
            'max_links_per_post' => 3,
            'upgrade_nudge' => '1'
        ));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (!is_single() || !$this->options['enabled'] || is_admin()) {
            return $content;
        }

        $keywords = explode('\n', trim($this->options['keywords']));
        $links_map = array();
        foreach (explode('\n', trim($this->options['affiliate_links'])) as $pair) {
            list($keyword, $url) = explode(':', trim($pair), 2);
            $links_map[trim($keyword)] = trim($url);
        }

        $word_count = str_word_count(strip_tags($content));
        $max_links = min((int)$this->options['max_links_per_post'], max(1, round($word_count / 300)));
        $inserted = 0;

        foreach ($keywords as $keyword) {
            if ($inserted >= $max_links) break;
            $keyword = trim($keyword);
            if (empty($keyword) || !isset($links_map[$keyword])) continue;

            $link_html = '<a href="' . esc_url($links_map[$keyword]) . '" target="_blank" rel="nofollow sponsored" class="smart-affiliate-link">' . esc_html($keyword) . '</a>';
            $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $link_html, $content, 1, $count);
            if ($count > 0) $inserted++;
        }

        // Premium upgrade nudge
        if ($this->options['upgrade_nudge'] && $inserted === 0) {
            $content .= '<p><em>Upgrade to <strong>Smart Affiliate Pro</strong> for AI-powered link insertion and 10x more earnings!</em></p>';
        }

        return $content;
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_options', 'smart_affiliate_options');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_options'); ?>
                <?php do_settings_sections('smart_affiliate_options'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="enabled">Enable Auto-Insertion</label></th>
                        <td><input type="checkbox" id="enabled" name="smart_affiliate_options[enabled]" value="1" <?php checked($this->options['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th>Keywords (one per line)</th>
                        <td><textarea name="smart_affiliate_options[keywords]" rows="5" cols="50"><?php echo esc_textarea($this->options['keywords']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (keyword:url, one per line)</th>
                        <td><textarea name="smart_affiliate_options[affiliate_links]" rows="5" cols="50"><?php echo esc_textarea($this->options['affiliate_links']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="smart_affiliate_options[max_links_per_post]" value="<?php echo esc_attr($this->options['max_links_per_post']); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Show Pro Upgrade Notice</th>
                        <td><input type="checkbox" name="smart_affiliate_options[upgrade_nudge]" value="1" <?php checked($this->options['upgrade_nudge']); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for AI keyword matching, A/B testing, analytics, and premium integrations. <a href="https://example.com/pro" target="_blank">Learn More</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_options', array(
            'enabled' => '1',
            'keywords' => "amazon, buy, best, review\nclickbank, earn",
            'affiliate_links' => "amazon:https://amazon.com/?tag=yourtag\nclickbank:https://hop.clickbank.net/?affiliate=yourid",
            'max_links_per_post' => 3,
            'upgrade_nudge' => '1'
        ));
    }

    public function deactivate() {
        // Nothing to do
    }
}

SmartAffiliateAutoInserter::get_instance();

// Create assets directory placeholder (in real plugin, include actual files)
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets/';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
    file_put_contents($assets_dir . 'script.js', '// Smart Affiliate JS\nconsole.log("Smart Affiliate loaded");');
}
?>