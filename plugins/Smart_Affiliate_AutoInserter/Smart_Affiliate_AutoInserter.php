/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content to boost monetization. Freemium model with Pro upgrade.
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_settings', array(
            'api_key' => '',
            'affiliates' => array(
                array('keyword' => 'WordPress', 'link' => 'https://example.com/aff/wp?ref=123', 'max_inserts' => 2)
            ),
            'is_pro' => false,
            'max_links_free' => 3
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (is_admin() || !is_single()) return $content;

        $inserted = 0;
        $max_links = $this->options['is_pro'] ? 999 : $this->options['max_links_free'];

        foreach ($this->options['affiliates'] as $aff) {
            if ($inserted >= $max_links) break;

            $pattern = '/\b' . preg_quote($aff['keyword'], '/') . '\b/i';
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches as $match) {
                    if ($inserted >= $max_links) break;
                    $replacement = '<a href="' . esc_url($aff['link']) . '" target="_blank" rel="nofollow sponsored" class="smart-aff-link">' . $match . '</a>';
                    $content = preg_replace('/\b' . preg_quote($match, '/') . '\b/i', $replacement, $content, 1);
                    $inserted++;
                }
            }
        }

        return $content;
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate Inserter', 'manage_options', 'smart-affiliate', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('smart_affiliate_group', 'smart_affiliate_settings');
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->options = wp_parse_args($_POST['smart_affiliate'], $this->options);
            update_option('smart_affiliate_settings', $this->options);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="">
                <?php settings_fields('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Pro License Key</th>
                        <td><input type="text" name="smart_affiliate[api_key]" value="<?php echo esc_attr($this->options['api_key']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links</th>
                        <td>
                            <div id="affiliates">
                                <?php foreach ($this->options['affiliates'] as $i => $aff): ?>
                                <div class="aff-row">
                                    <input type="text" name="smart_affiliate[affiliates][<?php echo $i; ?>][keyword]" placeholder="Keyword" value="<?php echo esc_attr($aff['keyword']); ?>" />
                                    <input type="url" name="smart_affiliate[affiliates][<?php echo $i; ?>][link]" placeholder="Affiliate Link" value="<?php echo esc_attr($aff['link']); ?>" />
                                    <input type="number" name="smart_affiliate[affiliates][<?php echo $i; ?>][max_inserts]" placeholder="Max Inserts per Post" value="<?php echo esc_attr($aff['max_inserts']); ?>" />
                                    <button type="button" class="button remove-aff">Remove</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-aff" class="button">Add Affiliate</button>
                            <p class="description">Free version limited to <?php echo $this->options['max_links_free']; ?> links per post. <strong>Upgrade to Pro for unlimited!</strong></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <script>
            jQuery(document).ready(function($) {
                $('#add-aff').click(function() {
                    var i = $('#affiliates .aff-row').length;
                    $('#affiliates').append('<div class="aff-row"><input type="text" name="smart_affiliate[affiliates]['+i+'][keyword]" placeholder="Keyword" /><input type="url" name="smart_affiliate[affiliates]['+i+'][link]" placeholder="Affiliate Link" /><input type="number" name="smart_affiliate[affiliates]['+i+'][max_inserts]" placeholder="Max Inserts per Post" /><button type="button" class="button remove-aff">Remove</button></div>');
                });
                $(document).on('click', '.remove-aff', function() {
                    $(this).closest('.aff-row').remove();
                });
            });
            </script>
        </div>
        <?php
    }

    public function activate() {
        if (!get_option('smart_affiliate_settings')) {
            update_option('smart_affiliate_settings', $this->options);
        }
    }
}

new SmartAffiliateAutoInserter();

// Pro upsell notice
function smart_affiliate_notice() {
    if (!current_user_can('manage_options')) return;
    $options = get_option('smart_affiliate_settings');
    if (!$options['is_pro']) {
        echo '<div class="notice notice-info"><p>Unlock <strong>unlimited links, analytics, and AI optimization</strong> with <a href="https://example.com/pro" target="_blank">Smart Affiliate Pro</a> for just $49/year!</p></div>';
    }
}
add_action('admin_notices', 'smart_affiliate_notice');
?>