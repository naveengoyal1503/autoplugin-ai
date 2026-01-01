/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects keywords in your posts and converts them to profitable affiliate links from Amazon.
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
    }

    public function init() {
        $this->options = get_option('smart_affiliate_settings', array(
            'amazon_tag' => '',
            'keywords' => array(
                array('keyword' => 'WordPress', 'link' => 'https://amazon.com/wordpress-book'),
            ),
            'enabled' => true,
            'pro' => false
        ));

        if ($this->options['enabled']) {
            add_filter('the_content', array($this, 'auto_link_keywords'), 20);
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
    }

    public function activate() {
        add_option('smart_affiliate_settings');
    }

    public function deactivate() {
        // Do nothing
    }

    public function auto_link_keywords($content) {
        if (is_admin() || !$this->options['enabled']) {
            return $content;
        }

        global $post;
        if (in_array($post->post_type, array('page', 'post'))) {
            foreach ($this->options['keywords'] as $kw) {
                if (isset($kw['keyword']) && !empty($kw['keyword'])) {
                    $pattern = '/\b' . preg_quote($kw['keyword'], '/') . '\b/i';
                    $link = isset($kw['link']) ? $kw['link'] : '#';
                    if (strpos($link, 'amazon.com') !== false && !empty($this->options['amazon_tag'])) {
                        $link = add_query_arg('tag', $this->options['amazon_tag'], $link);
                    }
                    $content = preg_replace($pattern, '<a href="$link" target="_blank" rel="nofollow sponsored">$0</a>', $content, 1);
                }
            }
        }
        return $content;
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLinker',
            'Affiliate AutoLinker',
            'manage_options',
            'smart-affiliate-autolinker',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_group', 'smart_affiliate_settings');
    }

    public function admin_scripts($hook) {
        if ($hook !== 'settings_page_smart-affiliate-autolinker') {
            return;
        }
        wp_enqueue_script('jquery');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_affiliate_settings', $_POST['settings']);
            $this->options = $_POST['settings'];
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoLinker Settings</h1>
            <form method="post" action="">
                <?php settings_fields('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Linking</th>
                        <td>
                            <input type="checkbox" name="settings[enabled]" value="1" <?php checked($this->options['enabled']); ?>>
                        </td>
                    </tr>
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td>
                            <input type="text" name="settings[amazon_tag]" value="<?php echo esc_attr($this->options['amazon_tag']); ?>" class="regular-text">
                            <p class="description">Your Amazon Associates tag (e.g., yourtag-20)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Keywords</th>
                        <td id="keywords-container">
                            <?php foreach ($this->options['keywords'] as $i => $kw): ?>
                            <div class="keyword-row">
                                <input type="text" name="settings[keywords][<?php echo $i; ?>][keyword]" value="<?php echo esc_attr($kw['keyword']); ?>" placeholder="Keyword">
                                <input type="url" name="settings[keywords][<?php echo $i; ?>][link]" value="<?php echo esc_attr($kw['link']); ?>" placeholder="Amazon product URL">
                                <button type="button" class="button remove-kw">Remove</button>
                            </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="Save Changes">
                </p>
                <script>
                jQuery(document).ready(function($) {
                    $('#keywords-container').on('click', '.remove-kw', function() {
                        $(this).closest('.keyword-row').remove();
                    });
                    $('#keywords-container').append('<button type="button" class="button" id="add-kw">Add Keyword</button>');
                    $('#add-kw').on('click', function() {
                        var i = $('.keyword-row').length;
                        $('#keywords-container').append(
                            '<div class="keyword-row">' +
                            '<input type="text" name="settings[keywords][" + i + "][keyword]" placeholder="Keyword">' +
                            '<input type="url" name="settings[keywords][" + i + "][link]" placeholder="Amazon product URL">' +
                            '<button type="button" class="button remove-kw">Remove</button>' +
                            '</div>'
                        );
                    });
                });
                </script>
            </form>
            <div class="notice notice-info">
                <p><strong>Pro Version ($49/year):</strong> Unlimited keywords, multiple networks, click analytics, A/B testing. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>
            </div>
        </div>
        <?php
    }
}

new SmartAffiliateAutoLinker();

// Freemium upsell notice
function smart_affiliate_admin_notice() {
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_smart-affiliate-autolinker') {
        echo '<div class="notice notice-success is-dismissible"><p>Unlock Pro features: Unlimited keywords & analytics! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'smart_affiliate_admin_notice');

?>