const { test, expect } = require( '@playwright/test' );
const { login } = require( './helpers/wp-admin' );

test( 'options page saves through the classic non-JS form and redirects with a success notice', async ( { browser } ) => {
	const context = await browser.newContext( { javaScriptEnabled: false } );
	const page = await context.newPage();

	try {
		await login( page );
		await page.goto( '/wp-admin/options-general.php?page=acme-demo-settings' );

		const tonePreset = page.locator( 'select[name="acme_demo_settings[tone_preset]"]' );

		await expect( tonePreset ).toBeVisible();
		await tonePreset.selectOption( 'bold' );

		await page.locator( 'button[data-lerm-save]' ).click();
		await page.waitForLoadState( 'domcontentloaded' );

		await expect( page ).toHaveURL( /lerm_admin_config_status=success/ );
		await expect( page.locator( '.lerm-settings-form-notice' ) ).toContainText( /Settings saved/ );
		await expect( tonePreset ).toHaveValue( 'bold' );
	} finally {
		await context.close();
	}
} );
