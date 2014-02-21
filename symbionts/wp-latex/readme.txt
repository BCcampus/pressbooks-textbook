=== WP LaTeX ===
Contributors: mdawaffe, sidney, automattic
Tags: latex, math, equations, WordPress.com
Stable tag: 1.8
Requires at least: 2.7
Tested up to: 3.2

WP LaTeX creates PNG images from inline $\LaTeX$ code in your posts and comments.

== Description ==

Writing equations and formulae is a snap with LaTeX, but really hard on a website.
No longer.  This plugin combines the power of LaTeX and the simplicity of WordPress
to give you the ultimate in math blogging platforms.

Wow that sounds nerdy.

== Installation ==

This plugin can generate the PNG images either by using [WordPress.com](http://wordpress.com/)'s
LaTeX server (recommended) or by using the version of LaTeX installed on your webserver
(LaTeX is not installed on most webservers; this method is recommended for advanced users only).

= Using WordPress.com's LaTeX Sever (recommended) =
1. Install and activate this plugin.
2. If you want to allow LaTeX images in your blog's comments in addition to your blog's posts,
   go to Settings -> WP LaTeX, check the Comments checkbox, and save the settings.
3. That's it :)

= Using Your Server's Installation of LaTeX (advanced) =
If you choose this advanced method, you will need several external programs to be installed and
working on your webserver, so installation is bit complicated.  Many hosts will not be able to
support this method.

Server Requirements:

1. Your server must be running some flavor of Linux, UNIX, or BSD.
2. You must have a working installation of LaTeX running.  I recommend the `texlive-latex-base`
   package together with the `tetex-math-extra` package.  Both are available to most Linux
   distributions.
3. Either `dvipng` (provided by the `dvipng` package) or both `dvips` and `convert` (provided by
   the `dvips` and `imagemagick` or `graphicsmagick` packages, respectively) must installed as
   well.  `dvipng` is preferred.

Setup:

1. Create a subdirectory called `latex/` in your `wp-content/` directory and make it writable by
   your webserver (chmod 777 will do the trick, but talk to your host to see what they recommend).
2. Install and activate this plugin.
3. Go to Settings -> WP LaTeX to configure the plugin and test the PNG generation.

== Frequently Asked Questions ==

= How do I add LaTeX to my posts? =

This plugin uses the [WordPress Shortcode Syntax](http://codex.wordpress.org/Shortcode_API).
Enter your LaTeX code inside of a `[latex]...[/latex]` shortcode.

`
[latex]e^{\i \pi} + 1 = 0[/latex]
`

You may alternatively use the following equivalent syntax reminiscent of LaTeX's inline
math mode syntax.

`
$latex e^{\i \pi} + 1 = 0$
`

That is, if you would have written `$some-code$` in a LaTeX document, just
write `$latex some-code$` in your WordPress post.

For the curious, the shortcode syntax is slightly faster for WordPress to process, but the
inline syntax is a little easier for us humans to read.  Pick your poison.

= Can I change the color of the images produced? =

Yes.  You can set the default text color and background color of the images in the
Plugins -> WP LaTeX admin page.

You can also change the colors on an image by image basis by specifying `color`
and `background` attributes inside the LaTeX shortcode.  For example:

`
[latex color="ff0000" background="00ff00"]e^{\i \pi} + 1 = 0[/latex]
`

will produce an image with a bright green background and a bright red foreground color.
Colors are specified in RGB with the standard 6 digit hex notation.

The equivalent "inline" syntax uses `fg` and `bg` parameters after the LaTeX code.

`
$latex e^{\i \pi} + 1 = 0&bg=00ff00&fg=ff0000$
`

= Can I change the size of the image? =

You can specify a `size` attribute in the LaTeX shortcode:

`
[latex size="4"]e^{\i \pi} + 1 = 0[/latex]
`

or, equivalently, an `s` parameter after the LaTeX inline syntax:

`
$latex e^{\i \pi} + 1 = 0&s=4$
`

The size can be any integer from -4 to 4 (0 is the default).  These numbers correspond to
the following LaTeX size commands.

`
	size = LaTeX size
	-4     \tiny
	-3     \scriptsize
	-2     \footnotesize
	-1     \small
	0      \normalsize (12pt)
	1      \large
	2      \Large
	3      \LARGE
	4      \huge
`

= The LaTeX images work, but they don't really fit in with my blog's theme = 

You can adjust the CSS used for the LaTeX images to suit your theme better.  Go to
Settings -> WP LaTeX and edit the Custom CSS.

= I want to break out of math mode and do some really wild stuff.  How do I do that? =

You can't with this plugin.  WP LaTeX forces you to stay in math mode.  Formatting and
styling for your posts should be done with markup and CSS, not LaTeX.

If you really want hardcore LaTeX formatting (or any other cool LaTeX features), you
should probably just use LaTeX.

= Instead of images, I get error messages.  What's up =

* `Formula does not parse`: Your LaTeX is invalid; there must be a syntax error or
  something in your code (WP LaTeX doesn't provide any debugging).
* `Formula Invalid`: Your LaTeX code attempts to use LaTeX commands that this plugin
  does not allow for security reasons.
* `You must stay in inline math mode`: Fairly self explanitory, don't you think?
  See above.
* `The forumula is too long`: Break your LaTeX up into multiple images.  WP LaTeX
  limits you to 2000 characters per image.
* `Could not open TEX file for writing` or `Could not write to TEX file`: You have
  some file permissions problems.  See Intallation instructions.

== Other Plugins ==

[Steve Mayer's LatexRender Plugin](http://sixthform.info/steve/wordpress/index.php?p=13)
is based on a [LaTeX Rendering Class](http://www.mayer.dial.pipex.com/tex.htm) originally
written by Benjamin Zeiss.  It's requirements are somewhat different and has a different 
installation procedure.

== Change Log ==

= 1.8 =
* Enhancement: Add pixel density support.
* Enhancement: Make LaTeX sanitation more forgiving.
* Bug Fix: Fix LaTeX Document generation under PHP 5.4.
* Bug Fix: Fix formula length limit.
* Bug Fix: Always use `wp_safe_redirect()` for added security.
* Bug Fix: Prevent unserialization of objects.
* Bug Fix: Better prevention of breaking out from mathmode.

= 1.7 =
* Bug Fix: Strip `<p>` and `<br>` from shortcode contents to make multiline LaTeX easier.
  Only works in shortcode syntax.

= 1.6 =
* Bug Fix: Make inline and shortcode syntax outputs consistent.
* Bug Fix: i18n
* Bug Fix: "Settings" not "Options"

= 1.5 =
* Bug Fix: Minus sign incorrectly parsed. (Fix for WordPress 2.8.)

= 1.4 =
* Bug Fix: Typos in PHP4 constructor for Automattic_Latex_DVIPNG

= 1.3 =
* Bug Fix: Compatibility with PHP 4
* Clarify syntax for LaTeX in posts

= 1.2 =
* Bug Fix: RGB parsing in DVIPNG
* Bug Fix: Hash collisions in file names
* Big Fix: Default colors never used
* Support for 3 digit hex codes

= 1.1 =
* Bug Fix: `tmpnam()` can return an error on some setups when called with a null parameter.
  Use `/tmp` instead (it should fall back to the system's temp directory). Props Marin Saric.
* Bug Fix: Additional entity -> ASCII cleaning.  Props Marin Saric.
* No longer requires the FauxML plugin.

== Upgrade Notice ==

= 1.8 =
PHP 5.4 Compatibility, better sanitation, and more.

= 1.7 =
Multiline LaTeX is now easier.

= 1.6 =
Fixes inline syntax output.
