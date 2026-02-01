/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages based on keyword matching. Boost your affiliate earnings effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_filter('widget_text', array($this, 'auto_insert_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array(
            'affiliate_tag' => '',
            'keywords' => array(),
            'products' => array(),
            'enabled' => true,
            'max_links' => 3,
            'pro_version' => false
        ));
    }

    public function activate() {
        if (!get_option('smart_affiliate_options')) {
            add_option('smart_affiliate_options', array(
                'affiliate_tag' => 'youraffiliatetag-20',
                'keywords' => array('laptop' => 'https://amazon.com/dp/B08N5WRWNW'),
                'products' => array(),
                'enabled' => true,
                'max_links' => 3,
                'pro_version' => false
            ));
        }
    }

    public function deactivate() {
        // Cleanup if needed
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
            'Affiliate Link Settings',
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
            'Keywords and Product Links',
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

        add_settings_field(
            'max_links',
            'Max Links per Post',
            array($this, 'max_links_render'),
            'smart_affiliate',
            'smart_affiliate_section'
        );
    }

    public function affiliate_tag_render() {
        $options = $this->options;
        ?>
        <input type='text' name='smart_affiliate_options[affiliate_tag]' value='<?php echo esc_attr($options['affiliate_tag']); ?>' class='regular-text' placeholder='yourtag-20' />
        <p class='description'>Your Amazon Associates affiliate tag (e.g., yourtag-20).</p>
        <?php
    }

    public function keywords_render() {
        $options = $this->options;
        $keywords = isset($options['keywords']) ? $options['keywords'] : array();
        echo '<div id="keyword-list">';
        if (!empty($keywords)) {
            foreach ($keywords as $keyword => $link) {
                echo '<p><input type="text" name="smart_affiliate_options[keywords][' . esc_attr($keyword) . ']" value="' . esc_attr($link) . '" class="regular-text" placeholder="Product URL" /> <label>for keyword: ' . esc_attr($keyword) . '</label> <button type="button" class="button button-small remove-keyword">Remove</button></p>';
            }
        }
        echo '</div>';
        echo '<p><button type="button" id="add-keyword" class="button">Add Keyword</button></p>';
        echo '<textarea name="smart_affiliate_options[bulk_keywords]" placeholder="Bulk add: keyword1=url1&#10;keyword2=url2" rows="5" cols="50"></textarea>';
        echo '<p class="description">Add keyword=product_url pairs, one per line in bulk textarea or use Add button.</p>';
        ?>
        <script>
        jQuery(document).ready(function($) {
            var keywordIndex = <?php echo count($keywords); ?>;
            $('#add-keyword').click(function() {
                var keyword = prompt('Enter keyword:');
                if (keyword) {
                    var url = prompt('Enter Amazon product URL:');
                    if (url) {
                        $('#keyword-list').append('<p><input type="text" name="smart_affiliate_options[keywords][' + keywordIndex + ']" value="' + url + '" class="regular-text" placeholder="Product URL" /> <label>for keyword: ' + keyword + '</label> <button type="button" class="button button-small remove-keyword">Remove</button></p>');
                        keywordIndex++;
                    }
                }
            });
            $(document).on('click', '.remove-keyword', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    }

    public function enabled_render() {
        $options = $this->options;
        ?>
        <input type='checkbox' name='smart_affiliate_options[enabled]' value='1' <?php checked($options['enabled']); ?> />
        <?php
    }

    public function max_links_render() {
        $options = $this->options;
        ?>
        <input type='number' name='smart_affiliate_options[max_links]' value='<?php echo esc_attr($options['max_links']); ?>' min='1' max='10' />
        <p class='description'>Maximum affiliate links to insert per post/page.</p>
        <?php
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate');
                do_settings_sections('smart_affiliate');
                submit_button();
                ?>
            </form>
            <?php if (!$this->options['pro_version']) : ?>
            <div class="notice notice-info">
                <p><strong>Go Pro</strong> for unlimited sites, analytics, A/B testing & more! <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (!is_single() && !is_page() || !$this->options['enabled'] || empty($this->options['affiliate_tag']) || empty($this->options['keywords'])) {
            return $content;
        }

        $max_links = intval($this->options['max_links']);
        $inserted = 0;
        $words = explode(' ', $content);
        $new_words = array();

        foreach ($words as $word) {
            $new_words[] = $word;
            foreach ($this->options['keywords'] as $keyword => $product_url) {
                if (stripos($word, $keyword) !== false && $inserted < $max_links) {
                    $aff_link = $this->build_amazon_link($product_url, $this->options['affiliate_tag']);
                    $new_words[] = '<span class="affiliate-link">' . $aff_link . '</span>';
                    $inserted++;
                    break;
                }
            }
            if ($inserted >= $max_links) break;
        }

        $content = implode(' ', $new_words);
        return $content;
    }

    private function build_amazon_link($product_url, $tag) {
        $parsed = parse_url($product_url);
        $query = [];
        parse_str($parsed['query'], $query);
        $query['tag'] = $tag;
        $new_query = http_build_query($query);
        $link = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'] . '?' . $new_query;
        return '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">' . esc_html(basename($parsed['path'])) . '</a>';
    }
}

new SmartAffiliateAutoInserter();

// Enqueue jQuery for admin
if (is_admin()) {
    wp_enqueue_script('jquery');
}

?>