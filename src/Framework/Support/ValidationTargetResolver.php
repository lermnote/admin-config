<?php
/**
 * Shared validation-target routing for admin pages and REST responses.
 *
 * Resolves which tab/subsection owns a validation error so both the non-JS
 * save redirect (OptionsPage) and the REST save response can point the
 * client at the first errored field.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ValidationTargetResolver {

	/**
	 * Resolve the first tab/subsection that contains a validation error.
	 *
	 * @param array<string, mixed>             $definition Schema definition.
	 * @param array<string, array<int, string>> $errors     Validation errors.
	 * @return array{tab: string, subsection: string}
	 */
	public static function first_validation_target( array $definition, array $errors ): array {
		$targets      = self::field_section_map( $definition );
		$fallback_tab = (string) array_key_first( PageSchema::sections( $definition ) );

		foreach ( array_keys( $errors ) as $path ) {
			$field_id = sanitize_key( (string) strtok( (string) $path, '.' ) );
			$target   = $targets[ $field_id ] ?? array(
				'tab'        => '',
				'subsection' => '',
			);

			if ( '' !== $target['tab'] ) {
				return $target;
			}
		}

		return array(
			'tab'        => $fallback_tab,
			'subsection' => '',
		);
	}

	/**
	 * Build the field-id → {tab, subsection} map for a schema definition.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, array{tab: string, subsection: string}>
	 */
	public static function field_section_map( array $definition ): array {
		$map = array();

		foreach ( PageSchema::sections( $definition ) as $section_id => $section ) {
			$groups = PageSchema::section_groups( $section );

			foreach ( PageSchema::section_fields( $section ) as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}

				$field_id = sanitize_key( (string) ( $field['id'] ?? '' ) );

				if ( '' === $field_id ) {
					continue;
				}

				$subsection = '';

				foreach ( $groups as $group ) {
					foreach ( (array) ( $group['fields'] ?? array() ) as $group_field ) {
						if ( sanitize_key( (string) ( $group_field['id'] ?? '' ) ) === $field_id ) {
							$subsection = sanitize_key( (string) ( $group['id'] ?? '' ) );
							break 2;
						}
					}
				}

				$map[ $field_id ] = array(
					'tab'        => (string) $section_id,
					'subsection' => $subsection,
				);
			}
		}

		return $map;
	}

	/**
	 * Determine whether a section should render secondary navigation.
	 *
	 * @param array<string, mixed>             $section Section definition.
	 * @param array<int, array<string, mixed>> $groups  Section groups.
	 */
	public static function section_uses_subsections( array $section, array $groups ): bool {
		if ( array_key_exists( 'use_subsections', $section ) ) {
			return ! empty( $section['use_subsections'] ) && count( $groups ) > 1;
		}

		return count( $groups ) > 1;
	}
}
