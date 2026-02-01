/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your WordPress posts and pages to boost monetization with minimal effort.
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array(
            'amazon_tag' => '',
            'keywords' => array(),
            'pro_version' => false
        ));
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || !is_single()) return $content;

        $keywords = $this->options['keywords'];
        $amazon_tag = $this->options['amazon_tag'];

        if (empty($keywords) || empty($amazon_tag)) return $content;

        foreach ($keywords as $keyword => $asin) {
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match($pattern, $content)) {
                $link = $this->generate_amazon_link($asin, $keyword, $amazon_tag);
                $content = preg_replace($pattern, $link, $content, 1);
            }
        }

        return $content;
    }

    private function generate_amazon_link($asin, $keyword, $tag) {
        $url = 'https://www.amazon.com/dp/' . $asin . '?tag=' . $tag;
        return '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener" class="smart-affiliate-link">' . esc_html($keyword) . '</a> ';
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('smart_affiliate', 'smart_affiliate_options');

        add_settings_section(
            'smart_affiliate_section',
            __('Setup your affiliate keywords', 'smart-affiliate-autoinserter'),
            null,
            'smart_affiliate'
        );

        add_settings_field(
            'amazon_tag',
            __('Amazon Affiliate Tag', 'smart-affiliate-autoinserter'),
            array($this, 'amazon_tag_render'),
            'smart_affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'keywords',
            __('Keywords and ASINs', 'smart-affiliate-autoinserter'),
            array($this, 'keywords_render'),
            'smart_affiliate',
            'smart_affiliate_section'
        );
    }

    public function amazon_tag_render() {
        $options = $this->options;
        ?>
        <input type='text' name='smart_affiliate_options[amazon_tag]' value='<?php echo esc_attr($options['amazon_tag']); ?>' class='regular-text' />
        <p class='description'><?php _e('Your Amazon Associates affiliate tag (e.g., yourid-20)', 'smart-affiliate-autoinserter'); ?></p>
        <?php
    }

    public function keywords_render() {
        $options = $this->options;
        $keywords = isset($options['keywords']) ? $options['keywords'] : array();
        echo '<div id="keyword-list">';
        if (!empty($keywords)) {
            foreach ($keywords as $kw => $asin) {
                echo '<div class="keyword-row">';
                echo '<input type="text" name="smart_affiliate_options[keywords][' . esc_attr($kw) . '][keyword]" value="' . esc_attr($kw) . '" placeholder="Keyword" />';
                echo '<input type="text" name="smart_affiliate_options[keywords][' . esc_attr($kw) . '][asin]" value="' . esc_attr($asin) . '" placeholder="ASIN (e.g., B08N5WRWNW)" />';
                echo '<button type="button" class="button remove-keyword">Remove</button>';
                echo '</div>';
            }
        }
        echo '</div>';
        echo '<button type="button" id="add-keyword" class="button">Add Keyword</button>';
        echo '<p class="description">Enter keywords from your content and matching Amazon ASINs. Links auto-insert on matching words.</p>';
        ?>
        <script>
        jQuery(document).ready(function($) {
            let kwCount = <?php echo count($keywords); ?>;
            $('#add-keyword').click(function() {
                let row = '<div class="keyword-row">';
                row += '<input type="text" name="smart_affiliate_options[keywords][kw' + kwCount + '][keyword]" placeholder="Keyword" />';
                row += '<input type="text" name="smart_affiliate_options[keywords][kw' + kwCount + '][asin]" placeholder="ASIN" />';
                row += '<button type="button" class="button remove-keyword">Remove</button>';
                row += '</div>';
                $('#keyword-list').append(row);
                kwCount++;
            });
            $(document).on('click', '.remove-keyword', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate');
                do_settings_sections('smart_affiliate');
                submit_button();
                ?>
            </form>
            <?php if (!$this->options['pro_version']) : ?>
            <div class="notice notice-info">
                <p><strong>Go Pro!</strong> Unlock AI keyword suggestions, analytics, and A/B testing for $29/year. <a href="#" onclick="alert('Upgrade at example.com/pro')">Upgrade Now</a></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_options', array());
    }
}

new SmartAffiliateAutoInserter();

// Pro teaser notice
do_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-success is-dismissible"><p>Smart Affiliate AutoInserter is active! Configure your Amazon tag and keywords in <a href="' . admin_url('options-general.php?page=smart-affiliate') . '">Settings &gt; Affiliate Inserter</a> to start earning.</p></div>';
});