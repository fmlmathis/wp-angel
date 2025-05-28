<?php

/**
 * Work with keywords
 */
class Wpil_Keyword
{

    static $ghost_link_cleanup = array();
    static $keyword_id_cache = null;
    static $keyword_url_cache = null;

    public function register()
    {
        add_action('wp_ajax_wpil_keyword_delete', [$this, 'delete']);
        add_action('wp_ajax_wpil_keyword_add', [$this, 'add']);
        add_action('wp_ajax_wpil_keyword_reset', [$this, 'reset']);
        add_action('wp_ajax_wpil_insert_selected_keyword_links', [$this, 'insertSelectedLinks']);
        add_action('wp_ajax_wpil_bulk_keyword_add', [$this, 'bulk_create_autolinks']);
        add_action('wp_ajax_wpil_bulk_keyword_process', [$this, 'bulk_process_autolinks']);
        add_action('wp_ajax_get_keyword_dropdown_data', array($this, 'ajax_get_keyword_dropdown_data'));
        add_action('wp_ajax_wpil_import_autolink_keywords', array($this, 'ajax_import_target_keywords_as_rules'));
        add_action('wp_ajax_wpil_run_autolink_import_create_scan', array($this, 'ajax_run_autolink_import_create_scan'));
        add_filter('screen_settings', array(__CLASS__, 'show_screen_options'), 11, 2);
        add_filter('set_screen_option_wpil_keyword_options', array(__CLASS__, 'saveOptions'), 12, 3);
        add_filter('wpil_process_keyword_list', array(__CLASS__, 'processKeywords'), 10, 1);
        add_filter('wpil_direct_add_keyword', array(__CLASS__, 'directStore'), 10, 1);
        self::init_cron();
    }

    /**
     * Inits cron
     **/
    public static function init_cron(){
        if(Wpil_Settings::autolinking_cron_task_enabled()){
            add_filter('cron_schedules', array(__CLASS__, 'add_autolinking_schedules'));
            add_action('admin_init', array(__CLASS__, 'schedule_autolink_tasks'));
            add_action('wpil_autolink_insert_cron', array(__CLASS__, 'do_scheduled_autolink_insert'));
        }else{
            self::clear_cron_schedules();
        }
    }

    public static function add_autolinking_schedules($schedules){
        if(!isset($schedules['5min'])){
            $schedules['5min'] = array(
                'interval' => 60 * 5,
                'display' => __('Every five Minutes', 'wpil')
            );
        }
        return $schedules;
    }

    /**
     * Schedules the broken link checks if the user hasn't disabled checking.
     * If the user has, then it disables the checks
     **/
    public static function schedule_autolink_tasks(){
        if(Wpil_Settings::autolinking_cron_task_enabled()){
            if(!wp_get_schedule('wpil_autolink_insert_cron')){
                wp_schedule_event(time(), '5min', 'wpil_autolink_insert_cron');
            }
        }elseif(wp_get_schedule('wpil_autolink_insert_cron')){
            self::clear_cron_schedules();
        }
    }

    public static function clear_cron_schedules(){
        $timestamp = wp_next_scheduled('wpil_autolink_insert_cron');
        wp_unschedule_event($timestamp, 'wpil_autolink_insert_cron');
    }

    /**
     * Goes over the posts on the site, and inserts autolinks post by post
     **/
    public static function do_scheduled_autolink_insert(){
        // first get the offset post
        $offset = (int)get_option('wpil_autolink_cron_offset', 0);
        $type   = get_option('wpil_autolink_cron_type', 'post');

        // next get the batch of posts that we'll be autolinking
        $batch = self::get_autolinking_cron_batch($offset, $type);

        // get the last id processed
        $last_id = get_option('wpil_autolink_cron_last_pid', null);

        // set the timelimit
        $limit = (defined('DOING_AJAX') && DOING_AJAX) ? 20: 60;

        $id = 0;
        if(!empty($batch)){
            foreach($batch as $id){
                if(Wpil_Base::overTimeLimit(10, $limit)){
                    break;
                }

                $post = new Wpil_Model_Post($id, $type);
                self::refresh_post_keywords($post);
                self::addKeywordsToPost($post);
            }
        }

        $pid = $type . '_' . $id;

        // if this is the same post as the last batch
        if($last_id === $pid){
            // if there's only one item left or there aren't any more in the batch
            if(empty($batch) || count($batch) < 2){
                $ind = false;
            }else{
                $ind = array_search($id, $batch);
                if($ind !== false){
                    $slice = array_slice($batch, $ind);
                    if(!empty($slice) && count($slice) > 1){
                        next($slice);
                        $ind = key($slice);
                    }else{
                        $ind = false;
                    }
                }
            }
        }else{
            $ind = array_search($id, $batch);
        }
        
        update_option('wpil_autolink_cron_last_pid', $pid);

        if(false !== $ind){
            update_option('wpil_autolink_cron_offset', $batch[$ind]);
        }else{
            if($type === 'post'){
                $type = 'term';
                get_transient('wpil_autolinking_cron_post_batch');
                
            }else{
                $type = 'post';
                delete_transient('wpil_autolinking_cron_term_batch');
            }

            update_option('wpil_autolink_cron_offset', 0);
            update_option('wpil_autolink_cron_type', $type);

            // if we've completed the 'posts'
            if($type === 'term'){
                // set a flag for this
                update_option('wpil_autolink_cron_completed_posts', 1);
            }
        }
    }

    /**
     * Gets the current batch of ids that we'll be processing in the autolinking cron task!
     * @param int $offset The last id that was processed by the cron task
     * @param string $type The current post type that we're processing (post|term)
     **/
    public static function get_autolinking_cron_batch($offset = 0, $type = 'post'){
        global $wpdb;
        $link_report_table = $wpdb->prefix . 'wpil_report_links';

        $batch_posts = get_transient('wpil_autolinking_cron_post_batch');
        $batch_terms = get_transient('wpil_autolinking_cron_term_batch');

        if(empty($batch_posts) && empty($batch_terms)){
            delete_option('wpil_autolink_cron_last_pid'); // clear the last post processed flag

            $batch_posts = array();
            $batch_terms = array();

            $ignore_string = '';
            $ignored_ids = Wpil_Settings::getItemTypeIds(Wpil_Settings::getIgnoreOrphanedPosts(), 'post');
    
            if(!empty($ignored_ids)){
                $ignore_string = " AND ID NOT IN ('" . implode("', '", $ignored_ids) . "')";
            }
    
            $statuses_query = Wpil_Query::postStatuses('a');
            $post_types = Wpil_Query::postTypes('a');

            $ids        = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} a WHERE a.ID IN (select distinct `post_id` from {$link_report_table} where `post_type` = 'post') {$ignore_string} {$ignore_string} {$statuses_query} {$post_types} ORDER BY a.ID DESC");
            $term_ids   = $wpdb->get_col("SELECT a.term_id FROM {$wpdb->terms} a WHERE a.term_id IN (select distinct `post_id` from {$link_report_table} where `post_type` = 'term') ORDER BY a.term_id DESC");

            if(!empty($ids)){
                $batch_posts = $ids;
            }

            if(!empty($term_ids)){
                $batch_terms = $term_ids;
            }

            update_option('wpil_cron_autolink_total_batch_size', (count($batch_posts) + count($batch_terms)));
        }

        if(!empty($offset)){
            if($type === 'post'){
                $ind = array_search($offset, $batch_posts);
                $batch_posts = (false !== $ind && isset($batch_posts[$ind])) ? array_slice($batch_posts, $ind): array();
            }else{
                $ind = array_search($offset, $batch_terms);
                $batch_terms = (false !== $ind && isset($batch_terms[$ind])) ? array_slice($batch_terms, $ind): array();
            }
        }

        set_transient('wpil_autolinking_cron_post_batch', $batch_posts, 6 * HOUR_IN_SECONDS);
        set_transient('wpil_autolinking_cron_term_batch', $batch_terms, 6 * HOUR_IN_SECONDS);
        return ($type === 'post') ? $batch_posts: $batch_terms;
    }


    /**
     * Show settings page
     */
    public static function init()
    {
        if (!empty($_POST['save_settings'])) {
            self::saveSettings();
        }

        $user = wp_get_current_user();
        $reset = !empty(get_option('wpil_keywords_reset'));
        $table = new Wpil_Table_Keyword();
        $table->prepare_items();
        include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/keywords.php';
    }

    public static function show_screen_options($settings, $screen_obj){

        $screen = get_current_screen();
        $options = get_user_meta(get_current_user_id(), 'wpil_keyword_options', true);

        // exit if we're not on the target keywords page
        if(!is_object($screen) || $screen->id != 'link-whisper_page_link_whisper_keywords'){
            return $settings;
        }

        // Check if the screen options have been saved. If so, use the saved value. Otherwise, use the default values.
        if ( $options ) {
            $per_page = !empty($options['per_page']) ? $options['per_page'] : 20 ;
            $hide_select_links = !empty($options['hide_select_links_column']) && $options['hide_select_links_column'] != 'off';
        } else {
            $per_page = 20;
            $hide_select_links = false;
        }

        //get apply button
        $button = get_submit_button( __( 'Apply', 'wp-screen-options-framework' ), 'primary large', 'screen-options-apply', false );

        //show HTML form
        ob_start();
        include WP_INTERNAL_LINKING_PLUGIN_DIR . 'templates/keyword_options.php';
        return ob_get_clean();
    }

    public static function saveOptions($status, $option, $value) {
        if(!wp_verify_nonce($_POST['screenoptionnonce'], 'screen-options-nonce')){
            return;
        }

        if ($option == 'wpil_keyword_options') {
            $value = [];
            if (isset( $_POST['wpil_keyword_options'] ) && is_array( $_POST['wpil_keyword_options'] )) {
                if (!isset($_POST['wpil_keyword_options']['hide_select_links_column'])) {
                    $_POST['wpil_keyword_options']['hide_select_links_column'] = 'off';
                }
                $value = $_POST['wpil_keyword_options'];
            }

            return $value;
        }

        return $status;
    }

    /**
     * Add new keyword
     */
    public static function add()
    {
        Wpil_Base::verify_nonce('wpil_keyword');

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // Remove any hooks that may interfere with AJAX requests
        Wpil_Base::remove_problem_hooks();

        if (!empty($_POST['keyword_id'])) {
            if (isset($_POST['wpil_keywords_add_same_link']) && isset($_POST['wpil_keywords_link_once'])) {
                self::updateKeywordSettings();
            }

            $keyword = (is_array($_POST['keyword_id'])) ? array_map(function($id){ return (int)$id; }, $_POST['keyword_id']) : self::getKeywordByID((int)$_POST['keyword_id']);
        } else {
            $keyword = self::store();
        }

        // if the autolinking process has been disabled
        if(Wpil_Settings::autolinking_insert_run_disabled()){
            // exit now
            wp_send_json([
                'progress' => 100,
                'finish' => true
            ]);
        }
        
        if(!is_array($keyword)){
            self::checkPosts($keyword);
        }else{
            self::processKeywords($keyword);
        }
    }

    /**
     * Runs the autolink creation process for existing keywords based on keyword id.
     * Accepts an array of ids to process, and removes ids from the array as they are processed.
     * If there isn't enough time to complete the processing run, 
     * the ids to be processed are returned so they can be sent for another processing run.
     * 
     * @param array $ids An array of keyword ids to run the content insertion process for.
     * @return array|bool Returns unprocessed ids if there's more to process, an empty array when all ids are processed, and false if no ids are supplied.
     */
    public static function processKeywords($ids = array())
    {
        if(empty($ids)){
            return false;
        }

        $keyword_total = (empty($_POST['keyword_total'])) ? count($ids) + .1: (int)$_POST['keyword_total']; // the number of keywords to process
        $loop = (array_key_exists('loop', $_POST)) ? ((int)$_POST['loop'] + 1) : 0;
        $total = !empty($_POST['total']) ? (int)$_POST['total'] + 0.1 : 0.1; // The TOTAL number of posts to process for the keyword
        $doing_refresh = (isset($_POST['refresh']) && !empty($_POST['refresh'])) ? true: false;
        $last_keyword = (isset($_POST['last_keyword']) && !empty($_POST['last_keyword'])) ? (int)$_POST['last_keyword']: false;

        // if a single id was given, wrap it in an array
        if(is_int($ids)){
            $ids = array($ids);
        }

        // sort the ids by priority if applicable
        $ids = self::sort_keyword_ids_by_insertion_priority($ids);

        // get the memory limit
        $memory_break_point = Wpil_Report::get_mem_break_point();

        // loop over the ids
        foreach($ids as $key => $id){
            // try getting the keyword from the DB
            $keyword = self::getKeywordByID((int)$id);

            // if we're close to the time limit or the memory limit, exit
            if(Wpil_Base::overTimeLimit(25, 30) || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point) ){
                break;
            }

            // skip to the next id if there's no keyword
            if(empty($keyword)){
                unset($ids[$key]);
                // also reset the loop
                $loop = 0;
                continue;
            }

            if($doing_refresh && $last_keyword != $id){
                self::refreshAppliedKeyword($keyword);
            }

            // run the autolink insertion process for a batch of posts using this keyword
            $results = self::checkPosts($keyword, true);

            // update the total count
            $total = $results['total'];

            // if all of the autolinks have been inserted
            if($results['finish']){
                // remove the current id from the list and proceed to the next one
                unset($ids[$key]);
                $loop = 0;
            }else{
                // if we have more posts to go over, break out of the loop
                break;
            }

        }

        $display_message = false;
        if(!empty($keyword) && !empty($ids)){
            // alternate the insert message between the percentage for the current keyword, and the total number of keywords
            if($loop % 3 === 0){
                $display_message = sprintf(__('%d of %d Autolink Rules Processed', 'wpil'), ((int)$keyword_total - count($ids)), (int)$keyword_total);
            }else{
                $display_message = sprintf(__('Creating "%s" autolinks: %d%% complete', 'wpil'), $keyword->keyword, $results['progress']);
            }
        }elseif(empty($ids)){ // if there's no keyword and no ids, we must be finishing up
            $display_message = __('Finishing up', 'wpil');
        }

        // maybe optimize the options table
        Wpil_Toolbox::maybe_optimize_options_table();

        $percent_complete = 0;
        if(!empty($keyword_total)){
            $percent_complete = floor((count($ids) / $keyword_total) * 100);
        }

        // return any remaining ids so they can be processed on another run
        wp_send_json([
            'nonce' => isset($_POST['nonce']) ? $_POST['nonce']: false,
            'displayMessage' => $display_message,
            'last_keyword' => (!empty($keyword) && !empty($ids)) ? $keyword->id: null,
            'keyword_id' => $ids,
            'progress' => 100 - $percent_complete,
            'total' => $total,
            'keyword_total' => $keyword_total,
            'loop' => $loop,
            'finish' => empty($ids)
        ]);
    }

    /**
     * Creates autolinks in bulk.
     * Doesn't handle keyword updating
     **/
    public static function bulk_create_autolinks(){
        Wpil_Base::verify_nonce('wpil_keyword');

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // Remove any hooks that may interfere with AJAX requests
        Wpil_Base::remove_problem_hooks();

        // create the bulk keywords
        $keywords = self::bulk_store();

        // if the user has submitted so many keywords that we can't do them all in one go
        if(isset($_POST['partial_insert']) && !empty($_POST['partial_insert'])){
            // return the inserted keywords so we can try another pass
            $ids = !empty($keywords) ? $keywords: false;
            wp_send_json(array('partial' => true, 'keyword_ids' => $ids));
        }

        if(!empty($keywords)){
            self::processKeywords($keywords);
        }else{
            wp_send_json(array('error' => array('title' => __('Autolink Data Not Saving', 'wpil'), 'text' => __('The autolink data could not be saved to the database. This could be caused by the autolink data already existing or an error in the data\'s format, please reload the page and check to make sure the data is appropriately formatted.', 'wpil'))));
        }
    }

    /**
     * Bulk inserts a list of autolinks based on keyword id
     **/
    public static function bulk_process_autolinks(){
        Wpil_Base::verify_nonce('wpil_keyword');

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // Remove any hooks that may interfere with AJAX requests
        Wpil_Base::remove_problem_hooks();

        if(isset($_POST['keyword_ids']) && !empty($_POST['keyword_ids']) && is_array($_POST['keyword_ids'])){
            $_POST['keyword_ids'] = array_map(function($id){ return (int) $id;}, $_POST['keyword_ids']);
        }else{
            wp_send_json(array('error' => array('title' => __('Processing Error', 'wpil'), 'text' => __('No autolink ids detected in the queue. Please reload the page and see if the autolink rules have been created.', 'wpil'))));
        }

        self::processKeywords($_POST['keyword_ids']);
    }

    /**
     * Reset links data
     */
    public static function reset()
    {
        global $wpdb;

        //verify input data
        Wpil_Base::verify_nonce('wpil_keyword');
        if (empty($_POST['count']) || (int)$_POST['count'] > 9999) {
            wp_send_json([
                'nonce' => $_POST['nonce'],
                'finish' => true
            ]);
        }

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // Remove any hooks that may interfere with AJAX requests
        Wpil_Base::remove_problem_hooks();

        $memory_break_point = Wpil_Report::get_mem_break_point();
        $total = !empty($_POST['total']) ? (int)$_POST['total'] : 1;

        if ($_POST['count'] == 1) {
            //make matched posts array on the first call
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}wpil_keyword_links");
            $statuses_query = Wpil_Query::postStatuses();
            $posts = $wpdb->get_results("SELECT ID as id, 'post' as type FROM {$wpdb->posts} WHERE post_content LIKE '%wpil_keyword_link%' $statuses_query");
            $posts = self::getLinkedPostsFromAlternateLocations($posts);
            $taxonomies = Wpil_Settings::getTermTypes();
            $terms = array();
            if(!empty($taxonomies)){
                $taxonomies = implode("','", $taxonomies);
                $terms = $wpdb->get_results("SELECT term_id as id, 'term' as type FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ('{$taxonomies}') AND description LIKE '%wpil_keyword_link%'");
            }
            $posts = array_merge($posts, $terms);
            $total = count($posts);
        } else {
            //get unprocessed posts
            $posts = get_option('wpil_keywords_reset', []);
            if ($total < count($posts)) {
                $total = count($posts);
            }
        }

        foreach ($posts as $key => $post) {
            $alt = (isset($post->alt)) ? true: false;
            $post = new Wpil_Model_Post($post->id, $post->type);
            if($alt){
                $content = $post->getContent();
            }else{
                $content = $post->getCleanContent();
            }

            preg_match_all('`(?:<|[\\\\]+?u003c)a [^><]*?(?:class=(?:["\']|[\\\\]+?u0022)[^"\']*?wpil_keyword_link[^"\']*?(?:["\']|[\\\\]+?u0022)|data-wpil-keyword-link=(?:["\']|[\\\\]+?u0022)linked(?:["\']|[\\\\]+?u0022))[^><]*?href=(?:["\']|[\\\\]+?u0022)([^"\']*?)(?:["\']|[\\\\]+?u0022)[^><]*?(?:>|[\\\\]+?u003e)(.*?)(?:<|[\\\\]+?u003c)[\\\\]*?\/a(?:>|[\\\\]+?u003e)|(?:<|[\\\\]+?u003c)a [^><]*?href=(?:["\']|[\\\\]+?u0022)([^"\']*?)(?:["\']|[\\\\]+?u0022)[^><]*?(?:class=(?:["\']|[\\\\]+?u0022)[^"\']*?wpil_keyword_link[^"\']*?(?:["\']|[\\\\]+?u0022)|data-wpil-keyword-link=(?:["\']|[\\\\]+?u0022)linked(?:["\']|[\\\\]+?u0022))[^><]*?(?:>|[\\\\]+?u003e)(.*?)(?:<|[\\\\]+?u003c)[\\\\]*?\/a(?:>|[\\\\]+?u003e)`i', $content, $matches);
            for ($i = 0; $i < count($matches[0]); $i++) {

                if(!empty($matches[1][$i]) && !empty($matches[2][$i])){
                    $link = $matches[1][$i];
                    $keyword = $matches[2][$i];
                }

                if(!empty($matches[3][$i]) && !empty($matches[4][$i])){
                    $link = $matches[3][$i];
                    $keyword = $matches[4][$i];
                }

                // TODO: wait for more feedback and think about this. We don't want to change the URL before saving it since that would cause us to create repeating autolinks that don't actually exist.
                /*
                if(!empty($link) && false){
                    $cleaned = esc_url_raw($link);
                    if(!empty($cleaned) && (false === strpos($link, 'http') && 1 !== strpos($link, '/'))){
                        $link = $cleaned;
                    }else{

                    }
                }*/

                if (!empty($link) && (0 !== strpos($link, 'http') || 1 !== strpos($link, '/')) && !empty($keyword)) {
                    $keyword_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}wpil_keywords WHERE (keyword = %s OR keyword = %s) AND link = %s", $keyword, trim($keyword), $link));
                    if (empty($keyword_id)) {
                        //create new keyword
                        $wpdb->insert($wpdb->prefix . 'wpil_keywords', [
                            'keyword' => $keyword,
                            'link' => $link,
                            'add_same_link' => get_option('wpil_keywords_add_same_link'),
                            'link_once' => get_option('wpil_keywords_link_once'),
                            'restrict_to_live' => get_option('wpil_keywords_restrict_to_live', 1),
                            'select_links' => 1, // always make this links user-selectable so that we don't start popping up links all over the placce
                            'limit_inserts' => get_option('wpil_keywords_limit_inserts'),
                            'insert_limit' => get_option('wpil_keywords_insert_limit'),
                            'prioritize_longtail' => get_option('wpil_keywords_prioritize_longtail'),
                            'same_lang' => get_option('wpil_keywords_same_lang', 0),
                            'set_priority' => get_option('wpil_keywords_set_priority', 0),
                            'restrict_cats' => get_option('wpil_keywords_restrict_to_cats'),
                            'case_sensitive' => get_option('wpil_keywords_case_sensitive'),
                        ]);
                        $keyword_id = $wpdb->insert_id;
                    }

                    $wpdb->insert($wpdb->prefix . 'wpil_keyword_links', [
                        'keyword_id' => $keyword_id,
                        'post_id' => $post->id,
                        'post_type' => $post->type,
                        'anchor' => $keyword,
                    ]);
                }
            }

            unset($posts[$key]);

            //break process if limits were reached
            if (Wpil_Base::overTimeLimit(7, 15) || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point)) {
                update_option('wpil_keywords_reset', $posts);
                Wpil_Base::update_option_cache('wpil_keywords_reset', $posts);
                break;
            }
        }

        if (empty($posts)) {
            update_option('wpil_keywords_reset', []);
            Wpil_Base::update_option_cache('wpil_keywords_reset', []);
        }

        wp_send_json([
            'nonce' => $_POST['nonce'],
            'ready' => $total - count($posts),
            'count' => ++$_POST['count'],
            'total' => $total,
            'finish' => empty($posts)
        ]);
    }

    /**
     * Inserts the links the user has selected from the autolink report page
     **/
    public static function insertSelectedLinks(){
        Wpil_Base::verify_nonce('wpil_keyword');

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // Remove any hooks that may interfere with AJAX requests
        Wpil_Base::remove_problem_hooks();

        $selected_ids = array_map(function($id){ return (int)$id; }, $_POST['link_ids']);

        if(empty($selected_ids)){
            wp_send_json(array('error' => array('title' => __('No Links Selected', 'wpil'), 'text' => __('Please select some links to insert', 'wpil'))));
        }

        $insert = array();
        $links = self::getPossibleLinksByID($selected_ids);
        $keyword_cache = array();

        foreach($links as $link){
            $post = (object) array('id' => $link->post_id, 'type' => $link->post_type);

            if(isset($link->keyword_id) && !isset($keyword_cache[$link->keyword_id])){
                $keyword_cache[$link->keyword_id] = self::getKeywordByID($link->keyword_id);
            }

            if(isset($keyword_cache[$link->keyword_id])){
                // add the link to the list of links to create
                $insert[$post->id . '_' . $post->type][] = maybe_unserialize($link->meta_data);

                // save the link ref to the db
                self::saveLinkToDB($keyword_cache[$link->keyword_id], $post, $link->case_keyword);

                // and remove the link from the potential list
                self::deletePossibleLinksById($link->id);
            }
        }

        //add links to all editors
        if (!empty($insert)) {
            // unhook the link adding to content from the post data insert action so duplicates aren't inserted
            Wpil_Base::remove_hooked_function('wp_insert_post_data', 'Wpil_Post', 'addLinksToContent', 9999);
            Wpil_Base::remove_hooked_function('wp_insert_post_data', 'Wpil_Post', 'addAutolinksToInsertRun', 998);

            foreach($insert as $key => $meta){
                $post = explode('_', $key);

                if ($post[1] == 'term') {
                    // add any existing links to insert to the metadata
                    $existing_links = Wpil_Toolbox::get_encoded_term_meta($post[0], 'wpil_links', true);
                    $meta = (!empty($existing_links) && is_array($existing_links)) ? array_merge($meta, $existing_links): $meta;
                    // update the stored meta
                    Wpil_Toolbox::update_encoded_term_meta($post[0], 'wpil_links', $meta);
                    Wpil_Term::addLinksToTerm($post[0]);
                    // delete the term meta to avoid duplicate inserts
                    delete_term_meta($post[0], 'wpil_links');
                } else {
                    // add any existing links to insert to the metadata
                    $existing_links = Wpil_Toolbox::get_encoded_post_meta($post[0], 'wpil_links', true);
                    $meta = (!empty($existing_links) && is_array($existing_links)) ? array_merge($meta, $existing_links): $meta;
                    // update the stored meta
                    Wpil_Toolbox::update_encoded_post_meta($post[0], 'wpil_links', $meta);
                    Wpil_Post::addLinksToContent(null, ['ID' => $post[0]], array(), false, true);
                    // delete the post meta to avoid duplicate inserts
                    delete_post_meta($post[0], 'wpil_links');
                }
            }
        }

        wp_send_json(array('success' => array('title' => __('Selected Links Created!', 'wpil'), 'text' => __('The selected auto links have been inserted!', 'wpil'))));
    }

    /**
     * Save keyword to DB
     *
     * @param $keyword
     * @param $link
     * @return object
     */
    public static function store()
    {
        global $wpdb;
        $keyword_data = trim(sanitize_text_field($_POST['keyword']));

        // if the keyword has been double quoted, don't split it on the commas
        if(0 === strpos($keyword_data, '\"') && strrpos($keyword_data, '\"') === (strlen($keyword_data) - 2)){
            $keyword_data = array(trim(substr($keyword_data, 2, strlen($keyword_data) - 4)));
        }else{
            $keyword_data = explode(',', $keyword_data);
        }

        $link = trim(esc_url_raw($_POST['link']));

        $set_priority = get_option('wpil_keywords_set_priority');
        $priority = (isset($_POST['wpil_keywords_priority_setting']) && !empty($_POST['wpil_keywords_priority_setting'])) ? (int)$_POST['wpil_keywords_priority_setting']: 0;

        $restrict_date = (isset($_POST['wpil_keywords_restrict_date']) && !empty($_POST['wpil_keywords_restrict_date'])) ? 1: 0;
        $date = null;
        if(isset($_POST['wpil_keywords_restricted_date']) && !empty($_POST['wpil_keywords_restricted_date'])){
            $date = preg_replace("([^0-9-])", "", $_POST['wpil_keywords_restricted_date']);
            if($date !== $_POST['wpil_keywords_restricted_date']){
                $date = null;
            }
        }

        $restrict_cats = (isset($_POST['wpil_keywords_restrict_to_cats']) && !empty($_POST['wpil_keywords_restrict_to_cats'])) ? 1: 0;
        $term_ids = '';
        if(isset($_POST['restricted_cats']) && !empty($_POST['restricted_cats'])){
            $ids = array_map(function($num){ return (int)$num; }, $_POST['restricted_cats']);
            $term_ids = implode(',', $ids);
        }

        $force_insert = (isset($_POST['wpil_keywords_force_insert']) && !empty($_POST['wpil_keywords_force_insert'])) ? 1: 0;

        $prioritize_longtail = get_option('wpil_keywords_prioritize_longtail', 0);

        self::saveSettings();
        self::prepareTable();
        $insert_ids = array();
        foreach($keyword_data as $keyword){
            $keyword = trim($keyword);
            if(empty($keyword)){
                continue;
            }

            if(!empty($prioritize_longtail) && (empty($priority) || empty($set_priority)) ){
                $priority = Wpil_Word::getWordCount($keyword);
            }

            $wpdb->insert($wpdb->prefix . 'wpil_keywords', [
                'keyword' => $keyword,
                'link' => $link,
                'add_same_link' => get_option('wpil_keywords_add_same_link'),
                'link_once' => get_option('wpil_keywords_link_once'),
                'restrict_to_live' => get_option('wpil_keywords_restrict_to_live', 1),
                'limit_inserts' => get_option('wpil_keywords_limit_inserts'),
                'insert_limit' => get_option('wpil_keywords_insert_limit'),
                'select_links' => get_option('wpil_keywords_select_links'),
                'set_priority' => $set_priority,
                'priority_setting' => $priority,
                'prioritize_longtail' => $prioritize_longtail,
                'restrict_date' => $restrict_date,
                'restricted_date' => $date,
                'restrict_cats' => $restrict_cats,
                'restricted_cats' => $term_ids,
                'case_sensitive' => get_option('wpil_keywords_case_sensitive'),
                'force_insert' => $force_insert,
                'same_lang' => get_option('wpil_keywords_same_lang', 0)
            ]);

            $insert_ids[] = $wpdb->insert_id;
        }

        Wpil_Telemetry::log_event('creating_autolinking_rule', array('count' => count($insert_ids)));

        return (count($insert_ids) === 1) ? self::getKeywordByID($wpdb->insert_id): $insert_ids;
    }

    /**
     * Bulk save keywords to DB
     *
     * @param $keyword
     * @param $link
     * @return object
     */
    public static function bulk_store()
    {
        global $wpdb;

        // if there isn't any data or it's not an array, exit
        if(!isset($_POST['keyword_data']) || empty($_POST['keyword_data']) || !is_array($_POST['keyword_data'])){
            return false;
        }

        // setup the keyword id array
        $insert_ids = array();

        // go over the keywords 
        foreach($_POST['keyword_data'] as $dat){
            $keyword_data = trim(sanitize_text_field($dat['keyword']));

            // if the keyword has been double quoted, don't split it on the commas
            if(0 === strpos($keyword_data, '\"') && strrpos($keyword_data, '\"') === (strlen($keyword_data) - 2)){
                $keyword_data = array(trim(substr($keyword_data, 2, strlen($keyword_data) - 4)));
            }else{
                $keyword_data = explode(',', $keyword_data);
            }

            $link = trim(esc_url_raw($dat['link']));

            $priority = (isset($dat['wpil_keywords_priority_setting']) && !empty($dat['wpil_keywords_priority_setting'])) ? (int)$dat['wpil_keywords_priority_setting']: 0;

            $restrict_date = (isset($dat['wpil_keywords_restrict_date']) && !empty($dat['wpil_keywords_restrict_date'])) ? 1: 0;
            $date = null;
            if(isset($dat['wpil_keywords_restricted_date']) && !empty($dat['wpil_keywords_restricted_date'])){
                $date = preg_replace("([^0-9-])", "", $dat['wpil_keywords_restricted_date']);
                if($date !== $dat['wpil_keywords_restricted_date']){
                    $date = null;
                }
            }

            $restrict_cats = (isset($dat['wpil_keywords_restrict_to_cats']) && !empty($dat['wpil_keywords_restrict_to_cats'])) ? 1: 0;
            $term_ids = '';
            if(isset($dat['restricted_cats']) && !empty($dat['restricted_cats'])){
                $ids = array_map(function($num){ return (int)$num; }, $dat['restricted_cats']);
                $term_ids = implode(',', $ids);
            }

            $force_insert = (isset($dat['wpil_keywords_force_insert']) && !empty($dat['wpil_keywords_force_insert'])) ? 1: 0;

            $add_same_link = (isset($dat['wpil_keywords_add_same_link']) && !empty($dat['wpil_keywords_add_same_link'])) ? 1: 0;
            $link_once = (isset($dat['wpil_keywords_link_once']) && !empty($dat['wpil_keywords_link_once'])) ? 1: 0;
            $restrict_to_live = (isset($dat['wpil_keywords_restrict_to_live']) && !empty($dat['wpil_keywords_restrict_to_live'])) ? 1: 0;
            $select_links = (isset($dat['wpil_keywords_select_links']) && !empty($dat['wpil_keywords_select_links'])) ? 1: 0;
            $set_priority = (isset($dat['wpil_keywords_set_priority']) && !empty($dat['wpil_keywords_set_priority'])) ? 1: 0;
            $case_sensitive = (isset($dat['wpil_keywords_case_sensitive']) && !empty($dat['wpil_keywords_case_sensitive'])) ? 1: 0;
            $prioritize_longtail = (isset($dat['wpil_keywords_prioritize_longtail']) && !empty($dat['wpil_keywords_prioritize_longtail'])) ? 1: 0;
            $same_language = (isset($dat['wpil_keywords_same_lang']) && !empty($dat['wpil_keywords_same_lang'])) ? 1: 0;

            $limit_inserts = (isset($dat['wpil_keywords_limit_inserts']) && !empty($dat['wpil_keywords_limit_inserts'])) ? 1: 0;
            $insert_limit = (isset($dat['wpil_keywords_insert_limit']) && !empty($dat['wpil_keywords_insert_limit']) && !empty($limit_inserts)) ? (int)$dat['wpil_keywords_insert_limit']: 0;

            self::prepareTable();
            foreach($keyword_data as $keyword){
                $keyword = trim($keyword);
                if(empty($keyword) || self::checkIfAutolinkRuleExists($keyword, $link)){
                    continue;
                }

                // if we're prioritizing longtail keywords & no priority has been manually set
                if(!empty($prioritize_longtail) && (empty($priority) || empty($set_priority)) ){
                    $priority = Wpil_Word::getWordCount($keyword);
                }

                $wpdb->insert($wpdb->prefix . 'wpil_keywords', [
                    'keyword' => $keyword,
                    'link' => $link,
                    'add_same_link' => $add_same_link,
                    'link_once' => $link_once,
                    'restrict_to_live' => $restrict_to_live,
                    'limit_inserts' => $limit_inserts,
                    'insert_limit' => $insert_limit,
                    'select_links' => $select_links,
                    'set_priority' => $set_priority,
                    'priority_setting' => $priority,
                    'prioritize_longtail' => $prioritize_longtail,
                    'restrict_date' => $restrict_date,
                    'restricted_date' => $date,
                    'restrict_cats' => $restrict_cats,
                    'restricted_cats' => $term_ids,
                    'case_sensitive' => $case_sensitive,
                    'force_insert' => $force_insert,
                    'same_lang' => $same_language
                ]);

                $insert_ids[] = $wpdb->insert_id;
            }
        }

        Wpil_Telemetry::log_event('bulk_autolinking_rule_created', array('count' => count($insert_ids)));

        return $insert_ids;
    }

    /**
     * Directly save keyword to DB.
     * 
     * $keyword_data args are:
     * array(
            'keyword' => (string),
            'link' => (string),
            'add_same_link' => (bool int) (0|1),
            'link_once' => (bool int) (0|1),
            'restrict_to_live' => (bool int) (0|1),
            'limit_inserts' => (bool int) (0|1),
            'insert_limit' => (int),
            'select_links' => (bool int) (0|1),
            'set_priority' => (bool int) (0|1),
            'priority_setting' => (int),
            'prioritize_longtail' => (bool int) (0|1),
            'restrict_date' => (bool int) (0|1),
            'restricted_date' => (null|timestring),
            'restrict_cats' => (bool int) (0|1),
            'restricted_cats' => (empty string | array of term ids), // it says 'restricted_cats', but it also works for tags
            'case_sensitive' => (bool int) (0|1),
            'force_insert' => (bool int) (0|1),
            'same_lang' => (bool int) (0|1),
        )
     *
     * @param array $keyword_data
     * @return object|array
     */
    public static function directStore($keyword_data){
        global $wpdb;

        if( empty($keyword_data) ||
            !isset($keyword_data['keyword']) ||
            empty($keyword_data['keyword']) ||
            !isset($keyword_data['link']) ||
            empty($keyword_data['link']))
        {
            return array();
        }

        $defaults = array(
            'keyword' => '',
            'link' => '',
            'add_same_link' => get_option('wpil_keywords_add_same_link'),
            'link_once' => get_option('wpil_keywords_link_once'),
            'restrict_to_live' => get_option('wpil_keywords_restrict_to_live', 1),
            'limit_inserts' => get_option('wpil_keywords_limit_inserts', 0),
            'insert_limit' => get_option('wpil_keywords_insert_limit', 0),
            'select_links' => get_option('wpil_keywords_select_links', 0),
            'set_priority' => get_option('wpil_keywords_set_priority', 0),
            'priority_setting' => 0,
            'prioritize_longtail' => get_option('wpil_keywords_prioritize_longtail', 0),
            'restrict_date' => 0,
            'restricted_date' => null,
            'restrict_cats' => get_option('wpil_keywords_restrict_to_cats'),
            'restricted_cats' => '',
            'case_sensitive' => get_option('wpil_keywords_case_sensitive'),
            'force_insert' => 0,
            'same_lang' => get_option('wpil_keywords_same_lang', 0),
        );

        // merge the supplied data with the defaults
        $keyword_data = array_merge($defaults, $keyword_data);

        // get the keyword(s)
        $keyword_data['keyword'] = trim(sanitize_text_field($keyword_data['keyword']));
        if(0 === strpos($keyword_data['keyword'], '"') && strrpos($keyword_data['keyword'], '"') === (strlen($keyword_data['keyword']) - 1)){
            $keyword_data['keyword'] = array(trim(substr($keyword_data['keyword'], 1, strlen($keyword_data['keyword']) - 2)));
        }else{
            $keyword_data['keyword'] = explode(',', $keyword_data['keyword']);
        }

        // assemble the keyword data that we're going to save
        $data['keyword'] = $keyword_data['keyword'];
        $data['link'] = trim(esc_url_raw($keyword_data['link']));
        $data['add_same_link'] = (int) $keyword_data['add_same_link'];
        $data['link_once'] = (int) $keyword_data['link_once'];
        $data['restrict_to_live'] = (int) $keyword_data['restrict_to_live'];
        $data['limit_inserts'] = (int) $keyword_data['limit_inserts'];
        $data['insert_limit'] = (int) $keyword_data['insert_limit'];
        $data['select_links'] = (int) $keyword_data['select_links'];
        $data['set_priority'] = (int) $keyword_data['set_priority'];
        $data['priority_setting'] = (int) $keyword_data['priority_setting'];
        $data['prioritize_longtail'] = (int) $keyword_data['prioritize_longtail'];
        $data['restrict_date'] = (int) $keyword_data['restrict_date'];
        $data['restricted_date'] = null;
        $data['restrict_cats'] = (int) $keyword_data['restrict_cats'];
        $data['restricted_cats'] = '';
        $data['case_sensitive'] = (int) $keyword_data['case_sensitive'];
        $data['force_insert'] = (int) $keyword_data['force_insert'];
        $data['same_lang'] = (int) $keyword_data['same_lang'];

        // check if there's a date restriction active
        if(!empty($data['restrict_date']) && !empty($keyword_data['restricted_date'])){
            // if there is, process out the date setting
            $date = preg_replace("([^0-9-])", "", $keyword_data['restricted_date']);
            // if the supplied date is the same as the sanitized date
            if($keyword_data['restricted_date'] === $date){
                // set the date restriction to the sanitized date
                $data['restricted_date'] = $date;
            }
        }

        // check if the keyword is restricted to specific terms
        if(!empty($data['restrict_cats']) && !empty($keyword_data['restricted_cats'])){
            // if it is, get the sanitized, comma separated list of term ids
            $ids = array_map(function($num){ return (int)$num; }, $keyword_data['restricted_cats']);
            $data['restricted_cats'] = implode(',', $ids);
        }

        $insert_ids = array();
        foreach($data['keyword'] as $keyword){
            $keyword = trim($keyword);
            if(empty($keyword)){
                continue;
            }

            $wpdb->insert($wpdb->prefix . 'wpil_keywords', [
                'keyword' => $keyword,
                'link' => $data['link'],
                'add_same_link' => $data['add_same_link'],
                'link_once' => $data['link_once'],
                'restrict_to_live' => $data['restrict_to_live'],
                'limit_inserts' => $data['limit_inserts'],
                'insert_limit' => $data['insert_limit'],
                'select_links' => $data['select_links'],
                'set_priority' => $data['set_priority'],
                'priority_setting' => $data['priority_setting'],
                'prioritize_longtail' => $data['prioritize_longtail'],
                'restrict_date' => $data['restrict_date'],
                'restricted_date' => $data['restricted_date'],
                'restrict_cats' => $data['restrict_cats'],
                'restricted_cats' => $data['restricted_cats'],
                'case_sensitive' => $data['case_sensitive'],
                'force_insert' => $data['force_insert'],
                'same_lang' => $data['same_lang'],
            ]);

            $insert_ids[] = $wpdb->insert_id;
        }

        return (count($insert_ids) === 1) ? self::getKeywordByID($wpdb->insert_id): $insert_ids;
    }

    /**
     * Create keywords DB table if not exists
     */
    public static function prepareTable()
    {
        global $wpdb;
        $table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wpil_keywords'");
        if ($table != $wpdb->prefix . 'wpil_keywords') {
            $wpil_link_table_query = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpil_keywords (
                                    id int(10) unsigned NOT NULL AUTO_INCREMENT,
                                    keyword varchar(255) NOT NULL,
                                    link varchar(512) NOT NULL,
                                    add_same_link int(1) unsigned NOT NULL,
                                    link_once int(1) unsigned NOT NULL,
                                    restrict_to_live int(1) unsigned NOT NULL,
                                    limit_inserts tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
                                    insert_limit int(10) UNSIGNED NOT NULL DEFAULT 0,
                                    select_links tinyint(1) DEFAULT 0,
                                    set_priority tinyint(1) DEFAULT 0,
                                    priority_setting int DEFAULT 0,
                                    prioritize_longtail tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
                                    restrict_date tinyint(1) DEFAULT 0,
                                    restricted_date DATETIME DEFAULT NULL,
                                    restrict_cats tinyint(1) DEFAULT 0,
                                    restricted_cats text,
                                    case_sensitive tinyint(1) DEFAULT 0,
                                    force_insert tinyint(1) DEFAULT 0,
                                    same_lang tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
                                    auto_imported tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
                                    auto_managed tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
                                    PRIMARY KEY  (id)
                                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

            // create DB table if it doesn't exist
            require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($wpil_link_table_query);
        }

        $table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wpil_keyword_links'");
        if ($table != $wpdb->prefix . 'wpil_keyword_links') {
            $wpil_link_table_query = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpil_keyword_links (
                                    id int(10) unsigned NOT NULL AUTO_INCREMENT,
                                    keyword_id int(10) unsigned NOT NULL,
                                    post_id bigint(20) unsigned NOT NULL,
                                    post_type varchar(10) NOT NULL,
                                    anchor text,
                                    PRIMARY KEY  (id),
                                    INDEX (keyword_id)
                                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

            // create DB table if it doesn't exist
            require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($wpil_link_table_query);
        }

        Wpil_Base::fixCollation($wpdb->prefix . 'wpil_keywords');
        Wpil_Base::fixCollation($wpdb->prefix . 'wpil_keyword_links');

        // set up the possible links table
        self::preparePossibleLinksTable();
    }

    /**
     * Creates the table for storing possible auto links so the user can select what links are to be inserted.
     **/
    public static function preparePossibleLinksTable(){
        global $wpdb;
        $data_table = $wpdb->prefix . 'wpil_keyword_select_links';
        $table = $wpdb->get_var("SHOW TABLES LIKE '{$data_table}'");
        if ($table != $data_table) {
            $wpil_link_table_query = "CREATE TABLE IF NOT EXISTS {$data_table} (
                                    id int(10) unsigned NOT NULL AUTO_INCREMENT,
                                    keyword_id int(10) unsigned NOT NULL,
                                    post_id bigint(20) unsigned NOT NULL,
                                    post_type varchar(10) NOT NULL,
                                    sentence_text text,
                                    case_keyword text,
                                    meta_data text,
                                    PRIMARY KEY  (id),
                                    INDEX (keyword_id)
                                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

            // create DB table if it doesn't exist
            require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($wpil_link_table_query);
        }
    }

    /**
     * Get data for keywords table
     *
     * @param $per_page
     * @param $page
     * @param $search
     * @param string $orderby
     * @param string $order
     * @return array
     */
    public static function getData($per_page, $page, $search,  $orderby = '', $order = '', $list_all = false)
    {
        self::prepareTable();
        global $wpdb;
        $limit = " LIMIT " . (($page - 1) * $per_page) . ',' . $per_page;

        $sort = " ORDER BY id DESC ";
        if ($orderby && $order) {
            $supported_cols = array('keyword', 'link', 'links');

            if(!in_array($orderby, $supported_cols)){
                $orderby = 'id';
            }

            if('links' === $orderby){
                $orderby = 'link_count';
            }

            $order = (mb_strtolower($order) === 'asc') ? 'asc': 'desc';
            $sort = " ORDER BY $orderby $order ";
        }
        $search = !empty($search) ? $wpdb->prepare(" AND (keyword LIKE %s OR link LIKE %s) ", Wpil_Toolbox::esc_like($search), Wpil_Toolbox::esc_like($search)) : '';
        $total = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}wpil_keywords WHERE 1 {$search}");
        $keywords = $wpdb->get_results("SELECT a.*, COUNT(b.keyword_id) as 'link_count' FROM {$wpdb->prefix}wpil_keywords a LEFT JOIN {$wpdb->prefix}wpil_keyword_links b ON a.id = b.keyword_id WHERE 1 {$search} GROUP BY a.id {$sort} {$limit}" );

        $keyword_ids = array();

        foreach($keywords as $kword){
            $keyword_ids[] = $kword->id;
        }
 
        $results = array();
        if(!empty($keyword_ids)){
            $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpil_keyword_links WHERE keyword_id IN (" . implode(', ', $keyword_ids) . ")");
            foreach($result as $r){
                $results[$r->keyword_id][] = $r;
            }
            $result = null;
        }

        //get posts with inserted links
        foreach ($keywords as $key => $keyword) {
            $links = [];
            $link_count = 0;
            if(isset($results[$keyword->id])){
                foreach ($results[$keyword->id] as $r) {
                    if($link_count < 100 || $list_all){
                        $links[] = (object)[
                            'post' => new Wpil_Model_Post($r->post_id, $r->post_type),
                            'anchor' => $r->anchor,
                            'url' => $keyword->link,
                        ];
                    }else{
                        $links[] = false;
                    }
                    $link_count++; // count the number of links this keyword has
                }
            }
            $keywords[$key]->links = $links;
            $keywords[$key]->link_count = $link_count;
        }

        return [
            'total' => $total,
            'keywords' => $keywords
        ];
    }

    /**
     * Removes the given autolinks from the given post and updates the post's content
     * @param $keyword The autolink that we're processing
     * @param $post The post that we're removing the link from
     * @param $de_autolink Instead of removeing the links, should be remove the autolink identifyers so that they are just normal links?
     **/
    public static function removeAndUpdate($keyword, $post, $de_autolink = false){
        $content = $post->getCleanContent();
        $excerpt = $post->maybeGetExcerpt();

        self::removeAllLinks($keyword, $content, $de_autolink);
        self::removeAllExcerptLinks($keyword, $post, $excerpt, $de_autolink);
        self::removeAllMetaContentLinks($keyword, $post, $de_autolink);
        return self::updateContent($content, $keyword, $post, false, $excerpt);
    }

    /**
     * Delete keyword from DB
     */
    public static function delete()
    {
        if (!empty($_POST['id'])) {
            global $wpdb;

            // be sure to ignore any external object caches
            Wpil_Base::ignore_external_object_cache();

            // Remove any hooks that may interfere with AJAX requests
            Wpil_Base::remove_problem_hooks();

            // get if we're deleting links or just "un-autolinking" them
            $de_autolink = (isset($_POST['de_autolink']) && $_POST['de_autolink'] === '1') ? true: false;

            $ids = (is_array($_POST['id'])) ? implode(',', array_map(function($id){ return (int)$id; }, $_POST['id'])) : (int)$_POST['id'];

            // if there are no ids to work with, exit
            if(empty($ids)){
                die();
            }

            // create a list for the deleted keywords
            $deleted = array();

            // get the memory limit
            $memory_break_point = Wpil_Report::get_mem_break_point();

            $keywords = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpil_keywords WHERE id IN (" . $ids . ")");
            $deleted_links = 0;
            if(!empty($keywords)){
                foreach($keywords as $keyword){
                    // if we're over time or near the memory limit
                    if(Wpil_Base::overTimeLimit(10, 15) || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point)){
                        // exit before we start removing a new autolink
                        break;
                    }

                    foreach(self::getLinksByKeyword($keyword->id) as $link) {
                        $post = new Wpil_Model_Post($link->post_id, $link->post_type);

                        self::removeAndUpdate($keyword, $post, $de_autolink);
                        $deleted_links += (int)$wpdb->delete($wpdb->prefix . 'wpil_keyword_links', ['keyword_id' => $keyword->id, 'post_id' => $link->post_id, 'post_type' => $link->post_type]);

                        // if we're over time or near the memory limit after removing && updating
                        if(Wpil_Base::overTimeLimit(10, 15) || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point)){
                            // exit before we go around again
                            break 2; // jump 2 so that we don't trigger the rule delete yet
                        }
                    }

                    // if the linksByKeyword loop successfully completed, then the links have been removed and we can delete the rule
                    $wpdb->delete($wpdb->prefix . 'wpil_keywords', ['id' => $keyword->id]);
                    $wpdb->delete($wpdb->prefix . 'wpil_keyword_select_links', ['keyword_id' => $keyword->id]);
                    $deleted[] = $keyword->id;
                }
            }

            if(!empty($deleted)){
                $de_autolink = (isset($_POST['de_autolink']) && $_POST['de_autolink'] === '1') ? 1: 0;
                Wpil_Telemetry::log_event('delete_autolinking_rule', array('count' => count($deleted), 'de_autolink' => $de_autolink));
            }

            wp_send_json(array('deleted' => $deleted, 'found_rules' => $keywords, 'deleted_links' => $deleted_links));
        }
    }

    /**
     * Deletes all stored possible links for the given keyword id
     **/
    public static function deletePossibleLinksForKeyword($keyword, $post = null){
        global $wpdb;

        if(empty($keyword->id) || !isset($keyword->id)){
            return false;
        }

        $id = (int) $keyword->id;

        if(!empty($post) && is_a($post, 'Wpil_Model_Post')){
            $wpdb->delete($wpdb->prefix . 'wpil_keyword_select_links', ['keyword_id' => $id, 'post_id' => $post->id, 'post_type' => $post->type]);
        }else{
            $wpdb->delete($wpdb->prefix . 'wpil_keyword_select_links', ['keyword_id' => $id]);
        }
    }

    /**
     * Deletes all stored possible links for the given post
     **/
    public static function deletePossibleLinksByPost($post){
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'wpil_keyword_select_links', ['post_id' => $post->id, 'post_type' => $post->type]);
    }

    /**
     * Delete inserted link DB record
     *
     * @param int $link_id The id of the link that we're checking
     * @param int $count The number of links that we have on record
     * @return int $deleted The number of links deleted.
     */
    public static function deleteLink($link, $count = 999) {
        global $wpdb;
        $links = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}wpil_keyword_links WHERE post_id = {$link->post_id} AND post_type = '{$link->post_type}' AND keyword_id = {$link->keyword_id}");
        $deleted = 0;
        foreach ($links as $key => $link) {
            if ($key >= $count) {
                $county = $wpdb->delete($wpdb->prefix . 'wpil_keyword_links', ['id' => $link->id]);
                if(!empty($county)){
                    $deleted += $county;
                }
            }
        }

        return $deleted;
    }

    /**
     * Get inserted links by keyword
     *
     * @param $keyword_id
     * @return array
     */
    public static function getLinksByKeyword($keyword_id, $post = null)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wpil_keyword_links';

        if(!empty($post) && is_a($post, 'Wpil_Model_Post')){
            $links = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE `keyword_id` = %d AND `post_id` = %d AND `post_type` = %s", $keyword_id, $post->id, $post->type));
        }else{
            $links = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE `keyword_id` = %d", $keyword_id));
        }

        return $links;
    }

    /**
     * Get possible links by keyword id
     *
     * @param $keyword_id
     * @param int $limit A limit on how many links are returned at once
     * @return array
     */
    public static function getPossibleLinksByKeyword($keyword_id, $limit = false)
    {
        global $wpdb;

        $limit = (!empty($limit)) ? "LIMIT " . intval($limit): '';
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpil_keyword_select_links WHERE keyword_id = {$keyword_id} {$limit}");
    }

    /**
     * Get the number of possible links by keyword id
     *
     * @param $keyword_id
     * @return array
     */
    public static function getPossibleLinkCountByKeyword($keyword_id)
    {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpil_keyword_select_links WHERE keyword_id = {$keyword_id}");
    }

    /**
     * Get inserted links by post
     *
     * @param $post
     * @return array
     */
    public static function getLinksByPost($post)
    {
        global $wpdb;
        return $wpdb->get_results("SELECT *, count(keyword_id) as `cnt` FROM {$wpdb->prefix}wpil_keyword_links WHERE post_id = {$post->id} AND post_type = '{$post->type}' GROUP BY keyword_id");
    }

    /**
     * Create link from keyword in all posts and terms
     *
     * @param $keyword
     * @param bool $return Should this return or echo the results? Default is echo for built in ajax. Passing true will also allow the function to process until the PHP time limit is nearly up.
     */
    public static function checkPosts($keyword, $return = false)
    {
        global $wpdb;
        update_option('wpil_post_procession', time());
        Wpil_Base::update_option_cache('wpil_post_procession', time());
        $max_links_per_post = get_option('wpil_max_links_per_post', 0);
        $at_limit = self::keywordAtLimit($keyword);
        $link_post = null;
        $internal = Wpil_Link::isInternal($keyword->link);

        if($internal || Wpil_Link::isAffiliateLink($keyword->link)){
            // check if the target is an internal post
            $link_post = Wpil_Post::getPostByLink($keyword->link);
            // if it is, check if we're at the inbound link limit
            if(!empty($link_post) && Wpil_link::at_max_inbound_links($link_post)){
                // if we are, set the limit flag
                $at_limit = true;
            }

            // if the keyword is set to only link when the target is published and it hasn't been yet 
            if( (!empty($link_post) && !empty($keyword->restrict_to_live) && $link_post->type === 'post' && $link_post->getStatus() !== 'publish') ||
                (empty($link_post) && !empty($keyword->restrict_to_live) && $internal)
            ){
                // say that we're at the limit
                $at_limit = true;
            }
        }

        $posts = get_transient('wpil_keyword_posts_' . $keyword->id);
        $total = !empty($_POST['total']) ? (int)$_POST['total'] : 0.1;
        if (empty($posts) && !$at_limit) {
            $ignore_posts = Wpil_Settings::getIgnoreKeywordsPosts();
            $post_types = implode("','", Wpil_Settings::get_autolinking_post_types());
            $same_lang_ids = array();
            //get matched posts and categories
            $where = " AND post_type IN ('{$post_types}')";
            if (!empty($link_post) && !empty($link_post->type) && $link_post->type == 'post') {
                if(!apply_filters('wpil_force_include_autolink_post', false, $link_post, $keyword, $link_post)){
                    $where .= " AND ID != " . $link_post->id;
                }

                // if we're restricting links to posts in the same language
                if(isset($keyword->same_lang) && !empty($keyword->same_lang)){
                    // try getting the ids for posts in the same language as this one
                    $same_lang_ids = Wpil_Post::getSameLanguagePosts($link_post->id);
                    // if we're successful
                    if(!empty($same_lang_ids)){
                        // restrict the ids to the same language posts
                        $where .= " AND ID IN (" . implode(',', $same_lang_ids) . ")";
                    }
                }

                $ignore_posts[] = $link_post->type . '_' . $link_post->id; // add the target post to the ignored post list so we can be more sure the target post's won't be linked
            }
            $when = '';
            if(!empty($keyword->restrict_date) && !empty($keyword->restricted_date)){
                $when = " AND `post_date_gmt` > '{$keyword->restricted_date}'";
            }

            $case_sensitive = (isset($keyword->case_sensitive) && !empty($keyword->case_sensitive)) ? "BINARY": "";

            $keyword_search = "(post_content LIKE {$case_sensitive} '%{$keyword->keyword}%'";

            // to catch unicode encoded letters, we'll JSON encode the keyword to convert the letters to their unicode equivalent // usually needed for accented letters like é
            $encoded_keyword = json_encode(str_replace("\'", "'", $keyword->keyword)); // we'll also unslash any apostrophes since the json_encode slashes the slash and causes the query to break
            if(!empty($encoded_keyword) && trim($encoded_keyword, '"') !== str_replace("\'", "'", $keyword->keyword)){
                $encoded_keyword = str_replace("'", "\'", trim($encoded_keyword, '"'));
                $keyword_search .= " OR post_content LIKE {$case_sensitive} '%{$encoded_keyword}%'";
            }

            // check to see if there are any "&" signs that aren't entity encoded
            // if there are, make sure that we search for an entity encoded versions of the keyword too

            // if there's an "&"
            if(false !== strpos($keyword->keyword, '&')){
                // decode any existing "&amp;"s and then re-encode to catch any holdouts
                $and_encoded = str_replace('&', '&amp;', str_replace('&amp;', '&', $keyword->keyword));
                $keyword_search .= " OR post_content LIKE {$case_sensitive} '%{$and_encoded}%'";
            }

            // and close up the content search area
            $keyword_search .= ")";

            $posts = [];
            $statuses_query = Wpil_Query::postStatuses();
            $statuses_query_p = Wpil_Query::postStatuses('p');
            $user_role_ignore = Wpil_Query::get_ignore_user_role_suggestions_query();
            $user_role_ignore_p = Wpil_Query::get_ignore_user_role_suggestions_query('p');
            $oxy_prefix = (!empty(get_option('oxy_meta_keys_prefixed', false))) ? '_ct_': 'ct_';
            $results = $wpdb->get_results("SELECT ID FROM {$wpdb->posts} WHERE {$keyword_search} $statuses_query {$user_role_ignore} $where $when
                                                    UNION
                                                    SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE m.meta_key IN ('_themify_builder_settings_json', '{$oxy_prefix}builder_shortcodes', 'mfn-page-items-seo') AND m.meta_value LIKE {$case_sensitive} '%{$keyword->keyword}%' $statuses_query_p {$user_role_ignore_p}
                                                     $where");
            $results = self::getPostsFromAlternateLocations($results, $keyword, $same_lang_ids);
            foreach ($results as $post) {
                $posts[] = new Wpil_Model_Post($post->ID);
            }

            if (!empty(Wpil_Settings::getTermTypes())) {
                $taxonomies = implode("','", Wpil_Settings::getTermTypes());
                $where = " AND taxonomy IN ('{$taxonomies}') ";
                $same_lang_term_ids = array();
                if (!empty($link_post) && !empty($link_post->type) && $link_post->type == 'term') {
                    $where .= " AND term_id != " . $link_post->id;

                    // if we're restricting links to terms in the same language
                    if(isset($keyword->same_lang) && !empty($keyword->same_lang)){
                        // try getting the ids for terms in the same language as this one
                        $same_lang_term_ids = Wpil_Post::getSameLanguageTerms($link_post->id);
                        // if we're successful
                        if(!empty($same_lang_term_ids)){
                            // restrict the ids to the same language terms
                            $where .= " AND term_id IN (" . implode(',', $same_lang_term_ids) . ")";
                        }
                    }
                }
                $results = $wpdb->get_results("SELECT * FROM {$wpdb->term_taxonomy} WHERE description LIKE {$case_sensitive} '%{$keyword->keyword}%' $where ");
                $results = self::getTermsFromAlternateLocations($results, $keyword, $same_lang_term_ids);
                foreach ($results as $category) {
                    $posts[] = new Wpil_Model_Post($category->term_id, 'term');
                }
            }

            foreach ($posts as $key => $post) {
                if(apply_filters('wpil_force_include_autolink_post', false, $post, $keyword, $link_post)){
                    continue;
                }

                if (in_array($post->type . '_' . $post->id, $ignore_posts)) {
                    unset($posts[$key]);
                }
            }

            $total = count($posts) + .1;
        }elseif(empty($posts) || $at_limit){
            // if there's no posts or we're already at the limit, 
            // set the post list as an empty array to allow processing without hiccups
            $posts = array();
        }

        //proceed posts
        $memory_break_point = Wpil_Report::get_mem_break_point();
        foreach ($posts as $key => $post) {
            // if we've hit the insert limit or the target post is at the limit
            if(self::keywordAtLimit($keyword) || Wpil_link::at_max_inbound_links($link_post)){ // TODO: Test to make sure it works with the Inbound Limit
                // unset all the remaining posts
                $posts = array();
                // and exit the loop
                break;
            }

            // skip to the next post if this one is at the limit
            if(!empty($max_links_per_post) && Wpil_link::at_max_outbound_links($post)){
                unset($posts[$key]);
                continue;
            }
            $phrases = Wpil_Suggestion::getPhrases($post->getContent(), $keyword->force_insert, array(), true, $keyword->keyword);
            self::makeLinks($phrases, $keyword, $post);
            unset($posts[$key]);

            if ( (Wpil_Base::overTimeLimit(10, 15) && empty($return)) || ($return && Wpil_Base::overTimeLimit(25, 30)) || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point) ) {
                set_transient('wpil_keyword_posts_' . $keyword->id, $posts, 60 * 5);
                break;
            }
        }


        if (empty($posts)) {
            delete_transient('wpil_keyword_posts_' . $keyword->id);
        }

        update_option('wpil_post_procession', 0);
        Wpil_Base::update_option_cache('wpil_post_procession', 0);

        // maybe optimize the options table
        Wpil_Toolbox::maybe_optimize_options_table();

        $percent_complete = 0;
        if(!empty($total)){
            $percent_complete = floor((count($posts) / $total) * 100);
        }

        $data = [
            'nonce' => isset($_POST['nonce']) ? $_POST['nonce']: false,
            'keyword_id' => $keyword->id,
            'progress' => 100 - $percent_complete,
            'total' => $total,
            'finish' => empty($posts)
        ];

        if($return){
            return $data;
        }else{
            wp_send_json($data);
        }
    }

    /**
     * Checkes to see if the current keyword is at it's insertion limit
     **/
    public static function keywordAtLimit($keyword){
        // if there's no keyword
        if(empty($keyword)){
            // return true so whatever's listing won't run
            return true;
        }

        // if no limit has been set
        if(empty($keyword->limit_inserts)){
            // return that we're not at the limit
            return false;
        }

        // get the number of inserted links and compare it to the limit
        $inserted = self::getAutolinkInsertCountById($keyword->id);
        if($inserted < $keyword->insert_limit){
            // if we're inside the limit, return false
            return false;
        }

        // if we made it this far, we must be at the limit
        return true;
    }

    /**
     * Check if keyword is part of word
     *
     * @param $sentence
     * @param $keyword
     * @param $pos
     * @return bool
     */
    public static function isPartOfWord($sentence, $keyword, $pos)
    {
        $endings = array_merge(Wpil_Word::$endings, ['', ' ', '>', '<', ' ', '-', urldecode('%C2%A0')]); // '%C2%A0' === nbsp
        if ($pos >= 1) {
            $char_prev = Wpil_Word::onlyText(trim(mb_substr($sentence, $pos - 1, 1)));
        } else {
            $char_prev = '';
        }
        $char_next = Wpil_Word::onlyText(trim(mb_substr($sentence, $pos + mb_strlen($keyword), 1)));

        if (in_array($char_prev, $endings) && in_array($char_next, $endings) || 
            (WPIL_CURRENT_LANGUAGE === 'english' && self::isAsianText($char_prev, $char_next, $keyword))) 
        {
            return false;
        }

        return true;
    }

    /**
     * Checks to see if the current text is Asian language text.
     **/
    public static function isAsianText($char_prev, $char_next, $keyword){
        $string = $char_prev . $keyword . $char_next;

        // if it's Japanese
        if($count = preg_match_all('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}。、]/u', $string)){
            $char_count = mb_strlen($string);

            // if all the chars are Japanese
            if($count === $char_count){
                return true;
            }elseif($char_count > $count && $char_count > 0 && $count > 0){
                // check to see if the majority of the text is Japanese
                $difference = $count/$char_count;

                // if it's over 60% Japanese
                if($difference > 0.6000){
                    return true;
                }

                // if the keyword isn't over 60% Japanese
                // check the character before and after the keyword to see if they are Japanese
                $edge_count = preg_match_all('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}。、]/u', ($char_prev . $char_next));

                // if they are
                if($edge_count === count(array_filter([$char_prev, $char_next]))){
                    // we're probably in a sentence that is overall Japanese
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if keyword is inside link
     *
     * @param $sentence
     * @param $keyword
     * @return bool
     */
    public static function insideLink($sentence, $keyword)
    {
        preg_match_all('`<a[^>]+>.*?</a>`i', $sentence, $matches);
        if(!empty($matches[0])){
            foreach($matches[0] as $match){
                // if the keyword occurs in an existing link, return true
                if(false !== mb_stripos($match, $keyword)){
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks to see if the sentence occurs inside a header tag
     **/
    public static function insideHeading($sentence, $keyword, $post){
        preg_match_all('`<h[1-6][^><]*?>(.*?)<\/h[1-6]>|&lt;h[1-6].*?&gt;(.*?)&lt;\/h[1-6]&gt;`i', $post->getContent(), $matches);

        if (!empty($matches)){
            foreach($matches[0] as $match){
                if(false !== strpos($match, $sentence)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Checks to see if the sentence already has a link.
     * If it does, checks to see if the autolink is using the force insertion override
     * 
     * @return bool Returns true if the link can be inserted and false if it can't
     **/
    public static function forceLinkCheck($sentence, $keyword){
        $has_link = (false !== strpos($sentence, '<a')) ? true : false;
        if(!$has_link || $has_link && !empty($keyword->force_insert)){
            return true;
        }

        return false;
    }

    /**
     * Get all keywords.
     * Randomly shuffles all keywords in the same priority so sentences with multiple keywords don't always get the same autolink
     *
     * @return array
     */
    public static function getKeywords()
    {
        global $wpdb;
        $keywords = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpil_keywords ORDER BY id");

        $sorted = array();
        foreach($keywords as $keyword){
            // if the keyword has a set priority
            if(!empty($keyword->set_priority) || !empty($keyword->prioritize_longtail)){
                // sort it into the box for that priority
                $sorted[$keyword->priority_setting][] = $keyword;
            }else{
                // if the priority isn't set, or we're no longer applying priority to the rule,
                if(!isset($sorted[0])){
                    $sorted[0] = array();
                }
                // add the rule in the base, (0), priority level
                $sorted[0][] = $keyword;
            }
        }

        $sorted2 = array();
        foreach($sorted as $key => $sort){
            shuffle($sort);
            $sorted2[$key] = $sort;
        }

        // sort the keyowrds by priority
        krsort($sorted2, SORT_NUMERIC);

        $results = array();
        foreach($sorted2 as $sort){
            foreach($sort as $kword){
                $results[] = $kword;
            }
        }

        return $results;
    }

    /**
     * Get keyword by ID
     *
     * @param $id
     * @return object|null
     */
    public static function getKeywordByID($id)
    {
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wpil_keywords WHERE id = " . $id);
    }

    /**
     * Get keywords by IDs
     *
     * @param $id
     * @return object|null
     */
    public static function getKeywordsByID($ids)
    {
        if(empty($ids)){
            return array();
        }

        // make sure all the ids are clean
        $ids = array_filter(array_map(function($id){ return (int) $id; }, $ids));

        if(empty($ids)){
            return array();
        }

        $ids = implode(',', $ids);

        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpil_keywords WHERE id IN (" . $ids . ")");
    }

    /**
     * Get keyword by URL
     *
     * @param $url
     * @return object|null
     */
    public static function getKeywordByURL($url)
    {
        if(empty($url)){
            return null;
        }

        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpil_keywords WHERE link = %s", $url));
    }

    /**
     * Get the keywords that are inserted in a specific post
     *
     * @param Wpil_Model_Post $post
     * @return object|null
     */
    public static function get_post_keywords($post)
    {
        if(empty($post) || !is_a($post, 'Wpil_Model_Post')){
            return null;
        }

        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpil_keywords WHERE `id` IN (select distinct `keyword_id` from {$wpdb->prefix}wpil_keyword_links where post_id = %d and post_type = %s)", $post->id, $post->type));
    }

    /**
     * Get all keywords
     *
     * @return object|null
     */
    public static function getAllKeywords()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpil_keywords");
    }

    /**
     * Get possible links by id.
     * Can accept a single id or array of ids
     *
     * @param int|array $id
     * @return object|array
     */
    public static function getPossibleLinksByID($id)
    {
        global $wpdb;

        if(is_array($id)){
            $id = array_filter(array_map(function($id){ return (int)$id; }, $id));
            if(!empty($id)){
                $id = implode(',', $id);
                return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpil_keyword_select_links WHERE `id` IN (" . $id . ")");
            }
        }else{
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpil_keyword_select_links WHERE `id` = %d", $id));
        }

        return array();
    }

    /**
     * Deletes possible links by id.
     * Can accept a single id or array of ids
     *
     * @param int|array $id
     * @return null
     */
    public static function deletePossibleLinksById($id)
    {
        global $wpdb;

        if(is_array($id)){
            $id = implode(',', $id);
            return $wpdb->query("DELETE FROM {$wpdb->prefix}wpil_keyword_select_links WHERE id IN (" . $id . ")");
        }else{
            return $wpdb->query("DELETE FROM {$wpdb->prefix}wpil_keyword_select_links WHERE id = " . $id);
        }
    }

    /**
     * Gets a list of post ids from all the posts that have the given autolink inserted in them.
     * Only returns the ids for posts, it doesn't do terms
     * @return array
     **/
    public static function getAutolinkedPostIds($id){
        global $wpdb;

        if(empty($id)){
            return array();
        }

        return $wpdb->get_col($wpdb->prepare("SELECT `post_id` FROM {$wpdb->prefix}wpil_keyword_links WHERE keyword_id = %d AND `post_type` = 'post'", $id));
    }

    /**
     * Gets the number of times all keywords have been inserted, keyed to id
     **/
    public static function getAutolinkInsertCounts(){
        global $wpdb;

        if(self::$keyword_id_cache !== null){
            return self::$keyword_id_cache;
        }

        $results = $wpdb->get_results("SELECT a.id, COUNT(b.keyword_id) AS 'county' FROM `{$wpdb->prefix}wpil_keywords` a LEFT JOIN `{$wpdb->prefix}wpil_keyword_links` b ON a.id = b.keyword_id GROUP BY a.id");

        if(!empty($results)){
            $index = array();
            foreach($results as $dat){
                $index[$dat->id] = $dat->county;
            }
            self::$keyword_id_cache = $index;
        }else{
            self::$keyword_id_cache = array();
        }

        return self::$keyword_id_cache;
    }

    /**
     * Gets the total number of times the given link has been inserted by id.
     * @return int
     **/
    public static function getAutolinkInsertCountById($id = 0){
        if(empty($id)){
            return 0;
        }

        $ids = self::getAutolinkInsertCounts();

        return isset($ids[$id]) ? $ids[$id]: 0;
    }

    /**
     * Gets the number of times all keywords have been inserted, keyed to id
     **/
    public static function getAutolinkKeywordUrlCache(){
        global $wpdb;

        if(self::$keyword_url_cache !== null){
            return self::$keyword_url_cache;
        }

        $results = $wpdb->get_results("SELECT `keyword`, `link` FROM `{$wpdb->prefix}wpil_keywords`");

        if(!empty($results)){
            $index = array();
            foreach($results as $dat){
                $id = md5($dat->keyword . '_' . $dat->link);
                $index[$id] = (array) $dat;
            }
            self::$keyword_url_cache = $index;
        }else{
            self::$keyword_url_cache = array();
        }

        return self::$keyword_url_cache;
    }

    /**
     * Checks to see if the current rule's keyword + url are already stored in the database
     * @return int
     **/
    public static function checkIfAutolinkRuleExists($keyword = '', $link = ''){
        if(empty($keyword) || empty($link)){
            return false;
        }

        $cache = self::getAutolinkKeywordUrlCache();
        $id = md5($keyword . '_' . $link);

        return isset($cache[$id]) && !empty($cache[$id]);
    }

    /**
     * Make links from all keywords for certain post
     *
     * @param $post
     */
    public static function addKeywordsToPost($post)
    {
        if (!in_array($post->getRealType(), Wpil_Settings::getAllTypes()) || !$post->statusApproved()) {
            return;
        }

        if (in_array($post->type . '_' . $post->id, Wpil_Settings::getIgnoreKeywordsPosts())) {
            return;
        }

        // exit if we've just inserted selected links so we don't insert duplicates
        if(!empty($_POST) && isset($_POST['action']) && 'wpil_insert_selected_keyword_links' === $_POST['action']){
            return;
        }

        // if we've already processed the post's content for autolinks
        if(Wpil_Base::action_happened('did_passive_autolink_queue') && empty(Wpil_Post::get_active_editors())){
            // exit
            return;
        }

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache(true);

        $max_links_per_post = get_option('wpil_max_links_per_post', 0);

        self::prepareTable();
        update_option('wpil_post_procession', time());
        Wpil_Base::update_option_cache('wpil_post_procession', time());
        $post_language = Wpil_Post::getPostLanguageCode($post);
        $keywords = self::getKeywords();
        $url_index = array();
        $keyword_index = array();
        $content = $post->getContent();
        $lowercased_content = mb_strtolower($content);
        foreach ($keywords as $key => $keyword) {
            $keyword->keyword = stripslashes($keyword->keyword);

            if(empty($keyword->keyword)){
                continue;
            }

            if (
                (!empty($keyword->case_sensitive) && strpos($content, $keyword->keyword)) || // if this is a case sensitive keyword and it doesn't show up in the content
                strpos($lowercased_content, mb_strtolower($keyword->keyword)) === false || // OR if it's a non-case sensitive keyword
                empty(preg_match('/(?<![a-zA-Z][\'\-_]|[a-zA-Z]|="|=\\\"|\/)'.preg_quote(mb_strtolower($keyword->keyword), '/').'(?![a-zA-Z]|[\'\-_][a-zA-Z]|<\/a>|<\\\\\/a>)/', $lowercased_content, $matches)) || empty($matches)) // OR the keyword only shows up inside of existing lnks
            {
                unset($keywords[$key]);
                continue;
            }
            // if a link with the current link's url is slated to be installed and the current link doesn't have rules to insert more than once
            if(isset($url_index[$keyword->link]) && !empty($keyword->link_once) && empty($keyword->add_same_link)){
                // remove it from the list
                unset($keywords[$key]);
                continue;
            }
            $internal = Wpil_Link::isInternal($keyword->link);
            $link_post = ($internal || Wpil_Link::isAffiliateLink($keyword->link)) ? Wpil_Post::getPostByLink($keyword->link): null;

            if(apply_filters('wpil_force_include_autolink_post', false, $post, $keyword, $link_post)){
                $url_index[$keyword->link] = true;
                $keyword_index[$keyword->keyword] = true;
                continue;
            }

            if (!empty($link_post) && !empty($link_post->type) && $link_post->type == $post->type && $link_post->id == $post->id) {
                unset($keywords[$key]);
                continue;
            }

            // if there's a keyword with the same text already slated up
            if(isset($keyword_index[$keyword->keyword])){
                // remove it from the list
                unset($keywords[$key]);
                continue;
            }

            if(self::keywordAtLimit($keyword)){
                unset($keywords[$key]);
                continue;
            }

            // if the keyword is set to only link when the target is published and it hasn't been yet 
            if( (!empty($link_post) && !empty($keyword->restrict_to_live) && $link_post->type === 'post' && $link_post->getStatus() !== 'publish') ||
                (empty($link_post) && !empty($keyword->restrict_to_live) && $internal)
            ){
                // remove the keyword from the list
                unset($keywords[$key]);
                continue;
            }

            if( (!empty($post_language) &&      // if the post has a set language
                !empty($link_post) &&           // and the autolink is pointing to an internal post
                !empty($keyword->same_lang) &&  // and its restricted to a specific language
                $post_language !== Wpil_Post::getPostLanguageCode($link_post))  // and that language doesn't match the target post's
                ||  // ORRRR!!!
                (!empty($keyword->same_lang) && empty($post_language))  // the keyword is restricted by language, but we can't tell what the post's language is
            ){
                // remove it from the list
                unset($keywords[$key]);
                continue;
            }
            $url_index[$keyword->link] = true;
            $keyword_index[$keyword->keyword] = true;
        }

        // remove any existing possible links
        self::deletePossibleLinksByPost($post);

        if (!empty($keywords)) {
            // compile the keyword texts so we can ignore them when splitting the phrases
            $ignore_texts = array_map(function($keyword){ return $keyword->keyword; }, $keywords);
            // create a list for the insertable links
            $possible_links = array();

            $phrases = Wpil_Suggestion::getPhrases($post->getFreshContent(), true, array(), true, $ignore_texts);
            foreach ($keywords as $keyword) {
                // if there is a limit to the number of links and this isn't a manually selected autolink
                if(!empty($max_links_per_post) && Wpil_link::at_max_outbound_links($post)){
                    continue;
                }

                $possible_links = array_merge($possible_links, self::makeLinks($phrases, $keyword, $post, true));
            }

            // if we have links
            if(!empty($possible_links)){
                // insert them with the appropriate inserter
                if ($post->type == 'term') {
                    // add any existing links to insert to the metadata
                    $existing_links = Wpil_Toolbox::get_encoded_term_meta($post->id, 'wpil_links', true);

                    $known_sentences = array();
                    if(!empty($existing_links)){
                        foreach($existing_links as $link){
                            $known_sentences[] = $link['sentence'];
                        }
                    }
    
                    // filter out duplicate sentence matches so we don't have a big stack of links for the same string
                    $link_list = array();
                    foreach($possible_links as $link){
                        if(!isset($link_list[$link['sentence']]) && !in_array($link['sentence'], $known_sentences, true)){
                            $link_list[$link['sentence']] = $link;
                        }
                    }
    
                    $possible_links = array_values($link_list);
                    $possible_links = (!empty($existing_links) && is_array($existing_links)) ? array_merge($possible_links, $existing_links): $possible_links;

                    Wpil_Toolbox::update_encoded_term_meta($post->id, 'wpil_links', $possible_links);

                    Wpil_Term::addLinksToTerm($post->id);
                    delete_term_meta($post->id, 'wpil_links');
                } else {
                    // add any existing links to insert to the metadata
                    $existing_links = Wpil_Toolbox::get_encoded_post_meta($post->id, 'wpil_links', true);

                    $known_sentences = array();
                    if(!empty($existing_links)){
                        foreach($existing_links as $link){
                            $known_sentences[] = $link['sentence'];
                        }
                    }

                    // filter out duplicate sentence matches so we don't have a big stack of links for the same string
                    $link_list = array();
                    foreach($possible_links as $link){
                        if(!isset($link_list[$link['sentence']]) && !in_array($link['sentence'], $known_sentences, true)){
                            $link_list[$link['sentence']] = $link;
                        }
                    }

                    $possible_links = array_values($link_list);
                    $possible_links = (!empty($existing_links) && is_array($existing_links)) ? array_merge($possible_links, $existing_links): $possible_links;

                    Wpil_Toolbox::update_encoded_post_meta($post->id, 'wpil_links', $possible_links);

                    // add the links to the content
                    Wpil_Post::addLinksToContent(null, ['ID' => $post->id], array());
                    delete_post_meta($post->id, 'wpil_links');
                }
            }
        }

        self::deleteGhostLinks($post);
        update_option('wpil_post_procession', 0);
        Wpil_Base::update_option_cache('wpil_post_procession', 0);
    }

    /**
     * Replace keyword with link
     *
     * @param $phrases
     * @param $keyword
     * @param $post
     * @param $return_meta Should we return the links to insert or not? Default is No so the links are inserted directly
     */
    public static function makeLinks($phrases, $keyword, $post, $return_meta = false)
    {
        if (self::canAddLink($post, $keyword)) {
            $meta = [];
            $keyword->keyword = stripslashes($keyword->keyword);
            foreach ($phrases as $phrase) {
                $begin = 0;
                while ($begin <= mb_strlen($phrase->text) && mb_stripos($phrase->text, $keyword->keyword, $begin) !== false) {
                    $begin = mb_stripos($phrase->text, $keyword->keyword, $begin);
                    if (!self::isPartOfWord($phrase->text, $keyword->keyword, $begin) && 
                        !self::insideLink($phrase->src, $keyword->keyword) && 
                        !self::insideHeading($phrase->src, $keyword->keyword, $post) && 
                        self::forceLinkCheck($phrase->sentence_src, $keyword) &&
                        !self::keywordAtLimit($keyword))
                    {
                        // if 
                        if( false !== strpos($keyword->keyword, '&') &&             // if there's an unencoded "&" in the keyword
                            false !== stripos($phrase->text, $keyword->keyword) &&  // the keyword is in the text
                            false === stripos($phrase->src, $keyword->keyword))     // but it's not in the src
                        {
                            $search_keyword = str_replace('&', '&amp;', str_replace('&amp;', '&', $keyword->keyword));
                        }else{
                            $search_keyword = $keyword->keyword;
                        }

                        // create the keyword search regex. By default, it's case-sensitive
                        $keyword_search = '/(?<![a-zA-Z][\'\-_]|[a-zA-Z])'.preg_quote($search_keyword, '/').'(?![a-zA-Z]|[\'\-_][a-zA-Z])/';
                        // if the keyword isn't explicitly case-sensitive
                        if(!isset($keyword->case_sensitive) || empty($keyword->case_sensitive)){
                            // make it insensitive
                            $keyword_search .= 'i';
                        }

                        preg_match($keyword_search, $phrase->src, $case_match);

                        if(empty($case_match[0])){
                            break;
                        }

                        $case_keyword = $case_match[0];
                        $custom_sentence = preg_replace('/(?<![a-zA-Z][\'\-_]|[a-zA-Z])'.preg_quote($case_keyword, '/').'(?![a-zA-Z]|[\'\-_][a-zA-Z])/', self::getFullLink($keyword, $case_keyword, $post), $phrase->src, 1);
                        if ($custom_sentence == $phrase->src) {
                            break;
                        }

                        // if the user wants to select links before inserting
                        if($keyword->select_links){
                            // save the link data to the possible links table
                            self::savePossibleLinkToDB($post, $phrase, $keyword, $case_keyword, $custom_sentence);
                        }else{
                            $before_custom_sentence = mb_substr($phrase->sentence_src, 0, mb_strpos($phrase->sentence_src, $phrase->src));
                            $after_custom_sentence = mb_substr($phrase->sentence_src, mb_strpos($phrase->sentence_src, $phrase->src) + mb_strlen($phrase->src));
                        
                            $custom_sentence = $before_custom_sentence . $custom_sentence . $after_custom_sentence;

                            $meta[] = [
                                'id' => $post->id,
                                'type' => $post->type,
                                'sentence' => $phrase->sentence_src,
                                'sentence_with_anchor' => '',
                                'added_by_keyword' => 1,
                                'custom_sentence' => $custom_sentence,
                                'keyword_data' => $keyword
                            ];

                            self::saveLinkToDB($keyword, $post, $case_keyword);

                        }

                        //Break loop if post should contain only one link for this keyword
                        if (!empty($keyword->link_once)) {
                            break 2;
                        }
                    }

                    $begin++;
                }
            }

            // if we're supposed to return the links instead of inserting them now
            if($return_meta){
                // return them if we have them, and an empty array if there aren't any
                return (!empty($meta)) ? $meta: array();
            }

            //add links to all editors
            if (!empty($meta)) {
                if ($post->type == 'term') {
                    // add any existing links to insert to the metadata
                    $existing_links = Wpil_Toolbox::get_encoded_term_meta($post->id, 'wpil_links', true);
                    $meta = (!empty($existing_links) && is_array($existing_links)) ? array_merge($meta, $existing_links): $meta;

                    Wpil_Toolbox::update_encoded_term_meta($post->id, 'wpil_links', $meta);
                    Wpil_Term::addLinksToTerm($post->id);
                    delete_term_meta($post->id, 'wpil_links');
                } else {
                    // add any existing links to insert to the metadata
                    $existing_links = Wpil_Toolbox::get_encoded_post_meta($post->id, 'wpil_links', true);
                    $meta = (!empty($existing_links) && is_array($existing_links)) ? array_merge($meta, $existing_links): $meta;

                    Wpil_Toolbox::update_encoded_post_meta($post->id, 'wpil_links', $meta);
                    Wpil_Post::addLinksToContent(null, ['ID' => $post->id], array(), false, true);
                    delete_post_meta($post->id, 'wpil_links');
                }
            }
        }

        // if we're down here and we're supposed to return links
        if($return_meta){
            // return an empty array since the autolink couldn't be inserted
            return array();
        }
    }

    /**
     * Get full link for replace
     *
     * @param $keyword
     * @param $link
     * @param object|bool $post A Wpil_Post_Model object for the current item getting an autolink
     * @return string
     */
    public static function getFullLink($keyword, $caseKeyword = '', $post = false)
    {

        $is_external = !Wpil_Link::isInternal($keyword->link);
        $open_new_tab = (int)get_option('wpil_2_links_open_new_tab', 0);
        $open_external_new_tab = false;
        if($is_external){
            $open_external_new_tab = get_option('wpil_external_links_open_new_tab', null);
        }

        //add target blank if needed
        $blank = '';
        $rel = '';
        if (($open_new_tab == 1 && empty($is_external)) || 
            ($is_external && $open_external_new_tab) ||
            ($open_new_tab == 1 && $open_external_new_tab === null)
        ) {
            $noreferrer = !empty(get_option('wpil_add_noreferrer', false)) ? ' noreferrer': '';
            $blank = 'target="_blank" ';
            $rel = 'rel="noopener' . $noreferrer;
        }

        // if the user has set external links to be nofollow, this is an external link, and this isn't an interlinked site
        if(
            !empty(get_option('wpil_add_nofollow', false)) && 
            $is_external && 
            !empty(wp_parse_url($keyword->link, PHP_URL_HOST)) &&
            !in_array(wp_parse_url($keyword->link, PHP_URL_HOST), Wpil_SiteConnector::get_linked_site_domains(), true))
        {
            if(empty($rel)){
                $rel = 'rel="nofollow';
            }else{
                $rel .= ' nofollow';
            }
        }

        // if the user has set some domains to be listed as sponsored
        if(
            $is_external && 
            !empty(wp_parse_url($keyword->link, PHP_URL_HOST)) &&
            Wpil_Link::isSponsoredLink($keyword->link))
        {
            if(empty($rel)){
                $rel = 'rel="sponsored';
            }else{
                $rel .= ' sponsored';
            }
        }

        if(!empty($rel)){
            $rel .= '"';
        }

        /**
         * allow the users to add classes to the link
         * @param string The class list
         * @param bool $external Is the link going to an external site?
         * @param string The location of the filter
         **/
        $classes = apply_filters('wpil_link_classes', '', $is_external, 'keyword');

        // if the user returned an array, stringify it
        if(is_array($classes)){
            $classes = implode(' ', $classes);
        }

        $classes = (!empty($classes)) ? sanitize_text_field($classes): '';

        $wp_object = null;
        if($post->type === 'post'){
            $wp_object = get_post($post->id);
        }elseif($post->type === 'term'){
            $wp_object = get_term($post->id);
        }

        $title = '';
        if(!empty(get_option('wpil_add_destination_title', false))){
            $dest_post = Wpil_Post::getPostByLink($keyword->link);

            if(!empty($dest_post)){
                if($dest_post->type === 'post'){
                    $wp_post = get_post($dest_post->id);
                    $title = apply_filters('wpil_link_destination_title', str_replace(array('[', ']'), array('&#91;', '&#93;'), get_the_title($wp_post)), $wp_post);
                }else{
                    $term = get_term($dest_post->id);
                    $title = apply_filters('wpil_link_destination_title', str_replace(array('[', ']'), array('&#91;', '&#93;'), $term->name), $term);
                }
            }
        }

        if(empty($title)){
            $title = str_replace(array('[', ']'), array('&#91;', '&#93;'), $caseKeyword);
        }

        $title = esc_attr(apply_filters('wpil_filter_autolink_title', $title, $wp_object, $keyword));

        $tracking_id = '';
        if(!Wpil_Settings::get_disable_link_insert_tracking()){
            $tracking_id .= ' data-wpil-replace=""'; // we're using a valid attr as a replace target so if there's any issues, it will parse just fine
        }

        return '<a class="' . trim('wpil_keyword_link ' . $classes) . '" href="' . $keyword->link . '" ' . $blank . ' ' . $rel . ' title="' . $title . '" data-wpil-keyword-link="linked"' . $tracking_id . '>' . $caseKeyword . '</a>';
    }

    /**
     * Check if link can be added to certain post
     *
     * @param $post
     * @param $keyword
     * @return bool
     */
    public static function canAddLink($post, $keyword)
    {
        global $wpdb;
        if(empty($keyword->keyword)){
            return false;
        }
        if (empty($keyword->add_same_link)) {
            $links = [];
            $outbound = Wpil_Report::getOutboundLinks($post, true);
            foreach (array_merge($outbound['internal'], $outbound['external']) as $l) {
                $links[] = Wpil_Link::normalize_url($l->url);
            }

            if (in_array(Wpil_Link::normalize_url($keyword->link), $links)) {
                return false;
            }
        }

        if (!empty($keyword->link_once)) {
            preg_match('|<a [^><]*?href=[\'"]' . preg_quote($keyword->link, '|') . '[\'"][^><]*?>.*?</a>|i', $post->getContent(), $matches);
            if (!empty($matches[0])) {
                return false;
            }
        }

        $internal = Wpil_Link::isInternal($keyword->link);
        $link_post = ($internal || Wpil_Link::isAffiliateLink($keyword->link)) ? Wpil_Post::getPostByLink($keyword->link): null;
        if (!empty($link_post) && !empty($link_post->type) && $link_post->getType() == 'Category') {
            $category_post = $wpdb->get_var("SELECT count(*) FROM {$wpdb->postmeta} WHERE post_id = {$post->id} AND meta_key = '_elementor_conditions' AND meta_value LIKE '%include/archive/category/{$link_post->id}%'");

            if (!empty((int)$category_post)) {
                return false;
            }
        }

        if( (!empty($keyword->restrict_to_live) && !empty($link_post) && $link_post->type === 'post' && $post->getStatus() !== 'publish') ||
            (!empty($keyword->restrict_to_live) && empty($link_post) && $internal)
        ){
            return false;
        }

        if($post->type === 'post' && isset($keyword->restricted_cats) && !empty($keyword->restricted_cats)){
            $in_cats = $wpdb->get_col("SELECT `object_id` FROM {$wpdb->term_relationships} WHERE `object_id` = {$post->id} && `term_taxonomy_id` IN ({$keyword->restricted_cats})");

            if(empty($in_cats)){
                return false;
            }
        }

        // if we're preventing twoway linking
        if(!empty($link_post) && get_option('wpil_prevent_two_way_linking', false)){
            $links = Wpil_Post::getLinkedPostIDs($post, false);
            foreach($links as $link){
                // if the post has been linked to by the destination post
                if(!empty($link->post) && (int)$link->post->id === (int)$link_post->id && $link->post->type === $link_post->type){
                    // exit
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Save inserted link to the DB table
     *
     * @param $keyword
     * @param $post
     */
    public static function saveLinkToDB($keyword, $post, $anchor = '')
    {
        global $wpdb;

        if(empty($anchor)){
            $anchor = $keyword->keyword;
        }

        $inserted = $wpdb->insert($wpdb->prefix . 'wpil_keyword_links', [
            'keyword_id' => $keyword->id,
            'post_id' => $post->id,
            'post_type' => $post->type,
            'anchor' => $anchor,
        ]);

        // if the insert was successful
        if(!empty($inserted)){
            // update the link insert count cache if it's here
            if(null !== self::$keyword_id_cache){
                if(isset(self::$keyword_id_cache[$keyword->id])){
                    self::$keyword_id_cache[$keyword->id] += (int)$inserted;
                }else{
                    self::$keyword_id_cache[$keyword->id] = (int)$inserted;
                }
            }
        }
    }

    /**
     * Save inserted link to the DB table
     *
     * @param object $post
     * @param object $phrase
     * @param object $keyword
     * @param string $case_keyword
     * @param string $custom_sentence
     */
    public static function savePossibleLinkToDB($post, $phrase, $keyword, $case_keyword, $custom_sentence)
    {
        global $wpdb;

        //replace changed phrase inside the sentence
        $custom_sentence = str_replace($phrase->src, $custom_sentence, $phrase->sentence_src);

        $meta_data = array(
            'id' => $post->id,
            'type' => $post->type,
            'sentence' => $phrase->sentence_src,
            'sentence_with_anchor' => '',
            'added_by_keyword' => 1,
            'custom_sentence' => $custom_sentence,
            'keyword_data' => $keyword
        );

        $wpdb->insert($wpdb->prefix . 'wpil_keyword_select_links', [
            'keyword_id' => $keyword->id,
            'post_id' => $post->id,
            'post_type' => $post->type,
            'sentence_text' => $phrase->sentence_src,
            'case_keyword' => $case_keyword,
            'meta_data' => serialize($meta_data)
        ]);
    }

    /**
     * Save keywords settings
     */
    public static function saveSettings()
    {
        $priority_setting = (isset($_POST['wpil_keywords_priority_setting'])) ? (int) $_POST['wpil_keywords_priority_setting']: 0;

        update_option('wpil_keywords_add_same_link', (int)$_POST['wpil_keywords_add_same_link']);
        update_option('wpil_keywords_link_once', (int)$_POST['wpil_keywords_link_once']);
        update_option('wpil_keywords_restrict_to_live', (int)$_POST['wpil_keywords_restrict_to_live']);
        update_option('wpil_keywords_select_links', (int) $_POST['wpil_keywords_select_links']);
        update_option('wpil_keywords_set_priority', (int) $_POST['wpil_keywords_set_priority']);
        update_option('wpil_keywords_priority_setting', $priority_setting);
        update_option('wpil_keywords_restrict_to_cats', (int)$_POST['wpil_keywords_restrict_to_cats']);
        update_option('wpil_keywords_case_sensitive', (int) $_POST['wpil_keywords_case_sensitive']);
        update_option('wpil_keywords_limit_inserts', (int) $_POST['wpil_keywords_limit_inserts']);
        update_option('wpil_keywords_insert_limit', (int) $_POST['wpil_keywords_insert_limit']);
        update_option('wpil_keywords_prioritize_longtail', (int) $_POST['wpil_keywords_prioritize_longtail']);
        update_option('wpil_keywords_same_lang', (int) $_POST['wpil_keywords_same_lang']);

        if( isset($_POST['wpil_keywords_restrict_to_cats']) && !empty($_POST['wpil_keywords_restrict_to_cats']) && 
            isset($_POST['restricted_cats']) && !empty($_POST['restricted_cats']))
        {
            $ids = array_filter(array_map(function($num){ return (int)$num; }, $_POST['restricted_cats']));
        }elseif(isset($_POST['wpil_keywords_restrict_to_cats']) && !empty($_POST['wpil_keywords_restrict_to_cats']) && isset($_POST['save_settings'])){
            $ids = array();
            foreach($_POST as $index => $value){
                if(false !== strpos($index, 'wpil_keywords_restrict_term_') && $value === 'on'){
                    $bits = explode('_', $index);
                    $id = end($bits);
                    $ids[] = $id;
                }
            }
            $ids = array_filter($ids);
        }

        $ids = (!empty($ids)) ? $ids: array();
        update_option('wpil_keywords_restricted_cats', $ids);
    }

    /**
     * Find deleted links in the post content and remove them from DB
     * TODO: Add a link limit cleanup here so that when we're deleting ghoast links, some housekeeping is done so that we keep to the limits
     * @param $post
     */
    public static function deleteGhostLinks($post)
    {
        $deleted = 0;
        foreach (self::getLinksByPost($post) as $link) {
            $keyword = self::getKeywordByID($link->keyword_id);
            if (!empty($keyword)) {
                $c = $post->getFreshContent();

                // if 
                if( false !== strpos($keyword->keyword, '&') && // if there's an unencoded "&" in the keyword
                    false === stripos($c, $keyword->keyword) && // the keyword is not in the text
                    false !== stripos($c, str_replace('&', '&amp;', str_replace('&amp;', '&', $keyword->keyword)))) // but it is in the src
                {
                    // encode the "&" in the keyword
                    $search_keyword = str_replace('&', '&amp;', str_replace('&amp;', '&', $keyword->keyword));
                }elseif(
                    false !== strpos($keyword->keyword, '\\\'') &&  // if there's a slashed quote in the keyword
                    false === stripos($c, $keyword->keyword) &&     // the keyword as it exists is not in the text
                    false !== stripos($c, str_replace('\\\'', '\'', $keyword->keyword)))
                {
                    // remove the slash from the keyword
                    $search_keyword = str_replace('\\\'', '\'', $keyword->keyword);
                }else{
                    $search_keyword = $keyword->keyword;
                } // TODO: If I have to accomodate one more character, create a freestanding keyword-adjuster

                preg_match_all('`<a (?:[^><]*?(?:class=["\'][^"\']*?wpil_keyword_link[^"\']*?["\']|data-wpil-keyword-link=["\']linked["\'])[^><]*?href=["\']' . preg_quote($keyword->link, '`') . '["\']|[^><]*?href=["\']' . preg_quote($keyword->link, '`') . '["\'].*?(?:data-wpil-keyword-link=["\']linked["\']))[^><]*?>' . preg_quote($search_keyword, '`') . '</a>`i', $c, $matches);
                if (empty($matches[0]) || count($matches[0]) != (int)$link->cnt) {
                    $deleted += self::deleteLink($link, count($matches[0]));
                }
            }
        }

        return $deleted;
    }

    /**
     * Update keyword settings
     */
    public static function updateKeywordSettings()
    {
        $keyword = self::getKeywordByID($_POST['keyword_id']);

        if (!empty($keyword)) {
            global $wpdb;

            $set_priority = (int)$_POST['wpil_keywords_set_priority'];
            $priority_setting = 0;
            if(isset($_POST['wpil_keywords_priority_setting'])){
                $priority_setting = (int)$_POST['wpil_keywords_priority_setting'];
            }

            $date = null;
            if(isset($_POST['wpil_keywords_restricted_date']) && !empty($_POST['wpil_keywords_restricted_date'])){
                $date = preg_replace("([^0-9-])", "", $_POST['wpil_keywords_restricted_date']);
                if($date !== $_POST['wpil_keywords_restricted_date']){
                    $date = null;
                }
            }

            $term_ids = '';
            if(isset($_POST['restricted_cats']) && !empty($_POST['restricted_cats'])){
                $ids = array_map(function($num){ return (int)$num; }, $_POST['restricted_cats']);
                $term_ids = implode(',', $ids);
            }

            $restrict_to_date = (int)$_POST['wpil_keywords_restrict_date'];
            $restrict_to_cats = (int)$_POST['wpil_keywords_restrict_to_cats'];
            $prioritize_longtail = (isset($_POST['wpil_keywords_prioritize_longtail']) && !empty($_POST['wpil_keywords_prioritize_longtail'])) ? 1: 0;

            if(!empty($prioritize_longtail) && (empty($priority_setting) || empty($set_priority)) ){
                $priority_setting = Wpil_Word::getWordCount($keyword->keyword);
            }

            $wpdb->update($wpdb->prefix . 'wpil_keywords', [
                'add_same_link' => (int)$_POST['wpil_keywords_add_same_link'],
                'link_once' => (int)$_POST['wpil_keywords_link_once'],
                'restrict_to_live' => (int)$_POST['wpil_keywords_restrict_to_live'],
                'limit_inserts' => (int)$_POST['wpil_keywords_limit_inserts'],
                'insert_limit' => (int)$_POST['wpil_keywords_insert_limit'],
                'select_links' => (int)$_POST['wpil_keywords_select_links'],
                'set_priority' => $set_priority,
                'priority_setting' => $priority_setting,
                'prioritize_longtail' => $prioritize_longtail,
                'restrict_date' => $restrict_to_date,
                'restricted_date' => $date,
                'restrict_cats' => $restrict_to_cats,
                'restricted_cats' => $term_ids,
                'case_sensitive' => (int)$_POST['wpil_keywords_case_sensitive'],
                'force_insert' => (int)$_POST['wpil_keywords_force_insert'],
                'same_lang' => (int)$_POST['wpil_keywords_same_lang'],
            ], ['id' => $keyword->id]);

            $removed_all = false;
            if($keyword->restrict_to_live == 0 && $_POST['wpil_keywords_restrict_to_live'] == 1){
                $removed_all = self::remove_unpublished_post_links($keyword);
            }

            // if we haven't removed all of the links from the site
            if(!$removed_all){
                if ($keyword->link_once == 0 && $_POST['wpil_keywords_link_once'] == 1) {
                    self::leftOneLink($keyword);
                }

                if ($keyword->add_same_link == 1 && $_POST['wpil_keywords_add_same_link'] == 0) {
                    self::removeSameLink($keyword);
                }

                // if date restricting has been turned on and a date is given or the given date is older than the saved date
                if( ($keyword->restrict_date == 0 && $restrict_to_date == 1 &&
                    !empty($date)) ||
                    (!empty($restrict_to_date) && !empty($date) && strtotime($date) > strtotime($keyword->restricted_date))
                ){
                    // update the keyword with the date
                    $keyword->restricted_date = $date;
                    // remove any autolinks on posts older than the set time
                    self::removeTooOldLinks($keyword->id);
                }

                if(!empty($term_ids)){
                    $keyword->restricted_cats = $term_ids;
                    self::removeCategoryRestrictedLinks($keyword);
                }

                if($keyword->force_insert == 1 && (int)$_POST['wpil_keywords_force_insert'] === 0){
                    self::removeForceInsertedLinks($keyword);
                }

                // remove any links over the limits
                self::removeLinksOverLimits($keyword);

                // clean up any links in the wrong languages
                self::checkSameLanguageLinks($keyword);
            }

            // clear any stored selectable links since we'll be adding new ones after this
            self::deletePossibleLinksForKeyword($keyword);

            // and process the ghost link cache to update the link totals
            self::ghost_link_cache_process();
        }
    }

    /**
     * Removes all of the given keyword's links if the target post is not published and
     * the keyword is set to only insert links if the target is visible.
     * 
     * @param object $keyword
     * @param object $post
     * @return bool Returns if we've processed removing the links or not
     **/
    public static function remove_unpublished_post_links($keyword, $post = null){
        global $wpdb;
        $table = $wpdb->prefix . 'wpil_keyword_links'; 
        $removed = false;

        $target_post = Wpil_Post::getPostByLink($keyword->link);
        if( (!empty($target_post) && ($target_post->type === 'term' || $target_post->getStatus() === 'publish')) ||
            (empty($target_post) && !Wpil_Link::isInternal($keyword->link))){
                return $removed;
        }

        if(!empty($post) && is_a($post, 'Wpil_Model_Post')){
            $links = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE `keyword_id` = %d AND `post_id` = %d AND `post_type` = %s", $keyword->id, $post->id, $post->type));
        }else{
            $links = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE `keyword_id` = %d", $keyword->id));
        }

        foreach ($links as $link) {
            $post = new Wpil_Model_Post($link->post_id, $link->post_type);
            if(self::removeAndUpdate($keyword, $post)){
                self::ghost_link_cache_add($post);
                $removed = true;
            }
        }

        return $removed;
    }

    /**
     * Remove all keyword links except one
     *
     * @param $keyword
     */
    public static function leftOneLink($keyword, $post = null)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wpil_keyword_links';

        if(!empty($post) && is_a($post, 'Wpil_Model_Post')){
            $links = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE `keyword_id` = %d AND `post_id` = %d AND `post_type` = %s", $keyword->id, $post->id, $post->type));
        }else{
            $links = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE `keyword_id` = %d GROUP BY `post_id`, `post_type` HAVING COUNT(`keyword_id`) > 1 ORDER BY `id` DESC", $keyword->id));
        }

        foreach ($links as $link) {
            $keyword = self::getKeywordByID($keyword->id);
            $post = new Wpil_Model_Post($link->post_id, $link->post_type);
            $content = $post->getCleanContent();
            $excerpt = $post->maybeGetExcerpt();
            self::removeNonFirstLinks($keyword, $content);

            // remove non-first links from the excerpts if present
            if(!empty($excerpt)){
                if(count(self::findKeywordLinks($keyword, $content)) > 0){
                    self::removeAllExcerptLinks($keyword, $post, $excerpt);
                }else{
                    self::removeNonFirstLinks($keyword, $excerpt);
                }
            }

            self::updateContent($content, $keyword, $post, true, $excerpt);
            self::ghost_link_cache_add($post);
        }
    }

    /**
     * Remove keyword links if post already has this link
     *
     * @param $keyword
     */
    public static function removeSameLink($keyword)
    {
        global $wpdb;

        if(empty($keyword) || !isset($keyword->id)){
            return;
        }

        $links = $wpdb->get_results($wpdb->prepare("SELECT post_id, post_type FROM {$wpdb->prefix}wpil_keyword_links WHERE keyword_id = %d GROUP BY post_id, post_type", $keyword->id));
        foreach ($links as $link) {
            $post = new Wpil_Model_Post($link->post_id, $link->post_type);
            $keyword = self::getKeywordByID($keyword->id);
            $content = $post->getCleanContent();
            $excerpt = $post->maybeGetExcerpt();

            $matches_keyword = self::findKeywordLinks($keyword, $content . "\n" . $excerpt);
            preg_match_all('|<a\s[^>]*href=["\']' . preg_quote($keyword->link, '|') . '[\'"][^>]*>|', $content . "\n" . $excerpt, $matches_all);

            if (count($matches_all[0]) > count($matches_keyword[0])) {
                self::removeAllLinks($keyword, $content);
                self::removeAllExcerptLinks($keyword, $post, $excerpt);
                self::updateContent($content, $keyword, $post, false, $excerpt);
                self::ghost_link_cache_add($post);
            }
        }
    }

    /**
     * Removes the keyword links from all posts that we're published before the link's time.
     * 
     * @param int $keyword_id
     **/
    public static function removeTooOldLinks($keyword_id){
        global $wpdb;

        if(empty($keyword_id)){
            return;
        }

        $keyword = self::getKeywordByID($keyword_id);

        // exit if there's no date
        if(empty($keyword->restricted_date)){
            return;
        }

        // get all the posts with the keywords
        $links = self::getLinksByKeyword($keyword->id);

        // exit if there's no links
        if(empty($links)){
            return;
        }

        // extract the post ids from the keywords
        $ids = array();
        foreach($links as $link){
            $ids[$link->post_id] = true;
        }

        $ids = implode(', ', array_keys($ids));

        // get all the posts that have been published before the given date
        $posts = $wpdb->get_results("SELECT ID FROM {$wpdb->posts} WHERE `ID` IN ({$ids}) AND `post_date_gmt` < '{$keyword->restricted_date}'");

        // exit if there's no posts published before the date
        if(empty($posts)){
            return;
        }

        // remove the links from the post contents
        foreach($posts as $post){
            $post = new Wpil_Model_Post($post->ID, 'post');
            self::removeAndUpdate($keyword, $post);
        }
    }

    /**
     * Remove all keyword links except one from curtain post
     *
     * @param $keyword
     * @param $content
     */
    public static function removeNonFirstLinks($keyword, &$content)
    {
        $links = self::findKeywordLinks($keyword, $content);

        if(is_array($links[0])){
            $links = $links[0];
        }

        if (count($links) > 1) {
            $begin = stripos($content, $links[0]) + strlen($links[0]);
            $first = substr($content, 0, $begin);
            $second = substr($content, $begin);
            self::removeAllLinks($keyword, $second);
            $content = $first . $second;
        }
    }

    /**
     * Remove all keyword links.
     * As in, remove this link from all places that it occurs in this specific post.
     * Not remove every single autolink
     *
     * @param $keyword
     * @param $content
     */
    public static function removeAllLinks($keyword, &$content, $de_autolink = false)
    {
        $links = self::findKeywordLinks($keyword, $content, true, true);
        if(!empty($links)){
            foreach($links as $link){
                foreach($links as $link){
                    $replace = ($de_autolink) ? str_replace(['class="wpil_keyword_link" ', 'wpil_keyword_link', ' data-wpil-keyword-link="linked"'], '', $link['link']): $link['anchor'];
                    $content = preg_replace('`' . preg_quote($link['link'], '`') . '`', $replace,  $content);
                }
            }
        }
    }

    /**
     * Removes the given autolink from the current post
     **/
    public static function removeAllMetaContentLinks($keyword, $post, $de_autolink = false){
        $fields = Wpil_Post::getMetaContentFieldList($post->type);

        $acf_fields = ($post->type === 'post') ? Wpil_Post::getAdvancedCustomFieldsList($post->id): Wpil_Term::getAdvancedCustomFieldsList($post->id);

        if(!empty($acf_fields)){
            $fields = array_merge($fields, $acf_fields);
        }

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $content = ($post->type === 'post') ? get_post_meta($post->id, $field, true): get_term_meta($post->id, $field, true);

                if(!is_string($content)){
                    continue;
                }

                self::removeAllLinks($keyword, $content, $de_autolink);

                if($post->type === 'post'){
                    update_post_meta($post->id, $field, $content);
                }else{
                    update_term_meta($post->id, $field, $content);
                }
            }
        }

        /**
         * Allows the user to remove the keywords from their own custom data fields
         **/
        do_action('wpil_meta_content_data_remove_autolinks', $post->id, $post->type, $keyword);
    }

    /**
     * Removes the given autolink from the current post's excerpt
     **/
    public static function removeAllExcerptLinks($keyword, $post, &$excerpt = '', $de_autolink = false){
        if($post->type === 'term' || empty($excerpt)){
            return;
        }

        self::removeAllLinks($keyword, $excerpt, $de_autolink);
    }

    /**
     * Removes links from all items that aren't in the categories listed by the user.
     * Also remove links from autolinking-ignored posts
     * 
     * @param $keyword
     **/
    public static function removeCategoryRestrictedLinks($keyword, $post = null){
        global $wpdb;
        $links = self::getLinksByKeyword($keyword->id, $post);

        if(empty($links)){
            return false;
        }

        // get all of the linked post ids
        $ids = array();
        foreach($links as $link){
            // skip the current item if it's a term
            if('term' === $link->post_type){
                continue;
            }
            $ids[$link->post_id] = true;
        }

        $ids = array_keys($ids);
        $cleanup_ids = array();

        // if the autolink is being restricted to specific cats
        if(isset($keyword->restricted_cats) && !empty($keyword->restricted_cats)){
            $search_ids = implode(',', $ids);

            // get all the linked post ids that do have the desired terms
            $found_ids = $wpdb->get_col("SELECT `object_id` FROM {$wpdb->term_relationships} WHERE `object_id` IN ({$search_ids}) && `term_taxonomy_id` IN ({$keyword->restricted_cats})");
    
            // diff the ids that have the terms against the autolinks on record to find the ones we need to clean
            $cleanup_ids = array_diff($ids, $found_ids);
        }

        // if there are posts being ignored from autolinking
        $ignored = Wpil_Settings::getIgnoreKeywordsPosts();
        if(!empty($ignored)){
            // try getting the ignored posts
            $ignore_ids = Wpil_Settings::getItemTypeIds($ignored, 'post');

            // if we have some
            if(!empty($ignore_ids)){

                // find all the ones that have this autolink and add them to the cleanup list
                $cleanup_ids = array_merge($cleanup_ids, array_intersect($ids, $ignore_ids)); // doesn't remove all autolinks from selected cats... Will have to do later
            }
        }

        if(!empty($cleanup_ids)){
            // remove the current keyword from the items
            foreach($cleanup_ids as $id){
                $post = new Wpil_Model_Post($id);
                self::removeAndUpdate($keyword, $post);
                self::ghost_link_cache_add($post);
            }
        }
    }

    /**
     * Removes force-inserted autolinks from sentences that already contain a link.
     * @param $keyword
     **/
    public static function removeForceInsertedLinks($keyword, $items = array()){
        global $wpdb;

        // get a list of all the posts that the keyword shows up in
        if(empty($items)){
            $items = $wpdb->get_results("SELECT post_id, post_type FROM {$wpdb->prefix}wpil_keyword_links WHERE keyword_id = {$keyword->id} GROUP BY post_id, post_type");
        }

        foreach ($items as $item) {
            $post = new Wpil_Model_Post($item->post_id, $item->post_type);
            $keyword = self::getKeywordByID($keyword->id);
            $content = $post->getCleanContent();
            $excerpt = $post->maybeGetExcerpt();

            self::removeAllLinks($keyword, $content);
            self::removeAllExcerptLinks($keyword, $post, $excerpt);
            self::updateContent($content, $keyword, $post, false, $excerpt);

            $phrases = Wpil_Suggestion::getPhrases($post->getFreshContent(), true, array(), true, array($keyword->keyword));

            // if there is a limit to the number of links and this isn't a manually selected autolink
            if(empty($max_links_per_post) || !Wpil_link::at_max_outbound_links($post)){
                self::makeLinks($phrases, $keyword, $post);
            }

            self::ghost_link_cache_add($post); // TODO keep an eye on support to make sure no one reports issues with setting updates timing out
        }

        update_option('wpil_post_procession', 0);
        Wpil_Base::update_option_cache('wpil_post_procession', 0);
    }

    public static function refreshAppliedKeyword($keyword = false, $post = null, $defer_ghost_process = false){
        if (!empty($keyword)) {
            // if date restricting has been turned on and a date is given or the given date is older than the saved date
/*            if( ($keyword->restrict_date == 1 && !empty($keyword->restricted_date))){
                // remove any autolinks on posts older than the set time
                self::removeTooOldLinks($keyword->id);
            }*/

//            self::cleanupDeletedPostIds($keyword); // todo make if needed to clear links pointing to deleted posts

            // check if the current link points to an unpublished post, and we're not supposed to link to unpublished posts
            $removed_all = self::remove_unpublished_post_links($keyword, $post);

            // if we haven't removed all the links with the prior check
            if(!$removed_all){
                self::removeCategoryRestrictedLinks($keyword, $post);

                self::removeSelfLinking($keyword);

                self::removeLinksOverLimits($keyword, $post);

                self::checkSameLanguageLinks($keyword, $post);

                self::removeKeywordsFromIgnoredClasses($keyword, $post);
            }
/*
            if($keyword->force_insert == 0){
                self::removeForceInsertedLinks($keyword);
            }
*/
            // clear any stored selectable links since we'll be adding new ones after this
            self::deletePossibleLinksForKeyword($keyword, $post);

            // if we're not waiting
            if(!$defer_ghost_process){
                // and process the ghost link cache to update the link totals
                self::ghost_link_cache_process();
            }
        }
    }

    /**
     * Removes accidental cases of post self linking... Usually from turning on the URL adjuster AFTER creating the autolinks
     **/
    public static function removeSelfLinking($keyword){
        global $wpdb;

        if(empty($keyword)){
            return;
        }

        $post = (Wpil_Link::isInternal($keyword->link) || Wpil_Link::isAffiliateLink($keyword->link)) ? Wpil_Post::getPostByLink($keyword->link): null;

        if(!empty($post) && $post->type == 'post'){
            $found_post = $wpdb->get_col($wpdb->prepare("SELECT `ID` FROM {$wpdb->posts} WHERE `ID` = %d AND `post_content` LIKE %s", $post->id, Wpil_Toolbox::esc_like($keyword->link)));
        
            if(!empty($found_post)){
                foreach($found_post as $id){
                    $posty = new Wpil_Model_Post($id);
                    self::removeAndUpdate($keyword, $posty);
                    self::ghost_link_cache_add($posty);
                }
            }
        }
    }

    /**
     * 
     **/
    public static function checkSameLanguageLinks($keyword, $post = null){
        // if we're restricting links to posts in the same language
        if(!empty($keyword) && isset($keyword->same_lang) && !empty($keyword->same_lang)){
            // get the post that the link points to 
            $target_post = (Wpil_Link::isInternal($keyword->link) || Wpil_Link::isAffiliateLink($keyword->link)) ? Wpil_Post::getPostByLink($keyword->link): null;

            // 
            if(empty($target_post) || $target_post->type !== 'post'){
                return;
            }
                
            // if we've been given a post to check
            if(!empty($post) && is_a($post, 'Wpil_Model_Post')){
                // check the language of it and the target post
                $post_lang = Wpil_Post::getPostLanguageCode($post);
                $target_lang = Wpil_Post::getPostLanguageCode($target_post);

                // if they are the same
                if($post_lang == $target_lang){
                    // exit now
                    return;
                }else{
                    // if they don't match
                    // remove every instance of this link from the post
                    self::removeAndUpdate($keyword, $post);

                    // add the post to the ghost link update list
                    self::ghost_link_cache_add($post);

                    // and exit
                    return;
                }
            }

            // try getting the ids for posts in the same language as this one
            $same_lang_ids = Wpil_Post::getSameLanguagePosts($target_post->id);

            // exit if there aren't any
            if(empty($same_lang_ids)){
                return;
            }

            // get the ids for all posts that have this link
            $existing_post_ids = self::getAutolinkedPostIds($keyword->id);

            // diff the two lists of ids to find the posts that need to have links removed
            $remove_posts = array_diff($existing_post_ids, $same_lang_ids);

            // if we found some
            if(!empty($remove_posts)){
                // go over the posts
                foreach($remove_posts as $id){
                    $post = new Wpil_Model_Post($id);

                    // remove every instance of this link from the post
                    self::removeAndUpdate($keyword, $post);

                    // and add the post to the ghost link update list
                    self::ghost_link_cache_add($post);
                }
            }
        }
    }

    /**
     * Removes a specific autolink from any class ignored elements where it has been inserted.
     * Useful for those follow-up cases where the user added a new CSS class to ignore and now he wants to refresh the autolinks.
     * 
     * For the time being, focusing only on the WP content and the editors that use WP content as a backup since we don't want to overload the system.
     **/
    public static function removeKeywordsFromIgnoredClasses($keyword, $post = null){
        global $wpdb;
        $table = $wpdb->prefix . 'wpil_keyword_links';
        $memory_break_point = Wpil_Report::get_mem_break_point();

        if(empty(Wpil_Settings::get_ignored_element_classes())){
            return;
        }

        if(!empty($post) && is_a($post, 'Wpil_Model_Post')){
            $posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE `keyword_id` = %d AND `post_id` = %d AND `post_type` = %s", $keyword->id, $post->id, $post->type));
        }else{
            $posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE `keyword_id` = %d ORDER BY `id` DESC", $keyword->id));
        }

        if(!empty($posts)){
            foreach($posts as $posty){
                // if we're close to the time limit or the memory limit, exit
                if(Wpil_Base::overTimeLimit(25, 30) || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point) ){
                    break;
                }

                $post = new Wpil_Model_Post($posty->post_id, $posty->post_type);
                $content = $post->getCleanContent();
                $excerpt = $post->maybeGetExcerpt();
        
                $old_content = md5($content);
                $old_excerpt = md5($excerpt);

                self::removeLinksInIgnoredClasses($keyword, $content);
                self::removeLinksInIgnoredClasses($keyword, $excerpt);

                if(md5($content) !== $old_content || md5($excerpt) !== $old_excerpt){
                    self::updateContent($content, $keyword, $post, false, $excerpt);
                    self::ghost_link_cache_add($post);
                }
            }
        }
    }

    /**
     * Removes Autolink links from 
     **/
    public static function removeLinksInIgnoredClasses($keyword, &$content){

        // get the classes to ignore
        $remove_classes = Wpil_Settings::get_ignored_element_classes();

        // exit if the user isn't ignoring any classed elements
        if(empty($remove_classes)){
            return $content;
        }

        // if 
        if( false !== strpos($keyword->keyword, '&') && // if there's an unencoded "&" in the keyword
            false === stripos($content, $keyword->keyword) && // the keyword is not in the text
            false !== stripos($content, str_replace('&', '&amp;', str_replace('&amp;', '&', $keyword->keyword)))) // but it is in the src
        {
            // encode the "&" in the keyword
            $search_keyword = str_replace('&', '&amp;', str_replace('&amp;', '&', $keyword->keyword));
        }elseif(
            false !== strpos($keyword->keyword, '\\\'') &&  // if there's a slashed quote in the keyword
            false === stripos($content, $keyword->keyword) &&     // the keyword as it exists is not in the text
            false !== stripos($content, str_replace('\\\'', '\'', $keyword->keyword)))
        {
            // remove the slash from the keyword
            $search_keyword = str_replace('\\\'', '\'', $keyword->keyword);
        }else{
            $search_keyword = $keyword->keyword;
        } // TODO: If I have to accomodate one more character, create a freestanding keyword-adjuster

        // create the regex for sniffing out the autolinks to remove
        $link_regex = '`(?:<a\s[^><]*?(?:class=["\'][^"\']*?wpil_keyword_link[^"\']*?["\']|data-wpil-keyword-link="linked")[^><]*?(href|url)=[\'\"]' . preg_quote($keyword->link, '`') . '*?[\'\"][^><]*?>|<a\s[^><]*?(href|url)=[\'\"]' . preg_quote($keyword->link, '`') . '*?[\'\"][^><]*?(?:class=["\'][^"\']*?wpil_keyword_link[^"\']*?["\']|data-wpil-keyword-link="linked")[^><]*?>)(?!<a)(' . $search_keyword . ')<\/a>`i';

        // create a list of the different styles of regex
        $regex_strings = array(
            'right_wildcard' => '',
            'left_wildcard' => '',
            'all_wildcard' => '',
            'no_wildcard' => ''
        );

        // sort the classes based on wildcard matching status and "OR" separate them in the search strings
        foreach($remove_classes as $class){
            // if the class isn't in the content, skip to the next class
            if(false === strpos($content, trim(trim($class, '*')))){
                continue;
            }

            $left   = ('*' === substr($class, 0, 1)) ? true: false;
            $right  = ('*' === substr($class, -1)) ? true: false;

            // TODO: double check these wildcard matches to make sure that we aren't just checking the first item for wildcard matching
            if($left & $right){
                $regex_strings['all_wildcard'] .= '|' . preg_quote(trim(trim($class, '*')));
            }elseif($left){
                $regex_strings['left_wildcard'] .= '|' . preg_quote(trim(trim($class, '*')));
            }elseif($right){
                $regex_strings['right_wildcard'] .= '|' . preg_quote(trim(trim($class, '*')));
            }else{
                $regex_strings['no_wildcard'] .= '|' . preg_quote(trim(trim($class, '*')));
            }
        }

        // go over the search strings
        foreach($regex_strings as $index => $string){
            if(empty($string)){
                continue;
            }

            // remove any leading/trailling "OR"s
            $regex_class = trim($string, '|');

            // create the class search string for the appropriate wildcard settings
            if($index === 'all_wildcard'){
                $regex_class = '["\'][^"\'<>]*?(' . $regex_class . ')[^"\'<>]*?["\']';
            }elseif($index === 'left_wildcard'){
                $regex_class = '["\'][^"\'<>]*?(' . $regex_class . ')(?:["\']|\s[^"\'<>]*?["\'])';
            }elseif($index === 'right_wildcard'){
                $regex_class = '(?:["\']|["\'][^"\'<>]*?\s)(' . $regex_class . ')[^"\'<>]*?["\']';
            }else{
                $regex_class = '(?:["\']|["\'][^"\'<>]*?\s)(' . $regex_class . ')(?:["\']|\s[^"\'<>]*?["\'])';
            }

            // create a regext that searches for opening/closing elements with the classes, and any images with the classes
            $regex = '/<([a-zA-Z]*?[^\s<>]*?)[\s][^<>]*?(class=' . $regex_class . ')[^<>]*?>[\s\S]*?<\/\1>|<img[\s][^<>]*?(class=' . $regex_class . ')[^<>]*?>/';
            // NOTE: This regex will remove the opening tag that contains the class and any closing tag that matches the opening tag's name.
            // So it's possible to remove everything between the first opening tag in a bunch of nested divs and the closing tag of the innermost element.
            // Leaving behind a number of unaffiliated closing divs.
            // Since we don't directly use most of the content we process, this _shouldn't_ be an issue for suggestions.
            // But it does mean that the reach of this is somewhat limited.
            // And if we do make the mistake of using phrase data for saving purposes, it could cause some issues.

            // find all of the classed sections
            preg_match_all($regex, $content, $matches);

            // if we have ignored section matches
            if(!empty($matches) && isset($matches[0]) && !empty($matches[0])){
                // go over matches
                foreach($matches[0] as $match){
                    // check each one for the current autolink
                    preg_match_all($link_regex, $content, $matches2);
                    // if we've found any inside the section
                    if(!empty($matches2) && isset($matches2[0]) && !empty($matches2[0])){
                        // go over the link matches
                        foreach($matches2[0] as $match2){
                            // if we're really sure that the section and the link are the same as in the content (and haven't somehow been changed)
                            if(false !== mb_strpos($content, $match) && false !== mb_strpos($content, $match2)){
                                // remove the link from inside the section
                                $replace = mb_eregi_replace(preg_quote($match2), $search_keyword, $match);
                                // and replace the old section in the post with the new one
                                $updated = mb_eregi_replace(preg_quote($match), $replace, $content);

                                // if that worked
                                if(!empty($updated)){
                                    // update the content!
                                    $content = $updated;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $content;
    }


    /**
     * Removes links that have been inserted above the posted limits.
     * Starts from the last posts to get linked, and moves down the list until enough links are removed to bring the totals into balance
     **/
    public static function removeLinksOverLimits($keyword, $target_post = null){
        global $wpdb;
        $table = $wpdb->prefix . 'wpil_keyword_links';

        // if the keyword is at the limit
        if(!empty($keyword) && self::keywordAtLimit($keyword)){
            // get the total number of links that have been inserted
            $current_inserts = self::getAutolinkInsertCountById($keyword->id);
            // get a list of the posts with inserted links starting with the most recent posts, working backwards to the oldest
            
            if(!empty($target_post) && is_a($target_post, 'Wpil_Model_Post')){
                $posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE `keyword_id` = %d AND `post_id` = %d AND `post_type` = %s", $keyword->id, $target_post->id, $target_post->type));
            }else{
                $posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE `keyword_id` = %d ORDER BY `id` DESC", $keyword->id));
            }

            if(!empty($posts)){
                // keep track of how many we've removed
                $removed = 0;
                // count how many times the link shows up in each post
                $count = array();
                foreach($posts as $post_data){
                    $id = $post_data->post_id . '_' . $post_data->post_type;

                    if(!isset($count[$id])){
                        $count[$id] = 1;
                    }else{
                        $count[$id]++;
                    }
                }

                // go over the posts
                foreach($posts as $post_data){
                    $post = new Wpil_Model_Post($post_data->post_id, $post_data->post_type);

                    // and remove every instance of this link from the posts until we're within the limit
                    self::removeAndUpdate($keyword, $post);

                    $removed += $count[$post_data->post_id . '_' . $post_data->post_type];

                    // add the post to the ghost link update list
                    self::ghost_link_cache_add($post);

                    // if we've removed enough links that we're within the limits
                    if(($current_inserts - $removed) <= $keyword->insert_limit){
                        // stopp processing the posts
                        break;
                    }
                }
            }
        }

        // if the link is set to 'once per post', check to make sure that there aren't posts that contain the link more than once
        if(!empty($keyword->link_once)){
            self::leftOneLink($keyword, $target_post);
        }
    }

    /**
     * Refreshes the links for a specific post
     **/
    public static function refresh_post_keywords($post){
        if(empty($post) || !is_a($post, 'Wpil_Model_Post') || empty($post->id)){
            return;
        }

        $memory_break_point = Wpil_Report::get_mem_break_point();
        $keywords = self::get_post_keywords($post);

        if(!empty($keywords)){
            foreach($keywords as $keyword){
                if(Wpil_Base::overTimeLimit(15, 30) || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point) ){
                    break;
                }

                self::refreshAppliedKeyword($keyword, $post, true);
            }
            self::ghost_link_cache_process();
        }
    }

    /**
     * Find keyword links in the content
     *
     * @param $keyword
     * @param $content
     * @param bool $return_text Should the anchor texts be returned for case sensitive matching?
     * @param bool $find_encoded Should we also search for autolinks that have been unicode-encoded for safe storage in JSON? (Default false so that we don't tag invisible and incorrectly added JSON links toward things like the "One per Post" setting)
     * @return array
     */
    public static function findKeywordLinks($keyword, $content, $return_text = false, $find_encoded = false)
    {
        $search_keyword = preg_quote($keyword->keyword, '`');
        if(false !== strpos($keyword->keyword, '&')){
            // look for HTML entity encoded "&"s too
            $search_keyword .= "|" . preg_quote(str_replace('&', '&amp;', str_replace('&amp;', '&', $keyword->keyword)), '`');
        }

        if(false !== strpos($keyword->keyword, '\\\'')){
            // look for slashed quotes/apostrophes if they're present
            $search_keyword .= "|" . preg_quote(str_replace('\\\'', '\'', $keyword->keyword), '`');
        }

        preg_match_all('`(?:<a\s[^><]*?(?:class=["\'][^"\']*?wpil_keyword_link[^"\']*?["\']|data-wpil-keyword-link="linked")[^><]*?(href|url)=[\'\"]' . preg_quote($keyword->link, '`') . '*?[\'\"][^><]*?>|<a\s[^><]*?(href|url)=[\'\"]' . preg_quote($keyword->link, '`') . '*?[\'\"][^><]*?(?:class=["\'][^"\']*?wpil_keyword_link[^"\']*?["\']|data-wpil-keyword-link="linked")[^><]*?>)(?!<a)(' . $search_keyword . ')<\/a>`i', $content, $matches);

        // if there is a unicode encoded link such as what we'd expect to find in JSON...
        if($find_encoded && false !== stripos($content, '\u003ca')){
            // check to see if autolinks have been saved in that content
            preg_match_all('`(?:\\\u003ca\s[^><"\']*?(?:class=\\\u0022[^"\']*?wpil_keyword_link[^"\']*?\\\u0022|data-wpil-keyword-link=\\\u0022linked\\\u0022)[^><"\']*?(href|url)=\\\u0022' . preg_quote($keyword->link, '`') . '*?\\\u0022[^><"\']*?\\\u003e|\\\u003ca\s[^><"\']*?(href|url)=\\\u0022' . preg_quote($keyword->link, '`') . '*?\\\u0022[^><"\']*?(?:class=\\\u0022[^"\']*?wpil_keyword_link[^"\']*?\\\u0022|data-wpil-keyword-link=\\\u0022linked\\\u0022)[^><"\']*?\\\u003e)(?!\\\u003ca)(' . $search_keyword . ')\\\u003c[\\\\]+?\/a\\\u003e`i', $content, $matches2);
            // if there are encoded autolinks, but no normal links
            if(!empty($matches2) && empty($matches)){
                // set the match list for the encoded links
                $matches = $matches2;
            }elseif(!empty($matches) && !empty($matches2)){
                // if there are normal and encoded links, merge them all together
                foreach($matches as $ind => $dat){
                    $matches[$ind] = array_merge($matches[$ind], $matches2[$ind]);
                }
            }

            // check again with extra slashed links to make sure we don't miss those
            preg_match_all('`(?:\\\u003ca\s[^><"\']*?(?:class=\\\u0022[^"\']*?wpil_keyword_link[^"\']*?\\\u0022|data-wpil-keyword-link=\\\u0022linked\\\u0022)[^><"\']*?(href|url)=\\\u0022' . preg_quote(preg_replace('`(?<!\\\\)\/`i', '\\/', $keyword->link), '`') . '*?\\\u0022[^><"\']*?\\\u003e|\\\u003ca\s[^><"\']*?(href|url)=\\\u0022' . preg_quote(preg_replace('`(?<!\\\\)\/`i', '\\/', $keyword->link), '`') . '*?\\\u0022[^><"\']*?(?:class=\\\u0022[^"\']*?wpil_keyword_link[^"\']*?\\\u0022|data-wpil-keyword-link=\\\u0022linked\\\u0022)[^><"\']*?\\\u003e)(?!\\\u003ca)(' . $search_keyword . ')\\\u003c[\\\\]+?\/a\\\u003e`i', $content, $matches2);

            // if there are encoded autolinks, but no normal links
            if(!empty($matches2) && empty($matches)){
                // set the match list for the encoded links
                $matches = $matches2;
            }elseif(!empty($matches) && !empty($matches2)){
                // if there are normal and encoded links, merge them all together
                foreach($matches as $ind => $dat){
                    $matches[$ind] = array_merge($matches[$ind], $matches2[$ind]);
                }
            }
        }

        // if there are no matches and the link has unicode characters
        if((empty($matches) || empty($matches[0])) && !empty(preg_match('`\\\\u[0-9a-fA-F]{1,4}`', $keyword->link))){
            // try decoding the unicode
            $decoding = json_decode('"'. $keyword->link. '"');
            // if it worked, but the link still isn't found
            if(!empty($decoding) && false === stripos($content, $decoding)){
                // try translating the results to ASCII to catch special chars
                $decoding = iconv('UTF-8', 'ASCII//TRANSLIT', $decoding);
            }

            // if the text couldn't be decoded or we're still not finding the link
            if(empty($decoding) || false === stripos($content, $decoding)){
                // try a literal search and replace to remove the unicode
                $characters = array(
                    "\u0021",  // Exclamation mark
                    "\u0023",  // Hash
                    "\u0024",  // Dollar sign
                    "\u0026",  // Ampersand
                    "\u0027",  // Single quote
                    "\u0028",  // Left parenthesis
                    "\u0029",  // Right parenthesis
                    "\u002A",  // Asterisk
                    "\u002B",  // Plus
                    "\u002C",  // Comma
                    "\u002F",  // Forward slash
                    "\u003A",  // Colon
                    "\u003B",  // Semicolon
                    "\u003D",  // Equals
                    "\u003F",  // Question mark
                    "\u0040",  // At symbol
                    "\u005B",  // Left square bracket
                    "\u005D",  // Right square bracket
                    "\u005F",  // Underscore
                    "\u007E",  // Tilde
                    "\u0025",  // Percent
                    "\u002D",  // Hyphen-minus
                    "\u002E",  // Period
                    "\u2012",  // Hyphen
                    "\u2013",  // En dash
                    "\u2014"   // Em dash
                );

                $replacements = array(
                    "!",  // Exclamation mark
                    "#",  // Hash
                    "$",  // Dollar sign
                    "&",  // Ampersand
                    "'",  // Single quote
                    "(",  // Left parenthesis
                    ")",  // Right parenthesis
                    "*",  // Asterisk
                    "+",  // Plus
                    ",",  // Comma
                    "/",  // Forward slash
                    ":",  // Colon
                    ";",  // Semicolon
                    "=",  // Equals
                    "?",  // Question mark
                    "@",  // At symbol
                    "[",  // Left square bracket
                    "]",  // Right square bracket
                    "_",  // Underscore
                    "~",  // Tilde
                    "%",  // Percent
                    "-",  // Hyphen-minus
                    ".",  // Period
                    "-",  // Hyphen/Normal Dash
                    "–",  // En Dash
                    "—",  // Em Dash
                );

                $decoding = str_replace($characters, $replacements, $keyword->link);

            }

            // try pulling the links again with the new link
            preg_match_all('`(?:<a\s[^><]*?(?:class=["\'][^"\']*?wpil_keyword_link[^"\']*?["\']|data-wpil-keyword-link="linked")[^><]*?(href|url)=[\'\"]' . preg_quote($decoding, '`') . '*?[\'\"][^><]*?>|<a\s[^><]*?(href|url)=[\'\"]' . preg_quote($decoding, '`') . '*?[\'\"][^><]*?(?:class=["\'][^"\']*?wpil_keyword_link[^"\']*?["\']|data-wpil-keyword-link="linked")[^><]*?>)(?!<a)(' . $search_keyword . ')<\/a>`i', $content, $matches);
            // also check if there is a unicode encoded link
            if($find_encoded && false !== stripos($content, '\u003ca')){
                // check to see if autolinks have been saved in that content
                preg_match_all('`(?:\\\u003ca\s[^><"\']*?(?:class=\\\u0022[^"\']*?wpil_keyword_link[^"\']*?\\\u0022|data-wpil-keyword-link=\\\u0022linked\\\u0022)[^><"\']*?(href|url)=\\\u0022' . preg_quote($keyword->link, '`') . '*?\\\u0022[^><"\']*?\\\u003e|\\\u003ca\s[^><"\']*?(href|url)=\\\u0022' . preg_quote($keyword->link, '`') . '*?\\\u0022[^><"\']*?(?:class=\\\u0022[^"\']*?wpil_keyword_link[^"\']*?\\\u0022|data-wpil-keyword-link=\\\u0022linked\\\u0022)[^><"\']*?\\\u003e)(?!\\\u003ca)(' . $decoding . ')\\\u003c[\\\\]+?\/a\\\u003e`i', $content, $matches2);
                // if there are encoded autolinks, but no normal links
                if(!empty($matches2) && empty($matches)){
                    // set the match list for the encoded links
                    $matches = $matches2;
                }elseif(!empty($matches) && !empty($matches2)){
                    // if there are normal and encoded links, merge them all together
                    foreach($matches as $ind => $dat){
                        $matches[$ind] = array_merge($matches[$ind], $matches2[$ind]);
                    }
                }

                // and do our double check again with the extra slashed links to make sure we don't miss those
                preg_match_all('`(?:\\\u003ca\s[^><"\']*?(?:class=\\\u0022[^"\']*?wpil_keyword_link[^"\']*?\\\u0022|data-wpil-keyword-link=\\\u0022linked\\\u0022)[^><"\']*?(href|url)=\\\u0022' . preg_quote(preg_replace('`(?<!\\\\)\/`i', '\\/', $keyword->link), '`') . '*?\\\u0022[^><"\']*?\\\u003e|\\\u003ca\s[^><"\']*?(href|url)=\\\u0022' . preg_quote(preg_replace('`(?<!\\\\)\/`i', '\\/', $keyword->link), '`') . '*?\\\u0022[^><"\']*?(?:class=\\\u0022[^"\']*?wpil_keyword_link[^"\']*?\\\u0022|data-wpil-keyword-link=\\\u0022linked\\\u0022)[^><"\']*?\\\u003e)(?!\\\u003ca)(' . $decoding . ')\\\u003c[\\\\]+?\/a\\\u003e`i', $content, $matches2);

                // if there are encoded autolinks, but no normal links
                if(!empty($matches2) && empty($matches)){
                    // set the match list for the encoded links
                    $matches = $matches2;
                }elseif(!empty($matches) && !empty($matches2)){
                    // if there are normal and encoded links, merge them all together
                    foreach($matches as $ind => $dat){
                        $matches[$ind] = array_merge($matches[$ind], $matches2[$ind]);
                    }
                }
            }
        }

        if($return_text){
            $return_matches = array();
            foreach($matches[0] as $key => $match){
                if(!$return_text){
                    $return_matches[] = $match;
                }else{
                    $return_matches[] = array('link' => $match, 'anchor' => $matches[3][$key]);
                }
            }

            return $return_matches;
        }else{
            return $matches;
        }
    }

    /**
     * Update post content in all editors
     */
    public static function updateContent($content, $keyword, $post, $left_one = false, $excerpt = '')
    {
        if ($post->type == 'post') {
            Wpil_Post::editors('removeKeywordLinks', [$keyword, $post->id, $left_one]);
            Wpil_Editor_Kadence::removeKeywordLinks($content, $keyword, $left_one);
            Wpil_Editor_YooTheme::removeKeywordLinks($content, $keyword, $left_one);
            // todo, update this so it removes the autolinks and re-inserts only the ones that are supposed to be in the content. As it is now, all the links are removed from the editor content when someone changes the date range for autolinks and the categories that are allowed.
        }

        // update the meta field content


        return $post->updateContent($content, $excerpt);
    }

    /**
     * Does a check to see if the user has set any autolinks for manual select
     **/
    public static function keywordLinkSelectActive(){
        global $wpdb;
        $keyword_table = $wpdb->prefix . 'wpil_keywords';

        $set = false;
        $table = $wpdb->get_var("SHOW TABLES LIKE '{$keyword_table}'");
        if($table === $keyword_table){
            $set = $wpdb->get_results("SELECT `id` FROM {$keyword_table} WHERE `select_links` = 1 LIMIT 1");
        }

        return (!empty($set)) ? true: false;
    }

    /**
     * Checks to see if the there are active autolinking rules
     **/
    public static function hasAutolinkRules(){
        global $wpdb;
        $keyword_table = $wpdb->prefix . 'wpil_keywords';

        return ($keyword_table === $wpdb->get_var("SHOW TABLES LIKE '{$keyword_table}'") && !empty($wpdb->get_results("SELECT `id` FROM {$keyword_table} LIMIT 1")));

    }

    public static function getLinkedPostsFromAlternateLocations($posts){
        global $wpdb;

        $found_posts = false;
        $active_post_types = Wpil_Settings::get_autolinking_post_types();

        // if WP Recipes is active and the user wants to add links to the recipe notes
        if(defined('WPRM_POST_TYPE') && in_array('wprm_recipe', $active_post_types)){
            $keys = Wpil_Editor_WPRecipe::get_selected_fields();

            if(!empty($keys)){
                $keys = "'" . implode("','", array_keys($keys)) . "'";
                $results = $wpdb->get_results("SELECT DISTINCT m.post_id AS id, 'post' as type, 1 AS alt FROM {$wpdb->postmeta} m WHERE `meta_key` IN ({$keys}) AND (meta_value LIKE '%wpil_keyword_link%')");

                if(!empty($results)){
                    $found_posts = true;
                    $posts = array_merge($posts, $results);
                }
            }
        }

        // if Goodlayers is active
        if(defined('GDLR_CORE_LOCAL')){
            $post_types_p = Wpil_Query::postTypes('p');
            $statuses_query_p = Wpil_Query::postStatuses('p');
            $results = $wpdb->get_results("SELECT DISTINCT m.post_id AS id, 'post' as type, 1 AS alt FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE m.meta_key = 'gdlr-core-page-builder' {$post_types_p} {$statuses_query_p} AND (meta_value LIKE '%wpil_keyword_link%')");

            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }

        // if Elementor is active
        if(defined('ELEMENTOR_VERSION')){ // todo think about moving to the editor file
            $post_types_p = Wpil_Query::postTypes('p');
            $statuses_query_p = Wpil_Query::postStatuses('p');
            $results = $wpdb->get_results("SELECT DISTINCT m.post_id AS id, 'post' as type, 1 AS alt FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE m.meta_key = '_elementor_data' {$post_types_p} {$statuses_query_p} AND (meta_value LIKE '%wpil_keyword_link%')");

            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }

        // if WooCommerce is active
        if(defined('WC_PLUGIN_FILE') && in_array('product', $active_post_types)){
            $results = $wpdb->get_results("SELECT DISTINCT ID AS id, 'post' as type, 1 AS alt FROM {$wpdb->posts} p WHERE p.post_type = 'product' AND p.post_excerpt LIKE '%wpil_keyword_link%'");

            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }

        // if ACF is active
        if(class_exists('ACF') && !get_option('wpil_disable_acf', false)){
            $post_types_p = Wpil_Query::postTypes('p');
            $statuses_query_p = Wpil_Query::postStatuses('p');
            $acf_fields = Wpil_Query::querySpecifiedAcfFields('mp');
            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT DISTINCT p.ID AS id, 'post' as type, 1 AS alt 
                                    FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON p.ID = m.post_id AND m.meta_value LIKE 'field_%' LEFT JOIN {$wpdb->postmeta} mp ON p.ID = mp.post_id AND SUBSTR(m.meta_key, 2) = mp.meta_key 
                                    WHERE m.meta_id > 0 {$acf_fields} {$post_types_p} {$statuses_query_p} AND mp.meta_value LIKE '%wpil_keyword_link%'")
            );

            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }

        if($found_posts){
            // if there are posts found, remove any duplicate ids
            $post_ids = array();
            foreach($posts as $post){
                $post_ids[$post->id] = $post;
            }

            $posts = array_values($post_ids);
        }


        return $posts;
    }

    public static function getPostsFromAlternateLocations($posts, $keyword, $same_lang_ids = array()){
        global $wpdb;

        $case_sensitive = (isset($keyword->case_sensitive) && !empty($keyword->case_sensitive)) ? "BINARY": "";
        $found_posts = false;
        $fields = Wpil_Post::getMetaContentFieldList('post');
        $active_post_types = Wpil_Settings::get_autolinking_post_types();
        $link_post = (Wpil_Link::isInternal($keyword->link) || Wpil_Link::isAffiliateLink($keyword->link)) ? Wpil_Post::getPostByLink($keyword->link): null;

        // if there are custom metafields to examine
        if(!empty($fields)){
            $ignore_target = "";
            if(!empty($link_post) && $link_post->type === 'post'){
                $ignore_target = " AND m.post_id != " . $link_post->id;
            }

            $post_types_p = Wpil_Query::postTypes('p');
            $statuses_query_p = Wpil_Query::postStatuses('p');
            $user_role_ignore_p = Wpil_Query::get_ignore_user_role_suggestions_query('p');
            $locations = '\'' . implode('\', \'', $fields) . '\'';
            $results = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT m.post_id AS ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE m.meta_key IN ({$locations}) {$post_types_p} {$statuses_query_p} {$user_role_ignore_p} {$ignore_target} AND (meta_value LIKE {$case_sensitive} %s)", Wpil_Toolbox::esc_like($keyword->keyword)));

            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }

        if(class_exists('ACF') && !get_option('wpil_disable_acf', false)){
            $ignore_target = "";
            if(!empty($link_post) && $link_post->type === 'post'){
                $ignore_target = " AND m.post_id != " . $link_post->id;
            }

            $post_types_p = Wpil_Query::postTypes('p');
            $statuses_query_p = Wpil_Query::postStatuses('p');
            $user_role_ignore_p = Wpil_Query::get_ignore_user_role_suggestions_query('p');
            $acf_fields = Wpil_Query::querySpecifiedAcfFields('mp');
            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT DISTINCT p.ID 
                                    FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON p.ID = m.post_id AND m.meta_value LIKE 'field_%' LEFT JOIN {$wpdb->postmeta} mp ON p.ID = mp.post_id AND SUBSTR(m.meta_key, 2) = mp.meta_key 
                                    WHERE m.meta_id > 0 {$acf_fields} {$ignore_target} {$post_types_p} {$statuses_query_p} {$user_role_ignore_p} AND mp.meta_value LIKE {$case_sensitive} %s", Wpil_Toolbox::esc_like($keyword->keyword))
            );

            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }

        // if WP Recipes is active and the user wants to add links to the recipe notes
        if(defined('WPRM_POST_TYPE') && in_array('wprm_recipe', $active_post_types)){
            $ignore_target = "";
            if(!empty($link_post) && $link_post->type === 'post'){ // We are searching for posts to link in, but the keyword's target might not be a post and we don't want to ignore a valid match
                $ignore_target = " AND m.post_id != " . $link_post->id;
            }

            $keys = Wpil_Editor_WPRecipe::get_selected_fields();

            if(!empty($keys)){
                $keys = "'" . implode("','", array_keys($keys)) . "'";
                $results = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT m.post_id AS ID FROM {$wpdb->postmeta} m WHERE `meta_key` IN ({$keys}) {$ignore_target} AND (meta_value LIKE {$case_sensitive} %s)", Wpil_Toolbox::esc_like($keyword->keyword)));

                if(!empty($results)){
                    $found_posts = true;
                    $posts = array_merge($posts, $results);
                }
            }
        }

        // get inbound links from active builders
        $builder_meta = Wpil_Post::get_builder_meta_keys();

        // if we have builders to search for
        if(!empty($builder_meta)){
            $builder_meta = "('" . implode("','", $builder_meta) . "')";

            $ignore_target = "";
            if(!empty($link_post) && $link_post->type === 'post'){
                $ignore_target = " AND m.post_id != " . $link_post->id;
            }

            $when = '';
            if(!empty($keyword->restrict_date) && !empty($keyword->restricted_date)){
                $when = " AND p.post_date_gmt > '{$keyword->restricted_date}'";
            }

            $post_types_p = Wpil_Query::postTypes('p');
            $statuses_query_p = Wpil_Query::postStatuses('p');
            $user_role_ignore_p = Wpil_Query::get_ignore_user_role_suggestions_query('p');
            $results = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT m.post_id AS ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE m.meta_key IN {$builder_meta} {$ignore_target} {$post_types_p} {$statuses_query_p} {$user_role_ignore_p} {$when} AND (meta_value LIKE {$case_sensitive} %s)", Wpil_Toolbox::esc_like($keyword->keyword)));
            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }

        // if WooCommerce is active
        if(defined('WC_PLUGIN_FILE') && in_array('product', $active_post_types)){
            $ignore_target = "";
            if(!empty($link_post) && $link_post->type === 'post'){
                $ignore_target = " AND p.ID != " . $link_post->id;
            }

            $statuses_query_p = Wpil_Query::postStatuses('p');
            $user_role_ignore_p = Wpil_Query::get_ignore_user_role_suggestions_query('p');
            $results = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT ID FROM {$wpdb->posts} p WHERE p.post_type = 'product' {$ignore_target} {$statuses_query_p} {$user_role_ignore_p} AND p.post_excerpt LIKE {$case_sensitive} %s", Wpil_Toolbox::esc_like($keyword->keyword)));

            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }

        // if Divi is active
        if(defined('ET_SHORTCODES_VERSION')){
            $metas = $wpdb->get_results("SELECT pm_use_on.meta_value FROM {$wpdb->postmeta} pm_use_on
                WHERE pm_use_on.meta_key = '_et_use_on'
                AND pm_use_on.meta_value > '0'
                AND EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta}
                    WHERE post_id = pm_use_on.post_id
                    AND meta_key = '_et_enabled'
                    AND meta_value = '1'
                )
                AND EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta}
                    WHERE post_id = pm_use_on.post_id
                    AND meta_key = '_et_body_layout_id'
                    AND meta_value > '0'
                )");

            if(!empty($metas)){
                $ids = array();
                foreach($metas as $meta){
                    if(empty($meta)){
                        continue;
                    }
                    preg_match('/^singular:post_type:([^:]+):id:(\d+)$/i', $meta->meta_value, $m);

                    if(!empty($m) && isset($m[2]) && !empty($m[2])){
                        $ids[] = (int)$m[2];
                    }
                }

                $ids = array_filter($ids);

                if(!empty($ids)){
                    $ids = array_unique($ids);
                    foreach($ids as $id){
                        $posts[] = (object) array('ID' => $id);
                    }
                }
            }
        }

        if($found_posts){
            // if there are posts found, remove any duplicate ids
            $post_ids = array();
            foreach($posts as $post){
                // if we have same language ids and the current post isn't in the list
                if(!empty($same_lang_ids) && is_array($same_lang_ids) && !in_array($post->ID, $same_lang_ids)){
                    // skip this post
                    continue;
                }

                $post_ids[$post->ID] = $post;
            }

            $posts = array_values($post_ids);
        }

        /**
         * Allows the user to filter the post ids that are examined for autolinking
         * @param array $posts The list of post ids that are going to be examind
         **/
        $posts = apply_filters('wpil_filter_autolink_ids_alternate_locations', $posts, 'post', $keyword);

        return $posts;
    }

    public static function getTermsFromAlternateLocations($posts, $keyword, $same_lang_term_ids = array()){
        global $wpdb;

        $found_terms = false;
        $fields = Wpil_Post::getMetaContentFieldList('term');
        $case_sensitive = (isset($keyword->case_sensitive) && !empty($keyword->case_sensitive)) ? "BINARY": "";
        $link_post = (Wpil_Link::isInternal($keyword->link) || Wpil_Link::isAffiliateLink($keyword->link)) ? Wpil_Post::getPostByLink($keyword->link): null;

        // if there are metafields to examine
        if(!empty($fields)){
            $ignore_target = "";
            if(!empty($link_post) && $link_post->type === 'term'){
                $ignore_target = " AND m.term_id != " . $link_post->id;
            }

            $locations = '\'' . implode('\', \'', $fields) . '\'';
            $results = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT m.term_id AS 'term_id' FROM {$wpdb->termmeta} m WHERE `meta_key` IN ({$locations}) {$ignore_target} AND (meta_value LIKE {$case_sensitive} %s)", Wpil_Toolbox::esc_like($keyword->keyword)));

            if(!empty($results)){
                $found_terms = true;
                $posts = array_merge($posts, $results);
            }
        }

        if(class_exists('ACF') && !get_option('wpil_disable_acf', false)){
            $ignore_target = "";
            if(!empty($link_post) && $link_post->type === 'term'){
                $ignore_target = " AND term_id != " . $link_post->id;
            }

            $acf_fields = Wpil_Query::querySpecifiedAcfFields();
            $results = $wpdb->get_results($wpdb->prepare("SELECT `term_id` FROM {$wpdb->termmeta} WHERE meta_key IN (SELECT DISTINCT SUBSTR(meta_key, 2) as `name` FROM {$wpdb->termmeta} WHERE meta_value LIKE 'field_%' AND SUBSTR(meta_key, 2) != '' {$acf_fields}) {$ignore_target} AND meta_value LIKE {$case_sensitive} %s", Wpil_Toolbox::esc_like($keyword->keyword)));
            if(!empty($results)){
                $found_terms = true;
                $posts = array_merge($posts, $results);
            }
        }

        if($found_terms){
            // if there are posts found, remove any duplicate ids
            $post_ids = array();
            foreach($posts as $post){
                // if we have same language ids and the current term isn't in the list
                if(!empty($same_lang_term_ids) && is_array($same_lang_term_ids) && !in_array($post->term_id, $same_lang_term_ids)){
                    // skip this post
                    continue;
                }

                $post_ids[$post->term_id] = $post;
            }

            $posts = array_values($post_ids);
        }

        /**
         * Allows the user to filter the term ids that are examined for autolinking
         * @param array $posts The list of term ids that are going to be examind
         **/
        $posts = apply_filters('wpil_filter_autolink_ids_alternate_locations', $posts, 'term', $keyword);

        return $posts;
    }

    /**
     * Adds a post to the ghost link cleanup cache if it's not already there.
     * Not really a cache, more like a deferred processing list, but "cache" gives you the idea of storing for future use
     * @param object $post
     **/
    public static function ghost_link_cache_add($post = false){
        if(empty($post) || !is_a($post, 'Wpil_Model_Post') || empty($post->id)){
            return;
        }

        $cache_id = $post->type . '_' . $post->id;

        if(!isset(self::$ghost_link_cleanup[$cache_id])){
            self::$ghost_link_cleanup[$cache_id] = $post;
        }
    }

    /**
     * Processes all of the posts that are patiently waiting in the ghost link cache.
     * Pulls fresh content from the database to check for links, so we shouldn't do anything to let the cache get too big
     **/
    public static function ghost_link_cache_process(){
        if(!empty(self::$ghost_link_cleanup)){
            foreach(self::$ghost_link_cleanup as $id => $post){
                self::deleteGhostLinks($post);
                unset(self::$ghost_link_cleanup[$id]);
            }
        }
    }

    /**
     * Sorts supplied autolink ids by insertion priority.
     * Takes a list of ids & askes the database what their insertion priority is sorted from highest to lowest
     **/
    public static function sort_keyword_ids_by_insertion_priority($ids = array()){
        global $wpdb;
        $keyword_table = $wpdb->prefix . 'wpil_keywords';

        if(empty($ids) || !is_array($ids)){
            return $ids;
        }

        $ids = array_filter(array_map(function($num){ return (int)$num; }, $ids));

        if(empty($ids)){
            return array();
        }

        $ids = implode(',', $ids);

        $results = $wpdb->get_col("SELECT `id` FROM {$keyword_table} WHERE `id` IN ({$ids}) ORDER BY `set_priority` DESC, `prioritize_longtail` DESC, `priority_setting` DESC, `id` DESC");
    
        return (!empty($results)) ? $results: array();
    }

    /**
     * Gets the dropdown data for the dropdowns on the Autolinking page.
     * Works for both the Links and the Possible Links dropdowns.
     **/
    public static function ajax_get_keyword_dropdown_data(){
        global $wpdb;

        Wpil_Base::verify_nonce('wpil-collapsible-nonce');

        if(!isset($_POST['dropdown_type']) || !isset($_POST['keyword_id']) || !isset($_POST['keyword']) || !isset($_POST['item_count'])){
            wp_send_json(array('error' => array('title' => __('Data Missing', 'wpil'), 'text' => __('Some of the data required to load the rest of the dropdown is missing. Please reload the page and try opening the dropdown again.', 'wpil'))));
        }

        // Search doesn't currently apply to autolink dropdowns, so we don't have to check that!

        // get the current offset and query limit
        $offset = (isset($_POST['item_count'])) ? (int) $_POST['item_count']: 0;
        $limit = "LIMIT 200 OFFSET {$offset} ";

        $table = ($_POST['dropdown_type'] === 'possible-links') ? "{$wpdb->prefix}wpil_keyword_select_links": "{$wpdb->prefix}wpil_keyword_links";
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE keyword_id = %d {$limit}", $_POST['keyword_id']));


        // now that we have the data, it's time to format it!
        $response = '';
        $county = 0; 
        if(!empty($results)){
            $county = count($results);
            if($_POST['dropdown_type'] === 'possible-links'){
                foreach($results as $dat){
                    $post = new Wpil_Model_Post($dat->post_id, $dat->post_type);
                    $display_sentence = preg_replace('/(?<![a-zA-Z])' . preg_quote($dat->case_keyword, '/') . '(?![a-zA-Z])/', '{{link-whisper-bold-open}}' . $dat->case_keyword . '{{link-whisper-bold-close}}', $dat->sentence_text, 1);
                    $response .= 
                    '<li id="select-keyword-' . $dat->id . '">
                        <div style="display: inline-block;"><b>' . __('Post', 'wpil') . '</b>: <a href="' . esc_url($post->getViewLink()) . '" target="_blank">' . esc_html($post->getTitle()) . '</a></div>
                        <br />
                        <span><b>' . __('Sentence', 'wpil') . '</b>: </span>
                        <br />
                        <label><div style="display: inline-block;"><input id="select-keyword-' . $dat->id . '" type="checkbox" name="wpil_keyword_select_link" data-select-keyword-id="' . $dat->id . '"></div><span>' . str_replace(array('{{link-whisper-bold-open}}', '{{link-whisper-bold-close}}'), array('<b>', '</b>'), esc_html($display_sentence)) . '</span></label>
                        <br />
                    </li>';
                }
            }else{
                foreach($results as $link){
                    $post = new Wpil_Model_Post($link->post_id, $link->post_type);
                    $anchor = (!empty($link->anchor)) ? '<strong>[' . esc_html(stripslashes($link->anchor)) . ']</strong>' : '';
                    $response .= 
                    '<li>' .
                        esc_html($post->getTitle()) . ' ' . $anchor . '
                        <br>
                        <a href="' . esc_url($post->getLinks()->edit) . '" target="_blank">[edit]</a>
                        <a href="' . esc_url($post->getLinks()->view) . '" target="_blank">[view]</a><br><br>
                    </li>';
                }
            }
        }

        wp_send_json(array('success' => array('item_data' => $response, 'item_count' => $county)));
    }

    /**
     * Runs the import to turn target keywords into autolihnking rules by AJAX!
     **/
    public static function ajax_import_target_keywords_as_rules(){
        Wpil_Base::verify_nonce('wpil_autolink_keyword_import');

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // Remove any hooks that may interfere with AJAX requests
        Wpil_Base::remove_problem_hooks();

        if(isset($_POST['reset']) && !empty($_POST['reset'])){
            delete_transient('wpil_target_keyword_autolink_import_step');
        }

        // exit if the customer doens't want to do settings
        if(empty(get_option('wpil_wizard_import_autolink_rules', 0))){
            wp_send_json(array('progress' => 100, 'finish' => true));
        }

        // process the keywords
        $finished = self::import_target_keywords_as_rules();

        // get the total number of keywords remaining and shoot that back
        $keyword_total = Wpil_TargetKeyword::get_active_keyword_count(['post-content-keyword', 'ai-generated-keyword']);

        $progress = 0;
        if(!empty($keyword_total)){
            // get the batch progress
            $batch_size = 1000;
            $step = get_transient('wpil_target_keyword_autolink_import_step');
            $step = (!empty($step)) ? $step: 0;
            $processed = $batch_size * $step;

            // and figure out where whe are in the processing to determine our progress
            if($processed > $keyword_total){
                $progress = 100;
            }else{
                $progress = (round(($batch_size * $step)/$keyword_total, 2)) * 100;
            }
        }

        wp_send_json(array('progress' => $progress, 'finish' => $finished));
    }

    /**
     * Pulls the the Target Keywords from their database and uses them to create autoliinking rules.
     * Checks to make sure that it doesn't create duplicate rules
     **/
    public static function import_target_keywords_as_rules(){
        global $wpdb;
        $table = $wpdb->prefix . 'wpil_keywords';
        $finished = false;

        // first step, make sure that the autolinking cron is turned on
        update_option('wpil_enable_autolink_cron_task', 1);

        // next, assemble the settings for the rules
        $settings = array(
            'keyword' => '',
            'link' => '',
            'add_same_link' => get_option('wpil_keywords_add_same_link', 0),
            'link_once' => get_option('wpil_keywords_link_once', 1),
            'restrict_to_live' => 1,
            'limit_inserts' => 1,
            'insert_limit' => 3,
            'select_links' => get_option('wpil_keywords_select_links', 0),
            'set_priority' => get_option('wpil_keywords_set_priority', 0),
            'priority_setting' => 0,
            'prioritize_longtail' => 1,
            'restrict_date' => 0,
            'restricted_date' => null,
            'restrict_cats' => get_option('wpil_keywords_restrict_to_cats', 0),
            'restricted_cats' => '',
            'case_sensitive' => get_option('wpil_keywords_case_sensitive', 0),
            'force_insert' => 0,
            'same_lang' => 1,
            'auto_managed' => 1
        );

        // setup the ignore list and get our processing data
        $post_ignore = Wpil_Settings::getAllIgnoredPosts();
        $post_cache = array();
        $batch_size = 1000;
        $step = get_transient('wpil_target_keyword_autolink_import_step');
        $step = (!empty($step)) ? $step: 0;
        $total_process_count = 0;
        while(!Wpil_Toolbox::is_over_memory_limit() && !Wpil_Base::overTimeLimit(5, 25)){
            // pull the available keywords except for the AI generated and the post content onse
            $keywords = Wpil_TargetKeyword::get_batch_active_keywords($step, $batch_size, ['post-content-keyword', 'ai-generated-keyword']);

            // if there are no keywords or there are already a ton of keywords
            if(empty($keywords) || (($batch_size * $step) > 100000)){
                $finished = true;
                // we're finished!
                break;
            }

            $autolinks = array();
            // go over the keywords
            foreach($keywords as $keyword){
                $pid = $keyword->post_type . '_' . $keyword->post_id;
                $total_process_count++; // count this as a process because one way or another, this keyword is being chekced off the list
                
                // if we're ignoring posts and this is one to ignore or the keyowrd happens to be empty
                if(!empty($post_ignore) && in_array($pid, $post_ignore) || empty($keyword->keywords)){
                    continue;
                }

                // if we haven't come across the post yet
                if(!isset($post_cache[$pid])){
                    // create the post object
                    $post = new Wpil_Model_Post($keyword->post_id, $keyword->post_type);
                    // see if we can create the view URL
                    if(!empty($post->getViewLink())){
                        // if we can, cache it
                        $post_cache[$pid] = $post;
                    }else{
                        // if we can't, say it was deleted and skip to the next one
                        $post_cache[$pid] = 'deleted';
                        continue;
                    }
                }elseif($post_cache[$pid] === 'deleted'){
                    // if the post has been deleted, skip to the next one
                    continue;
                }

                $keyword = trim($keyword->keywords);
                if(empty($keyword) || self::checkIfAutolinkRuleExists($keyword, $post->getViewLink())){
                    continue;
                }

                $rule = array('keyword' => $keyword, 'link' => $post->getViewLink());

                // if we're prioritizing longtail keywords & no priority has been manually set
                if(!empty($settings['prioritize_longtail']) && (empty($settings['priority']) || empty($settings['set_priority'])) ){
                    $rule['priority'] = Wpil_Word::getWordCount($keyword);
                }

                // if we've made it this far, add the keyword + url to the list
                $autolinks[] = $rule;
            }

            // create autolinking rules if we have any available
            if(!empty($autolinks)){
                $count = 0;
                $insert_query = "INSERT INTO {$table} (keyword, link, add_same_link, link_once, restrict_to_live, limit_inserts, insert_limit, select_links, set_priority, priority_setting, prioritize_longtail, restrict_date, restricted_date, restrict_cats, restricted_cats, case_sensitive, force_insert, same_lang, auto_imported, auto_managed) VALUES ";
                $placeholders = "('%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d')";
                $autolink_data = array();
                $place_holders = array();
                $total = count($autolinks);
                $limit = 100;
                foreach($autolinks as $key => $linking_rule){
                    $data = array_merge($settings, $linking_rule);

                    array_push(
                        $autolink_data, 
                        $data['keyword'],
                        $data['link'],
                        $data['add_same_link'],
                        $data['link_once'],
                        $data['restrict_to_live'],
                        $data['limit_inserts'],
                        $data['insert_limit'],
                        $data['select_links'],
                        $data['set_priority'],
                        $data['priority_setting'],
                        $data['prioritize_longtail'],
                        $data['restrict_date'],
                        $data['restricted_date'],
                        $data['restrict_cats'],
                        $data['restricted_cats'],
                        $data['case_sensitive'],
                        $data['force_insert'],
                        $data['same_lang'],
                        1,
                        1
                    );
                    $place_holders[] = $placeholders;

    
                    // if we've hit the limit
                    if($count > $limit || ($key + 1) >= $total){
                        // assemble the insert
                        $insert = ($insert_query . implode(', ', $place_holders));
                        $insert = $wpdb->prepare($insert, $autolink_data);
                        // insert the data
                        $wpdb->query($insert);
                        // reset the data variables
                        $autolink_data = [];
                        $place_holders = [];
                        $count = 0;
                    }
        
                    $count++;
                }

                // if we still have data that hasn't been inserted
                if(!empty($autolink_data) && !empty($place_holders)){
                    // assemble the insert
                    $insert = ($insert_query . implode(', ', $place_holders));
                    $insert = $wpdb->prepare($insert, $autolink_data);
                    // and insert the data
                    $wpdb->query($insert);
                }
            }

            // increase step
            $step++;
            // and save the progress
            set_transient('wpil_target_keyword_autolink_import_step', $step, MINUTE_IN_SECONDS * 10);
        }

        $total_keywords = Wpil_TargetKeyword::get_active_keyword_count(['post-content-keyword', 'ai-generated-keyword']);
        $processed = ($step * 1000);
        // track the progress
        Wpil_Toolbox::track_process_progress(
            'autolink_keyword_importing', 
            'Creating Autolinking Rules', 
            $total_process_count, 
            ($total_keywords - $processed < 0) ? 0: ($total_keywords - $processed), 
            $total_keywords
        );

        return $finished;
    }

    /**
     * Runs the cron-based autolinking system... Via Ajax 😵‍💫
     **/
    public static function ajax_run_autolink_import_create_scan(){
        Wpil_Base::verify_nonce('wpil_wizard_autolink_created_nonce');


        // if this is a reset of the process
        if(isset($_POST['reset']) && !empty($_POST['reset'])){
            // reset the cron task's queue so that we can start at the beginning
            update_option('wpil_autolink_cron_offset', 0);
            update_option('wpil_autolink_cron_type', 'post');
            update_option('wpil_auto_import_autolink_insert_count', 0); // simple counter so we can keep track of how many lynks are inserted when going an import
            update_option('wpil_autolink_cron_completed_posts', 0);
            delete_transient('wpil_autolinking_cron_post_batch');
            delete_transient('wpil_autolinking_cron_term_batch');
            set_transient('wpil_wizard_inserting_autolinks', time(), HOUR_IN_SECONDS * 6); // set a flag so the frontend can tell we're on this phase
        }

        // set a flag so that we know that the autolinking process is running
        set_transient('wpil_doing_ajax_autolinks', time(), MINUTE_IN_SECONDS * 10);

        // run the cron task from here!
        self::do_scheduled_autolink_insert();

        $insert_count = get_option('wpil_auto_import_autolink_insert_count', 0);

        $link_data = array('autolink_inserts' => $insert_count);

        // get the number of outstanding posts
        $batch_posts = get_transient('wpil_autolinking_cron_post_batch');
        $batch_terms = get_transient('wpil_autolinking_cron_term_batch');
        $remaining = (!empty($batch_posts) && is_array($batch_posts)) ? count($batch_posts) : 0;
        $remaining = (!empty($batch_terms) && is_array($batch_terms)) ? count($batch_terms) + $remaining : $remaining;

        // and the total to process
        $total_count = get_option('wpil_cron_autolink_total_batch_size', 0);
        
        // now calculate the progress
        if(empty($total_count)){
            $progress = 100; // default to complete if there's no total number of items
        }else{
            $progress = round(($total_count - $remaining)/$total_count, 2) * 100;
        }

        wp_send_json(
            array(
                'data' => array(
                    'finished' => ((bool) get_option('wpil_autolink_cron_completed_posts', 0) || $progress > 99),
                    'progress' => $progress,
                    'link_data' => $link_data
                )
            )
        );
    }

    /**
     * TODO: Remove it and the attendant tracking if we can't find a use for it after 3.0.0
     **/
    public static function update_auto_imported_link_count($count = 0){
        if(empty($count) || !isset($_POST['action']) || $_POST['action'] !== 'wpil_run_autolink_import_create_scan'){
            return;
        }
        $inserted_so_far = get_option('wpil_auto_import_autolink_insert_count', 0);
        $inserted_so_far += $count;
        update_option('wpil_auto_import_autolink_insert_count', $inserted_so_far);

    }
}
