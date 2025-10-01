<?php
// Définition des types de posts personnalisés
global $angel_post_types;
$angel_post_types = ['page', 'business-plan', 'lancement', 'pilotage', 'solution'];

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
    global $angel_post_types;
    
    if (!is_admin() || !$post) {
        return $can_edit;
    }

    $post_type = get_post_type($post);
    
    if (in_array($post_type, $angel_post_types)) {
        return false;
    }

    return $can_edit;
}
add_filter('use_block_editor_for_post', 'disable_gutenberg_for_cpt', 10, 2);

/**
 * Supprimer complètement l’éditeur (classique et Gutenberg)
 */
add_action('init', function () {
    global $angel_post_types;
    
    foreach ($angel_post_types as $post_type) {
        remove_post_type_support($post_type, 'editor');
    }
});

add_action('admin_menu', function () {
    global $angel_post_types;
  
    foreach ($angel_post_types as $type) {
        remove_meta_box('commentstatusdiv', $type, 'normal'); // Onglet Discussion
        remove_meta_box('commentsdiv', $type, 'normal');      // Onglet Commentaires
    }
});

add_filter('comments_open', function ($open, $post_id) {
    global $angel_post_types;
    
    $post = get_post($post_id);
    if (in_array($post->post_type, $angel_post_types)) {
        return false;
    }
    return $open;
}, 10, 2);

add_action('after_setup_theme', function () {
  register_nav_menus([
    'main_menu' => __('Menu principal', 'twentytwentytwo-child'),
  ]);
});

remove_theme_support('block-templates');


function autoriser_champs_yoast_api() {
  // On inclut les types de posts par défaut et les vôtres
  $post_types_a_autoriser = ['post', 'page', 'business-plan', 'lancement', 'pilotage', 'solution'];

  // On boucle sur chaque type de post pour autoriser les champs Yoast
  foreach ($post_types_a_autoriser as $post_type) {
      // Autoriser le champ "Titre SEO"
      register_post_meta($post_type, '_yoast_wpseo_title', [
          'show_in_rest' => true,
          'single' => true,
          'type' => 'string',
      ]);

      // Autoriser le champ "Méta description"
      register_post_meta($post_type, '_yoast_wpseo_metadesc', [
          'show_in_rest' => true,
          'single' => true,
          'type' => 'string',
      ]);
      
      // Vous pouvez ajouter d'autres champs ici si besoin, ex: '_yoast_wpseo_canonical'
  }
}
add_action('rest_api_init', 'autoriser_champs_yoast_api');
