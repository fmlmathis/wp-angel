<?php
// Charger les styles du parent
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style(
    'twentytwentytwo-child-style',
    get_stylesheet_uri(),
    ['twentytwentytwo-style'],
    wp_get_theme()->get('Version')
  );
});

// Enregistrer le bloc ACF
add_action('acf/init', function () {
  if (function_exists('acf_register_block_type')) {
    acf_register_block_type([
      'name' => 'mon-bloc',
      'title' => __('Mon Bloc', 'twentytwentytwo-child'),
      'description' => __('Un bloc ACF custom.'),
      'render_template' => get_stylesheet_directory() . '/blocks/mon-bloc.php',
      'category' => 'formatting',
      'icon' => 'admin-comments',
      'keywords' => ['custom', 'acf'],
    ]);
  }
});

/**
 * Désactiver l'éditeur Gutenberg pour certains Custom Post Types
 */
function disable_gutenberg_for_cpt($can_edit, $post) {
    if (!is_admin() || !$post) {
        return $can_edit;
    }

    // Liste des slugs de CPT pour lesquels désactiver Gutenberg
    $disabled_cpts = ['business-plan', 'creer-son-entreprise', 'piloter-son-entreprise']; // Remplacer avec vos slugs de CPT

    if (in_array(get_post_type($post), $disabled_cpts)) {
        return false;
    }

    return $can_edit;
}
add_filter('use_block_editor_for_post', 'disable_gutenberg_for_cpt', 10, 2);

/**
 * Désactiver complètement l'éditeur visuel (WYSIWYG) pour certains CPT
 */
function disable_wysiwyg_editor_for_cpt() {
    global $post;
    
    if (!$post) {
        return;
    }
    
    // Liste des CPT pour lesquels désactiver l'éditeur visuel
    $disabled_cpts = ['business-plan', 'creer-son-entreprise', 'piloter-son-entreprise'];
    
    if (in_array(get_post_type($post), $disabled_cpts)) {
        // Désactiver l'éditeur visuel complètement
        add_filter('user_can_richedit', '__return_false');
        
        // Masquer les onglets de l'éditeur (Visuel/Texte)
        add_action('admin_head', function() {
            echo '<style>
                .wp-editor-tabs { display: none !important; }
                #wp-content-editor-tools { background-color: #f0f0f1; border: none; }
            </style>';
        });
    }
}
add_action('admin_init', 'disable_wysiwyg_editor_for_cpt');
