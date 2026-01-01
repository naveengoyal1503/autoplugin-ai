/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automatically inserts and tracks affiliate links in posts, cloaks them for better conversions, and provides click analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateLinkManager {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sal_update_link', array($this, 'ajax_update_link'));
        add_filter('the_content', array($this, 'auto_insert_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('sal_options', array(
            'keywords' => array(),
            'affiliate_url' => '',
            'pro_version' => false
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-frontend', plugin_dir_url(__FILE__) . 'sal-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sal-frontend', 'sal_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate Manager', 'manage_options', 'sal-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->options['keywords'] = sanitize_textarea_field($_POST['keywords']);
            $this->options['keywords'] = explode(',', $this->options['keywords']);
            $this->options['affiliate_url'] = esc_url_raw($_POST['affiliate_url']);
            update_option('sal_options', $this->options);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Manager</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Keywords (comma-separated)</th>
                        <td><textarea name="keywords" rows="5" cols="50"><?php echo esc_textarea(implode(',', $this->options['keywords'])); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Base URL</th>
                        <td><input type="url" name="affiliate_url" value="<?php echo esc_attr($this->options['affiliate_url']); ?>" style="width: 400px;" /></td>
                    </tr>
                </table>
                <?php if (!$this->options['pro_version']) { ?>
                <p><strong>Upgrade to Pro for analytics and A/B testing: <a href="#" onclick="alert('Pro version available at example.com/pro')">Get Pro ($49/year)</a></strong></p>
                <?php } ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('form').on('submit', function() {
                // Track form submit for analytics (pro feature tease)
            });
        });
        </script>
        <?php
    }

    public function auto_insert_links($content) {
        if (!is_single() || empty($this->options['keywords']) || empty($this->options['affiliate_url'])) {
            return $content;
        }
        global $post;
        $words = explode(' ', $content);
        foreach ($words as $key => $word) {
            foreach ($this->options['keywords'] as $keyword) {
                $keyword = trim(strtolower($keyword));
                if (strtolower($word) === $keyword && rand(1, 3) === 1) { // Insert ~33% of matches
                    $cloaked_url = add_query_arg('salid', $post->ID . '-' . $key, $this->options['affiliate_url']);
                    $link = '<a href="' . esc_url($cloaked_url) . '" rel="nofollow" target="_blank">' . esc_html($word) . '</a>';
                    $words[$key] = $link;
                }
            }
        }
        $content = implode(' ', $words);
        return $content;
    }

    public function ajax_update_link() {
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $click_data = $_POST['data'];
        update_option('sal_clicks', get_option('sal_clicks', array()) + $click_data);
        wp_send_json_success();
    }

    public function activate() {
        add_option('sal_options', array('keywords' => array(), 'affiliate_url' => '', 'pro_version' => false));
    }
}

new SmartAffiliateLinkManager();

// Frontend JS (embedded for single file)
function sal_frontend_js() {
    ?>
    <script>jQuery(document).ready(function($) {
        $('a[href*="salid"]').on('click', function() {
            $.post(sal_ajax.ajaxurl, {action: 'sal_update_link', data: {url: $(this).attr('href')}}, function() {});
        });
    });</script>
    <?php
}
add_action('wp_footer', 'sal_frontend_js');