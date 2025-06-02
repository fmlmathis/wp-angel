<?php
/**
 * Plugin Name: Headless Frontend Links
 * Description: Redirects WordPress admin "View" links to the headless frontend
 * Version: 1.0
 * Author: Angel Start
 */

/**
 * Liste des types de contenu qui doivent avoir le préfixe /blog
 * Ajoutez ou retirez des types selon vos besoins
 */
function get_blog_post_types() {
    return ['post']; // Uniquement les articles standard
}

/**
 * Modifie les URLs des posts pour qu'ils pointent vers le frontend headless
 */
function headless_frontend_post_link($permalink, $post) {
    // URL du frontend
    $frontend_url = 'https://new.angel-start.com';
    
    // Obtenir le chemin relatif de l'URL
    $path = parse_url($permalink, PHP_URL_PATH);
    
    // Si c'est un type de contenu qui doit avoir le préfixe /blog
    $blog_post_types = get_blog_post_types();
    if (in_array($post->post_type, $blog_post_types) && strpos($path, '/blog/') !== 0) {
        // Si le chemin ne commence pas déjà par /blog/, l'ajouter
        $path = '/blog' . $path;
    }
    
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
    
    // Si c'est une catégorie ou un tag de blog, ajouter le préfixe /blog
    if ($term->taxonomy === 'category' || $term->taxonomy === 'post_tag') {
        if (strpos($path, '/blog/') !== 0) {
            $path = '/blog' . $path;
        }
    }
    
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
    
    // Vérifier si cette pièce jointe est liée à un article de blog
    $parent_post_id = wp_get_post_parent_id($attachment_id);
    if ($parent_post_id) {
        $parent_post = get_post($parent_post_id);
        $blog_post_types = get_blog_post_types();
        if ($parent_post && in_array($parent_post->post_type, $blog_post_types) && strpos($path, '/blog/') !== 0) {
            $path = '/blog' . $path;
        }
    }
    
    // Construire la nouvelle URL avec le domaine du frontend
    return $frontend_url . $path;
}

/**
 * Modifie les URLs des types de contenu personnalisés
 */
function headless_frontend_custom_post_type_link($permalink, $post) {
    // Utiliser la même fonction que pour les posts standard
    return headless_frontend_post_link($permalink, $post);
}

// Appliquer les filtres pour tous les types de contenu
add_filter('post_link', 'headless_frontend_post_link', 10, 2);
add_filter('page_link', 'headless_frontend_page_link', 10, 2);
add_filter('term_link', 'headless_frontend_term_link', 10, 2);
add_filter('attachment_link', 'headless_frontend_attachment_link', 10, 2);
add_filter('post_type_link', 'headless_frontend_custom_post_type_link', 10, 2);

/**
 * Ajoute un message dans l'admin pour indiquer que le site est en mode headless
 */
function headless_admin_notice() {
    echo '<div class="notice notice-info is-dismissible">
        <p>Ce site WordPress est en mode <strong>headless</strong>. Le frontend public est disponible sur <a href="https://new.angel-start.com" target="_blank">new.angel-start.com</a>.</p>
    </div>';
}
add_action('admin_notices', 'headless_admin_notice');
