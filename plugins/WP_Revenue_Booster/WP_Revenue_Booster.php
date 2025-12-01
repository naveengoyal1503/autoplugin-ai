<?php
/*
Plugin Name: WP Revenue Booster
Description: Maximize revenue by rotating and optimizing affiliate links, ads, and sponsored content.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'inject_content'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_revenue_settings', array($this, 'save_settings'));
        add_action('wp_ajax_nopriv_save_revenue_settings', array($this, 'save_settings'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }

    public function inject_content() {
        $settings = get_option('wp_revenue_booster_settings', array());
        $content = '';

        if (!empty($settings['affiliate_links']) && rand(1, 100) <= 30) {
            $links = $settings['affiliate_links'];
            $link = $links[array_rand($links)];
            $content .= '<div class="revenue-boost-affiliate"><a href="' . esc_url($link['url']) . '" target="_blank">' . esc_html($link['text']) . '</a></div>';
        }

        if (!empty($settings['ads']) && rand(1, 100) <= 20) {
            $ads = $settings['ads'];
            $ad = $ads[array_rand($ads)];
            $content .= '<div class="revenue-boost-ad">' . $ad['code'] . '</div>';
        }

        if (!empty($settings['sponsored']) && rand(1, 100) <= 10) {
            $sponsored = $settings['sponsored'];
            $item = $sponsored[array_rand($sponsored)];
            $content .= '<div class="revenue-boost-sponsored">' . $item['content'] . '</div>';
        }

        if ($content) {
            echo '<div class="wp-revenue-booster-container">' . $content . '</div>';
        }
    }

    public function admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        $settings = get_option('wp_revenue_booster_settings', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="javascript:void(0);" id="revenue-booster-form">
                <table class="form-table">
                    <tr>
                        <th><label>Affiliate Links</label></th>
                        <td>
                            <div id="affiliate-links">
                                <?php foreach ($settings['affiliate_links'] as $link): ?>
                                    <p><input type="text" name="affiliate_url[]" value="<?php echo esc_attr($link['url']); ?>" placeholder="URL" style="width: 40%" />
                                    <input type="text" name="affiliate_text[]" value="<?php echo esc_attr($link['text']); ?>" placeholder="Link Text" style="width: 40%" /></p>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="addAffiliateLink()">Add Link</button>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Ad Codes</label></th>
                        <td>
                            <div id="ad-codes">
                                <?php foreach ($settings['ads'] as $ad): ?>
                                    <p><textarea name="ad_code[]" placeholder="Ad Code" style="width: 80%"><?php echo esc_textarea($ad['code']); ?></textarea></p>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="addAdCode()">Add Ad</button>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Sponsored Content</label></th>
                        <td>
                            <div id="sponsored-content">
                                <?php foreach ($settings['sponsored'] as $item): ?>
                                    <p><textarea name="sponsored_content[]" placeholder="Sponsored Content" style="width: 80%"><?php echo esc_textarea($item['content']); ?></textarea></p>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="addSponsoredContent()">Add Content</button>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" class="button-primary" value="Save Settings" />
                </p>
            </form>
        </div>
        <script>
            function addAffiliateLink() {
                jQuery('#affiliate-links').append('<p><input type="text" name="affiliate_url[]" placeholder="URL" style="width: 40%" /><input type="text" name="affiliate_text[]" placeholder="Link Text" style="width: 40%" /></p>');
            }
            function addAdCode() {
                jQuery('#ad-codes').append('<p><textarea name="ad_code[]" placeholder="Ad Code" style="width: 80%"></textarea></p>');
            }
            function addSponsoredContent() {
                jQuery('#sponsored-content').append('<p><textarea name="sponsored_content[]" placeholder="Sponsored Content" style="width: 80%"></textarea></p>');
            }
            jQuery('#revenue-booster-form').on('submit', function() {
                var data = {
                    action: 'save_revenue_settings',
                    affiliate_url: jQuery('input[name^="affiliate_url"]').map(function(){return this.value;}).get(),
                    affiliate_text: jQuery('input[name^="affiliate_text"]').map(function(){return this.value;}).get(),
                    ad_code: jQuery('textarea[name^="ad_code"]').map(function(){return this.value;}).get(),
                    sponsored_content: jQuery('textarea[name^="sponsored_content"]').map(function(){return this.value;}).get(),
                };
                jQuery.post(ajaxurl, data, function(response) {
                    alert('Settings saved!');
                });
            });
        </script>
        <?php
    }

    public function save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $settings = array();
        $settings['affiliate_links'] = array();
        if (!empty($_POST['affiliate_url'])) {
            foreach ($_POST['affiliate_url'] as $i => $url) {
                if (!empty($url)) {
                    $settings['affiliate_links'][] = array(
                        'url' => esc_url_raw($url),
                        'text' => sanitize_text_field($_POST['affiliate_text'][$i])
                    );
                }
            }
        }
        $settings['ads'] = array();
        if (!empty($_POST['ad_code'])) {
            foreach ($_POST['ad_code'] as $code) {
                if (!empty($code)) {
                    $settings['ads'][] = array('code' => wp_kses_post($code));
                }
            }
        }
        $settings['sponsored'] = array();
        if (!empty($_POST['sponsored_content'])) {
            foreach ($_POST['sponsored_content'] as $content) {
                if (!empty($content)) {
                    $settings['sponsored'][] = array('content' => wp_kses_post($content));
                }
            }
        }
        update_option('wp_revenue_booster_settings', $settings);
        wp_die('Settings saved');
    }
}

new WP_Revenue_Booster();
