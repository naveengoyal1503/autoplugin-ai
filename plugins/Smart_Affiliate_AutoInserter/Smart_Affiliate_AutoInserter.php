/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages for passive income.
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
            'affiliate_tag' => '',
            'keywords' => array(),
            'enabled' => true
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (!is_single() || !$this->options['enabled'] || empty($this->options['affiliate_tag']) || empty($this->options['keywords'])) {
            return $content;
        }

        foreach ($this->options['keywords'] as $keyword => $product) {
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches as $match) {
                    $link = $this->generate_amazon_link($product, $this->options['affiliate_tag']);
                    $content = str_replace($match, $match . ' ' . $link, $content, $count);
                    if ($count > 0) break 2; // Limit to one insertion per keyword
                }
            }
        }
        return $content;
    }

    private function generate_amazon_link($product, $tag) {
        $asin = isset($product['asin']) ? $product['asin'] : 'B07H8Q1J6Q'; // Default product
        $text = isset($product['text']) ? $product['text'] : 'Check it out';
        return '<a href="https://www.amazon.com/dp/' . $asin . '?tag=' . $tag . '" target="_blank" rel="nofollow noopener">' . $text . '</a>';
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('smart_affiliate', 'smart_affiliate_options');

        add_settings_section(
            'smart_affiliate_section',
            'Affiliate Settings',
            null,
            'smart_affiliate'
        );

        add_settings_field(
            'affiliate_tag',
            'Amazon Affiliate Tag',
            array($this, 'affiliate_tag_render'),
            'smart_affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'keywords',
            'Keywords & Products',
            array($this, 'keywords_render'),
            'smart_affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'enabled',
            'Enable Auto-Insertion',
            array($this, 'enabled_render'),
            'smart_affiliate',
            'smart_affiliate_section'
        );
    }

    public function affiliate_tag_render() {
        $options = $this->options;
        ?>
        <input type='text' name='smart_affiliate_options[affiliate_tag]' value='<?php echo esc_attr($options['affiliate_tag']); ?>' />
        <p class='description'>Your Amazon Associates affiliate tag (e.g., yourid-20).</p>
        <?php
    }

    public function keywords_render() {
        $options = $this->options;
        $keywords = isset($options['keywords']) ? $options['keywords'] : array();
        echo '<div id="keywords-container">';
        foreach ($keywords as $kw => $prod) {
            echo '<div class="keyword-row">';
            echo '<input type="text" name="smart_affiliate_options[keywords][' . $kw . '][keyword]" value="' . esc_attr($prod['keyword']) . '" placeholder="Keyword">';
            echo '<input type="text" name="smart_affiliate_options[keywords][' . $kw . '][asin]" value="' . esc_attr($prod['asin']) . '" placeholder="ASIN">';
            echo '<input type="text" name="smart_affiliate_options[keywords][' . $kw . '][text]" value="' . esc_attr($prod['text']) . '" placeholder="Link Text">';
            echo '<button type="button" class="remove-kw">Remove</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-keyword">Add Keyword</button>';
        echo '<script>var keywordCount = ' . count($keywords) . ';</script>';
        ?>
        <style>
        .keyword-row { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
        .keyword-row input { margin-right: 5px; width: 150px; }
        </style>
        <?php
    }

    public function enabled_render() {
        $options = $this->options;
        ?>
        <input type='checkbox' name='smart_affiliate_options[enabled]' <?php checked($options['enabled']); ?> value='1' />
        <?php
    }

    public function options_page() {
        ?>
        <div class="wrap">
        <h1>Smart Affiliate AutoInserter</h1>
        <form method="post" action="options.php">
        <?php
        settings_fields('smart_affiliate');
        do_settings_sections('smart_affiliate');
        submit_button();
        ?>
        </form>
        <p><strong>Pro Features (Upgrade for $49/year):</strong> AI keyword suggestions, click tracking, A/B testing, premium support.</p>
        </div>
        <?php
    }

    public function activate() {
        if (!get_option('smart_affiliate_options')) {
            update_option('smart_affiliate_options', array('enabled' => true));
        }
    }
}

new SmartAffiliateAutoInserter();

// Inline JS for admin
add_action('admin_footer', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'smart-affiliate') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#add-keyword').click(function() {
                var html = '<div class="keyword-row">' +
                    '<input type="text" name="smart_affiliate_options[keywords][" + keywordCount + "][keyword]" placeholder="Keyword">' +
                    '<input type="text" name="smart_affiliate_options[keywords][" + keywordCount + "][asin]" placeholder="ASIN">' +
                    '<input type="text" name="smart_affiliate_options[keywords][" + keywordCount + "][text]" placeholder="Link Text">' +
                    '<button type="button" class="remove-kw">Remove</button>' +
                    '</div>';
                $('#keywords-container').append(html);
                keywordCount++;
            });
            $(document).on('click', '.remove-kw', function() {
                $(this).closest('.keyword-row').remove();
            });
        });
        </script>
        <?php
    }
});