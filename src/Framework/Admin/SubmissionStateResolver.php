<?php
/**
 * Submission state resolver for flash messages, validation errors, and tab routing.
 *
 * Extracted from OptionsPage. Handles merging flashed submission values back
 * into rendering context, resolving validation-error targets for redirect,
 * and mapping field IDs to their owning tab/subsection.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Admin;

use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SubmissionStateResolver {

	/**
	 * Schema definition.
	 *
	 * @var array<string, mixed>
	 */
	private array $definition;

	private OptionStore $store;
	private FieldTypeRegistry $field_types;
	private string $page_slug;

	/**
	 * Lazily-built field-id → {tab, subsection} map.
	 *
	 * @var array<string, array{tab: string, subsection: string}>|null
	 */
	private ?array $field_section_map_cache = null;

	/**
	 * @param array<string, mixed> $definition Schema definition.
	 */
	public function __construct(
		array $definition,
		OptionStore $store,
		FieldTypeRegistry $field_types,
		string $page_slug
	) {
		$this->definition  = $definition;
		$this->store       = $store;
		$this->field_types = $field_types;
		$this->page_slug   = $page_slug;
	}

	// ─── Public: render-time value merging ───────────────────────────

	/**
	 * Merge flashed submission data into section values for rendering.
	 *
	 * @param array<string, mixed>      $values     Saved values.
	 * @param array<string, mixed>|null $flash      Flash data from ValidationFlash.
	 * @param string                    $section_id Section ID.
	 * @return array<string, mixed>
	 */
	public function section_render_values( array $values, ?array $flash, string $section_id ): array {
		if ( ! is_array( $flash ) ) {
			return $values;
		}

		if ( ! $this->is_global_flash( $flash ) && (string) ( $flash['tab'] ?? '' ) !== $section_id ) {
			return $values;
		}

		$submitted = is_array( $flash['submitted'] ?? null ) ? $flash['submitted'] : array();

		return $this->merge_section_submitted_values( $section_id, $values, $submitted );
	}

	/**
	 * Merge flashed submission data back into one section for non-JS validation retries.
	 *
	 * Some controls intentionally submit no key when emptied (for example multi-selects,
	 * checkbox lists, or an emptied group). A plain `wp_parse_args()` merge would
	 * resurrect the last saved value after a validation failure, so we replay those
	 * omissions as their empty state instead.
	 *
	 * @param array<string, mixed> $values    Saved values.
	 * @param array<string, mixed> $submitted Flashed submitted values.
	 * @return array<string, mixed>
	 */
	private function merge_section_submitted_values( string $section_id, array $values, array $submitted ): array {
		$section = PageSchema::section( $this->definition, $section_id );

		if ( null === $section ) {
			return $values;
		}

		foreach ( PageSchema::section_fields( $section ) as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['id'] ) ) {
				continue;
			}

			$field_id = (string) $field['id'];

			if ( array_key_exists( $field_id, $submitted ) ) {
				$values[ $field_id ] = $submitted[ $field_id ];
				continue;
			}

			$missing = $this->missing_submission_render_value( $field );

			if ( $missing['apply'] ) {
				$values[ $field_id ] = $missing['value'];
			}
		}

		return $values;
	}

	/**
	 * Controls like multi-selects and empty repeaters omit their key entirely.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return array{apply: bool, value: mixed}
	 */
	private function missing_submission_render_value( array $field ): array {
		if ( array_key_exists( 'missing_submission_value', $field ) ) {
			return array(
				'apply' => true,
				'value' => $field['missing_submission_value'],
			);
		}

		$type     = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$callback = $this->field_types->missing_submission_callback( $type );

		if ( is_callable( $callback ) ) {
			$missing = call_user_func( $callback, $field );

			if ( is_array( $missing ) ) {
				return array(
					'apply' => ! empty( $missing['apply'] ),
					'value' => $missing['value'] ?? null,
				);
			}
		}

		return array(
			'apply' => false,
			'value' => null,
		);
	}

	// ─── Public: flash errors and notices ─────────────────────────────

	/**
	 * Extract validation errors for a specific section from flash data.
	 *
	 * @param array<string, mixed>|null $flash      Flash data.
	 * @param string                    $section_id Section ID.
	 * @return array<string, array<int, string>>
	 */
	public function section_flash_errors( ?array $flash, string $section_id ): array {
		if ( ! is_array( $flash ) ) {
			return array();
		}

		if ( $this->is_global_flash( $flash ) ) {
			$errors = is_array( $flash['errors'] ?? null ) ? $flash['errors'] : array();

			return $this->filter_section_errors( $errors, $section_id );
		}

		if ( (string) ( $flash['tab'] ?? '' ) !== $section_id ) {
			return array();
		}

		return is_array( $flash['errors'] ?? null ) ? $flash['errors'] : array();
	}

	/**
	 * Build a section-level notice from flash data and redirect status.
	 *
	 * @param array<string, mixed>|null $flash      Flash data.
	 * @param string                    $section_id Section ID.
	 * @return array{class: string, message: string}|null
	 */
	public function section_flash_notice( ?array $flash, string $section_id ): ?array {
		if ( is_array( $flash ) && (string) ( $flash['tab'] ?? '' ) === $section_id ) {
			$message = isset( $flash['message'] ) && is_scalar( $flash['message'] ) ? (string) $flash['message'] : '';

			if ( '' === $message ) {
				return null;
			}

			return array(
				'class'   => 'validation_error' === $this->redirect_status() ? 'notice-error' : 'notice-warning',
				'message' => $message,
			);
		}

		if ( 'success' === $this->redirect_status() ) {
			return array(
				'class'   => 'notice-success',
				'message' => __( 'Settings saved.', 'lerm-admin-config' ),
			);
		}

		return null;
	}

	/**
	 * The sanitized redirect status from the URL, or '' if absent.
	 */
	public function redirect_status(): string {
		return isset( $_GET['lerm_admin_config_status'] )
			? sanitize_key( wp_unslash( $_GET['lerm_admin_config_status'] ) )
			: '';
	}

	// ─── Private: flash helpers ───────────────────────────────────────

	/**
	 * @param array<string, mixed> $flash
	 */
	private function is_global_flash( array $flash ): bool {
		return ! empty( $flash['global'] );
	}

	/**
	 * Filter errors to only those belonging to a given section.
	 *
	 * @param array<string, array<int, string>> $errors     All errors.
	 * @param string                            $section_id Target section.
	 * @return array<string, array<int, string>>
	 */
	private function filter_section_errors( array $errors, string $section_id ): array {
		$filtered = array();

		foreach ( $errors as $path => $messages ) {
			if ( $section_id !== $this->field_target( (string) $path )['tab'] ) {
				continue;
			}

			$filtered[ (string) $path ] = $messages;
		}

		return $filtered;
	}

	// ─── Public: validation target routing ────────────────────────────

	/**
	 * Resolve the first tab/subsection that contains a validation error.
	 *
	 * @param array<string, array<int, string>> $errors Validation errors.
	 * @return array{tab: string, subsection: string}
	 */
	public function first_validation_target( array $errors ): array {
		$fallback_tab = (string) array_key_first( PageSchema::sections( $this->definition ) );

		foreach ( array_keys( $errors ) as $path ) {
			$target = $this->field_target( (string) $path );

			if ( '' !== $target['tab'] ) {
				return $target;
			}
		}

		return array(
			'tab'        => $fallback_tab,
			'subsection' => '',
		);
	}

	// ─── Public: field → section mapping ──────────────────────────────

	/**
	 * Resolve the owning tab/subsection for a dotted field path.
	 *
	 * @return array{tab: string, subsection: string}
	 */
	public function field_target( string $field_path ): array {
		$field_id = sanitize_key( (string) strtok( $field_path, '.' ) );

		return $this->field_section_map()[ $field_id ] ?? array(
			'tab'        => '',
			'subsection' => '',
		);
	}

	/**
	 * Lazily-built field-id → {tab, subsection} map.
	 *
	 * @return array<string, array{tab: string, subsection: string}>
	 */
	private function field_section_map(): array {
		if ( null !== $this->field_section_map_cache ) {
			return $this->field_section_map_cache;
		}

		$map = array();

		foreach ( PageSchema::sections( $this->definition ) as $section_id => $section ) {
			$groups       = PageSchema::section_groups( $section );
			$use_subsects = $this->section_uses_subsections( $section, $groups );

			foreach ( PageSchema::section_fields( $section ) as $field ) {
				$field_id = (string) ( $field['id'] ?? '' );

				if ( '' === $field_id ) {
					continue;
				}

				$subsection = '';

				if ( $use_subsects ) {
					foreach ( $groups as $group ) {
						foreach ( (array) ( $group['fields'] ?? array() ) as $gf ) {
							if ( (string) ( $gf['id'] ?? '' ) === $field_id ) {
								$subsection = sanitize_key( (string) ( $group['id'] ?? '' ) );
								break 2;
							}
						}
					}
				}

				$map[ $field_id ] = array(
					'tab'        => (string) $section_id,
					'subsection' => $subsection,
				);
			}
		}

		$this->field_section_map_cache = $map;

		return $this->field_section_map_cache;
	}

	/**
	 * Determine whether a section should render secondary navigation.
	 *
	 * Inlined from OptionsPage::section_uses_subsections() to avoid a
	 * cross-class dependency on a private helper.
	 *
	 * @param array<string, mixed>             $section Section definition.
	 * @param array<int, array<string, mixed>> $groups  Section groups.
	 */
	private function section_uses_subsections( array $section, array $groups ): bool {
		if ( array_key_exists( 'use_subsections', $section ) ) {
			return ! empty( $section['use_subsections'] ) && count( $groups ) > 1;
		}

		return count( $groups ) > 1;
	}

	// ─── Public: tab routing ──────────────────────────────────────────

	/**
	 * Resolve the posted tab from save/reset requests.
	 */
	public function posted_tab(): string {
		$sections = PageSchema::sections( $this->definition );
		$tab      = isset( $_POST['lerm_settings_tab'] ) ? sanitize_key( wp_unslash( $_POST['lerm_settings_tab'] ) ) : (string) array_key_first( $sections );

		if ( ! isset( $sections[ $tab ] ) ) {
			return (string) array_key_first( $sections );
		}

		return $tab;
	}

	/**
	 * Resolve the posted subsection from AJAX reset requests.
	 */
	public function posted_subsection(): string {
		return isset( $_POST['lerm_settings_subsection'] ) ? sanitize_key( wp_unslash( $_POST['lerm_settings_subsection'] ) ) : '';
	}

	/**
	 * Resolve the current tab from the URL.
	 */
	public function current_tab(): string {
		$sections = PageSchema::sections( $this->definition );
		$tab      = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : (string) array_key_first( $sections );

		if ( ! isset( $sections[ $tab ] ) ) {
			return (string) array_key_first( $sections );
		}

		return $tab;
	}

	/**
	 * Flash resource key (the page slug).
	 */
	public function flash_resource_key(): string {
		return $this->page_slug;
	}
}
