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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_settings', array(
            'api_key' => '',
            'affiliates' => array(),
            'max_links' => 2,
            'pro' => false
        ));
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || !is_single()) return $content;

        $affiliates = $this->options['affiliates'];
        if (empty($affiliates)) return $content;

        $max_links = $this->options['pro'] ? 5 : min(2, count($affiliates));
        $inserted = 0;

        foreach ($affiliates as $aff) {
            if ($inserted >= $max_links) break;

            $keyword = $aff['keyword'];
            $link = $aff['link'];
            $text = $aff['text'];

            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match_all($pattern, $content, $matches)) {
                $replacement = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener" class="smart-aff-link">' . esc_html($text) . '</a>';
                $content = preg_replace($pattern, $replacement, $content, 1);
                $inserted++;
            }
        }

        return $content;
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
        register_setting('smart_affiliate', 'smart_affiliate_settings');

        add_settings_section(
            'smart_affiliate_section',
            'Affiliate Links Setup',
            null,
            'smart_affiliate'
        );

        add_settings_field(
            'smart_affiliates',
            'Add Affiliate Links',
            array($this, 'affiliates_cb'),
            'smart_affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'max_links',
            'Max Links per Post',
            array($this, 'max_links_cb'),
            'smart_affiliate',
            'smart_affiliate_section'
        );
    }

    public function affiliates_cb() {
        $affiliates = $this->options['affiliates'];
        echo '<div id="affiliates-list">';
        foreach ($affiliates as $i => $aff) {
            echo '<div class="affiliate-row">';
            echo '<input type="text" name="smart_affiliate_settings[affiliates][' . $i . '][keyword]" value="' . esc_attr($aff['keyword']) . '" placeholder="Keyword"> ';
            echo '<input type="text" name="smart_affiliate_settings[affiliates][' . $i . '][text]" value="' . esc_attr($aff['text']) . '" placeholder="Link Text"> ';
            echo '<input type="url" name="smart_affiliate_settings[affiliates][' . $i . '][link]" value="' . esc_url($aff['link']) . '" placeholder="Affiliate URL"> ';
            echo '<button type="button" class="remove-aff">Remove</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-affiliate">Add Link</button>';
        echo '<p class="description">Pro version supports unlimited links and AI keyword suggestions.</p>';
        echo '<script> 
        jQuery(document).ready(function($){
            $("#add-affiliate").click(function(){
                var i = $("#affiliates-list .affiliate-row").length;
                $("#affiliates-list").append(
                    '<div class="affiliate-row">'+
                    '<input type="text" name="smart_affiliate_settings[affiliates]["+i+"][keyword]" placeholder="Keyword"> '+
                    '<input type="text" name="smart_affiliate_settings[affiliates]["+i+"][text]" placeholder="Link Text"> '+
                    '<input type="url" name="smart_affiliate_settings[affiliates]["+i+"][link]" placeholder="Affiliate URL"> '+
                    '<button type="button" class="remove-aff">Remove</button> </div>'
                );
            });
            $(document).on("click", ".remove-aff", function(){
                $(this).parent().remove();
            });
        });
        </script>';
    }

    public function max_links_cb() {
        $max = $this->options['max_links'];
        echo '<input type="number" name="smart_affiliate_settings[max_links]" value="' . esc_attr($max) . '" min="1" max="5">';
        echo '<p class="description">Free: up to 2. Pro: unlimited.</p>';
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
            <p><strong>Upgrade to Pro</strong> for AI suggestions, analytics, and unlimited links: <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_settings');
    }

    public function deactivate() {}
}

new SmartAffiliateAutoInserter();

// Pro check simulation (in real, use license check)
function is_pro_version() {
    return false; // Set to true for pro
}
?>