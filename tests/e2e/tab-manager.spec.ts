import { test, expect } from './support/fixtures';
import { logIn, publishInDialog } from './support/tab-editor';
import { wpCli } from './support/wp-cli';

type Fixtures = { category: number; product: number; url: string };

test( 'tabs are managed on the category and product screens through the editor dialog', async ( { page } ) => {
	const stamp = String( Date.now() );
	const categoryName = `E2E Tabs ${ stamp }`;
	const categoryTab = `Category tab ${ stamp }`;
	const productTab = `Product tab ${ stamp }`;
	const fixtures: Fixtures = JSON.parse(
		wpCli(
			'eval',
			`$term = wp_insert_term( "${ categoryName }", "product_cat" ); $product = new WC_Product_Simple(); $product->set_name( "E2E Tab Product ${ stamp }" ); $product->set_status( "publish" ); $product->set_category_ids( [ $term["term_id"] ] ); $product->save(); echo wp_json_encode( [ "category" => $term["term_id"], "product" => $product->get_id(), "url" => get_permalink( $product->get_id() ) ] );`
		)
	);
	try {
		await logIn( page );

		await page.goto( '/wp-admin/' );
		await expect( page.locator( '#menu-posts-product a[href="edit.php?post_type=jps_cs_tab"]' ) ).toHaveCount( 0 );

		await page.goto( `/wp-admin/term.php?taxonomy=product_cat&tag_ID=${ fixtures.category }&post_type=product` );
		await page.locator( '.jps-cs-tab-manager .jps-cs-tab-open', { hasText: 'Add tab' } ).click();
		await expect( page.locator( '.jps-cs-tab-dialog' ) ).toBeVisible();
		await publishInDialog( page, categoryTab, `Category body ${ stamp }` );
		await expect( page.locator( '.jps-cs-tab-list' ) ).toContainText( categoryTab, { timeout: 30_000 } );
		await expect( page.locator( '.jps-cs-tab-dialog' ) ).toBeVisible();
		await page.locator( '.jps-cs-tab-dialog__close' ).click();

		await page.goto( `/wp-admin/post.php?post=${ fixtures.product }&action=edit` );
		await page.locator( 'a[href="#jps-cs-product-tabs"]' ).click();
		const inherited = page.locator( '.jps-cs-tab-list tr', { hasText: categoryTab } );
		await expect( inherited ).toContainText( `Category: ${ categoryName }` );
		await expect( inherited.locator( 'input[name="_jps_cs_hidden_tabs[]"]' ) ).toBeVisible();
		await page.locator( '#jps-cs-product-tabs .jps-cs-tab-open', { hasText: 'Add tab' } ).click();
		await publishInDialog( page, productTab, `Product body ${ stamp }` );
		await expect( page.locator( '.jps-cs-tab-list' ) ).toContainText( productTab, { timeout: 30_000 } );
		await page.locator( '.jps-cs-tab-dialog__close' ).click();
		await expect( page.locator( '.jps-cs-tab-list tr', { hasText: productTab } ) ).toContainText( 'This product' );

		await page.goto( new URL( fixtures.url ).pathname );
		await expect( page.getByRole( 'tab', { name: categoryTab } ) ).toBeVisible();
		await expect( page.getByRole( 'tab', { name: productTab } ) ).toBeVisible();
	} finally {
		wpCli(
			'eval',
			`wp_delete_post( ${ fixtures.product }, true ); wp_delete_term( ${ fixtures.category }, "product_cat" ); foreach ( get_posts( [ "post_type" => "jps_cs_tab", "post_status" => array_values( get_post_stati() ), "numberposts" => -1, "s" => "${ stamp }" ] ) as $tab ) { wp_delete_post( $tab->ID, true ); }`
		);
	}
} );
