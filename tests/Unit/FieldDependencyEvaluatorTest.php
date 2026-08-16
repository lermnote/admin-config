<?php
/**
 * FieldDependencyEvaluator operator matrix tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\Admin\FieldDependencyEvaluator;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class FieldDependencyEvaluatorTest extends TestCase {

	private FieldDependencyEvaluator $evaluator;

	protected function setUp(): void {
		parent::setUp();

		$this->evaluator = new FieldDependencyEvaluator( array() );
	}

	public function testEqualityMatchesScalarValues(): void {
		$this->assertTrue( $this->evaluator->matches( 'red', '==', 'red' ) );
		$this->assertFalse( $this->evaluator->matches( 'blue', '==', 'red' ) );
		// An empty operator defaults to equality.
		$this->assertTrue( $this->evaluator->matches( 'red', '', 'red' ) );
	}

	public function testNotEqualOperatorsExcludeTheExpectedValue(): void {
		$this->assertFalse( $this->evaluator->matches( 'red', '!=', 'red' ) );
		$this->assertTrue( $this->evaluator->matches( 'blue', '!=', 'red' ) );
		$this->assertFalse( $this->evaluator->matches( 'red', '!==', 'red' ) );
		$this->assertTrue( $this->evaluator->matches( 'blue', '!==', 'red' ) );
	}

	public function testInOperatorChecksArrayIntersection(): void {
		$this->assertTrue( $this->evaluator->matches( array( 'news', 'blog' ), 'in', array( 'news', 'rss' ) ) );
		$this->assertFalse( $this->evaluator->matches( array( 'blog' ), 'in', array( 'news', 'rss' ) ) );
		$this->assertTrue( $this->evaluator->matches( 'news', 'in', array( 'news', 'rss' ) ) );
	}

	public function testNotInOperatorsRequireEmptyIntersection(): void {
		$this->assertTrue( $this->evaluator->matches( array( 'blog' ), 'not_in', array( 'news', 'rss' ) ) );
		$this->assertFalse( $this->evaluator->matches( array( 'news' ), 'not_in', array( 'news', 'rss' ) ) );
		$this->assertTrue( $this->evaluator->matches( array( 'blog' ), 'not in', array( 'news', 'rss' ) ) );
	}

	public function testNumericComparisonOperators(): void {
		$this->assertTrue( $this->evaluator->matches( 3, '>', 2 ) );
		$this->assertFalse( $this->evaluator->matches( 2, '>', 2 ) );
		$this->assertTrue( $this->evaluator->matches( 2, '>=', 2 ) );
		$this->assertTrue( $this->evaluator->matches( 1, '<', 2 ) );
		$this->assertFalse( $this->evaluator->matches( 2, '<', 2 ) );
		$this->assertTrue( $this->evaluator->matches( 2, '<=', 2 ) );
	}

	public function testNumericOperatorsRejectNonNumericValues(): void {
		$this->assertFalse( $this->evaluator->matches( 'abc', '>', 2 ) );
		$this->assertFalse( $this->evaluator->matches( 3, '>', 'abc' ) );
		$this->assertFalse( $this->evaluator->matches( 'abc', '<=', 5 ) );
	}

	public function testMatchesArrayActualValuesAgainstScalarExpected(): void {
		$this->assertTrue( $this->evaluator->matches( array( 'news', 'blog' ), '==', 'news' ) );
		$this->assertFalse( $this->evaluator->matches( array( 'news', 'blog' ), '==', 'rss' ) );
	}
}
