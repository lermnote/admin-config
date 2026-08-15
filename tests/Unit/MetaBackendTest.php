<?php
/**
 * Meta backend write semantics tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\Backends\UserMetaBackend;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class MetaBackendTest extends TestCase {

	public function testWriteTreatsIdenticalPayloadAsSuccess(): void {
		$backend = new UserMetaBackend( 7, 'unit_meta_backend' );
		$payload = array(
			'headline' => 'Stored',
		);

		$this->assertTrue( $backend->write( $payload ) );
		$this->assertTrue( $backend->write( $payload ) );
	}
}
