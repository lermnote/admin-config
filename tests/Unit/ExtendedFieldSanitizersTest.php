<?php
/**
 * Extended / advanced field sanitizer tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\FieldTypes\AdvancedFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\AsyncFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\BuiltinFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\ExtendedPrimitiveFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

final class ExtendedFieldSanitizersTest extends TestCase {

	public function testPaletteSanitizerKeepsEmptyChoiceEmpty(): void {
		$this->assertSame(
			'',
			$this->store()->sanitize_field(
				array(
					'id'      => 'scheme',
					'type'    => 'palette',
					'choices' => array(
						'blue' => array( '#2271b1' ),
					),
					'default' => 'blue',
				),
				''
			)
		);
	}

	public function testImageSelectSanitizerPreservesChoiceKeys(): void {
		$this->assertSame(
			'Red',
			$this->store()->sanitize_field(
				array(
					'id'      => 'theme',
					'type'    => 'image_select',
					'choices' => array(
						'Red' => 'https://example.test/red.png',
					),
				),
				'Red'
			)
		);
	}

	public function testIconSanitizerKeepsEmptyChoiceEmpty(): void {
		$field_types = new FieldTypeRegistry();

		foreach ( AdvancedFieldTypes::definitions() as $type => $definition ) {
			$field_types->register( (string) $type, $definition );
		}

		$store = new OptionStore(
			array(
				'id'    => 'unit_extended_field_sanitizers',
				'store' => array(
					'type' => 'option',
					'key'  => 'unit_extended_field_sanitizers',
				),
			),
			$field_types
		);

		$this->assertSame(
			'',
			$store->sanitize_field(
				array(
					'id'      => 'marker',
					'type'    => 'icon',
					'choices' => array(
						'dashicons-star' => 'Star',
					),
					'default' => 'dashicons-star',
				),
				''
			)
		);
	}

	public function testNumericSanitizerKeepsRawNumberWhenCastIsEmpty(): void {
		$this->assertSame(
			3.7,
			$this->store()->sanitize_field(
				array(
					'id'      => 'ratio',
					'type'    => 'number',
					'cast'    => '',
					'default' => 1,
				),
				'3.7'
			)
		);
	}

	public function testAjaxSelectReplaysEmptyArrayOnMissingSubmission(): void {
		$field_types = new FieldTypeRegistry();

		foreach ( AsyncFieldTypes::definitions() as $type => $definition ) {
			$field_types->register( (string) $type, $definition );
		}

		$merged = ValidationFlash::merge_submitted_values(
			array(
				'featured' => array( 'studio-preview' ),
			),
			array(),
			array(
				array(
					'id'       => 'featured',
					'type'     => 'ajax_select',
					'multiple' => true,
				),
			),
			$field_types
		);

		$this->assertSame( array(), $merged['featured'] );
	}

	private function store(): OptionStore {
		$field_types = new FieldTypeRegistry();

		foreach ( array_merge( BuiltinFieldTypes::definitions(), ExtendedPrimitiveFieldTypes::definitions() ) as $type => $definition ) {
			$field_types->register( (string) $type, $definition );
		}

		return new OptionStore(
			array(
				'id'    => 'unit_extended_field_sanitizers',
				'store' => array(
					'type' => 'option',
					'key'  => 'unit_extended_field_sanitizers',
				),
			),
			$field_types
		);
	}
}
