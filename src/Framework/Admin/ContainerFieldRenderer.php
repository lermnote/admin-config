<?php
/**
 * Classic admin structured container field renderer.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Admin;

use Lerm\AdminConfig\Framework\Support\FieldPath;
use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContainerFieldRenderer {

	use FieldErrorMatcher;

	/**
	 * @var callable
	 */
	private $nested_render;

	/**
	 * @var array<string, mixed>
	 */
	private array $field_errors;
	private string $current_path;

	private FieldDependencyEvaluator $dep_evaluator;
	private string $option_name;

	/**
	 * @param callable             $nested_render Callback to render a nested sub-field.
	 * @param array<string, mixed> $field_errors
	 */
	public function __construct(
		callable $nested_render,
		array $field_errors = array(),
		string $current_path = '',
		FieldDependencyEvaluator $dep_evaluator = null,
		string $option_name = ''
	) {
		$this->nested_render = $nested_render;
		$this->field_errors  = $field_errors;
		$this->current_path  = $current_path;
		$this->dep_evaluator = $dep_evaluator ?? new FieldDependencyEvaluator( array() );
		$this->option_name   = $option_name;
	}

	/**
	 * Render fieldsets as a compact grid of nested controls.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_fieldset( array $field, $value, string $field_name ): void {
		$field_id = (string) $field['id'];
		$values   = is_array( $value ) ? $value : array();
		$fields   = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$path     = $this->resolve_render_path( $field_id );
		$invalid  = $this->field_has_errors( $this->field_errors, $path, true );
		$classes  = array_filter(
			array_map(
				'trim',
				explode( ' ', 'lerm-fieldset ' . (string) ( $field['wrapper_class'] ?? '' ) )
			)
		);

		if ( $invalid ) {
			$classes[] = 'is-invalid';
		}

		echo '<div class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '" data-target="' . esc_attr( $field_id ) . '" data-field-path="' . esc_attr( $path ) . '">';
		$this->render_child_fields( $fields, $values, $field_name, $field_id, $path );
		echo '</div>';
	}

	/**
	 * Render accordion field panels.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_accordion( array $field, $value, string $field_name ): void {
		$field_id       = (string) $field['id'];
		$field_path     = $this->resolve_render_path( $field_id );
		$values         = is_array( $value ) ? $value : array();
		$items          = $this->panel_items( $field );
		$allow_multiple = ! empty( $field['allow_multiple'] );
		$open_first     = ! array_key_exists( 'open_first', $field ) || ! empty( $field['open_first'] );
		$invalid        = $this->field_has_errors( $this->field_errors, $field_path, true );

		echo '<div class="lerm-fieldset lerm-accordion-field' . ( $invalid ? ' is-invalid' : '' ) . '" data-target="' . esc_attr( $field_id ) . '" data-field-path="' . esc_attr( $field_path ) . '" data-lerm-accordion data-allow-multiple="' . esc_attr( $allow_multiple ? '1' : '0' ) . '">';

		foreach ( $items as $index => $item ) {
			$item_id      = (string) $item['id'];
			$item_path    = FieldPath::join( $field_path, $item_id );
			$item_title   = (string) $item['title'];
			$item_desc    = (string) ( $item['description'] ?? '' );
			$item_fields  = is_array( $item['fields'] ?? null ) ? $item['fields'] : array();
			$item_values  = is_array( $values[ $item_id ] ?? null ) ? $values[ $item_id ] : array();
			$item_invalid = $this->field_has_errors( $this->field_errors, $item_path, true );
			$is_open      = $item_invalid || ! empty( $item['open'] ) || ( $open_first && 0 === $index );
			$panel_id     = $field_id . '__' . $item_id;
			$button_id    = $panel_id . '__button';

			echo '<section class="lerm-accordion__item' . ( $item_invalid ? ' is-invalid' : '' ) . ( $is_open ? ' is-open' : '' ) . '" data-item-id="' . esc_attr( $item_id ) . '">';
			echo '<button type="button" id="' . esc_attr( $button_id ) . '" class="lerm-accordion__trigger" data-lerm-accordion-trigger aria-expanded="' . esc_attr( $is_open ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $panel_id ) . '">';
			echo '<span>' . esc_html( $item_title ) . '</span>';
			echo '<span class="lerm-accordion__chevron" aria-hidden="true"></span>';
			echo '</button>';
			echo '<div id="' . esc_attr( $panel_id ) . '" class="lerm-accordion__panel" data-lerm-accordion-panel aria-labelledby="' . esc_attr( $button_id ) . '"' . ( $is_open ? '' : ' hidden' ) . '>';

			if ( '' !== $item_desc ) {
				echo '<p class="description lerm-accordion__description">' . esc_html( $item_desc ) . '</p>';
			}

			$this->render_child_fields(
				$item_fields,
				$item_values,
				$field_name . '[' . $item_id . ']',
				$field_id . '__' . $item_id,
				$item_path
			);
			echo '</div></section>';
		}

		echo '</div>';
	}

	/**
	 * Render tabbed field panels.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_tabbed( array $field, $value, string $field_name ): void {
		$field_id   = (string) $field['id'];
		$field_path = $this->resolve_render_path( $field_id );
		$values     = is_array( $value ) ? $value : array();
		$items      = $this->panel_items( $field );
		$active_tab = sanitize_key( (string) ( $field['default_tab'] ?? '' ) );

		if ( '' === $active_tab && ! empty( $items[0]['id'] ) ) {
			$active_tab = (string) $items[0]['id'];
		}

		foreach ( $items as $item ) {
			$item_id   = (string) ( $item['id'] ?? '' );
			$item_path = FieldPath::join( $field_path, $item_id );

			if ( '' !== $item_id && $this->field_has_errors( $this->field_errors, $item_path, true ) ) {
				$active_tab = $item_id;
				break;
			}
		}

		echo '<div class="lerm-fieldset lerm-tabbed-field' . ( $this->field_has_errors( $this->field_errors, $field_path, true ) ? ' is-invalid' : '' ) . '" data-target="' . esc_attr( $field_id ) . '" data-field-path="' . esc_attr( $field_path ) . '" data-lerm-tabbed data-default-tab="' . esc_attr( $active_tab ) . '">';
		echo '<div class="lerm-tabbed__nav" role="tablist">';

		foreach ( $items as $index => $item ) {
			$item_id      = (string) $item['id'];
			$item_path    = FieldPath::join( $field_path, $item_id );
			$item_invalid = $this->field_has_errors( $this->field_errors, $item_path, true );
			$is_active    = $item_id === $active_tab || ( '' === $active_tab && 0 === $index );
			$panel_id     = $field_id . '__' . $item_id;
			$trigger_id   = $panel_id . '__tab';

			echo '<button type="button" id="' . esc_attr( $trigger_id ) . '" class="lerm-tabbed__trigger' . ( $is_active ? ' is-active' : '' ) . ( $item_invalid ? ' is-invalid' : '' ) . '" data-lerm-tabbed-trigger data-lerm-tabbed-target="' . esc_attr( $item_id ) . '" role="tab" aria-selected="' . esc_attr( $is_active ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $panel_id ) . '" tabindex="' . esc_attr( $is_active ? '0' : '-1' ) . '">';
			echo esc_html( (string) $item['title'] );
			echo '</button>';
		}

		echo '</div><div class="lerm-tabbed__panels">';

		foreach ( $items as $index => $item ) {
			$item_id      = (string) $item['id'];
			$item_path    = FieldPath::join( $field_path, $item_id );
			$item_desc    = (string) ( $item['description'] ?? '' );
			$item_fields  = is_array( $item['fields'] ?? null ) ? $item['fields'] : array();
			$item_values  = is_array( $values[ $item_id ] ?? null ) ? $values[ $item_id ] : array();
			$item_invalid = $this->field_has_errors( $this->field_errors, $item_path, true );
			$is_active    = $item_id === $active_tab || ( '' === $active_tab && 0 === $index );
			$panel_id     = $field_id . '__' . $item_id;
			$trigger_id   = $panel_id . '__tab';

			echo '<section id="' . esc_attr( $panel_id ) . '" class="lerm-tabbed__panel' . ( $item_invalid ? ' is-invalid' : '' ) . '" data-item-id="' . esc_attr( $item_id ) . '" data-lerm-tabbed-panel="' . esc_attr( $item_id ) . '" role="tabpanel" aria-labelledby="' . esc_attr( $trigger_id ) . '"' . ( $is_active ? '' : ' hidden' ) . '>';

			if ( '' !== $item_desc ) {
				echo '<p class="description lerm-tabbed__description">' . esc_html( $item_desc ) . '</p>';
			}

			$this->render_child_fields(
				$item_fields,
				$item_values,
				$field_name . '[' . $item_id . ']',
				$field_id . '__' . $item_id,
				$item_path
			);
			echo '</section>';
		}

		echo '</div></div>';
	}

	/**
	 * Render repeatable groups.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_group( array $field, $value, string $field_name ): void {
		$field_id    = (string) $field['id'];
		$field_path  = $this->resolve_render_path( $field_id );
		$items       = is_array( $value ) ? array_values( $value ) : array();
		$button_text = (string) ( $field['button_text'] ?? __( 'Add item', 'lerm-admin-config' ) );

		echo '<div class="lerm-group' . ( $this->field_has_errors( $this->field_errors, $field_path, true ) ? ' is-invalid' : '' ) . '" data-target="' . esc_attr( $field_id ) . '" data-field-path="' . esc_attr( $field_path ) . '">';
		echo '<div class="lerm-group__toolbar">';
		echo '<button type="button" class="button button-secondary" data-lerm-group-add>' . esc_html( $button_text ) . '</button>';
		echo '</div>';
		echo '<div class="lerm-group__empty" ' . ( ! empty( $items ) ? 'hidden' : '' ) . '>' . esc_html__( 'No items added yet.', 'lerm-admin-config' ) . '</div>';
		echo '<div class="lerm-group-list" data-lerm-group-list>';

		foreach ( $items as $index => $item ) {
			echo $this->group_item_markup( $field, $field_name, is_array( $item ) ? $item : array(), (string) $index, $field_path, FieldPath::join( $field_path, '__INDEX__' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</div>';
		echo '<script type="text/html" class="lerm-group-template">' . $this->group_item_markup( $field, $field_name, array(), '__INDEX__', $field_path, FieldPath::join( $field_path, '__INDEX__' ) ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Build one repeatable group item.
	 *
	 * @param array<string, mixed> $field Group definition.
	 * @param array<string, mixed> $item  Current item values.
	 */
	private function group_item_markup( array $field, string $field_name, array $item, string $index, string $field_path = '', string $path_template = '' ): string {
		$fields          = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$item_path       = FieldPath::join( $field_path, $index );
		$item_has_errors = $this->field_has_errors( $this->field_errors, $item_path, true );

		ob_start();
		?>
		<div class="lerm-group-item<?php echo esc_attr( $item_has_errors ? ' is-invalid' : '' ); ?>" data-lerm-group-item data-index="<?php echo esc_attr( $index ); ?>" data-field-path="<?php echo esc_attr( $item_path ); ?>" data-field-path-template="<?php echo esc_attr( $path_template ); ?>">
			<div class="lerm-group-item__header">
				<span class="lerm-sorter-handle" aria-hidden="true">&#8645;</span>
				<strong class="lerm-group-item__title">
				<?php
				// translators: %s is the item number in the group, starting from 1. For example: "Item 1", "Item 2", etc. Do not translate the number itself.
				echo esc_html( sprintf( __( 'Item %s', 'lerm-admin-config' ), is_numeric( $index ) ? (string) ( (int) $index + 1 ) : '#' ) );
				?>
				</strong>
				<button type="button" class="button button-secondary button-link-delete" data-lerm-group-remove><?php echo esc_html__( 'Remove', 'lerm-admin-config' ); ?></button>
			</div>
			<div class="lerm-group-item__body">
				<?php
				$this->render_child_fields(
					$fields,
					$item,
					$field_name . '[' . $index . ']',
					(string) $field['id'] . '__' . $index,
					$item_path,
					FieldPath::join( $path_template, '' ),
					'lerm-group-item__field'
				);
				?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render a nested sub-field for fieldsets and repeaters.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	private function render_nested_field( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
		( $this->nested_render )( $field, $value, $field_name, $input_id, $name_template, $id_template );
	}

	/**
	 * Render a flat set of child controls inside a structured container.
	 *
	 * @param array<int, array<string, mixed>> $fields Child field definitions.
	 * @param array<string, mixed>             $values Child field values.
	 */
	private function render_child_fields( array $fields, array $values, string $field_name, string $field_id, string $base_path = '', string $base_path_template = '', string $item_class = 'lerm-fieldset__item' ): void {
		$base_path          = '' !== $base_path ? $base_path : $this->current_path;
		$base_path_template = '' !== $base_path_template ? $base_path_template : $base_path;

		foreach ( $fields as $child ) {
			if ( ! is_array( $child ) || ! isset( $child['id'] ) ) {
				continue;
			}

			$child_id    = (string) $child['id'];
			$child_name  = $field_name . '[' . $child_id . ']';
			$child_value = $values[ $child_id ] ?? ( $child['default'] ?? '' );
			$child_path  = FieldPath::join( $base_path, $child_id );
			$error       = $this->field_error_message( $this->field_errors, $child_path );
			$has_errors  = $this->field_has_errors( $this->field_errors, $child_path, true );
			$classes     = trim( $item_class . ( $has_errors ? ' is-invalid' : '' ) );

			echo '<div class="' . esc_attr( $classes ) . '" data-subfield-id="' . esc_attr( $child_id ) . '" data-field-type="' . esc_attr( sanitize_key( (string) ( $child['type'] ?? 'text' ) ) ) . '" data-field-path="' . esc_attr( $child_path ) . '"';

			if ( '' !== $base_path_template ) {
				echo ' data-field-path-template="' . esc_attr( FieldPath::join( $base_path_template, $child_id ) ) . '"';
			}

			echo '>';
			echo '<label class="lerm-fieldset__label" for="' . esc_attr( $field_id . '__' . $child_id ) . '">' . esc_html( (string) ( $child['label'] ?? $child_id ) ) . '</label>';
			$this->render_nested_field( $child, $child_value, $child_name, $field_id . '__' . $child_id );

			if ( ! empty( $child['description'] ) ) {
				echo '<p class="description">' . esc_html( (string) $child['description'] ) . '</p>';
			}

			if ( '' !== $error ) {
				printf( '<p class="lerm-field-error" data-lerm-field-error-message>%s</p>', esc_html( $error ) );
			}

			echo '</div>';
		}
	}

	private function resolve_render_path( string $field_id ): string {
		if ( '' === $field_id ) {
			return $this->current_path;
		}

		if ( '' === $this->current_path || $field_id === $this->current_path ) {
			return $field_id;
		}

		return FieldPath::join( $this->current_path, $field_id );
	}


	/**
	 * @param array<string, mixed> $field
	 * @return array<int, array<string, mixed>>
	 */
	private function panel_items( array $field ): array {
		$items      = is_array( $field['items'] ?? null ) ? $field['items'] : array();
		$normalized = array();

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$item_id    = isset( $item['id'] ) && is_scalar( $item['id'] ) ? sanitize_key( (string) $item['id'] ) : '';
			$item_title = isset( $item['title'] ) && is_scalar( $item['title'] ) ? (string) $item['title'] : '';
			$item_id    = '' !== $item_id ? $item_id : 'item_' . (string) ( (int) $index + 1 );

			$normalized[] = array(
				'id'          => $item_id,
				'title'       => '' !== $item_title ? $item_title : ucfirst( str_replace( '_', ' ', $item_id ) ),
				'description' => isset( $item['description'] ) && is_scalar( $item['description'] ) ? (string) $item['description'] : '',
				'fields'      => is_array( $item['fields'] ?? null ) ? $item['fields'] : array(),
				'open'        => ! empty( $item['open'] ),
			);
		}

		return $normalized;
	}

	// ── Flat field rendering ──────────────────────────────────────────

	/**
	 * Render all fields for a section.
	 *
	 * @param array<int, array<string, mixed>> $fields          Field definitions.
	 * @param array<string, mixed>             $values          Saved values.
	 * @param callable                         $render_control  Callback to render the field control.
	 * @param string                           $section_id      Current section ID.
	 * @param bool                             $show_group_headings Whether group headings should be rendered.
	 * @param string                           $layout          Layout mode ('table' or 'stack').
	 * @param array<string, mixed>             $field_errors    Field error map.
	 */
	public function render_fields(
		array $fields,
		array $values,
		callable $render_control,
		string $section_id = '',
		bool $show_group_headings = true,
		string $layout = 'table',
		array $field_errors = array()
	): void {
		$current_group_heading = '';

		foreach ( $fields as $field ) {
			$group_heading = (string) ( $field['group_heading'] ?? '' );

			if ( $show_group_headings && $group_heading && $group_heading !== $current_group_heading ) {
				$current_group_heading = $group_heading;

				if ( 'stack' === $layout ) {
					printf(
						'<div class="lerm-settings-group lerm-settings-group--stack"><h3>%s</h3></div>',
						esc_html( $group_heading )
					);
				} else {
					printf(
						'<tr class="lerm-settings-group"><td colspan="2"><h3>%s</h3></td></tr>',
						esc_html( $group_heading )
					);
				}
			}

			$this->render_field( $field, $values, $render_control, $layout, $field_errors );
		}
	}

	/**
	 * Render a single field row.
	 *
	 * @param array<string, mixed> $field          Field definition.
	 * @param array<string, mixed> $values         Saved values.
	 * @param callable             $render_control Callback to render the field control.
	 * @param string               $layout         Layout mode.
	 * @param array<string, mixed> $field_errors   Field error map.
	 */
	public function render_field(
		array $field,
		array $values,
		callable $render_control,
		string $layout = 'table',
		array $field_errors = array()
	): void {
		if ( 'stack' === $layout ) {
			$this->render_stack_field_row( $field, $values, $render_control, $field_errors );
			return;
		}

		$this->render_table_field_row( $field, $values, $render_control, $field_errors );
	}

	/**
	 * @param array<string, mixed> $field
	 * @param array<string, mixed> $values
	 * @param callable             $render_control
	 * @param array<string, mixed> $field_errors
	 */
	private function render_stack_field_row( array $field, array $values, callable $render_control, array $field_errors ): void {
		$context   = $this->field_row_context( $field, $values, $field_errors );
		$row_attrs = $context['row_attrs'];

		if ( '' === $context['label'] ) {
			$row_attrs[0] = 'class="lerm-settings-row lerm-settings-row--nolabel' . ( $context['has_errors'] ? ' is-invalid' : '' ) . '"';
		}

		echo '<div ' . implode( ' ', $row_attrs ) . '>';

		if ( '' !== $context['label'] ) {
			printf(
				'<div class="lerm-settings-row__head"><label for="%1$s">%2$s</label></div>',
				esc_attr( $context['field_id'] ),
				esc_html( $context['label'] )
			);
		}

		echo '<div class="lerm-settings-row__body">';

		$render_control( $field, $context, $field_errors );
		$this->render_field_notes( $context['description'], $context['field_error'] );

		echo '</div></div>';
	}

	/**
	 * @param array<string, mixed> $field
	 * @param array<string, mixed> $values
	 * @param callable             $render_control
	 * @param array<string, mixed> $field_errors
	 */
	private function render_table_field_row( array $field, array $values, callable $render_control, array $field_errors ): void {
		$context = $this->field_row_context( $field, $values, $field_errors );

		echo '<tr ' . implode( ' ', $context['row_attrs'] ) . '>';

		if ( '' !== $context['label'] ) {
			printf(
				'<th scope="row"><label for="%1$s">%2$s</label></th>',
				esc_attr( $context['field_id'] ),
				esc_html( $context['label'] )
			);
		} else {
			echo '<th scope="row"></th>';
		}

		echo '<td>';

		$render_control( $field, $context, $field_errors );
		$this->render_field_notes( $context['description'], $context['field_error'] );

		echo '</td></tr>';
	}

	/**
	 * Build the rendering context for a single field row.
	 *
	 * @param array<string, mixed> $field        Field definition.
	 * @param array<string, mixed> $values       Saved values.
	 * @param array<string, mixed> $field_errors Field error map.
	 * @return array{field_id: string, field_type: string, field_name: string, field_value: mixed, description: string, field_error: string, has_errors: bool, label: string, row_attrs: array<int, string>}
	 */
	private function field_row_context( array $field, array $values, array $field_errors ): array {
		$field_id    = (string) $field['id'];
		$field_type  = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$field_name  = $this->option_name . '[' . $field_id . ']';
		$field_value = $values[ $field_id ] ?? ( $field['default'] ?? '' );
		$description = (string) ( $field['description'] ?? '' );
		$field_error = $this->field_error_message( $field_errors, $field_id );
		$has_errors  = $this->field_has_errors( $field_errors, $field_id, true );
		$dependency  = $this->dep_evaluator->field_dependency( $field );
		$label       = isset( $field['label'] ) ? (string) $field['label'] : '';
		$row_attrs   = array(
			'class="lerm-settings-row' . ( $has_errors ? ' is-invalid' : '' ) . '"',
			'data-field-id="' . esc_attr( $field_id ) . '"',
			'data-field-path="' . esc_attr( $field_id ) . '"',
			'data-field-type="' . esc_attr( $field_type ) . '"',
		);

		if ( ! empty( $dependency ) ) {
			$row_attrs[] = 'data-dependency-field="' . esc_attr( (string) $dependency['field'] ) . '"';
			$row_attrs[] = 'data-dependency-operator="' . esc_attr( (string) $dependency['operator'] ) . '"';
			$row_attrs[] = 'data-dependency-value="' . esc_attr( $this->dep_evaluator->attribute_value( $dependency['value'] ) ) . '"';

			if ( ! $this->dep_evaluator->is_satisfied( $field, $values ) ) {
				$row_attrs[] = 'hidden';
			}
		}

		return array(
			'field_id'    => $field_id,
			'field_type'  => $field_type,
			'field_name'  => $field_name,
			'field_value' => $field_value,
			'description' => $description,
			'field_error' => $field_error,
			'has_errors'  => $has_errors,
			'label'       => $label,
			'row_attrs'   => $row_attrs,
		);
	}
}
