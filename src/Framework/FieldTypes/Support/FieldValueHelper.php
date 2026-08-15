<?php
/**
 * Shared scalar casting and field value sanitization utilities.
 *
 * Extracts duplicated helpers from BuiltinFieldTypes,
 * ExtendedPrimitiveFieldTypes and AsyncFieldTypes into a single
 * canonical location.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\FieldTypes\Support;

use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FieldValueHelper {

	/**
	 * Cast a string value to int or float based on the cast parameter.
	 *
	 * @return string|int|float
	 */
	public static function cast_scalar_value( string $value, string $cast ) {
		if ( 'int' === $cast ) {
			return (int) $value;
		}

		if ( 'float' === $cast ) {
			return (float) $value;
		}

		return $value;
	}

	/**
	 * Sanitize a numeric value with optional min/max clamping.
	 *
	 * @param mixed $value
	 * @return int|float
	 */
	public static function sanitize_numeric_value( array $field, $value ) {
		$default = $field['default'] ?? 0;
		$cast    = (string) ( $field['cast'] ?? 'int' );
		$number  = is_numeric( $value ) ? (float) $value : ( is_numeric( $default ) ? (float) $default : 0.0 );
		$min     = isset( $field['min'] ) && is_numeric( $field['min'] ) ? (float) $field['min'] : null;
		$max     = isset( $field['max'] ) && is_numeric( $field['max'] ) ? (float) $field['max'] : null;

		if ( null !== $min && $number < $min ) {
			$number = $min;
		}

		if ( null !== $max && $number > $max ) {
			$number = $max;
		}

		return ( '' === $cast || 'float' === $cast ) ? $number : (int) round( $number );
	}

	/**
	 * Sanitize a checkbox-list or multi-choice submission against allowed choices.
	 *
	 * @param mixed $value
	 * @return array<int, string>
	 */
	public static function sanitize_checkbox_list_values( array $field, $value, bool $strict ): array {
		$choices = $strict ? PageSchema::choices( $field ) : array();
		$values  = is_array( $value ) ? $value : array();
		$clean   = array();

		foreach ( $values as $item ) {
			$item = is_scalar( $item ) ? (string) $item : '';

			if ( '' === $item ) {
				continue;
			}

			if ( ! $strict || array_key_exists( $item, $choices ) ) {
				$clean[] = $item;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Sanitize a URL-ish field value (shared by the url and upload controls).
	 *
	 * @param mixed $value
	 */
	public static function sanitize_url_value( $value ): string {
		return esc_url_raw( PageSchema::scalar_value( $value, '', true ) );
	}

	/**
	 * Sanitize a media value into the {id, url, thumbnail} payload shape.
	 *
	 * Accepts an attachment id (scalar or array 'id'), or a url-only payload
	 * for externally hosted media. Shared by the media and background-image
	 * controls.
	 *
	 * @param mixed $value
	 * @return array<string, int|string>
	 */
	public static function sanitize_media_value( $value ): array {
		$attachment_id = 0;
		$url           = '';

		if ( is_array( $value ) ) {
			$attachment_id = absint( $value['id'] ?? 0 );
			$url           = esc_url_raw( PageSchema::scalar_value( $value['url'] ?? '', '', true ) );
		} elseif ( is_scalar( $value ) ) {
			$attachment_id = absint( $value );
		}

		if ( $attachment_id > 0 ) {
			$attachment_url = (string) wp_get_attachment_url( $attachment_id );

			if ( '' !== $attachment_url ) {
				return array_filter(
					array(
						'id'        => $attachment_id,
						'url'       => $attachment_url,
						'thumbnail' => (string) wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
					)
				);
			}
		}

		if ( '' !== $url ) {
			return array(
				'id'        => 0,
				'url'       => $url,
				'thumbnail' => is_array( $value ) ? esc_url_raw( PageSchema::scalar_value( $value['thumbnail'] ?? $url, '', true ) ) : $url,
			);
		}

		return array();
	}
}
