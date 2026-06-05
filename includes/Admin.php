<?php

namespace T1Schema;

/**
 * Admin panel handler.
 *
 * Registers the admin menu page and enqueues the React app assets.
 *
 * @package T1Schema
 * @since   1.0.0
 */
class Admin {

    public function init(): void {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Register the top-level admin menu page.
     */
    public function register_menu(): void {
        add_menu_page(
            __( 't1 Schema', 't1-schema' ),
            __( 't1 Schema', 't1-schema' ),
            apply_filters( 't1schema_required_capability', 'manage_options' ),
            't1-schema',
            [ $this, 'render_page' ],
            'dashicons-code-standards',
            81
        );
    }

    /**
     * Render the admin page container.
     * The React app mounts to #t1schema-root.
     */
    public function render_page(): void {
        echo '<div id="t1schema-root"></div>';

        // Diagnostic output — visible when React fails to mount.
        // Remove this block after debugging.
        if ( isset( $_GET['t1debug'] ) ) { // phpcs:ignore
            $manifest_path = T1SCHEMA_PATH . 'assets/.vite/manifest.json';
            $manifest_exists = file_exists( $manifest_path );
            $manifest_data = $manifest_exists ? json_decode( file_get_contents( $manifest_path ), true ) : null; // phpcs:ignore
            $js_file = $manifest_data['src/main.jsx']['file'] ?? '(not found)';
            $js_path = T1SCHEMA_PATH . 'assets/' . $js_file;
            $css_files = $manifest_data['src/main.jsx']['css'] ?? [];

            echo '<div style="margin:20px 0;padding:20px;background:#1e1e2e;color:#cdd6f4;border-radius:8px;font-family:monospace;font-size:13px;line-height:1.8;">';
            echo '<h3 style="color:#f38ba8;margin:0 0 12px;">🔮 t1 Schema — Diagnostic Report</h3>';
            echo '<div style="color:#a6adc8;">PHP: ' . esc_html( phpversion() ) . ' | WP: ' . esc_html( get_bloginfo( 'version' ) ) . '</div>';
            echo '<hr style="border-color:#45475a;margin:10px 0;">';

            // Constants
            echo '<div><span style="color:#89b4fa;">T1SCHEMA_PATH:</span> ' . esc_html( T1SCHEMA_PATH ) . '</div>';
            echo '<div><span style="color:#89b4fa;">T1SCHEMA_URL:</span> ' . esc_html( T1SCHEMA_URL ) . '</div>';
            echo '<div><span style="color:#89b4fa;">T1SCHEMA_VERSION:</span> ' . esc_html( T1SCHEMA_VERSION ) . '</div>';
            echo '<hr style="border-color:#45475a;margin:10px 0;">';

            // Manifest
            echo '<div><span style="color:#89b4fa;">Manifest path:</span> ' . esc_html( $manifest_path ) . '</div>';
            echo '<div><span style="color:#89b4fa;">Manifest exists:</span> ' . ( $manifest_exists ? '<span style="color:#a6e3a1;">YES ✓</span>' : '<span style="color:#f38ba8;">NO ✗</span>' ) . '</div>';

            if ( $manifest_exists ) {
                echo '<div><span style="color:#89b4fa;">JS file in manifest:</span> ' . esc_html( $js_file ) . '</div>';
                echo '<div><span style="color:#89b4fa;">JS file on disk:</span> ' . ( file_exists( $js_path ) ? '<span style="color:#a6e3a1;">EXISTS ✓</span>' : '<span style="color:#f38ba8;">MISSING ✗ (' . esc_html( $js_path ) . ')</span>' ) . '</div>';
                echo '<div><span style="color:#89b4fa;">JS URL:</span> <a href="' . esc_url( T1SCHEMA_URL . 'assets/' . $js_file ) . '" target="_blank" style="color:#89dceb;">' . esc_html( T1SCHEMA_URL . 'assets/' . $js_file ) . '</a></div>';

                foreach ( $css_files as $css ) {
                    $css_path = T1SCHEMA_PATH . 'assets/' . $css;
                    echo '<div><span style="color:#89b4fa;">CSS file:</span> ' . esc_html( $css ) . ' — ' . ( file_exists( $css_path ) ? '<span style="color:#a6e3a1;">EXISTS ✓</span>' : '<span style="color:#f38ba8;">MISSING ✗</span>' ) . '</div>';
                }
            }
            echo '<hr style="border-color:#45475a;margin:10px 0;">';

            // SCRIPT_DEBUG
            echo '<div><span style="color:#89b4fa;">SCRIPT_DEBUG:</span> ' . ( defined( 'SCRIPT_DEBUG' ) ? ( SCRIPT_DEBUG ? 'true' : 'false' ) : 'not defined' ) . '</div>';

            // Assets directory listing
            $assets_dir = T1SCHEMA_PATH . 'assets/';
            echo '<div><span style="color:#89b4fa;">Assets dir contents:</span></div>';
            if ( is_dir( $assets_dir ) ) {
                $files = scandir( $assets_dir ); // phpcs:ignore
                foreach ( $files as $f ) {
                    if ( $f === '.' || $f === '..' ) continue;
                    echo '<div style="padding-left:16px;color:#bac2de;">  ' . esc_html( $f ) . ( is_dir( $assets_dir . $f ) ? '/' : ' (' . size_format( filesize( $assets_dir . $f ) ) . ')' ) . '</div>';
                }
            } else {
                echo '<div style="color:#f38ba8;">  assets/ directory does not exist!</div>';
            }
            echo '<hr style="border-color:#45475a;margin:10px 0;">';

            // REST API
            echo '<div><span style="color:#89b4fa;">REST URL:</span> <a href="' . esc_url( rest_url( 't1-schema/v1/types' ) ) . '" target="_blank" style="color:#89dceb;">' . esc_html( rest_url( 't1-schema/v1/types' ) ) . '</a></div>';

            // DB tables
            global $wpdb;
            $g_table = $wpdb->prefix . 't1schema_globals';
            $r_table = $wpdb->prefix . 't1schema_rules';
            $g_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $g_table ) ); // phpcs:ignore
            $r_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $r_table ) ); // phpcs:ignore
            echo '<div><span style="color:#89b4fa;">DB table globals:</span> ' . ( $g_exists ? '<span style="color:#a6e3a1;">EXISTS ✓</span>' : '<span style="color:#f38ba8;">MISSING ✗</span>' ) . '</div>';
            echo '<div><span style="color:#89b4fa;">DB table rules:</span> ' . ( $r_exists ? '<span style="color:#a6e3a1;">EXISTS ✓</span>' : '<span style="color:#f38ba8;">MISSING ✗</span>' ) . '</div>';

            echo '<hr style="border-color:#45475a;margin:10px 0;">';
            echo '<div style="color:#a6adc8;">Add <strong>?t1debug</strong> to the URL to show this panel. Check the browser console (F12) for JavaScript errors.</div>';
            echo '</div>';
        }
    }

    /**
     * Enqueue React app assets only on the t1 Schema page.
     *
     * @param string $hook_suffix Current admin page hook.
     */
    public function enqueue_assets( string $hook_suffix ): void {
        if ( $hook_suffix !== 'toplevel_page_t1-schema' ) {
            return;
        }

        $manifest_path = T1SCHEMA_PATH . 'assets/.vite/manifest.json';
        $asset_url     = T1SCHEMA_URL . 'assets/';

        // Production: load from manifest.
        if ( file_exists( $manifest_path ) ) {
            $manifest = json_decode( file_get_contents( $manifest_path ), true ); // phpcs:ignore

            $js_file  = $manifest['src/main.jsx']['file'] ?? 'app.js';
            $css_files = $manifest['src/main.jsx']['css'] ?? [];

            wp_enqueue_script(
                't1schema-app',
                $asset_url . $js_file,
                [],
                T1SCHEMA_VERSION,
                true
            );

            foreach ( $css_files as $css_file ) {
                wp_enqueue_style(
                    't1schema-app-' . basename( $css_file, '.css' ),
                    $asset_url . $css_file,
                    [],
                    T1SCHEMA_VERSION
                );
            }
        } elseif ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
            // Development only: load from Vite dev server.
            // This block is excluded from production builds and only runs
            // when SCRIPT_DEBUG is explicitly enabled in wp-config.php.
            $dev_server = 'http://localhost:5173';

            wp_enqueue_script(
                't1schema-vite-client',
                $dev_server . '/@vite/client',
                [],
                null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
                false
            );

            wp_enqueue_script(
                't1schema-app',
                $dev_server . '/src/main.jsx',
                [],
                null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
                true
            );
        }

        // Vite outputs ES modules — add type="module" to all t1schema scripts.
        add_filter( 'script_loader_tag', function ( string $tag, string $handle ) {
            if ( in_array( $handle, [ 't1schema-vite-client', 't1schema-app' ], true ) ) {
                return str_replace( ' src=', ' type="module" src=', $tag );
            }
            return $tag;
        }, 10, 2 );

        // Localize config for the React app.
        wp_localize_script( 't1schema-app', 't1SchemaConfig', [
            'restUrl'   => rest_url( 't1-schema/v1/' ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'adminUrl'  => admin_url(),
            'pluginUrl' => T1SCHEMA_URL,
            'version'   => T1SCHEMA_VERSION,
            'siteName'  => get_bloginfo( 'name' ),
            'siteUrl'   => home_url( '/' ),
        ] );
    }
}
