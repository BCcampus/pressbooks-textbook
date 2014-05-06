=== PressBooks Textbook ===
Contributors: bdolor
Donation link: https://github.com/BCcampus/pressbooks-textbook/wiki/Contribution-guidelines
Tags: pressbooks, textbook
Requires at least: 3.8.3
Tested up to: 3.9
Stable tag: 1.0.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

PressBooks Textbook adds functionality to the PressBooks plugin to make it easier to author textbooks.

== Description ==
**PressBooks Textbook** adds functionality to PressBooks to make it easier to author textbooks as well. The features it currently offers are: 

* Textbook Theme
* TinyMCE table buttons
* TinyMCE textbook buttons 
* TinyMCE spell check
* Search functionality
* Creative Commons attribution
* Prominent admin buttons (Import, Plugin)
* Annotation functionality
* Redistribution capabilities for free, digital versions of your book.
* Download links to openly licensed textbooks, ready to remix.

Textbooks have functional and styling considerations above and beyond regular books. Open textbooks are those that are licensed with a [creative commons license](http://creativecommons.org).
This plugin was built primarily to support the creation, remixing and distribution of open textbooks for the [open textbook project in BC](http://open.bccampus.ca/about-2/).

== Installation ==

IMPORTANT! 

You must first install [PressBooks](https://github.com/pressbooks/pressbooks). This plugin won't work without it.
The PressBooks github repository is updated frequently. [Stay up to date](https://github.com/pressbooks/pressbooks/tree/master).

Most of the functionality of this plugin, like search, textbook buttons and annotation are tied directly to the `Open Textbooks` theme. Network Activate the `Open Textbooks` 
theme, then activate at the book level. You'll have access to those features and more. 

= Using Git =

1. cd /wp-content/plugins 
2. git clone https://github.com/BCcampus/pressbooks-textbook.git 
3. Activate the plugin at the network level, through the 'Plugins' menu in WordPress
4. Activate the `Open Textbooks` theme at the network level
5. Activate the `Open Textbooks` theme at the book level.

= OR, go to the WordPress Dashboard =

1. Navigate to the Network Admin -> Plugins
2. Search for 'PressBooks Textbook'
3. Click 'Network Activate'
4. Activate the `Open Textbooks` theme at the network level
5. Activate the `Open Textbooks` theme at the book level.

= OR, upload manually =

1. Upload `pressbooks-textbook` to the `/wp-content/plugins/` directory
2. Activate the plugin at the network level, through the 'Plugins' menu in WordPress
3. Activate the `Open Textbooks` theme at the network level
4. Activate the `Open Textbooks` theme at the book level.

== Screenshots == 

1. Modified home page 
2. Search feature 
3. Textbook specific buttons with styling maintained throughout export routines 
4. Textbook theme 
5. Customize your plugin options for each book in your collection

== Changelog ==

See: https://github.com/BCcampus/pressbooks-textbook/commits/master for more detail

= 1.0.8 (2014/05/06) =
* adding spell check to tinyMCE
* fix for checking active plugins at book level
* disable including license in feeds (for a valid EPUB export)

= 1.0.7 (2014/05/02) =
* fix filtering of root child themes (thanks John!)
* tested up to WP 3.9

= 1.0.6 (2014/04/11) = 
* fixing filter function to ameliorate conflicts with already active plugins.

= 1.0.5 (2014/04/04) = 
* adding download links to 31 openly licensed textbooks in various digital formats. Remix!

= 1.0.4 (2014/04/03) = 
* fixing redistribution of export files option on home page, requires re-setting if previously set

= 1.0.3 (2014/04/01) = 
* updating and fixing mce table plugin

= 1.0.2 (2014/03/31) = 
* choose which plugins you want to include 
* adding tabs to plugin options page

= 1.0.1 (2014/03/18) =
* annotation capabilities
* administration menu page
* Redistribution capabilities for free, digital versions of your book

= 1.0.0 (2014/03/13) =
* initial release

== How to contribute code == 

Pull requests are enthusiastically received **and** scrutinized for quality. 

* The best way is to initiate a pull request on [GitHub](https://github.com/BCcampus/pressbooks-textbook).