/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=GeoAffiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: GeoAffiliate Link Optimizer
 * Description: Auto-manage affiliate links with geolocation targeting, schedule replacements, and category organization.
 * Version: 1.0
 * Author: Perplexity AI
 */

if (!defined('ABSPATH')) exit;

class GeoAffiliate_Link_Optimizer {
    private $option_name = 'geoaffiliate_links';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_post_save_geoaffiliate_links', [$this, 'save_links']);
        add_filter('the_content', [$this, 'replace_links_in_content']);
        add_shortcode('geoaffiliate_links', [$this, 'shortcode_display_links']);
    }

    public function add_admin_menu() {
        add_menu_page('GeoAffiliate Links', 'GeoAffiliate Links', 'manage_options', 'geoaffiliate-links', [$this, 'admin_page']);
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $links = get_option($this->option_name, []);
        ?>
        <div class="wrap">
            <h1>GeoAffiliate Link Optimizer</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="save_geoaffiliate_links">
                <?php wp_nonce_field('save_geoaffiliate', 'geoaffiliate_nonce'); ?>
                <table class="widefat" style="max-width: 100%; margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th>Keyword/Phrase</th>
                            <th>Affiliate URL</th>
                            <th>Geolocation (Country code, comma separated)</th>
                            <th>Category</th>
                            <th>Schedule From (YYYY-MM-DD)</th>
                            <th>Schedule To (YYYY-MM-DD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($links)) {
                            $links = [ ['keyword'=>'','url'=>'','geo'=>'','category'=>'','date_from'=>'','date_to'=>''] ];
                        }
                        foreach ($links as $idx => $link):
                        ?>
                        <tr>
                            <td><input type="text" name="links[<?php echo $idx; ?>][keyword]" value="<?php echo esc_attr($link['keyword']); ?>" required></td>
                            <td><input type="url" name="links[<?php echo $idx; ?>][url]" value="<?php echo esc_url($link['url']); ?>" required></td>
                            <td><input type="text" name="links[<?php echo $idx; ?>][geo]" value="<?php echo esc_attr($link['geo']); ?>" placeholder="US,CA"></td>
                            <td><input type="text" name="links[<?php echo $idx; ?>][category]" value="<?php echo esc_attr($link['category']); ?>"></td>
                            <td><input type="date" name="links[<?php echo $idx; ?>][date_from]" value="<?php echo esc_attr($link['date_from']); ?>"></td>
                            <td><input type="date" name="links[<?php echo $idx; ?>][date_to]" value="<?php echo esc_attr($link['date_to']); ?>"></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="add-row">Add Link</button></p>
                <p><input type="submit" class="button-primary" value="Save Links"></p>
            </form>
        </div>
        <script>
        document.getElementById('add-row').addEventListener('click', function() {
            const tableBody = document.querySelector('table.widefat tbody');
            const rowCount = tableBody.rows.length;
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td><input type="text" name="links[${rowCount}][keyword]" required></td>
                <td><input type="url" name="links[${rowCount}][url]" required></td>
                <td><input type="text" name="links[${rowCount}][geo]" placeholder="US,CA"></td>
                <td><input type="text" name="links[${rowCount}][category]"></td>
                <td><input type="date" name="links[${rowCount}][date_from]"></td>
                <td><input type="date" name="links[${rowCount}][date_to]"></td>
            `;
            tableBody.appendChild(newRow);
        });
        </script>
        <?php
    }

    public function save_links() {
        // Verify nonce
        if (!isset($_POST['geoaffiliate_nonce']) || !wp_verify_nonce($_POST['geoaffiliate_nonce'], 'save_geoaffiliate')) {
            wp_die('Nonce verification failed');
        }
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $posted_links = isset($_POST['links']) && is_array($_POST['links']) ? $_POST['links'] : [];
        $clean_links = [];
        foreach ($posted_links as $link) {
            $keyword = sanitize_text_field($link['keyword']);
            $url = esc_url_raw($link['url']);
            $geo = sanitize_text_field($link['geo']);
            $category = sanitize_text_field($link['category']);
            $date_from = sanitize_text_field($link['date_from']);
            $date_to = sanitize_text_field($link['date_to']);
            if ($keyword && $url) {
                $clean_links[] = [
                    'keyword' => $keyword,
                    'url' => $url,
                    'geo' => $geo,
                    'category' => $category,
                    'date_from' => $date_from,
                    'date_to' => $date_to
                ];
            }
        }

        update_option($this->option_name, $clean_links);
        wp_redirect(admin_url('admin.php?page=geoaffiliate-links&updated=true'));
        exit;
    }

    public function replace_links_in_content($content) {
        if (is_admin()) return $content; // Only frontend

        $links = get_option($this->option_name, []);
        if (empty($links)) return $content;

        $user_country = $this->get_user_country();
        $today = date('Y-m-d');

        foreach ($links as $link) {
            // Check schedule
            if ($link['date_from'] && $today < $link['date_from']) continue;
            if ($link['date_to'] && $today > $link['date_to']) continue;

            // Check geo
            if ($link['geo']) {
                $allowed_countries = array_map('strtoupper', array_map('trim', explode(',', $link['geo'])));
                if ($user_country && !in_array($user_country, $allowed_countries)) continue;
            }

            // Replace keyword with affiliate link if keyword exists
            $keyword_escaped = preg_quote($link['keyword'], '/');
            $replacement = '<a href="' . esc_url($link['url']) . '" target="_blank" rel="nofollow noopener">' . esc_html($link['keyword']) . '</a>';
            // Replace first occurrence only
            $content = preg_replace('/\b' . $keyword_escaped . '\b/', $replacement, $content, 1);
        }

        return $content;
    }

    private function get_user_country() {
        // Use a simple IP to country lookup via an external API
        if (isset($_COOKIE['geoaffiliate_country'])) {
            return sanitize_text_field($_COOKIE['geoaffiliate_country']);
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        $country = '';
        if ($ip) {
            $response = wp_remote_get("https://ipapi.co/{$ip}/country/");
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                if ($body && strlen($body) === 2) {
                    $country = strtoupper(trim($body));
                    setcookie('geoaffiliate_country', $country, time()+3600*24*7, COOKIEPATH, COOKIE_DOMAIN);
                }
            }
        }
        return $country;
    }

    public function shortcode_display_links($atts) {
        $atts = shortcode_atts(['category'=>''], $atts);
        $links = get_option($this->option_name, []);
        if ($atts['category']) {
            $links = array_filter($links, function($link) use ($atts) {
                return strtolower($link['category']) === strtolower($atts['category']);
            });
        }
        if (empty($links)) return '<p>No affiliate links found.</p>';

        $out = '<ul class="geoaffiliate-links">';
        foreach ($links as $link) {
            $out .= '<li><a href="' . esc_url($link['url']) . '" target="_blank" rel="nofollow noopener">' . esc_html($link['keyword']) . '</a></li>';
        }
        $out .= '</ul>';
        return $out;
    }
}

new GeoAffiliate_Link_Optimizer();
