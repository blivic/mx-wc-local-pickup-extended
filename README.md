## MX WooCommerce Local Pickup Extended 

* Requires at least: 4.0
* Tested up to: 4.4.2
* Tested WooCommerce up to: 2.5.0

A shipping plugin for WooCommerce that extends the Local pickup method by adding multiple pickup locations.

### Description

The built-in WooCommerce Local Pickup shipping method allows your customers pick up their purchased products personally, in your store. Local Pickup Extended extends this functionality by adding multiple pickup locations for your customers to choose from during checkout. The selected pickup location is displayed to your customer on Order details page (after purchase), in the Account – View Order page (frontend) and in the order emails. 

Admins and/or shop managers can configure and offer a fee or discount for pickup in terms of fixed amount, fixed amount per product or percentage of cart total. If not defined the shipping method is free of charge.

### Installation

1. Upload `mx-wc-local-pickup-extended` folder to the `/wp-content/plugins/` directory or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use the WooCommerce -> Settings -> Shipping -> Local Pickup Extended screen to configure the available options

### Credits

This plugin is a fork of the WooCommerce Shipping Options plugin available in the WP repo. It now works specifically for Local pickup, the chosen pickup location is now visible also on the Order details page (after purchase) and in the order emails. Once this shipping method is selected, selecting one of the available options (pickup locations) is required. Also, now you can enter negative values in the pickup fee/discount field so you can also offer discounts. The plugin is ready for localization (translation). There's a POT file in the languages folder so translating the strings is a breeze now. Btw, the plugin is already translated to Croatian.

### Screenshots

##### Settings page ( Woo - Settings - Shipping - Local pickup extended)

![Local pickup extended settings](http://media-x.hr/wp-content/uploads/2016/03/Local-pickup-extended-Settings.jpeg)

##### Frontend (checkout)

![Local pickup extended frontend](http://media-x.hr/wp-content/uploads/2016/03/pickup-in-store-select.jpeg)

##### Want more screenshots?
The blog post [MX WooCommerce Local Pickup Extended](http://media-x.hr/mx-woocommerce-local-pickup-extended/) is in Croatian but you can check the additional screenshots.

### Frequently Asked Questions

> What does this plugin do? 

This plugin extends the in-built Local pickup functionality by adding multiple pickup locations for your customers to choose from during checkout.

> Can i add a fee or a discount to this shipping method? =

By default, this shipping method is free. You can set a cost for this pickup method by selecting one of the available Fee types and entering a positive value in the Pickup Fee/Discount field. If you want to offer discount, select a Fee type and enter a negative value (e.g. -10) in the Pickup Fee/Discount field.

> Can i localize (translate) this plugin? =

Sure, no problem. There's a POT file in the languages folder so you can localize it by using, for example, Poedit.
