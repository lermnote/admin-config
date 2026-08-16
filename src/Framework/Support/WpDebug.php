<?php
/**
 * Shared WP_DEBUG flag access.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WpDebug {

	public static function enabled(): bool {
		return defined( 'WP_DEBUG' ) ? (bool) constant( 'WP_DEBUG' ) : false;
	}
}
