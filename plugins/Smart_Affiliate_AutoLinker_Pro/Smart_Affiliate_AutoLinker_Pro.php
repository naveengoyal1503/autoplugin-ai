/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker Pro
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically converts keywords in posts and pages into affiliate links from Amazon. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoLinker {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        } else {
            add_filter('the_content', array($this, 'auto_link_keywords'));
            add_filter('widget_text', array($this, 'auto_link_keywords'));
        }
        load_plugin_textdomain('smart-affiliate-autolinker', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function activate() {
        add_option('saal_keywords', array(
            array('keyword' => 'WordPress', 'url' => 'https://amazon.com/wordpress-book?tag=youraffiliateid-20', 'free' => true)
        ));
        add_option('saal_amazon_tag', 'youraffiliateid-20');
        add_option('saal_free_limit', 3);
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
        register_setting('saal_settings', 'saal_keywords');
        register_setting('saal_settings', 'saal_amazon_tag');
        register_setting('saal_settings', 'saal_free_limit');
        register_setting('saal_settings', 'saal_is_premium');
    }

    public function settings_page() {
        $keywords = get_option('saal_keywords', array());
        $amazon_tag = get_option('saal_amazon_tag', '');
        $free_limit = get_option('saal_free_limit', 3);
        $is_premium = get_option('saal_is_premium', false);
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoLinker Settings</h1>
            <?php if (!$is_premium) : ?>
                <div class="notice notice-warning"><p><strong>Free Version:</strong> Limited to <?php echo $free_limit; ?> keywords. <a href="https://example.com/premium" target="_blank">Upgrade to Pro</a> for unlimited features!</p></div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('saal_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="saal_amazon_tag" value="<?php echo esc_attr($amazon_tag); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Keywords</th>
                        <td>
                            <div id="keywords-list">
                                <?php foreach ($keywords as $i => $kw) : ?>
                                    <div class="keyword-row">
                                        <input type="text" name="saal_keywords[<?php echo $i; ?>][keyword]" value="<?php echo esc_attr($kw['keyword']); ?>" placeholder="Keyword" />
                                        <input type="url" name="saal_keywords[<?php echo $i; ?>][url]" value="<?php echo esc_attr($kw['url']); ?>" placeholder="Affiliate URL" />
                                        <label><input type="checkbox" name="saal_keywords[<?php echo $i; ?>][free]" <?php checked($kw['free']); ?> /> Free Tier</label>
                                        <button type="button" class="button remove-kw">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p><button type="button" id="add-keyword" class="button">Add Keyword</button></p>
                            <p class="description">Free version limited to <?php echo $free_limit; ?> keywords. <?php if (!$is_premium) : ?><a href="https://example.com/premium" target="_blank">Go Pro</a><?php endif; ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let kwIndex = <?php echo count($keywords); ?>;
            $('#add-keyword').click(function() {
                let freeLimit = <?php echo $free_limit; ?>;
                let freeCount = $('.keyword-row input[name*="[free]"]:checked').length;
                if (!$('#saal_is_premium').val() && $('.keyword-row').length >= freeLimit) {
                    alert('Free version limited to ' + freeLimit + ' keywords. Upgrade to Pro!');
                    return;
                }
                $('#keywords-list').append(
                    '<div class="keyword-row">' +
                    '<input type="text" name="saal_keywords[' + kwIndex + '][keyword]" placeholder="Keyword" />' +
                    '<input type="url" name="saal_keywords[' + kwIndex + '][url]" placeholder="Affiliate URL" />' +
                    '<label><input type="checkbox" name="saal_keywords[' + kwIndex + '][free]" checked /> Free Tier</label>' +
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
        <?php
    }

    public function auto_link_keywords($content) {
        $keywords = get_option('saal_keywords', array());
        $amazon_tag = get_option('saal_amazon_tag', '');
        $is_premium = get_option('saal_is_premium', false);
        $free_limit = get_option('saal_free_limit', 3);

        // Filter to free keywords only if not premium
        if (!$is_premium) {
            $keywords = array_filter($keywords, function($kw) {
                return isset($kw['free']) && $kw['free'];
            });
            $keywords = array_slice($keywords, 0, $free_limit);
        }

        if (empty($keywords)) {
            return $content;
        }

        foreach ($keywords as $kw) {
            $keyword = preg_quote($kw['keyword'], '/');
            $link = $kw['url'];
            if (strpos($link, 'amazon') !== false && !preg_match('/tag=/', $link)) {
                $link .= '?tag=' . $amazon_tag;
            }
            $pattern = '/\b(' . $keyword . ')\b/i';
            $replacement = '<a href="$1" target="_blank" rel="nofollow noopener sponsored">$1</a>';
            $content = preg_replace($pattern, $replacement, $content, 1); // Replace once per keyword
        }
        return $content;
    }
}

SmartAffiliateAutoLinker::get_instance();

// Premium nag
add_action('admin_notices', function() {
    $is_premium = get_option('saal_is_premium', false);
    if (!$is_premium && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoLinker:</strong> Unlock unlimited keywords, analytics, and more with <a href="https://example.com/premium" target="_blank">Pro version</a>! Earn more from your content.</p></div>';
    }
});