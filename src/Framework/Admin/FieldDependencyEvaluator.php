<?php
/**
 * Field dependency evaluator for conditional field visibility.
 *
 * Pure logic extracted from OptionsPage. No WordPress global state dependencies.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Admin;

use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FieldDependencyEvaluator {

	/**
	 * Schema definition.
	 *
	 * @var array<string, mixed>
	 */
	private array $definition;

	/**
	 * @var array<string, bool>|null
	 */
	private ?array $controller_fields_cache = null;

	/**
	 * @param array<string, mixed> $definition Schema definition.
	 */
	public function __construct( array $definition ) {
		$this->definition = $definition;
	}

	/**
	 * Return the change-listener attribute for fields that control dependencies.
	 *
	 * @param array<string, mixed> $field Field definition.
	 */
	public function controller_attribute( array $field ): string {
		$field_id = isset( $field['id'] ) ? sanitize_key( (string) $field['id'] ) : '';

		if ( '' === $field_id ) {
			return '';
		}

		return isset( $this->controller_fields()[ $field_id ] )
			? ' data-lerm-controller="1"'
			: '';
	}

	/**
	 * Resolve whether a field's dependency chain is currently satisfied.
	 *
	 * @param array<string, mixed> $field  Field definition.
	 * @param array<string, mixed> $values Current form values.
	 * @param array<string, bool>  $seen   Recursion guard for malformed cycles.
	 */
	public function is_satisfied( array $field, array $values, array $seen = array() ): bool {
		$dependency = $this->field_dependency( $field );

		if ( empty( $dependency ) ) {
			return true;
		}

		$field_id = isset( $field['id'] ) ? sanitize_key( (string) $field['id'] ) : '';

		if ( '' !== $field_id && isset( $seen[ $field_id ] ) ) {
			return false;
		}

		$controller_id = (string) $dependency['field'];
		$controller    = PageSchema::field( $this->definition, $controller_id );

		if ( ! is_array( $controller ) ) {
			return false;
		}

		if ( '' !== $field_id ) {
			$seen[ $field_id ] = true;
		}

		if ( ! $this->is_satisfied( $controller, $values, $seen ) ) {
			return false;
		}

		$actual = array_key_exists( $controller_id, $values )
			? $values[ $controller_id ]
			: ( $controller['default'] ?? '' );

		return $this->matches(
			$actual,
			(string) $dependency['operator'],
			$dependency['value']
		);
	}

	/**
	 * Build a set of field IDs that control other fields' visibility.
	 *
	 * @return array<string, bool>
	 */
	public function controller_fields(): array {
		if ( null !== $this->controller_fields_cache ) {
			return $this->controller_fields_cache;
		}

		$controllers = array();

		foreach ( PageSchema::fields( $this->definition ) as $field ) {
			$dependency = $this->field_dependency( $field );

			if ( empty( $dependency ) ) {
				continue;
			}

			$controllers[ (string) $dependency['field'] ] = true;
		}

		$this->controller_fields_cache = $controllers;

		return $controllers;
	}

	/**
	 * Extract the dependency tuple from a field definition.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return array<string, mixed>
	 */
	public function field_dependency( array $field ): array {
		$dependency = $field['dependency'] ?? null;

		if ( ! is_array( $dependency ) || empty( $dependency[0] ) ) {
			return array();
		}

		$controller = sanitize_key( (string) $dependency[0] );
		$operator   = isset( $dependency[1] ) && is_scalar( $dependency[1] ) ? trim( (string) $dependency[1] ) : '==';

		if ( '' === $controller ) {
			return array();
		}

		return array(
			'field'    => $controller,
			'operator' => '' !== $operator ? $operator : '==',
			'value'    => $dependency[2] ?? true,
		);
	}

	/**
	 * Evaluate a single dependency condition.
	 *
	 * @param mixed  $actual   Actual controller value.
	 * @param string $operator Comparison operator.
	 * @param mixed  $expected Expected dependency value.
	 */
	public function matches( $actual, string $operator, $expected ): bool {
		$operator        = '' !== trim( $operator ) ? trim( $operator ) : '==';
		$actual_values   = $this->scalar_list( $actual );
		$expected_values = $this->scalar_list( $expected );
		$expected_value  = (string) ( $expected_values[0] ?? '' );

		if ( '!=' === $operator || '!==' === $operator ) {
			return ! in_array( $expected_value, $actual_values, true );
		}

		if ( 'in' === $operator ) {
			return count( array_intersect( $actual_values, $expected_values ) ) > 0;
		}

		if ( 'not_in' === $operator || 'not in' === $operator ) {
			return 0 === count( array_intersect( $actual_values, $expected_values ) );
		}

		if ( in_array( $operator, array( '>', '>=', '<', '<=' ), true ) ) {
			$actual_number   = isset( $actual_values[0] ) && is_numeric( $actual_values[0] ) ? (float) $actual_values[0] : null;
			$expected_number = is_numeric( $expected_value ) ? (float) $expected_value : null;

			if ( null === $actual_number || null === $expected_number ) {
				return false;
			}

			if ( '>' === $operator ) {
				return $actual_number > $expected_number;
			}

			if ( '>=' === $operator ) {
				return $actual_number >= $expected_number;
			}

			if ( '<' === $operator ) {
				return $actual_number < $expected_number;
			}

			return $actual_number <= $expected_number;
		}

		return in_array( $expected_value, $actual_values, true );
	}

	/**
	 * Serialize a dependency value for use in a data attribute.
	 *
	 * @param mixed $value Dependency value.
	 */
	public function attribute_value( $value ): string {
		if ( is_array( $value ) ) {
			$encoded = wp_json_encode( array_values( $this->scalar_list( $value ) ) );

			return false !== $encoded ? $encoded : '';
		}

		return $this->scalar( $value );
	}

	/**
	 * Normalize a dependency value to a list of scalar strings.
	 *
	 * @param mixed $value Controller value.
	 * @return array<int, string>
	 */
	private function scalar_list( $value ): array {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'scalar' ), $value );
		}

		return array( $this->scalar( $value ) );
	}

	/**
	 * Normalize a dependency controller value for reliable string comparisons.
	 *
	 * @param mixed $value Controller value.
	 */
	private function scalar( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}

		if ( is_numeric( $value ) ) {
			return (string) $value;
		}

		return PageSchema::scalar_value( $value );
	}
}
