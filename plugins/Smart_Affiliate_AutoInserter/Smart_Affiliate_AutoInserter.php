/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages using keyword matching to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $affiliate_id = 'your-amazon-affiliate-id'; // Change to your Amazon Associate ID
    private $keywords = array(
        'laptop' => 'https://www.amazon.com/dp/B0ABC123XYZ?tag=your-amazon-affiliate-id',
        'phone' => 'https://www.amazon.com/dp/B0DEF456UVW?tag=your-amazon-affiliate-id',
        'book' => 'https://www.amazon.com/dp/1234567890?tag=your-amazon-affiliate-id',
        // Add more keyword => product URL pairs here
    );

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'settings_init'));
        } else {
            add_filter('the_content', array($this, 'insert_affiliate_links'));
        }
    }

    public function insert_affiliate_links($content) {
        if (!is_single() || empty($this->keywords)) {
            return $content;
        }

        $pro_version = get_option('saai_pro_version', false);
        $max_links = $pro_version ? 10 : 2;
        $inserted = 0;

        foreach ($this->keywords as $keyword => $url) {
            if ($inserted >= $max_links) break;

            $link = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow sponsored">' . esc_html(ucfirst($keyword)) . '</a> ';
            $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $link . '$0', $content, 1);
            $inserted++;
        }

        // Pro upsell notice in free version
        if (!$pro_version && rand(1, 10) === 1) {
            $content .= '<p><em>Upgrade to Pro for unlimited links and analytics! <a href="https://example.com/pro-upgrade" target="_blank">Get Pro</a></em></p>';
        }

        return $content;
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate Inserter', 'manage_options', 'smart-affiliate', array($this, 'settings_page'));
    }

    public function settings_init() {
        register_setting('saai_settings', 'saai_affiliate_id');
        register_setting('saai_settings', 'saai_keywords');
        register_setting('saai_settings', 'saai_pro_version');
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('saai_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            update_option('saai_keywords', $_POST['keywords']);
            if (isset($_POST['pro_key']) && $_POST['pro_key'] === 'pro-unlock-key') { // Demo pro unlock
                update_option('saai_pro_version', true);
            }
        }
        $aff_id = get_option('saai_affiliate_id', $this->affiliate_id);
        $keywords = get_option('saai_keywords', json_encode($this->keywords));
        $pro = get_option('saai_pro_version', false);
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($aff_id); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Keywords (JSON)</th>
                        <td><textarea name="keywords" rows="10" cols="50"><?php echo esc_textarea($keywords); ?></textarea><br><small>Example: {"laptop":"https://amazon.com/dp/B0ABC?tag=yourid"}</small></td>
                    </tr>
                    <?php if (!$pro) : ?>
                    <tr>
                        <th>Pro Unlock</th>
                        <td><input type="text" name="pro_key" placeholder="Enter pro key" /> <a href="https://example.com/buy-pro">Buy Pro ($49/yr)</a></td>
                    </tr>
                    <?php else : ?>
                    <tr><td colspan="2"><strong>Pro version active! Unlimited links enabled.</strong></td></tr>
                    <?php endif; ?>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function activate() {
        add_option('saai_affiliate_id', $this->affiliate_id);
    }

    public function deactivate() {}
}

new SmartAffiliateAutoInserter();

// Freemium upsell script
add_action('admin_notices', function() {
    if (!get_option('saai_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Smart Affiliate AutoInserter Pro</strong> for unlimited links & analytics! <a href="options-general.php?page=smart-affiliate">Upgrade now</a></p></div>';
    }
});