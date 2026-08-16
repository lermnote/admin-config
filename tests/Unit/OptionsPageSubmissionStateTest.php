<?php
/**
 * Options page submission state tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\FieldTypes\BuiltinFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\ExtendedPrimitiveFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\FieldTypes\StructuredFieldTypes;
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

final class OptionsPageSubmissionStateTest extends TestCase {

	public function testMissingSubmissionRulesComeFromFieldTypeMetadata(): void {
		$definition = array(
			'id'       => 'unit_missing_submission_state',
			'store'    => array(
				'type' => 'option',
				'key'  => 'unit_missing_submission_state',
			),
			'sections' => array(
				'general' => array(
					'fields' => array(
						array(
							'id'       => 'channels',
							'type'     => 'select',
							'multiple' => true,
							'choices'  => array(
								'news' => 'News',
								'blog' => 'Blog',
							),
						),
						array(
							'id'      => 'audiences',
							'type'    => 'checkbox_list',
							'choices' => array(
								'members' => 'Members',
								'guests'  => 'Guests',
							),
						),
						array(
							'id'      => 'flags',
							'type'    => 'checkbox',
							'choices' => array(
								'beta' => 'Beta',
							),
						),
						array(
							'id'     => 'cards',
							'type'   => 'group',
							'fields' => array(
								array(
									'id'   => 'title',
									'type' => 'text',
								),
							),
						),
						array(
							'id'      => 'tone',
							'type'    => 'select',
							'choices' => array(
								'calm' => 'Calm',
								'bold' => 'Bold',
							),
						),
						array(
							'id'                       => 'slug',
							'type'                     => 'text',
							'missing_submission_value' => 'untitled',
						),
						array(
							'id'   => 'headline',
							'type' => 'text',
						),
					),
				),
			),
		);
		$merged     = $this->merge_section_submitted_values(
			$definition,
			'general',
			array(
				'channels'  => array( 'news' ),
				'audiences' => array( 'members' ),
				'flags'     => array( 'beta' ),
				'cards'     => array(
					array(
						'title' => 'Existing card',
					),
				),
				'tone'      => 'calm',
				'slug'      => 'existing-slug',
				'headline'  => 'Saved headline',
			),
			array(
				'headline' => 'Draft headline',
			)
		);

		$this->assertSame( array(), $merged['channels'] );
		$this->assertSame( array(), $merged['audiences'] );
		$this->assertSame( array(), $merged['flags'] );
		$this->assertSame( array(), $merged['cards'] );
		$this->assertSame( 'calm', $merged['tone'] );
		$this->assertSame( 'untitled', $merged['slug'] );
		$this->assertSame( 'Draft headline', $merged['headline'] );
	}

	/**
	 * Merge flashed submission values through the shared ValidationFlash
	 * implementation, restricted to one section's fields.
	 *
	 * @param array<string, mixed> $definition
	 * @param array<string, mixed> $values
	 * @param array<string, mixed> $submitted
	 * @return array<string, mixed>
	 */
	private function merge_section_submitted_values( array $definition, string $section_id, array $values, array $submitted ): array {
		$field_types = new FieldTypeRegistry();

		foreach ( array_merge( BuiltinFieldTypes::definitions(), ExtendedPrimitiveFieldTypes::definitions(), StructuredFieldTypes::definitions() ) as $type => $field_type_definition ) {
			$field_types->register( (string) $type, $field_type_definition );
		}

		$section = PageSchema::section( $definition, $section_id );

		return ValidationFlash::merge_submitted_values(
			$values,
			$submitted,
			null !== $section ? PageSchema::section_fields( $section ) : array(),
			$field_types
		);
	}
}
