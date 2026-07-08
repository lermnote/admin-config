<?php // phpcs:disable WordPress.Files.FileName
/**
 * Generic native options page container.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Admin;

use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\Registry\FieldModuleRegistry;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionsPage {

	use FieldErrorMatcher;

	/**
	 * Page definition.
	 *
	 * @var array<string, mixed>
	 */
	private array $definition;

	private OptionStore $store;

	private FieldTypeRegistry $field_types;

	private AssetResolver $asset_resolver;
	private ?FieldModuleRegistry $field_modules = null;

	/**
	 * The JS global variable name for this page instance.
	 * Namespaced by page slug to avoid collisions on multi-instance pages.
	 */
	private string $js_global;
	private bool $network_admin = false;

	/**
	 * @var array<string, mixed>
	 */
	private array $render_field_errors = array();

	/**
	 * @var array<int, string>
	 */
	private array $render_path_stack = array();

	private FieldDependencyEvaluator $dep_evaluator;
	private SchemaDebugPanel $debug_panel;
	private SubmissionStateResolver $submission;
	private OptionsPageLifecycle $lifecycle;

	/**
	 * @param array<string, mixed> $definition Page definition.
	 */
	public function __construct( array $definition, OptionStore $store, FieldTypeRegistry $field_types, AssetResolver $asset_resolver, bool $register_hooks = true, ?FieldModuleRegistry $field_modules = null ) {
		$this->definition     = $definition;
		$this->store          = $store;
		$this->field_types    = $field_types;
		$this->asset_resolver = $asset_resolver;
		$this->field_modules  = $field_modules;
		$this->dep_evaluator  = new FieldDependencyEvaluator( $this->definition );
		$menu                 = is_array( $this->definition['menu'] ?? null ) ? $this->definition['menu'] : array();
		$this->network_admin  = ! empty( $menu['network_admin'] );
		$this->debug_panel    = new SchemaDebugPanel(
			$this->definition,
			$this->field_modules,
			$this->field_types,
			$this->schema_id(),
			$this->page_slug(),
			$this->option_name(),
			$this->capability(),
			$this->network_admin
		);
		$this->submission     = new SubmissionStateResolver(
			$this->definition,
			$this->store,
			$this->field_types,
			$this->page_slug()
		);
		// JS global can be overridden per-instance via the definition.
		$this->js_global = isset( $this->definition['js_global'] ) && is_string( $this->definition['js_global'] )
			? $this->definition['js_global']
			: 'lermAdminConfig';

		$this->lifecycle = new OptionsPageLifecycle(
			$this->definition,
			$this->asset_resolver,
			$this->capability(),
			$this->page_slug(),
			$this->network_admin,
			$this->js_global,
			array( $this, 'render_page' )
		);

		if ( $register_hooks ) {
			add_action( $this->lifecycle->menu_action(), array( $this->lifecycle, 'register_menu' ) );
			add_action( 'admin_post_' . $this->save_action(), array( $this, 'handle_save' ) );
			add_action( 'admin_enqueue_scripts', array( $this->lifecycle, 'enqueue_assets' ) );
		}
	}

	/**
	 * Handle a non-JS save request.
	 */
	public function handle_save(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these settings.', 'lerm-admin-config' ) );
		}

		$tab        = $this->submission->posted_tab();
		$subsection = $this->submission->posted_subsection();

		check_admin_referer( $this->nonce_action( $tab ) );

		$submitted = isset( $_POST[ $this->option_name() ] ) && is_array( $_POST[ $this->option_name() ] )
			? wp_unslash( $_POST[ $this->option_name() ] )
			: array();

		$success             = $this->store->import_all( $submitted );
		$status              = 'success';
		$redirect_tab        = $tab;
		$redirect_subsection = $subsection;

		if ( $this->store->has_validation_errors() ) {
			$error_target = $this->submission->first_validation_target( $this->store->validation_errors() );

			$status              = 'validation_error';
			$redirect_tab        = $error_target['tab'];
			$redirect_subsection = $error_target['subsection'];
			ValidationFlash::store(
				'options_page',
				$this->schema_id(),
				$this->submission->flash_resource_key(),
				array(
					'tab'        => $redirect_tab,
					'subsection' => $redirect_subsection,
					'global'     => true,
					'message'    => __( 'Please review the highlighted fields before saving again.', 'lerm-admin-config' ),
					'errors'     => $this->store->validation_errors(),
					'submitted'  => $submitted,
				)
			);
		} elseif ( ! $success ) {
			$status = 'error';
			ValidationFlash::store(
				'options_page',
				$this->schema_id(),
				$this->submission->flash_resource_key(),
				array(
					'tab'        => $redirect_tab,
					'subsection' => $redirect_subsection,
					'global'     => true,
					'message'    => __( 'Unable to save these settings right now.', 'lerm-admin-config' ),
					'errors'     => array(),
					'submitted'  => $submitted,
				)
			);
		} else {
			ValidationFlash::clear( 'options_page', $this->schema_id(), $this->submission->flash_resource_key() );
		}

		$redirect_url = add_query_arg(
			array(
				'page'                     => $this->page_slug(),
				'tab'                      => $redirect_tab,
				'lerm_admin_config_status' => $status,
			),
			$this->admin_parent_url()
		);

		if ( '' !== $redirect_subsection ) {
			$redirect_url = add_query_arg( 'subsection', $redirect_subsection, $redirect_url );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Delegates to OptionsPageLifecycle for external callers.
	 *
	 * @deprecated 0.5.0 Use the lifecycle instance instead.
	 */
	public function enqueue_support_assets( string $handle_suffix = '' ): void {
		$this->lifecycle->enqueue_support_assets( $handle_suffix );
	}

	/**
	 * Render the page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			return;
		}

		$view        = is_array( $this->definition['view'] ?? null ) ? $this->definition['view'] : array();
		$sections    = PageSchema::sections( $this->definition );
		$current_tab = $this->submission->current_tab();
		$values      = $this->store->all();
		$flash       = ValidationFlash::consume( 'options_page', $this->schema_id(), $this->submission->flash_resource_key() );
		?>
		<div class="wrap lerm-settings-wrap">
			<div class="lerm-settings-shell">
				<aside class="lerm-settings-sidebar">
					<div class="lerm-settings-sidebar__brand">
						<p class="lerm-settings-eyebrow"><?php echo esc_html( (string) ( $view['eyebrow'] ?? __( 'Native admin', 'lerm-admin-config' ) ) ); ?></p>
						<h1><?php echo esc_html( (string) ( $view['title'] ?? __( 'Admin Config', 'lerm-admin-config' ) ) ); ?></h1>
						<p><?php echo esc_html( (string) ( $view['description'] ?? __( 'A native, extensible settings page built on schema, storage, and reusable field renderers.', 'lerm-admin-config' ) ) ); ?></p>
					</div>

					<nav class="lerm-settings-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'lerm-admin-config' ); ?>">
						<?php foreach ( $sections as $section_id => $section ) : ?>
							<?php $section_field_count = count( PageSchema::section_fields( $section ) ); ?>
									<a class="lerm-settings-nav__item <?php echo esc_attr( $section_id === $current_tab ? 'is-active' : '' ); ?>"
								href="
								<?php
								echo esc_url(
									add_query_arg(
										array(
											'page' => $this->page_slug(),
											'tab'  => $section_id,
										),
										$this->admin_parent_url()
									)
								);
								?>
										"
								data-tab-target="<?php echo esc_attr( $section_id ); ?>">
								<span class="lerm-settings-nav__title"><?php echo esc_html( (string) $section['title'] ); ?></span>
								<span class="lerm-settings-nav__meta">
									<?php
									echo esc_html(
										// translators: %s is the number of fields in the section, e.g. "5 fields". Do not translate the number itself.
										sprintf( _n( '%s field', '%s fields', $section_field_count, 'lerm-admin-config' ), number_format_i18n( $section_field_count ) )
									);
									?>
								</span>
							</a>
						<?php endforeach; ?>
					</nav>
				</aside>

				<section class="lerm-settings-main">
					<div class="lerm-settings-panel">
						<?php
						// Intro header: title/description swapped by JS on tab switch.
						// PHP seeds the initially-active tab; JS takes over from there.
						$active_section = $sections[ $current_tab ] ?? reset( $sections );
						?>
						<div class="lerm-settings-panel__intro" data-lerm-tab-intro>
							<div>
								<p class="lerm-settings-eyebrow"><?php esc_html_e( 'Current section', 'lerm-admin-config' ); ?></p>
								<h2 data-lerm-tab-intro-title><?php echo esc_html( (string) ( $active_section['title'] ?? '' ) ); ?></h2>
								<p data-lerm-tab-intro-desc><?php echo esc_html( (string) ( $active_section['description'] ?? '' ) ); ?></p>
							</div>
						</div>

						<?php
						$active_section_definition = is_array( $active_section ) ? $active_section : array();
						$active_section_groups     = PageSchema::section_groups( $active_section_definition );
						$current_subsection        = $this->section_uses_subsections( $active_section_definition, $active_section_groups )
							? $this->current_subsection_for_section( (string) $current_tab, $active_section_groups )
							: '';
						?>
						<form method="post" action="<?php echo esc_url( $this->admin_post_url() ); ?>"
								class="lerm-settings-form"
								data-option-name="<?php echo esc_attr( $this->option_name() ); ?>"
								data-schema-id="<?php echo esc_attr( $this->schema_id() ); ?>"
								data-js-global="<?php echo esc_attr( $this->js_global ); ?>"
								novalidate>
							<input type="hidden" name="action" value="<?php echo esc_attr( $this->save_action() ); ?>">
							<input type="hidden" name="lerm_settings_tab" value="<?php echo esc_attr( $current_tab ); ?>" data-lerm-current-tab="1">
							<input type="hidden" name="lerm_settings_subsection" value="<?php echo esc_attr( $current_subsection ); ?>" data-lerm-current-subsection="1">
							<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( $this->nonce_action( $current_tab ) ) ); ?>" data-lerm-current-nonce="1">

							<?php foreach ( $sections as $section_id => $section ) : ?>
								<?php
								$section_fields     = PageSchema::section_fields( $section );
								$section_groups     = PageSchema::section_groups( $section );
								$use_subsections    = $this->section_uses_subsections( $section, $section_groups );
								$current_subsection = $use_subsections ? $this->current_subsection_for_section( (string) $section_id, $section_groups ) : '';
								$section_errors     = $this->submission->section_flash_errors( $flash, (string) $section_id );
								$section_values     = $this->submission->section_render_values( $values, $flash, (string) $section_id );
								$section_notice     = $this->submission->section_flash_notice( $flash, (string) $section_id );
								?>
							<div data-tab-panel="<?php echo esc_attr( $section_id ); ?>"
								data-tab-title="<?php echo esc_attr( (string) ( $section['title'] ?? '' ) ); ?>"
								data-tab-description="<?php echo esc_attr( (string) ( $section['description'] ?? '' ) ); ?>"
								data-tab-nonce="<?php echo esc_attr( wp_create_nonce( $this->nonce_action( (string) $section_id ) ) ); ?>"
								data-current-subsection="<?php echo esc_attr( $current_subsection ); ?>"
								<?php echo esc_attr( $section_id !== $current_tab ? 'hidden' : '' ); ?>>

								<?php if ( null !== $section_notice ) : ?>
									<div class="lerm-settings-form-notice notice <?php echo esc_attr( $section_notice['class'] ); ?> inline">
										<p><?php echo esc_html( $section_notice['message'] ); ?></p>
									</div>
								<?php endif; ?>

								<div class="lerm-settings-sticky-wrap" data-lerm-sticky-wrap>
									<div class="lerm-settings-actions lerm-settings-actions--sticky lerm-settings-sticky-bar" data-lerm-sticky-bar>
										<button type="submit" class="button button-primary button-large" data-lerm-save><?php esc_html_e( 'Save changes', 'lerm-admin-config' ); ?></button>
										<button type="button" class="button button-secondary" data-lerm-reset="section"><?php esc_html_e( 'Reset current page', 'lerm-admin-config' ); ?></button>
										<button type="button" class="button button-secondary button-link-delete" data-lerm-reset="all"><?php esc_html_e( 'Reset all tabs', 'lerm-admin-config' ); ?></button>
										<span class="spinner lerm-settings-spinner"></span>
										<span class="lerm-settings-actions__hint"><?php esc_html_e( 'Changes are saved instantly without reloading the page. Use Ctrl/Cmd + S to save faster.', 'lerm-admin-config' ); ?></span>
										<span class="lerm-settings-actions__spacer" aria-hidden="true"></span>
										<span class="lerm-status-pill lerm-settings-actions__status" data-lerm-status="idle"><?php esc_html_e( 'Synced', 'lerm-admin-config' ); ?></span>
									</div>
								</div>

								<?php if ( $use_subsections ) : ?>
									<div class="lerm-settings-sticky-wrap lerm-settings-sticky-wrap--subnav" data-lerm-sticky-wrap>
										<?php /* translators: %s: section title. */ ?>
										<nav class="lerm-settings-subnav lerm-settings-subnav--sticky lerm-settings-sticky-bar" data-lerm-sticky-bar aria-label="<?php echo esc_attr( sprintf( __( '%s groups', 'lerm-admin-config' ), (string) ( $section['title'] ?? __( 'Section', 'lerm-admin-config' ) ) ) ); ?>">
											<?php foreach ( $section_groups as $group_index => $group ) : ?>
												<button type="button"
													class="lerm-settings-subnav__item <?php echo esc_attr( (string) $group['id'] === $current_subsection ? 'is-active' : '' ); ?>"
													data-subsection-target="<?php echo esc_attr( (string) $group['id'] ); ?>"
													aria-pressed="<?php echo esc_attr( (string) $group['id'] === $current_subsection ? 'true' : 'false' ); ?>">
													<?php echo esc_html( (string) $group['label'] ); ?>
												</button>
											<?php endforeach; ?>
										</nav>
									</div>

									<div class="lerm-settings-subsections">
										<?php foreach ( $section_groups as $group_index => $group ) : ?>
											<section class="lerm-settings-subsection"
												data-subsection-panel="<?php echo esc_attr( (string) $group['id'] ); ?>"
												<?php echo esc_attr( (string) $group['id'] !== $current_subsection ? 'hidden' : '' ); ?>>
												<div class="lerm-settings-stack" role="group" aria-label="<?php echo esc_attr( (string) $group['label'] ); ?>">
													<?php if ( ! empty( $group['fields'] ) ) : ?>
														<?php $this->container_field_renderer()->render_fields( (array) $group['fields'], $section_values, array( $this, 'render_field_control' ), (string) $section_id, $this->subsection_uses_group_headings( (array) $group['fields'], (string) $group['label'] ), 'stack', $section_errors ); ?>
													<?php else : ?>
														<div class="lerm-settings-empty-group"><?php esc_html_e( 'No settings in this group yet.', 'lerm-admin-config' ); ?></div>
													<?php endif; ?>
												</div>
											</section>
										<?php endforeach; ?>
									</div>
								<?php else : ?>
									<div class="lerm-settings-stack" role="group" aria-label="<?php echo esc_attr( (string) ( $section['title'] ?? __( 'Section', 'lerm-admin-config' ) ) ); ?>">
										<?php $this->container_field_renderer()->render_fields( $section_fields, $section_values, array( $this, 'render_field_control' ), (string) $section_id, true, 'stack', $section_errors ); ?>
									</div>
								<?php endif; ?>

								<div class="lerm-settings-actions lerm-settings-actions--footer">
									<button type="submit" class="button button-primary button-large" data-lerm-save><?php esc_html_e( 'Save changes', 'lerm-admin-config' ); ?></button>
									<button type="button" class="button button-secondary" data-lerm-reset="section"><?php esc_html_e( 'Reset current page', 'lerm-admin-config' ); ?></button>
									<button type="button" class="button button-secondary button-link-delete" data-lerm-reset="all"><?php esc_html_e( 'Reset all tabs', 'lerm-admin-config' ); ?></button>
								</div>
							</div>
							<?php endforeach; ?>
						</form>

						<?php $this->debug_panel->render(); ?>

					</div>
				</section>
			</div>
		</div>
		<?php
	}

	public function schema_id(): string {
		$id = isset( $this->definition['id'] ) && is_scalar( $this->definition['id'] ) ? sanitize_key( (string) $this->definition['id'] ) : '';

		return '' !== $id ? $id : sanitize_key( $this->option_name() );
	}

	/**
	 * Determine whether a section should render secondary navigation.
	 *
	 * @param array<string, mixed>               $section Section definition.
	 * @param array<int, array<string, mixed>>   $groups  Section groups.
	 */
	private function section_uses_subsections( array $section, array $groups ): bool {
		if ( array_key_exists( 'use_subsections', $section ) ) {
			return ! empty( $section['use_subsections'] ) && count( $groups ) > 1;
		}

		return count( $groups ) > 1;
	}

	/**
	 * Determine whether subsection panels should still render field group headings.
	 *
	 * @param array<int, array<string, mixed>> $fields          Subsection fields.
	 * @param string                           $subsection_label Current subsection label.
	 */
	private function subsection_uses_group_headings( array $fields, string $subsection_label ): bool {
		$labels = array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$heading = trim( (string) ( $field['group_heading'] ?? '' ) );

			if ( '' === $heading || in_array( $heading, $labels, true ) ) {
				continue;
			}

			$labels[] = $heading;
		}

		if ( count( $labels ) > 1 ) {
			return true;
		}

		if ( empty( $labels ) ) {
			return false;
		}

		return trim( $labels[0] ) !== trim( $subsection_label );
	}

	/**
	 * Resolve which subsection should be rendered initially for a section.
	 *
	 * @param string                           $section_id Section ID.
	 * @param array<int, array<string, mixed>> $groups     Section groups.
	 */
	private function current_subsection_for_section( string $section_id, array $groups ): string {
		if ( empty( $groups ) ) {
			return '';
		}

		$fallback_subsection = (string) ( $groups[0]['id'] ?? '' );

		if ( '' === $fallback_subsection || $section_id !== $this->submission->current_tab() ) {
			return $fallback_subsection;
		}

		$requested_subsection = isset( $_GET['subsection'] ) ? sanitize_key( wp_unslash( $_GET['subsection'] ) ) : '';

		if ( '' === $requested_subsection ) {
			return $fallback_subsection;
		}

		foreach ( $groups as $group ) {
			if ( (string) ( $group['id'] ?? '' ) === $requested_subsection ) {
				return $requested_subsection;
			}
		}

		return $fallback_subsection;
	}

	/**
	 * Delegates to ContainerFieldRenderer for test backward-compatibility.
	 *
	 * @deprecated 0.5.0 Use container_field_renderer()->render_field() instead.
	 *
	 * @param array<string, mixed> $field      Field definition.
	 * @param array<string, mixed> $values     Saved values.
	 * @param string               $layout     Layout mode.
	 * @param array<string, mixed> $field_errors Field error map.
	 */
	public function render_field( array $field, array $values, string $layout = 'table', array $field_errors = array() ): void {
		$render_control = function ( array $f, array $ctx, array $errs ): void {
			$this->render_field_control( $f, $ctx, $errs );
		};
		$this->container_field_renderer()->render_field( $field, $values, $render_control, $layout, $field_errors );
	}

	/**
	 * @param array<string, mixed> $field
	 * @param array{field_id: string, field_type: string, field_name: string, field_value: mixed} $context
	 * @param array<string, mixed> $field_errors
	 */
	private function render_field_control( array $field, array $context, array $field_errors ): void {
		$custom_render = $this->field_types->render_callback( $context['field_type'] );

		$previous_errors           = $this->render_field_errors;
		$this->render_field_errors = $field_errors;
		$this->render_path_stack[] = $context['field_id'];

		try {
			if ( is_callable( $custom_render ) ) {
				call_user_func( $custom_render, $field, $context['field_value'], $context['field_name'], $this );
			} else {
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" %5$s placeholder="%6$s">',
					esc_attr( (string) ( $field['input_type'] ?? 'text' ) ),
					esc_attr( $context['field_id'] ),
					esc_attr( $context['field_name'] ),
					esc_attr( $this->scalar_string( $context['field_value'] ) ),
					$this->dep_evaluator->controller_attribute( $field ),
					esc_attr( (string) ( $field['placeholder'] ?? '' ) )
				);
			}
		} finally {
			array_pop( $this->render_path_stack );
			$this->render_field_errors = $previous_errors;
		}
	}

	public function container_field_renderer(): ContainerFieldRenderer {
		return new ContainerFieldRenderer(
			function ( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
				$this->nested_render_proxy( $field, $value, $field_name, $input_id, $name_template, $id_template );
			},
			$this->render_field_errors,
			$this->current_render_path(),
			$this->dep_evaluator,
			$this->option_name()
		);
	}

	/**
	 * Proxy for rendering a nested sub-field inside a structured container.
	 *
	 * Contains the FieldTypeRegistry lookup and fallback rendering logic that
	 * was previously in ContainerFieldRenderer::render_nested_field().
	 *
	 * @param array<string, mixed> $field        Field definition.
	 * @param mixed                $value        Field value.
	 * @param string               $field_name   Form field name attribute.
	 * @param string               $input_id     DOM id attribute.
	 * @param string               $name_template Template for the name attribute in repeaters.
	 * @param string               $id_template   Template for the id attribute in repeaters.
	 */
	public function nested_render_proxy( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
		$field_type    = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$custom_render = $this->field_types->nested_render_callback( $field_type );

		if ( is_callable( $custom_render ) ) {
			call_user_func( $custom_render, $field, $value, $field_name, $input_id, $this, $name_template, $id_template );
			return;
		}

		$name_attr = '' !== $name_template ? ' data-name-template="' . esc_attr( $name_template ) . '"' : '';
		$id_attr   = '' !== $id_template ? ' data-id-template="' . esc_attr( $id_template ) . '"' : '';

		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" placeholder="%5$s"%6$s%7$s>',
			esc_attr( (string) ( $field['input_type'] ?? 'text' ) ),
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( PageSchema::scalar_value( $value ) ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
			$name_attr,
			$id_attr
		);
	}

	private function current_render_path(): string {
		if ( empty( $this->render_path_stack ) ) {
			return '';
		}

		return (string) end( $this->render_path_stack );
	}

	/**
	 * Resolve the capability required to use the page.
	 */
	private function capability(): string {
		$menu = is_array( $this->definition['menu'] ?? null ) ? $this->definition['menu'] : array();

		return (string) ( $menu['capability'] ?? $this->definition['capability'] ?? 'manage_options' );
	}

	/**
	 * Resolve the page slug.
	 */
	private function page_slug(): string {
		$page_id = isset( $this->definition['id'] ) ? sanitize_key( (string) $this->definition['id'] ) : '';

		return '' !== $page_id ? $page_id : 'admin-config';
	}

	/**
	 * Resolve the form input namespace / option name.
	 *
	 * Delegates to the store's backing StorageBackend so that the HTML form
	 * field names always match the key used by whichever backend is active
	 * (option row, term meta, user meta, post meta).
	 */
	private function option_name(): string {
		return $this->store->storage_key();
	}

	/**
	 * Resolve the admin path for the configured parent.
	 */
	private function admin_parent_url(): string {
		$menu   = is_array( $this->definition['menu'] ?? null ) ? $this->definition['menu'] : array();
		$parent = (string) ( $menu['parent_slug'] ?? 'themes.php' );

		if ( false !== strpos( $parent, '.php' ) ) {
			return $this->admin_base_url( $parent );
		}

		return add_query_arg( 'page', $parent, $this->admin_base_url( 'admin.php' ) );
	}

	/**
	 * Resolve the correct admin base URL for site or network admin.
	 */
	private function admin_base_url( string $path = '' ): string {
		return $this->network_admin ? network_admin_url( $path ) : admin_url( $path );
	}

	/**
	 * Resolve the form target for non-JS submissions.
	 */
	private function admin_post_url(): string {
		return $this->admin_base_url( 'admin-post.php' );
	}

	/**
	 * Nonce action for a section.
	 */
	private function nonce_action( string $tab ): string {
		return 'lerm_admin_config_' . $this->page_slug() . '_' . sanitize_key( $tab );
	}

	/**
	 * Non-JS admin-post action.
	 */
	private function save_action(): string {
		return 'lerm_admin_config_save_' . $this->page_slug();
	}

	/**
	 * Normalize scalar-like values to strings for safe rendering.
	 * Unified with OptionStore::string_value(); both delegate here via PageSchema.
	 *
	 * @param mixed  $value Source value.
	 * @param string $default_value Fallback string.
	 */
	private function scalar_string( $value, string $default_value = '' ): string {
		return PageSchema::scalar_value( $value, $default_value );
	}

	/**
	 * Return the change-listener attribute for fields that control dependencies.
	 *
	 * @param array<string, mixed> $field Field definition.
	 */
	public function dependency_controller_attribute( array $field ): string {
		return $this->dep_evaluator->controller_attribute( $field );
	}

}
