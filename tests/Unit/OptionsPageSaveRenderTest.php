<?php
/**
 * OptionsPage non-JS save flow and page render tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\FieldTypes\AdvancedFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\BuiltinFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\FieldTypes\StructuredFieldTypes;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

final class OptionsPageSaveRenderTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		$_POST = array();
		$_GET  = array();
	}

	public function testRenderPageOutputsFormNonceNavigationAndFields(): void {
		$_GET['tab'] = 'general';

		$output = $this->capture_render( $this->options_page() );

		$this->assertStringContainsString( '<form method="post" action="https://example.test/wp-admin/admin-post.php"', $output );
		$this->assertStringContainsString( 'name="action" value="lerm_admin_config_save_unit_save_render"', $output );
		$this->assertStringContainsString( 'name="_wpnonce" value="nonce-lerm_admin_config_unit_save_render_general"', $output );
		$this->assertStringContainsString( 'class="lerm-settings-nav__item is-active"', $output );
		$this->assertStringContainsString( 'data-tab-target="general"', $output );
		$this->assertStringContainsString( 'data-tab-panel="advanced"', $output );
		$this->assertStringContainsString( 'name="options_framework[headline]"', $output );
		$this->assertStringContainsString( 'name="options_framework[mode]"', $output );
		$this->assertStringContainsString( '>Save changes</button>', $output );
	}

	public function testHandleSavePersistsValuesAndRedirectsWithSuccessStatus(): void {
		$_POST = array(
			'lerm_settings_tab' => 'general',
			'_wpnonce'          => 'nonce-lerm_admin_config_unit_save_render_general',
			'options_framework' => array(
				'headline' => 'Saved headline',
			),
		);

		$this->assertThrows(
			\LermAdminConfigRedirectIntercepted::class,
			function (): void {
				$this->options_page()->handle_save();
			}
		);

		$this->assertSame(
			'https://example.test/wp-admin/themes.php?page=unit_save_render&tab=general&lerm_admin_config_status=success',
			(string) end( $GLOBALS['lerm_admin_config_redirects'] )
		);
		$this->assertSame(
			'lerm_admin_config_unit_save_render_general',
			(string) end( $GLOBALS['lerm_admin_config_checked_referers'] )
		);
		$this->assertSame(
			'Saved headline',
			$GLOBALS['lerm_admin_config_options']['options_framework']['headline'] ?? null
		);
		$this->assertEmpty( $GLOBALS['lerm_admin_config_transients'] );
	}

	public function testHandleSaveValidationFailureStoresFlashAndSkipsPersist(): void {
		$field_types = $this->field_types();
		$field_types->register_validator(
			'text',
			static function ( array $field, $value, bool $strict, OptionStore $store ) {
				unset( $field, $strict, $store );

				if ( 'bad' === $value ) {
					return new \WP_Error( 'invalid_headline', 'Headline cannot be bad.' );
				}

				return $value;
			}
		);

		$_POST = array(
			'lerm_settings_tab' => 'general',
			'_wpnonce'          => 'nonce-lerm_admin_config_unit_save_render_general',
			'options_framework' => array(
				'headline' => 'bad',
			),
		);

		$this->assertThrows(
			\LermAdminConfigRedirectIntercepted::class,
			function () use ( $field_types ): void {
				$this->options_page( $field_types )->handle_save();
			}
		);

		$redirect = (string) end( $GLOBALS['lerm_admin_config_redirects'] );
		$this->assertStringContainsString( 'lerm_admin_config_status=validation_error', $redirect );

		$flash = (array) reset( $GLOBALS['lerm_admin_config_transients'] );
		$this->assertSame( 'general', $flash['tab'] ?? null );
		$this->assertArrayHasKey( 'headline', is_array( $flash['errors'] ?? null ) ? $flash['errors'] : array() );
		$this->assertSame( 'bad', $flash['submitted']['headline'] ?? null );

		$this->assertArrayNotHasKey( 'options_framework', $GLOBALS['lerm_admin_config_options'] );
	}

	public function testHandleSaveDeniesUsersWithoutCapability(): void {
		$GLOBALS['lerm_admin_config_current_user_can'] = false;

		$this->assertThrows(
			\LermAdminConfigWpDieIntercepted::class,
			function (): void {
				$this->options_page()->handle_save();
			}
		);

		$this->assertStringContains(
			'not allowed',
			(string) end( $GLOBALS['lerm_admin_config_wp_die'] )
		);
	}

	public function testRenderPageReplaysFlashedValuesErrorsAndNotice(): void {
		ValidationFlash::store(
			'options_page',
			'unit_save_render',
			'unit_save_render',
			array(
				'tab'        => 'general',
				'subsection' => '',
				'global'     => true,
				'message'    => 'Please review the highlighted fields before saving again.',
				'errors'     => array(
					'headline' => array( 'Headline cannot be bad.' ),
				),
				'submitted'  => array(
					'headline' => 'bad',
				),
			)
		);

		$_GET['tab']                      = 'general';
		$_GET['lerm_admin_config_status'] = 'validation_error';

		$output = $this->capture_render( $this->options_page() );

		$this->assertStringContainsString( 'lerm-settings-form-notice notice notice-error inline', $output );
		$this->assertStringContainsString( 'Please review the highlighted fields before saving again.', $output );
		$this->assertStringContainsString( 'value="bad"', $output );
		$this->assertStringContainsString( 'Headline cannot be bad.', $output );
		$this->assertStringContainsString( 'class="lerm-settings-row is-invalid"', $output );
	}

	private function options_page( ?FieldTypeRegistry $field_types = null ): OptionsPage {
		$field_types = $field_types ?? $this->field_types();
		$definition  = array(
			'id'       => 'unit_save_render',
			'sections' => array(
				'general'  => array(
					'title'  => 'General',
					'fields' => array(
						array(
							'id'    => 'headline',
							'type'  => 'text',
							'label' => 'Headline',
						),
					),
				),
				'advanced' => array(
					'title'  => 'Advanced',
					'fields' => array(
						array(
							'id'      => 'mode',
							'type'    => 'select',
							'label'   => 'Mode',
							'choices' => array(
								'light' => 'Light',
								'dark'  => 'Dark',
							),
						),
					),
				),
			),
		);

		$store    = new OptionStore( $definition, $field_types );
		$resolver = new class() implements AssetResolver {
			public function url( string $filename ): string {
				return 'https://example.test/assets/' . ltrim( $filename, '/' );
			}

			public function version(): string {
				return 'unit-version';
			}
		};

		return new OptionsPage( $definition, $store, $field_types, $resolver, false );
	}

	private function field_types(): FieldTypeRegistry {
		$field_types = new FieldTypeRegistry();

		foreach ( array_merge( BuiltinFieldTypes::definitions(), StructuredFieldTypes::definitions(), AdvancedFieldTypes::definitions() ) as $type => $field_type_definition ) {
			$field_types->register( (string) $type, $field_type_definition );
		}

		return $field_types;
	}

	private function capture_render( OptionsPage $page ): string {
		ob_start();

		try {
			$page->render_page();

			return (string) ob_get_clean();
		} catch ( \Throwable $throwable ) {
			ob_end_clean();
			throw $throwable;
		}
	}
}
