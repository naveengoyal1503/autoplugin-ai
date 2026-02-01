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
 * Author URI: https://example.com
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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_settings', array(
            'amazon_tag' => '',
            'keywords' => array(),
            'enabled' => 1,
            'max_links' => 3,
            'pro' => 0
        ));

        if ($this->options['enabled']) {
            add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
            add_filter('the_excerpt', array($this, 'insert_affiliate_links'), 99);
        }

        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function activate() {
        if (!get_option('smart_affiliate_settings')) {
            add_option('smart_affiliate_settings', array(
                'amazon_tag' => 'your-associate-tag',
                'keywords' => array(
                    array('keyword' => 'laptop', 'link' => 'https://amzn.to/example'),
                    array('keyword' => 'phone', 'link' => 'https://amzn.to/example')
                ),
                'enabled' => 1,
                'max_links' => 3,
                'pro' => 0
            ));
        }
    }

    public function deactivate() {
        // Nothing to do
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || !$this->options['enabled']) {
            return $content;
        }

        global $post;
        if (!$post || in_array($post->post_status, array('draft', 'private'))) {
            return $content;
        }

        $keywords = $this->options['keywords'];
        $max_links = intval($this->options['max_links']);
        $inserted = 0;

        foreach ($keywords as $kw_data) {
            if ($inserted >= $max_links) break;

            $keyword = strtolower($kw_data['keyword']);
            $link = $kw_data['link'];
            $tag = $this->options['amazon_tag'];

            if (strpos(strtolower($content), $keyword) !== false && $tag) {
                $aff_link = $this->build_amazon_link($keyword, $tag, $link);
                $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<a href="$aff_link" target="_blank" rel="nofollow sponsored">$0</a>', $content, 1);
                $inserted++;
            }
        }

        return $content;
    }

    private function build_amazon_link($keyword, $tag, $fallback) {
        $search_url = 'https://www.amazon.com/s?k=' . urlencode($keyword) . '&tag=' . $tag;
        return $fallback ?: $search_url;
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_group', 'smart_affiliate_settings', array($this, 'sanitize_settings'));
    }

    public function sanitize_settings($input) {
        $input['amazon_tag'] = sanitize_text_field($input['amazon_tag']);
        $input['enabled'] = isset($input['enabled']) ? 1 : 0;
        $input['max_links'] = max(1, min(10, intval($input['max_links'])));
        $input['keywords'] = isset($input['keywords']) ? array_map(function($k) {
            return array(
                'keyword' => sanitize_text_field($k['keyword']),
                'link' => esc_url_raw($k['link'])
            );
        }, $input['keywords']) : array();
        return $input;
    }

    public function admin_scripts($hook) {
        if ($hook !== 'settings_page_smart-affiliate') return;
        wp_enqueue_script('jquery');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_group'); ?>
                <?php do_settings_sections('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Amazon Associate Tag</th>
                        <td><input type="text" name="smart_affiliate_settings[amazon_tag]" value="<?php echo esc_attr($this->options['amazon_tag']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="smart_affiliate_settings[enabled]" <?php checked($this->options['enabled']); ?> value="1" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Max Links per Post</th>
                        <td><input type="number" name="smart_affiliate_settings[max_links]" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Keywords & Custom Links</th>
                        <td>
                            <div id="keywords-container">
                                <?php foreach ($this->options['keywords'] as $i => $kw): ?>
                                <div class="keyword-row">
                                    <input type="text" name="smart_affiliate_settings[keywords][<?php echo $i; ?>][keyword]" placeholder="Keyword (e.g. laptop)" value="<?php echo esc_attr($kw['keyword']); ?>" />
                                    <input type="url" name="smart_affiliate_settings[keywords][<?php echo $i; ?>][link]" placeholder="Custom Link (optional)" value="<?php echo esc_attr($kw['link']); ?>" />
                                    <button type="button" class="button remove-kw">Remove</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-keyword" class="button">Add Keyword</button>
                            <p class="description">Leave custom link empty to auto-generate Amazon search link. <strong>Pro: AI keyword detection coming soon!</strong></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for AI-powered keyword matching, link analytics, and more. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let kwIndex = <?php echo count($this->options['keywords']); ?>;
            $('#add-keyword').click(function() {
                $('#keywords-container').append(
                    '<div class="keyword-row">' +
                    '<input type="text" name="smart_affiliate_settings[keywords][" + kwIndex + '][keyword]" placeholder="Keyword" />' +
                    '<input type="url" name="smart_affiliate_settings[keywords][" + kwIndex + '][link]" placeholder="Custom Link" />' +
                    '<button type="button" class="button remove-kw">Remove</button>' +
                    '</div>'
                );
                kwIndex++;
            });
            $(document).on('click', '.remove-kw', function() {
                $(this).closest('.keyword-row').remove();
            });
        });
        </script>
        <style>
        .keyword-row { margin-bottom: 10px; }
        .keyword-row input { margin-right: 10px; }
        </style>
        <?php
    }
}

new SmartAffiliateAutoInserter();