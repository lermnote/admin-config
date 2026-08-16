<?php
/**
 * Storage backend backed by WordPress site options.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SiteOptionBackend extends OptionBackend {

	public function key(): string {
		return 'site_' . $this->option_name;
	}

	/**
	 * @return mixed
	 */
	protected function read_raw() {
		return get_site_option( $this->option_name );
	}

	protected function persist_raw( array $data ): bool {
		return update_site_option( $this->option_name, $data );
	}

	protected function delete_raw(): bool {
		return delete_site_option( $this->option_name );
	}
}
