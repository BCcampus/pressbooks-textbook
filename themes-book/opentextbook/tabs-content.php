<?php


$tabs = pbt_get_web_options_tab();

if ( ! empty( $tabs ) ) {

	$i      = 1;
	$html   = '<div id="tabs">';
	$labels = '<ul>';
	$panels = '';

	foreach ( $tabs as $key => $tab ) {
		$title   = pbt_explode_on_underscores( $key, 'first' );
		$method  = 'pbt_' . $key;
		$labels .= "<li><a href='#tabs-{$i}'>{$title} <span class='dashicons'></span></a></li>";
		$panels .= "<div id='tabs-{$i}'>";
		$panels .= $method( $post );
		$panels .= '</div>';
		$i ++;
	}
	$labels .= '</ul>';
	$panels .= '</div>';

	$html .= $labels . $panels;

	echo $html;

}

