<?php
/**
 * WordPress comment container backed by admin-config schema and stores.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Containers;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Contracts\Container;
use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Backends\ArrayBackend;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\WordPress\Support\ContainerSaveSupport;
use Lerm\AdminConfig\WordPress\Support\EntityContainerSupport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CommentContainer implements Container {

	use EntityContainerSupport;

	private bool $hooks_registered       = false;
	private bool $assets_hook_registered = false;

	public function type(): string {
		return 'comment';
	}

	public function mount( CompiledSchema $schema ): void {
		$this->schemas[ $schema->id() ] = $schema;

		if ( ! $this->hooks_registered ) {
			add_action( 'add_meta_boxes_comment', array( $this, 'register_meta_boxes' ) );
			add_action( 'edit_comment', array( $this, 'save_comment' ) );
			$this->hooks_registered = true;
		}

		if ( ! $this->assets_hook_registered ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			$this->assets_hook_registered = true;
		}
	}

	public function register_meta_boxes( ?\WP_Comment $comment = null ): void {
		foreach ( $this->schemas as $schema ) {
			$container = $schema->container();
			$priority  = (string) ( $container['priority'] ?? 'default' );
			$priority  = in_array( $priority, array( 'core', 'default', 'high', 'low' ), true ) ? $priority : 'default';

			$context = (string) ( $container['context'] ?? 'normal' );
			$context = in_array( $context, array( 'normal', 'side', 'advanced' ), true ) ? $context : 'normal';

			add_meta_box(
				$this->meta_box_id( $schema ),
				(string) ( $container['title'] ?? $schema->definition()['title'] ?? __( 'Comment Settings', 'lerm-admin-config' ) ),
				array( $this, 'render_meta_box' ),
				'comment',
				$context,
				$priority,
				array(
					'schema_id' => $schema->id(),
				)
			);
		}
	}

	public function enqueue_assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'comment' !== $screen->id ) {
			return;
		}

		foreach ( $this->schemas as $schema ) {
			$this->renderer( $schema )->lifecycle()->enqueue_support_assets( 'comment-' . $schema->id() );
		}
	}

	public function render_meta_box( \WP_Comment $comment, array $callback_args ): void {
		$schema_id = isset( $callback_args['args']['schema_id'] ) ? sanitize_key( (string) $callback_args['args']['schema_id'] ) : '';

		if ( '' === $schema_id || ! isset( $this->schemas[ $schema_id ] ) ) {
			return;
		}

		$schema      = $this->schemas[ $schema_id ];
		$store       = $this->stores->store( $schema, array( 'comment_id' => $comment->comment_ID ) );
		$renderer    = $this->renderer( $schema, $store );
		$sections    = PageSchema::sections( $schema->definition() );
		$flash       = $this->consume_flash( 'comment', $schema, (string) $comment->comment_ID, $store );
		$show_titles = count( $sections ) > 1;

		echo '<div class="lerm-comment-metabox lerm-metabox--stack">';

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

		$this->nonce_field( 'comment', $schema );
		echo '</div>';
	}

	public function save_comment( int $comment_id ): void {
		$comment = get_comment( $comment_id );

		if ( ! $comment instanceof \WP_Comment ) {
			return;
		}

		foreach ( $this->schemas as $schema ) {
			if ( ! ContainerSaveSupport::authorize_save( 'comment', $schema, 'edit_comment', $comment_id ) ) {
				continue;
			}

			$store     = $this->stores->store( $schema, array( 'comment_id' => $comment_id ) );
			$submitted = ContainerSaveSupport::submitted_values( $store );

			ContainerSaveSupport::persist(
				'comment',
				$schema->id(),
				(string) $comment_id,
				$store,
				$submitted,
				null,
				__( 'Please review the highlighted comment fields before saving again.', 'lerm-admin-config' ),
				__( 'Unable to save these comment settings right now.', 'lerm-admin-config' )
			);
		}
	}

	private function renderer( CompiledSchema $schema, ?OptionStore $store = null ): OptionsPage {
		$resolved_store = $store ?? $this->framework->store(
			$schema->definition(),
			new ArrayBackend( 'comment_defaults_' . $schema->id() )
		);

		return $this->framework->render_options_page( $schema->definition(), $resolved_store );
	}

	private function meta_box_id( CompiledSchema $schema ): string {
		return 'lerm-admin-config-comment-' . $schema->id();
	}
}
