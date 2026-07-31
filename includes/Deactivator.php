<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles plugin deactivation.
 *
 * Cleans up transients but preserves all schema data.
 *
 * @package T1Schema
 * @since   1.0.0
 */
class Deactivator {

    /**
     * Run deactivation routines.
     *
     * @return void
     */
    public function deactivate(): void {
        $this->clear_transients();
        flush_rewrite_rules();
    }

    /**
     * Clear all t1 Schema transients.
     *
     * @return void
     */
    private function clear_transients(): void {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_t1schema_%'
                OR option_name LIKE '_transient_timeout_t1schema_%'"
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
    }
}
