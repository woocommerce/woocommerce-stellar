=== WooCommerce Stellar ===
Contributors: prospress, thenbrent, mattallan, sebd86
Tags: ecommerce, e-commerce, woocommerce, stellar, bitcoin, cryptocurrency, crypto-currency
Requires at least: 4.0
Tested up to: 4.0
Stable tag: 1.0.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Accept payment for WooCommerce orders via Stellar (both the currency and the protocol).


== Description ==

[Stellar](https://stellar.org/) is a protocol for sending and receiving money in any pair of currencies.

With the WooCommerce Stellar extension, you can accept payment for orders in your WooCommerce store using the Stellar protocol (and currency).

[vimeo https://vimeo.com/109090976]

After installing the extension, you will be able to sell in any currency which your Stellar account can accept.

You can add new currencies to your account at [Stellar.org](https://launch.stellar.org/).

The Stellar protocol also has a built in currency, named Stellar, with the currency code **STR**. The WooCommerce Stellar extension adds **STR** as an option for your store's currency.

To sell items in **STR**:

1. Go to: **WooCommerce > Settings > General** administration screen
2. In the **Currency** field, search for _Stellar_
3. Select **Stellar (STR)**
4. Save the settings

But you don't have to sell in Stellar to use Stellar, you can (theoretically) sell in any currency, that's what's so stellar about Stellar!

= Demo Store =

Want to try it out?

Purchase a Stellar collector item from the [Prospress Stellar Store](http://prospress.com/stellar-store/).

The proceeds of every sale are donated to the Stellar foundation.

= Contribute =

Want to get involved with the WooCommerce Stellar extension's development, join us on [GitHub](http://github.com/Prospress/woocommerce-stellar).

= Disclaimers =

The Woo logo and the WooCommerce name are trademarks of Automattic Inc. No affiliation or endorsement of this plugin by Automattic is intended or implied.

== Installation ==

1. Upload the plugin's files to the `/wp-content/plugins/` directory
1. Activate the plugin through the **Plugins** menu in WordPress
1. Visit the **WooCommerce > Settings > Checkout > Stellar** administration screen
1. Select **Enable Stellar**.
1. Enter your **Stellar Address**
1. Save your settings

You're ready to start selling via Stellar!

**Optional:** set your store's currency to **Stellar (STR)**.


== Frequently Asked Questions ==

= Why is my Stellar account showing as invalid? =

Your Stellar account may display as invalid if:

1. you have entered an incorrect Stellar address
1. your account has a balance of 0 Stellar - to avoid spam accounts, accounts must have a minimum of 20 Stellar

= Why isn't Stellar displaying as a payment method? =

When you enter you account address, the extension will check your account to see which currencies it accepts.

If your store's currency is not in the set of currencies your Stellar account accepts, then the extension will not display Stellar as a payment method on checkout.

To have it display, you can either:

1. change your store's currency to **Stellar (STR)**
1. add the store's currency to your Stellar account

= What currencies can I accept via Stellar? =

Stellar will attempt to find a path between the currency/currencies your customer holds a balance in, and the currency you are selling in.

In theory, this means Stellar can be used to sell in _any_ currency.

In practice, your customers may only be able to pay in currencies in which **they already hold a balance**. Because the Stellar network is still young, the available paths between currencies are limited. You may have trouble selling in less popular currencies, like AUD or NZD, to customers who do not hold a balance in this currency. Rest assured, things are moving fast and it won't be too long before your customers in Hanoi can pay in Dong (VND), while you'll receive it in the mighty greenback (USD).

= How can I add new currencies to my Stellar account? =

1. Login to your [Stellar Dashboard](https://launch.stellar.org/)
2. Click the **add a currency** link (displayed under your balance)
3. Enter the full URL of a Stellar gateway which supports multiple currencies ([list of gateways](https://github.com/stellar/docs/blob/master/docs/gateway-list.md))
4. Add the gateway to your account

Refer to the Stellar docs article for more information on [adding currencies to your account](https://github.com/stellar/docs/blob/master/docs/Adding-Multiple-Currencies.md).

= How can I sell with Stellar from multiple WooCommerce Stores? =

To sell from multiple WooCommerce stores, you will need to create a new Stellar account for each store.

Because the Stellar protocol only accepts numerical Destination Tags, there is no way to prefix them for different stores (e.g. `JFK-12358` or `SFO-1321`).

This restriction makes it unfeasible to use the same Stellar account on more than one site. The good news is, creating a Stellar account is both easy and free. You'll just need to seed each account with a few hundred Stellar before you can use them to accept payments.

= What is this pesky notice about Destination Tags? =

The WooCommerce Stellar extension uses Destination Tags to match an order with a payment.

Stellar accounts can be configured to require Destination Tags on all incoming payments or accept payments without a corresponding Destination Tag.

If a Stellar account does not require Destination Tags, customers are able to make a payment that **can not** be matched with an order. This means a store manager will need to manually find the payment in their account and mark the order in WooCommerce as paid.

When a Stellar address is saved on **WooCommerce > Settings > Checkout > Stellar** administration screen, the extension checks the account to see if it requires Destination Tags.

If the account does not require Destination Tags, the WooCommerce Stellar extension encourages you to configure your account to require Destination Tags and avoid transactions that can not be automatically matched.

= The notice asks for my Secret Key. Isn't it dangerous to share my Secret Key? =

Yes!

The Destination Tags notice provides a convenient way to configure your account to require Destination Tags (as there is no graphical interface available via the Stellar client to set this, yet).

In order to configure your account, the extension needs your Stellar Secret Key.

The extension tries to be as secure as possible with your sensitive key. It sends it directly from your browser to Stellar.org. It does not send your key to your web server, or any other web server, and it definitely does not store your key anywhere.

This greatly reduces the risk of your secret key being stolen by an attacker; however, if you have malicious code running in your browser, for example, some JavaScript served by a hacked WordPress plugin, that code will be able to steal your Secret Key (and do all sorts of other nasty things with your site).

If you would prefer not to enter your Secret Key in your browser, you can configure your account manually using the [AccountSet API command](https://www.stellar.org/api/#api-accountset).

For example, you can use the following from Mac OS X's terminal application (after entering your secret key and account address):

`
curl -X POST https://live.stellar.org:9002 -d '
{
  "method": "submit",
  "params": [
    {
      "secret": "",
      "tx_json": {
        "TransactionType": "AccountSet",
        "Account": "",
        "SetFlag": 1
      }
    }
  ]
}'
`

= Where can I find my Stellar account's Secret Key? = 

1. Login to your [Stellar Dashboard](https://launch.stellar.org/)
2. Click your username in the header menu
3. Scroll to the bottom of the **Settings** page
4. Click **Reveal** next to the Secret Key setting

For a visual guide, checkout the [screenshots](https://wordpress.org/plugins/woocommerce-stellar/screenshots/) section.

= I'm having trouble, where can I get help? =

If you've found a bug in the extension or have a feature request, please [open a new issue](https://github.com/Prospress/woocommerce-stellar/issues/new) on the [WooCommerce Stellar GitHub repository](https://github.com/Prospress/woocommerce-stellar/).

If you have a question, [post a topic in the WordPress.org WooCommerce Stellar Forum](https://wordpress.org/support/plugin/woocommerce-stellar/). We may or may not reply, but would appreciate help from other users of the extension in handling support - while our code is free our time is not.


== Screenshots ==

1. Stellar WooCommerce Gateway Settings
1. Stellar Payment Method on Checkout
1. Order Confirmation After Payment via Stellar
1. Stellar WooCommerce Gateway Settings Screen with Destination Tag Notice
1. Stellar.org Settings Page with Secret Key


== Changelog ==

= 1.0.2 =
* Tweak: remove unused error log call
* Tweak: update banners and icons to include Prospress logo
* Tweak: add trademark disclaimers

= 1.0.1 =
* Fix: closing PHP tag in stellar-instructions.php template breaking RSS feeds and XML sitemaps

= 1.0 =
* Initial release. To the moon!

== Upgrade Notice ==

= 1.0.1 =
Upgrade to fix issues with RSS feeds and XML sitemaps.