/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak affiliate links, track clicks, and optimize conversions with smart redirects.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCloakerPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->load_textdomain();
        add_shortcode('afflink', array($this, 'afflink_shortcode'));
        add_rewrite_rule('^go/([^/]+)/?', 'index.php?aff_go=$matches[1]', 'top');
        add_filter('query_vars', array($this, 'query_vars'));
        add_action('template_redirect', array($this, 'template_redirect'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', 'sac-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('sac_options', 'sac_links');
        register_setting('sac_options', 'sac_premium');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('sac_links', sanitize_textarea_field($_POST['sac_links']));
        }
        $links = get_option('sac_links', "");
        $premium = get_option('sac_premium', false);
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function afflink_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $links = $this->get_links();
        if (isset($links[$atts['id']])) {
            return '<a href="' . home_url('/go/' . $atts['id'] . '/') . '" class="sac-link">' . $links[$atts['id']]['text'] . '</a>';
        }
        return '';
    }

    public function query_vars($vars) {
        $vars[] = 'aff_go';
        return $vars;
    }

    public function template_redirect() {
        if ($slug = get_query_var('aff_go')) {
            $links = $this->get_links();
            if (isset($links[$slug])) {
                $this->track_click($slug);
                if (get_option('sac_premium') && rand(1, 10) <= 2) { // Simple A/B 20% test
                    wp_redirect($links[$slug]['alt_url']);
                } else {
                    wp_redirect($links[$slug]['url']);
                }
                exit;
            }
        }
    }

    private function get_links() {
        $raw = get_option('sac_links', "");
        $lines = explode("\n", trim($raw));
        $links = array();
        foreach ($lines as $line) {
            if (strpos($line, '|') !== false) {
                list($id, $text, $url, $alt_url) = explode('|', $line, 4);
                $links[trim($id)] = array(
                    'text' => trim($text),
                    'url' => esc_url_raw(trim($url)),
                    'alt_url' => !empty(trim($alt_url)) ? esc_url_raw(trim($alt_url)) : ''
                );
            }
        }
        return $links;
    }

    private function track_click($slug) {
        $clicks = get_option('sac_clicks', array());
        $clicks[$slug] = isset($clicks[$slug]) ? $clicks[$slug] + 1 : 1;
        update_option('sac_clicks', $clicks);
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    private function load_textdomain() {
        load_plugin_textdomain('smart-affiliate-cloaker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

SmartAffiliateCloakerPro::get_instance();

// Embedded admin page
$admin_page_content = '<div class="wrap">
<h1>Smart Affiliate Cloaker Pro</h1>
<form method="post" action="">
<table class="form-table">
<tr>
<th>Add Links</th>
<td><textarea name="sac_links" rows="10" cols="50" placeholder="id|Display Text|Affiliate URL|Alt URL (Premium)">' . esc_textarea(get_option('sac_links', '')) . '</textarea><br>
<small>Format: id|text|url|alt_url (one per line)</small></td>
</tr>
</table>
<?php submit_button(); ?>
</form>
<h2>Stats</h2>
<?php
$clicks = get_option("sac_clicks", array());
if (!empty($clicks)) {
    echo "<ul>";
    foreach ($clicks as $id => $count) {
        echo "<li>ID $id: $count clicks</li>";
    }
    echo "</ul>";
} else {
    echo '<p>No clicks yet.</p>';
}
?>
<p><strong>Upgrade to Pro</strong> for A/B testing, unlimited links, and advanced analytics. <a href="#" onclick="alert('Pro upgrade: $29/year')">Buy Now</a></p>
</div>';
file_put_contents(plugin_dir_path(__FILE__) . 'admin-page.php', "<?php echo '$admin_page_content'; ?>");

// Simple JS file content
$js_content = "jQuery(document).ready(function($) {
    $('.sac-link').on('click', function() {
        // Optional tracking
        console.log('Affiliate link clicked');
    });
});";
file_put_contents(plugin_dir_path(__FILE__) . 'sac-script.js', $js_content);