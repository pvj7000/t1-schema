<?php

namespace T1Schema;

/**
 * Admin Bar integration — shows t1 Schema icon with active schemas on hover.
 *
 * Hooks into Frontend's render pipeline to capture the schemas that were
 * actually assembled, then displays them in the admin bar.
 *
 * @package T1Schema
 * @since   1.2.0
 */
class AdminBar {

    /** @var array Captured schemas from the current page render. */
    private static array $captured_schemas = [];

    public function init(): void {
        // Note: no capability check here — init() fires at plugins_loaded,
        // before WordPress has loaded the current user. Each callback
        // checks current_user_can() individually.

        // Capture schemas from the actual render pipeline.
        add_filter( 't1schema_jsonld_output', [ $this, 'capture_from_render' ], 1, 2 );

        // Render admin bar items.
        add_action( 'admin_bar_menu', [ $this, 'add_menu' ], 100 );
        add_action( 'wp_head', [ $this, 'inline_styles' ], 999 );
    }

    /**
     * Filter hook: captures the schemas that were actually rendered.
     * Passes through without modification.
     */
    public function capture_from_render( string $output, array $schemas ): string {
        foreach ( $schemas as $schema ) {
            $raw_type = $schema['@type'] ?? 'Unknown';
            $type     = is_array( $raw_type ) ? implode( ' + ', $raw_type ) : $raw_type;
            unset( $schema['_t1schema_meta'], $schema['@context'] );

            $props = count( array_filter(
                array_keys( $schema ),
                fn( $k ) => ! str_starts_with( $k, '@' )
            ) );

            self::$captured_schemas[] = [
                'type'  => $type,
                'props' => $props,
            ];
        }
        return $output;
    }

    /**
     * Add t1 Schema node to the admin bar.
     */
    public function add_menu( \WP_Admin_Bar $wp_admin_bar ): void {
        if ( is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $count = count( self::$captured_schemas );
        $label = "🔮 {$count}";
        $color = $count > 0 ? '#10b981' : '#9ca3af';

        $wp_admin_bar->add_node( [
            'id'    => 't1-schema',
            'title' => "<span style='color:{$color};font-weight:600'>{$label}</span>",
            'href'  => admin_url( 'admin.php?page=t1-schema' ),
            'meta'  => [
                'title' => "t1 Schema: {$count} active schema(s) on this page",
            ],
        ] );

        if ( $count === 0 ) {
            $wp_admin_bar->add_node( [
                'parent' => 't1-schema',
                'id'     => 't1schema-empty',
                'title'  => '<span style="color:#9ca3af;font-size:12px">No schemas active on this page</span>',
                'href'   => admin_url( 'admin.php?page=t1-schema' ),
            ] );
            return;
        }

        foreach ( self::$captured_schemas as $i => $schema ) {
            $badge = $this->get_type_color( $schema['type'] );
            $wp_admin_bar->add_node( [
                'parent' => 't1-schema',
                'id'     => "t1schema-schema-{$i}",
                'title'  => "<span style='display:inline-block;width:8px;height:8px;border-radius:50%;background:{$badge};margin-right:6px'></span>"
                          . "<strong>{$schema['type']}</strong>"
                          . "<span style='color:#9ca3af;margin-left:6px;font-size:11px'>{$schema['props']} props</span>",
                'href'   => admin_url( 'admin.php?page=t1-schema' ),
            ] );
        }

        $wp_admin_bar->add_node( [
            'parent' => 't1-schema',
            'id'     => 't1schema-dashboard',
            'title'  => '<span style="color:#6366f1;font-size:12px">→ Open Dashboard</span>',
            'href'   => admin_url( 'admin.php?page=t1-schema' ),
        ] );
    }

    /**
     * Minimal inline styles for the admin bar dropdown.
     */
    public function inline_styles(): void {
        if ( is_admin() || ! current_user_can( 'manage_options' ) || ! is_admin_bar_showing() ) {
            return;
        }
        echo '<style>#wp-admin-bar-t1schema .ab-sub-wrapper{min-width:220px}#wp-admin-bar-t1schema .ab-submenu .ab-item{line-height:1.6!important;height:auto!important;padding:4px 10px!important}</style>' . "\n";
    }

    private function get_type_color( string $type ): string {
        return match ( true ) {
            in_array( $type, [ 'Organization', 'LocalBusiness' ] )   => '#6366f1',
            in_array( $type, [ 'Article', 'BlogPosting' ] )          => '#f59e0b',
            in_array( $type, [ 'Product', 'Offer' ] )                => '#10b981',
            in_array( $type, [ 'FAQPage', 'HowTo' ] )                => '#8b5cf6',
            in_array( $type, [ 'WebSite', 'WebPage' ] )              => '#3b82f6',
            in_array( $type, [ 'BreadcrumbList' ] )                   => '#ec4899',
            default                                                   => '#64748b',
        };
    }
}
