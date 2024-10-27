=== Plugin Name ===
Contributors: are79
Donate link: http://arepaypal.ehibou.com/
Tags: monetize, paypal, subscriptions, pay to register, pay to read, paid content
Requires at least: 2.0.2
Tested up to: 3.8
Stable tag: 1.9.2.4

This plugin is used to monetize wordpress blog content using PayPal. It is designed to sell your knowledge.

== Description ==

This plugin is used to monetize wordpress blog content using PayPal.
It is designed to sell your knowledge.

Features:

* Post/Page can be set to contain hidden content.
* Instead of the hidden text user is shown a message about the action he has to take to be able to see the content.
* Content can be set as hidden for unregistered users and visible for registered
* Content can be set as hidden for unregistered-unpayed users and visible for registered-paid users
* Gogglebot can index hidden content so users can search for a hidden content but can not see it. Other search engines will see only visible content
* Administrator can grant users to access payed content
* All the features are configurable using administration screens
* Plugin uses PayPal IPN - Instant Payment Notification protocol so payment/content delivery process is fully automated.
* Bonus posts. Packages of posts can be created. So what buying one post from the package all other becomes visible also
* Price for all blog. Administrator can set price for all payed posts so what user who pays can view all the hidden content
* Pay To register feature.

Workflow:

1. User is exposed with a message "Please login" and a login hyperlink instead of hidden content
2. After user logs in he is exposed with a content or a new message "Please pay" and a PayPal button
3. After user pays and returns back to the blog he is exposed with a content


Usage:

* Edit the posts - you want to contain hidden content. Surround the content you want to be hidden for unregistered users with [Are_PayPal_LoginPlease][/Are_PayPal_LoginPlease] pseudo tags.
* To tax previously hidden content edit post prices and user privileges under Are PayPal administration section 

== Screenshots ==

1. Screenshot 
2. Screenshot 
3. Screenshot 
4. Screenshot 
5. Screenshot 
6. Screenshot 
7. Screenshot
8. Screenshot
9. Screenshot
11. Screenshot
12. Screenshot
13. Screenshot
14. Screenshot

== Installation ==

1. Unzip the archive into a folder are_paypal
2. Upload this folder to your wordpress blog plugins folder
3. Enable the plugin in wordpress plugin administration section
4. Configure the plugin using Are PayPal section in your blogs administrators site

== Upgrade Notice ==

= 1.9.0 =
Smarty cache folder is now configurable via administration section. Default folder "cache" in the plugin's home folder is not safe enough. Use path which is outside of your documents root or public_html.
= 1.9.1 =
More control over Bonus Posts. 
Checkbox for manual control over Bonus posts was added in Configuration page.
In case checkbox is unchecked the plugin will set automatically post A as bonus post for post B while manually setting post B as bonus post for post A.In case checkbox is unchecked you will have to set both ends manually.
= 1.9.2.2 =
Fixed it to work with latest paypal ipn changes
= 1.9.2.3 =
Ordering of IPN responces by RequestID so that the latest is on top.
Additional field is visible in the list - Date