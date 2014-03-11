=== Creative Commons Configurator ===
Contributors: gnotaras
Donate link: http://bit.ly/1aoPaow
Tags: cc, cc0, license, public domain, metadata, legal, creative, commons, seo, attribution, copyright, cc license, creative commons, cc zero, rights, copyright
Requires at least: 2.7
Tested up to: 3.7.1
Stable tag: 1.5.2
License: Apache License v2
License URI: http://www.apache.org/licenses/LICENSE-2.0.txt


Adds Creative Commons license information to your posts, pages, attachment pages and feeds. Fully customizable.


== Description ==

[Creative-Commons-Configurator](http://www.g-loaded.eu/2006/01/14/creative-commons-configurator-wordpress-plugin/ "Official Creative-Commons-Configurator Homepage") is the only tool a user will ever need in order to set a [Creative Commons License](http://creativecommons.org/) on a WordPress blog and control the inclusion or display of the license information and relevant metadata into the blog pages or the syndication feeds. All configuration is done via a page in the administration panel.

By default, the license you have chosen in the License settings is automatically appended to all content, unless otherwise specified in the settings. Since the 1.5.1 release, it is possible to customize the license metadata on a per post basis from the *License* box in the post editing panel:

- You can stop license metadata from appearing on individual posts.
- Although not recommended, you can use the *All Rights Reserved* clause on specific posts.
- It is possible to waive all rights from a post and publish it in the Public Domain by choosing the <a href="http://creativecommons.org/about/cc0">CC0</a> rights waiver.

Template tags and filters are also available for those who need extra customization.

Features at a glance:

- Configuration page in the WordPress administration panel. No manual editing of files is needed for basic usage.
- Per post settings (use default license, no license, fll back to *All Rights Reserved*, *No Rights Reserved* via *CC0*).
- A widget is available to add to your sidebars.
- License selection by using the web-based license selection API from CreativeCommons.org.
- The license information can be reset at any time without affecting current license customization settings.
- Adds license information to:
 - The HTML head area (Not visible to human visitors).
 - The Atom, RSS 2.0 and RDF (RSS 1.0) feeds through the Creative Commons RSS module, which validates properly. This option is compatible only with WordPress 2 or newer due to technical reasons.
 - Displays a block with license information under the published content. Basic customization (license information and formatting) is available through the configuration panel.
- Some template tags are provided for use in your theme templates.
- The plugin is ready for localization.


= Translations =

There is an ongoing effort to translate Creative-Commons-Configurator to as many languages as possible. The easiest way to contribute translations is to register to the [translations project](https://www.transifex.com/projects/p/cc-configurator "Creative-Commons-Configurator translations project") at the Transifex service.

Once registered, join the team of the language translation you wish to contribute to. If a team does not exist for your language, be the first to create a translation team by requesting the language and start translating.


= Free License and Donations =

*Creative-Commons-Configurator* is released under the terms of the <a href="http://www.apache.org/licenses/LICENSE-2.0.html">Apache License version 2</a> and, therefore, is **Free software**.

However, a significant amount of **time** and **energy** has been put into developing this plugin, so, its production has not been free from cost. If you find this plugin useful and, if it has made your life easier, you can show your appreciation by making a small <a href="http://bit.ly/1aoPaow">donation</a>.

Thank you in advance for **donating**!


= Code Contributions =

If you are interested in contributing code to this project, please make sure you read the [special section](http://wordpress.org/plugins/creative-commons-configurator-1/other_notes/#How-to-contribute-code "How to contribute code") for this purpose, which contains all the details.


= Support and Feedback =

Please post your questions and provide general feedback and requests at the [Creative-Commons-Configurator Community Support Forum](http://wordpress.org/support/plugin/creative-commons-configurator-1/).

To avoid duplicate effort, please do some research on the forum before asking a question, just in case the same or similar question has already been answered.

Also, make sure you read the [FAQ](http://wordpress.org/plugins/creative-commons-configurator-1/faq/ "Creative-Commons-Configurator FAQ").


= Template Tags =

This plugin provides some *Template Tags*, which can be used in your theme templates. These are the following:

**NOTE**: Template tags will be revised in upcoming versions.

Text Hyperlink

- `bccl_get_license_text_hyperlink()` - Returns the text hyperlink of your current license for use in the PHP code.
- `bccl_license_text_hyperlink()` - Displays the text hyperlink.

Image Hyperlink

- `bccl_get_license_image_hyperlink()` - Returns the image hyperlink of the current license.
- `bccl_license_image_hyperlink()` - Displays the image hyperlink of the current license.

License URIs

- `bccl_get_license_url()` - Returns the license's URL.
- `bccl_get_license_deed_url()` - Returns the license's Deed URL. Usually this is the same URI as returned by the bccl_get_license_url() function.

Full HTML Code

- `bccl_get_full_html_license()` - Returns the full HTML code of the license. This includes the text and the image hyperlinks.
- `bccl_full_html_license()` - Displays the full HTML code of the license. This includes the text and the image hyperlinks.

Complete License Block

- `bccl_license_block($work, $css_class, $show_button)` - Displays a complete license block. This template tag can be used to publish specific original work under the current license or in order to display the license block at custom locations on your website. This function supports the following arguments:
 1. `$work` (alphanumeric): This argument is used to define the work to be licensed. Its use is optional, when the template tag is used in single-post view. If not defined, the user-defined settings for the default license block are used.
 1. `$css_class` (alphanumeric): This argument sets the name of the CSS class that will be used to format the license block. It is optional. If not defined, then the default class <em>cc-block</em> is used.
 1. `$show_button` (one of: "default", "yes", "no"): This argument is optional. It can be used in order to control the appearance of the license icon.

Licence Documents

- `bccl_license_summary($width, $height, $css_class)` - Displays the license's summary document in an <em>iframe</em>.
- `bccl_license_legalcode($width, $height, $css_class)` - Displays the license's full legal code in an <em>iframe</em>.


= Advanced Customization =

Creative-Commons-Configurator allows filtering of some of the generated metadata and also of some core functionality through filters. This way advanced customization of the plugin is possible.

The available filters are:

1. `bccl_cc_license_text` - applied to the text that is generated for the Creative Commons License. The hooked function should accept and return 1 argument: a string.
1. `bccl_cc0_license_text` - applied to the text that is generated for the CC0 rights waiver. The hooked function should accept and return 1 argument: a string.
1. `bccl_arr_license_text` - applied to the text that is generated for All Rights Reserved clause. The hooked function should accept and return 1 argument: a string.
1. `bccl_widget_html` - applied to the HTML code that is generated for the widget. The hooked function should accept and return 1 argument: a string.

**Example 1**: you want to append a copyright notice to the CC license text.

This can easily be done by hooking a custom function to the `bccl_cc_license_text` filter:

`
function append_copyright_notice_to_cc_text( $license_text ) {
    $extra_text = '<br />Copyright &copy; ' . get_the_date('Y') . ' - Some Rights Reserved';
    return $license_text . $extra_text;
}
add_filter( 'bccl_cc_license_text', 'append_copyright_notice_to_cc_text', 10, 1 );
`
This code can be placed inside your theme's `functions.php` file.


== Installation ==

1. Extract the compressed (zip) package in the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit the plugin's administration panel at `Options->License` to read the detailed instructions about customizing the display of license information.

Read more information about the [Creative-Commons-Configurator](http://www.g-loaded.eu/2006/01/14/creative-commons-configurator-wordpress-plugin/ "Official Creative-Commons-Configurator Homepage").


== Upgrade Notice ==

No special requirements when upgrading.


== Frequently Asked Questions ==

= There is no amount set in the donation form! How much should I donate? =

The amount of the donation is totally up to you. You can think of it like this: Are you happy with the plugin? Do you think it makes your life easier or adds value to your web site? If this is a yes and, if you feel like showing your appreciation, you could imagine buying me a cup of coffee at your favorite Cafe and <a href="http://bit.ly/1aoPaow">make a donation</a> accordingly.

= Will this plugin support other licenses apart from Creative-Commons licenses? =

Currently there are no plans to support other licenses.

= Where can I get support? =

You can get first class support from the [community of users](http://wordpress.org/support/plugin/creative-commons-configurator-1 "Creative-Commons-Configurator Users"). Please post your questions, feature requests and general feedback in the forums.

Keep in mind that in order to get helpful answers and eventually solve any problem you encounter with the plugin, it is essential to provide as much information as possible about the problem and the configuration of the plugin. If you use a customized installation of WordPress, please make sure you provide the general details of your setup.

Also, my email can be found in the `cc-configurator.php` file. If possible, I'll help. Please note that it may take a while to get back to you.

= Is there a bug tracker? =

You can find the bug tracker at the [Creative-Commons-Configurator Development web site](http://www.codetrax.org/projects/wp-cc-configurator).


== Screenshots ==

1. Creative-Commons-Configurator administration interface.


== Changelog ==

In the following list there are links to the changelog of each release:

- [1.5.2](http://www.codetrax.org/versions/200)
 - Updated translations (thanks: Jani Uusitalo, bzg, Matthias Heil, alvaroto, bizover)
- [1.5.1](http://www.codetrax.org/versions/133)
 - Some license customization on a per post basis has been implemented (options: use default, opt-out, CC0, ARR)
 - Refactoring.
- [1.5.0](http://www.codetrax.org/versions/181)
 - Refactoring.
 - Re-designed mechanism that manages the settings.
 - Full support for SSL admin panel.
 - A Creative Commons widget is now available.
- [1.4.1](http://www.codetrax.org/versions/134)
- [1.4.0](http://www.codetrax.org/versions/128)
- [1.3.2](http://www.codetrax.org/versions/131)
- [1.3.1](http://www.codetrax.org/versions/129)
- [1.3.0](http://www.codetrax.org/versions/127)
- [1.2](http://www.codetrax.org/versions/7)
- [1.1](http://www.codetrax.org/versions/5)
- [1.0](http://www.codetrax.org/versions/22)
- [0.6](http://www.codetrax.org/versions/45)
- [0.5](http://www.codetrax.org/versions/44)
- [0.4](http://www.codetrax.org/versions/43)
- [0.2](http://www.codetrax.org/versions/42)
- [0.1](http://www.codetrax.org/versions/41)



== How to contribute code ==

This section contains information about how to contribute code to this project.

Creative-Commons-Configurator is released under the Apache License v2.0 and is free open-source software. Therefore, code contributions are more than welcome!

But, please, note that not all code contributions will finally make it to the main branch. Patches which fix bugs or improve the current features are very likely to be included. On the contrary, patches which add too complicated or sophisticated features, extra administration options or transform the general character of the plugin are unlikely to be included.

= Source Code =

The repository with the most up-to-date source code can be found on Bitbucket (Mercurial). This is where development takes place:

`https://bitbucket.org/gnotaras/wordpress-creative-commons-configurator`

The main repository is very frequently mirrored to Github (Git):

`https://github.com/gnotaras/wordpress-creative-commons-configurator`

The Subversion repository on WordPress.org is only used for releases. The trunk contains the latest stable release:

`http://plugins.svn.wordpress.org/creative-commons-configurator-1/`

= Creating a patch =

Using Mercurial:

`
hg clone https://bitbucket.org/gnotaras/wordpress-creative-commons-configurator
cd wordpress-creative-commons-configurator
# ... make changes ...
hg commit -m "fix for bug"
# create a patch for the last commit
hg export --git tip > bug-fix.patch
`

Using Git:

`
git clone https://github.com/gnotaras/wordpress-creative-commons-configurator
cd wordpress-creative-commons-configurator
# ... make changes to cc-configurator.php or other file ...
git add cc-configurator.php
git commit -m "my fix"
git show > bug-fix.patch
`

Using SVN:

`
svn co http://plugins.svn.wordpress.org/creative-commons-configurator-1/trunk/ creative-commons-configurator-trunk
cd creative-commons-configurator-trunk
# ... make changes ...
svn diff > bug-fix.patch
`

= Patch Submission =

Here are some ways in which you can submit a patch:

* submit to the [bug tracker](http://www.codetrax.org/projects/wp-cc-configurator/issues) of the development website.
* create a pull request on Bitbucket or Github.
* email it to me directly (my email address can be found in `cc-configurator.php`).
* post it in the WordPress forums.

Please note that it may take a while before I get back to you regarding the patch.

Last, but not least, all code contributions are governed by the terms of the Apache License v2.0.


