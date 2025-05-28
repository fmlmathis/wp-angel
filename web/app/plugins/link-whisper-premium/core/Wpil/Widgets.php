<?php

/**
 * Class for creating and manipulating widgets on the frontend
 */
class Wpil_Widgets
{

    static $related_post_cache = array();

    /**
     * Register and initialize class
     */
    public function register()
    {
        self::register_shortcode();
        add_action('add_meta_boxes', array(__CLASS__, 'add_related_posts_metabox'), 2);
        add_action('save_post', array(__CLASS__, 'save_related_posts_metabox'), 2);
        add_action('wp_ajax_wpil_refresh_related_post_links', array(__CLASS__, 'ajax_refresh_related_post_links'), 2);
        add_action('wp_ajax_wpil_search_related_posts', array(__CLASS__, 'ajax_search_related_posts'), 2);
        add_action('wp_ajax_wpil_save_related_posts', array(__CLASS__, 'ajax_save_related_posts'), 2);
        add_filter('the_content', array(__CLASS__, 'append_related_posts'), 999999);
    }

    public static function prepare_table(){
        global $wpdb;
        
        $related_posts = $wpdb->prefix . "wpil_related_posts";
        
        // if the related post table doesn't exist
        $rp_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$related_posts}'");
        if(empty($rp_tbl_exists)){
            $related_posts_table_query = "CREATE TABLE IF NOT EXISTS {$related_posts} (
                                            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            post_id int(10) unsigned NOT NULL,
                                            post_type varchar(8),
                                            processed tinyint(1) DEFAULT 0,
                                            manual_process tinyint(1) DEFAULT 0,
                                            process_time bigint(20),
                                            related_post_data text,
                                            PRIMARY KEY (id),
                                            INDEX (post_id)
                                        )";
                /**
                 * id === table index
                 * post_id === post|term id
                 * post_type === data type, 'post'|'term'
                 * processed === boolint, has the post been processed?
                 * manual_process === boolint, have the related posts been manually chosen?
                 * process_time === timestamp of last process
                 * related_post_data === the encoded data for the related posts
                 */

            // create DB table if it doesn't exist
            require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($related_posts_table_query);
        }
    }

    /**
     * Gets the related post data for a single post.
     * Also housekeeps to make sure that data only exists for a single post at a time
     **/
    public static function get_related_post_data($post_id = 0, $post_type = 'post'){
        global $wpdb;
        $related_posts = $wpdb->prefix . "wpil_related_posts";

        if(empty($post_id) || empty($post_type)){
            return array();
        }

        $cached = self::get_related_post_cache_data($post_id, $post_type);
        if(!is_null($cached)){
            return $cached; 
        }

        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$related_posts} WHERE `post_id` = %d AND `post_type` = %s", $post_id, $post_type), ARRAY_A);

        // if there's related post data, but a post is entered more than once
        if(!empty($results) && count($results) > 1){
            // find the newest dataset
            $newest = 0;
            $newest_data = array();
            $old_ids = array();
            foreach($results as $dat){
                if($dat['process_time'] > $newest){
                    // if we've found newer new data
                    if(!empty($newest_data)){
                        // enter the old new data
                        $old_ids[] = $newest_data['id'];
                    }

                    $newest_data = $dat;
                    $newest = $dat['process_time'];
                }else{
                    $old_ids[] = $dat['id'];
                }
            }

            // reset the original data
            $results = array();
            // and update it with the newest related post data
            $results[] = $newest_data;

            // if we found old duplicate data
            if(!empty($old_ids)){
                // remove it
                $old_ids = implode(',', $old_ids);
                $wpdb->query($wpdb->prepare("DELETE FROM {$related_posts} WHERE `id` IN ({$old_ids})"));
            }
        }

        $return = (!empty($results) && isset($results[0]) && !empty($results[0])) ? $results[0]: array();

        self::set_related_post_cache_data($post_id, $post_type, $return);

        return $return;
    }

    /**
     * Gets any related post cache data from the PHP cache
     **/
    public static function get_related_post_cache_data($id = 0, $type = ''){
        if(empty($id) || empty($type) || !in_array($type, array('post', 'term'), true)){
            return null;
        }

        $cache_id = $id . '_' . $type;

        return (isset(self::$related_post_cache[$cache_id]) && !empty(self::$related_post_cache[$cache_id])) ? self::$related_post_cache[$cache_id]: null;
    }

    /**
     * Stores the pulled related post data to the PHP cache in case there are new calls for it
     **/
    public static function set_related_post_cache_data($id = 0, $type = '', $data = array()){
        if(empty($id) || empty($type) || !in_array($type, array('post', 'term'), true)){
            return null;
        }

        $cache_id = $id . '_' . $type;

        self::$related_post_cache[$cache_id] = $data;

        return (!empty(self::$related_post_cache[$cache_id])) ? true: array();
    }

    /**
     * Gets the related post links for a single post
     **/
    public static function get_related_post_link_data($post_id = 0, $post_type = 'post', $ignore_stale = true){
        if(empty($post_id) || empty($post_type)){
            return array();
        }

        $data = self::get_related_post_data($post_id, $post_type);

        // if the data is not fresh enough
//        if(!empty($data) && $ignore_stale && $data['process_date'] < (time() - YEAR_IN_SECONDS)){ // todo make setting to allow for periodically clearing data
//            return array();
//        }

        // if the data is not set to manual process and the user has selected "manual select"
        if(!empty($data) && empty($data['manual_process']) && Wpil_Settings::get_related_post_settings('select_method') === 'manual'){
            // return empty here
            return array();
        }

        $data = (!empty($data) && isset($data['related_post_data'])) ? maybe_unserialize($data['related_post_data']): array();

        return (!empty($data)) ? $data: array();
    }

    /**
     * Updates the related post data for a single post
     **/
    public static function update_related_post_data($post_id = 0, $post_type = 'post', $related_post_data = array(), $manual_process = NULL){
        global $wpdb;
        $related_posts = $wpdb->prefix . "wpil_related_posts";

        if(empty($post_id) || empty($post_type)){
            return array();
        }

        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$related_posts} WHERE `post_id` = %d AND `post_type` = %s", $post_id, $post_type), ARRAY_A);
        if(!empty($results)){
            // should only be one though!
            foreach($results as $result){
                if($manual_process === NULL){
                    $wpdb->query($wpdb->prepare("UPDATE {$related_posts} SET `processed` = 1, `process_time` = %d, `related_post_data` = %s WHERE `id` = %s", time(), maybe_serialize($related_post_data), $result['id']));
                }else{
                    $wpdb->query($wpdb->prepare("UPDATE {$related_posts} SET `processed` = 1, `manual_process` = %d, `process_time` = %d, `related_post_data` = %s WHERE `id` = %s", $manual_process, time(), maybe_serialize($related_post_data), $result['id']));
                }
            }
        }else{
            
            // if the manual process flag hasn't been set
            if(null === $manual_process){
                // set it based on the settings
                $settings = Wpil_Settings::get_related_post_settings();
                $manual_process = ($settings['select_method'] === 'manual') ? 1: 0;
            }

            $insert_query = "INSERT INTO {$related_posts} 
                            (post_id, post_type, processed, manual_process, process_time, related_post_data) VALUES 
                            (%d, %s, %d, %d, %d, %s)";
            $wpdb->query($wpdb->prepare($insert_query, $post_id, $post_type, 1, $manual_process, time(), maybe_serialize($related_post_data)));
        }

        return (!empty($results)) ? $results: array();
    }

    /**
     * Deletes the related post data for a single post
     **/
    public static function delete_related_post_data($post_id = 0, $post_type = 'post'){
        global $wpdb;
        $related_posts = $wpdb->prefix . "wpil_related_posts";

        if(empty($post_id) || empty($post_type)){
            return array();
        }

        $wpdb->query($wpdb->prepare("DELETE FROM {$related_posts} WHERE `post_id` = %d AND `post_type` = %s", $post_id, $post_type));
    }

    /**
     * Deletes the related post data for all "automatically" selected related posts
     **/
    public static function clear_automatic_related_post_data($post_id = 0, $post_type = 'post'){
        global $wpdb;
        $related_posts = $wpdb->prefix . "wpil_related_posts";

        if(empty($post_id) || empty($post_type)){
            return array();
        }

        $wpdb->query($wpdb->prepare("DELETE FROM {$related_posts} WHERE `post_id` = %d AND `post_type` = %s AND `manual_process` = 0", $post_id, $post_type)); // manual_process == 0 means that the suggestions were automatically chosen
    }

    /**
     * Deletes the related post data for all related posts
     **/
    public static function clear_all_related_post_data(){
        global $wpdb;
        $related_posts = $wpdb->prefix . "wpil_related_posts";

        $wpdb->query("TRUNCATE TABLE {$related_posts}");
    }


    public static function register_shortcode(){
        add_shortcode('link-whisper-related-posts', array(__CLASS__, 'render_related_posts'));
    }

    public static function render_related_posts($attributes, $content = '', $shortcode = ''){
        global $post;

        // if this:
        if( is_admin() || // is the admin
            empty($post) || // there's no post
            !is_a($post, 'WP_Post') || // this is not a post data type
            !isset($post->ID) || // it's somehow an empty post
            !isset($post->post_type) // ditto
        ){
            return '[' . $shortcode . ']';
        }

        // check if the shortcode has the "force_display" attribute to override the manual control box
        $force_display = (!empty($attributes) && isset($attributes['force_display']) && $attributes['force_display'] === 'true');
        $active = Wpil_Settings::related_posts_active($post->ID, $force_display);

        if(
            is_front_page() || 
            is_home() ||
            is_archive() ||
            !$active || // or related posts isn't active for this post
            (!$force_display && !in_array($post->post_type, Wpil_Settings::get_related_posts_active_post_types(), true)) // or the post isn't in a selected post type and we're not set to override it
        ){
            return;
        }

        // find out if we're doing a preview
        $preview = (isset($_GET['nonce']) && wp_verify_nonce($_GET['nonce'], 'wpil-related-posts-preview-nonce'));

        $settings = self::get_shortcode_settings('related-posts');

        // handle any supplied shortcode display attributes
        if(isset($attributes['rows']) && (int) $attributes['rows'] > 0 && (int) $attributes['rows'] < 4){
            $settings['widget_layout']['display'] = 'row';
            $settings['widget_layout']['count'] = (int) $attributes['rows'];
        }elseif(isset($attributes['cols']) && (int) $attributes['cols'] > 0 && (int) $attributes['cols'] < 4){
            $settings['widget_layout']['display'] = 'column';
            $settings['widget_layout']['count'] = (int) $attributes['cols'];
        }

        // if the user has supplied a max link count
        if(isset($attributes['max_links']) && (int) $attributes['max_links'] > 0 && (int) $attributes['max_links'] < 40){
            $settings['link_count'] = (int) $attributes['max_links'];
        }

        // get link data
        $data = array(
            'links' => self::get_related_post_links($post, false, $preview),
            'post' => $post,
            'settings' => $settings
        );

        // get template
        ob_start();

        $template = get_stylesheet_directory() . '/templates/link-whisper/frontend/related-posts.php';
        if(file_exists($template)){
            include $template;
        }else{
            // render our template
            include WP_INTERNAL_LINKING_PLUGIN_DIR . 'templates/frontend/related-posts.php';
        }

        $template_output = ob_get_clean();

        return (!empty($template_output) && is_string($template_output)) ? trim($template_output): '';

        // designs:
            // **simple list
            // **before and after dual lists
                // **all posts before on the left, and all posts after on the right
        // make template overridable
        // be sure to add shortcode to the "ignore related post links" setting!
        // settings:
            // select how to add related posts
                // shortcode/auto append
                    // for autoappend, allow selecting the post types it's available in
            // radio/slider to select linking policy
                // use only links in page -> be indifferent to links in page -> **avoid using links already in page
            // number of links
            // widget title
            // title element
            // widget description
            // prioritize pages in the same category/tag?
            // radio/slider for selecting related posts based on cats, tags, or both
    }

    /**
     * Gets the related post data for display in the widget.
     * If no related post data is available for display, it will do a search to find related posts
     * 
     * @param object $post A standard WordPress post object
     * @return array $data The link data to use when rendering the shortcode
     **/
    public static function get_related_post_links($post, $force_refresh = false, $doing_preview = false){
        global $wpdb;
        $link_report_table = $wpdb->prefix . 'wpil_report_links';

        // if we're not doing anything that requires new links
        $links = array();
        if(!$force_refresh && !$doing_preview){
            // try getting the links for the current post
            $links = self::get_related_post_link_data($post->ID);
        }

        // if there's no link data
        if(empty($links) || $force_refresh || $doing_preview){
            // and the overall id list
            $post_ids = array();
            // do a really quick pull of the settings
            $settings = self::get_shortcode_settings('related-posts');
            $top_up_related = true;
            $search_ids = array();
            $exclude_ids = array();
            $top_up_related = true;

            // if the settings are set to purely manual selection and we're not doing a reset
            if($settings['select_method'] === 'manual' && !$force_refresh && (!$doing_preview || $doing_preview && !empty($links))){
                // exit now
                return $links;
            }

            $relationships = array('related' => array(), 'not-related' => array());

            if(isset($settings['use_ai_data']) && !empty($settings['use_ai_data'])){
                $post_embeddings = Wpil_AI::get_embedding_relatedness_data($post->ID, 'post', true);
                if($post_embeddings && !empty($settings['ai_relation_threshold'])){
                    foreach($post_embeddings as $p_id => $relation){
                        $bits = explode('_', $p_id);
                        if($relation > $settings['ai_relation_threshold']){
                            $relationships['related'][] = $bits[1];
                        }else{
                            $relationships['not-related'][] = $bits[1];
                        }
                    }
                }
            }

            $args = array(
                'numberposts' => $settings['link_count'],
                'post_type' => Wpil_Settings::get_related_posts_active_post_types()
            );

            switch ($settings['sort_order']) {
                case 'rand':
                    $args['orderby'] = 'rand';
                    break;
                case 'newest':
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
                    break;
                case 'oldest':
                    $args['orderby'] = 'date';
                    $args['order'] = 'ASC';
                    break;
            }

            if($settings['link_handling'] !== 'none'){
                $wpil_post = new Wpil_Model_Post($post->ID);

                switch ($settings['link_handling']) {
                    case 'no-outbound-internal':
                    case 'prefer-outbound-internal':
                    case 'only-outbound-internal':
                        foreach($wpil_post->getOutboundInternalLinks() as $link){
                            if( isset($link->post) && 
                                !empty($link->post) && 
                                isset($link->post->type) && 
                                $link->post->type === 'post')
                            {
                                $post_ids[] = $link->post->id;
                            }
                        }

                        if($settings['link_handling'] === 'no-outbound-internal'){
                            $exclude_ids = $post_ids;
                        }else{

                            if($settings['link_handling'] === 'only-outbound-internal'){
                                $top_up_related = false;
                            }
                            $search_ids = $post_ids;
                        }

                        break;
                    case 'no-inbound-internal':
                    case 'prefer-inbound-internal':
                    case 'only-inbound-internal':
                        foreach($wpil_post->getInboundInternalLinks() as $link){
                            if( isset($link->post) && 
                                !empty($link->post) && 
                                isset($link->post->type) && 
                                $link->post->type === 'post')
                            {
                                $post_ids[] = $link->post->id;
                            }
                        }

                        if($settings['link_handling'] === 'no-inbound-internal'){
                            $exclude_ids = $post_ids;
                        }else{

                            if($settings['link_handling'] === 'only-inbound-internal'){
                                $top_up_related = false;
                            }
                            $search_ids = $post_ids;
                        }
                        break;
                }
            }

            if(Wpil_Settings::translation_enabled()){
                $same_lang_ids = Wpil_Post::getSameLanguagePosts($post->ID);
                if(!empty($same_lang_ids)){
                    if(!empty($search_ids)){
                        $search_ids = array_intersect($search_ids, $same_lang_ids);
                    }else{
                        $search_ids = $same_lang_ids;
                    }
                }

                if(Wpil_Settings::translation_enabled()){
                    $different_lang_ids = Wpil_Post::getNonSameLanguagePosts($post->ID);
                    if(!empty($different_lang_ids)){
                        $exclude_ids = array_merge($exclude_ids, $different_lang_ids);
                    }
                }
            }

            if($settings['orphaned_linking'] !== 'none'){
                if(Wpil_Settings::use_link_table_for_data()){
                    $post_status = Wpil_Query::postStatuses('a');
                    $post_types = Wpil_Query::postTypes('a');
                    $ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} a WHERE a.ID NOT IN (select distinct `target_id` from {$link_report_table} where `target_type` = 'post') {$post_status} {$post_types}");
                    $search_ids = array_unique(array_merge($search_ids, $ids));
                }else{
                    $args['meta_query'] = array(
                        array(
                            'key' => 'wpil_links_inbound_internal_count',
                            'value' => '0'
                        )
                    );
                }

                if($settings['orphaned_linking'] === 'only-orphaned'){
                    $top_up_related = false;
                }
            }

            if($settings['parent_search'] !== 'none'){
                $wpil_post = new Wpil_Model_Post($post->ID);
                $related = Wpil_Toolbox::get_related_post_ids($wpil_post);

                if($settings['parent_search'] !== 'only-both'){
                    $top_up_related = false;
                }else{
                    // add the found ids to the post id list so we can ignore them during the top-up
                    $post_ids = array_merge($post_ids, $related);
                }

                // now since we can't use post__in and post__not_in at the same time, we need to do a little filtering
                // if we're going to be excluding ids
                if(!empty($exclude_ids) && !empty($related)){
                    // remove any links from the search list that are on the exclude list
                    $search_ids = array_diff($related, $exclude_ids);
                    // and clear the exclude so that we don't wrongly set the flag
                    //$exclude_ids = array();
                }elseif(!empty($search_ids) && !empty($related)){
                    // if we're looking to filter the search ids,
                    // find all the ids that are present in both lists
                    $search_ids = array_intersect($related, $search_ids);
                }elseif(empty($search_ids) && !empty($related)){
                    $search_ids = $related;
                }
            }

            if(!empty($relationships['related'])){
                $search_ids = (!empty($search_ids)) ? array_intersect($search_ids, $relationships['related']): $relationships['related'];
            }

            if(!empty($relationships['not-related'])){
                $exclude_ids = array_merge($exclude_ids, $relationships['not-related']);
            }

            if(!empty($search_ids) && !empty($exclude_ids)){
                $search_ids = array_diff($search_ids, $exclude_ids);
            }

            if(!empty($search_ids)){
                // remove the current post if it's in the search ids
                $search_ids = array_diff($search_ids, array($post->ID));
                $args['post__in'] = $search_ids;
            }elseif(!empty($exclude_ids)){
                // add this post's id to the exclude list to prevent self-linking
                $exclude_ids[] = $post->ID;
                $args['post__not_in'] = $exclude_ids;
            }else{
                // make sure not to link to the current post
                $args['post__not_in'] = array($post->ID);
            }

            if($settings['term_search'] !== 'none'){
                $taxes = get_object_taxonomies($post);
                
                $query_taxes = array();
                foreach($taxes as $tax){
                    if($settings['term_search'] === 'both' || $settings['term_search'] === 'only_both'){
                        $query_taxes[] = $tax;
                    }else{
                        $hierarchical = get_taxonomy($tax)->hierarchical;
                        if( ($hierarchical && ($settings['term_search'] === 'cats' || $settings['term_search'] === 'only-cats')) ||
                            (!$hierarchical && ($settings['term_search'] === 'tags' || $settings['term_search'] === 'only-tags'))
                        ){
                            $query_taxes[] = $tax;
                        }
                    }

                    if(false !== strpos($settings['term_search'], 'only-')){
                        $top_up_related = false;
                    }
                }

                $terms = wp_get_object_terms($post->ID, $query_taxes, ['fields' => 'all_with_object_id', 'orderby' => 'count', 'order' => 'desc', 'hide_empty' => true]);
                if(!empty($terms)){
                    $tax_args = array();
                    foreach($terms as $term){
                        if(!isset($tax_args[$term->taxonomy])){
                            $tax_args[$term->taxonomy] = array(
                                'taxonomy' => $term->taxonomy,
                                'field' => 'term_id',
                                'terms' => array($term->term_id)
                            );
                        }else{
                            $tax_args[$term->taxonomy]['terms'][] = $term->term_id;
                        }
                    }
                    if(!empty($tax_args)){
                        $args['tax_query'] = array(array_values($tax_args));
                    }
                }
            }

            $tax_ignores = array();
            if(!empty($settings['cat_ignore'])){
                foreach($settings['cat_ignore'] as $id){
                    $term = get_term($id);
                    if(!empty($term) && !is_wp_error($term)){
                        $tax_ignores[$term->taxonomy][] = (int)$id;
                    }
                }
            }

            if(!empty($settings['tag_ignore'])){
                foreach($settings['tag_ignore'] as $id){
                    $term = get_term($id);
                    if(!empty($term) && !is_wp_error($term)){
                        $tax_ignores[$term->taxonomy][] = (int)$id;
                    }
                }
            }

            $term_ignores = array();
            if(!empty($tax_ignores)){
                if(isset($args['tax_query']) && !empty($args['tax_query'])){
                    $args['tax_query']['relation'] = 'AND';
                }else{
                    $args['tax_query'] = array('relation' => "AND");
                }

                foreach($tax_ignores as $tax => $ids){
                    $term_ignores[] = array(
                        'taxonomy' => $tax,
                        'field'    => 'term_id',
                        'terms'    => $ids,
                        'operator' => 'NOT IN'
                    );
                }

                $args['tax_query'] = array_merge($args['tax_query'], $term_ignores);
            }

            $posts = get_posts(
                $args
            );

            $post_count = count($posts);

            // if we aren't able to find enough posts to satisfy the limit and we're allowed to top up the widget
            if($post_count < $settings['link_count'] && $top_up_related){
                // search for posts outside of the terms so we can reach the limit
                $new_args = array(
                    'numberposts' => ($settings['link_count'] - $post_count),
                    'post_type' => Wpil_Settings::get_related_posts_active_post_types()
                );

                // include the sort order if supplied
                switch ($settings['sort_order']) {
                    case 'rand':
                        $new_args['orderby'] = 'rand';
                        break;
                    case 'newest':
                        $new_args['orderby'] = 'date';
                        $new_args['order'] = 'DESC';
                        break;
                    case 'oldest':
                        $new_args['orderby'] = 'date';
                        $new_args['order'] = 'ASC';
                        break;
                }

                // make sure the current post is on the list of ids to ignore
                $post_ids[] = $post->ID;

                $new_ignore_posts = array_merge(array_map(function($post){ return $post->ID; }, $posts), $post_ids, $exclude_ids);
                if(!empty($new_ignore_posts)){
                    $new_args['post__not_in'] = $new_ignore_posts;
                }

                if(!empty($term_ignores)){
                    $new_args['tax_query'] = array(
                        'relation' => "AND",
                        $term_ignores
                    );
                }

                $new_posts = get_posts(
                    $new_args
                );

                if(!empty($new_posts)){
                    $posts = array_merge($posts, $new_posts);
                }
            }

            if(!empty($posts)){
                foreach($posts as $found_post){
                    $wpil_post = new Wpil_Model_Post($found_post->ID);
                    if(empty($wpil_post)){
                        continue;
                    }
                    $links[] = array('post_id' => $found_post->ID, 'url' => $wpil_post->getViewLink(), 'anchor' => get_the_title($found_post), 'thumb_url' => self::extract_first_image_url($found_post));
                }
            }

            // if we're not just loading links for a preview
            if(!$doing_preview){
                // save the resultes of our efforts so we can use them again later
                self::update_related_post_data($post->ID, 'post', $links, 0);
            }
        }
    
        return $links;
    }

    /**
     * Tries to extract the first image from a post content so we can use it as a post thumbnail
     **/
    public static function extract_first_image_url($post){
        if(empty($post) || !isset($post->post_content) || empty($post->post_content)){
            return '';
        }

        $url = '';
        if(!empty(preg_match_all('/<img\s[^>]*?(?:src|srcset)="([^"]*?)"\s[^>]*?\/>/', $post->post_content, $img))){
            foreach($img[1] as $i){
                $cleaned = esc_url_raw($i);
                if(!empty($cleaned) && Wpil_Link::isInternal($i) && filter_var($i, FILTER_VALIDATE_URL) !== false){
                    $url = $cleaned;
                    break;
                }
            }
        }

        return $url;
    }

    /**
     * Add the Related Posts metabox
     * 
     * @param string $post_id Post ID
     * @return void
     **/
    public static function add_related_posts_metabox() {
        if(!Wpil_Settings::get_related_post_settings('active')){
            return;
        }
        foreach(Wpil_Settings::get_related_posts_active_post_types() as $post_type){
            add_meta_box(
                'related_posts_metabox',
                __('Link Whisper Related Posts', 'wpil'),
                array(__CLASS__, 'related_posts_metabox_callback'),
                $post_type
            );
        }
    }

    /**
     * Saves data when the post is updated.
     * Currently, only saves if the related posts metabox is active so that it updates if the user clicks the "Update Post" button
     **/
    public static function save_related_posts_metabox($post_id){
        $is_autosave = wp_is_post_autosave($post_id);
        $is_revision = wp_is_post_revision($post_id);
        $is_valid_nonce = (isset($_POST[ 'link_whisper_related_posts_nonce']) && wp_verify_nonce($_POST['link_whisper_related_posts_nonce'], get_current_user_id() . 'wpil_related_post_metabox_nonce')) ? true : false;

        if($is_autosave || $is_revision || !$is_valid_nonce){
            return;
        }

        if(isset($_POST['wpil_related_posts_active'])){
            update_post_meta( $post_id, 'wpil_related_posts_active', sanitize_text_field($_POST['wpil_related_posts_active']));
        }
    }

    /**
     * Callback for the Related Post metabox
     * 
     * @param object $post Post object
     * @param array  $meta Post meta
     * @return void
     **/
    public static function related_posts_metabox_callback( $post, $meta ) {
        $links = self::get_related_post_links($post);
        $related_active = Wpil_Settings::related_posts_active($post->ID);
        ?>
        <div id="link-whisper-related-posts-container" class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-target-parent wpil-tooltip-target#related_posts_metabox wpil-tooltip-no-position" <?php echo Wpil_Toolbox::generate_tooltip_text('related-posts-intro'); ?>>
            <input type="hidden" id="link-whisper-related-posts-nonce" name="link_whisper_related_posts_nonce" value="<?php echo wp_create_nonce(get_current_user_id() . 'wpil_related_post_metabox_nonce')?>">
            <input type="hidden" id="link-whisper-related-posts-current-post" value="<?php echo intval($post->ID); ?>">
            <br>
            <label class="wpil-is-tooltipped wpil-no-scale" <?php echo Wpil_Toolbox::generate_tooltip_text('related-posts-active'); ?>>
                <input type="hidden" name="wpil_related_posts_active" value="0">
                <input type="checkbox" id="link-whisper-related-posts-enable" name="wpil_related_posts_active" value="1" <?php echo ($related_active) ? 'checked="checked"': '';?>>
                <?php echo sprintf(__('Display %s widget on this post', 'wpil'), '<strong>' . __('Link Whisper Related Posts', 'wpil') . '</strong>'); ?>
            </label>
            <br>
            <div style="margin-top: 3px;">
                <em class="wpil-related-posts-enable-helptext unchecked <?php echo ($related_active) ? 'hidden': '';?>"><?php esc_html_e('(Checking this will enable the Related Posts widget for this post, but will not turn it on for other posts)', 'wpil'); ?></em>
                <em class="wpil-related-posts-enable-helptext checked <?php echo (!$related_active) ? 'hidden': '';?>"><?php esc_html_e('(Unchecking this will disable the Related Posts widget for this post, but will not turn it off for other posts)', 'wpil'); ?></em>
            </div>
            <br>
            <br>
            <br>
            <div id="link-whisper-related-posts-content-container" class="<?php echo (!$related_active) ? 'wpil-section-disabled hidden': '';?>">
                <div class="wpil-is-tooltipped wpil-no-scale" <?php echo Wpil_Toolbox::generate_tooltip_text('related-posts-current-related'); ?>>
                    <div>
                        <strong>
                            <span style="font-size: 16px;"><?php esc_html_e('Current Related Posts:', 'wpil'); ?></span>
                        </strong>
                        <br>
                        <em><?php esc_html_e('(To remove a related post, uncheck it and click the "Update Related Posts" button)', 'wpil'); ?></em>
                    </div>
                    <div id="link-whisper-related-posts-links">
                        <ul id="link-whisper-related-posts-link-list" class="form-no-clear" >
                        <?php
                        foreach($links as $link){ ?>
                            <li class="link-whisper-related-posts-item">
                                <label class="selectit">
                                    <input id="link-whisper-related-posts-entry-<?php echo esc_attr($link['post_id']); ?>" data-post-id="<?php echo intval($link['post_id']); ?>" value="<?php echo esc_attr(json_encode($link)); ?>" type="checkbox" name="link_whisper_related_post[]" checked="checked">
                                    <?php echo esc_html($link['anchor']); ?>
                                </label>
                            </li>
                            <?php
                        } ?>
                        </ul>
                    </div>
                </div>
                <br>
                <div class="wpil-is-tooltipped wpil-no-scale" <?php echo Wpil_Toolbox::generate_tooltip_text('related-posts-search'); ?>>
                    <div>
                        <strong>
                            <span style="font-size: 16px;"><?php esc_html_e('Search For Posts:', 'wpil'); ?></span>
                        </strong>
                        <br>
                        <em><?php esc_html_e('(Type a few letters/words to search for posts to link to)', 'wpil'); ?></em>
                    </div>
                    <br>
                    <div id="link-whisper-related-posts-search-container">
                        <input type="text" id="link-whisper-related-posts-search" placeholder="<?php esc_html_e('Search Posts...', 'wpil'); ?>">
                        <div style="display:none;" class="link-whisper-related-post-loading la-ball-clip-rotate la-mid"><div></div></div>
                    </div>
                    <br>
                    <div id="link-whisper-related-posts-search-results-container">
                        <div id="link-whisper-related-posts-search-results-title" style="display: none;">
                            <strong>
                                <span style="font-size: 16px;"><?php esc_html_e('Search Results:', 'wpil'); ?></span>
                            </strong>
                            <br>
                            <em><?php esc_html_e('(To add related posts, select the ones you want from the search list, click the "Add Posts" button, and then click the "Update Related Posts" button)', 'wpil'); ?></em>
                        </div>
                        <br>
                        <div id="link-whisper-related-posts-search-results"></div>
                        <br>
                        <input type="button" id="link-whisper-related-posts-add-posts" style="display: none;" class="button btn disabled" value="<?php esc_attr_e('Add Posts', 'wpil'); ?>">
                    </div>
                </div>
            </div>
            <br>
            <br>
            <input type="button" id="link-whisper-related-posts-save" class="button btn disabled wpil-is-tooltipped wpil-no-scale" <?php echo Wpil_Toolbox::generate_tooltip_text('related-posts-update'); ?> value="<?php esc_attr_e('Update Related Posts', 'wpil'); ?>">
        </div>
        <?php
    }

    public static function ajax_search_related_posts(){
        global $wpdb;

        Wpil_Base::verify_nonce('wpil_related_post_metabox_nonce');

        if(!isset($_POST['search']) || empty($_POST['search'])){
            wp_send_json('no_search');
        }

        $search_results = array('success' => array('content' => __('No Results Found', 'wpil'), 'found' => 0));

        $search_words = array_map(function($word){
            global $wpdb;
            return $wpdb->prepare('%s', Wpil_Toolbox::esc_like($word));
        }, array_filter(array_map('trim', explode(' ', $_POST['search']))));

        if(empty($search_words)){
            wp_send_json($search_results);
        }

        $exclude = '';
        if(isset($_POST['selected_ids']) && !empty($_POST['selected_ids'])){
            $exclude_ids = array_filter(array_map(function($id){ return intval($id); }, $_POST['selected_ids']));
            if(!empty($exclude_ids)){
                $exclude = " AND `ID` NOT IN (" . implode(',', $exclude_ids) . ")";
            }
        }

        $search_words = "AND `post_title` LIKE " . implode(" AND `post_title` LIKE ", $search_words);
        $post_types = Wpil_Query::postTypes();
        $post_status = Wpil_Query::postStatuses();

        $posts = $wpdb->get_results("SELECT `ID`, `post_title` FROM {$wpdb->posts} WHERE 1=1 {$post_types} {$post_status} {$exclude} {$search_words} ORDER BY `ID` DESC LIMIT 15");

        if(!empty($posts)){
            $results = array();
            foreach($posts as $post){
                if(!empty($post)){
                    $dat = array(
                        'post_id' => $post->ID,
                        'url' => get_permalink($post->ID),
                        'anchor' => $post->post_title
                    );

                    // add the related post item to the results
                    $results[] = 
                    '<li class="link-whisper-related-posts-item">
                        <label class="selectit">
                            <input id="link-whisper-related-posts-entry-' . $dat['post_id'] . '" data-post-id="' . intval($dat['post_id']) . '" value="' . esc_attr(json_encode($dat)) . '" type="checkbox" name="link_whisper_related_post[]">
                            ' . esc_html($dat['anchor']) .' (Published: '.get_the_time(get_option('date_format'), $post->ID).')
                        </label>
                    </li>';
                }
            }

            if(!empty($results)){
                $search_results['success']['content'] = $results;
                $search_results['success']['found'] = count($results);
            }
        }

        wp_send_json($search_results);
    }

    public static function ajax_save_related_posts(){
        Wpil_Base::verify_nonce('wpil_related_post_metabox_nonce');

        if(!isset($_POST['post_id']) || empty($_POST['post_id'])){
            wp_send_json('no_post_id');
        }
        $post_id = (int)$_POST['post_id'];

        $ids = array();
        if(isset($_POST['selected_ids']) && !empty($_POST['selected_ids'])){
            $ids = array_filter(array_map(function($id){ return intval($id); }, $_POST['selected_ids']));
        }

        $active = isset($_POST['active']) && !empty($_POST['active']) && $_POST['active'] === 'true' ? 1: 0;
        update_post_meta($post_id, 'wpil_related_posts_active', $active);

        $links = array();

        if(!empty($ids)){
            foreach($ids as $id){
                $post = get_post($id);
                if(!empty($post) && !is_a($post, 'WP_Error')){
                    $links[] = array('post_id' => $post->ID, 'url' => get_permalink($post), 'anchor' => get_the_title($post), 'thumb_url' => self::extract_first_image_url($post));
                }
            }
        }

        self::update_related_post_data($post_id, 'post', $links, 1);
    }

    public static function get_shortcode_settings($widget = ''){
        $settings = array();
        switch($widget){
            case 'related-posts':
                $settings = Wpil_Settings::get_related_post_settings();

                // if the data is cached and doesn't have the "widget_placement" setting
                if(!isset($settings['ai_relation_threshold'])){// NOTE: So I can find this later! reset related settings, related post update, related post settings
                    // refresh the setting data
                    $settings = Wpil_Settings::get_related_post_settings('', true);
                }

                break;
        }

        return $settings;
    }

    public static function ajax_refresh_related_post_links(){
        global $wpdb;
        $related_posts = $wpdb->prefix . 'wpil_related_posts';

        Wpil_Base::verify_nonce('wpil_refresh_related_post_nonce');

        // if this is the first pass
        if(isset($_POST['initial']) && !empty($_POST['initial']) && $_POST['initial'] === 'true'){
            $context = (isset($_POST['context']) && !empty($_POST['context']) && $_POST['context'] === 'auto') ? " AND `manual_process` = 0": "";

            // de-process the existing related post links
            $wpdb->query("UPDATE {$related_posts} SET `processed` = 0 WHERE 1=1 {$context}");

            // add all posts that are not in the table
            $post_types = Wpil_Query::postTypes($wpdb->posts);
            $post_statuses = Wpil_Query::postStatuses($wpdb->posts);
            $wpdb->query(
                "INSERT INTO {$related_posts} (post_id, post_type) 
                    SELECT {$wpdb->posts}.ID, 'post' AS `post_type` 
                    FROM {$wpdb->posts}
                    WHERE NOT EXISTS ( 
                        SELECT `post_id` FROM {$related_posts} WHERE {$related_posts}.post_id = {$wpdb->posts}.ID
                    ) 
                    {$post_types}
                    {$post_statuses}");

            // remove any posts that don't exist anymore
            $wpdb->query(
                "DELETE FROM {$related_posts} 
                    WHERE NOT EXISTS ( 
                    SELECT `ID` FROM {$wpdb->posts} WHERE {$related_posts}.post_id = {$wpdb->posts}.ID
                )");

            // get a total count of the posts to process
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$related_posts} WHERE `processed` = 0");

            // this may have taken a while, so go back around again
            wp_send_json(array('continue' => true, 'processed' => 0, 'total' => $count, 'message' => __("Progress: ", 'wpil') . 0 . ' of ' . $count));
        }

        // set up the count and total variables if they aren't already set
        if(!isset($_POST['total']) || empty($_POST['total'])){
            $total = $wpdb->get_var("SELECT COUNT(*) FROM {$related_posts}");
        }else{
            $total = (int) $_POST['total'];
        }

        if(!isset($_POST['processed']) || empty($_POST['processed'])){
            $processed = $wpdb->get_var("SELECT COUNT(*) FROM {$related_posts} WHERE `processed` = 1");
        }else{
            $processed = (int) $_POST['processed'];
        }


        // now that the related post table is all set, we can begin processing the posts
        $count = 0;
        while(!Wpil_Base::overTimeLimit(0, 10)){
            $results = $wpdb->get_results("SELECT `post_id`, `post_type` FROM {$related_posts} WHERE `processed` = 0 LIMIT 100");

            foreach($results as $r_post){
                $post = get_post($r_post->post_id);

                // if there's no post or it's not actually a wp post
                if(empty($post) || !is_a($post, 'WP_Post')){
                    // mark it as processed so we can check it off the list
                    $wpdb->query("UPDATE {$related_posts} SET `processed` = 1 WHERE `post_id` = {$r_post->post_id} AND `post_type` = {$r_post->post_type}");
                    // increase the processed count
                    $count++;
                    // and continue to the next one
                    continue;
                }

                self::get_related_post_links($post, true);
                $count++;
            }

            if(empty($results)){
                $processed += $count;
                wp_send_json(array('success' => array('title' => __('Processing Complete!', 'wpil'), 'text' => __('The related post links have been refreshed!', 'wpil')), 'processed' => $processed, 'total' => $total, 'message' => __("Processing Complete!", 'wpil')));
            }
        }

        // update the processed count
        $processed += $count;

        wp_send_json(array('continue' => true, 'processed' => $processed, 'total' => $total, 'message' => __("Progress: ", 'wpil') . $processed . ' of ' . $total));
    }

    /**
     * Gets the list of posts to process for the automatic related post systems.
     * Currently limits to 100 posts at once
     **/
    public static function get_related_posts_process_list(){
        global $wpdb;
        $table = $wpdb->prefix . 'wpil_related_posts';
        $processing_limit = (time() - (7 * DAY_IN_SECONDS));

        $data = $wpdb->get_results("SELECT post_id, post_type FROM {$table} WHERE (`processed` < 1 OR `process_time` < {$processing_limit}) AND `manual_process` < 1 LIMIT 100"); // Post === not processed || process_time < limit for reprocessing

        return !empty($data) ? $data: array();
    }

    /**
     * 
     **/
    public static function append_related_posts($content){
        global $post;

        if(
            !empty($post) && 
            is_a($post, 'WP_Post') && 
            isset($post->ID) && 
            isset($post->post_type) &&
            in_array($post->post_type, Wpil_Settings::get_related_posts_active_post_types(), true) && 
            Wpil_Settings::related_posts_active($post->ID) &&
            !Wpil_Base::has_ancestor_function('dynamic_sidebar') &&
            (!defined('WPIL_RUNNING_LINK_SCAN'))
        ){
            $settings = self::get_shortcode_settings('related-posts');

            if(!empty($settings['active']) && $settings['insert_method'] === 'append'){
                if(isset($settings['widget_placement'])){
                    switch ($settings['widget_placement']) {
                        case 'top':
                            $content = (do_shortcode('[link-whisper-related-posts]') . $content);
                            break;
                        case 'paragraphs_from_top':
                            // count off the number of paragraphs to skip
                            $offset = Wpil_Suggestion::get_paragraph_offset($content, $settings['widget_placement_paragraphs'], false, false);

                            // cut the content at the dividing line
                            $part_a = mb_substr($content, 0, $offset);
                            $part_b = mb_substr($content, $offset);

                            // insert the related post shortcode and stitch it up again
                            $content = $part_a . do_shortcode('[link-whisper-related-posts]') . $part_b;
                            break;
                        case 'paragraphs_from_bottom':
                            $offset = Wpil_Suggestion::get_paragraph_offset($content, $settings['widget_placement_paragraphs'], true, false);

                            $part_a = mb_substr($content, 0, $offset);
                            $part_b = mb_substr($content, $offset);

                            $content = $part_a . do_shortcode('[link-whisper-related-posts]') . $part_b;
                            break;
                        case 'bottom':
                        default:
                            $content .= do_shortcode('[link-whisper-related-posts]');
                            break;
                    }
                }else{
                    $content .= do_shortcode('[link-whisper-related-posts]');
                }
            }
        }

        return $content;
    }

    /**
     * 
     **/
    public static function select_newest_related_post(){
        $args = array();
        $post_types = Wpil_Settings::get_related_posts_active_post_types();
        if(!empty($post_types)){
            $args['post_type'] = $post_types;
        }

        $posts = get_posts(
            $args
        );

        if(!empty($posts) && !empty($posts[0])){
            return $posts[0];
        }

        // if that didn't work, try getting the overall latest posts
        $posts = wp_get_recent_posts();
        if(!empty($posts)){
            return get_post($posts[0]['ID']);
        }

        // if that didn't work, report a failure
        return false;
    }

    /**
     * Generates the Related Posts widget styles so they can be echoed out on the Related Posts template.
     * By generating the styles here, and making them optional in the template, we're able to account for code changes while preserving template compatibility
     **/
    public static function generate_related_post_styles($settings = array()){
        if(empty($settings)){
            $settings = Wpil_Settings::get_related_post_settings();
        }

        $full_styling = $settings['styling']['full'];
        $mobile_styling = $settings['styling']['mobile'];
        $layout_count = !empty($settings['widget_layout']['count']) ? $settings['widget_layout']['count']: 1;

        $row_item_width = (int)(100/ceil($settings['link_count']/$layout_count));
        if(empty($row_item_width)){
            $row_item_width = 100;
        }
        
        $full_widget_margins = array();
        $mobile_widget_margins = array();
        
        $widget_keys = array(
            'widget-margin-top' => 'false', 
            'widget-margin-right' => 'false', 
            'widget-margin-bottom' => 'false', 
            'widget-margin-left' => 'false'
        );
        $widget_full_rules = array_intersect_key($full_styling, $widget_keys);
        $widget_mobile_rules = array_intersect_key($mobile_styling, $widget_keys);

        foreach($widget_full_rules as $id => $value){
            if($value !== 'false'){
                $rule = substr($id, strlen('widget-'));
                $full_widget_margins[] = $rule . ': ' . (int) $value . 'px;';
            }
        }

        foreach($widget_mobile_rules as $id => $value){
            if($value !== 'false'){
                $rule = substr($id, strlen('widget-'));
                $mobile_widget_margins[] = $rule . ': ' . (int) $value . 'px;';
            }
        }

        // now generate the item margins for both normal and mobile views
        $full_item_margins = array();
        $mobile_item_margins = array();

        $item_keys = array(
            'item-margin-top' => 'false', 
            'item-margin-right' => 'false', 
            'item-margin-bottom' => 'false', 
            'item-margin-left' => 'false'
        );

        $item_full_rules = array_intersect_key($full_styling, $item_keys);
        $item_mobile_rules = array_intersect_key($mobile_styling, $item_keys);

        foreach($item_full_rules as $id => $value){
            if($value !== 'false'){
                $rule = substr($id, strlen('item-'));
                $full_item_margins[] = $rule . ': ' . (int) $value . 'px;';
            }
        }

        foreach($item_mobile_rules as $id => $value){
            if($value !== 'false'){
                $rule = substr($id, strlen('item-'));
                $mobile_item_margins[] = $rule . ': ' . (int) $value . 'px;';
            }
        }

        $style = 
        '.lwrp.link-whisper-related-posts{
            ' . (!empty($full_styling['widget-background-color']) ? 'background: ' . strip_tags($full_styling['widget-background-color']) . ';': '') . '
            ' . (!empty($full_widget_margins) ? implode("\n", $full_widget_margins): '') . '
        }
        .lwrp .lwrp-title{
            ' . (!empty($full_styling['widget-title-text-color']) ? 'color: ' . strip_tags($full_styling['widget-title-text-color']) . ';': '') . '
            ' . (!empty($full_styling['widget-title-font-size']) && $full_styling['widget-title-font-size'] !== 'false' ? 'font-size: ' . (int) $full_styling['widget-title-font-size'] . 'px' . ';': '') . '
        }';

        if(isset($full_styling['widget-title-text-hover-color']) && !empty($full_styling['widget-title-text-hover-color'])){
            $style .= '.lwrp .lwrp-title:hover{
                color: ' . strip_tags($full_styling['widget-title-text-hover-color']) . ';
            }';
        }

        $style .= 
        '.lwrp .lwrp-description{
            ' . (!empty($full_styling['widget-description-text-color']) ? 'color: ' . strip_tags($full_styling['widget-description-text-color']) . ';': '') . '
            ' . (!empty($full_styling['widget-description-font-size']) && $full_styling['widget-description-font-size'] !== 'false' ? 'font-size: ' . (int) $full_styling['widget-description-font-size'] . 'px' . ';': '') . '

        }
        .lwrp .lwrp-list-container{
        }
        .lwrp .lwrp-list-multi-container{
            display: flex;
        }
        .lwrp .lwrp-list-double{
            width: 48%;
        }
        .lwrp .lwrp-list-triple{
            width: 32%;
        }
        .lwrp .lwrp-list-row-container{
            display: flex;
            justify-content: space-between;
        }
        .lwrp .lwrp-list-row-container .lwrp-list-item{
            width: calc(' . $row_item_width . '% - 20px);
        }
        .lwrp .lwrp-list-item:not(.lwrp-no-posts-message-item){
            ' . (!empty($full_styling['item-background-color']) ? 'background: ' . strip_tags($full_styling['item-background-color']) . ';': '') . '
            ' . (!empty($full_item_margins) ? implode("\n", $full_item_margins): '');
    
            if(($settings['use_thumbnail'] === 1 || $settings['use_first_img'] === 1) && !empty($settings['thumbnail_size'])){
                $style .= 'max-width: ' . $settings['thumbnail_size'] . 'px;';
            }
            
            if(
                isset($full_styling['item-list-style']) && 
                !empty($full_styling['item-list-style']) && 
                $full_styling['item-list-style'] !== 'site-default')
            {
                $style .= 'list-style: ' . strip_tags($full_styling['item-list-style']) . ';';
            }
            $style .= '
        }
        .lwrp .lwrp-list-item img{
            max-width: 100%;
            height: auto;
            object-fit: cover;
            aspect-ratio: 1 / 1;
        }
        .lwrp .lwrp-list-item.lwrp-empty-list-item{
            background: initial !important;
        }
        .lwrp .lwrp-list-item .lwrp-list-link .lwrp-list-link-title-text,
        .lwrp .lwrp-list-item .lwrp-list-no-posts-message{
            ' . (!empty($full_styling['item-title-text-color']) ? 'color: ' . strip_tags($full_styling['item-title-text-color']) . ';': '') . '
            ' . (!empty($full_styling['item-title-font-size']) && $full_styling['item-title-font-size'] !== 'false' ? 'font-size: ' . (int) $full_styling['item-title-font-size'] . 'px' . ';': '') . '
            ' . (isset($full_styling['item-title-font-weight']) && $full_styling['item-title-font-weight'] !== 'site-default' ? 'font-weight: ' . strip_tags($full_styling['item-title-font-weight']) . ';': '') . '
            ' . (isset($full_styling['item-title-font-style']) && $full_styling['item-title-font-style'] !== 'site-default' ? 'font-style: ' . strip_tags($full_styling['item-title-font-style']) . ';': '') . '
        }';

        if(isset($full_styling['item-title-text-hover-color']) && !empty($full_styling['item-title-text-hover-color'])){
            $style .= '.lwrp .lwrp-list-item .lwrp-list-link .lwrp-list-link-title-text:hover{
                color: ' . strip_tags($full_styling['item-title-text-hover-color']) . ';
            }';
        }

        $mobile_breakpoint = (isset($mobile_styling['mobile_breakpoint']) && !empty($mobile_styling['mobile_breakpoint'])) ? (int) $mobile_styling['mobile_breakpoint']: 480;

        $style .= 
        '@media screen and (max-width: ' . $mobile_breakpoint . 'px) {
            .lwrp.link-whisper-related-posts{
                ' . (!empty($mobile_styling['widget-background-color']) ? 'background: ' . strip_tags($mobile_styling['widget-background-color']) . ';': '') . '
                ' . (!empty($mobile_widget_margins) ? implode("\n", $mobile_widget_margins): '') . '
            }
            .lwrp .lwrp-title{
                ' . (!empty($mobile_styling['widget-title-text-color']) ? 'color: ' . strip_tags($mobile_styling['widget-title-text-color']) . ';': '') . '
                ' . (!empty($mobile_styling['widget-title-font-size']) && $mobile_styling['widget-title-font-size'] !== 'false' ? 'font-size: ' . (int) $mobile_styling['widget-title-font-size'] . 'px' . ';': '') . '
            }';

            if(isset($mobile_styling['widget-title-text-hover-color']) && !empty($mobile_styling['widget-title-text-hover-color'])){
                $style .= '.lwrp .lwrp-title:hover{
                    color: ' . strip_tags($mobile_styling['widget-title-text-hover-color']) . ';
                }';
            }

            $style .= 
            '.lwrp .lwrp-description{
                ' . (!empty($mobile_styling['widget-description-text-color']) ? 'color: ' . strip_tags($mobile_styling['widget-description-text-color']) . ';': '') . '
                ' . (!empty($mobile_styling['widget-description-font-size']) && $mobile_styling['widget-description-font-size'] !== 'false' ? 'font-size: ' . (int) $mobile_styling['widget-description-font-size'] . 'px' . ';': '') . '
            }
            .lwrp .lwrp-list-multi-container{
                flex-direction: column;
            }
            .lwrp .lwrp-list-multi-container ul.lwrp-list{
                margin-top: 0px;
                margin-bottom: 0px;
                padding-top: 0px;
                padding-bottom: 0px;
            }
            .lwrp .lwrp-list-double,
            .lwrp .lwrp-list-triple{
                width: 100%;
            }
            .lwrp .lwrp-list-row-container{
                justify-content: initial;
                flex-direction: column;
            }
            .lwrp .lwrp-list-row-container .lwrp-list-item{
                width: 100%;
            }
            .lwrp .lwrp-list-item:not(.lwrp-no-posts-message-item){
                ' . (!empty($mobile_styling['item-background-color']) ? 'background: ' . strip_tags($mobile_styling['item-background-color']) . ';': '') . '
                ' . (!empty($mobile_item_margins) ? implode("\n", $mobile_item_margins): '');
        
                if(($settings['use_thumbnail'] === 1 || $settings['use_first_img'] === 1) && !empty($settings['thumbnail_size'])){
                    $style .= 'max-width: initial;';
                }
                
                if(
                    isset($mobile_styling['item-list-style']) && 
                    !empty($mobile_styling['item-list-style']) && 
                    $mobile_styling['item-list-style'] !== 'site-default')
                {
                    $style .= 'list-style: ' . strip_tags($mobile_styling['item-list-style']) . ';';
                }
                $style .= '
            }
            .lwrp .lwrp-list-item .lwrp-list-link .lwrp-list-link-title-text,
            .lwrp .lwrp-list-item .lwrp-list-no-posts-message{
                ' . (!empty($mobile_styling['item-title-text-color']) ? 'color: ' . strip_tags($mobile_styling['item-title-text-color']) . ';': '') . '
                ' . (!empty($mobile_styling['item-title-font-size']) && $mobile_styling['item-title-font-size'] !== 'false' ? 'font-size: ' . (int) $mobile_styling['item-title-font-size'] . 'px' . ';': '') . '
                ' . (isset($mobile_styling['item-title-font-weight']) && $mobile_styling['item-title-font-weight'] !== 'site-default' ? 'font-weight: ' . strip_tags($mobile_styling['item-title-font-weight']) . ';': '') . '
                ' . (isset($mobile_styling['item-title-font-style']) && $mobile_styling['item-title-font-style'] !== 'site-default' ? 'font-style: ' . strip_tags($mobile_styling['item-title-font-style']) . ';': '') . '
            }';

            if(isset($mobile_styling['item-title-text-hover-color']) && !empty($mobile_styling['item-title-text-hover-color'])){
                $style .= '.lwrp .lwrp-list-item .lwrp-list-link .lwrp-list-link-title-text:hover{
                    color: ' . strip_tags($mobile_styling['item-title-text-hover-color']) . ';
                }';
            }

            $style .= ';
        }';

        return $style;
    }
}