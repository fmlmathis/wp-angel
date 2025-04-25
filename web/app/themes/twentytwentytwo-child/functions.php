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
