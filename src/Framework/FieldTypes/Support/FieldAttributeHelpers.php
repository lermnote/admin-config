<?php
/**
 * Shared attribute escaping and rendering helpers for field type classes.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\FieldTypes\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait FieldAttributeHelpers {

	/**
	 * Escapes a scalar field attribute with a fallback default.
	 *
	 * Used for min, max, step, placeholder, rows, and similar values
	 * that are output as HTML attribute values.
	 *
	 * @param array  $field   Field definition array.
	 * @param string $key     Attribute key in the field array.
	 * @param mixed  $fallback Fallback value when the key is absent.
	 * @return string Escaped attribute value ready for output.
	 */
	private static function numeric_attr( array $field, string $key, $fallback = '' ): string {
		return esc_attr( (string) ( $field[ $key ] ?? $fallback ) );
	}

	/**
	 * Reads a boolean flag from a field definition.
	 *
	 * @param array  $field    Field definition array.
	 * @param string $key      Flag key in the field array.
	 * @param bool   $fallback Default when the key is absent.
	 * @return bool
	 */
	private static function flag( array $field, string $key, bool $fallback ): bool {
		return array_key_exists( $key, $field ) ? ! empty( $field[ $key ] ) : $fallback;
	}

	/**
	 * Renders a nested-field validation warning message.
	 *
	 * @param string $message Warning text to display.
	 */
	private static function render_nested_warning( string $message ): void {
		printf(
			'<p class="description" style="color:#b91c1c;font-style:italic">%s</p>',
			esc_html( $message )
		);
	}

	/**
	 * Renders the shared up/down spinner buttons for number inputs.
	 */
	private static function number_input_actions(): void {
		printf(
			'<span class="lerm-number-input__actions"><button type="button" class="lerm-number-input__button" data-lerm-number-step="up" aria-label="%1$s"><span aria-hidden="true">&#9650;</span></button><button type="button" class="lerm-number-input__button" data-lerm-number-step="down" aria-label="%2$s"><span aria-hidden="true">&#9660;</span></button></span>',
			esc_attr__( 'Increase value', 'lerm-admin-config' ),
			esc_attr__( 'Decrease value', 'lerm-admin-config' )
		);
	}
}
