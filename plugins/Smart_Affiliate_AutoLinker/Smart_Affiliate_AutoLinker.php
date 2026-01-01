/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects keywords in your WordPress content and converts them into cloaked affiliate links from your dashboard, boosting commissions effortlessly.
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
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_link_content'));
        add_action('wp_ajax_sa_save_keywords', array($this, 'save_keywords'));
        add_action('wp_ajax_sa_delete_keyword', array($this, 'delete_keyword'));
    }

    public function init() {
        if (!session_id()) {
            session_start();
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sa-frontend', plugin_dir_url(__FILE__) . 'sa-frontend.js', array('jquery'), '1.0.0', true);
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

    public function settings_page() {
        if (isset($_POST['sa_submit'])) {
            check_admin_referer('sa_settings_nonce');
            $keywords = sanitize_textarea_field($_POST['sa_keywords']);
            update_option('sa_keywords', $keywords);
        }
        $keywords = get_option('sa_keywords', '');
        $keyword_list = $this->parse_keywords($keywords);
        include plugin_dir_path(__FILE__) . 'settings.php';
    }

    private function parse_keywords($keywords_text) {
        $lines = explode("\n", trim($keywords_text));
        $list = array();
        foreach ($lines as $line) {
            $parts = preg_split('/\s*\|\|\s*/', trim($line), 2);
            if (count($parts) === 2) {
                $list[] = array(
                    'keyword' => sanitize_text_field($parts),
                    'url' => esc_url_raw($parts[1])
                );
            }
        }
        return $list;
    }

    public function save_keywords() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        check_ajax_referer('sa_ajax_nonce', 'nonce');
        $keywords = sanitize_textarea_field($_POST['keywords']);
        update_option('sa_keywords', $keywords);
        wp_send_json_success('Keywords saved!');
    }

    public function delete_keyword() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        check_ajax_referer('sa_ajax_nonce', 'nonce');
        $keyword_list = $this->parse_keywords(get_option('sa_keywords', ''));
        $index = intval($_POST['index']);
        if (isset($keyword_list[$index])) {
            unset($keyword_list[$index]);
            $new_keywords = '';
            foreach ($keyword_list as $item) {
                $new_keywords .= $item['keyword'] . ' || ' . $item['url'] . "\n";
            }
            update_option('sa_keywords', trim($new_keywords));
            wp_send_json_success('Keyword deleted!');
        }
        wp_send_json_error('Error deleting keyword');
    }

    public function auto_link_content($content) {
        if (is_feed() || is_admin()) {
            return $content;
        }
        $keyword_list = $this->parse_keywords(get_option('sa_keywords', ''));
        if (empty($keyword_list)) {
            return $content;
        }

        foreach ($keyword_list as $item) {
            $keyword = preg_quote($item['keyword'], '/');
            $pattern = '/\b(' . $keyword . ')\b/i';
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $replacements = array();
                foreach ($matches as $match) {
                    $offset = $match[1];
                    if (!isset($replacements[$offset])) {
                        $link = '<a href="' . esc_url($item['url']) . '" rel="nofollow noopener" target="_blank" class="sa-affiliate-link">' . $match . '</a>';
                        $content = substr_replace($content, $link, $offset, strlen($match));
                        // Adjust offsets for subsequent matches
                        foreach ($matches as $k => $m) {
                            if ($m[1] > $offset) {
                                $matches[$k][1] += strlen($link) - strlen($match);
                            }
                        }
                    }
                }
            }
        }
        return $content;
    }
}

SmartAffiliateAutoLinker::get_instance();

// Pro upgrade notice
function sa_pro_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoLinker Pro:</strong> Unlock unlimited keywords, click tracking & analytics for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'sa_pro_notice');

// Settings page template
$sa_template = '<?php /* Settings Page */ ?>
<div class="wrap">
    <h1>Smart Affiliate AutoLinker Settings</h1>
    <form method="post" action="">
        <?php wp_nonce_field("sa_settings_nonce"); ?>
        <textarea name="sa_keywords" rows="10" cols="80" placeholder="Keyword || Affiliate URL\nexample || https://amazon.com/product?tag=yourid"><?php echo esc_textarea($keywords); ?></textarea>
        <p class="description">One keyword per line: <code>keyword || https://affiliate-link.com</code></p>
        <p><input type="submit" name="sa_submit" class="button-primary" value="Save Keywords"></p>
    </form>
    <h2>Current Keywords:</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>Keyword</th><th>URL</th><th>Action</th></tr></thead>
        <tbody>';
foreach ($keyword_list as $index => $item) {
    $sa_template .= '<tr><td>' . esc_html($item['keyword']) . '</td><td>' . esc_html($item['url']) . '</td><td><button class="button sa-delete" data-index="' . $index . '">Delete</button></td></tr>';
}
$sa_template .= '</tbody></table>
    <script>
    jQuery(document).ready(function($) {
        $(".sa-delete").click(function(e) {
            e.preventDefault();
            if (confirm("Delete this keyword?")) {
                var index = $(this).data("index");
                $.post(ajaxurl, {
                    action: "sa_delete_keyword",
                    nonce: "' . wp_create_nonce('sa_ajax_nonce') . '",
                    index: index
                }, function(res) {
                    if (res.success) {
                        location.reload();
                    } else {
                        alert("Error deleting keyword");
                    }
                });
            }
        });
    });
    </script>
</div>';

// Save template to file for dynamic loading
file_put_contents(plugin_dir_path(__FILE__) . 'settings.php', $sa_template);

?>