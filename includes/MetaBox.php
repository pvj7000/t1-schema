<?php

namespace T1Schema;

/**
 * Post editor meta box for t1 Schema.
 *
 * Displays local schema status and a compact editor directly
 * in the WordPress post/page edit screen.
 *
 * @package T1Schema
 * @since   1.1.0
 */
class MetaBox {

    public function init(): void {
        add_action( 'add_meta_boxes', [ $this, 'register' ] );
        add_action( 'save_post', [ $this, 'save' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
    }

    /**
     * Register the meta box for all public post types.
     */
    public function register(): void {
        $cap = apply_filters( 't1schema_required_capability', 'manage_options' );
        if ( ! current_user_can( $cap ) ) {
            return;
        }

        $post_types = get_post_types( [ 'public' => true ], 'names' );

        foreach ( $post_types as $post_type ) {
            if ( $post_type === 'attachment' ) {
                continue;
            }

            add_meta_box(
                't1schema-local-schemas',
                __( '🔮 t1 Schema — Local Schemas', 't1-schema' ),
                [ $this, 'render' ],
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render the meta box content.
     *
     * @param \WP_Post $post Current post.
     */
    public function render( \WP_Post $post ): void {
        $raw     = get_post_meta( $post->ID, '_t1schema_local', true );
        $schemas = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];
        $schemas = is_array( $schemas ) ? $schemas : [];

        wp_nonce_field( 't1schema_meta_box', 't1schema_meta_box_nonce' );

        // Get available types.
        $registry   = new SchemaRegistry();
        $type_names = $registry->get_type_names();
        sort( $type_names );

        $admin_url = admin_url( 'admin.php?page=t1-schema' );

        echo '<div class="t1schema-metabox">';

        // Current schemas.
        if ( ! empty( $schemas ) ) {
            echo '<div class="t1schema-metabox__list">';
            foreach ( $schemas as $i => $schema ) {
                $type       = $schema['@type'] ?? 'Unknown';
                $status     = $schema['_t1schema_meta']['status'] ?? 'active';
                $override   = $schema['_t1schema_meta']['override_global'] ?? true;
                $health     = SchemaValidator::validate( $schema );
                $error_cnt  = count( $health['errors'] ?? [] );
                $warn_cnt   = count( $health['warnings'] ?? [] );
                $info_cnt   = count( $health['infos'] ?? [] );

                $status_class = $health['valid']
                    ? ( $warn_cnt > 0 ? 't1schema-status--warning' : ( $info_cnt > 0 ? 't1schema-status--info' : 't1schema-status--valid' ) )
                    : 't1schema-status--error';

                $status_label = $health['valid']
                    ? ( $warn_cnt > 0 ? "{$warn_cnt} warning" . ( $warn_cnt > 1 ? 's' : '' ) : ( $info_cnt > 0 ? 'Valid (Custom)' : 'Valid' ) )
                    : "{$error_cnt} error" . ( $error_cnt > 1 ? 's' : '' );

                echo '<div class="t1schema-metabox__item">';
                echo '<div class="t1schema-metabox__item-header">';
                echo '<span class="t1schema-metabox__type">' . esc_html( $type ) . '</span>';
                echo '<span class="t1schema-metabox__badge ' . esc_attr( $status_class ) . '">' . esc_html( $status_label ) . '</span>';
                echo '</div>';

                // Show error/warning/info details.
                if ( ! empty( $health['errors'] ) || ! empty( $health['warnings'] ) || ! empty( $health['infos'] ) ) {
                    echo '<div class="t1schema-metabox__issues">';
                    foreach ( $health['errors'] as $err ) {
                        echo '<div class="t1schema-metabox__issue t1schema-metabox__issue--error">✗ ' . esc_html( $err ) . '</div>';
                    }
                    foreach ( array_slice( $health['warnings'], 0, 3 ) as $warn ) {
                        echo '<div class="t1schema-metabox__issue t1schema-metabox__issue--warning">⚠ ' . esc_html( $warn ) . '</div>';
                    }
                    if ( $warn_cnt > 3 ) {
                        echo '<div class="t1schema-metabox__issue t1schema-metabox__issue--warning">… and ' . esc_html( $warn_cnt - 3 ) . ' more</div>';
                    }
                    foreach ( array_slice( $health['infos'], 0, 3 ) as $info ) {
                        echo '<div class="t1schema-metabox__issue t1schema-metabox__issue--info">ℹ ' . esc_html( $info ) . '</div>';
                    }
                    echo '</div>';
                }

                echo '<div class="t1schema-metabox__item-meta">';
                echo $override ? '<span class="t1schema-metabox__flag">' . esc_html__( '↑ Overrides global', 't1-schema' ) . '</span>' : '<span class="t1schema-metabox__flag">' . esc_html__( '∥ Coexists with global', 't1-schema' ) . '</span>';
                echo '<button type="button" class="t1schema-metabox__remove" data-index="' . esc_attr( $i ) . '" title="Remove this schema">×</button>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p class="t1schema-metabox__empty">' . esc_html__( 'No local schemas on this page.', 't1-schema' ) . '<br>' . esc_html__( 'Global schemas still apply.', 't1-schema' ) . '</p>';
        }

        // Quick-add form.
        echo '<div class="t1schema-metabox__add">';
        echo '<label class="t1schema-metabox__add-label">' . esc_html__( 'Quick Add Schema', 't1-schema' ) . '</label>';
        echo '<div class="t1schema-metabox__add-row">';
        echo '<select name="t1schema_quick_add_type" class="t1schema-metabox__select">';
        echo '<option value="">' . esc_html__( 'Select type…', 't1-schema' ) . '</option>';
        foreach ( $type_names as $type ) {
            echo '<option value="' . esc_attr( $type ) . '">' . esc_html( $type ) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</div>';

        // Link to full editor.
        echo '<div class="t1schema-metabox__footer">';
        echo '<a href="' . esc_url( $admin_url ) . '" class="t1schema-metabox__link" target="_blank">';
        echo esc_html__( 'Open Full Editor →', 't1-schema' );
        echo '</a>';
        echo '</div>';

        // Hidden field to track removals.
        echo '<input type="hidden" name="t1schema_remove_indices" id="t1schema_remove_indices" value="">';

        echo '</div>';

        // Inline JS for remove buttons.
        echo '<script>
        document.querySelectorAll(".t1schema-metabox__remove").forEach(function(btn) {
            btn.addEventListener("click", function() {
                var idx = this.getAttribute("data-index");
                var field = document.getElementById("t1schema_remove_indices");
                var current = field.value ? field.value.split(",") : [];
                current.push(idx);
                field.value = current.join(",");
                this.closest(".t1schema-metabox__item").style.opacity = "0.3";
                this.closest(".t1schema-metabox__item").style.textDecoration = "line-through";
                this.disabled = true;
            });
        });
        </script>';
    }

    /**
     * Save meta box data on post save.
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     */
    public function save( int $post_id, \WP_Post $post ): void {
        // Verify nonce.
        if ( ! isset( $_POST['t1schema_meta_box_nonce'] ) ||
             ! wp_verify_nonce( $_POST['t1schema_meta_box_nonce'], 't1schema_meta_box' ) ) {
            return;
        }

        // Check permissions.
        $cap = apply_filters( 't1schema_required_capability', 'manage_options' );
        if ( ! current_user_can( $cap ) ) {
            return;
        }

        // Skip autosaves.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Handle removals.
        $remove_indices = sanitize_text_field( $_POST['t1schema_remove_indices'] ?? '' );
        if ( $remove_indices ) {
            $indices = array_map( 'intval', explode( ',', $remove_indices ) );
            $raw     = get_post_meta( $post_id, '_t1schema_local', true );
            $schemas = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];
            $schemas = is_array( $schemas ) ? $schemas : [];

            foreach ( $indices as $idx ) {
                unset( $schemas[ $idx ] );
            }
            $schemas = array_values( $schemas );

            if ( empty( $schemas ) ) {
                delete_post_meta( $post_id, '_t1schema_local' );
            } else {
                update_post_meta( $post_id, '_t1schema_local', wp_json_encode( $schemas ) );
            }
        }

        // Handle quick-add.
        $quick_add_type = sanitize_text_field( $_POST['t1schema_quick_add_type'] ?? '' );
        if ( $quick_add_type ) {
            $raw     = get_post_meta( $post_id, '_t1schema_local', true );
            $schemas = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];
            $schemas = is_array( $schemas ) ? $schemas : [];

            $new_schema = [
                '@context'        => 'https://schema.org',
                '@type'           => $quick_add_type,
                '_t1schema_meta' => [
                    'override_global' => true,
                    'status'          => 'active',
                ],
            ];

            // Pre-populate common variables based on type.
            $auto_map = [
                'Article'     => [ 'headline' => '{{post_title}}', 'datePublished' => '{{post_date}}', 'dateModified' => '{{post_modified}}', 'image' => '{{featured_image_url}}' ],
                'BlogPosting' => [ 'headline' => '{{post_title}}', 'datePublished' => '{{post_date}}', 'dateModified' => '{{post_modified}}', 'image' => '{{featured_image_url}}' ],
                'WebPage'     => [ 'name' => '{{post_title}}', 'url' => '{{post_url}}', 'datePublished' => '{{post_date}}', 'dateModified' => '{{post_modified}}' ],
                'Product'     => [ 'name' => '{{post_title}}', 'image' => '{{featured_image_url}}' ],
            ];

            if ( isset( $auto_map[ $quick_add_type ] ) ) {
                $new_schema = array_merge( $new_schema, $auto_map[ $quick_add_type ] );
            }

            $schemas[] = $new_schema;
            update_post_meta( $post_id, '_t1schema_local', wp_json_encode( $schemas ) );
        }
    }

    /**
     * Enqueue meta box styles.
     *
     * @param string $hook_suffix Current admin page.
     */
    public function enqueue_styles( string $hook_suffix ): void {
        if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }

        wp_add_inline_style( 'wp-admin', $this->get_inline_css() );
    }

    /**
     * Get meta box CSS.
     *
     * @return string CSS string.
     */
    private function get_inline_css(): string {
        return '
        .t1schema-metabox { font-size: 12px; }
        .t1schema-metabox__list { margin-bottom: 12px; }
        .t1schema-metabox__item {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 6px;
            background: #fafbfc;
            transition: opacity 0.2s;
        }
        .t1schema-metabox__item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        .t1schema-metabox__type {
            font-weight: 600;
            color: #1e293b;
        }
        .t1schema-metabox__badge {
            font-size: 10px;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 10px;
        }
        .t1schema-status--valid { background: #dcfce7; color: #166534; }
        .t1schema-status--warning { background: #fef3c7; color: #92400e; }
        .t1schema-status--error { background: #fee2e2; color: #991b1b; }
        .t1schema-status--info { background: #dbeafe; color: #1e40af; }
        .t1schema-metabox__issues {
            margin: 4px 0;
            font-size: 11px;
        }
        .t1schema-metabox__issue {
            padding: 2px 0;
            line-height: 1.3;
        }
        .t1schema-metabox__issue--error { color: #dc2626; }
        .t1schema-metabox__issue--warning { color: #d97706; }
        .t1schema-metabox__issue--info { color: #2563eb; }
        .t1schema-metabox__item-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
        }
        .t1schema-metabox__flag {
            font-size: 10px;
            color: #94a3b8;
        }
        .t1schema-metabox__remove {
            background: none;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            color: #94a3b8;
            cursor: pointer;
            font-size: 14px;
            line-height: 1;
            padding: 1px 5px;
            transition: all 0.15s;
        }
        .t1schema-metabox__remove:hover { border-color: #fca5a5; color: #ef4444; background: #fef2f2; }
        .t1schema-metabox__empty {
            text-align: center;
            color: #94a3b8;
            font-style: italic;
            padding: 12px 0;
            margin: 0;
            line-height: 1.5;
        }
        .t1schema-metabox__add {
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            margin-top: 8px;
        }
        .t1schema-metabox__add-label {
            display: block;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .t1schema-metabox__add-row { display: flex; gap: 6px; }
        .t1schema-metabox__select {
            flex: 1;
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 12px;
            background: white;
        }
        .t1schema-metabox__footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            margin-top: 10px;
            text-align: center;
        }
        .t1schema-metabox__link {
            color: #4263eb;
            text-decoration: none;
            font-weight: 500;
            font-size: 12px;
        }
        .t1schema-metabox__link:hover { text-decoration: underline; }
        ';
    }
}
