import type { Locator } from '@playwright/test';
import { test, expect, type Page } from './support/fixtures';
import { logIn, publishInDialog } from './support/tab-editor';
import { wpCli } from './support/wp-cli';

type Fixtures = { product: number; blue: number; red: number; url: string };

function createFixtures( stamp: string ): Fixtures {
	return JSON.parse(
		wpCli(
			'eval',
			`
$attribute = new WC_Product_Attribute();
$attribute->set_name( "Colour" ); $attribute->set_options( [ "Blue", "Red" ] ); $attribute->set_position( 0 ); $attribute->set_visible( true ); $attribute->set_variation( true );
$product = new WC_Product_Variable();
$product->set_name( "E2E Variation Tabs ${ stamp }" ); $product->set_status( "publish" ); $product->set_attributes( [ $attribute ] ); $product->save();
$ids = [];
foreach ( [ "Blue", "Red" ] as $colour ) {
	$variation = new WC_Product_Variation();
	$variation->set_status( "publish" ); $variation->set_parent_id( $product->get_id() ); $variation->set_attributes( [ "colour" => $colour ] ); $variation->set_regular_price( "5" ); $variation->save();
	$ids[ $colour ] = $variation->get_id();
}
WC_Product_Variable::sync( $product->get_id() );
$tab = static function ( $title, $scope, $meta ) {
	$id = wp_insert_post( [ "post_type" => "jps_cs_tab", "post_status" => "publish", "post_title" => $title, "post_content" => "<p>$title content</p>" ] );
	update_post_meta( $id, "_jps_cs_scope", $scope );
	foreach ( $meta as $key => $value ) { update_post_meta( $id, $key, $value ); }
	return $id;
};
$parent_tab = $tab( "Parent specs ${ stamp }", "products", [ "_jps_cs_products" => [ $product->get_id() ], "_jps_cs_priority" => "30" ] );
$tab( "Blue specs ${ stamp }", "variations", [ "_jps_cs_variations" => [ $ids["Blue"] ], "_jps_cs_replaces" => $parent_tab, "_jps_cs_priority" => "" ] );
$tab( "Red extra ${ stamp }", "variations", [ "_jps_cs_variations" => [ $ids["Red"] ], "_jps_cs_priority" => "60" ] );
echo wp_json_encode( [ "product" => $product->get_id(), "blue" => $ids["Blue"], "red" => $ids["Red"], "url" => get_permalink( $product->get_id() ) ] );`
		)
	);
}

function deleteFixtures( fixtures: Fixtures, stamp: string ): void {
	wpCli(
		'eval',
		`wp_delete_post( ${ fixtures.product }, true ); foreach ( get_posts( [ "post_type" => "jps_cs_tab", "post_status" => array_values( get_post_stati() ), "numberposts" => -1, "s" => "${ stamp }" ] ) as $tab ) { wp_delete_post( $tab->ID, true ); }`
	);
}

async function expectSelectionFollowsTabs( page: Page, stamp: string, isShown: ( title: string ) => Locator ): Promise<void> {
	const parent = isShown( `Parent specs ${ stamp }` );
	const blue = isShown( `Blue specs ${ stamp }` );
	const red = isShown( `Red extra ${ stamp }` );
	const colour = page.locator( 'select[name="attribute_colour"]' );
	await expect( parent ).toBeVisible();
	await expect( blue ).toBeHidden();
	await expect( red ).toBeHidden();
	await colour.selectOption( 'Blue' );
	await expect( blue ).toBeVisible();
	await expect( parent ).toBeHidden();
	await expect( red ).toBeHidden();
	await colour.selectOption( 'Red' );
	await expect( red ).toBeVisible();
	await expect( parent ).toBeVisible();
	await expect( blue ).toBeHidden();
	await page.getByRole( 'link', { name: /clear/i } ).click();
	await expect( parent ).toBeVisible();
	await expect( blue ).toBeHidden();
	await expect( red ).toBeHidden();
}

test( 'variation tabs follow the selected variation in the classic tabs layout', async ( { page } ) => {
	const stamp = String( Date.now() );
	const fixtures = createFixtures( stamp );
	try {
		await page.goto( new URL( fixtures.url ).pathname );
		await expectSelectionFollowsTabs( page, stamp, ( title ) => page.getByRole( 'tab', { name: title } ) );
	} finally {
		deleteFixtures( fixtures, stamp );
	}
} );

test( 'variation tabs follow the selected variation in the accordion layout', async ( { page } ) => {
	const stamp = String( Date.now() );
	const fixtures = createFixtures( stamp );
	const accordionItem = ( title: string ): string =>
		`<!-- wp:woocommerce/accordion-item -->
<div class="wp-block-woocommerce-accordion-item"><!-- wp:woocommerce/accordion-header -->
<h3 class="wp-block-woocommerce-accordion-header accordion-item__heading"><button class="accordion-item__toggle"><span>${ title }</span></button></h3>
<!-- /wp:woocommerce/accordion-header -->
<!-- wp:woocommerce/accordion-panel -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper"><!-- wp:paragraph --><p>${ title } body</p><!-- /wp:paragraph --></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item -->`;
	const details = `<!-- wp:woocommerce/product-details {"align":"wide","className":"is-style-minimal"} -->
<!-- wp:woocommerce/accordion-group -->
<div class="wp-block-woocommerce-accordion-group">${ accordionItem( 'Accordion layout' ) }</div>
<!-- /wp:woocommerce/accordion-group -->
<!-- /wp:woocommerce/product-details -->`;
	const encoded = Buffer.from( details ).toString( 'base64' );
	const templateId = Number(
		wpCli(
			'eval',
			`$content = file_get_contents( WC_ABSPATH . "templates/templates/blockified/single-product.html" ); $content = str_replace( '<!-- wp:woocommerce/product-details {"align":"wide","className":"is-style-minimal"} /-->', base64_decode( "${ encoded }" ), $content ); $id = wp_insert_post( [ "post_type" => "wp_template", "post_name" => "single-product", "post_title" => "Single Product", "post_status" => "publish", "post_content" => $content ] ); wp_set_object_terms( $id, get_stylesheet(), "wp_theme" ); echo $id;`
		)
	);
	try {
		await page.goto( new URL( fixtures.url ).pathname );
		await expect( page.locator( '.wp-block-woocommerce-accordion-item', { hasText: 'Accordion layout' } ) ).toBeVisible();
		await expectSelectionFollowsTabs( page, stamp, ( title ) => page.locator( '.wp-block-woocommerce-accordion-item', { hasText: title } ) );
	} finally {
		wpCli( 'eval', `wp_delete_post( ${ templateId }, true );` );
		deleteFixtures( fixtures, stamp );
	}
} );

test( 'a variation tab is added in the variation panel through the editor dialog', async ( { page } ) => {
	const stamp = String( Date.now() );
	const fixtures = createFixtures( stamp );
	const title = `Blue dialog ${ stamp }`;
	try {
		await logIn( page );
		await page.goto( `/wp-admin/post.php?post=${ fixtures.product }&action=edit` );
		await page.locator( 'a[href="#variable_product_options"]' ).click();
		const blueRow = page.locator( '.woocommerce_variation' ).filter( { has: page.locator( `input[name^="variable_post_id"][value="${ fixtures.blue }"]` ) } );
		await blueRow.locator( 'h3' ).first().click();
		const manager = blueRow.locator( '.jps-cs-tab-manager[data-scope="variations"]' );
		await manager.locator( '.jps-cs-tab-open', { hasText: 'Add tab' } ).click();
		await publishInDialog( page, title, `Blue dialog body ${ stamp }` );
		await expect( manager.locator( '.jps-cs-tab-list tr', { hasText: title } ) ).toContainText( 'This variation', { timeout: 30_000 } );
		await manager.locator( '.jps-cs-tab-dialog__close' ).click();
		const saved = JSON.parse(
			wpCli(
				'eval',
				`$tab = get_posts( [ "post_type" => "jps_cs_tab", "post_status" => "publish", "numberposts" => 1, "title" => "${ title }" ] )[0]; echo wp_json_encode( [ get_post_meta( $tab->ID, "_jps_cs_scope", true ), get_post_meta( $tab->ID, "_jps_cs_variations", true ) ] );`
			)
		);
		expect( saved ).toEqual( [ 'variations', [ fixtures.blue ] ] );
	} finally {
		deleteFixtures( fixtures, stamp );
	}
} );
