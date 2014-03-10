<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package PressBooks_Textbook
 * @author Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * 
 * @copyright 2014 Brad Payne
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<h4>Some of the features we'd like to see:</h4>
	<ol>
		<li>'fork' this textbook</li>
		<li>OER (Open Educational Resources) search</li>
		<li>making select export files available to the public</li>
	</ol>

	<p>If you're interested in making code contributions, make a pull request or first visit our <a href="https://github.com/BCcampus/pressbooks-textbook/wiki/Contribution-guidelines">guidelines for contributions</a>.</p>
</div>
