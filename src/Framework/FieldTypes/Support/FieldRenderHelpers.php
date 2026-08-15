<?php
/**
 * Shared rendering helpers for field type classes.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\FieldTypes\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FieldRenderHelpers {

	public static function name_attr( string $template ): string {
		return '' !== $template
			? ' data-name-template="' . esc_attr( $template ) . '"'
			: '';
	}

	public static function id_attr( string $template ): string {
		return '' !== $template
			? ' data-id-template="' . esc_attr( $template ) . '"'
			: '';
	}

	public static function sub_name( string $field_name, string $key ): string {

		return $field_name . '[' . $key . ']';
	}

	public static function sub_template( string $template, string $key ): string {
		return '' !== $template ? $template . '[' . $key . ']' : '';
	}

	public static function sub_id( string $input_id, string $key ): string {
		return $input_id . '__' . sanitize_html_class( str_replace( '_', '-', $key ) );
	}

	public static function sub_id_template( string $template, string $key ): string {
		return '' !== $template
			? $template . '__' . sanitize_html_class( str_replace( '_', '-', $key ) )
			: '';
	}

	/**
	 * Normalize accordion/tabbed panel items into a uniform shape.
	 *
	 * @param array<string, mixed> $field
	 * @return array<int, array<string, mixed>>
	 */
	public static function panel_items( array $field ): array {
		$items      = is_array( $field['items'] ?? null ) ? $field['items'] : array();
		$normalized = array();

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$item_id    = isset( $item['id'] ) && is_scalar( $item['id'] ) ? sanitize_key( (string) $item['id'] ) : '';
			$item_title = isset( $item['title'] ) && is_scalar( $item['title'] ) ? (string) $item['title'] : '';
			$item_id    = '' !== $item_id ? $item_id : 'item_' . (string) ( (int) $index + 1 );

			$normalized[] = array(
				'id'          => $item_id,
				'title'       => '' !== $item_title ? $item_title : ucfirst( str_replace( '_', ' ', $item_id ) ),
				'description' => isset( $item['description'] ) && is_scalar( $item['description'] ) ? (string) $item['description'] : '',
				'fields'      => is_array( $item['fields'] ?? null ) ? $item['fields'] : array(),
				'open'        => ! empty( $item['open'] ),
			);
		}

		return $normalized;
	}
}
