<?php
/*
Plugin Name: Auto Affiliate Link Manager
Description: Converts specified keywords in posts to cloaked affiliate links automatically.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Auto_Affiliate_Link_Manager.php
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AutoAffiliateLinkManager {
    private $option_name = 'aaflm_keywords';

    public function __construct() {
        add_action('admin_menu', array($this, 'create_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'replace_keywords_with_links'));
    }

    public function create_admin_menu() {
        add_options_page(
            'Auto Affiliate Link Manager',
            'Affiliate Link Manager',
            'manage_options',
            'aaflm-settings',
            array($this, 'settings_page')
        );
    }

    public function register_settings() {
        register_setting('aaflm_settings_group', $this->option_name, array($this, 'sanitize_keywords'));
    }

    public function sanitize_keywords($input) {
        if (!is_array($input)) {
            return array();
        }
        $clean = array();
        foreach ($input as $keyword => $link) {
            $k = sanitize_text_field($keyword);
            $l = esc_url_raw($link);
            if ($k && $l) {
                $clean[$k] = $l;
            }
        }
        return $clean;
    }

    public function settings_page() {
        $keywords = get_option($this->option_name, array());
        ?>
        <div class="wrap">
            <h1>Auto Affiliate Link Manager Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('aaflm_settings_group'); ?>
                <table class="form-table" id="keyword-table">
                    <thead>
                        <tr><th>Keyword</th><th>Affiliate URL</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($keywords)) : ?>
                        <?php foreach ($keywords as $keyword => $url) : ?>
                        <tr>
                            <td><input type="text" name="aaflm_keywords[<?php echo esc_attr($keyword); ?>]" value="<?php echo esc_attr($keyword); ?>" required /></td>
                            <td><input type="url" name="aaflm_keywords[<?php echo esc_attr($keyword); ?>]" value="<?php echo esc_attr($url); ?>" required /></td>
                            <td><button class="button remove-row">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="add-keyword">Add New Keyword</button></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        document.getElementById('add-keyword').addEventListener('click', function() {
            var tbody = document.querySelector('#keyword-table tbody');
            var newRow = document.createElement('tr');
            newRow.innerHTML = '<td><input type="text" name="aaflm_keywords[newkeyword]" value="" required /></td><td><input type="url" name="aaflm_keywords[newkeyword_url]" value="" required /></td><td><button class="button remove-row">Remove</button></td>';
            tbody.appendChild(newRow);
        });

        document.querySelector('#keyword-table').addEventListener('click', function(e) {
            if(e.target && e.target.classList.contains('remove-row')) {
                e.preventDefault();
                var row = e.target.closest('tr');
                if(confirm('Remove this keyword?')) {
                    row.remove();
                }
            }
        });
        </script>
        <?php
    }

    public function replace_keywords_with_links($content) {
        $keywords = get_option($this->option_name, array());
        if (empty($keywords)) {
            return $content;
        }

        // Sort keywords by length to avoid nested replacements
        uksort($keywords, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($keywords as $keyword => $url) {
            $escaped_keyword = preg_quote($keyword, '/');
            $pattern = '/(?<!<a[^>]*?>)(?<!<[^>]*?)\b' . $escaped_keyword . '\b(?![^<]*?<\/a>)(?![^>]*?>)/i';
            $replacement = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener noreferrer">' . $keyword . '</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }
}

new AutoAffiliateLinkManager();
