<?php
/**
 * WordPress metabox container backed by admin-config schema and stores.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Containers;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Contracts\Container;
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\WordPress\Support\HasBlockEditorPanel;
use Lerm\AdminConfig\WordPress\Support\BlockEditorPanelContext;
use Lerm\AdminConfig\WordPress\Support\ContainerSaveSupport;
use Lerm\AdminConfig\WordPress\Support\EntityContainerSupport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MetaboxContainer implements Container, BlockEditorPanelContext {

	use HasBlockEditorPanel;
	use EntityContainerSupport;

	private bool $hooks_registered = false;

	public function container_type_for_block_panel(): string {
		return 'metabox';
	}

	public function type(): string {
		return 'metabox';
	}

	public function mount( CompiledSchema $schema ): void {
		$this->schemas[ $schema->id() ] = $schema;

		if ( $this->hooks_registered ) {
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		$this->hooks_registered = true;
	}

	/**
	 * @param \WP_Post $post
	 */
	public function register_meta_boxes( string $post_type, $post = null ): void {
		if ( $this->post_context_uses_block_editor( $post_type, $post instanceof \WP_Post ? $post : null ) ) {
			return;
		}

		foreach ( $this->schemas as $schema ) {
			$container  = $schema->container();
			$post_types = ContainerSaveSupport::normalize_string_list( $container['post_types'] ?? array() );

			if ( ! in_array( $post_type, $post_types, true ) ) {
				continue;
			}

			$priority = (string) ( $container['priority'] ?? 'default' );
			$priority = in_array( $priority, array( 'core', 'default', 'high', 'low' ), true ) ? $priority : 'default';

			$context = (string) ( $container['context'] ?? 'advanced' );
			$context = in_array( $context, array( 'normal', 'side', 'advanced' ), true ) ? $context : 'advanced';

			add_meta_box(
				$this->meta_box_id( $schema ),
				(string) ( $container['title'] ?? $schema->definition()['title'] ?? __( 'Settings', 'lerm-admin-config' ) ),
				array( $this, 'render_meta_box' ),
				$post_type,
				$context,
				$priority,
				array(
					'schema_id' => $schema->id(),
				)
			);
		}
	}

	public function render_meta_box( \WP_Post $post, array $callback_args ): void {
		$schema_id = isset( $callback_args['args']['schema_id'] ) ? sanitize_key( (string) $callback_args['args']['schema_id'] ) : '';

		if ( '' === $schema_id || ! isset( $this->schemas[ $schema_id ] ) ) {
			return;
		}

		$schema      = $this->schemas[ $schema_id ];
		$store       = $this->stores->store( $schema, array( 'post_id' => $post->ID ) );
		$renderer    = $this->framework->render_options_page( $schema->definition(), $store );
		$sections    = PageSchema::sections( $schema->definition() );
		$flash       = $this->consume_flash( 'metabox', $schema, (string) $post->ID, $store );
		$show_titles = count( $sections ) > 1;

		if ( empty( $sections ) ) {
			return;
		}

		$this->nonce_field( 'metabox', $schema );

		echo '<div class="lerm-metabox lerm-metabox--stack">';
		if ( null !== $flash['notice'] ) {
			printf(
				'<div class="notice %1$s inline"><p>%2$s</p></div>',
				esc_attr( $flash['notice']['class'] ),
				esc_html( $flash['notice']['message'] )
			);
		}

		foreach ( $sections as $section_id => $section ) {
			$title       = isset( $section['title'] ) && is_scalar( $section['title'] ) ? (string) $section['title'] : '';
			$description = isset( $section['description'] ) && is_scalar( $section['description'] ) ? (string) $section['description'] : '';

			if ( $show_titles && '' !== $title ) {
				printf( '<h3>%s</h3>', esc_html( $title ) );
			}

			if ( '' !== $description ) {
				printf( '<p class="description">%s</p>', esc_html( $description ) );
			}

			$renderer->container_field_renderer()->render_fields(
				PageSchema::section_fields( $section ),
				$flash['values'],
				$renderer->field_control_renderer(),
				(string) $section_id,
				false,
				'stack',
				$flash['errors']
			);
		}
		echo '</div>';
	}

	public function save_post( int $post_id, \WP_Post $post ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		foreach ( $this->schemas as $schema ) {
			$container  = $schema->container();
			$post_types = ContainerSaveSupport::normalize_string_list( $container['post_types'] ?? array() );

			if ( ! in_array( $post->post_type, $post_types, true ) ) {
				continue;
			}

			if ( ! ContainerSaveSupport::authorize_save( 'metabox', $schema, 'edit_post', $post_id ) ) {
				continue;
			}

			$store     = $this->stores->store( $schema, array( 'post_id' => $post_id ) );
			$submitted = ContainerSaveSupport::submitted_values( $store );

			ContainerSaveSupport::persist(
				'metabox',
				$schema->id(),
				(string) $post_id,
				$store,
				$submitted,
				null,
				__( 'Please review the highlighted metabox fields before saving again.', 'lerm-admin-config' ),
				__( 'Unable to save these metabox settings right now.', 'lerm-admin-config' )
			);
		}
	}

	private function meta_box_id( CompiledSchema $schema ): string {
		return 'lerm-admin-config-metabox-' . $schema->id();
	}

	private function post_context_uses_block_editor( string $post_type, ?\WP_Post $post = null ): bool {
		if ( null !== $post ) {
			return use_block_editor_for_post( $post );
		}

		return '' !== $post_type
			&& use_block_editor_for_post_type( $post_type );
	}
}
