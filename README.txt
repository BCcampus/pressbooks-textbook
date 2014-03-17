=== PressBooks Textbook ===
Contributors: bdolor
Donation link: https://github.com/BCcampus/pressbooks-textbook/wiki/Contribution-guidelines
Tags: pressbooks, textbook
Requires at least: 3.8.1
Tested up to: 3.8.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

PressBooks Textbook adds functionality to the PressBooks plugin to make it easier to author textbooks.

== Description ==
**PressBooks Textbook** adds functionality to PressBooks to make it easier to author textbooks as well. The features it currently offers are: 

* Textbook Theme
* TinyMCE table buttons
* TinyMCE textbook buttons 
* Search functionality
* Creative Commons attribution
* Prominent admin buttons (Import, Plugin)

Textbooks have functional and styling considerations above and beyond regular books. Open textbooks are those that are licensed with a [creative commons license](http://creativecommons.org).
This plugin was built primarily to support the creation, remixing and distribution of open textbooks for the [open textbook project in BC](http://open.bccampus.ca/about-2/).

== Installation ==

IMPORTANT! 

You must first install [PressBooks](https://github.com/pressbooks/pressbooks). This plugin won't work without it.
The PressBooks github repository is updated frequently. [Stay up to date](https://github.com/pressbooks/pressbooks/tree/master).

= Using Git =

cd /wp-content/plugins
git clone https://github.com/BCcampus/pressbooks-textbook.git 

= OR, go to the WordPress Dashboard =

1. Navigate to the Network Admin -> Plugins
2. Search for 'PressBooks Textbook'
3. Click 'Network Activate'

= OR, upload manually =

1. Upload `pressbooks-textbook` to the `/wp-content/plugins/` directory
2. Activate the plugin at the network level, through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Dude, where's my functionality? = 

Some of the functionality, like search and textbook buttons are tied directly to the `Open Textbooks` theme. Network Activate the 
theme, then activate at the book level and you'll have access to those features and more. 

== Screenshots ==

1. Modified homepage
2. Search feature
3. Textbook specific buttons, styling maintain throughout export routines
4. Textbook theme

== Changelog ==

See: https://github.com/BCcampus/pressbooks-textbook/commits/master


== Upgrade Notice == 

= 1.0.1 =
* annotation capabilities
* administration menu page
* download latest export files function

= 1.0.0 =
* initial release

== How to contribute code == 

Pull requests are enthusiastically received **and** scrutinized for quality. 

* The best way is to initiate a pull request on [GitHub](https://github.com/BCcampus/pressbooks-textbook).