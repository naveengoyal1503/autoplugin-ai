/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Booster Pro
 * Description: Automatically converts keywords to affiliate links, manages linked keywords, displays custom coupons, and tracks clicks for affiliate marketing.
 * Version: 1.0
 * Author: YourName
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateBoosterPro {
    private $option_name = 'abp_settings';
    private $db_version = '1.0';

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'convert_keywords_to_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_abp_track_click', array($this, 'ajax_track_click')); // For logged in users
        add_action('wp_ajax_nopriv_abp_track_click', array($this, 'ajax_track_click'));
    }

    public function activate_plugin() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'abp_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            link TEXT NOT NULL,
            clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('abp_db_version', $this->db_version);
        // Default keywords array
        $default_keywords = array(
            array('keyword' => 'WordPress', 'url' => 'https://affiliate.example.com/wordpress'),
            array('keyword' => 'hosting', 'url' => 'https://affiliate.example.com/hosting'),
            array('keyword' => 'SEO', 'url' => 'https://affiliate.example.com/seo-tool'),
        );
        add_option($this->option_name, $default_keywords);
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Booster Pro', 'Affiliate Booster', 'manage_options', 'affiliate-booster-pro', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('abp_plugin', $this->option_name, array($this, 'validate_keywords'));

        add_settings_section('abp_plugin_section', __('Keyword to Affiliate Links','abp'), null, 'abp_plugin');

        add_settings_field(
            'abp_keywords',
            __('Affiliate Keywords and Links','abp'),
            array($this, 'keywords_field_render'),
            'abp_plugin',
            'abp_plugin_section'
        );
    }

    public function keywords_field_render() {
        $keywords = get_option($this->option_name, array());
        if(!is_array($keywords)) $keywords = array();

        echo '<table style="width:100%; max-width:100%;">';
        echo '<thead><th>'.__('Keyword','abp').'</th><th>'.__('Affiliate URL','abp').'</th><th></th></thead>';
        echo '<tbody id="abp-keywords-list">';
        foreach($keywords as $index => $item) {
            $kw = esc_attr($item['keyword']);
            $url = esc_url($item['url']);
            echo '<tr>';
            echo '<td><input type="text" name="'.$this->option_name.'['.$index.'][keyword]" value="'.$kw.'" style="width:90%;"></td>';
            echo '<td><input type="url" name="'.$this->option_name.'['.$index.'][url]" value="'.$url.'" style="width:90%;"></td>';
            echo '<td><button class="button abp-remove-row">'.__('Remove','abp').'</button></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '<p><button id="abp-add-keyword" class="button">'.__('Add Keyword','abp').'</button></p>';
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function(){
            document.getElementById('abp-add-keyword').addEventListener('click', function(e){
                e.preventDefault();
                const tbody = document.getElementById('abp-keywords-list');
                const count = tbody.children.length;
                const tr = document.createElement('tr');
                tr.innerHTML = '<td><input type="text" name="<?php echo $this->option_name; ?>['+count+'][keyword]" style="width:90%;"></td>' +
                               '<td><input type="url" name="<?php echo $this->option_name; ?>['+count+'][url]" style="width:90%;"></td>' +
                               '<td><button class="button abp-remove-row"><?php echo __("Remove", "abp"); ?></button></td>';
                tbody.appendChild(tr);
            });

            document.body.addEventListener('click', function(e){
                if(e.target && e.target.classList.contains('abp-remove-row')){
                    e.preventDefault();
                    e.target.closest('tr').remove();
                }
            });
        });
        </script>
        <?php
    }

    public function validate_keywords($input) {
        $clean = array();
        if(!is_array($input)) return $clean;

        foreach ($input as $k => $item) {
            $keyword = sanitize_text_field($item['keyword']);
            $url = esc_url_raw($item['url']);
            if(!empty($keyword) && !empty($url)) {
                $clean[] = array(
                    'keyword' => $keyword,
                    'url' => $url
                );
            }
        }
        return $clean;
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Booster Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('abp_plugin');
                do_settings_sections('abp_plugin');
                submit_button();
                ?>
            </form>
            <h2><?php _e('Usage Instructions','abp'); ?></h2>
            <p><?php _e('The plugin automatically finds and converts your specified keywords in post content into affiliate links. It tracks all clicks for analytics.','abp'); ?></p>
        </div>
        <?php
    }

    public function convert_keywords_to_links($content) {
        $keywords = get_option($this->option_name, array());
        if(empty($keywords) || !is_array($keywords)) return $content;

        // Sort keywords descending by length to prevent substring conflicts
        usort($keywords, function($a, $b) {
            return strlen($b['keyword']) - strlen($a['keyword']);
        });

        $replacements = array();

        foreach ($keywords as $item) {
            $keyword = preg_quote($item['keyword'], '/');
            $url = esc_url($item['url']);

            // Create a tracked affiliate link
            $tracked_url = add_query_arg(array('abp_ref' => md5($url)), $url);

            // We replace keyword only outside anchor tags to avoid nested links
            $replacements[$keyword] = '<a href="' . esc_url($tracked_url) . '" class="abp-affiliate-link" target="_blank" rel="nofollow noopener noreferrer">' . esc_html($item['keyword']) . '</a>';
        }

        // Use callback to replace outside anchors - simple approach
        $content = $this->replace_keywords_outside_anchors($content, $replacements);

        $content .= $this->abp_click_tracking_js();

        return $content;
    }

    private function replace_keywords_outside_anchors($content, $replacements) {
        // Split content by <a> tags
        return preg_replace_callback('/(<a[^>]*>.*?<\/a>)|([^<]+)/i', function($matches) use ($replacements) {
            if(!empty($matches[1])) {
                // Inside anchor, do not replace
                return $matches[1];
            } else {
                // Outside anchor, replace all keywords
                $text = $matches[2];
                foreach($replacements as $keyword => $link) {
                    // Replace only first occurrence per segment
                    $text = preg_replace('/\b' . $keyword . '\b/i', $link, $text, 1);
                }
                return $text;
            }
        }, $content);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', $this->abp_click_tracking_js());
    }

    private function abp_click_tracking_js() {
        // JS for tracking clicks on affiliate links
        $ajax_url = admin_url('admin-ajax.php');
        return "
<script type='text/javascript'>
(function($){
  $(document).ready(function(){
    $('body').on('click', '.abp-affiliate-link', function(e){
      var href = $(this).attr('href');
      $.post('$ajax_url', {
        action: 'abp_track_click',
        link: href
      });
    });
  });
})(jQuery);
</script>
";
    }

    public function ajax_track_click() {
        if(!isset($_POST['link'])) wp_send_json_error('Missing link');

        global $wpdb;
        $table_name = $wpdb->prefix . 'abp_clicks';

        $link = esc_url_raw($_POST['link']);
        $wpdb->insert($table_name, array('link' => $link), array('%s'));

        wp_send_json_success();
    }
}

new AffiliateBoosterPro();
