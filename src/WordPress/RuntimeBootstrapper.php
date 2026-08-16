<?php
/**
 * Shared runtime boot scheduling for plugin and embedded bootstrap modes.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RuntimeBootstrapper {

	/**
	 * Schedule the registrar + boot sequence at init priority 0, or run it
	 * immediately when init has already fired.
	 */
	public static function schedule( Runtime $runtime, ?callable $registrar, string $mode ): void {
		$boot_runtime = static function () use ( $runtime, $registrar, $mode ): void {
			if ( is_callable( $registrar ) ) {
				call_user_func( $registrar, $runtime );
			}

			if ( is_admin() ) {
				$runtime->boot();
			}

			do_action( 'lerm_admin_config_booted', $runtime, $mode );
		};

		if ( function_exists( 'did_action' ) && 0 === did_action( 'init' ) ) {
			add_action( 'init', $boot_runtime, 0 );
		} else {
			$boot_runtime();
		}
	}
}
