=== Beanstream for WooCommerce ===
Contributors: vkuberan
Tags: woocommerce, beanstream, payment gateway, credit card, ecommerce, e-commerce, commerce, cart, checkout
Requires at least: 3.5.0
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=vkuberan%40gmail%2ecom&lc=US&item_name=Thank%20you%20for%20your%20donation%20for%20Beanstream%20Gateway&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tested up to: 4.1.0
Stable tag: trunk
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A Payment Gateway for WooCommerce allowing you to take credit card payments using Beanstream.

== Description ==
= What is Beanstream? =
[Beanstream] (https://www.beanstream.com/) allows you to process credit cards through its gateway. This plugin aims to show anyone that they can use Beanstream to take credit card payments in their WooCommerce store without having to write a single line of code. All you have to do is copy Merchand id and API pass key to a settings page and you're done.

= Why Beanstream? =
Beanstream allows you to take credit card payments without having to put a lot of effort into securing your site. Normally you would have to save a customers sensitive credit card information on a seperate server than your site, using different usernames, passwords and limiting access to the point that it's nearly impossible to hack from the outside. It's a process that helps ensure security, but is not easy to do, and if done improperly leaves you open to fines and possibly lawsuits.
If you use this plugin, all you have to do is include an SSL certificate on your site and the hard work is done for you. Credit card breaches are serious, and with this plugin and an SSL certificate, you're protected. Your customers credit card information never hits your servers, it goes from your customers computer straight to Beanstream servers keeping their information safe.

= Contributing =
If you'd like to contribute, feel free to tackle a feature or fix a bug on [Github](https://github.com/vkuberan/beanstream-for-woocommerce) and when you're ready, send a pull request. If you'd like to get more involved than that, please e-mail me at [vkuberan@outlook.com](mailto:vkuberan@outlook.com).

== Installation ==

= Minimum Requirements =
* WooCommerce 2.1.0 or later

= Automatic installation =
Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of WooCommerce, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.
In the search field type "Beanstream for WooCommerce" and click Search Plugins. Once you've found our plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking "Install Now".

= Manual installation =
1. Upload 'beanstream-for-woocommerce' to the '/wp-content/plugins/' directory
2. Unzip 'beanstream-for-woocommerce'
3. Activate the plugin through the 'Plugins' menu in WordPress
[instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= What now? =

Go to the WooCommerce settings, click the checkout tab, then click the Beanstream for WooCommerce link at the top of the page. Once there, you should make sure the enable checkbox is checked, and then you just need to fill in your merchand Id and API pass code settings which can be found on your [Beanstream account page](https://www.beanstream.com/admin/). That's it.
Once you're ready to take live payments, make sure the test mode checkbox is unchecked. You'll also need to force SSL on checkout in the WooCommerce settings and of course have an SSL certificate.

== Upgrade Notice ==
Version 1.0  is major release.

== Updating ==
The plugin should automatically update with new features, but you could always download the new version of the plugin and manually update the same way you would manually install.

== Screenshots ==

1. The standard credit card form on the checkout page.
2. Beanstream admin settings

== Frequently Asked Questions ==

= Does I need to have an SSL Certificate? =
Yes you do. For any transaction involving sensitive information, you should take security seriously, and credit card information is incredibly sensitive. This plugin disables itself if you try to process live transactions without an SSL certificate. You can read [Beanstream's reasaoning for using SSL here](http://developer.beanstream.com/tag/ssl/).

= Does this plugin work with Subscriptions? =
No. But If you want this plugin to work on subscriptions, You can contact me at vkuberan@outlook.com

= How can I help improve it? =
You're the best. The [Beanstram for WooCommerce GitHub repository](https://github.com/vkuberan/beanstream-for-woocommerce) is a great place to start. Feel free to look through the issues already reported, or add your own. If you feel like you can fix something or improve the code, feel free to send a pull request and explain what's going on and I'll be glad to merge it into the plugin.

= If you want to donate? =
This plugin is completely open-source and completely free and will remain that way for it's entire life. With that said, this plugin is free and you're not required to pay a penny, but if this plugin helped you and your business and you feel it's worth some spare change, [send it here](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=vkuberan%40gmail%2ecom&lc=US&item_name=Thank%20you%20for%20your%20donation%20for%20Beanstream%20Gateway&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest). Thank you.

= My Question isn't on this list, what do I do now? =
Drop me an email at vkuberan@outlook.com I will help you out if I got time.

== Changelog ==

= 1.0 =
* Feature: Charge a guest using Beanstram
* Feature: Authorize & Capture or Authorize only
* Feature: Refund