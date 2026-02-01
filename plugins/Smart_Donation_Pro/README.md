# Smart Donation Pro

A powerful, lightweight WordPress plugin to collect one-time donations via Stripe with progress bars, custom amounts, and goal tracking. Perfect for bloggers, creators, and non-profits.

## Features
- **Easy Donation Forms**: Use shortcode `[smart_donation]` to embed customizable forms.
- **Progress Bar**: Visual goal tracking with real-time updates.
- **Custom Amounts**: Preset buttons (e.g., $10, $25, $50) or custom input.
- **Stripe Integration**: Secure payments (requires free Stripe account).
- **Admin Dashboard**: Set Stripe keys, goals, and thank-you pages.
- **Mobile Responsive**: Works on all devices.
- **Freemium Ready**: Extend with pro features like recurring donations.

## Installation
1. Upload the `smart-donation-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Pro** to configure Stripe keys and goal.
4. Add `[smart_donation]` shortcode to any page/post.

## Setup
1. Sign up for a [Stripe account](https://stripe.com) and get your Publishable and Secret keys.
2. Enter keys in plugin settings.
3. Set a donation goal (e.g., $1000).
4. Optionally select a thank-you page.
5. Create assets: Add `assets/script.js` and `assets/style.css` in plugin dir (use provided templates below).

### Required Assets
**assets/script.js**:
javascript
var stripe = Stripe(sdp_ajax.publishable_key);
var elements = stripe.elements();
var card = elements.create('card');
card.mount('#sdp-card-element');

jQuery('.sdp-amount-btn, #sdp-custom-amount').on('click change', function() {
    jQuery('#sdp-custom-amount').val(jQuery(this).data('amount') || jQuery(this).val());
});

jQuery('#sdp-form').on('submit', function(e) {
    e.preventDefault();
    stripe.createToken(card).then(function(result) {
        if (result.error) {
            jQuery('#sdp-message').html('<div class="error">' + result.error.message + '</div>');
        } else {
            jQuery.post(sdp_ajax.ajax_url, {
                action: 'sdp_process_donation',
                nonce: sdp_ajax.nonce,
                stripeToken: result.token.id,
                amount: jQuery('#sdp-custom-amount').val()
            }, function(response) {
                if (response.success) {
                    window.location = response.data.redirect;
                } else {
                    jQuery('#sdp-message').html('<div class="error">' + response.data + '</div>');
                }
            });
        }
    });
});


**assets/style.css**:
css
.sdp-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
.sdp-goal-bar { background: #f0f0f0; height: 20px; border-radius: 10px; margin-bottom: 20px; overflow: hidden; position: relative; }
.sdp-progress { height: 100%; background: #0073aa; transition: width 0.3s; }
.sdp-goal-text { position: absolute; top: 0; left: 10px; font-size: 12px; line-height: 20px; }
.sdp-amounts { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
.sdp-amount-btn { flex: 1; padding: 10px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; }
.sdp-amount-btn:hover { background: #005a87; }
#sdp-custom-amount { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
#sdp-card-element { padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; background: white; }
.sdp-submit-btn { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
.sdp-submit-btn:hover { background: #218838; }
#sdp-message { margin-top: 10px; padding: 10px; border-radius: 4px; }
#sdp-message.error { background: #f8d7da; color: #721c24; }
@media (max-width: 600px) { .sdp-amounts { flex-direction: column; } }


## Usage
- Embed `[smart_donation amount="5,10,25,50" button_text="Support Us!"]`.
- Track total donations in settings.
- Donations redirect to thank-you page post-payment.

## Pro Version
Upgrade for recurring Stripe subscriptions, email notifications, analytics, and more.

## Support
Contact support@example.com or visit plugin URI.