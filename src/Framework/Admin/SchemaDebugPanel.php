<?php
/**
 * Debug panel for schema introspection at runtime.
 *
 * Extracted from OptionsPage. Renders a collapsible JSON debug panel
 * when WP_DEBUG is enabled or the schema's view.debug flag is set.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Admin;

use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\Registry\FieldModuleRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SchemaDebugPanel {

	/**
	 * Schema definition.
	 *
	 * @var array<string, mixed>
	 */
	private array $definition;

	private ?FieldModuleRegistry $field_modules;

	private string $schema_id;
	private string $page_slug;
	private string $option_name;
	private string $capability;
	private bool $network_admin;

	/**
	 * @param array<string, mixed> $definition Schema definition.
	 */
	public function __construct(
		array $definition,
		?FieldModuleRegistry $field_modules,
		string $schema_id,
		string $page_slug,
		string $option_name,
		string $capability,
		bool $network_admin
	) {
		$this->definition    = $definition;
		$this->field_modules = $field_modules;
		$this->schema_id     = $schema_id;
		$this->page_slug     = $page_slug;
		$this->option_name   = $option_name;
		$this->capability    = $capability;
		$this->network_admin = $network_admin;
	}

	/**
	 * Whether the debug panel should be rendered.
	 */
	public function is_enabled(): bool {
		if ( ! current_user_can( $this->capability ) ) {
			return false;
		}

		$view = is_array( $this->definition['view'] ?? null ) ? $this->definition['view'] : array();

		if ( array_key_exists( 'debug', $view ) ) {
			return ! empty( $view['debug'] );
		}

		return defined( 'WP_DEBUG' ) ? (bool) constant( 'WP_DEBUG' ) : false;
	}

	/**
	 * Render the debug panel HTML.
	 */
	public function render(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$json = wp_json_encode(
			$this->payload(),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		if ( false === $json ) {
			return;
		}
		?>
		<section class="lerm-debug-panel" data-lerm-debug-panel>
			<div class="lerm-debug-panel__header">
				<div>
					<h3><?php esc_html_e( 'Runtime Debug', 'lerm-admin-config' ); ?></h3>
					<p><?php esc_html_e( 'Schema, storage, module, and data-source summary for this admin screen.', 'lerm-admin-config' ); ?></p>
				</div>
				<button type="button" class="button button-secondary" data-lerm-debug-copy><?php esc_html_e( 'Copy JSON', 'lerm-admin-config' ); ?></button>
			</div>
			<pre class="lerm-debug-panel__json" data-lerm-debug-json><?php echo esc_html( $json ); ?></pre>
		</section>
		<?php
	}

	/**
	 * Build the debug payload array.
	 *
	 * @return array<string, mixed>
	 */
	public function payload(): array {
		$container       = is_array( $this->definition['container'] ?? null ) ? $this->definition['container'] : array();
		$store           = is_array( $this->definition['store'] ?? null ) ? $this->definition['store'] : array();
		$menu            = is_array( $this->definition['menu'] ?? null ) ? $this->definition['menu'] : array();
		$sections        = PageSchema::sections( $this->definition );
		$section_summary = array();

		foreach ( $sections as $section_id => $section ) {
			$section_summary[ (string) $section_id ] = array(
				'title'  => (string) ( $section['title'] ?? $section_id ),
				'fields' => count( PageSchema::section_fields( $section ) ),
				'groups' => count( PageSchema::section_groups( $section ) ),
			);
		}

		return array(
			'schema_id'     => $this->schema_id,
			'page_slug'     => $this->page_slug,
			'option_name'   => $this->option_name,
			'capability'    => $this->capability,
			'network_admin' => $this->network_admin,
			'container'     => array(
				'type'       => (string) ( $container['type'] ?? 'options_page' ),
				'capability' => (string) ( $container['capability'] ?? $menu['capability'] ?? $this->capability ),
			),
			'store'         => array(
				'type' => (string) ( $store['type'] ?? 'option' ),
				'key'  => (string) ( $store['key'] ?? $this->option_name ),
			),
			'summary'       => array(
				'sections' => count( $sections ),
				'fields'   => count( PageSchema::fields( $this->definition ) ),
				'defaults' => count( PageSchema::defaults( $this->definition ) ),
			),
			'sections'      => $section_summary,
			'field_types'   => $this->field_types_for_debug(),
			'modules'       => $this->field_modules ? $this->field_modules->modules_for_definition( $this->definition ) : array(),
			'data_sources'  => $this->data_sources(),
		);
	}

	/**
	 * @return array<int, string>
	 */
	private function field_types_for_debug(): array {
		if ( $this->field_modules ) {
			return $this->field_modules->field_types_for_definition( $this->definition );
		}

		$types = array();

		foreach ( PageSchema::fields( $this->definition ) as $field ) {
			$type = sanitize_key( (string) ( $field['type'] ?? 'text' ) );

			if ( '' !== $type ) {
				$types[ $type ] = $type;
			}
		}

		return array_values( $types );
	}

	/**
	 * @return array<int, string>
	 */
	private function data_sources(): array {
		$sources = array();

		foreach ( PageSchema::sections( $this->definition ) as $section ) {
			$this->collect_data_sources_from_fields( PageSchema::section_fields( $section ), $sources );
		}

		return array_values( $sources );
	}

	/**
	 * @param array<int, array<string, mixed>> $fields
	 * @param array<string, string>            $sources
	 */
	private function collect_data_sources_from_fields( array $fields, array &$sources ): void {
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$source_id = sanitize_key( (string) ( $field['source'] ?? $field['data_source'] ?? '' ) );

			if ( '' !== $source_id ) {
				$sources[ $source_id ] = $source_id;
			}

			$child_fields = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();

			if ( ! empty( $child_fields ) ) {
				$this->collect_data_sources_from_fields( $child_fields, $sources );
			}

			$items = is_array( $field['items'] ?? null ) ? $field['items'] : array();

			foreach ( $items as $item ) {
				if ( ! is_array( $item ) || ! is_array( $item['fields'] ?? null ) ) {
					continue;
				}

				$this->collect_data_sources_from_fields( $item['fields'], $sources );
			}
		}
	}
}
