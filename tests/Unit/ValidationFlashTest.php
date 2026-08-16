<?php
/**
 * ValidationFlash render-surface tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\FieldTypes\BuiltinFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

final class ValidationFlashTest extends TestCase {

	public function testRenderValuesFallsBackToSimpleMergeWithoutDefinition(): void {
		$merged = ValidationFlash::render_values(
			array( 'headline' => 'Saved' ),
			array(
				'submitted' => array(
					'headline' => 'Draft',
				),
			)
		);

		$this->assertSame(
			array(
				'headline' => 'Draft',
			),
			$merged
		);
	}

	public function testRenderValuesPreservesUnknownSubmittedKeys(): void {
		$field_types = new FieldTypeRegistry();

		foreach ( BuiltinFieldTypes::definitions() as $type => $definition ) {
			$field_types->register( (string) $type, $definition );
		}

		$merged = ValidationFlash::render_values(
			array( 'headline' => 'Saved' ),
			array(
				'submitted' => array(
					'headline'    => 'Draft',
					'dynamic_key' => 'Kept',
				),
			),
			array(
				'sections' => array(
					'general' => array(
						'fields' => array(
							array(
								'id'   => 'headline',
								'type' => 'text',
							),
						),
					),
				),
			),
			$field_types
		);

		$this->assertSame(
			array(
				'headline'    => 'Draft',
				'dynamic_key' => 'Kept',
			),
			$merged
		);
	}

	public function testNoticeResolvesStoredClassAndMessage(): void {
		ValidationFlash::store(
			'options_page',
			'unit_flash_notice',
			'unit_flash_notice',
			array(
				'class'   => 'notice-error',
				'message' => 'Please review the highlighted fields.',
			)
		);

		$flash = ValidationFlash::consume( 'options_page', 'unit_flash_notice', 'unit_flash_notice' );

		$this->assertSame(
			array(
				'class'   => 'notice-error',
				'message' => 'Please review the highlighted fields.',
			),
			ValidationFlash::notice( $flash )
		);
	}

	public function testNoticeReturnsNullForMissingMessage(): void {
		ValidationFlash::store(
			'options_page',
			'unit_flash_notice_empty',
			'unit_flash_notice_empty',
			array(
				'class' => 'notice-error',
			)
		);

		$flash = ValidationFlash::consume( 'options_page', 'unit_flash_notice_empty', 'unit_flash_notice_empty' );

		$this->assertNull( ValidationFlash::notice( $flash ) );
	}

	public function testFieldErrorsPassesThroughTheErrorMapUnchanged(): void {
		ValidationFlash::store(
			'profile',
			'unit_flash_errors',
			'7',
			array(
				'errors' => array(
					'entry_badge.slug'  => array( 'Slug is too short.' ),
					'entry_badge.label' => array( 'Label is required.' ),
				),
			)
		);

		$flash = ValidationFlash::consume( 'profile', 'unit_flash_errors', '7' );

		$this->assertSame(
			array(
				'entry_badge.slug'  => array( 'Slug is too short.' ),
				'entry_badge.label' => array( 'Label is required.' ),
			),
			ValidationFlash::field_errors( $flash )
		);
	}

	public function testFieldErrorsReturnsEmptyMapWithoutFlashErrors(): void {
		$this->assertSame( array(), ValidationFlash::field_errors( null ) );
		$this->assertSame( array(), ValidationFlash::field_errors( array( 'message' => 'No errors.' ) ) );
	}
}
