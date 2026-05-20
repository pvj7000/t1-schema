<?php

namespace T1Schema;

/**
 * Resolves dynamic variable tags in schema data.
 *
 * Converts {{post_title}}, {{site_url}} etc. into actual values
 * at render time for the current page context.
 *
 * @package T1Schema
 * @since   1.0.0
 */
class VariableResolver {

    /**
     * Available variable definitions grouped by category.
     *
     * @return array<string, array<string, string>> Category => [tag => description].
     */
    public static function get_available_variables(): array {
        return [
            'post' => [
                'post_title'          => 'Post/Page title',
                'post_excerpt'        => 'Post excerpt',
                'post_content'        => 'Full post content (plain text)',
                'post_date'           => 'Publish date (ISO 8601)',
                'post_modified'       => 'Last modified date (ISO 8601)',
                'post_url'            => 'Post permalink',
                'post_id'             => 'Post ID',
                'post_slug'           => 'Post slug',
                'post_type'           => 'Post type',
                'featured_image_url'  => 'Featured image URL (full size)',
                'featured_image_alt'  => 'Featured image alt text',
            ],
            'author' => [
                'author_name'         => 'Author display name',
                'author_url'          => 'Author posts URL',
                'author_description'  => 'Author bio/description',
                'author_avatar_url'   => 'Author avatar URL',
            ],
            'site' => [
                'site_name'           => 'Site title',
                'site_url'            => 'Site home URL',
                'site_description'    => 'Site tagline',
                'site_logo'           => 'Custom logo URL',
                'site_language'       => 'Site language code',
            ],
            'taxonomy' => [
                'primary_category'       => 'Primary category name',
                'primary_category_url'   => 'Primary category URL',
                'categories'             => 'Comma-separated category names',
                'tags'                   => 'Comma-separated tag names',
            ],
            'archive' => [
                'term_name'              => 'Current taxonomy term name (on archive)',
                'term_description'       => 'Current taxonomy term description',
                'term_url'               => 'Current taxonomy term URL',
                'archive_title'          => 'Archive page title',
                'archive_url'            => 'Current archive URL',
                'search_query'           => 'Current search query',
            ],
            'meta' => [
                'meta:{key}'          => 'Custom post meta value (replace {key} with meta key)',
            ],
            'custom' => self::get_custom_variable_catalog(),
        ];
    }

    /**
     * Get user-defined custom variables for the catalog.
     */
    private static function get_custom_variable_catalog(): array {
        $vars = get_option( 't1schema_custom_variables', [] );
        if ( ! is_array( $vars ) || empty( $vars ) ) {
            return [
                'custom.{key}' => 'User-defined site constant (create in Dashboard → Settings)',
            ];
        }

        $catalog = [];
        foreach ( $vars as $key => $value ) {
            $preview = mb_strlen( $value ) > 30 ? mb_substr( $value, 0, 30 ) . '…' : $value;
            $catalog[ "custom.{$key}" ] = "Site constant: {$preview}";
        }
        return $catalog;
    }

    /**
     * Resolve all {{variable}} tags in a schema data string or array.
     *
     * @param mixed    $data    Schema data (string, array, or nested).
     * @param int|null $post_id Post ID context. Null for global schemas.
     * @return mixed   Resolved data with variables replaced.
     */
    public static function resolve( mixed $data, ?int $post_id = null ): mixed {
        if ( is_string( $data ) ) {
            return self::resolve_string( $data, $post_id );
        }

        if ( is_array( $data ) ) {
            $resolved = [];
            foreach ( $data as $key => $value ) {
                $resolved[ $key ] = self::resolve( $value, $post_id );
            }
            return $resolved;
        }

        return $data;
    }

    /**
     * Resolve variable tags in a single string.
     *
     * @param string   $text    String potentially containing {{tags}}.
     * @param int|null $post_id Post ID context.
     * @return string  Resolved string.
     */
    private static function resolve_string( string $text, ?int $post_id ): string {
        return preg_replace_callback(
            '/\{\{([a-z_:.]+(?:\{[^}]*\})?)\}\}/',
            function ( array $matches ) use ( $post_id ) {
                return self::get_variable_value( $matches[1], $post_id );
            },
            $text
        ) ?? $text;
    }

    /**
     * Get the value for a specific variable tag.
     *
     * @param string   $tag     Variable tag name (without braces).
     * @param int|null $post_id Post ID context.
     * @return string  Resolved value or empty string.
     */
    private static function get_variable_value( string $tag, ?int $post_id ): string {
        $post = $post_id ? get_post( $post_id ) : null;

        // Handle meta:{key} pattern.
        if ( str_starts_with( $tag, 'meta:' ) ) {
            $meta_key = substr( $tag, 5 );
            if ( $post_id && $meta_key ) {
                return (string) get_post_meta( $post_id, $meta_key, true );
            }
            return '';
        }

        // Handle custom.{key} pattern — user-defined site constants.
        if ( str_starts_with( $tag, 'custom.' ) ) {
            $var_key = substr( $tag, 7 );
            $custom  = get_option( 't1schema_custom_variables', [] );
            return (string) ( $custom[ $var_key ] ?? '' );
        }

        $value = match ( $tag ) {
            // Post variables.
            'post_title'         => $post ? get_the_title( $post ) : '',
            'post_excerpt'       => $post ? wp_strip_all_tags( get_the_excerpt( $post ) ) : '',
            'post_content'       => $post ? wp_strip_all_tags( $post->post_content ) : '',
            'post_date'          => $post ? get_the_date( 'c', $post ) : '',
            'post_modified'      => $post ? get_the_modified_date( 'c', $post ) : '',
            'post_url'           => $post ? get_permalink( $post ) : '',
            'post_id'            => $post ? (string) $post->ID : '',
            'post_slug'          => $post ? $post->post_name : '',
            'post_type'          => $post ? $post->post_type : '',
            'featured_image_url' => $post_id ? (string) get_the_post_thumbnail_url( $post_id, 'full' ) : '',
            'featured_image_alt' => self::get_featured_image_alt( $post_id ),

            // Author variables (custom author profile preferred over WP native).
            'author_name'        => $post ? self::resolve_author_name( $post ) : '',
            'author_url'         => $post ? get_author_posts_url( $post->post_author ) : '',
            'author_description' => $post ? get_the_author_meta( 'description', $post->post_author ) : '',
            'author_avatar_url'  => $post ? self::resolve_author_avatar( $post ) : '',

            // Site variables.
            'site_name'          => get_bloginfo( 'name' ),
            'site_url'           => home_url( '/' ),
            'site_description'   => get_bloginfo( 'description' ),
            'site_logo'          => self::get_site_logo_url(),
            'site_language'      => get_bloginfo( 'language' ),

            // Taxonomy variables.
            'primary_category'     => self::get_primary_category_name( $post_id ),
            'primary_category_url' => self::get_primary_category_url( $post_id ),
            'categories'           => $post_id ? self::get_term_names( $post_id, 'category' ) : '',
            'tags'                 => $post_id ? self::get_term_names( $post_id, 'post_tag' ) : '',

            // Archive / context variables.
            'term_name'            => self::get_current_term_name(),
            'term_description'     => self::get_current_term_description(),
            'term_url'             => self::get_current_term_url(),
            'archive_title'        => self::get_archive_title(),
            'archive_url'          => self::get_archive_url(),
            'search_query'         => get_search_query(),

            default => '',
        };

        /**
         * Filter the resolved value of a dynamic variable.
         *
         * @since 1.0.0
         *
         * @param string   $value   The resolved value.
         * @param string   $tag     The variable tag name.
         * @param int|null $post_id The post ID context.
         */
        return (string) apply_filters( 't1schema_resolve_variable', $value, $tag, $post_id );
    }

    /**
     * Get the featured image alt text.
     *
     * @param int|null $post_id Post ID.
     * @return string  Alt text or empty string.
     */
    private static function get_featured_image_alt( ?int $post_id ): string {
        if ( ! $post_id ) {
            return '';
        }

        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( ! $thumbnail_id ) {
            return '';
        }

        return (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
    }

    /**
     * Get the site custom logo URL.
     *
     * @return string Logo URL or empty string.
     */
    private static function get_site_logo_url(): string {
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        if ( ! $custom_logo_id ) {
            return '';
        }

        $image = wp_get_attachment_image_url( $custom_logo_id, 'full' );
        return $image ? $image : '';
    }

    /**
     * Resolve author name: custom author profile > WP native display_name.
     *
     * Supports teil1_blog_post CPT which stores custom author IDs in
     * _teil1_content_custom_author meta, resolved against the
     * teil1_author_profiles option.
     *
     * @since 1.4.9
     * @param \WP_Post $post Post object.
     * @return string  Author display name.
     */
    private static function resolve_author_name( \WP_Post $post ): string {
        $custom_author_id = get_post_meta( $post->ID, '_teil1_content_custom_author', true );

        if ( $custom_author_id ) {
            $profiles = get_option( 'teil1_author_profiles', [] );
            foreach ( (array) $profiles as $profile ) {
                if ( ( $profile['id'] ?? '' ) === $custom_author_id && ! empty( $profile['name'] ) ) {
                    return $profile['name'];
                }
            }
        }

        return get_the_author_meta( 'display_name', $post->post_author );
    }

    /**
     * Resolve author avatar: custom author profile > Gravatar.
     *
     * @since 1.4.9
     * @param \WP_Post $post Post object.
     * @return string  Avatar URL.
     */
    private static function resolve_author_avatar( \WP_Post $post ): string {
        $custom_author_id = get_post_meta( $post->ID, '_teil1_content_custom_author', true );

        if ( $custom_author_id ) {
            $profiles = get_option( 'teil1_author_profiles', [] );
            foreach ( (array) $profiles as $profile ) {
                if ( ( $profile['id'] ?? '' ) === $custom_author_id && ! empty( $profile['avatar'] ) ) {
                    return $profile['avatar'];
                }
            }
        }

        return (string) get_avatar_url( $post->post_author, [ 'size' => 96 ] );
    }

    /**
     * Get the primary category name for a post.
     *
     * @param int|null $post_id Post ID.
     * @return string  Category name or empty string.
     */
    private static function get_primary_category_name( ?int $post_id ): string {
        if ( ! $post_id ) {
            return '';
        }

        $categories = get_the_category( $post_id );
        return ! empty( $categories ) ? $categories[0]->name : '';
    }

    /**
     * Get the primary category URL for a post.
     *
     * @param int|null $post_id Post ID.
     * @return string  Category URL or empty string.
     */
    private static function get_primary_category_url( ?int $post_id ): string {
        if ( ! $post_id ) {
            return '';
        }

        $categories = get_the_category( $post_id );
        if ( empty( $categories ) ) {
            return '';
        }

        $link = get_category_link( $categories[0]->term_id );
        return $link ? $link : '';
    }

    /**
     * Get comma-separated term names for a taxonomy.
     *
     * @param int    $post_id  Post ID.
     * @param string $taxonomy Taxonomy slug.
     * @return string Comma-separated names.
     */
    private static function get_term_names( int $post_id, string $taxonomy ): string {
        $terms = get_the_terms( $post_id, $taxonomy );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '';
        }

        return implode( ', ', wp_list_pluck( $terms, 'name' ) );
    }

    /**
     * Get the current taxonomy term name (on archive pages).
     */
    private static function get_current_term_name(): string {
        $obj = get_queried_object();
        return ( $obj instanceof \WP_Term ) ? $obj->name : '';
    }

    /**
     * Get the current taxonomy term description.
     */
    private static function get_current_term_description(): string {
        $obj = get_queried_object();
        return ( $obj instanceof \WP_Term ) ? $obj->description : '';
    }

    /**
     * Get the current taxonomy term URL.
     */
    private static function get_current_term_url(): string {
        $obj = get_queried_object();
        if ( ! ( $obj instanceof \WP_Term ) ) {
            return '';
        }
        $link = get_term_link( $obj );
        return is_wp_error( $link ) ? '' : $link;
    }

    /**
     * Get the archive page title.
     */
    private static function get_archive_title(): string {
        if ( is_category() || is_tag() || is_tax() ) {
            $obj = get_queried_object();
            return ( $obj instanceof \WP_Term ) ? $obj->name : '';
        }
        if ( is_post_type_archive() ) {
            return post_type_archive_title( '', false ) ?: '';
        }
        if ( is_author() ) {
            $obj = get_queried_object();
            return ( $obj instanceof \WP_User ) ? $obj->display_name : '';
        }
        return '';
    }

    /**
     * Get the current archive URL.
     */
    private static function get_archive_url(): string {
        if ( is_category() || is_tag() || is_tax() ) {
            return self::get_current_term_url();
        }
        if ( is_post_type_archive() ) {
            $pt = get_query_var( 'post_type' );
            if ( is_array( $pt ) ) {
                $pt = $pt[0] ?? '';
            }
            $link = get_post_type_archive_link( $pt );
            return $link ?: '';
        }
        if ( is_author() ) {
            $obj = get_queried_object();
            return ( $obj instanceof \WP_User ) ? get_author_posts_url( $obj->ID ) : '';
        }
        return home_url( $_SERVER['REQUEST_URI'] ?? '/' );
    }
}
