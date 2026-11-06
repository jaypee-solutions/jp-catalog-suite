<?php
/**
 * Germanized quote-mode integration behavior.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

final class Test_Germanized extends WP_UnitTestCase {

    public function test_quote_mode_removes_only_non_kept_legal_checkboxes(): void {
        if (
            ! class_exists( 'WooCommerce_Germanized' )
            || ! class_exists( 'WC_GZD_Legal_Checkbox_Manager' )
        ) {
            $this->markTestSkipped( 'Germanized is not active in the integration environment.' );
        }

        $manager = WC_GZD_Legal_Checkbox_Manager::instance();
        $manager->do_register_action();
        $this->assertSame( [ 'privacy' ], array_keys( $manager->get_checkboxes() ) );

        $keep_terms = static fn (): array => [ 'privacy', 'terms' ];
        add_filter( 'jps_cs_germanized_kept_checkboxes', $keep_terms );
        $manager->do_register_action();
        remove_filter( 'jps_cs_germanized_kept_checkboxes', $keep_terms );
        $this->assertSame( [ 'terms', 'privacy' ], array_keys( $manager->get_checkboxes() ) );

        add_filter( 'jps_cs_quote_mode_active', '__return_false' );
        $manager->do_register_action();
        remove_filter( 'jps_cs_quote_mode_active', '__return_false' );
        $this->assertContains( 'download', array_keys( $manager->get_checkboxes() ) );
    }
}
