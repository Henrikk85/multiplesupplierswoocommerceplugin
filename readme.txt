=== Multi-Supplier Order Manager ===
Contributors: henrikkriiger
Tags: woocommerce, suppliers, orders, email, pdf, automation
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically splits WooCommerce orders by supplier and sends separate emails with PDF attachments to suppliers and transportation companies.

== Description ==

Multi-Supplier Order Manager is a powerful WordPress plugin that integrates seamlessly with WooCommerce to handle complex multi-supplier order scenarios. When a customer places an order containing products from different suppliers, this plugin automatically:

* **Detects multi-supplier orders** - Automatically identifies when an order contains products from multiple suppliers
* **Splits orders by supplier** - Organizes order items by their assigned suppliers
* **Generates PDF documents** - Creates separate, professional PDF documents for each supplier containing only their relevant items
* **Sends automated emails** - Automatically emails each supplier with their specific order details and PDF attachment
* **Notifies transportation** - Sends pickup information to your transportation company for each supplier location
* **Manages supplier database** - Provides a complete supplier management system with contact information and addresses

= Key Features =

* **Supplier Management**: Add, edit, and manage supplier information including contact details and addresses
* **Product-Supplier Assignment**: Easily assign products to specific suppliers through the product edit screen
* **Automatic Order Processing**: No manual intervention required - the plugin works automatically when orders are placed
* **Professional PDF Generation**: Clean, branded PDF documents with company information and order details
* **Email Templates**: Customizable email templates for both suppliers and transportation companies
* **Transportation Integration**: Separate notifications for pickup coordination with detailed location information
* **Multi-language Support**: Ready for translation with proper internationalization
* **WooCommerce Integration**: Seamlessly integrates with WooCommerce order management system

= Perfect For =

* Dropshipping businesses with multiple suppliers
* Wholesale operations with distributed inventory
* Marketplace-style stores with multiple vendors
* Any business that needs to coordinate with multiple suppliers per order

= How It Works =

1. **Setup**: Add your suppliers and assign products to them
2. **Configuration**: Set up your transportation company details and email templates
3. **Automatic Processing**: When a multi-supplier order is placed, the plugin automatically:
   - Identifies which products belong to which suppliers
   - Generates separate PDF documents for each supplier
   - Sends emails to each supplier with their specific items
   - Sends pickup notifications to your transportation company
   - Adds order notes for tracking

= Requirements =

* WordPress 5.0 or higher
* WooCommerce 5.0 or higher
* PHP 7.4 or higher

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/multi-supplier-order-manager` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to 'Supplier Manager' in your WordPress admin menu
4. Add your suppliers and configure their contact information
5. Assign products to suppliers by editing individual products
6. Configure your transportation company details in the Settings tab
7. The plugin will automatically process multi-supplier orders from this point forward

== Frequently Asked Questions ==

= Does this plugin work with all WooCommerce themes? =

Yes, the plugin works independently of your theme and integrates directly with WooCommerce's order processing system.

= Can I customize the PDF templates? =

The current version includes professional PDF templates. Custom template support may be added in future versions.

= What happens if a product doesn't have a supplier assigned? =

Products without assigned suppliers will be ignored during the multi-supplier processing. Only products with assigned suppliers will be included in the automated emails.

= Can I use this with variable products? =

Yes, the plugin supports both simple and variable products. You can assign suppliers to specific variations.

= Does this work with other order statuses? =

By default, the plugin processes orders when they reach "Processing" or "Completed" status. This can be customized if needed.

== Screenshots ==

1. Supplier management interface - Add and manage all your suppliers
2. Product supplier assignment - Easily assign products to suppliers
3. Plugin settings - Configure transportation company and email templates
4. Generated PDF example - Professional supplier order documents
5. Email template example - Automated supplier notifications

== Changelog ==

= 1.0.0 =
* Initial release
* Supplier management system
* Automatic order splitting by supplier
* PDF generation for supplier documents
* Email automation for suppliers and transportation
* WooCommerce integration
* Multi-language support

== Upgrade Notice ==

= 1.0.0 =
Initial release of Multi-Supplier Order Manager.

== Support ==

For support, feature requests, or bug reports, please visit our GitHub repository or contact us through the WordPress support forums.

== Privacy Policy ==

This plugin does not collect or store any personal data beyond what is already collected by WooCommerce. All supplier and order information is stored locally in your WordPress database.
