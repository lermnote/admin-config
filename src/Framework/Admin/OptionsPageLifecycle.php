<?php
/**
 * Options page lifecycle: menu registration and asset enqueueing.
 *
 * Extracted from OptionsPage. Handles WordPress admin menu registration
 * and script/style enqueueing for the settings page.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Admin;

use Lerm\AdminConfig\Framework\Contracts\AssetPathResolver;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\Support\I18nStrings;
use Lerm\AdminConfig\Framework\Support\PackageAssets;
use Lerm\AdminConfig\Framework\Support\ScriptAssetMetadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionsPageLifecycle {

	/**
	 * Page definition.
	 *
	 * @var array<string, mixed>
	 */
	private array $definition;

	private AssetResolver $asset_resolver;
	private string $capability;
	private string $page_slug;
	private bool $network_admin;
	private string $js_global;

	/**
	 * @var callable
	 */
	private $render_callback;

	/**
	 * Settings page hook suffix.
	 */
	private string $page_hook = '';

	/**
	 * @param array<string, mixed> $definition      Page definition.
	 * @param callable             $render_callback Callback for rendering the page content.
	 */
	public function __construct(
		array $definition,
		AssetResolver $asset_resolver,
		string $capability,
		string $page_slug,
		bool $network_admin,
		string $js_global,
		callable $render_callback
	) {
		$this->definition      = $definition;
		$this->asset_resolver  = $asset_resolver;
		$this->capability      = $capability;
		$this->page_slug       = $page_slug;
		$this->network_admin   = $network_admin;
		$this->js_global       = $js_global;
		$this->render_callback = $render_callback;
	}

	/**
	 * The page hook suffix, set after register_menu() runs.
	 */
	public function page_hook(): string {
		return $this->page_hook;
	}

	/**
	 * Register the page under its configured parent.
	 */
	public function register_menu(): void {
		$menu = is_array( $this->definition['menu'] ?? null ) ? $this->definition['menu'] : array();

		$this->page_hook = (string) add_submenu_page(
			(string) ( $menu['parent_slug'] ?? 'themes.php' ),
			(string) ( $menu['page_title'] ?? __( 'Admin Config', 'lerm-admin-config' ) ),
			(string) ( $menu['menu_title'] ?? __( 'Admin Config', 'lerm-admin-config' ) ),
			$this->capability,
			$this->page_slug,
			$this->render_callback
		);
	}

	/**
	 * Enqueue page assets.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		$this->enqueue_support_assets( $this->page_slug );
	}

	/**
	 * Enqueue the shared field UI assets for non-page containers.
	 */
	public function enqueue_support_assets( string $handle_suffix = '' ): void {
		$code_editor_settings = wp_enqueue_code_editor(
			array(
				'type' => 'text/html',
			)
		);

		wp_enqueue_media();
		wp_enqueue_style( 'wp-codemirror' );
		wp_enqueue_script( 'wp-theme-plugin-editor' );

		$suffix     = '' !== $handle_suffix ? sanitize_key( $handle_suffix ) : $this->page_slug;
		$css_handle = 'lerm-admin-config-' . $suffix;
		$js_handle  = 'lerm-admin-config-js-' . $suffix;
		$script     = $this->script_asset();

		wp_enqueue_style(
			$css_handle,
			$this->asset_url( 'admin-config.css' ),
			array( 'wp-codemirror' ),
			$this->asset_version()
		);
		wp_enqueue_script(
			$js_handle,
			$this->asset_url( $script['file'] ),
			$script['dependencies'],
			$script['version'],
			true
		);

		wp_set_script_translations(
			$js_handle,
			'lerm-admin-config',
			dirname( PackageAssets::directory() ) . '/languages/'
		);

		wp_localize_script(
			$js_handle,
			$this->js_global,
			I18nStrings::for_admin_page( $code_editor_settings )
		);
	}

	/**
	 * Resolve the correct WordPress admin menu hook for this page.
	 */
	public function menu_action(): string {
		return $this->network_admin ? 'network_admin_menu' : 'admin_menu';
	}

	/**
	 * Asset URL, delegated to the injected AssetResolver.
	 */
	private function asset_url( string $asset ): string {
		return $this->asset_resolver->url( $asset );
	}

	/**
	 * Asset path, delegated to resolvers that expose filesystem locations.
	 */
	private function asset_path( string $asset ): string {
		if ( $this->asset_resolver instanceof AssetPathResolver ) {
			return $this->asset_resolver->path( $asset );
		}

		return PackageAssets::path( $asset );
	}

	/**
	 * Resolve the built JavaScript asset metadata.
	 *
	 * @return array{file: string, dependencies: array<int, string>, version: string}
	 */
	private function script_asset(): array {
		return ScriptAssetMetadata::resolve(
			'admin-config',
			'admin-config.js',
			array( 'wp-theme-plugin-editor', 'wp-api-fetch' ),
			$this->asset_version(),
			function ( string $asset ): string {
				return $this->asset_path( $asset );
			}
		);
	}

	/**
	 * Asset version, delegated to the injected AssetResolver.
	 */
	private function asset_version(): string {
		$version = $this->asset_resolver->version();
		$assets  = array(
			$this->asset_path( 'admin-config.css' ),
			$this->asset_path( 'admin-config.js' ),
		);
		$mtime   = 0;

		foreach ( $assets as $asset_path ) {
			if ( is_readable( $asset_path ) ) {
				$asset_mtime = (int) filemtime( $asset_path );

				if ( $asset_mtime > $mtime ) {
					$mtime = $asset_mtime;
				}
			}
		}

		return $mtime > 0 ? $version . '.' . (string) $mtime : $version;
	}
}
