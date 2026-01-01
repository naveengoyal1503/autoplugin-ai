/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects keywords in your content and converts them into profitable affiliate links from Amazon.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoLinker {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_link_content'), 20);
        add_filter('wp_trim_excerpt', array($this, 'auto_link_excerpt'), 20);
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_settings', array(
            'amazon_tag' => '',
            'keywords' => array(
                array('keyword' => 'WordPress', 'url' => ''),
                array('keyword' => 'plugin', 'url' => ''),
            ),
            'max_links' => 3,
            'open_new_tab' => 1,
            'nofollow' => 1,
            'pro' => 0
        ));
        if ($this->options['pro'] == 0) {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function activate() {
        add_option('smart_affiliate_settings');
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLinker',
            'Affiliate AutoLinker',
            'manage_options',
            'smart-affiliate-autolinker',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_group', 'smart_affiliate_settings');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('smart-affiliate-admin', plugin_dir_url(__FILE__) . 'admin.css');
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoLinker:</strong> Upgrade to Pro for unlimited keywords, analytics & more! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }

    public function auto_link_content($content) {
        if (is_feed() || is_preview() || is_admin()) return $content;

        $max_links = intval($this->options['max_links']);
        $linked = 0;

        foreach ($this->options['keywords'] as $kw) {
            if ($linked >= $max_links) break;
            if (empty($kw['keyword']) || empty($kw['url'])) continue;

            $keyword = esc_attr($kw['keyword']);
            $url = esc_url($kw['url']);
            $link_attr = '';
            if (!empty($this->options['open_new_tab'])) $link_attr .= ' target="_blank" ';
            if (!empty($this->options['nofollow'])) $link_attr .= ' rel="nofollow" ';

            $regex = '/\b(' . preg_quote($keyword, '/') . ')\b/i';
            $content = preg_replace_callback($regex, function($matches) use ($url, $link_attr, &$linked) {
                if ($linked >= 3) return $matches; // Free limit
                $linked++;
                return '<a href="' . $url . '" ' . $link_attr . '>' . $matches[1] . '</a>';
            }, $content);
        }
        return $content;
    }

    public function auto_link_excerpt($excerpt) {
        return $this->auto_link_content($excerpt);
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $settings = array(
                'amazon_tag' => sanitize_text_field($_POST['amazon_tag']),
                'keywords' => isset($_POST['keywords']) ? array_map(function($k, $u) {
                    return array('keyword' => sanitize_text_field($k), 'url' => esc_url_raw($u));
                }, $_POST['keywords_keyword'], $_POST['keywords_url']) : array(),
                'max_links' => intval($_POST['max_links']),
                'open_new_tab' => isset($_POST['open_new_tab']) ? 1 : 0,
                'nofollow' => isset($_POST['nofollow']) ? 1 : 0,
                'pro' => 0
            );
            update_option('smart_affiliate_settings', $settings);
            echo '<div class="updated"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoLinker Settings</h1>
            <form method="post" action="">
                <?php settings_fields('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="amazon_tag" value="<?php echo esc_attr($this->options['amazon_tag']); ?>" class="regular-text" placeholder="your-tag-123" /></td>
                    </tr>
                    <tr>
                        <th>Keywords & Links</th>
                        <td>
                            <div id="keywords-container">
                                <?php foreach ($this->options['keywords'] as $i => $kw): ?>
                                <div class="keyword-row">
                                    <input type="text" name="keywords_keyword[<?php echo $i; ?>]" value="<?php echo esc_attr($kw['keyword']); ?>" placeholder="Keyword" style="width:200px;" />
                                    <input type="url" name="keywords_url[<?php echo $i; ?>]" value="<?php echo esc_attr($kw['url']); ?>" placeholder="https://amazon.com/..." style="width:300px;" />
                                    <button type="button" class="button remove-kw">Remove</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-keyword" class="button">Add Keyword</button>
                            <p class="description">Free version limited to 5 keywords. <a href="https://example.com/pro" target="_blank">Pro: Unlimited</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="max_links" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Open in New Tab</th>
                        <td><input type="checkbox" name="open_new_tab" <?php checked($this->options['open_new_tab']); ?> /></td>
                    </tr>
                    <tr>
                        <th>NoFollow</th>
                        <td><input type="checkbox" name="nofollow" <?php checked($this->options['nofollow']); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <script>
            jQuery(document).ready(function($) {
                let kwCount = <?php echo count($this->options['keywords']); ?>;
                $('#add-keyword').click(function() {
                    if (kwCount >= 5) { alert('Upgrade to Pro for more keywords!'); return; }
                    $('#keywords-container').append(
                        '<div class="keyword-row">' +
                        '<input type="text" name="keywords_keyword[' + kwCount + ']" placeholder="Keyword" style="width:200px;" />' +
                        '<input type="url" name="keywords_url[' + kwCount + ']" placeholder="https://amazon.com/..." style="width:300px;" />' +
                        '<button type="button" class="button remove-kw">Remove</button>' +
                        '</div>'
                    );
                    kwCount++;
                });
                $(document).on('click', '.remove-kw', function() {
                    $(this).parent().remove();
                });
            });
            </script>
        </div>
        <style>
        .keyword-row { margin-bottom: 10px; padding: 10px; background: #f9f9f9; }
        </style>
        <?php
    }
}

new SmartAffiliateAutoLinker();

// Auto-generate Amazon links if tag set
function smart_generate_amazon_url($keyword, $tag) {
    $keyword = urlencode($keyword);
    return 'https://amazon.com/s?k=' . $keyword . '&tag=' . $tag;
}
?>