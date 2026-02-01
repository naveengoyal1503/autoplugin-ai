/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your content using keyword matching and AI-like rules.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array(
            'keywords' => array(),
            'links' => array(),
            'max_links' => 3,
            'enabled' => true
        ));
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function activate() {
        if (!get_option('smart_affiliate_options')) {
            add_option('smart_affiliate_options', array(
                'keywords' => array(),
                'links' => array(),
                'max_links' => 3,
                'enabled' => true
            ));
        }
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (!is_single() || !$this->options['enabled'] || !current_user_can('read')) {
            return $content;
        }

        $words = explode(' ', $content);
        $inserted = 0;
        $max = intval($this->options['max_links']);

        foreach ($words as $index => &$word) {
            if ($inserted >= $max) break;

            foreach ($this->options['keywords'] as $keyword => $link_data) {
                if (stripos($word, $keyword) !== false) {
                    $link = '<a href="' . esc_url($link_data['url']) . '" target="_blank" rel="nofollow sponsored">' . esc_html($word) . '</a>';
                    $word = $link;
                    $inserted++;
                    break;
                }
            }
        }

        return implode(' ', $words);
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
        register_setting('smart_affiliate_group', 'smart_affiliate_options');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_affiliate_options', $_POST['smart_affiliate_options']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $options = $this->options;
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="">
                <?php settings_fields('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enabled</th>
                        <td><input type="checkbox" name="smart_affiliate_options[enabled]" <?php checked($options['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="smart_affiliate_options[max_links]" value="<?php echo esc_attr($options['max_links']); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Keywords & Links</th>
                        <td>
                            <div id="keyword-links">
                                <?php foreach ($options['keywords'] as $kw => $data): ?>
                                    <div class="keyword-row">
                                        <input type="text" name="smart_affiliate_options[keywords][<?php echo esc_attr($kw); ?>][keyword]" placeholder="Keyword" value="<?php echo esc_attr($data['keyword']); ?>" />
                                        <input type="url" name="smart_affiliate_options[keywords][<?php echo esc_attr($kw); ?>][url]" placeholder="Affiliate URL" value="<?php echo esc_attr($data['url']); ?>" />
                                        <button type="button" class="button remove-kw">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-kw" class="button">Add Keyword/Link</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock AI-powered link suggestions, analytics, and unlimited links for $49/year!</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#add-kw').click(function() {
                var count = $('#keyword-links .keyword-row').length;
                $('#keyword-links').append(
                    '<div class="keyword-row">' +
                    '<input type="text" name="smart_affiliate_options[keywords][' + count + '][keyword]" placeholder="Keyword" />' +
                    '<input type="url" name="smart_affiliate_options[keywords][' + count + '][url]" placeholder="Affiliate URL" />' +
                    '<button type="button" class="button remove-kw">Remove</button>' +
                    '</div>'
                );
            });
            $(document).on('click', '.remove-kw', function() {
                $(this).parent().remove();
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

// Freemium nag
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_smart-affiliate') {
        echo '<div class="notice notice-info"><p><strong>Pro Features:</strong> AI link suggestions, performance tracking, and more. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
});