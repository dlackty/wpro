<?php

class BackendS3Test extends WP_UnitTestCase {

	function testFiltersAreRegistered() {
		/*
		$s3_backend = wpro()->backends->backend_by_name('Amazon S3');

		// 10 is the filter priority:
		$this->assertEquals(has_filter('wpro_backend_retrieval_baseurl', array($s3_backend, 'url')), 10);
		*/
	}

	function testS3Options() {
		wpro()->backends->activate_backend('Amazon S3');
		$this->assertTrue(wpro()->options->is_an_option('wpro-aws-key'));
		$this->assertTrue(wpro()->options->is_an_option('wpro-aws-secret'));
		$this->assertTrue(wpro()->options->is_an_option('wpro-aws-bucket'));
		$this->assertTrue(wpro()->options->is_an_option('wpro-aws-virthost'));
		$this->assertTrue(wpro()->options->is_an_option('wpro-aws-endpoint'));
		$this->assertTrue(wpro()->options->is_an_option('wpro-aws-ssl'));
		$this->assertTrue(wpro()->options->is_an_option('wpro-aws-cloudfront'));
		wpro()->backends->deactivate_backend();
		$this->assertFalse(wpro()->options->is_an_option('wpro-aws-key'));
		$this->assertFalse(wpro()->options->is_an_option('wpro-aws-secret'));
		$this->assertFalse(wpro()->options->is_an_option('wpro-aws-bucket'));
		$this->assertFalse(wpro()->options->is_an_option('wpro-aws-virthost'));
		$this->assertFalse(wpro()->options->is_an_option('wpro-aws-endpoint'));
		$this->assertFalse(wpro()->options->is_an_option('wpro-aws-ssl'));
		$this->assertFalse(wpro()->options->is_an_option('wpro-aws-cloudfront'));
	}
}

