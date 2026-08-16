<?php
/**
 * Embedded-mode bootstrap for themes or bundled packages.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress;

use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\Framework\Resolvers\DefaultAssetResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmbeddedBootstrap {

	public static function boot( string $assets_url, string $version_constant = 'LERM_VERSION', ?callable $registrar = null ): Runtime {
		$runtime = new Runtime(
			new Framework(
				new DefaultAssetResolver( $assets_url, $version_constant )
			)
		);

		RuntimeBootstrapper::schedule( $runtime, $registrar, 'embedded' );

		return $runtime;
	}
}
