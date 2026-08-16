<?php
/**
 * Plugin-install bootstrap for the admin config runtime.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress;

use Lerm\AdminConfig\Framework\Framework;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PluginBootstrap {

	public static function boot( string $plugin_file, ?callable $registrar = null ): Runtime {
		$runtime = new Runtime(
			new Framework(
				new PluginAssetResolver( $plugin_file )
			)
		);

		RuntimeBootstrapper::schedule( $runtime, $registrar, 'plugin' );

		return $runtime;
	}
}
