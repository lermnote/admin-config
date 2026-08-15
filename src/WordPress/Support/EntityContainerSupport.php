<?php
/**
 * Shared state and flash helpers for entity containers.
 *
 * Consumed by the metabox, taxonomy, profile, and comment containers, which
 * all mount schemas against an entity store, render through ValidationFlash,
 * and guard saves with a nonce + capability check.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Support;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Stores\StoreResolver;
use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\Framework\Storage\OptionStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait EntityContainerSupport {

	/**
	 * @var array<string, CompiledSchema>
	 */
	private array $schemas = array();

	private Framework $framework;

	private StoreResolver $stores;

	public function __construct( Framework $framework, StoreResolver $stores ) {
		$this->framework = $framework;
		$this->stores    = $stores;
	}

	/**
	 * @return array<string, CompiledSchema>
	 */
	public function schemas(): array {
		return $this->schemas;
	}

	public function framework(): Framework {
		return $this->framework;
	}

	/**
	 * Consume the validation flash for a container resource and resolve the
	 * render inputs: replayed values, field errors, and the section notice.
	 *
	 * @param string $resource Flash resource key (entity ID or add-form key).
	 * @return array{values: array<string, mixed>, errors: array<string, mixed>, notice: array{class: string, message: string}|null}
	 */
	protected function consume_flash( string $scope, CompiledSchema $schema, string $resource, OptionStore $store ): array {
		$flash = ValidationFlash::consume( $scope, $schema->id(), $resource );

		return array(
			'values' => ValidationFlash::render_values( $store->all(), $flash, $schema->definition(), $this->framework->field_types() ),
			'errors' => ValidationFlash::field_errors( $flash ),
			'notice' => ValidationFlash::notice( $flash ),
		);
	}

	/**
	 * Output the save nonce field for a container scope.
	 */
	protected function nonce_field( string $scope, CompiledSchema $schema ): void {
		wp_nonce_field(
			ContainerSaveSupport::nonce_action( $scope, $schema ),
			ContainerSaveSupport::nonce_name( $scope, $schema )
		);
	}
}
