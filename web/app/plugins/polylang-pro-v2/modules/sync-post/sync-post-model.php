<?php
/**
 * @package Polylang-Pro
 */

/**
 * Model for synchronizing posts
 *
 * @since 2.6
 */
class PLL_Sync_Post_Model {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * @var PLL_Sync
	 */
	public $sync;

	/**
	 * @var PLL_Sync_Content
	 */
	public $sync_content;

	/**
	 * Stores a copy information.
	 *
	 * @var array
	 */
	protected $doing_copy;

	/**
	 * Stores a synchronization information.
	 *
	 * @var array
	 */
	protected $doing_sync;

	/**
	 * Used to tell if we're doing synchro.
	 *
	 * @var string
	 */
	const SYNC = 'sync';

	/**
	 * Used to tell if we're doing copy.
	 *
	 * @var string
	 */
	const COPY = 'copy';

	/**
	 * The current language.
	 *
	 * @var PLL_Language|null
	 */
	private $curlang;

	/**
	 * Constructor
	 *
	 * @since 2.6
	 *
	 * @param object $polylang Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->options      = &$polylang->options;
		$this->model        = &$polylang->model;
		$this->sync         = &$polylang->sync;
		$this->sync_content = &$polylang->sync_content;
		$this->curlang      = &$polylang->curlang;

		add_filter( 'pll_copy_taxonomies', array( $this, 'copy_taxonomies' ), 5, 4 );
		add_filter( 'pll_copy_post_metas', array( $this, 'copy_post_metas' ), 5, 4 );
	}

	/**
	 * Copies all taxonomies.
	 *
	 * @since 2.1
	 *
	 * @param string[] $taxonomies List of taxonomy names.
	 * @param bool     $sync       True for a synchronization, false for a simple copy.
	 * @param int      $from       Source post id.
	 * @param int      $to         Target post id.
	 * @return string[]
	 */
	public function copy_taxonomies( $taxonomies, $sync, $from, $to ) {
		if ( ! empty( $from ) && ! empty( $to ) && $this->can_copy( $from, $to ) ) {
			$taxonomies = array_diff( get_post_taxonomies( $from ), get_taxonomies( array( '_pll' => true ) ) );
		}
		return $taxonomies;
	}

	/**
	 * Copies all custom fields.
	 *
	 * @since 2.1
	 *
	 * @param string[] $keys List of custom fields names.
	 * @param bool     $sync True if it is synchronization, false if it is a copy.
	 * @param int      $from Id of the post from which we copy the information.
	 * @param int      $to   Id of the post to which we paste the information.
	 * @return string[]
	 */
	public function copy_post_metas( $keys, $sync, $from, $to ) {
		if ( ! empty( $from ) && ! empty( $to ) && $this->can_copy( $from, $to ) ) {
			$from_keys = array_keys( get_post_custom( $from ) ); // *All* custom fields.
			$to_keys   = array_keys( get_post_custom( $to ) ); // Adding custom fields of the destination allow to synchronize deleted custom fields.
			$keys      = array_merge( $from_keys, $to_keys );
			$keys      = array_unique( $keys );
			$keys      = array_diff( $keys, array( '_edit_last', '_edit_lock' ) );

			// Trash meta status must not be synchronized when bulk trashing / restoring posts otherwise WP can't restore the right post status.
			if ( $this->doing_bulk_trash( $to ) ) {
				$keys = array_diff( $keys, array( '_wp_trash_meta_status', '_wp_trash_meta_time' ) );
			}
		}
		return $keys;
	}

	/**
	 * Checks if the synchronized post is included in bulk trashing or restoring posts
	 *
	 * @since 2.1.2
	 *
	 * @param int $post_id Id of the target post.
	 * @return bool
	 */
	protected function doing_bulk_trash( $post_id ) {
		return 'edit.php' === $GLOBALS['pagenow'] && isset( $_GET['action'], $_GET['post'] ) && in_array( sanitize_key( $_GET['action'] ), array( 'trash', 'untrash' ) ) && in_array( $post_id, array_map( 'absint', $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Duplicates or synchronizes the post to one language and optionally saves the synchronization group.
	 *
	 * @since 3.7
	 *
	 * @param int    $post_id    Post ID of the source post.
	 * @param string $lang       Target language slug.
	 * @param string $strategy   `sync` if doing synchro, `copy` otherwise.
	 * @param bool   $save_group True to update the synchronization group, false otherwise.
	 * @return int ID of the target post, 0 on failure.
	 *
	 * @phpstan-param 'sync'|'copy' $strategy
	 */
	public function copy( $post_id, $lang, $strategy = self::COPY, $save_group = false ) {
		global $wpdb;

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			// The source post doesn't exist.
			return 0;
		}

		$tr_post   = clone $post;
		$tr_id     = $this->model->post->get( $post_id, $this->model->get_language( $lang ) );
		$languages = array_keys( $this->get( $post_id ) );

		// Loads the strings translations with the post's target language.
		PLL()->load_strings_translations( $lang );

		// If it does not exist, create it.
		if ( ! $tr_id ) {
			$tr_post->ID = 0;
			$tr_id       = wp_insert_post( wp_slash( $tr_post->to_array() ) );
			if ( empty( $tr_id ) ) {
				return 0;
			}

			if ( self::SYNC === $strategy ) {
				$this->doing_sync[ $post_id ][ $tr_id ] = true;
			} else {
				$this->doing_copy[ $post_id ][ $tr_id ] = true;
			}

			$this->model->post->set_language( $tr_id, $lang ); // Necessary to do it now to share slug.

			$translations = $this->model->post->get_translations( $post_id );
			$translations[ $lang ] = $tr_id;
			$this->model->post->save_translations( $post_id, $translations ); // Saves translations in case we created a post.

			$languages[] = $lang;

			// Maybe duplicates the featured image.
			if ( $this->options['media_support'] ) {
				add_filter( 'pll_translate_post_meta', array( $this->sync_content, 'duplicate_thumbnail' ), 10, 3 );
			}

			add_filter( 'pll_maybe_translate_term', array( $this->sync_content, 'duplicate_term' ), 10, 3 );

			$this->sync->taxonomies->copy( $post_id, $tr_id, $lang );
			$this->sync->post_metas->copy( $post_id, $tr_id, $lang );

			$_POST['post_tr_lang'][ $lang ] = $tr_id; // Hack to avoid creating multiple posts if the original post is saved several times (ex WooCommerce 3.0+).

			/**
			 * Fires after a synchronized post has been created
			 *
			 * @since 2.3.11
			 *
			 * @param int    $post_id ID of the source post.
			 * @param int    $tr_id   ID of the newly created post.
			 * @param string $lang    Language of the newly created post.
			 * @param string  $strategy `sync` if doing synchro, `copy` otherwise.
			 */
			do_action( 'pll_created_sync_post', $post_id, $tr_id, $lang, $strategy );

			/** This action is documented in /polylang/include/crud-posts.php */
			do_action( 'pll_save_post', $post_id, $post, $translations ); // Fire again as we just updated $translations.

			unset( $this->doing_copy[ $post_id ][ $tr_id ] );
		}

		$previous = get_post( $tr_id ); // Remember the previous post to handle the status transition.

		if ( ! $previous instanceof WP_Post ) {
			// Something went wrong!
			return 0;
		}

		if ( $save_group ) {
			$this->save_group( $post_id, $languages );
		}

		$tr_post->ID = $tr_id;
		$tr_post->post_parent = (int) $this->model->post->get( $post->post_parent, $lang ); // Translates post parent.
		$tr_post = $this->sync_content->copy_content( $post, $tr_post, $lang );

		// The columns to copy in DB.
		$columns = array(
			'post_author',
			'post_date',
			'post_date_gmt',
			'post_content',
			'post_title',
			'post_excerpt',
			'comment_status',
			'ping_status',
			'post_name',
			'post_modified',
			'post_modified_gmt',
			'post_parent',
			'menu_order',
			'post_mime_type',
		);

		// Don't synchronize when trashing / restoring in bulk as it causes an error fired by WP.
		if ( ! $this->doing_bulk_trash( $tr_id ) ) {
			$columns[] = 'post_status';
		}

		is_sticky( $post_id ) ? stick_post( $tr_id ) : unstick_post( $tr_id );

		/**
		 * Filters the post fields to synchronize when synchronizing posts
		 *
		 * @since 2.3
		 *
		 * @param array  $fields     WP_Post fields to synchronize.
		 * @param int    $post_id    Post id of the source post.
		 * @param string $lang       Target language slug.
		 * @param bool   $save_group True to update the synchronization group, false otherwise.
		 */
		$columns = apply_filters( 'pll_sync_post_fields', array_combine( $columns, $columns ), $post_id, $lang, $save_group );

		$wpdb->update( $wpdb->posts, array_intersect_key( $tr_post->to_array(), $columns ), array( 'ID' => $tr_id ) ); // Don't use wp_update_post to avoid conflict (reverse sync).
		clean_post_cache( $tr_id );

		wp_transition_post_status( $tr_post->post_status, $previous->post_status, $tr_post );

		/**
		 * Fires after a post has been synchronized.
		 *
		 * @since 2.6.3
		 *
		 * @param int    $post_id ID of the source post.
		 * @param int    $tr_id   ID of the target post.
		 * @param string $lang    Language of the target post.
		 * @param string  $strategy `sync` if doing synchro, `copy` otherwise.
		 */
		do_action( 'pll_post_synchronized', $post_id, $tr_id, $lang, $strategy );

		// Restores the strings translations with the current language.
		if ( $this->curlang instanceof PLL_Language ) {
			PLL()->load_strings_translations( $this->curlang->slug );
		}

		unset( $this->doing_sync[ $post_id ][ $tr_id ] );

		return $tr_id;
	}

	/**
	 * Duplicates the post to one language and optionally saves the synchronization group.
	 * Backward compatibility with Polylang Pro < 3.7.
	 *
	 * @since 2.2
	 * @since 3.7 Deprecated, replaced by `PLL_Sync_Post_Model::copy()`.
	 * @deprecated
	 *
	 * @param int    $from       Post ID of the source post.
	 * @param string $lang       Target language slug.
	 * @param bool   $save_group True to update the synchronization group, false otherwise.
	 * @return int ID of the target post, 0 on failure.
	 */
	public function copy_post( $from, $lang, $save_group = true ) {
		_deprecated_function( __METHOD__, '3.7', 'PLL_Sync_Post_Model::copy()' );

		$strategy = $save_group ? self::SYNC : self::COPY;

		return $this->copy( $from, $lang, $strategy, $save_group );
	}

	/**
	 * Saves the synchronization group
	 * This is stored as an array beside the translations in the post_translations term description
	 *
	 * @since 2.1
	 *
	 * @param int   $post_id   ID of the post currently being saved.
	 * @param array $sync_post Array of languages to sync with this post.
	 * @return void
	 */
	public function save_group( $post_id, $sync_post ) {
		$term = $this->model->post->get_object_term( $post_id, 'post_translations' );

		if ( empty( $term ) ) {
			return;
		}

		$d    = maybe_unserialize( $term->description );
		$lang = $this->model->post->get_language( $post_id );

		if ( ! is_array( $d ) || empty( $lang ) ) {
			return;
		}

		$lang = $lang->slug;

		if ( empty( $sync_post ) ) {
			if ( isset( $d['sync'][ $lang ] ) ) {
				$d['sync'] = array_diff( $d['sync'], array( $d['sync'][ $lang ] ) );
			}
		} else {
			$sync_post[] = $lang;
			$d['sync']   = empty( $d['sync'] ) ? array_fill_keys( $sync_post, $lang ) : array_merge( array_diff( $d['sync'], array( $lang ) ), array_fill_keys( $sync_post, $lang ) );
		}

		wp_update_term( (int) $term->term_id, 'post_translations', array( 'description' => maybe_serialize( $d ) ) );
	}

	/**
	 * Get all posts synchronized with a given post
	 *
	 * @since 2.1
	 *
	 * @param int $post_id The id of the post.
	 * @return array An associative array of arrays with language code as key and post id as value.
	 */
	public function get( $post_id ) {
		$term = $this->model->post->get_object_term( $post_id, 'post_translations' );

		if ( ! empty( $term ) ) {
			$lang = $this->model->post->get_language( $post_id );
			$d    = maybe_unserialize( $term->description );

			if ( ! is_array( $d ) || empty( $lang ) ) {
				return array();
			}

			if ( ! empty( $d['sync'][ $lang->slug ] ) ) {
				$keys = array_keys( $d['sync'], $d['sync'][ $lang->slug ] );
				return array_intersect_key( $d, array_flip( $keys ) );
			}
		}

		return array();
	}

	/**
	 * Checks whether two posts are synchronized.
	 *
	 * @since 2.1
	 *
	 * @param int $post_id  The ID of a first post to compare.
	 * @param int $other_id The ID of the other post to compare.
	 * @return bool
	 */
	public function are_synchronized( $post_id, $other_id ) {
		return isset( $this->doing_sync[ $post_id ][ $other_id ] ) || in_array( $other_id, $this->get( $post_id ) );
	}

	/**
	 * Checks whether two posts can be copied.
	 *
	 * @since 3.7
	 *
	 * @param int $from The ID of a first post to compare.
	 * @param int $to   The ID of the other post to compare.
	 * @return bool
	 */
	protected function can_copy( $from, $to ) {
		return isset( $this->doing_copy[ $from ][ $to ] ) || $this->are_synchronized( $from, $to );
	}

	/**
	 * Check if the current user can synchronize a post in other language
	 *
	 * @since 2.6
	 *
	 * @param int    $post_id Post to synchronize.
	 * @param string $lang    Language code.
	 * @return bool
	 */
	public function current_user_can_synchronize( $post_id, $lang ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$tr_id = $this->model->post->get( $post_id, $this->model->get_language( $lang ) );

		// If we don't have a translation yet, check if we have the right to create a new one?
		if ( empty( $tr_id ) ) {
			$post_type = get_post_type( $post_id );
			$post_type_object = get_post_type_object( $post_type );
			return current_user_can( $post_type_object->cap->create_posts );
		}

		// Do we have the right to edit this translation?
		if ( ! current_user_can( 'edit_post', $tr_id ) ) {
			return false;
		}

		// Is this translation synchronized with a post that we can't edit?
		$ids = $this->get( $tr_id );

		foreach ( $ids as $id ) {
			if ( ! current_user_can( 'edit_post', $id ) ) {
				return false;
			}
		}

		return true;
	}
}
