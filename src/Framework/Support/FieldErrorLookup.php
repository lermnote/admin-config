<?php
/**
 * Stateless field error lookup utility.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FieldErrorLookup {

	/**
	 * Collect all unique error messages for a field path.
	 *
	 * @param array<string, mixed> $field_errors        Field error map.
	 * @param string               $field_path          Dotted field path.
	 * @param bool                 $include_descendants Whether to include child-path errors.
	 * @return array<int, string>
	 */
	public static function messages( array $field_errors, string $field_path, bool $include_descendants = false ): array {
		$messages = array();

		foreach ( $field_errors as $path => $raw_messages ) {
			if ( ! self::matches_path( (string) $path, $field_path, $include_descendants ) ) {
				continue;
			}

			foreach ( is_array( $raw_messages ) ? $raw_messages : array( $raw_messages ) as $message ) {
				$message = is_scalar( $message ) ? trim( (string) $message ) : '';

				if ( '' === $message || in_array( $message, $messages, true ) ) {
					continue;
				}

				$messages[] = $message;
			}
		}

		return $messages;
	}

	/**
	 * Check whether a field path has any errors.
	 *
	 * Short-circuits on first match — avoids building the full messages array
	 * when only a boolean result is needed.
	 *
	 * @param array<string, mixed> $field_errors        Field error map.
	 * @param string               $field_path          Dotted field path.
	 * @param bool                 $include_descendants Whether to include child-path errors.
	 */
	public static function has_errors( array $field_errors, string $field_path, bool $include_descendants = false ): bool {
		foreach ( $field_errors as $path => $raw_messages ) {
			if ( ! self::matches_path( (string) $path, $field_path, $include_descendants ) ) {
				continue;
			}

			if ( ! is_array( $raw_messages ) ) {
				return true;
			}

			foreach ( $raw_messages as $message ) {
				if ( is_scalar( $message ) && '' !== trim( (string) $message ) ) {
					return true;
				}
			}

			// The key matched but all messages were empty: no real error.
		}

		return false;
	}

	/**
	 * Whether an error path matches the queried field path.
	 */
	private static function matches_path( string $path, string $field_path, bool $include_descendants ): bool {
		if ( $path === $field_path ) {
			return true;
		}

		return $include_descendants && '' !== $field_path && FieldPath::starts_with( $path, $field_path );
	}
}
