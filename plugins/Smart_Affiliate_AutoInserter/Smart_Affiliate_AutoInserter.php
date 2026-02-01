/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into posts using keyword matching to maximize commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_settings', array());
    }

    public function load_textdomain() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_menu() {
        add_options_page(
            __('Smart Affiliate Settings', 'smart-affiliate-autoinserter'),
            __('Affiliate Inserter', 'smart-affiliate-autoinserter'),
            'manage_options',
            'smart-affiliate',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_settings_group', 'smart_affiliate_settings', array($this, 'sanitize_settings'));
    }

    public function sanitize_settings($input) {
        $sanitized = array();
        $sanitized['keywords'] = isset($input['keywords']) ? sanitize_textarea_field($input['keywords']) : '';
        $sanitized['affiliate_links'] = isset($input['affiliate_links']) ? sanitize_textarea_field($input['affiliate_links']) : '';
        $sanitized['max_links'] = isset($input['max_links']) ? absint($input['max_links']) : 3;
        $sanitized['enabled'] = isset($input['enabled']) ? 1 : 0;
        $sanitized['pro'] = isset($input['pro']) ? 1 : 0;
        return $sanitized;
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_settings_group'); ?>
                <?php do_settings_sections('smart_affiliate_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable Auto-Insertion', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <input type="checkbox" name="smart_affiliate_settings[enabled]" value="1" <?php checked($this->options['enabled']); ?>>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Keywords (one per line)', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <textarea name="smart_affiliate_settings[keywords]" rows="10" cols="50" class="large-text" placeholder="keyword1&#10;keyword2&#10;best product"><?php echo esc_textarea($this->options['keywords']); ?></textarea>
                            <p class="description"><?php _e('Enter keywords to match in content.', 'smart-affiliate-autoinserter'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Affiliate Links (keyword=url, one per line)', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <textarea name="smart_affiliate_settings[affiliate_links]" rows="10" cols="50" class="large-text" placeholder="keyword1=https://affiliate-link1.com&#10;keyword2=https://affiliate-link2.com"><?php echo esc_textarea($this->options['affiliate_links']); ?></textarea>
                            <p class="description"><?php _e('Format: keyword=affiliate_url', 'smart-affiliate-autoinserter'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Max Links per Post', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <input type="number" name="smart_affiliate_settings[max_links]" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
                <p><?php _e('<strong>Pro Version:</strong> Unlock unlimited keywords, analytics, A/B testing, and more! <a href="#" onclick="alert(&#39;Upgrade to Pro for $49/year&#39;)">Upgrade Now</a>', 'smart-affiliate-autoinserter'); ?></p>
            </form>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (!isset($this->options['enabled']) || !$this->options['enabled']) {
            return $content;
        }

        if (is_admin() || is_feed()) {
            return $content;
        }

        $keywords = explode("\n", trim($this->options['keywords']));
        $links_raw = explode("\n", trim($this->options['affiliate_links']));
        $affiliate_links = array();
        foreach ($links_raw as $line) {
            if (strpos($line, '=') !== false) {
                list($kw, $url) = explode('=', $line, 2);
                $affiliate_links[trim($kw)] = trim($url);
            }
        }

        $max_links = isset($this->options['max_links']) ? (int)$this->options['max_links'] : 3;
        $inserted = 0;
        $words = explode(' ', $content);

        foreach ($words as $index => &$word) {
            if ($inserted >= $max_links) break;

            foreach ($keywords as $keyword) {
                $keyword = trim($keyword);
                if (stripos($word, $keyword) !== false && isset($affiliate_links[$keyword])) {
                    $link = '<a href="' . esc_url($affiliate_links[$keyword]) . '" rel="nofollow sponsored" target="_blank">' . esc_html($word) . '</a>';
                    $word = $link;
                    $inserted++;
                    break;
                }
            }
        }

        return implode(' ', $words);
    }

    public function plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=smart-affiliate') . '">' . __('Settings', 'smart-affiliate-autoinserter') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

new SmartAffiliateAutoInserter();