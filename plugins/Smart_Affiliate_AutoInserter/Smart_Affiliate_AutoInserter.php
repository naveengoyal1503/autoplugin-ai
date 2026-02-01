/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into posts and pages using keyword matching to maximize earnings.
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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_settings', array());
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'settings_init'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        } else {
            add_filter('the_content', array($this, 'auto_insert_links'), 99);
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('smart_affiliate_settings', array(
            'api_key' => '',
            'affiliate_links' => array(),
            'max_links_per_post' => 3,
            'is_pro' => false
        ));
    }

    public function deactivate() {
        // Cleanup if needed
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

    public function settings_init() {
        register_setting('smart_affiliate_group', 'smart_affiliate_settings');

        add_settings_section(
            'smart_affiliate_section',
            __('Affiliate Links Setup', 'smart-affiliate-autoinserter'),
            null,
            'smart-affiliate'
        );

        add_settings_field(
            'affiliate_links',
            __('Affiliate Links', 'smart-affiliate-autoinserter'),
            array($this, 'affiliate_links_field'),
            'smart-affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'max_links_per_post',
            __('Max Links Per Post', 'smart-affiliate-autoinserter'),
            array($this, 'max_links_field'),
            'smart-affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'is_pro',
            __('Pro Version', 'smart-affiliate-autoinserter'),
            array($this, 'pro_field'),
            'smart-affiliate',
            'smart_affiliate_section'
        );
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate_group');
                do_settings_sections('smart-affiliate');
                submit_button();
                ?>
            </form>
            <?php if (!$this->options['is_pro']): ?>
            <div class="notice notice-info">
                <p><strong>Go Pro</strong> for unlimited links, AI keyword matching, and analytics! <a href="https://example.com/pro" target="_blank">Upgrade now ($49/year)</a></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function affiliate_links_field() {
        $links = $this->options['affiliate_links'] ?? array();
        echo '<div id="affiliate-links-container">';
        foreach ($links as $index => $link) {
            $this->single_link_field($index, $link['keyword'], $link['url'], $link['text']);
        }
        echo '</div>';
        echo '<p><a href="#" id="add-link">' . __('Add New Link', 'smart-affiliate-autoinserter') . '</a></p>';
        echo '<script>
            jQuery(document).ready(function($) {
                var index = ' . count($links) . ';
                $("#add-link").click(function(e) {
                    e.preventDefault();
                    $("#affiliate-links-container").append(
                        \`<?php $this->single_link_template(); ?>\`.replace(/INDEX/g, index)
                    );
                    index++;
                });
            });
        </script>';
    }

    private function single_link_field($index, $keyword, $url, $text) {
        ?>
        <div class="affiliate-link-row">
            <p>
                <label>Keyword: <input type="text" name="smart_affiliate_settings[affiliate_links][<?php echo $index; ?>][keyword]" value="<?php echo esc_attr($keyword); ?>" /></label>
            </p>
            <p>
                <label>Link URL: <input type="url" name="smart_affiliate_settings[affiliate_links][<?php echo $index; ?>][url]" value="<?php echo esc_attr($url); ?>" /></label>
            </p>
            <p>
                <label>Link Text: <input type="text" name="smart_affiliate_settings[affiliate_links][<?php echo $index; ?>][text]" value="<?php echo esc_attr($text); ?>" /></label>
            </p>
            <p><a href="#" class="remove-link">Remove</a></p>
        </div>
        <?php
    }

    private function single_link_template() {
        echo '<div class="affiliate-link-row">
            <p><label>Keyword: <input type="text" name="smart_affiliate_settings[affiliate_links][INDEX][keyword]" /></label></p>
            <p><label>Link URL: <input type="url" name="smart_affiliate_settings[affiliate_links][INDEX][url]" /></label></p>
            <p><label>Link Text: <input type="text" name="smart_affiliate_settings[affiliate_links][INDEX][text]" /></label></p>
            <p><a href="#" class="remove-link">Remove</a></p>
        </div>';
    }

    public function max_links_field() {
        $max = $this->options['max_links_per_post'] ?? 3;
        echo '<input type="number" name="smart_affiliate_settings[max_links_per_post]" value="' . esc_attr($max) . '" min="1" max="10" />';
    }

    public function pro_field() {
        $pro = $this->options['is_pro'] ?? false;
        if (!$pro) {
            echo '<p>Upgrade to Pro for advanced features.</p>';
        } else {
            echo '<p><strong>Pro Active!</strong></p>';
        }
    }

    public function admin_scripts($hook) {
        if ($hook !== 'settings_page_smart-affiliate') return;
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'jQuery(".remove-link").click(function(e){e.preventDefault();jQuery(this).closest(".affiliate-link-row").remove();});');
    }

    public function auto_insert_links($content) {
        if (is_admin() || empty($this->options['affiliate_links'])) {
            return $content;
        }

        $max_links = intval($this->options['max_links_per_post'] ?? 3);
        if ($max_links <= 0) return $content;

        $inserted = 0;
        $words = explode(' ', $content);
        $new_words = array();

        foreach ($words as $word) {
            $new_words[] = $word;

            foreach ($this->options['affiliate_links'] as $link) {
                if (stripos($word, $link['keyword']) !== false && $inserted < $max_links) {
                    $link_html = '<a href="' . esc_url($link['url']) . '" target="_blank" rel="nofollow noopener">' . esc_html($link['text'] ?: $link['keyword']) . '</a>';
                    $new_words[] = $link_html;
                    $inserted++;
                    break;
                }
            }

            if ($inserted >= $max_links) break;
        }

        return implode(' ', $new_words);
    }
}

new SmartAffiliateAutoInserter();