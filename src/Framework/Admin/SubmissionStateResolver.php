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
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\Framework\Support\ValidationTargetResolver;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

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
		FieldTypeRegistry $field_types,
		string $page_slug
	) {
		$this->definition  = $definition;
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

		$section = PageSchema::section( $this->definition, $section_id );

		if ( null === $section ) {
			return $values;
		}

		$submitted = is_array( $flash['submitted'] ?? null ) ? $flash['submitted'] : array();

		return ValidationFlash::merge_submitted_values(
			$values,
			$submitted,
			PageSchema::section_fields( $section ),
			$this->field_types
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
		return ValidationTargetResolver::first_validation_target( $this->definition, $errors );
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
		if ( null === $this->field_section_map_cache ) {
			$this->field_section_map_cache = ValidationTargetResolver::field_section_map( $this->definition );
		}

		return $this->field_section_map_cache;
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
