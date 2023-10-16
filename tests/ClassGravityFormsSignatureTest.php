<?php

namespace WPSL\GravityFormSignature;

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use wpCloud\StatelessMedia\WPStatelessStub;
use WPSL\GravityFormSignature\GravityFormSignature;

/**
 * Class ClassGravityFormsSignatureTest
 */

class ClassGravityFormsSignatureTest extends TestCase {
  const TEST_URL = 'https://test.test';
  const UPLOADS_URL = self::TEST_URL . '/uploads';
  const TEST_FILE = 'gravity_forms/image.png';
  const SRC_URL = self::UPLOADS_URL . '/' . self::TEST_FILE;
  const DST_URL = WPStatelessStub::TEST_GS_HOST . '/' . self::TEST_FILE;
  const TEST_UPLOAD_DIR = [
    'baseurl' => self::UPLOADS_URL,
    'basedir' => '/var/www/uploads'
  ];

  // Adds Mockery expectations to the PHPUnit assertions count.
  use MockeryPHPUnitIntegration;

  public function setUp(): void {
		parent::setUp();
		Monkey\setUp();

    // WP mocks
    Functions\when('wp_upload_dir')->justReturn( self::TEST_UPLOAD_DIR );
    Functions\when('wp_normalize_path')->returnArg();
        
    // WP_Stateless mocks
    Filters\expectApplied('wp_stateless_file_name')
      ->andReturn( self::TEST_FILE );

    Filters\expectApplied('wp_stateless_handle_root_dir')
      ->andReturn( 'uploads' );

    Functions\when('ud_get_stateless_media')->justReturn( WPStatelessStub::instance() );
  }
	
  public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

  public function testShouldInitHooks() {
    $gravityFormSignature = new GravityFormSignature();

    $gravityFormSignature->module_init([]);

    self::assertNotFalse( has_filter('gform_save_field_value', [ $gravityFormSignature, 'gform_save_field_value' ]) );
    self::assertNotFalse( has_filter('site_url', [ $gravityFormSignature, 'signature_url' ]) );
    self::assertNotFalse( has_filter('gform_signature_delete_file_pre_delete_entry', [ $gravityFormSignature, 'delete_signature' ]) );
  }

  public function testShouldSaveFieldValue() {
    $gravityFormSignature = new GravityFormSignature();

    Actions\expectDone('sm:sync::syncFile')->once();

    $gravityFormSignature->gform_save_field_value(self::TEST_FILE, null, null, null, null);

    $this->assertTrue(true);
  }

  public function testShouldProcessSignatureUrl() {
    $gravityFormSignature = new GravityFormSignature();

    Functions\when('rgar')->justReturn( 'GFSignature' );

    Actions\expectDone('sm:sync::syncFile')->once();

    $this->assertEquals(
      self::DST_URL,
      $gravityFormSignature->signature_url(self::SRC_URL, null, null, null) 
    );
  }

  public function testShouldRemoveSignatureFile() {
    $gravityFormSignature = new GravityFormSignature();

    Functions\when('rgar')->justReturn( null );

    Actions\expectDone('sm:sync::deleteFile')
      ->once()
      ->with(self::TEST_FILE);

    $gravityFormSignature->delete_signature(self::TEST_FILE, null, 15, null);
    
    $this->assertTrue(true);
  }
}

function debug_backtrace($a, $b) {
  return [
    [
      'class' => 'GFSignature',
      'function' => 'get_signature_url',
      'args' => [
        ClassGravityFormsSignatureTest::TEST_FILE,
      ]
    ],
  ];
}
