=== Site Prober ===
Contributors: Liaison  
Donate link:   
Tags: Site Prober
Requires at least: 6.8  
Tested up to: 6.8 
Stable tag: 1.0.0
License: GPLv3 or later  
License URI: https://www.gnu.org/licenses/gpl-3.0.html  

Site Prober plugin helps you log changes 
and actions on your WordPress site.
Securing your Wordpress website

== Description ==
<strong>A Lightweighted and Easy hands-on plugin to secure your Wordpress website.</strong><br />

Wonder when did your WordPress website change and who did this?
Want to track your WordPress website change history? 
Find out when and who did what on your WordPress website with Site Prober plugin. 

* Who logged in, who logged out and if someone tried to log in but failed.
* When a post was published, and who published it
* If a plugin/theme was activated/deactivated

<strong>Export to CSV</strong> - Export your Wordpress website change history to CSV. 

* <strong>Posts</strong> - Created, updated, deleted
* <strong>Categories</strong> - Created, updated, deleted
* <strong>Taxonomies</strong> - Created, updated, deleted
* <strong>Comments</strong> - Created, approved, unapproved, trashed, untrashed, spammed, unspammed, deleted
* <strong>Users</strong> - Login, logout, login failed, update profile, registered, deleted
* <strong>Plugins</strong> - Installed, updated, activated, deactivated, changed
* <strong>Themes</strong> - Installed, updated, deleted, activated, changed (Editor and Customizer)

<h3>Data Storage Isolation</h3>

In order to keep your website database clean and to be easire to backup, 
all logs data are stored in a isolated custom table within your WordPress database.



== Build Status ==

[2025/11/3] Tested:
1. wp_site_prober table delete and create when plugin activated.
2. Action logger:
'wp_login', 'wp_logout', 'wp_login_failed', 'save_post', 'update_post',
'switch_theme', 'activated_plugin', 'deactivated_plugin', 'profile_update'
3. Search actions, description


== Development ==
[Site Prober](https://github.com/liaisontw/wp-site-prober)  


== Installation ==  

For an automatic installation through WordPress:
1. Select Add New from the WordPress Plugins menu in the admin area.
2. Search for 'Site Prober'.
3. Click Install Now, then Activate Plugin.

For manual installation via FTP:
1. Upload the 'Site Prober' folder to the '/wp-content/plugins/' directory.
2. Activate the plugin from the 'Plugins' screen or 'Installed Plugins' menu in WordPress Dashboard.


== Frequently Asked Questions ==  



== Screenshots ==  

1. Dash-Board: Settings-wp-site-prober.
2. Site Prober_Setting_Panel

== Changelog ==

[1.0.0] First released.

== Upgrade Notice ==

