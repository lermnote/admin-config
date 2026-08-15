<?php
/**
 * Storage backend backed by WordPress options (get_option / update_option).
 *
 * This is the default backend used by the admin config runtime for theme/plugin
 * settings pages.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

use Lerm\AdminConfig\Framework\Contracts\StorageBackend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OptionBackend implements StorageBackend {

	protected string $option_name;

	public function __construct( string $option_name ) {
		$this->option_name = sanitize_key( $option_name );
	}

	public function read(): array {
		$data = $this->read_raw();
		return is_array( $data ) ? $data : array();
	}

	public function write( array $data ): bool {
		$result = $this->persist_raw( $data );

		if ( false === $result ) {
			// update_option returns false both on DB error AND when the value
			// hasn't changed. Distinguish the two by re-reading. Compare
			// normalized JSON to avoid int/string type coercion and key-
			// reordering false negatives from the serialize round-trip.
			$stored = $this->read_raw();
			if ( ! is_array( $stored ) ) {
				return false;
			}
			$stored_json = wp_json_encode( $stored );
			$data_json   = wp_json_encode( $data );
			return is_string( $stored_json ) && $stored_json === $data_json;
		}

		return $result;
	}

	public function key(): string {
		return $this->option_name;
	}

	public function delete(): bool {
		return $this->delete_raw();
	}

	/**
	 * @return mixed
	 */
	protected function read_raw() {
		return get_option( $this->option_name );
	}

	protected function persist_raw( array $data ): bool {
		return update_option( $this->option_name, $data );
	}

	protected function delete_raw(): bool {
		return delete_option( $this->option_name );
	}
}
