=== WP Save Custom Header ===
Contributors: kobenland
Tags: admin, custom header, header, header image, custom header image
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=UUFKGWPK469GW
Requires at least: 3.0
Tested up to: 3.2
Stable tag: 1.6

This plugin is DEPRECATED with WordPress 3.2.

== Description ==

This plugin is DEPRECATED with WordPress 3.2. Since its functionality is incorporated into WordPress core, this plugin will be no longer supported!

It lets you save and reuse your uploaded header images.

It will save your images to the `/images/headers` folder within your template folder.
In the Custom Header menu the images will then show up as defaults, together with your other registered custom header images.

= Translations =
Available in:

* English
* Deutsch
* Italiano
* Portugu&ecirc;s


== Installation ==

Do not install !


== Changelog ==

= 1.6 =
* Added selfdeactivation when WordPress runs on Version 3.2 or higher.
* Uploaded header files are converted to the WordPress Core way and will not be lost.


= 1.5 =
* Tested for WordPress 3.1.2
* Now a custom folder name can be specified on non-multiuser installs. See: Settings > Media
* Use Settings API to display error messages


= 1.4 =
* Tested for WordPress 3.1.1
* Updated class structure
* Added complete multisite support
* Added functionality to setup plugin options for every blog on multisite installs

= 1.3 =
* Fixed update issue with thumbnail pics, when the header was edited in the Media Library
* Added Brasilian Portugese translation (thx Fernando L.)
* Added Italian translation (thx Pietro R.)
* Added version check for 3.0
* Fixed two minor bugs

= 1.2 =
* Header will now completely vanish when deleted through Media Library (file and data)
* Header title-tag customizable through Media Library
* Additional check whether image folder is writable
* Full uninstall support: All files and data will be deleted and the default header reinstated, upon uninstall of plugin.


= 1.1.2 =
* Enhancement: New header will be shown right after upload
* Minor Readme.txt and Author URL fixes

= 1.1 & 1.1.1 =
* Dealing with SVN issues

= 1.0 =
* Initial Release