<?php
// inc/class-textbook.php

class TextbookTest extends WP_UnitTestCase {

	protected $pbt;

	public function setUp() {
		parent::setUp();
		$this->pbt = \PBT\Textbook::get_instance();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_getInstance() {
		$this->assertInstanceOf( '\PBT\Textbook', $this->pbt );
	}


	function test_isTexbookTheme() {
		register_theme_directory( __DIR__ . '/data/themes' );
		$pbt = \PBT\Textbook::get_instance();

		$nope = wp_get_theme( 'notpbt', __DIR__ . '/data/themes' );
		$f    = $pbt::isTextbookTheme( $nope );
		$this->assertFalse( $f );
		wp_clean_themes_cache();

		$yup = wp_get_theme( 'pbt', __DIR__ . '/data/themes' );
		$t   = $pbt::isTextbookTheme( $yup );
		$this->assertTrue( $t );
		wp_clean_themes_cache();
	}
}
