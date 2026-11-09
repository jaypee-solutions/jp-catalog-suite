<?php
/**
 * One-time migration to single-owner product tabs.
 *
 * Tabs that listed several categories or products are split into one copy per owner.
 * Products keep seeing exactly the tabs they saw before: duplicates are hidden and
 * replacements follow the copy that applies to their product.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Tab_Migration {

    private const OPTION   = 'jps_cs_tabs_single_owner_migrated';
    private const STATUSES = [ 'publish', 'draft', 'pending', 'future', 'private' ];
    private const OWNER_META = [
        'categories' => [ 'key' => '_jps_cs_categories', 'type' => 'product_cat' ],
        'products'   => [ 'key' => '_jps_cs_products', 'type' => 'product' ],
    ];

    /**
     * Runs once; a partial failure is not retried.
     */
    public static function maybe_migrate(): void {
        if ( ! add_option( self::OPTION, 'yes' ) ) {
            return;
        }
        $groups = [];
        foreach ( self::tabs() as $tab ) {
            if ( Plugin::canonical_id( $tab->ID, 'jps_cs_tab' ) !== $tab->ID ) {
                continue;
            }
            $scope = Product_Tabs::scope( $tab );
            if ( ! isset( self::OWNER_META[ $scope ] ) ) {
                continue;
            }
            $owner_ids = self::owner_ids( $tab, $scope );
            if ( count( $owner_ids ) > 1 ) {
                $groups[ $tab->ID ] = [ 'scope' => $scope, 'members' => self::split( $tab, $scope, $owner_ids ) ];
            }
        }
        $category_groups = array_filter( $groups, static fn ( array $group ): bool => 'categories' === $group['scope'] );
        foreach ( $category_groups as $original_id => $group ) {
            self::hide_duplicates( $original_id, $group['members'] );
        }
        self::remap_replacements( $category_groups );
    }

    /**
     * @return \WP_Post[]
     */
    private static function tabs(): array {
        return get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => self::STATUSES, 'numberposts' => -1, 'suppress_filters' => true ] );
    }

    /**
     * @return int[] Canonical, unique owner IDs in stored order.
     */
    private static function owner_ids( \WP_Post $tab, string $scope ): array {
        $meta = self::OWNER_META[ $scope ];
        $ids  = array_map( static fn ( int $id ): int => Plugin::canonical_id( $id, $meta['type'] ), array_map( 'absint', (array) get_post_meta( $tab->ID, $meta['key'], true ) ) );
        return array_values( array_unique( array_filter( $ids ) ) );
    }

    private static function set_owner( int $tab_id, string $scope, int $owner_id ): void {
        foreach ( self::OWNER_META as $owner_scope => $meta ) {
            update_post_meta( $tab_id, $meta['key'], $owner_scope === $scope ? [ $owner_id ] : [] );
        }
    }

    /**
     * The original keeps the first owner; every further owner gets a copy.
     *
     * @param int[] $owner_ids
     * @return array<int, array{0: int, 1: int}> Members as [ tab ID, owner ID ].
     */
    private static function split( \WP_Post $tab, string $scope, array $owner_ids ): array {
        self::set_owner( $tab->ID, $scope, $owner_ids[0] );
        $members = [ [ $tab->ID, $owner_ids[0] ] ];
        // Copies must keep the original markup even when no user with unfiltered_html is logged in.
        kses_remove_filters();
        foreach ( array_slice( $owner_ids, 1 ) as $owner_id ) {
            $copy_id = wp_insert_post(
                wp_slash(
                    [
                        'post_type'    => 'jps_cs_tab',
                        'post_title'   => $tab->post_title,
                        'post_content' => $tab->post_content,
                        'post_status'  => $tab->post_status,
                        'post_author'  => $tab->post_author,
                    ]
                ),
                true
            );
            if ( is_wp_error( $copy_id ) ) {
                continue;
            }
            foreach ( [ '_jps_cs_scope', '_jps_cs_priority', '_jps_cs_replaces' ] as $key ) {
                update_post_meta( $copy_id, $key, get_post_meta( $tab->ID, $key, true ) );
            }
            self::set_owner( $copy_id, $scope, $owner_id );
            $members[] = [ $copy_id, $owner_id ];
        }
        kses_init();
        self::own_translations( $tab, $scope, $owner_ids[0] );
        return $members;
    }

    private static function own_translations( \WP_Post $tab, string $scope, int $owner_id ): void {
        $trid = apply_filters( 'wpml_element_trid', null, $tab->ID, 'post_jps_cs_tab' );
        if ( ! $trid ) {
            return;
        }
        foreach ( (array) apply_filters( 'wpml_get_element_translations', null, $trid, 'post_jps_cs_tab' ) as $translation ) {
            $translation_id = (int) ( $translation->element_id ?? 0 );
            if ( $translation_id && $translation_id !== $tab->ID ) {
                self::set_owner( $translation_id, $scope, $owner_id );
            }
        }
    }

    /**
     * Tab IDs of the group's members whose category applies to the product, in group order.
     *
     * @param array<int, array{0: int, 1: int}> $members
     * @return int[]
     */
    private static function applying_members( array $members, int $product_id ): array {
        $expanded = Product_Tabs::expanded_category_ids( $product_id );
        $applying = [];
        foreach ( $members as [ $tab_id, $category_id ] ) {
            if ( in_array( Plugin::canonical_id( $category_id, 'product_cat' ), $expanded, true ) ) {
                $applying[] = $tab_id;
            }
        }
        return $applying;
    }

    /**
     * A product that saw the tab once keeps seeing one copy; a product that hid it hides all copies.
     *
     * @param array<int, array{0: int, 1: int}> $members
     */
    private static function hide_duplicates( int $original_id, array $members ): void {
        $product_ids = get_posts(
            [
                'post_type'        => 'product',
                'post_status'      => 'any',
                'numberposts'      => -1,
                'fields'           => 'ids',
                'suppress_filters' => true,
                'tax_query'        => [ [ 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => array_column( $members, 1 ), 'include_children' => true ] ], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- One-time migration.
            ]
        );
        $seen = [];
        foreach ( $product_ids as $product_id ) {
            $canonical_product = Plugin::canonical_id( (int) $product_id, 'product' );
            if ( isset( $seen[ $canonical_product ] ) ) {
                continue;
            }
            $seen[ $canonical_product ] = true;
            $applying = self::applying_members( $members, (int) $product_id );
            if ( [] === $applying ) {
                continue;
            }
            $hidden = array_map( static fn ( int $id ): int => Plugin::canonical_id( $id, 'jps_cs_tab' ), array_map( 'absint', (array) get_post_meta( $canonical_product, '_jps_cs_hidden_tabs', true ) ) );
            if ( in_array( $original_id, $hidden, true ) ) {
                $hidden = array_merge( $hidden, $applying );
            } elseif ( count( $applying ) >= 2 ) {
                $hidden = array_merge( $hidden, array_slice( $applying, 1 ) );
            }
            update_post_meta( $canonical_product, '_jps_cs_hidden_tabs', array_values( array_unique( array_filter( $hidden ) ) ) );
        }
    }

    /**
     * A product tab that replaced a split category tab now replaces the first copy that applies to its product.
     *
     * @param array<int, array{scope: string, members: array<int, array{0: int, 1: int}>}> $category_groups
     */
    private static function remap_replacements( array $category_groups ): void {
        if ( [] === $category_groups ) {
            return;
        }
        foreach ( self::tabs() as $tab ) {
            if ( 'products' !== Product_Tabs::scope( $tab ) ) {
                continue;
            }
            $replaced_id = Plugin::canonical_id( absint( get_post_meta( $tab->ID, '_jps_cs_replaces', true ) ), 'jps_cs_tab' );
            $product_id  = self::owner_ids( $tab, 'products' )[0] ?? 0;
            if ( ! $product_id || ! isset( $category_groups[ $replaced_id ] ) ) {
                continue;
            }
            $applying = self::applying_members( $category_groups[ $replaced_id ]['members'], $product_id );
            if ( [] !== $applying ) {
                update_post_meta( $tab->ID, '_jps_cs_replaces', $applying[0] );
            }
        }
    }
}
