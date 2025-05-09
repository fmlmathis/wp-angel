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
 * Désactiver Gutenberg pour certaines pages et CPT
 */
function disable_gutenberg_for_cpt($can_edit, $post) {
    if (!is_admin() || !$post) {
        return $can_edit;
    }

    $post_type = get_post_type($post);
    $disabled_post_types = ['page', 'business-plan', 'creer-son-entreprise', 'piloter-son-entreprise'];

    if (in_array($post_type, $disabled_post_types)) {
        return false;
    }

    return $can_edit;
}
add_filter('use_block_editor_for_post', 'disable_gutenberg_for_cpt', 10, 2);

/**
 * Supprimer complètement l’éditeur (classique et Gutenberg)
 */
add_action('init', function () {
    $disabled_post_types = ['page', 'business-plan', 'creer-son-entreprise', 'piloter-son-entreprise'];

    foreach ($disabled_post_types as $post_type) {
        remove_post_type_support($post_type, 'editor');
    }
});
