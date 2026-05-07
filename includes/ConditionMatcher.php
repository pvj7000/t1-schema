<?php

namespace T1Schema;

/**
 * Evaluates schema rule conditions against the current page context.
 *
 * @package T1Schema
 * @since   1.2.0
 */
class ConditionMatcher {

    /**
     * Check if a rule's conditions match the given context.
     *
     * All conditions in a rule must match (AND logic).
     *
     * @param array $conditions Array of condition objects from the rule.
     * @param array $context    Context array from ContextDetector::detect().
     * @return bool
     */
    public static function matches( array $conditions, array $context ): bool {
        if ( empty( $conditions ) ) {
            return false; // Rules without conditions don't fire (use globals for that).
        }

        foreach ( $conditions as $condition ) {
            if ( ! self::evaluate_single( $condition, $context ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition against the context.
     *
     * @param array $condition Single condition: { type, value? }
     * @param array $context   Current page context.
     * @return bool
     */
    private static function evaluate_single( array $condition, array $context ): bool {
        $type  = $condition['type'] ?? '';
        $value = $condition['value'] ?? '';

        switch ( $type ) {
            // --- Singular content ---
            case 'singular':
                return $context['type'] === 'singular'
                    && ( empty( $value ) || $context['post_type'] === $value );

            // --- Archives ---
            case 'archive':
                if ( $value === 'blog' || $value === '' ) {
                    return $context['type'] === 'archive' && $context['subtype'] === 'blog';
                }
                return $context['type'] === 'archive' && $context['post_type'] === $value;

            // --- Taxonomies ---
            case 'taxonomy':
                return $context['type'] === 'taxonomy'
                    && ( empty( $value ) || $context['taxonomy'] === $value );

            case 'taxonomy_term':
                // value format: "taxonomy:term_slug"
                $parts = explode( ':', $value, 2 );
                if ( count( $parts ) !== 2 ) {
                    return false;
                }
                return $context['type'] === 'taxonomy'
                    && $context['taxonomy'] === $parts[0]
                    && $context['term'] === $parts[1];

            // --- Special pages ---
            case 'front_page':
                return $context['type'] === 'front_page';

            case 'search':
                return $context['type'] === 'search';

            case '404':
                return $context['type'] === '404';

            case 'date':
                return $context['type'] === 'date';

            case 'blog':
                return $context['type'] === 'archive' && $context['subtype'] === 'blog';

            // --- Authors ---
            case 'author':
                return $context['type'] === 'author';

            case 'author_specific':
                return $context['type'] === 'author'
                    && $context['author'] === $value;

            default:
                /**
                 * Filter for custom condition evaluation.
                 *
                 * @since 1.2.0
                 *
                 * @param bool  $match     Default: false.
                 * @param array $condition The condition to evaluate.
                 * @param array $context   The current page context.
                 */
                return (bool) apply_filters( 't1schema_condition_match', false, $condition, $context );
        }
    }
}
