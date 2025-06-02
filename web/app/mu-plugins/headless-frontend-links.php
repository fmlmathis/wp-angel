<?php
/**
 * Plugin Name: Headless Frontend Links
 * Description: Redirects WordPress admin "View" links to the headless frontend
 * Version: 1.0
 * Author: Angel Start
 */

/**
 * Modifie les URLs des posts pour qu'ils pointent vers le frontend headless
 */
function headless_frontend_post_link($permalink, $post) {
    // URL du frontend
    $frontend_url = 'https://new.angel-start.com';
    
    // Obtenir le chemin relatif de l'URL
    $path = parse_url($permalink, PHP_URL_PATH);
    
    // Construire la nouvelle URL avec le domaine du frontend
    return $frontend_url . $path;
}

/**
 * Modifie les URLs des termes pour qu'ils pointent vers le frontend headless
 */
function headless_frontend_term_link($permalink, $term) {
    // URL du frontend
    $frontend_url = 'https://new.angel-start.com';
    
    // Obtenir le chemin relatif de l'URL
    $path = parse_url($permalink, PHP_URL_PATH);
    
    // Construire la nouvelle URL avec le domaine du frontend
    return $frontend_url . $path;
}

/**
 * Modifie les URLs des pages pour qu'elles pointent vers le frontend headless
 */
function headless_frontend_page_link($permalink, $page_id) {
    // URL du frontend
    $frontend_url = 'https://new.angel-start.com';
    
    // Obtenir le chemin relatif de l'URL
    $path = parse_url($permalink, PHP_URL_PATH);
    
    // Construire la nouvelle URL avec le domaine du frontend
    return $frontend_url . $path;
}

/**
 * Modifie les URLs des pièces jointes pour qu'elles pointent vers le frontend headless
 */
function headless_frontend_attachment_link($permalink, $attachment_id) {
    // URL du frontend
    $frontend_url = 'https://new.angel-start.com';
    
    // Obtenir le chemin relatif de l'URL
    $path = parse_url($permalink, PHP_URL_PATH);
    
    // Construire la nouvelle URL avec le domaine du frontend
    return $frontend_url . $path;
}

// Appliquer les filtres pour tous les types de contenu
add_filter('post_link', 'headless_frontend_post_link', 10, 2);
add_filter('page_link', 'headless_frontend_page_link', 10, 2);
add_filter('term_link', 'headless_frontend_term_link', 10, 2);
add_filter('attachment_link', 'headless_frontend_attachment_link', 10, 2);
add_filter('post_type_link', 'headless_frontend_post_link', 10, 2);

/**
 * Ajoute un message dans l'admin pour indiquer que le site est en mode headless
 */
function headless_admin_notice() {
    echo '<div class="notice notice-info is-dismissible">
        <p>Ce site WordPress est en mode <strong>headless</strong>. Le frontend public est disponible sur <a href="https://new.angel-start.com" target="_blank">new.angel-start.com</a>.</p>
    </div>';
}
add_action('admin_notices', 'headless_admin_notice');
