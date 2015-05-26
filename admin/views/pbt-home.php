<?php
/**
 * This admin home page describes the what and why of opentextbooks. 
 *
 * @package PressBooks_Textbook
 * @author Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * 
 * @copyright 2014 Brad Payne
 */
if ( ! defined( 'ABSPATH' ) ) exit;

?>

<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<?php
	// temporary message to shift people over to the correct location
	if ( current_user_can( 'manage_options' ) ) {
		echo "<div class='updated'><p>Manage <a href='options-general.php?page=pressbooks-textbook-settings'>PressBooks Textbook settings</a>.</p></div>";
	}
	?>
	<h3>Why Open Textbooks?</h3>
	<p>Open Textbooks are important because they have the potential to:</p>

	<ol>
		<li>increase access to higher education by reducing student costs;</li>
		<li>give faculty more control over their instructional resources;</li>
		<li>move the OER agenda forward in a meaningful, measurable way.</li>
	</ol>
	<h3>What are Open Textbooks/OER?</h3>
	<p>Open Textbooks are open educational resources (OER); they are instructional resources created and shared in ways so that more people have access to them. That’s a different model than traditionally-copyrighted materials. OER are defined as&nbsp;“teaching, learning, and research resources that reside in the public domain or have been released under an intellectual property license that permits their free use and re-purposing by others” (<a href="http://www.hewlett.org/programs/education/open-educational-resources" target="_blank">Hewlett Foundation</a>).</p>
	<p>When creating Open Textbooks and other OERs, it is best to adhere to the five Rs of open education as <a href="https://www.opencontent.org/definition/" target="_blank">defined by David Wiley</a>, which are:</p>
	<ol class="ol1">
		<li><strong>Retain</strong> – i.e. no digital rights management restrictions (DRM), the content is yours to keep, whether you’re the author, instructor or student.</li>
		<li><strong>Reuse</strong> – you are free to use materials in a wide variety of ways without expressly asking permission of the copyright holder.</li>
		<li><strong>Revise</strong> – as an educator, you can adapt, adjust, or modify the content to suit specific purposes and make the materials more relevant to your students.&nbsp;This means making it available in a number of different formats and including source files, where possible.</li>
		<li><strong>Remix</strong> – you or your students can pull together a number of different resources to create something new.</li>
		<li><strong>Redistribute</strong> – you are free to share with others, so they can reuse, remix, improve upon, correct, review or otherwise enjoy your work.</li>
	</ol>
</div>
