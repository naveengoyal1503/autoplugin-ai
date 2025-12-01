<?php
/*
Plugin Name: WP Affiliate Link Manager
Description: Automatically convert keywords into affiliate links and manage all links from a single dashboard.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Affiliate_Link_Manager.php
*/

if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
function wpaflm_admin_menu() {
    add_menu_page(
        'Affiliate Link Manager',
        'Affiliate Links',
        'manage_options',
        'wpaflm',
        'wpaflm_admin_page',
        'dashicons-admin-links'
    );
}
add_action('admin_menu', 'wpaflm_admin_menu');

// Admin page
function wpaflm_admin_page() {
    if (isset($_POST['wpaflm_save_links'])) {
        $links = array();
        if (isset($_POST['keyword']) && isset($_POST['url'])) {
            for ($i = 0; $i < count($_POST['keyword']); $i++) {
                $links[] = array(
                    'keyword' => sanitize_text_field($_POST['keyword'][$i]),
                    'url' => esc_url($_POST['url'][$i])
                );
            }
        }
        update_option('wpaflm_links', $links);
    }
    $links = get_option('wpaflm_links', array());
    ?>
    <div class="wrap">
        <h1>Affiliate Link Manager</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Keyword</th>
                    <th>Affiliate URL</th>
                </tr>
                <?php for ($i = 0; $i < 10; $i++): ?>
                <tr>
                    <td><input type="text" name="keyword[]" value="<?php echo isset($links[$i]['keyword']) ? $links[$i]['keyword'] : ''; ?>" style="width: 100%;" /></td>
                    <td><input type="url" name="url[]" value="<?php echo isset($links[$i]['url']) ? $links[$i]['url'] : ''; ?>" style="width: 100%;" /></td>
                </tr>
                <?php endfor; ?>
            </table>
            <p class="submit">
                <input type="submit" name="wpaflm_save_links" class="button-primary" value="Save Links" />
            </p>
        </form>
    </div>
    <?php
}

// Auto-link keywords in content
function wpaflm_auto_link($content) {
    $links = get_option('wpaflm_links', array());
    foreach ($links as $link) {
        $keyword = $link['keyword'];
        $url = $link['url'];
        if (!empty($keyword) && !empty($url)) {
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            $replacement = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow">$0</a>';
            $content = preg_replace($pattern, $replacement, $content);
        }
    }
    return $content;
}
add_filter('the_content', 'wpaflm_auto_link');

// Add shortcode for manual linking
function wpaflm_shortcode($atts) {
    $atts = shortcode_atts(array(
        'keyword' => '',
        'url' => ''
    ), $atts);
    if (!empty($atts['keyword']) && !empty($atts['url'])) {
        return '<a href="' . esc_url($atts['url']) . '" target="_blank" rel="nofollow">' . esc_html($atts['keyword']) . '</a>';
    }
    return '';
}
add_shortcode('wpaflm', 'wpaflm_shortcode');
