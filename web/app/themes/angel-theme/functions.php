<?php

// Chargement automatique du CSS
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('angel-style', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
});
