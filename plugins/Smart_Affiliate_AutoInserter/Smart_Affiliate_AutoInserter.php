/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your WordPress posts and pages to boost revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $affiliate_id;
    private $keywords;

    public function __construct() {
        $this->affiliate_id = get_option('saa_affiliate_id', '');
        $this->keywords = get_option('saa_keywords', array());
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function activate() {
        add_option('saa_affiliate_id', '');
        add_option('saa_keywords', array());
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saa-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (empty($this->affiliate_id) || empty($this->keywords) || is_admin()) {
            return $content;
        }

        foreach ($this->keywords as $keyword => $product_url) {
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match($pattern, $content)) {
                $link = '<a href="' . esc_url($product_url . '?tag=' . $this->affiliate_id) . '" target="_blank" rel="nofollow noopener" class="saa-affiliate-link">' . $keyword . '</a>';
                $content = preg_replace($pattern, $link, $content, 1);
            }
        }
        return $content;
    }

    public function settings_init() {
        register_setting('saa_plugin', 'saa_affiliate_id');
        register_setting('saa_plugin', 'saa_keywords');
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'saa',
            array($this, 'options_page')
        );
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('saa_plugin');
                do_settings_sections('saa_plugin');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Amazon Affiliate ID</th>
                        <td><input type="text" name="saa_affiliate_id" value="<?php echo esc_attr($this->affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Keywords (JSON: {"keyword":"product_url"})</th>
                        <td>
                            <textarea name="saa_keywords" rows="10" cols="50"><?php echo esc_textarea(json_encode($this->keywords)); ?></textarea>
                            <p class="description">Enter keywords and Amazon product URLs in JSON format, e.g. {"best laptop":"https://amazon.com/dp/B08N5WRWNW"}</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock AI suggestions, analytics, and more for $29/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }
}

new SmartAffiliateAutoInserter();

// Pro teaser notice
add_action('admin_notices', function() {
    if (!get_option('saa_pro_dismissed')) {
        echo '<div class="notice notice-info"><p>Upgrade <strong>Smart Affiliate AutoInserter</strong> to Pro for AI-powered links and analytics! <a href="https://example.com/pro" target="_blank">Learn More</a> | <a href="?saa_dismiss=1">Dismiss</a></p></div>';
    }
});

if (isset($_GET['saa_dismiss'])) {
    update_option('saa_pro_dismissed', 1);
    wp_redirect(admin_url('options-general.php?page=saa'));
    exit;
}

// Create assets dir and dummy JS
$assets_dir = plugin_dir_path(__FILE__) . 'assets/';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
}
if (!file_exists($assets_dir . 'script.js')) {
    file_put_contents($assets_dir . 'script.js', '// Smart Affiliate AutoInserter JS\njQuery(document).ready(function($) { console.log("SAA loaded"); });');
}
?>