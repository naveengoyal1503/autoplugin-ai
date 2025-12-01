<?php
/*
Plugin Name: Affiliate Deal Vault
Description: Create and manage exclusive affiliate coupons/deals to increase affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Vault.php
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateDealVault {
    public function __construct() {
        add_action('admin_menu', array($this, 'adv_add_admin_menu'));
        add_action('admin_init', array($this, 'adv_register_settings'));
        add_shortcode('affiliate_deals', array($this, 'adv_display_deals'));
        add_action('wp_enqueue_scripts', array($this, 'adv_enqueue_scripts'));
    }

    public function adv_add_admin_menu() {
        add_menu_page(
            'Affiliate Deal Vault',
            'Affiliate Deal Vault',
            'manage_options',
            'affiliate_deal_vault',
            array($this, 'adv_options_page'),
            'dashicons-cart',
            100
        );
    }

    public function adv_register_settings() {
        register_setting('adv_deals_group', 'adv_deals');
    }

    public function adv_options_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Process form submit
        if (isset($_POST['adv_deals_nonce']) && wp_verify_nonce($_POST['adv_deals_nonce'], 'adv_save_deals')) {
            $deals_data = isset($_POST['adv_deals']) ? $_POST['adv_deals'] : array();

            // Sanitize input
            $sanitized_deals = array();
            foreach ($deals_data as $deal) {
                $title = sanitize_text_field($deal['title']);
                $description = sanitize_textarea_field($deal['description']);
                $affiliate_url = esc_url_raw($deal['affiliate_url']);
                $expiry = sanitize_text_field($deal['expiry']);
                if ($title && $affiliate_url) {
                    $sanitized_deals[] = array(
                        'title' => $title,
                        'description' => $description,
                        'affiliate_url' => $affiliate_url,
                        'expiry' => $expiry,
                    );
                }
            }

            update_option('adv_deals', $sanitized_deals);
            echo '<div class="updated"><p>Deals saved successfully.</p></div>';
        }

        $saved_deals = get_option('adv_deals', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Deal Vault</h1>
            <form method="post" action="">
                <?php wp_nonce_field('adv_save_deals', 'adv_deals_nonce'); ?>

                <table class="widefat" id="adv_deals_table">
                    <thead>
                        <tr>
                            <th>Deal Title</th>
                            <th>Description</th>
                            <th>Affiliate URL</th>
                            <th>Expiry Date (YYYY-MM-DD)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if (!empty($saved_deals)) :
                        foreach ($saved_deals as $index => $deal): ?>
                            <tr>
                                <td><input type="text" name="adv_deals[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($deal['title']); ?>" required></td>
                                <td><textarea name="adv_deals[<?php echo esc_attr($index); ?>][description]" rows="2"><?php echo esc_textarea($deal['description']); ?></textarea></td>
                                <td><input type="url" name="adv_deals[<?php echo esc_attr($index); ?>][affiliate_url]" value="<?php echo esc_url($deal['affiliate_url']); ?>" required></td>
                                <td><input type="date" name="adv_deals[<?php echo esc_attr($index); ?>][expiry]" value="<?php echo esc_attr($deal['expiry']); ?>"></td>
                                <td><button type="button" class="button adv_remove_row">Remove</button></td>
                            </tr>
                        <?php endforeach;
                    else: ?>
                        <tr><td colspan="5">No deals added yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <p><button type="button" class="button button-primary" id="adv_add_row">Add Deal</button></p>
                <p><input type="submit" class="button button-primary" value="Save Deals"></p>
            </form>
        </div>
        <script>
            (function(){
                let table = document.getElementById('adv_deals_table').getElementsByTagName('tbody');
                let addBtn = document.getElementById('adv_add_row');

                function createRow(index){
                    let tr = document.createElement('tr');

                    tr.innerHTML = 
                        '<td><input type="text" name="adv_deals['+index+'][title]" required></td>'+ 
                        '<td><textarea name="adv_deals['+index+'][description]" rows="2"></textarea></td>'+ 
                        '<td><input type="url" name="adv_deals['+index+'][affiliate_url]" required></td>'+ 
                        '<td><input type="date" name="adv_deals['+index+'][expiry]"></td>'+ 
                        '<td><button type="button" class="button adv_remove_row">Remove</button></td>';
                    return tr;
                }

                addBtn.addEventListener('click', function(){
                    let rows = table.querySelectorAll('tr').length;
                    table.appendChild(createRow(rows));
                });

                table.addEventListener('click', function(e){
                    if(e.target && e.target.classList.contains('adv_remove_row')) {
                        e.target.closest('tr').remove();
                    }
                });
            })();
        </script>
        <?php
    }

    public function adv_display_deals() {
        $deals = get_option('adv_deals', array());
        $output = '';
        $today = date('Y-m-d');

        if (empty($deals)) {
            return '<p>No affiliate deals available at the moment.</p>';
        }

        $output .= '<div class="adv-deals-list">';
        foreach ($deals as $deal) {
            $expiry = sanitize_text_field($deal['expiry']);
            if ($expiry && $expiry < $today) {
                // Skip expired deals
                continue;
            }

            $title = esc_html($deal['title']);
            $description = esc_html($deal['description']);
            $url = esc_url($deal['affiliate_url']);

            $output .= '<div class="adv-deal-item" style="border:1px solid #ddd;padding:10px;margin-bottom:10px;">';
            $output .= '<h3><a href="' . $url . '" target="_blank" rel="nofollow noopener">' . $title . '</a></h3>';
            if (!empty($description)) {
                $output .= '<p>' . $description . '</p>';
            }
            $output .= '<p><a class="adv-deal-button" href="' . $url . '" target="_blank" rel="nofollow noopener" style="background:#0073aa;color:#fff;padding:8px 12px;text-decoration:none;border-radius:3px;">Grab This Deal</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }

    public function adv_enqueue_scripts() {
        wp_enqueue_style('adv-style', false);
        $custom_css = ".adv-deal-button:hover { background:#005177 !important; }";
        wp_add_inline_style('adv-style', $custom_css);
    }
}

new AffiliateDealVault();
