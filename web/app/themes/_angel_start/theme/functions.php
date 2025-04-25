<?php
/**
 * _angel_start functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package _angel_start
 */

if ( ! defined( '_ANGEL_START_VERSION' ) ) {
	/*
	 * Set the theme’s version number.
	 *
	 * This is used primarily for cache busting. If you use `npm run bundle`
	 * to create your production build, the value below will be replaced in the
	 * generated zip file with a timestamp, converted to base 36.
	 */
	define( '_ANGEL_START_VERSION', '0.1.0' );
}

if ( ! defined( '_ANGEL_START_TYPOGRAPHY_CLASSES' ) ) {
	/*
	 * Set Tailwind Typography classes for the front end, block editor and
	 * classic editor using the constant below.
	 *
	 * For the front end, these classes are added by the `_angel_start_content_class`
	 * function. You will see that function used everywhere an `entry-content`
	 * or `page-content` class has been added to a wrapper element.
	 *
	 * For the block editor, these classes are converted to a JavaScript array
	 * and then used by the `./javascript/block-editor.js` file, which adds
	 * them to the appropriate elements in the block editor (and adds them
	 * again when they’re removed.)
	 *
	 * For the classic editor (and anything using TinyMCE, like Advanced Custom
	 * Fields), these classes are added to TinyMCE’s body class when it
	 * initializes.
	 */
	define(
		'_ANGEL_START_TYPOGRAPHY_CLASSES',
		'prose prose-neutral max-w-none prose-a:text-primary'
	);
}

if ( ! function_exists( '_angel_start_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function _angel_start_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on _angel_start, use a find and replace
		 * to change '_angel_start' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( '_angel_start', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in two locations.
		register_nav_menus(
			array(
				'menu-1' => __( 'Primary', '_angel_start' ),
				'menu-2' => __( 'Footer Menu', '_angel_start' ),
			)
		);

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		// Add support for editor styles.
		add_theme_support( 'editor-styles' );

		// Enqueue editor styles.
		add_editor_style( 'style-editor.css' );
		add_editor_style( 'style-editor-extra.css' );

		// Add support for responsive embedded content.
		add_theme_support( 'responsive-embeds' );

		// Remove support for block templates.
		remove_theme_support( 'block-templates' );
	}
endif;
add_action( 'after_setup_theme', '_angel_start_setup' );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function _angel_start_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Footer', '_angel_start' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Add widgets here to appear in your footer.', '_angel_start' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', '_angel_start_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function _angel_start_scripts() {
	wp_enqueue_style( '_angel_start-style', get_stylesheet_uri(), array(), _ANGEL_START_VERSION );
	wp_enqueue_script( '_angel_start-script', get_template_directory_uri() . '/js/script.min.js', array(), _ANGEL_START_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', '_angel_start_scripts' );

/**
 * Enqueue the block editor script.
 */
function _angel_start_enqueue_block_editor_script() {
	if ( is_admin() ) {
		wp_enqueue_script(
			'_angel_start-editor',
			get_template_directory_uri() . '/js/block-editor.min.js',
			array(
				'wp-blocks',
				'wp-edit-post',
			),
			_ANGEL_START_VERSION,
			true
		);
		wp_add_inline_script( '_angel_start-editor', "tailwindTypographyClasses = '" . esc_attr( _ANGEL_START_TYPOGRAPHY_CLASSES ) . "'.split(' ');", 'before' );
	}
}
add_action( 'enqueue_block_assets', '_angel_start_enqueue_block_editor_script' );

/**
 * Add the Tailwind Typography classes to TinyMCE.
 *
 * @param array $settings TinyMCE settings.
 * @return array
 */
function _angel_start_tinymce_add_class( $settings ) {
	$settings['body_class'] = _ANGEL_START_TYPOGRAPHY_CLASSES;
	return $settings;
}
add_filter( 'tiny_mce_before_init', '_angel_start_tinymce_add_class' );

/**
 * Limit the block editor to heading levels supported by Tailwind Typography.
 *
 * @param array  $args Array of arguments for registering a block type.
 * @param string $block_type Block type name including namespace.
 * @return array
 */
function _angel_start_modify_heading_levels( $args, $block_type ) {
	if ( 'core/heading' !== $block_type ) {
		return $args;
	}

	// Remove <h1>, <h5> and <h6>.
	$args['attributes']['levelOptions']['default'] = array( 2, 3, 4 );

	return $args;
}
add_filter( 'register_block_type_args', '_angel_start_modify_heading_levels', 10, 2 );

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Register ACF Blocks
 */
function _angel_start_register_acf_blocks() {
    if (!function_exists('acf_register_block_type')) {
        return;
    }

    // Get all block folders
    $blocks_dir = get_template_directory() . '/blocks';
    if (!is_dir($blocks_dir)) {
        return;
    }

    $block_folders = array_filter(glob($blocks_dir . '/*'), 'is_dir');

    foreach ($block_folders as $block_folder) {
        $block_name = basename($block_folder);
        $block_config_file = $block_folder . '/config.php';
        
        // Skip if no config file exists
        if (!file_exists($block_config_file)) {
            continue;
        }
        
        // Load block configuration
        $config = include $block_config_file;
        
        // Register the block
        acf_register_block_type([
            'name'              => $block_name,
            'title'             => $config['title'] ?? ucfirst(str_replace('-', ' ', $block_name)),
            'description'       => $config['description'] ?? '',
            'render_template'   => "blocks/{$block_name}/template.php",
            'category'          => $config['category'] ?? 'formatting',
            'icon'              => $config['icon'] ?? 'block-default',
            'keywords'          => $config['keywords'] ?? [],
            'supports'          => $config['supports'] ?? [
                'align' => true,
                'mode' => false,
                'jsx' => true
            ],
            'example'           => $config['example'] ?? [],
            'enqueue_style'     => isset($config['enqueue_style']) && $config['enqueue_style'] ? 
                                   get_template_directory_uri() . "/blocks/{$block_name}/style.css" : 
                                   false,
            'enqueue_script'    => isset($config['enqueue_script']) && $config['enqueue_script'] ? 
                                   get_template_directory_uri() . "/blocks/{$block_name}/script.js" : 
                                   false,
        ]);
    }
}
add_action('acf/init', '_angel_start_register_acf_blocks');
