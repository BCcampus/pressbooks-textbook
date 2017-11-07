<?php
// inc/class-textbook.php

class TextbookTest extends WP_UnitTestCase {

	/**
	 * @var PBT instance
	 */
	protected $pbt;

	public function setUp() {
		parent::setUp();
		$this->pbt = \PBT\Textbook::get_instance();

	}

	public function testGetInstance() {
		$this->assertInstanceOf( '\PBT\Textbook', $this->pbt );

	}

	function testIsTexbookTheme() {
		$nope = __DIR__ . '/data/not-pbt-theme.css';
		$yup = __DIR__ . '/data/pbt-theme.css';// set the default theme to opentextbooks

		switch_theme( 'notpbttheme' );
		update_option( 'stylesheet_root', $nope );
		update_option( 'stylesheet', 'notpbttheme' );

		$false = $this->pbt::isTextbookTheme( $nope );
		$this->assertFalse( $false );
		wp_clean_themes_cache();


//		switch_theme( 'pbttheme' );
//		update_option( 'stylesheet_root', $yup );
//		update_option( 'stylesheet', 'pbttheme' );
//
//		$true = $this->pbt::isTextbookTheme( $yup );
//		$this->assertTrue( $true );
//		wp_clean_themes_cache();

	}
}
