=== WP-Invites ===
Author: Jehy
Tags: captcha,registration,user,admin,access,authenification,register
Requires at least: 4.0
Tested up to: 4.2
Stable tag: 2.50
Invites system for wordpress, wordpress MU and buddypress!

== Description ==

####Description
Stop any strangers from entering your blog! Only the ones who received invitation code will be able to register. You can configure different plugin options - separators, number of chars to be used for code generation, code working time and much more! Also, now you can add codes manually and view added codes.

####Compatibility
This plugin is compatible with Wordpress 4, should be compatible with buddypress and may be compatible with wordpress MU. If you find any compatibility issues for current versions - feedback appreciated.

[Changelog](https://wordpress.org/plugins/wp-invites/changelog/).

####Localization

* English
* Russian

####Attention!
This plugin only denies new registrations from strangers. If you need to make your blog really private, you should restrict viewing [with Registered-Users-Only-2 plugin](http://wordpress.org/extend/plugins/registered-users-only-2/) or somewhat alike.

####Questions
If you have troubles with my plugin, need more details, or have suggestions - please visit [my blog](http://jehy.ru/articles/2009/02/09/wordpress-plugins/#comments) for more info.


####Please!
If you don't rate my plugin as 5/5 - please write why - and I will change plugin, add options and fix bugs. It's very unpleasant to see silient low rates.  
If you don't understand what plugin does - also don't rate it ;)

####Donate or help?
If you want to ensure the future development and support of this plugin, you can make donation [on this page](http://jehy.ru/articles/donate/) or just write about this plugin in your blog.


###Installation
Please look instructions on the [installation page ^_^](http://wordpress.org/extend/plugins/wp-invites/installation/).

== Changelog ==

0.1 - First release  
0.2 - Activation and language issues with wordpress MU fixed  
0.3 - Updated to work with the latest BuddyPress system   
0.4 - Defined str_split function for compatibility with PHP4   
1.0 - Many different fixes, including usernames's replacing and error reporting   
1.1 - Fixed broken compatibility mode for MySQL 4, added some error reporting    
1.2 - Crytical upgrade for WP MU (not for simple wordpress), install as soon as possible.    
1.3 - Fixed registration incompatibility issues.    

2.0 - Absolutely new version, with many functions, compatible with newest wordpress, wordpress mu and buddypress.    
2.1 - Several fixes for simple wordpress.    
2.2 - Internal release    
2.21 - Fix for simple wordpress & Buddypress combo errors. Hope it works.    
2.30 - Fixed for wordpress 4.1, direct SQL replaced with $wpdb queries, added missing menu button.    
2.40 - Many changes for admin area, added cool styling, improved some SQL queries. Also fixed localization issues.    
2.41,2.42 - Fix for buddypress.
2.50 - Code rewrite in Object oriented style, debug mode implemented, fixed error with user activation.


== Installation ==
Usually:  
1. Upload the complete folder `wp-invites` to the `/wp-content/plugins/` directory;  
3. Activate the plugin through the 'Plugins' menu in WordPress;  
2. Get activation codes through Plugins-&gt;WP-invites (Site admin-&gt;WP-invites for WP MU) link;  
4. Enjoy.
  
  
For old versions of Wordpress MU and Buddypress:  
1. Upload the complete folder `wp-invites` to the `/mu-plugins/` directory;  
2. Put file `wp-invites-MU_INIT.php` from `wp-invites` to upper directory, `mu-plugins`;  
3. Get activation codes through Site admin-&gt;WP-invites link;  
4. Enjoy.

== Frequently Asked Questions ==

Still none.

== Screenshots ==
1. Invitation code request while registering in Wordpress.
2. Invitation code is displayed for administrator in user profile.
3. Invitation code is also displayed for administrator in user profile in Buddypress.