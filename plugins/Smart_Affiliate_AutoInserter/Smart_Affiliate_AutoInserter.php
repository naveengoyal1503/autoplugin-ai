/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress posts and pages to boost revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array(
            'enabled' => true,
            'affiliates' => array(
                array('keyword' => 'wordpress', 'link' => 'https://example.com/aff/wp', 'text' => 'Best WordPress hosting'),
                array('keyword' => 'plugin', 'link' => 'https://example.com/aff/plugin', 'text' => 'Top plugins')
            ),
            'max_links' => 3
        ));
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('smart-affiliate', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (!$this->options['enabled'] || is_admin() || !is_single()) return $content;

        $words = explode(' ', $content);
        $inserted = 0;
        $max = intval($this->options['max_links']);

        foreach ($words as $index => &$word) {
            if ($inserted >= $max) break;

            foreach ($this->options['affiliates'] as $aff) {
                if (stripos($word, $aff['keyword']) !== false) {
                    $link = '<a href="' . esc_url($aff['link']) . '" target="_blank" rel="nofollow sponsored">' . esc_html($aff['text']) . '</a>';
                    $word = str_replace($aff['keyword'], $link, $word);
                    $inserted++;
                    break;
                }
            }
        }

        return implode(' ', $words);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate Inserter', 'manage_options', 'smart-affiliate', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('smart_affiliate_options', 'smart_affiliate_options');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_options'); ?>
                <?php do_settings_sections('smart_affiliate_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="smart_affiliate_options[enabled]" value="1" <?php checked($this->options['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="smart_affiliate_options[max_links]" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Rules</th>
                        <td>
                            <table id="affiliates-table" class="wp-list-table widefat striped">
                                <thead><tr><th>Keyword</th><th>Link</th><th>Display Text</th><th>Action</th></tr></thead>
                                <tbody>
                                <?php foreach ($this->options['affiliates'] as $i => $aff): ?>
                                    <tr>
                                        <td><input type="text" name="smart_affiliate_options[affiliates][<?php echo $i; ?>][keyword]" value="<?php echo esc_attr($aff['keyword']); ?>" /></td>
                                        <td><input type="url" name="smart_affiliate_options[affiliates][<?php echo $i; ?>][link]" value="<?php echo esc_attr($aff['link']); ?>" /></td>
                                        <td><input type="text" name="smart_affiliate_options[affiliates][<?php echo $i; ?>][text]" value="<?php echo esc_attr($aff['text']); ?>" /></td>
                                        <td><button type="button" class="button remove-row">Remove</button></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="button" id="add-row" class="button">Add Rule</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Premium:</strong> Unlock advanced keyword matching, click tracking, A/B testing, and priority support for $49/year. <a href="https://example.com/premium" target="_blank">Get Premium</a></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var i = <?php echo count($this->options['affiliates']); ?>;
            $('#add-row').click(function() {
                var row = '<tr><td><input type="text" name="smart_affiliate_options[affiliates][" + i + "][keyword]" /></td><td><input type="url" name="smart_affiliate_options[affiliates][" + i + "][link]" /></td><td><input type="text" name="smart_affiliate_options[affiliates][" + i + "][text]" /></td><td><button type="button" class="button remove-row">Remove</button></td></tr>';
                $('#affiliates-table tbody').append(row);
                i++;
            });
            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }

    public function activate() {
        if (!get_option('smart_affiliate_options')) {
            update_option('smart_affiliate_options', $this->options);
        }
    }

    public function deactivate() {}
}

new SmartAffiliateAutoInserter();