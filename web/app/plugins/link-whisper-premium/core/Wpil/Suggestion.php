<?php

/**
 * Work with suggestions
 */
class Wpil_Suggestion
{
    public static $undeletable = false;
    public static $max_anchor_length = 0;
    public static $min_anchor_length = 0;
    public static $keyword_dummy_index = 0;
    public static $ai_suggestion_threashold = 0.40;

    public static $post_word_cache = array();

    /**
     * Gets the suggestions for the current post/cat on ajax call.
     * Processes the suggested posts in batches to avoid timeouts on large sites.
     **/
    public static function ajax_get_post_suggestions(){

        $post_id = intval($_POST['post_id']);
        $term_id = intval($_POST['term_id']);
        $key = intval($_POST['key']);
        $user = wp_get_current_user();

        if((empty($post_id) && empty($term_id)) || empty($key) || 999999 > $key || empty($user->ID)){
            wp_send_json(array(
                'error' => array(
                    'title' => __('Data Error', 'wpil'),
                    'text'  => __('There was some data missing while processing the site content, please refresh the page and try again.', 'wpil'),
                )
            ));
        }

        // if the nonce doesn't check out, exit
        Wpil_Base::verify_nonce('wpil_suggestion_nonce');

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // Remove any hooks that may interfere with AJAX requests
        Wpil_Base::remove_problem_hooks();

        // check if we'll be doing AI scoring of the suggestions
        $ai_scoring_active = Wpil_Settings::get_ai_suggestion_score_active();

        // check if the user wants to use ai powerd suggestions
        $ai_powered = Wpil_Settings::get_use_ai_suggestions();

        if(!empty($term_id)){
            $post = new Wpil_Model_Post($term_id, 'term');
        }else{
            $post = new Wpil_Model_Post($post_id);
        }

        $count = null;
        if(isset($_POST['count'])){
            $count = intval($_POST['count']);
        }

        if(empty($count) && !empty(get_option('wpil_make_suggestion_filtering_persistent', false))){
            Wpil_Settings::update_suggestion_filters();
        }

        $batch_size = Wpil_Settings::getProcessingBatchSize();

        if(isset($_POST['type']) && 'outbound_suggestions' === $_POST['type']){
            // get the total number of posts that we'll be going through
            if(!isset($_POST['post_count']) || empty($_POST['post_count'])){
                $post_count = self::getPostProcessCount($post);
            }else{
                $post_count = intval($_POST['post_count']);
            }

            if($ai_powered){
                // check to see if the post content has changed since we last did this
                if(Wpil_AI::sentence_post_id_changed($post)){
                    // if it has, clear the data
                    Wpil_AI::clear_ai_suggestion_data($post);
                }

                // make sure that the database doens't have too much embedding data stored
                Wpil_AI::housekeep_phrase_embedding_data();

                $calculated = Wpil_AI::has_calculated_phrase_embeddings($post);
                $embedding_data = Wpil_AI::get_single_post_embedding_data($post);

                // see if we have the embedding data for this post
                if(!$calculated && empty($embedding_data)){
                    // if we don't, get it from OAI
                    $post_data = Wpil_AI::live_query_single_post_embedding_data($post);
                    // if we were successful
                    if(!empty($post_data)){
                        // save the data
                        Wpil_AI::save_single_post_embedding_data($post, $post_data);
                        // and exit so we can restart the loop now that we have the data
                        $message = __('Using AI to evaluate post content', 'wpil');
                        wp_send_json(array('status' => 'no_suggestions', 'post_count' => $post_count, 'batch_size' => $batch_size, 'count' => $count, 'message' => $message, 'ai_score' => $ai_scoring_active));
                    }
                }

                // if the AI calculations haven't completed
                if(!$calculated && !empty($embedding_data)){
                    // run the calculations
                    $calculations = Wpil_AI::stepped_calculate_phrase_embeddings($post, true); // TODO: Handle cases where the result is empty
                    $message = sprintf(__('Calculating post relationships... %s calculated so far', 'wpil'), $calculations);
                    wp_send_json(array('status' => 'no_suggestions', 'post_count' => $post_count, 'batch_size' => $batch_size, 'count' => $count, 'message' => $message, 'ai_score' => $ai_scoring_active));
                }
            }

            $phrase_array = array();
            while(!Wpil_Base::overTimeLimit(15, 45) && ($count * $batch_size) < $post_count){ // TODO: CHECK INBOUND SUGGSETIONS TO MAKE SURE THAT REMOVING THE -1 isn't a provcltme
                // get the phrases for this batch of posts
                if($ai_powered){
                    $phrases = self::getAIPostSuggestions($post, null, false, null, $count, $key);
                    if(is_string($phrases)){
                        break;
                    }

                }else{
                    $phrases = self::getPostSuggestions($post, null, false, null, $count, $key);
                }

                if(!empty($phrases)){
                    $phrase_array[] = $phrases;
                }

                $count++;
                break;
            }

            $status = 'no_suggestions';
            if(!empty($phrase_array)){
                $stored_phrases = get_transient('wpil_post_suggestions_' . $key);
                if(empty($stored_phrases)){
                    $stored_phrases = $phrase_array;
                }else{
                    // decompress the suggestions so we can add more to the list
                    $stored_phrases = Wpil_Toolbox::decompress($stored_phrases);

                    foreach($phrase_array as $phrases){
                        // add the suggestions
                        $stored_phrases[] = $phrases;
                    }
                }

                // compress the suggestions to save space
                $stored_phrases = Wpil_Toolbox::compress($stored_phrases);

                // store the current suggestions in a transient
                set_transient('wpil_post_suggestions_' . $key, $stored_phrases, MINUTE_IN_SECONDS * 15);
                // send back our status
                $status = 'has_suggestions';
            }

            $finish = false;
            $num = ($batch_size * $count < $post_count) ? $batch_size * $count : $post_count;
            if((!empty($phrases) && $phrases === 'hold_position')){
                $doing_cooldown = get_transient('wpil_chat_gpt_api_waiter');
                if((empty($doing_cooldown) || $doing_cooldown < time()) && !Wpil_AI::is_rate_limited()){
                    $message = __('Using AI to build anchors...', 'wpil');
                }else{
                    $message = __('ChatGPT Request Limit Reached for the Hour, Using Alternate Method to build anchors...', 'wpil');
                }
            }elseif(!empty($phrases) && $phrases === 'no_data'){
                $message = __('No Processable Content Found, Exiting Suggestions...', 'wpil');
                $finish = true;
            }elseif(!empty($phrases) && $phrases === 'no_posts'){
                $message = __('No Viable Posts Found, Finishing Up!', 'wpil');
                $finish = true;
            }else{
                $message = sprintf(__('Processing Link Suggestions: %d of %d processed', 'wpil'), $num, $post_count);
            }

            wp_send_json(array('status' => $status, 'post_count' => $post_count, 'batch_size' => $batch_size, 'count' => $count, 'message' => $message, 'ai_score' => $ai_scoring_active, 'finish' => $finish));

        }elseif(isset($_POST['type']) && 'inbound_suggestions' === $_POST['type']){

            $phrases = [];
            $memory_break_point = Wpil_Report::get_mem_break_point();
            $ignore_posts = Wpil_Settings::getIgnorePosts();
            $batch_size = $batch_size * 10;
            $max_links_per_post = get_option('wpil_max_links_per_post', 0);

            // if the keywords list only contains newline semicolons
            if(isset($_POST['keywords']) && empty(trim(str_replace(';', '', $_POST['keywords'])))){
                // remove the "keywords" index
                unset($_POST['keywords']);
                unset($_REQUEST['keywords']);
            }

            $completed_processing_count = (isset($_POST['completed_processing_count']) && !empty($_POST['completed_processing_count'])) ? (int) $_POST['completed_processing_count'] : 0;

            $keywords = self::getKeywords($post);

            $source_id = isset($_REQUEST['source']) && !empty($_REQUEST['source']) && !empty(preg_match('/(?:post|term)_[0-9]+(?![^0-9])/', $_REQUEST['source'], $m)) ? $m[0]: null;
            if(!empty($source_id)){
                $suggested_post_ids = array($source_id);
            }else{
                $suggested_post_ids = get_transient('wpil_inbound_suggested_post_ids_' . $key);
            }

            // get all the suggested posts for linking TO this post
            if(empty($suggested_post_ids)){
                $search_keywords = self::getKeywords($post, true);
                $search_keywords = (is_array($search_keywords)) ? implode(' ', $search_keywords) : $search_keywords;
                $ignored_category_posts = Wpil_Settings::getIgnoreCategoriesPosts();
                $linked_post_ids = Wpil_Post::getLinkedPostIDs($post);
                $excluded_posts = array_merge($linked_post_ids, $ignored_category_posts);
                $suggested_posts = self::getInboundSuggestedPosts($search_keywords, $excluded_posts);
                $suggested_terms = self::get_inbound_suggested_terms($search_keywords, $post);
                $suggested_post_ids = array();

                // if the user wants to ignore "noindex" posts from the suggestions
                if(Wpil_Settings::removeNoindexFromSuggestions()){
                    if(!empty($suggested_posts)){
                        // filter out the post ids that have been set to "noindex"
                        $suggested_posts = Wpil_Query::remove_noindex_ids($suggested_posts, 'post');
                    }

                    if(!empty($suggested_terms)){
                        // filter out the term ids that have been set to "noindex"
                        $suggested_terms = Wpil_Query::remove_noindex_ids($suggested_terms, 'term');
                    }
                }

                $post_embeddings = Wpil_AI::get_embedding_relatedness_data($post->id, $post->type, true);
                $relatedness_threshold = Wpil_Settings::get_suggestion_filter('ai_relatedness_threshold');

                foreach($suggested_posts as $suggested_post){
                    $id = 'post_' . $suggested_post;
                    
                    if(!empty($post_embeddings) && isset($post_embeddings[$id]) && $post_embeddings[$id] < $relatedness_threshold){
//                        continue;
                    }

                    $suggested_post_ids[] = $id;
                }

                foreach($suggested_terms as $suggested_term){
                    $id = 'term_' . $suggested_term;

                    if(!empty($post_embeddings) && isset($post_embeddings[$id]) && $post_embeddings[$id] < $relatedness_threshold){
//                        continue;
                    }

                    $suggested_post_ids[] = $id;
                }

                // if the user wants to limit the number of posts searched for suggestions
                $max_count = Wpil_Settings::get_max_suggestion_post_count();
                if(!empty($max_count) && !empty($suggested_post_ids)){
                    // shuffle the ids so we don't consistently miss posts
                    shuffle($suggested_post_ids);
                    // obtain the number of posts that the user wants
                    $suggested_post_ids = array_slice($suggested_post_ids, 0, $max_count);
                }

                set_transient('wpil_inbound_suggested_post_ids_' . $key, $suggested_post_ids, MINUTE_IN_SECONDS * 10);
            }else{
                // if there are stored ids, re-save the transient to refresh the count down
                set_transient('wpil_inbound_suggested_post_ids_' . $key, $suggested_post_ids, MINUTE_IN_SECONDS * 10);
            }

            $last_post = (isset($_POST['last_post'])) ? $_POST['last_post'] : 0;

            if(isset(array_flip($suggested_post_ids)[$last_post])){
                $post_ids_to_process = array_slice($suggested_post_ids, (array_search($last_post, $suggested_post_ids) + 1), $batch_size);
            }else{
                $post_ids_to_process = array_slice($suggested_post_ids, 0, $batch_size);
            }

            $process_count = 0;
            $current_post = $last_post;
            foreach($post_ids_to_process as $post_id) {
                $temp_phrases = [];
                foreach ($keywords as $ind => $keyword) {
                    if (Wpil_Base::overTimeLimit(15, 45) || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point) ){
                        break 2;
                    }

                    $bits = explode('_', $post_id);
                    $links_post = new Wpil_Model_Post($bits[1], $bits[0]);
                    $current_post = $post_id;

                    // if the post isn't being ignored
                    if(!in_array($post_id, $ignore_posts)){
                        // if the user has set a max link count for posts and this is the first pass for the post
                        if(!empty($max_links_per_post) && $ind < 1 && Wpil_link::at_max_outbound_links($links_post)){
                            // skip any posts that are at the limit
                            break;
                        }

                        //get suggestions for post
                        if (!empty($keyword)) {
                            $suggestions = self::getPostSuggestions($links_post, $post, false, $keyword, null, $key);
                        } else {
                            $suggestions = self::getPostSuggestions($links_post, $post, false, null, null, $key);
                        }

                        //skip if no suggestions
                        if (!empty($suggestions)) {
                            $temp_phrases = array_merge($temp_phrases, $suggestions);
                        }
                    }
                }

                // increase the count of processed posts
                $process_count++;

                if (count($temp_phrases)) {
                    Wpil_Phrase::TitleKeywordsCheck($temp_phrases, $keyword);
                    $phrases = array_merge($phrases, $temp_phrases);
                }
            }

            // get the suggestions transient
            $stored_phrases = get_transient('wpil_post_suggestions_' . $key);

            // if there are suggestions stored
            if(!empty($stored_phrases)){
                // decompress the suggestions so we can add more to the list
                $stored_phrases = Wpil_Toolbox::decompress($stored_phrases);
            }else{
                $stored_phrases = array();
            }

            // if there are phrases to save
            if($phrases){
                if(empty($stored_phrases)){
                    $stored_phrases = $phrases;
                }else{
                    // add the suggestions
                    $stored_phrases = array_merge($stored_phrases, $phrases);
                }
            }

            // compress the suggestions to save space
            $stored_phrases = Wpil_Toolbox::compress($stored_phrases);

            // save the suggestion data
            set_transient('wpil_post_suggestions_' . $key, $stored_phrases, MINUTE_IN_SECONDS * 15);

            $processing_status = array(
                    'status' => 'no_suggestions',
                    'keywords' => $keywords,
                    'last_post' => $current_post,
                    'post_count' => count($suggested_post_ids),
                    'id_count_to_process' => count($post_ids_to_process),
                    'completed' => empty(count($post_ids_to_process)), // has the processing run completed? If it has, then there won't be any posts to process
                    'completed_processing_count' => ($completed_processing_count += $process_count),
                    'batch_size' => $batch_size,
                    'posts_processed' => $process_count,
                    'ai_score' => $ai_scoring_active,
            );

            if(!empty($phrases)){
                $processing_status['status'] = 'has_suggestions';
            }

            // maybe optimize the options table
            Wpil_Toolbox::maybe_optimize_options_table();

            wp_send_json($processing_status);

        }else{
            wp_send_json(array(
                'error' => array(
                    'title' => __('Unknown Error', 'wpil'),
                    'text'  => __('The data is incomplete for processing the request, please reload the page and try again.', 'wpil'),
                )
            ));
        }
    }

    /**
     * Gets the suggestions for any external sites that the user has linked to this one.
     *
     **/
    public static function ajax_get_external_site_suggestions(){
        $post_id = intval($_POST['post_id']);
        $term_id = intval($_POST['term_id']);
        $key = intval($_POST['key']);
        $user = wp_get_current_user();
        $ai_scoring_active = Wpil_Settings::get_ai_suggestion_score_active();

        // exit the processing if there's no external linking to do
        $linking_enabled = get_option('wpil_link_external_sites', false);
        if(empty($linking_enabled)){
            $message = (!$ai_scoring_active) ? __('Processing Complete', 'wpil'): __('Beginning AI Suggestion Processing', 'wpil');
            wp_send_json(array('status' => 'no_suggestions', 'post_count' => 0, 'batch_size' => 300, 'count' => 0, 'message' => $message, 'ai_score' => $ai_scoring_active));
        }

        if((empty($post_id) && empty($term_id)) || empty($key) || 999999 > $key || empty($user->ID)){
            wp_send_json(array(
                'error' => array(
                    'title' => __('Data Error', 'wpil'),
                    'text'  => __('There was some data missing while processing the site content, please refresh the page and try again.', 'wpil'),
                )
            ));
        }

        // if the nonce doesn't check out, exit
        Wpil_Base::verify_nonce('wpil_suggestion_nonce');

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // Remove any hooks that may interfere with AJAX requests
        Wpil_Base::remove_problem_hooks();

        if(!empty($term_id)){
            $post = new Wpil_Model_Post($term_id, 'term');
        }else{
            $post = new Wpil_Model_Post($post_id);
        }

        $count = 0;
        if(isset($_POST['count'])){
            $count = intval($_POST['count']);
        }

        $batch_size = Wpil_Settings::getProcessingBatchSize();

        if(!isset($_POST['post_count']) || empty($_POST['post_count'])){
            // get the total number of posts that we'll be going through
            $post_count = Wpil_SiteConnector::count_data_items();
        }else{
            $post_count = (int) $_POST['post_count'];
        }

        // exit the processing if there's no external posts to link to
        if(empty($post_count)){
            $message = (!$ai_scoring_active) ? __('Processing Complete', 'wpil'): __('Beginning AI Suggestion Processing', 'wpil');
            wp_send_json(array('status' => 'no_suggestions', 'post_count' => 0, 'batch_size' => $batch_size, 'count' => $count, 'message' => $message, 'ai_score' => $ai_scoring_active));
        }

        $phrase_array = array();
        while(!Wpil_Base::overTimeLimit(15, 30) && (($count - 1) * $batch_size) < $post_count){

            // get the phrases for this batch of posts
            $phrases = self::getExternalSiteSuggestions($post, false, null, $count, $key);

            if(!empty($phrases)){
                $phrase_array[] = $phrases;
            }

            $count++;
        }

        $status = 'no_suggestions';
        if(!empty($phrase_array)){
            $stored_phrases = get_transient('wpil_post_suggestions_' . $key);
            if(empty($stored_phrases)){
                $stored_phrases = $phrase_array;
            }else{
                // decompress the suggestions so we can add more to the list
                $stored_phrases = Wpil_Toolbox::decompress($stored_phrases);

                foreach($phrase_array as $phrases){
                    // add the suggestions
                    $stored_phrases[] = $phrases;
                }
            }

            // compress the suggestions to save space
            $stored_phrases = Wpil_Toolbox::compress($stored_phrases);

            // store the current suggestions in a transient
            set_transient('wpil_post_suggestions_' . $key, $stored_phrases, MINUTE_IN_SECONDS * 15);
            // send back our status
            $status = 'has_suggestions';
        }

        $num = ($batch_size * $count < $post_count) ? $batch_size * $count : $post_count;
        $message = sprintf(__('Processing External Site Link Suggestions: %d of %d processed', 'wpil'), $num, $post_count);

        wp_send_json(array('status' => $status, 'post_count' => $post_count, 'batch_size' => $batch_size, 'count' => $count, 'message' => $message, 'ai_score' => $ai_scoring_active));
    }

    public static function ajax_perform_ai_suggestion_scoring(){
        $post_id = intval($_POST['post_id']);
        $term_id = intval($_POST['term_id']);
        $no_response_count = isset($_POST['no_response']) ? intval($_POST['no_response']): 0;
        $process_key = intval($_POST['key']);
        $user = wp_get_current_user();
        $inbound_suggestions = (isset($_POST['type']) && 'inbound_suggestions' === $_POST['type']) ? true : false;

        // if the processing specifics are missing, exit
        if((empty($post_id) && empty($term_id)) || empty($process_key) || 999999 > $process_key || empty($user->ID)){
            wp_send_json(array(
                'error' => array(
                    'title' => __('Data Error', 'wpil'),
                    'text'  => __('There was some data missing while processing the site content, please refresh the page and try again.', 'wpil'),
                )
            ));
        }

        // if the nonce doesn't check out, exit
        Wpil_Base::verify_nonce('wpil_suggestion_nonce');

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // Remove any hooks that may interfere with AJAX requests
        Wpil_Base::remove_problem_hooks();

        // setup the post
        $post = (!empty($post_id)) ? new Wpil_Model_Post($post_id): new Wpil_Model_Post($term_id, 'term');

        if('outbound_suggestions' === $_POST['type']){
            // get the suggestions from the database
            $phrases = get_transient('wpil_post_suggestions_' . $process_key);
        }elseif($inbound_suggestions){
            $phrases = get_transient('wpil_post_suggestions_' . $process_key);
        }

        // if there are suggestions
        if(!empty($phrases)){
            // decompress the suggestions
            $phrases = Wpil_Toolbox::decompress($phrases);
        }

        // if we're processing inbound suggestions
        if($inbound_suggestions && !empty($phrases)){
            // nest the suggestions so we can process them
            $phrases = array($phrases);
        }

        $search_data = array();
        $ai_scored_count = 0;
        $total_suggestions = 0;
        foreach($phrases as $batch_key => $phrase_data){
            foreach($phrase_data as $phrase_key => $phrase){
                foreach($phrase->suggestions as $suggestion_key => $suggestion){
                    $total_suggestions++;
                    if($suggestion->has_ai_scored()){
                        $ai_scored_count++;
                        continue;
                    }
                    $search_data[] = array(
                        'batch_key' => $batch_key,
                        'phrase_key'=> $phrase_key,
                        'suggestion_key' => $suggestion_key,
                        'sentence' => $phrase->text,
                        'context' => $phrase->sentence_text,
                        'target' => ($inbound_suggestions) ? $post->getTitle(true): $suggestion->post->getTitle(true)
                    );
                }
            }
        }

        if(empty($search_data)){
            wp_send_json(array('ai_score_complete' => true, 'message' => __('Finishing Up!', 'wpil')));
        }
//        wp_send_json(array('ai_score_complete' => true, 'message' => 'Message! ' . time()));
        $relatedness = Wpil_AI::determine_relatedness($search_data);

        if($relatedness === 'no-response'){
            $exit_now = ($no_response_count >= 3);
            $no_response_count++;
            $message = ($exit_now) ? sprintf(__('Open AI isn\'t responding to our requests... Continuing to suggestion processing', 'wpil'), (3 - $no_response_count)): sprintf(__('Open AI isn\'t responding... This is attempt number %d', 'wpil'), $no_response_count);
            wp_send_json(array('ai_score_complete' => $exit_now, 'message' => $message, 'no_response' => $no_response_count));
        }

        if(!empty($relatedness)){
            foreach($relatedness as $id => $dat){
                $id_bits = explode('-', $id);
                if( !empty($id_bits) && // if there are bits
                    count($id_bits) === 3 && // if there are 3 of them
                    isset($phrases[$id_bits[0]]) && // if all the bits correlate to keys in the phrases
                    isset($phrases[$id_bits[0]][$id_bits[1]]) &&
                    isset($phrases[$id_bits[0]][$id_bits[1]]->suggestions) &&
                    isset($phrases[$id_bits[0]][$id_bits[1]]->suggestions[$id_bits[2]]))
                {
                    // add the data about the ai scoring to the suggestion
                    $phrases[$id_bits[0]][$id_bits[1]]->suggestions[$id_bits[2]]->ai_score_data = $dat;
                    $ai_scored_count++;
                }
            }
        }

        // if we're processing inbound suggestions
        if($inbound_suggestions){
            // de-nest the suggestions
            $phrases = $phrases[0];
        }

        $phrases = Wpil_Toolbox::compress($phrases);
        set_transient('wpil_post_suggestions_' . $process_key, $phrases, DAY_IN_SECONDS);

        $message = sprintf(__('Performing AI Scoring: %d of %d scored.', 'wpil'), $ai_scored_count, $total_suggestions);

        wp_send_json(array('ai_score_complete' => false, 'message' => $message));
    }

    /**
     * Gets the suggestions for the current post/cat on ajax call.
     * Processes the suggested posts in batches to avoid timeouts on large sites.
     **/
    public static function ajax_search_for_inbound_targets(){

        $post_id = intval($_POST['post_id']);
        $term_id = intval($_POST['term_id']);
        $key = intval($_POST['key']);
        $user = wp_get_current_user();

        if((empty($post_id) && empty($term_id)) || empty($key) || 999999 > $key || empty($user->ID)){
            wp_send_json(array(
                'error' => array(
                    'title' => __('Data Error', 'wpil'),
                    'text'  => __('There was some data missing while processing the site content, please refresh the page and try again.', 'wpil'),
                )
            ));
        }

        // if the nonce doesn't check out, exit
        Wpil_Base::verify_nonce('wpil_suggestion_nonce');

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // Remove any hooks that may interfere with AJAX requests
        Wpil_Base::remove_problem_hooks();

        if(!empty($term_id)){
            $post = new Wpil_Model_Post($term_id, 'term');
        }else{
            $post = new Wpil_Model_Post($post_id);
        }
/*
        if(!empty(get_option('wpil_make_suggestion_filtering_persistent', false))){
            Wpil_Settings::update_suggestion_filters();
        }
*/
        // if the keywords list only contains newline semicolons
        if(isset($_POST['keywords']) && empty(trim(str_replace(';', '', $_POST['keywords'])))){
            // remove the "keywords" index
            unset($_POST['keywords']);
            unset($_REQUEST['keywords']);
        }

        $suggested_post_ids = get_transient('wpil_inbound_suggested_post_ids_' . $key);
        // get all the suggested posts for linking TO this post
        if(empty($suggested_post_ids)){
            $search_keywords = self::getKeywords($post, true);
            $search_keywords = (is_array($search_keywords)) ? implode(' ', $search_keywords) : $search_keywords;
            $ignored_category_posts = Wpil_Settings::getIgnoreCategoriesPosts();
            $linked_post_ids = Wpil_Post::getLinkedPostIDs($post);
            $excluded_posts = array_merge($linked_post_ids, $ignored_category_posts);
            $suggested_posts = self::getInboundSuggestedPosts($search_keywords, $excluded_posts);
            $suggested_terms = self::get_inbound_suggested_terms($search_keywords, $post);
            $suggested_post_ids = array();

            // if the user wants to ignore "noindex" posts from the suggestions
            if(Wpil_Settings::removeNoindexFromSuggestions()){
                if(!empty($suggested_posts)){
                    // filter out the post ids that have been set to "noindex"
                    $suggested_posts = Wpil_Query::remove_noindex_ids($suggested_posts, 'post');
                }

                if(!empty($suggested_terms)){
                    // filter out the term ids that have been set to "noindex"
                    $suggested_terms = Wpil_Query::remove_noindex_ids($suggested_terms, 'term');
                }
            }

            foreach($suggested_posts as $suggested_post){
                $suggested_post_ids[] = 'post_' . $suggested_post;
            }

            foreach($suggested_terms as $suggested_term){
                $suggested_post_ids[] = 'term_' . $suggested_term;
            }

            // if the user wants to limit the number of posts searched for suggestions
            $max_count = Wpil_Settings::get_max_suggestion_post_count();
            if(!empty($max_count) && !empty($suggested_post_ids)){
                // shuffle the ids so we don't consistently miss posts
                shuffle($suggested_post_ids);
                // obtain the number of posts that the user wants
                $suggested_post_ids = array_slice($suggested_post_ids, 0, $max_count);
            }

            set_transient('wpil_inbound_suggested_post_ids_' . $key, $suggested_post_ids, MINUTE_IN_SECONDS * 20);
        }else{
            // if there are stored ids, re-save the transient to refresh the count down
            set_transient('wpil_inbound_suggested_post_ids_' . $key, $suggested_post_ids, MINUTE_IN_SECONDS * 20);
        }

        wp_send_json(array('success' => array('key' => $key, 'id' => $post->id, 'type' => $post->type)));
    }

    /**
     * Updates the link report displays with the suggestion results from ajax_get_post_suggestions.
     **/
    public static function ajax_update_suggestion_display(){
        $post_id = intval($_POST['post_id']);
        $term_id = intval($_POST['term_id']);
        $process_key = intval($_POST['key']);
        $user = wp_get_current_user();

        // if the processing specifics are missing, exit
        if((empty($post_id) && empty($term_id)) || empty($process_key) || 999999 > $process_key || empty($user->ID)){
            wp_send_json(array(
                'error' => array(
                    'title' => __('Data Error', 'wpil'),
                    'text'  => __('There was some data missing while processing the site content, please refresh the page and try again.', 'wpil'),
                )
            ));
        }

        // if the nonce doesn't check out, exit
        Wpil_Base::verify_nonce('wpil_suggestion_nonce');

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // Remove any hooks that may interfere with AJAX requests
        Wpil_Base::remove_problem_hooks();

        if(!empty($term_id)){
            $post = new Wpil_Model_Post($term_id, 'term');
        }else{
            $post = new Wpil_Model_Post($post_id);
        }

        $same_category = Wpil_Settings::get_suggestion_filter('same_category');
        $max_suggestions_displayed = Wpil_Settings::get_max_suggestion_count();

        if('outbound_suggestions' === $_POST['type']){
            // get the suggestions from the database
            $phrases = get_transient('wpil_post_suggestions_' . $process_key);

            // if there are suggestions
            if(!empty($phrases)){
                // decompress the suggestions
                $phrases = Wpil_Toolbox::decompress($phrases);
            }

            // merge them all into a suitable array
            $phrase_groups = self::merge_phrase_suggestion_arrays($phrases);

            foreach($phrase_groups as $phrases){
                foreach($phrases as $phrase){
                    usort($phrase->suggestions, function ($a, $b) {
                        $a_pillar = false;
                        $b_pillar = false;
                        if(class_exists('WPSEO_Meta') && method_exists('WPSEO_Meta', 'get_value')){
                            $a_pillar = ($a->post->type === 'post' && WPSEO_Meta::get_value('is_cornerstone', $a->post->id) === '1');
                            $b_pillar = ($b->post->type === 'post' && WPSEO_Meta::get_value('is_cornerstone', $b->post->id) === '1');
                        }elseif(defined('RANK_MATH_VERSION')){
                            $a_pillar = ($a->post->type === 'post' && get_post_meta($a->post->id, 'rank_math_pillar_content', true) === 'on');
                            $b_pillar = ($b->post->type === 'post' && get_post_meta($b->post->id, 'rank_math_pillar_content', true) === 'on');
                        }

                        // if one of these is pillar and the other isnt
                        if($a_pillar !== $b_pillar){
                            // prioritize the pillar content
                            return ($a_pillar > $b_pillar) ? -1 : 1;
                        }

                        if(!empty($a->ai_relatedness_calculation) && !empty($b->ai_relatedness_calculation)){
                            return ($a->ai_relatedness_calculation > $b->ai_relatedness_calculation) ? -1 : 1;
                        }

                        // if they both have the same pillar status, sort by score
                        if ($a->total_score == $b->total_score) {
                            return 0;
                        }
                        return ($a->total_score > $b->total_score) ? -1 : 1;
                    });
                }
            }

            $used_posts = array($post_id . ($post->type == 'term' ? 'cat' : ''));

            foreach($phrase_groups as $type => $phrases){
                if (!empty($phrase_groups[$type])) {
                    //$phrase_groups[$type] = self::deleteWeakPhrases(array_filter($phrase_groups[$type]));
                    $phrase_groups[$type] = self::addAnchors($phrase_groups[$type], true);

                    // if the user is limiting the number of suggestions to display
                    if(!empty($max_suggestions_displayed) && !empty($phrase_groups[$type])){
                        // trim back the number of suggestions to fit
                        $phrase_groups[$type] = array_slice($phrase_groups[$type], 0, $max_suggestions_displayed);
                    }
                }
            }

            //remove same suggestions on top level
            foreach($phrase_groups as $phrases){
                foreach ($phrases as $key => $phrase) {
                    if(empty($phrase->suggestions) || !isset($phrase->suggestions[0])){
                        unset($phrases[$key]);
                        continue;
                    }

                    if(is_a($phrase->suggestions[0]->post, 'Wpil_Model_ExternalPost')){
                        $post_key = ($phrase->suggestions[0]->post->type=='term'?'ext_cat':'ext_post') . $phrase->suggestions[0]->post->id;
                    }else{
                        $post_key = ($phrase->suggestions[0]->post->type=='term'?'cat':'') . $phrase->suggestions[0]->post->id;
                    }

                    if (!empty($target) || !in_array($post_key, $used_posts)) {
                        $used_posts[] = $post_key;
                    } else {
                        if (!empty(self::$undeletable)) {
                            $phrase->suggestions[0]->opacity = .5;
                        } else {
                            unset($phrase->suggestions[0]);
                        }

                    }

                    if (!count($phrase->suggestions)) {
                        unset($phrases[$key]);
                    } else {
                        if (!empty(self::$undeletable)) {
                            $i = 1;
                            foreach ($phrase->suggestions as $suggestion) {
                                $i++;
                                if ($i > 10) {
                                    $suggestion->opacity = .5;
                                }
                            }
                        } else {
                            $phrase->suggestions = array_slice($phrase->suggestions, 0, 10);
                        }
                    }
                }
            }

            /* NOTE I don't remember if I moved this down, or this is the old place that it used to be at and it should be higher up the page... Test to find out the correct placing and remove the appropriate block
            foreach($phrase_groups as $type => $phrases){
                if (!empty($phrase_groups[$type])) {
                    $phrase_groups[$type] = self::deleteWeakPhrases(array_filter($phrase_groups[$type]));
                    $phrase_groups[$type] = self::addAnchors($phrase_groups[$type], true);

                    // if the user is limiting the number of suggestions to display
                    if(!empty($max_suggestions_displayed) && !empty($phrase_groups[$type])){
                        // trim back the number of suggestions to fit
                        $phrase_groups[$type] = array_slice($phrase_groups[$type], 0, $max_suggestions_displayed);
                    }
                }
            }
*/
            $selected_categories = self::get_selected_categories();
            $taxes = get_object_taxonomies(get_post($post_id));
            $query_cats = array();
            $query_tags = array();
            foreach($taxes as $tax){
                if(get_taxonomy($tax)->hierarchical){
                    $query_cats[] = $tax;
                }else{
                    $query_tags[] = $tax;
                }
            }
            $categories = wp_get_object_terms($post_id, $query_cats, ['fields' => 'all_with_object_id']);
            if (empty($categories) || is_a($categories, 'WP_Error')) {
                $categories = [];
            }

            if(empty($selected_categories) && !empty($categories)){
                $selected_categories = array_map(function($cat){ return $cat->term_taxonomy_id; }, $categories);
            }

            $selected_tags = self::get_selected_tags();

            $tags = wp_get_object_terms($post_id, $query_tags, ['fields' => 'all_with_object_id']);
            if (empty($tags) || is_a($tags, 'WP_Error')) {
                $tags = [];
            }

            if(empty($selected_tags) && !empty($tags)){
                $selected_tags = array_map(function($tag){ return $tag->term_taxonomy_id; }, $tags);
            }

            $same_tag = !empty(Wpil_Settings::get_suggestion_filter('same_tag'));
            $has_orphaned = Wpil_Dashboard::hasOrphanedPosts();
            $has_parent = ($post->type === 'post' && !empty(Wpil_Toolbox::get_related_post_ids($post))) ? true: false;
            $link_orphaned = Wpil_Settings::get_suggestion_filter('link_orphaned') ? 1 : 0;
            $same_parent = Wpil_Settings::get_suggestion_filter('same_parent') ? 1 : 0;
            $select_post_types = Wpil_Settings::get_suggestion_filter('select_post_types') ? 1 : 0;
            $selected_post_types = self::getSuggestionPostTypes();
            $post_types = Wpil_Settings::getPostTypeLabels(Wpil_Settings::getPostTypes());
            $filter_time_format = Wpil_Toolbox::convert_date_format_from_js();
            $ai_relatedness_threshold = Wpil_Settings::get_suggestion_filter('ai_relatedness_threshold');
            $ai_use_ai_suggestions = Wpil_Settings::get_use_ai_suggestions();
            include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/linking_data_list_v2.php';
            // clear the suggestion cache now that we're done with it
            self::clearSuggestionProcessingCache($process_key, $post->id);
        }elseif('inbound_suggestions' === $_POST['type']){
            $phrases = get_transient('wpil_post_suggestions_' . $process_key);
            // decompress the suggestions
            $phrases = Wpil_Toolbox::decompress($phrases);
            //add links to phrases
            Wpil_Phrase::InboundSort($phrases);
            $phrases = self::addAnchors($phrases);
            $groups = self::getInboundGroups($phrases);

            // if the user is limiting the number of suggestions to display
            if(!empty($max_suggestions_displayed) && !empty($groups)){
                // trim back the number of suggestions to fit
                $groups = array_slice($groups, 0, $max_suggestions_displayed, true); // preserve the keys because the array is keyed to the post id
            }

            $selected_categories = self::get_selected_categories();
            $taxes = get_object_taxonomies(get_post($post_id));
            $query_taxes = array();
            foreach($taxes as $tax){
                if(get_taxonomy($tax)->hierarchical){
                    $query_taxes[] = $tax;
                }
            }
            $categories = wp_get_object_terms($post_id, $query_taxes, ['fields' => 'all_with_object_id']);
            if (empty($categories) || is_a($categories, 'WP_Error')) {
                $categories = [];
            }

            if(empty($selected_categories) && !empty($categories)){
                $selected_categories = array_map(function($cat){ return $cat->term_taxonomy_id; }, $categories);
            }

            $same_tag = !empty(Wpil_Settings::get_suggestion_filter('same_tag'));
            $selected_tags = self::get_selected_tags();
            $taxes = get_object_taxonomies(get_post($post_id));
            $query_taxes = array();
            foreach($taxes as $tax){
                if(empty(get_taxonomy($tax)->hierarchical)){
                    $query_taxes[] = $tax;
                }
            }
            $tags = wp_get_object_terms($post_id, $query_taxes, ['fields' => 'all_with_object_id']);
            if (empty($tags) || is_a($tags, 'WP_Error')) {
                $tags = [];
            }

            if(empty($selected_tags) && !empty($tags)){
                $selected_tags = array_map(function($tag){ return $tag->term_taxonomy_id; }, $tags);
            }

            $has_parent = ($post->type === 'post' && !empty(Wpil_Toolbox::get_related_post_ids($post))) ? true: false;
            $same_parent = Wpil_Settings::get_suggestion_filter('same_parent') ? 1 : 0;
            $select_post_types = Wpil_Settings::get_suggestion_filter('select_post_types') ? 1 : 0;
            $selected_post_types = self::getSuggestionPostTypes();
            $post_types = Wpil_Settings::getPostTypeLabels(Wpil_Settings::getPostTypes());
            $filter_time_format = Wpil_Toolbox::convert_date_format_from_js();
            $ai_relatedness_threshold = Wpil_Settings::get_suggestion_filter('ai_relatedness_threshold');
            $source_id = isset($_REQUEST['source']) && !empty($_REQUEST['source']) && !empty(preg_match('/(?:post|term)_[0-9]+(?![^0-9])/', $_REQUEST['source'], $m)) ? $m[0]: null;
            include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/inbound_suggestions_page_container.php';
            self::clearSuggestionProcessingCache($process_key, $post->id);
        }

        exit;
    }

    /** 
     * Saves the user's "Load without animation" setting so it's persistent between loads
     **/
    public static function ajax_save_animation_load_status(){
        if(isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wpil-load-with-animation-nonce') && (isset($_POST['status']) || array_key_exists('status', $_POST))){
            update_user_meta(get_current_user_id(), 'wpil_disable_load_with_animation', (int)$_POST['status']);
        }
    }

    /** 
     * Saves the user's "Allow multiple links in edited sentence" setting so it's persistent between loads
     **/
    public static function ajax_set_allow_multiple_sentence_links(){
        Wpil_Base::verify_nonce('allow_multiple_links_editor');

        $checked = (isset($_POST['checked']) && !empty($_POST['checked'])) ? 1: 0;

        update_user_meta(get_current_user_id(), 'wpil_allow_multiple_editor_links', $checked);

        wp_send_json(array('success' => array('text' => 'Setting updated!')));
    }

    /**
     * Merges multiple arrays of phrase data into a single array suitable for displaying.
     **/
    public static function merge_phrase_suggestion_arrays($phrase_array = array(), $inbound_suggestions = false){

        if(empty($phrase_array)){
            return array();
        }

        $merged_phrases = array('internal_site' => array(), 'external_site' => array());
        if(true === $inbound_suggestions){ // a simpler process is used for the inbound suggestions // Note: not currently used but might be used for inbound external matches
            foreach($phrase_array as $phrase_batch){
                $unserialized_batch = maybe_unserialize($phrase_batch);
                if(!empty($unserialized_batch)){
                    $merged_phrases = array_merge($merged_phrases, $unserialized_batch);
                }
            }
        }else{
            foreach($phrase_array as $phrase_batch){
                $unserialized_batch = maybe_unserialize($phrase_batch);
                if(is_array($unserialized_batch) && !empty($unserialized_batch)){
                    foreach($unserialized_batch as $phrase_key => $phrase_obj){
                        // go over each suggestion in the phrase obj
                        foreach($phrase_obj->suggestions as $post_id => $suggestion){
                            if(is_a($suggestion->post, 'Wpil_Model_ExternalPost')){
                                if(!isset($merged_phrases['external_site'][$phrase_key])){
                                    $base_phrase = $phrase_obj;
                                    unset($base_phrase->suggestions);
                                    $merged_phrases['external_site'][$phrase_key] = $base_phrase;
                                }
                                $merged_phrases['external_site'][$phrase_key]->suggestions[] = $suggestion;
                            }else{
                                if(!isset($merged_phrases['internal_site'][$phrase_key])){
                                    $base_phrase = $phrase_obj;
                                    unset($base_phrase->suggestions);
                                    $merged_phrases['internal_site'][$phrase_key] = $base_phrase;
                                }
                                $merged_phrases['internal_site'][$phrase_key]->suggestions[] = $suggestion;
                            }
                        }
                    }
                }
            }
        }

        if(isset($merged_phrases['internal_site']) && !empty($merged_phrases['internal_site'])){
            foreach($merged_phrases['internal_site'] as $phrase_key => $phrase){
                if(isset($phrase->suggestions) && !empty($phrase->suggestions)){
                    usort($phrase->suggestions, function ($a, $b) {
                        if(!empty($a->ai_relatedness_calculation) || !empty($b->ai_relatedness_calculation)){
                            if ($a->ai_relatedness_calculation == $b->ai_relatedness_calculation) {
                                return 0;
                            }
                            return ($a->ai_relatedness_calculation > $b->ai_relatedness_calculation) ? -1 : 1;
                        }


                        if ($a->total_score == $b->total_score) {
                            return 0;
                        }
                        return ($a->total_score > $b->total_score) ? -1 : 1;
                    });
                    $merged_phrases['internal_site'][$phrase_key]->top_ai_score = $phrase->suggestions[0]->ai_relatedness_calculation;
                    $merged_phrases['internal_site'][$phrase_key]->top_sentence_score = $phrase->suggestions[0]->total_score;
                }
            }

            usort($merged_phrases['internal_site'], function ($a, $b) {
                if(!empty($a->top_ai_score) || !empty($b->top_ai_score)){
                    if ($a->top_ai_score == $b->top_ai_score) {
                        return 0;
                    }
                    return ($a->top_ai_score > $b->top_ai_score) ? -1 : 1;
                }


                if ($a->top_sentence_score == $b->top_sentence_score) {
                    return 0;
                }
                return ($a->top_sentence_score > $b->top_sentence_score) ? -1 : 1;
            });
        }

        return $merged_phrases;
    }

    public static function getPostProcessCount($post){
        global $wpdb;
        //add all posts to array
        $post_count = 0;
        $exclude = self::getTitleQueryExclude($post);
        $post_types = implode("','", Wpil_Settings::getPostTypes());
        $exclude_categories = Wpil_Settings::getIgnoreCategoriesPosts();
        if (!empty($exclude_categories)) {
            $exclude_categories = " AND ID NOT IN (" . implode(',', $exclude_categories) . ") ";
        } else {
            $exclude_categories = '';
        }

        // get the age query if the user is limiting the range for linking
        $age_string = Wpil_Query::getPostDateQueryLimit();

        $statuses_query = Wpil_Query::postStatuses();
        $results = $wpdb->get_results("SELECT COUNT('ID') AS `COUNT` FROM {$wpdb->prefix}posts WHERE 1=1 $exclude $exclude_categories AND post_type IN ('{$post_types}') $statuses_query {$age_string}");
        $post_count = $results[0]->COUNT;

        $taxonomies = Wpil_Settings::getTermTypes();
        if (!empty($taxonomies) && empty(self::get_selected_categories()) && empty(self::get_selected_tags())) {
            //add all categories to array
            $exclude = "";
            if ($post->type == 'term') {
                $exclude = " AND t.term_id != {$post->id} ";
            }

            $results = $wpdb->get_results("SELECT COUNT(t.term_id)  AS `COUNT` FROM {$wpdb->prefix}term_taxonomy tt LEFT JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id WHERE tt.taxonomy IN ('" . implode("', '", $taxonomies) . "') $exclude");
            $post_count += $results[0]->COUNT;
        }

        return $post_count;
    }

    public static function getExternalSitePostCount(){
        global $wpdb;
        $data_table = $wpdb->prefix . 'wpil_site_linking_data';
        $post_count = 0;


        $linked_sites = Wpil_SiteConnector::get_linked_sites();
        if(!empty($linked_sites)){
            $results = $wpdb->get_var("SELECT COUNT(item_id) FROM {$data_table}");
            $post_count += $results;
        }

        return $results;
    }

    /**
     * Get link suggestions for the post
     *
     * @param $post_id
     * @param $ui
     * @param null $target_post_id
     * @return array|mixed
     */
    public static function getPostSuggestions($post, $target = null, $all = false, $keyword = null, $count = null, $process_key = 0)
    {
        $ignored_words = Wpil_Settings::getIgnoreWords();
        $stemmed_ignore_words = Wpil_Settings::getStemmedIgnoreWords();
        $is_outbound = (empty($target)) ? true: false;
        $use_slug = Wpil_Settings::use_post_slug_for_suggestions();

        if ($target) {
            $internal_links = Wpil_Post::getLinkedPostIDs($target, false);
        }else{

            $internal_links = get_transient('wpil_outbound_post_links' . $process_key);
            if(empty($internal_links)){
                // if we're preventing twoway linking
                if(get_option('wpil_prevent_two_way_linking', false)){
                    // get the inbound && the outbound internal links
                    $internal_links = Wpil_Post::getLinkedPostIDs($post, false);
                }else{
                    $internal_links = Wpil_Report::getOutboundLinks($post);
                    $internal_links = $internal_links['internal'];
                }
                set_transient('wpil_outbound_post_links' . $process_key, Wpil_Toolbox::compress($internal_links), MINUTE_IN_SECONDS * 15);

            }else{
                $internal_links = Wpil_Toolbox::decompress($internal_links);
            }

        }

        $used_posts = [];
        foreach ($internal_links as $link) {
            if (!empty($link->post)) {
                $used_posts[] = ($link->post->type == 'term' ? 'cat' : '') . $link->post->id;
            }
        }

        //get all possible words from post titles
        $words_to_posts = self::getTitleWords($post, $target, $keyword, $count, $process_key);

        // if this is an inbound suggestion call
        if(!empty($target)){
            // get all selected target keywords
            $target_keywords = self::getPostKeywords($target, $process_key);
        }else{
            $post_keywords = self::getPostKeywords($post, $process_key);
            $target_keywords = self::getOutboundPostKeywords($words_to_posts, $post_keywords);

            // create a list of more specific keywords to use for overriding the outbound keyword matching limitations
            $more_specific_keywords = self::getMoreSpecificKeywords($target_keywords, $post_keywords);
//            $unique_keywords = self::getPostUniqueKeywords($target_keywords, $process_key);
        }

        // remove any keywords that contain ignored words
        foreach($target_keywords as $ind => $target_keyword){
            if( in_array($target_keyword->stemmed, $ignored_words, true) || 
                in_array($target_keyword->normalized, $ignored_words, true))
            {
                unset($target_keywords[$ind]);
            }
        }

        //get all posts with same category
        $result = self::getSameCategories($post, $process_key, $is_outbound);
        $category_posts = [];
        foreach ($result as $cat) {
            $category_posts[] = $cat->object_id;
        }

        $word_segments = array();
        if(!empty($target) && method_exists('Wpil_Stemmer', 'get_segments') && empty($_REQUEST['keywords'])){
            // todo consider caching this too since it'll have to be called multiple times
            $word_segments = array();
            if(!empty($target_keywords)){
                foreach($target_keywords as $dat){
                    $dat_words = explode(' ', $dat->stemmed);
                    $word_segments = array_merge($word_segments, $dat_words);
                }
            }

            $word_segments = array_merge($word_segments, array_keys($words_to_posts));
            $word_segments = Wpil_Stemmer::get_segments($word_segments);
        }

        // if this is an inbound link scan
        if(!empty($target)){
            $phrases = self::getPhrases($post->getContent(), false, $word_segments);
        }else{
            // if this is an outbound link scan, get the phrases formatted for outbound use
            $phrases = self::getOutboundPhrases($post, $process_key);
        }

        // get if the user wants to only match on target keywords and isn't searching
        $only_match_target_keywords = (!empty(get_option('wpil_only_match_target_keywords', false)) && empty($_REQUEST['keywords']));

        //divide text to phrases
        foreach ($phrases as $key_phrase => $phrase) {
            if(empty($target)){
                // if this is an outbound link search, remove all phrases that contain the target keywords.
                $has_keyword = self::checkSentenceForKeywords($phrase->text, $post_keywords, array()/*, $unique_keywords*/, $more_specific_keywords);
                if($has_keyword){
                    unset($phrases[$key_phrase]);
                    continue;
                }
            }

            //get array of unique sentence words cleared from ignore phrases
            if (!empty($_REQUEST['keywords'])) {
                $sentence = trim(preg_replace('/\s+/', ' ', $phrase->text));
                $words_uniq = array_map(function($word){ return Wpil_Stemmer::Stem($word); }, array_unique(Wpil_Word::getWords($sentence)));
            } else {
                // if this is an inbound scan
                if(!empty($target)){
                    $text = Wpil_Word::strtolower(Wpil_Word::removeEndings($phrase->text, ['.','!','?','\'',':','"']));
                    $words_uniq = array_unique(Wpil_Word::cleanFromIgnorePhrases($text));
                }else{
                    // if this is an outbound scan
                    $words_uniq = $phrase->words_uniq;
                }
            }

            $suggestions = [];
            foreach ($words_uniq as $word) { // takes barely any time
                // if we're only matching with target keywords, exit the loop
                if($only_match_target_keywords){
                    break;
                }

                // copy the current state of the word and restemm it without accents to catch accent trouble
                $orig = $word;
                $restemm = Wpil_Stemmer::Stem(Wpil_Word::remove_accents($word), true);

                if (empty($_REQUEST['keywords']) && (in_array($word, $stemmed_ignore_words) || in_array($restemm, $stemmed_ignore_words))) {
                    continue;
                }

                //skip word if no one post title has this word
                if (empty($words_to_posts[$word])) {
                    // if the de-accented & restemmed version of the word is present
                    if(isset($words_to_posts[$restemm])){
                        // set the word to it
                        $word = $restemm;
                    }else{
                        continue;
                    }
                }

                //create array with all possible posts for current word
                foreach ($words_to_posts[$word] as $p) {
                    if (is_null($target)) {
                        $key = $p->type == 'term' ? 'cat' . $p->id : $p->id;
                    } else {
                        $key = $post->type == 'term' ? 'cat' . $post->id : $post->id;
                    }

                    if (in_array($key, $used_posts) || (isset($suggestions[$key]) && isset($suggestions[$key]['words']) && in_array($orig, $suggestions[$key]['words']))) {
                        continue;
                    }

                    //create new suggestion
                    if (empty($suggestions[$key])) {
                        //check if post have same category with main post
                        $same_category = false;
                        if ($p->type == 'post' && in_array($p->id, $category_posts)) {
                            $same_category = true;
                        }

                        if (!is_null($target)) {
                            $suggestion_post = $post;
                        } else {
                            $suggestion_post = $p;
                        }

                        // unset the suggestions post content if it's set
                        if(isset($suggestion_post->content)){
                            $suggestion_post->content = null;
                        }

                        $suggestions[$key] = [
                            'post' => $suggestion_post,
                            'post_score' => $same_category ? .5 : 0,
                            'words' => []
                        ];
                    }

                    //add new word to suggestion
                    if (!in_array($orig, $suggestions[$key]['words'])) {
                        $suggestions[$key]['words'][] = $orig;
                        $suggestions[$key]['post_score'] += 1;
                    }
                }
            }

            // if there are target keywords
            if(!empty($target_keywords)){

                $stemmed_phrase = Wpil_Word::getStemmedSentence($phrase->text);
                $normalized = Wpil_Word::getStemmedSentence(Wpil_Word::remove_accents($stemmed_phrase), true);

                foreach($target_keywords as $target_keyword){
                    // skip the keyword if it's only 2 chars long
                    if(3 > strlen($target_keyword->keywords)){
                        continue;
                    }

                    // if the keyword is in the phrase
                    $in_unstemmed = false !== strpos(Wpil_Word::strtolower($phrase->text), Wpil_Word::strtolower($target_keyword->keywords)); // the keyword exists in an unstemmed version
                    $in_stemmed = false !== strpos($stemmed_phrase, $target_keyword->stemmed); // if the keyword can be found after stemming it, and the stemmed version isn't an ignored word
                    $in_normalized = false;

                    // check for accent-normalized matches
                    if(!$in_unstemmed && !$in_stemmed){
                        $in_normalized = !empty($normalized) && false !== strpos($normalized, $target_keyword->normalized); // if the keyword can be found after normalizing AND stemming it, and this version isn't an ignored word
                    }

                    if($in_unstemmed || $in_stemmed || ($in_normalized && !empty($normalized))) 
                    {
                        // do an additional check to make sure the stemmed keyword isn't a partial match of a different word. //EX: TK of "shoe" would be found in "shoestring" and would trip the above check
                        if($in_unstemmed && !$in_stemmed){
                            $pos = Wpil_Word::mb_strpos(Wpil_Word::strtolower($phrase->text), Wpil_Word::strtolower($target_keyword->keywords));
                            if(Wpil_Keyword::isPartOfWord(Wpil_Word::strtolower($phrase->text), Wpil_Word::strtolower($target_keyword->keywords), $pos)){ // TODO: Create a general isPartOfWord function in a toolbox class so we don't have to go reaching across classes for methods
                                continue;
                            }
                        }

                        if($in_stemmed){
                            $pos = Wpil_Word::mb_strpos($stemmed_phrase, $target_keyword->stemmed);
                            if(Wpil_Keyword::isPartOfWord($stemmed_phrase, $target_keyword->stemmed, $pos)){ // TODO: Create a general isPartOfWord function in a toolbox class so we don't have to go reaching across classes for methods
                                continue;
                            }
                        }

                        if($in_normalized){
                            $pos = Wpil_Word::mb_strpos($normalized, $target_keyword->normalized);
                            if(Wpil_Keyword::isPartOfWord($normalized, $target_keyword->normalized, $pos)){ // TODO: Create a general isPartOfWord function in a toolbox class so we don't have to go reaching across classes for methods
                                continue;
                            }
                        }

                        // if we're doing outbound suggestion matching
                        if(empty($target)){
                            $key = $target_keyword->post_type == 'term' ? 'cat' . $target_keyword->post_id : $target_keyword->post_id;
                            $link_post = new Wpil_Model_Post($target_keyword->post_id, $target_keyword->post_type);
                        }else{
                            $key = $post->type == 'term' ? 'cat' . $post->id : $post->id;
                            $link_post = $post;
                        }
                        if ( in_array($key, $used_posts) ) {
                            break;
                        }

                        // if this keyword has already been used
                        if((!empty($suggestions[$key]['used_keywords']) && !empty($target_keyword->keyword_index) && isset($suggestions[$key]['used_keywords'][$target_keyword->keyword_index]))){
                            // skip to the next one
                            continue;
                        }

                        //create new suggestion
                        if (!isset($suggestions[$key])) {

                            //check if post have same category with main post
                            $same_category = false;
                            if ($link_post->type == 'post' && in_array($link_post->id, $category_posts)) {
                                $same_category = true;
                            }

                            // unset the suggestions post content if it's set
                            if(isset($link_post->content)){
                                $link_post->content = null;
                            }

                            $suggestions[$key] = [
                                'post' => $link_post,
                                'post_score' => $same_category ? .5 : 0,
                                'words' => [],
                                'matched_target_keywords' => array()
                            ];
                        }

                        // add the target keyword to the suggestion's data
                        $suggestions[$key]['matched_target_keywords'][] = $target_keyword;

                        // if the sentence had to be normalized to match the keywords
                        if($in_normalized){
                            // pull the "keywords" from the original string so that we can manufacture an accent-correct link

                            // to do this, we'll slice the string on where the keyword is found
                            $before_words = mb_substr($normalized, 0, Wpil_Word::mb_strpos($normalized, $target_keyword->normalized));
                            // count the number of words
                            $before_words = count(explode(' ', $before_words));
                            // if it's more than 0, reduce by 1 to accomodate slice()
                            $before_words = ($before_words > 0 ) ? $before_words - 1: $before_words;
                            // now we'll blow up the starting string
                            $bits = explode(' ', $stemmed_phrase);
                            // remove the starting words
                            $bits = array_slice($bits, $before_words);
                            // finally, remove the words that go into the target keyword
                            $key_words = array_slice($bits, 0, $target_keyword->word_count);
                        }else{
                            $key_words = explode(' ', $target_keyword->stemmed);
                        }

                        foreach($key_words as $word){
                            //add new word to suggestion if it hasn't already been listed and the user isn't searching for keywords
                            if (!in_array($word, $suggestions[$key]['words']) && empty($_REQUEST['keywords'])) {
                                if(!self::isAsianText()){
                                    $suggestions[$key]['words'][] = $word;
                                }else{
                                    $suggestions[$key]['words'][] = mb_str_split($word);
                                }

                                $suggestions[$key]['post_score'] += 30; // add more points since this is for a target keyword
                                $suggestions[$key]['passed_target_keywords'] = true;
                            }elseif(!isset($suggestions[$key]['passed_target_keywords'])){
                                $suggestions[$key]['post_score'] += 20; // add more points since this is for a target keyword
                                $suggestions[$key]['passed_target_keywords'] = true;
                            }

                            if(isset($target_keyword->keyword_index) && !empty($target_keyword->keyword_index)){
                                $suggestions[$key]['used_keywords'][$target_keyword->keyword_index] = true;
                            }
                            
                        }
                    }
                }
            }

            /** Performs a word-by-word keyword match. So if a "Keyword" contains text like "best business site", it will check for matches to "best", "business", and "site". Rather than seeing if the text contains "best business site" specifically. **//*
            // create the target keyword suggestions
            foreach ($uniq_word_list as $word) {
                if(!isset($target_keywords[$word])){
                    continue;
                }

                foreach($target_keywords[$word] as $key_id => $kwrd){
                    $key = $post->type == 'term' ? 'cat' . $post->id : $post->id;
                    if (in_array($key, $used_posts)) {
                        continue;
                    }

                    //create new suggestion
                    if (!isset($suggestions[$key])) {

                        //check if post have same category with main post
                        $same_category = false;
                        if ($post->type == 'post' && in_array($post->id, $category_posts)) {
                            $same_category = true;
                        }

                        // unset the suggestions post content if it's set
                        if(isset($post->content)){
                            $post->content = null;
                        }

                        $suggestions[$key] = [
                            'post' => $post,
                            'post_score' => $same_category ? .5 : 0,
                            'words' => []
                        ];
                    }

                    //add new word to suggestion
                    if (!in_array($word, $suggestions[$key]['words'])) {
                        $suggestions[$key]['words'][] = $word;
                        $suggestions[$key]['post_score'] += 3; // add more points since this is for a target keyword
                        $suggestions[$key]['passed_target_keywords'] = true;
                    }elseif(!isset($suggestions[$key]['passed_target_keywords'])){
                        $suggestions[$key]['post_score'] += 2; // add more points since this is for a target keyword
                        $suggestions[$key]['passed_target_keywords'] = true;
                    }

                    // award more points if the suggestion has an exact match with the keywords
                    if($kwrd->word_count > 1 && false !== strpos(Wpil_Word::getStemmedSentence($phrase->text), $kwrd->stemmed)){
                        $suggestions[$key]['post_score'] += 1000;
                    }
                }
            }*/

            //check if suggestion has at least 2 words & is less than 10 words long, and then calculate count of close words
            foreach ($suggestions as $key => $suggestion) {
                if ((!empty($_REQUEST['keywords']) && count($suggestion['words']) != count(array_unique(explode(' ', $keyword))))
                    || (empty($_REQUEST['keywords']) && count($suggestion['words']) < 2 && !isset($suggestion['passed_target_keywords']))
                ) {
                    unset ($suggestions[$key]);
                    continue;
                }

                // get the suggestion's current length
                $suggestion['length'] = self::getSuggestionAnchorLength($phrase, $suggestion['words']);

                // if the suggestion isn't long enough
                if($suggestion['length'] < self::get_min_anchor_length()){
                    // remove it and continue to the next
                    unset ($suggestions[$key]);
                    continue;
                }

                // if the suggested anchor is longer than 10 words
                if(self::get_max_anchor_length() < $suggestion['length']){
                    // see if we can trim up the suggestion to get under the limit
                    $trimmed_suggestion = self::adjustTooLongSuggestion($phrase, $suggestion);
                    // if we can
                    if( self::get_max_anchor_length() >= $trimmed_suggestion['length'] && 
                        count($suggestion['words']) >= 2)
                    {
                        // update the suggestion
                        $suggestion = $trimmed_suggestion;
                    }else{
                        // if we can't, remove the suggestion
                        unset($suggestions[$key]);
                        continue;
                    }
                }

                sort($suggestion['words']);

                if($use_slug){
                    $title_words = $suggestion['post']->getSlugWords();
                }else{
                    $title_words = $suggestion['post']->getTitle();
                }

                $close_words = self::getMaxCloseWords($suggestion['words'], $title_words);

                if ($close_words > 1) {
                    $suggestion['post_score'] += $close_words;
                }

                //calculate anchor score
                $close_words = self::getMaxCloseWords($suggestion['words'], $phrase->text);
                $suggestion['anchor_score'] = count($suggestion['words']);
                if ($close_words > 1) {
                    $suggestion['anchor_score'] += $close_words * 2;
                }
                $suggestion['total_score'] = $suggestion['anchor_score'] + $suggestion['post_score'];
                $search_target = ($is_outbound) ? $post: $target;
                $suggestion['ai_relatedness_calculation'] = Wpil_AI::get_post_relationship_score($search_target, $suggestion['post']);

                $phrase->suggestions[$key] = new Wpil_Model_Suggestion($suggestion);
            }

            if (!count($phrase->suggestions)) {
                unset($phrases[$key_phrase]);
                continue;
            }

            usort($phrase->suggestions, function ($a, $b) {
                if ($a->total_score == $b->total_score) {
                    return 0;
                }
                return ($a->total_score > $b->total_score) ? -1 : 1;
            });
        }

        // if we're processing outbound suggestions
        if(empty($target)){
            // remove all top-level suggestion post duplicates and leave the best suggestion as top
            $phrases = self::remove_top_level_suggested_post_repeats($phrases);
        }

        //remove same suggestions on top level
        foreach ($phrases as $key => $phrase) {
            if(empty($phrase->suggestions)){
                unset($phrases[$key]);
                continue;
            }
            $post_key = ($phrase->suggestions[0]->post->type=='term'?'cat':'') . $phrase->suggestions[0]->post->id;
            if (!empty($target) || !in_array($post_key, $used_posts)) {
                $used_posts[] = $post_key;
            } else {
                if (!empty(self::$undeletable)) {
                    $phrase->suggestions[0]->opacity = .5;
                } else {
                    unset($phrase->suggestions[0]);
                }

            }

            if (!count($phrase->suggestions)) {
                unset($phrases[$key]);
            } else {
                if (!empty(self::$undeletable)) {
                    $i = 1;
                    foreach ($phrase->suggestions as $suggestion) {
                        $i++;
                        if ($i > 10) {
                            $suggestion->opacity = .5;
                        }
                    }
                } else {
                    if (!$all) {
                        $phrase->suggestions = array_slice($phrase->suggestions, 0, 10);
                    }else{
                        $phrase->suggestions = array_values($phrase->suggestions);
                    }
                }
            }
        }

        $phrases = self::deleteWeakPhrases($phrases);

        return $phrases;
    }

    /**
     * Get link suggestions for the post.
     * Now with "AI" searching!
     *
     * @param $post_id
     * @param $ui
     * @param null $target_post_id
     * @return array|mixed
     */
    public static function getAIPostSuggestions($post, $target = null, $all = false, $keyword = null, &$count = null, $process_key = 0)
    {
        $ignored_words = Wpil_Settings::getIgnoreWords();
        $stemmed_ignore_words = Wpil_Settings::getStemmedIgnoreWords();
        $is_outbound = (empty($target)) ? true: false;
        $use_slug = Wpil_Settings::use_post_slug_for_suggestions();

        if($target){
            $internal_links = Wpil_Post::getLinkedPostIDs($target, false);
        }else{
            $internal_links = get_transient('wpil_outbound_post_links' . $process_key);
            if(empty($internal_links)){
                // if we're preventing twoway linking
                if(get_option('wpil_prevent_two_way_linking', false)){
                    // get the inbound && the outbound internal links
                    $internal_links = Wpil_Post::getLinkedPostIDs($post, false);
                }else{
                    $internal_links = Wpil_Report::getOutboundLinks($post);
                    $internal_links = $internal_links['internal'];
                }
                set_transient('wpil_outbound_post_links' . $process_key, Wpil_Toolbox::compress($internal_links), MINUTE_IN_SECONDS * 15);
            }else{
                $internal_links = Wpil_Toolbox::decompress($internal_links);
            }
        }

        $used_posts = [];
        foreach($internal_links as $link){
            if(!empty($link->post)){
                $used_posts[] = $link->post->type . '_' . $link->post->id;
            }
        }

        // get the post's embedding data
        $post_data = (array)Wpil_AI::get_embedding_calc_phrases($post, true, true);
        if(empty($post_data)){ //todo: Maybe fall back to the old method with a note saying something like we weren't abnle to get the AI suggestion data or something.
            return 'no_data';
        }

        foreach($post_data['calculation'] as $sentence => &$relations){
            foreach($relations as $pid => $score){
                if($score < self::$ai_suggestion_threashold || in_array($pid, $used_posts)){
                    unset($relations[$pid]);
                }
            }

            if(empty($relations)){
                unset($post_data['calculation'][$sentence]);
            }else{
                // sort the relations 
                arsort($relations);
                // and trim out excessive post suggestions
                $county = 0;
                foreach($relations as $pid => $score){
                    if($county > 10){ // todo: ccalibrate
                        unset($relations[$pid]);
                    }
                    $county++;
                }
            }
        }

        if(empty($post_data['calculation'])){
            return 'no_posts';
        }

        // see if we're on a cooldown for the anchor API
        $doing_cooldown = get_transient('wpil_chat_gpt_api_waiter');

        // if we're using AI to id sentences
        $using_ai_anchors = false;
        if(
            !Wpil_Settings::get_disable_ai_anchor_building() && // if we're building anchors with AI
            (empty($doing_cooldown) || $doing_cooldown < time())// and we're not currently on an API cooldown
        ){
            $new_phrases = array();
            foreach($post_data['calculation'] as $sentence => $relations){
                foreach($relations as $pid => $score){
                    if(!isset($new_phrases[$sentence])){
                        $new_phrases[$sentence] = array();
                    }
                    $bits = explode('_', $pid);
                    $posty = new Wpil_Model_Post($bits[1], $bits[0]);
                    $new_phrases[$sentence][] = array($pid, $posty->getTitle(true));
                }
            }

            // if we still have processing to do
            $processing = Wpil_AI::assess_post_sentence_anchors($new_phrases, $post->get_pid());

            // if we've hit the API limit
            if(Wpil_AI::is_rate_limited()){
                // set a flag so that we can tell that we need to make up the difference with the keyword selection method
                set_transient('wpil_chat_gpt_api_waiter', (time() + (int)(10 * MINUTE_IN_SECONDS)));
            }

            if(empty($processing) && !is_null($processing) && $count < 15){
                return 'hold_position';
            }
            $using_ai_anchors = true;
        }

        // if we've completed processsing
        // get the AI suggestions
        $ai_suggested_sentences = Wpil_AI::get_ai_post_suggestion_sentences($post);

        if(empty($ai_suggested_sentences)){
            $using_ai_anchors = false;
        }else{
            $using_ai_anchors = true;
        }

        $processed_sentences = Wpil_AI::get_processed_anchor_sentences($post, true, true);

        //get all possible words from post titles
        $words_to_posts = self::get_viable_ai_posts($post, $target, $keyword, $post_data['calculation'], $count, $process_key);

        // if this is an inbound suggestion call
        if(!empty($target)){
            // get all selected target keywords
            $target_keywords = self::getPostKeywords($target, $process_key);
        }else{
            $post_keywords = self::getPostKeywords($post, $process_key);
            $target_keywords = self::getOutboundPostKeywords($words_to_posts, $post_keywords);
            // create a list of more specific keywords to use for overriding the outbound keyword matching limitations
            $more_specific_keywords = self::getMoreSpecificKeywords($target_keywords, $post_keywords);
        }

        // remove any keywords that contain ignored words
        foreach($target_keywords as $ind => $target_keyword){
            if( in_array($target_keyword->stemmed, $ignored_words, true) || 
                in_array($target_keyword->normalized, $ignored_words, true))
            {
                unset($target_keywords[$ind]);
            }
        }

        //get all posts with same category
        $result = self::getSameCategories($post, $process_key, $is_outbound);
        $category_posts = [];
        foreach ($result as $cat) {
            $category_posts[] = $cat->object_id;
        }

        // if this is an inbound link scan
        if(!empty($target)){
            $phrases = self::getPhrases($post->getContent(), false);
        }else{
            // if this is an outbound link scan, get the phrases formatted for outbound use
            $phrases = self::getOutboundPhrases($post, $process_key, ('sentence_text' ===self::get_phrase_text_prop()));
        }

        // get if the user wants to only match on target keywords and isn't searching
        $only_match_target_keywords = (!empty(get_option('wpil_only_match_target_keywords', false)) && empty($_REQUEST['keywords']));

        //divide text to phrases
        foreach ($phrases as $key_phrase => $phrase) {
            // get the sentence's id
            $phrase_id = md5(self::get_ai_phrase_text($phrase));

            $suggestions = [];
            if($using_ai_anchors && isset($processed_sentences[$phrase_id])){

                // if the sentence isn't in our list, skip to the next one
                if(!isset($ai_suggested_sentences[$phrase_id])){
                    unset($phrases[$key_phrase]);
                    continue;
                }
    
                foreach($ai_suggested_sentences[$phrase_id] as $suggestion_data){
                    $sentence = trim(preg_replace('/\s+/', ' ', $suggestion_data->suggestion_words));

                    // if the ai sentence is inside the actual sentencce
                    if(false !== strpos($phrase->text, $sentence)){ // TOOD: Account for "only match target keywords" settinf
                        // add the sentencce words and create the phrase here
                        $target_post = new Wpil_Model_Post($suggestion_data->target_id, $suggestion_data->target_type);

                        if (is_null($target)) {
                            $key = $target_post->type == 'term' ? 'cat' . $target_post->id : $target_post->id;
                        } else {
                            $key = $post->type == 'term' ? 'cat' . $post->id : $post->id;
                        }

                        if (in_array($key, $used_posts) || (isset($suggestions[$key]))) {
                            //continue;
                        }

                        //check if post have same category with main post
                        $same_category = false;
                        if ($suggestion_data->target_type == 'post' && in_array($suggestion_data->target_id, $category_posts)) {
                            $same_category = true;
                        }

                        if (!is_null($target)) {
                            $suggestion_post = $post;
                        } else {
                            $suggestion_post = $target_post;
                        }
    
                        // unset the suggestions post content if it's set
                        if(isset($suggestion_post->content)){
                            $suggestion_post->content = null;
                        }
    
                        $words = Wpil_Word::getWords($sentence);
                        $suggestions[$key] = [
                            'post' => $suggestion_post,
                            'post_score' => $same_category ? .5 : 0,
                            'words' => $words,
                            'post_score' => count($words)
                        ];
    
                        continue;
                    }
    
                    $words_uniq = array_map(function($word){ return Wpil_Stemmer::Stem($word); }, array_unique(Wpil_Word::getWords($sentence)));
                    foreach ($words_uniq as $word) { // takes barely any time
                        // if we're only matching with target keywords, exit the loop
                        if($only_match_target_keywords){
                            break;
                        }
    
                        // copy the current state of the word and restemm it without accents to catch accent trouble
                        $orig = $word;
                        $restemm = Wpil_Stemmer::Stem(Wpil_Word::remove_accents($word), true);
    
                        if (empty($_REQUEST['keywords']) && (in_array($word, $stemmed_ignore_words) || in_array($restemm, $stemmed_ignore_words))) {
                            continue;
                        }
    
                        $target_post = new Wpil_Model_Post($suggestion_data->target_id, $suggestion_data->target_type);
    
                        if (is_null($target)) {
                            $key = $target_post->type == 'term' ? 'cat' . $target_post->id : $target_post->id;
                        } else {
                            $key = $post->type == 'term' ? 'cat' . $post->id : $post->id;
                        }
    
                        if (in_array($key, $used_posts) || (isset($suggestions[$key]) && isset($suggestions[$key]['words']) && in_array($orig, $suggestions[$key]['words']))) {
                            continue;
                        }
    
                        //create new suggestion
                        if (empty($suggestions[$key])) {
                            //check if post have same category with main post
                            $same_category = false;
                            if ($suggestion_data->target_type == 'post' && in_array($suggestion_data->target_id, $category_posts)) {
                                $same_category = true;
                            }
    
                            if (!is_null($target)) {
                                $suggestion_post = $post;
                            } else {
                                $suggestion_post = $target_post;
                            }
    
                            // unset the suggestions post content if it's set
                            if(isset($suggestion_post->content)){
                                $suggestion_post->content = null;
                            }
    
                            $suggestions[$key] = [
                                'post' => $suggestion_post,
                                'post_score' => $same_category ? .5 : 0,
                                'words' => []
                            ];
                        }
    
                        //add new word to suggestion
                        if (!in_array($orig, $suggestions[$key]['words'])) {
                            $suggestions[$key]['words'][] = $orig;
                            $suggestions[$key]['post_score'] += 1;
                        }
                    }
                }
            }else{

                if(empty($target)){
                    // if this is an outbound link search, remove all phrases that contain the target keywords.
                    $has_keyword = self::checkSentenceForKeywords($phrase->text, $post_keywords, array()/*, $unique_keywords*/, $more_specific_keywords);
                    if($has_keyword){
                        unset($phrases[$key_phrase]);
                        continue;
                    }
    
                    if( !isset($post_data['calculation'][$phrase->sentence_text]) || 
                        $post_data['calculation'][$phrase->sentence_text] < self::$ai_suggestion_threashold)
                    {
                        unset($phrases[$key_phrase]);
                        continue;
                    }
                }

                //get array of unique sentence words cleared from ignore phrases
                if (!empty($_REQUEST['keywords'])) { // TODO: Make work?
                    $sentence = trim(preg_replace('/\s+/', ' ', $phrase->text));
                    $words_uniq = array_map(function($word){ return Wpil_Stemmer::Stem($word); }, array_unique(Wpil_Word::getWords($sentence)));
                } else {
                    // if this is an inbound scan
                    if(!empty($target)){
                        $text = Wpil_Word::strtolower(Wpil_Word::removeEndings($phrase->text, ['.','!','?','\'',':','"']));
                        $words_uniq = array_unique(Wpil_Word::cleanFromIgnorePhrases($text));
                    }else{
                        // if this is an outbound scan
                        $words_uniq = $phrase->words_uniq;
                    }
                }

                foreach ($words_uniq as $word) { // takes barely any time
                    // if we're only matching with target keywords, exit the loop
                    if($only_match_target_keywords){
                        break;
                    }
    
                    // copy the current state of the word and restemm it without accents to catch accent trouble
                    $orig = $word;
                    $restemm = Wpil_Stemmer::Stem(Wpil_Word::remove_accents($word), true);
    
                    if (empty($_REQUEST['keywords']) && (in_array($word, $stemmed_ignore_words) || in_array($restemm, $stemmed_ignore_words))) {
                        continue;
                    }
    
                    //skip word if no one post title has this word
                    if (empty($words_to_posts[$word])) {
                        // if the de-accented & restemmed version of the word is present
                        if(isset($words_to_posts[$restemm])){
                            // set the word to it
                            $word = $restemm;
                        }else{
                            continue;
                        }
                    }
    
                    //create array with all possible posts for current word
                    foreach ($words_to_posts[$word] as $p) {
                        if (is_null($target)) {
                            $key = $p->type == 'term' ? 'cat' . $p->id : $p->id;
                        } else {
                            $key = $post->type == 'term' ? 'cat' . $post->id : $post->id;
                        }
    
                        if (in_array($key, $used_posts) || (isset($suggestions[$key]) && isset($suggestions[$key]['words']) && in_array($orig, $suggestions[$key]['words']))) {
                            continue;
                        }
    
                        //create new suggestion
                        if (empty($suggestions[$key])) {
                            //check if post have same category with main post
                            $same_category = false;
                            if ($p->type == 'post' && in_array($p->id, $category_posts)) {
                                $same_category = true;
                            }
    
                            if (!is_null($target)) {
                                $suggestion_post = $post;
                            } else {
                                $suggestion_post = $p;
                            }
    
                            // unset the suggestions post content if it's set
                            if(isset($suggestion_post->content)){
                                $suggestion_post->content = null;
                            }
    
                            $suggestions[$key] = [
                                'post' => $suggestion_post,
                                'post_score' => $same_category ? .5 : 0,
                                'words' => []
                            ];
                        }
    
                        //add new word to suggestion
                        if (!in_array($orig, $suggestions[$key]['words'])) {
                            $suggestions[$key]['words'][] = $orig;
                            $suggestions[$key]['post_score'] += 1;
                        }
                    }
                }
            }


            // if there are target keywords
            if(!empty($target_keywords)){

                $stemmed_phrase = Wpil_Word::getStemmedSentence($phrase->text);
                $normalized = Wpil_Word::getStemmedSentence(Wpil_Word::remove_accents($stemmed_phrase), true);

                foreach($target_keywords as $target_keyword){
                    // skip the keyword if it's only 2 chars long
                    if(3 > strlen($target_keyword->keywords)){
                        continue;
                    }

                    // if the keyword is in the phrase
                    $in_unstemmed = false !== strpos(Wpil_Word::strtolower($phrase->text), Wpil_Word::strtolower($target_keyword->keywords)); // the keyword exists in an unstemmed version
                    $in_stemmed = false !== strpos($stemmed_phrase, $target_keyword->stemmed); // if the keyword can be found after stemming it, and the stemmed version isn't an ignored word
                    $in_normalized = false;

                    // check for accent-normalized matches
                    if(!$in_unstemmed && !$in_stemmed){
                        $in_normalized = !empty($normalized) && false !== strpos($normalized, $target_keyword->normalized); // if the keyword can be found after normalizing AND stemming it, and this version isn't an ignored word
                    }

                    if($in_unstemmed || $in_stemmed || ($in_normalized && !empty($normalized))) 
                    {
                        // do an additional check to make sure the stemmed keyword isn't a partial match of a different word. //EX: TK of "shoe" would be found in "shoestring" and would trip the above check
                        if($in_unstemmed && !$in_stemmed){
                            $pos = Wpil_Word::mb_strpos(Wpil_Word::strtolower($phrase->text), Wpil_Word::strtolower($target_keyword->keywords));
                            if(Wpil_Keyword::isPartOfWord(Wpil_Word::strtolower($phrase->text), Wpil_Word::strtolower($target_keyword->keywords), $pos)){ // TODO: Create a general isPartOfWord function in a toolbox class so we don't have to go reaching across classes for methods
                                continue;
                            }
                        }

                        if($in_stemmed){
                            $pos = Wpil_Word::mb_strpos($stemmed_phrase, $target_keyword->stemmed);
                            if(Wpil_Keyword::isPartOfWord($stemmed_phrase, $target_keyword->stemmed, $pos)){ // TODO: Create a general isPartOfWord function in a toolbox class so we don't have to go reaching across classes for methods
                                continue;
                            }
                        }

                        if($in_normalized){
                            $pos = Wpil_Word::mb_strpos($normalized, $target_keyword->normalized);
                            if(Wpil_Keyword::isPartOfWord($normalized, $target_keyword->normalized, $pos)){ // TODO: Create a general isPartOfWord function in a toolbox class so we don't have to go reaching across classes for methods
                                continue;
                            }
                        }

                        // if we're doing outbound suggestion matching
                        if(empty($target)){
                            $key = $target_keyword->post_type == 'term' ? 'cat' . $target_keyword->post_id : $target_keyword->post_id;
                            $link_post = new Wpil_Model_Post($target_keyword->post_id, $target_keyword->post_type);
                        }else{
                            $key = $post->type == 'term' ? 'cat' . $post->id : $post->id;
                            $link_post = $post;
                        }
                        if ( in_array($key, $used_posts) ) {
                            break;
                        }

                        // if this keyword has already been used
                        if((!empty($suggestions[$key]['used_keywords']) && !empty($target_keyword->keyword_index) && isset($suggestions[$key]['used_keywords'][$target_keyword->keyword_index]))){
                            // skip to the next one
                            continue;
                        }

                        //create new suggestion
                        if (!isset($suggestions[$key])) {

                            //check if post have same category with main post
                            $same_category = false;
                            if ($link_post->type == 'post' && in_array($link_post->id, $category_posts)) {
                                $same_category = true;
                            }

                            // unset the suggestions post content if it's set
                            if(isset($link_post->content)){
                                $link_post->content = null;
                            }

                            $suggestions[$key] = [
                                'post' => $link_post,
                                'post_score' => $same_category ? .5 : 0,
                                'words' => [],
                                'matched_target_keywords' => array()
                            ];
                        }

                        // add the target keyword to the suggestion's data
                        $suggestions[$key]['matched_target_keywords'][] = $target_keyword;

                        // if the sentence had to be normalized to match the keywords
                        if($in_normalized){
                            // pull the "keywords" from the original string so that we can manufacture an accent-correct link

                            // to do this, we'll slice the string on where the keyword is found
                            $before_words = mb_substr($normalized, 0, Wpil_Word::mb_strpos($normalized, $target_keyword->normalized));
                            // count the number of words
                            $before_words = count(explode(' ', $before_words));
                            // if it's more than 0, reduce by 1 to accomodate slice()
                            $before_words = ($before_words > 0 ) ? $before_words - 1: $before_words;
                            // now we'll blow up the starting string
                            $bits = explode(' ', $stemmed_phrase);
                            // remove the starting words
                            $bits = array_slice($bits, $before_words);
                            // finally, remove the words that go into the target keyword
                            $key_words = array_slice($bits, 0, $target_keyword->word_count);
                        }else{
                            $key_words = explode(' ', $target_keyword->stemmed);
                        }

                        foreach($key_words as $word){
                            //add new word to suggestion if it hasn't already been listed and the user isn't searching for keywords
                            if (!in_array($word, $suggestions[$key]['words']) && empty($_REQUEST['keywords'])) {
                                if(!self::isAsianText()){
                                    $suggestions[$key]['words'][] = $word;
                                }else{
                                    $suggestions[$key]['words'][] = mb_str_split($word);
                                }

                                $suggestions[$key]['post_score'] += 30; // add more points since this is for a target keyword
                                $suggestions[$key]['passed_target_keywords'] = true;
                            }elseif(!isset($suggestions[$key]['passed_target_keywords'])){
                                $suggestions[$key]['post_score'] += 20; // add more points since this is for a target keyword
                                $suggestions[$key]['passed_target_keywords'] = true;
                            }

                            if(isset($target_keyword->keyword_index) && !empty($target_keyword->keyword_index)){
                                $suggestions[$key]['used_keywords'][$target_keyword->keyword_index] = true;
                            }
                            
                        }
                    }
                }
            }

            //check if suggestion has at least 2 words & is less than 10 words long, and then calculate count of close words
            foreach ($suggestions as $key => $suggestion) {
                if ((!empty($_REQUEST['keywords']) && count($suggestion['words']) != count(array_unique(explode(' ', $keyword))))
                    || (empty($_REQUEST['keywords']) && count($suggestion['words']) < 2 && !isset($suggestion['passed_target_keywords']))
                ) {
                    unset ($suggestions[$key]);
                    continue;
                }

                $search_target = ($is_outbound) ? $suggestion['post']: $post;
                $pid = $search_target->type . '_' . $search_target->id;
                $ai_score = isset($post_data['calculation'][self::get_ai_phrase_text($phrase)], $post_data['calculation'][self::get_ai_phrase_text($phrase)][$pid]) ? $post_data['calculation'][self::get_ai_phrase_text($phrase)][$pid]: 0;

                if($ai_score < self::$ai_suggestion_threashold){
                    unset ($suggestions[$key]);
                    continue;
                }

                // get the suggestion's current length
                $suggestion['length'] = self::getSuggestionAnchorLength($phrase, $suggestion['words']);

                // if the suggestion isn't long enough
                if($suggestion['length'] < self::get_min_anchor_length()){
                    // remove it and continue to the next
                    unset ($suggestions[$key]);
                    continue;
                }

                // if the suggested anchor is longer than 10 words
                if(self::get_max_anchor_length() < $suggestion['length']){
                    // see if we can trim up the suggestion to get under the limit
                    $trimmed_suggestion = self::adjustTooLongSuggestion($phrase, $suggestion);
                    // if we can
                    if( self::get_max_anchor_length() >= $trimmed_suggestion['length'] && 
                        count($suggestion['words']) >= 2)
                    {
                        // update the suggestion
                        $suggestion = $trimmed_suggestion;
                    }else{
                        // if we can't, remove the suggestion
                        unset($suggestions[$key]);
                        continue;
                    }
                }

                sort($suggestion['words']);

                if($use_slug){
                    $title_words = $suggestion['post']->getSlugWords();
                }else{
                    $title_words = $suggestion['post']->getTitle();
                }

                $close_words = self::getMaxCloseWords($suggestion['words'], $title_words);

                if ($close_words > 1) {
                    $suggestion['post_score'] += $close_words;
                }

                //calculate anchor score
                $close_words = self::getMaxCloseWords($suggestion['words'], $phrase->text);
                $suggestion['anchor_score'] = count($suggestion['words']);
                if ($close_words > 1) {
                    $suggestion['anchor_score'] += $close_words * 2;
                }
                $suggestion['total_score'] = $suggestion['anchor_score'] + $suggestion['post_score'];

                // currently not used! TODO: use!
                $suggestion['ai_relatedness_calculation'] = $ai_score;//Wpil_AI::get_post_relationship_score($search_target, $suggestion['post']);

                $phrase->suggestions[$key] = new Wpil_Model_Suggestion($suggestion);
            }

            if (!count($phrase->suggestions)) {
                unset($phrases[$key_phrase]);
                continue;
            }

            usort($phrase->suggestions, function ($a, $b) {
                if ($a->total_score == $b->total_score) {
                    return 0;
                }
                return ($a->total_score > $b->total_score) ? -1 : 1;
            });
        }

        // if we're processing outbound suggestions
        if(empty($target) && Wpil_Settings::get_show_top_ai_suggestions()){
            // remove post suggestions that are for lower scored sentences
            self::remove_repeating_suggested_posts($phrases); // makes sure that each post is only offered once in the suggestions
        }

        // if we've done the AI suggestions, we should be able to do this in one pass
        // for that matter, the keyword-based method should be able to do it too
        if($using_ai_anchors || !$using_ai_anchors){
            // so set the count so high that we'll autocomplete the loop
            $count += 100000;
        }

        return $phrases;
    }
    
    /**
     * Removes less related posts from the suggestions so that we're left with ounly the highest scoring ones
     **/
    public static function remove_repeating_suggested_posts($phrases = array()){
        if(empty($phrases)){
            return array();
        }

        // id is: phrase_id . _ . post_type
        $post_index = array();
        foreach($phrases as $phrase_id => $dat){
            foreach($dat->suggestions as $suggestion_id => $suggestion){
                $pid = $suggestion->post->get_pid();
                if(!isset($post_index[$pid]) || $post_index[$pid]['score'] < $suggestion->ai_relatedness_calculation){
                    $post_index[$pid] = array(
                        'phrase_id' => $phrase_id,
                        'score' => $suggestion->ai_relatedness_calculation,
                        'suggestion_id' => $suggestion_id
                    );
                }
            }
        }

        if(!empty($post_index)){
            foreach($phrases as $phrase_id => $dat){
                foreach($dat->suggestions as $key => $suggestion){
                    $pid = $suggestion->post->get_pid();
                    if($post_index[$pid]['phrase_id'] != $phrase_id){
                        unset($phrases[$phrase_id]->suggestions[$key]);
                    }elseif($post_index[$pid]['phrase_id'] == $phrase_id && $post_index[$pid]['suggestion_id'] != $key){
                        unset($phrases[$phrase_id]->suggestions[$key]);
                    }
                }

                if(empty($phrases[$phrase_id]->suggestions)){
                    unset($phrases[$phrase_id]->suggestions);
                }
            }
        }

        return $phrases;
    }

    /**
     * 
     **/
    public static function get_phrase_text_prop(){
        return (1 === 1) ? 'sentence_text': 'text';
    }

    /**
     * @param object $phrase_sentences
     **/
    public static function get_ai_phrase_text($phrase_sentences = array()){
        $prop = self::get_phrase_text_prop();
        if(!empty($phrase_sentences->$prop) && isset($phrase_sentences->$prop)){
            return $phrase_sentences->$prop;
        }

        return '';
    }

    public static function load_post_word_cache($id){
        global $wpdb;
        $target_keyword_table = $wpdb->prefix . "wpil_target_keyword_data";

        $words = array();
        $bits = explode('_', $id);

        if($bits[0] === 'term'){
            $results = $wpdb->get_results($wpdb->prepare("SELECT a.name, b.keywords FROM {$wpdb->terms} a left join {$target_keyword_table} b on a.term_id = b.post_id AND b.post_type = 'term' WHERE a.ID = %d", $bits[1]));
        }else{
            $results = $wpdb->get_results($wpdb->prepare("SELECT a.post_title, b.keywords FROM {$wpdb->posts} a left join {$target_keyword_table} b on a.ID = b.post_id AND b.post_type = 'post' WHERE a.ID = %d", $bits[1]));
        }

        if(!empty($results)){
            $words['title_words'] = $results[0]->post_title;
            $words['keywords'] = array();
            foreach($results as $dat){
                if(!isset($words['keywords'][$dat->keywords])){
                    $kwords = Wpil_Word::strtolower($dat->keywords);
                    if(!isset($words['keywords'][$kwords])){
                        $words['keywords'][$kwords] = $kwords;
                    }
                }
            }

            if(!empty($words['keywords'])){
                $words['keywords'] = array_values($words['keywords']);
            }
        }

        return $words;
    }

    /**
     * Collect uniques words from all post titles
     *
     * @param $post_id
     * @param null $target
     * @return array
     */
    public static function get_viable_ai_posts($post, $target = null, $keyword = null, $ai_post_relations = array(), $count = null, $process_key = 0)
    {
        global $wpdb;
        $link_report_table = $wpdb->prefix . 'wpil_report_links';

        $ignore_words = Wpil_Settings::getIgnoreWords();
        $ignore_posts = Wpil_Settings::getIgnorePosts();
        $ignore_categories_posts = Wpil_Settings::getIgnoreCategoriesPosts();
        $ignore_numbers = get_option(WPIL_OPTION_IGNORE_NUMBERS, 1);
        $only_show_cornerstone = (get_option('wpil_link_to_yoast_cornerstone', false) && empty($target));
        $outbound_selected_posts = Wpil_Settings::getOutboundSuggestionPostIds();
        $inbound_link_limit = (int)get_option('wpil_max_inbound_links_per_post', 0);
        $post_embeddings = Wpil_AI::get_embedding_relatedness_data($post->id, $post->type, true);
        $has_api_key = Wpil_Settings::getOpenAIKey();
        $relatedness_threshold = Wpil_Settings::get_suggestion_filter('ai_relatedness_threshold');

        $posts = [];
        if (!is_null($target)) {
            $posts[] = $target;
        } else {
            $limit  = Wpil_Settings::getProcessingBatchSize();
            $post_ids = get_transient('wpil_title_word_ids_' . $process_key);
            if(empty($post_ids) && !is_array($post_ids)){
                //add all posts to array
                $exclude = self::getTitleQueryExclude($post);
                $post_types = implode("','", self::getSuggestionPostTypes());

                // get all posts in the same language if translation active
                $include = "";
                $check_language_ids = false;
                $ids = array();
                if (Wpil_Settings::translation_enabled()) {
                    $ids = Wpil_Post::getSameLanguagePosts($post->id);

                    if(!empty($ids) && count($ids) < $limit){
                        $include = " AND ID IN (" . implode(', ', $ids) . ") ";
                    }elseif(!empty($ids) && count($ids) > $limit){
                        $check_language_ids = true;
                    }else{
                        $include = " AND ID IS NULL ";
                    }
                }

                // get the age query if the user is limiting the range for linking
                $age_string = Wpil_Query::getPostDateQueryLimit();

                $blood_relative = '';
                if($post->type === 'post' && !empty(Wpil_Settings::get_suggestion_filter('same_parent'))){
                    $related_ids = Wpil_Toolbox::get_related_post_ids($post);
                    if(!empty($related_ids)){
                        $blood_relative = 'AND ID IN (' . implode(',', $related_ids) . ')';
                    }
                }

                $user_role_ignore = Wpil_Query::get_ignore_user_role_suggestions_query();

                $statuses_query = Wpil_Query::postStatuses();
                $post_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE 1=1 $exclude AND post_type IN ('{$post_types}') $statuses_query {$blood_relative} {$age_string} {$user_role_ignore}" . $include);

/*
                // if we're checking for semantically related posts
                if(!empty($has_api_key) && !empty($post_embeddings)){
                    if(!empty($post_embeddings)){
                        // get our relatedness threashold
                        foreach($post_ids as $key => $post_id){
                            $id = 'post_' . $post_id;
                            if(isset($post_embeddings[$id]) && $post_embeddings[$id] < $relatedness_threshold){
                                unset($post_ids[$key]);
                            }
                        }
                    }
                }*/

                if(!empty($post_ids) && !empty(Wpil_Settings::get_suggestion_filter('link_orphaned'))){
                    $post_ids = implode(',', $post_ids);

                    if(Wpil_Settings::use_link_table_for_data()){
                        $post_ids = $wpdb->get_col("SELECT `post_id` as 'ID' FROM {$link_report_table} WHERE `target_id` NOT IN ({$post_ids}) AND `target_type` = 'post'");
                    }else{

                        $post_ids = $wpdb->get_col("SELECT `post_id` as 'ID' FROM {$wpdb->postmeta} WHERE `post_id` IN ({$post_ids}) AND `meta_key` = 'wpil_links_inbound_internal_count' AND `meta_value` = 0");
                    }
                }elseif(!empty($post_ids) && !empty($inbound_link_limit)){
                    $post_ids = implode(',', $post_ids);
                    if(Wpil_Settings::use_link_table_for_data()){
                        $post_ids = $wpdb->get_col("SELECT `post_id` as 'ID' FROM {$link_report_table} WHERE `target_id` IN ({$post_ids}) AND `target_type` = 'post' GROUP BY target_id HAVING COUNT(post_id) < {$inbound_link_limit}");
                    }else{
                        $post_ids = $wpdb->get_col("SELECT `post_id` as 'ID' FROM {$wpdb->postmeta} WHERE `post_id` IN ({$post_ids}) AND `meta_key` = 'wpil_links_inbound_internal_count' AND `meta_value` < {$inbound_link_limit}");
                    }
                }

                if(empty($post_ids)){
                    $post_ids = array();
                }

                // if we have a lot of language ids to check
                if(!empty($post_ids) && $check_language_ids){
                    // find all the ids that are in the same language
                    $post_ids = array_intersect($post_ids, $ids);
                }

                if(!empty($post_ids) && Wpil_Settings::removeNoindexFromSuggestions()){
                    $post_ids = Wpil_Query::remove_noindex_ids($post_ids);
                }

                $ai_ids = array();
                foreach($ai_post_relations as $sentence => $relations){
                    foreach($relations as $id => $score){
                        $ai_ids[$id] = 1;
                    }
                }

                if(!empty($ai_ids) && !empty($post_ids)){
                    foreach($post_ids as $ind => $id){
                        $pid = 'post_' . $id;
                        if(!isset($ai_ids[$pid])){
                            unset($post_ids[$ind]);
                        }
                    }
                }

                // if the user wants to limit the number of posts searched for suggestions
                $max_count = Wpil_Settings::get_max_suggestion_post_count();
                if(!empty($max_count) && !empty($post_ids)){
                    // shuffle the ids so we don't consistently miss posts
                    shuffle($post_ids);
                    // obtain the number of posts that the user wants
                    $post_ids = array_slice($post_ids, 0, $max_count);
                }

                set_transient('wpil_title_word_ids_' . $process_key, $post_ids, MINUTE_IN_SECONDS * 15);
            }

            // if we're only supposed to show links to the Yoast cornerstone content
            if($only_show_cornerstone && !empty($result)){
                // get the ids from the initial query
                $ids = array();
                foreach($result as $item){
                    $ids[] = $item->ID;
                }

                // query the meta to see what posts have been set as cornerstone content
                $result = $wpdb->get_results("SELECT `post_id` AS ID FROM {$wpdb->postmeta} WHERE `post_id` IN (" . implode(', ', $ids) . ") AND `meta_key` = '_yoast_wpseo_is_cornerstone'");
            }

            // if we're limiting outbound suggestions to specfic posts
            if(empty($target) && !empty($outbound_selected_posts) && !empty($result)){
                // get all of the ids that the user wants to make suggestions to
                $ids = array();
                foreach($outbound_selected_posts as $selected_post){
                    if(false !== strpos($selected_post, 'post_')){
                        $ids[substr($selected_post, 5)] = true;
                    }
                }

                // filter out all the items that aren't in the outbound suggestion limits
                $result_items = array();
                foreach($result as $item){
                    if(isset($ids[$item->ID])){
                        $result_items[] = $item;
                    }
                }

                // update the results with the filtered ids
                $result = $result_items;
            }

            $posts = [];
            $process_ids = array_slice($post_ids, 0, $limit);

            if(!empty($process_ids)){
                $process_ids = implode("', '", $process_ids);
                $result = $wpdb->get_results("SELECT ID, post_title, post_name FROM {$wpdb->posts} WHERE ID IN ('{$process_ids}')");

                foreach ($result as $item) {
                    if (!in_array('post_' . $item->ID, $ignore_posts) && !in_array($item->ID, $ignore_categories_posts)) {
                        $post_obj = new Wpil_Model_Post($item->ID);
                        $post_obj->title = $item->post_title;
                        $post_obj->slug = $item->post_name;
                        $pid = $post_obj->type . '_' . $post_obj->id;

                        $posts[$pid] = $post_obj;
                    }
                }

                // remove this batch of post ids from the list and save the list
                $save_ids = array_slice($post_ids, $limit);
                set_transient('wpil_title_word_ids_' . $process_key, $save_ids, MINUTE_IN_SECONDS * 15);
            }

            // if terms are to be scanned, but the user is restricting suggestions by term, don't search for terms to link to. Only search for terms if:
            if (    !empty(Wpil_Settings::getTermTypes()) && // terms have been selected
                    empty(Wpil_Settings::get_suggestion_filter('same_category')) && // we're not restricting by category
                    empty(Wpil_Settings::get_suggestion_filter('same_tag')) && // we're not restricting by tag
                    empty(Wpil_Settings::get_suggestion_filter('same_parent')) && // we're not restricting to the post's family
                    empty($only_show_cornerstone)) // the user hasn't set LW to only process cornerstone content
            {
                if (is_null($count) || $count == 0) {
                    //add all categories to array
                    $exclude = "";
                    if ($post->type == 'term') {
                        $exclude = " AND t.term_id != {$post->id} ";
                    }

                    $taxonomies = Wpil_Settings::getTermTypes();
                    $result = $wpdb->get_results("SELECT t.term_id FROM {$wpdb->prefix}term_taxonomy tt LEFT JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id WHERE tt.taxonomy IN ('" . implode("', '", $taxonomies) . "') $exclude");

                    // if the user only wants to make outbound suggestions to specific categories
                    if(empty($target) && !empty($outbound_selected_posts) && !empty($result)){
                        // get all of the ids that the user wants to make suggestions to
                        $ids = array();
                        foreach($outbound_selected_posts as $selected_term){
                            if(false !== strpos($selected_term, 'term_')){
                                $ids[substr($selected_term, 5)] = true;
                            }
                        }

                        foreach($result as $key => $item){
                            if(!isset($ids[$item->term_id])){
                                unset($result[$key]);
                            }
                        }
                    }

                    // if we're checking for semantically related posts
                    /*if($has_api_key){ // todo: make into actual setting
                        if(!empty($post_embeddings)){
                            foreach($result as $key => $item){
                                $id = 'term_' . $item->term_id;
                                if(isset($post_embeddings[$id]) && $post_embeddings[$id] < $relatedness_threshold){
                                    unset($result[$key]);
                                }
                            }
                        }
                    }*/

                    $ai_ids = array();
                    foreach($ai_post_relations as $sentence => $relations){
                        foreach($relations as $id => $score){
                            $ai_ids[$id] = 1;
                        }
                    }

                    foreach ($result as $term) {
                        $tid = 'term_' . $term->term_id;
                        if (!in_array($tid, $ignore_posts) && isset($ai_ids[$tid])) {
                            $posts[$tid] = new Wpil_Model_Post($term->term_id, 'term');
                        }
                    }
                }
            }
        }

        if(!empty($target) && $words = get_transient('wpil_inbound_title_words_' . $process_key)){// TODO: Remove at update 2.3.3 if no one complains about slower inbound Internal suggestions // renabling since I think there has been a slowdown for some users. I think that I disabled the check because a user was having issues with the keyword searching not updating. I think that's been fixed now, so it should be safe to set this going again.
            return $words;
        }else{
            // get if the user is only matching with part of the post titles
            $partial_match = Wpil_Settings::matchPartialTitles();
            // get if the user wants to use the slug for suggestions instead of the title
            $use_slug = Wpil_Settings::use_post_slug_for_suggestions();

            $words = [];
            foreach ($posts as $key => $p) {
                //get unique words from post title
                if (!empty($keyword)) { 
                    $title_words = array_unique(Wpil_Word::getWords($keyword));
                } else {
                    if($use_slug){
                        $title = $p->getSlugWords();
                    }else{
                        $title = $p->getTitle();
                    }
                    if($partial_match){
                        $title = self::getPartialTitleWords($title);
                    }

                    // if these are outbound suggestions
                    if(empty($target)){
                        // get any target keywords the post has and add them to the title so we can make outbound matches based on them
                        $title .= Wpil_TargetKeyword::get_active_keyword_string($p->id, $p->type);
                    }

                    $title_words = array_map(function($w){ return trim(trim($w, '[]{}\'"()$&|'));}, array_unique(Wpil_Word::getWords($title)));
                }

                foreach ($title_words as $word) {
                    $normalized_word = Wpil_Stemmer::Stem(Wpil_Word::remove_accents(Wpil_Word::strtolower($word)), true, true);
                    $word = Wpil_Stemmer::Stem(Wpil_Word::strtolower($word));

                    //check if word is not a number and is not in the ignore words list
                    if (!empty($_REQUEST['keywords']) ||
                        (strlen($word) > 2 && !in_array($word, $ignore_words) && (!$ignore_numbers || !is_numeric(str_replace(['.', ',', '$'], '', $word))))
                    ) {
                        $words[$word][] = $p;

                        if(strlen($normalized_word) > 2 && $word !== $normalized_word){
                            $words[$normalized_word][] = $p;
                        }
                    }
                }
            }

/*
            $words = [];
            foreach($ai_post_relations as $sentence => &$data){
                foreach($data as $pid => $score){
                    $words = array();
                    if(!isset($posts[$pid])){
                        unset($data[$pid]);
                    }else{
                        //get unique words from post title
                        if (!empty($keyword)) { 
                            $title_words = array_unique(Wpil_Word::getWords($keyword));
                        } else {
                            if($use_slug){
                                $title = $posts[$pid]->getSlugWords();
                            }else{
                                $title = $posts[$pid]->getTitle();
                            }
                            if($partial_match){
                                $title = self::getPartialTitleWords($title);
                            }

                            // if these are outbound suggestions
                            if(empty($target)){
                                // get any target keywords the post has and add them to the title so we can make outbound matches based on them
                                $title .= Wpil_TargetKeyword::get_active_keyword_string($posts[$pid]->id, $posts[$pid]->type);
                            }

                            $title_words = array_map(function($w){ return trim(trim($w, '[]{}\'"()$&|'));}, array_unique(Wpil_Word::getWords($title)));
                        }

                        foreach ($title_words as $word) {
                            $normalized_word = Wpil_Stemmer::Stem(Wpil_Word::remove_accents(Wpil_Word::strtolower($word)), true, true);
                            $word = Wpil_Stemmer::Stem(Wpil_Word::strtolower($word));

                            //check if word is not a number and is not in the ignore words list
                            if (!empty($_REQUEST['keywords']) ||
                                (strlen($word) > 2 && !in_array($word, $ignore_words) && (!$ignore_numbers || !is_numeric(str_replace(['.', ',', '$'], '', $word))))
                            ) {
                                $words[$word] = 1;

                                if(strlen($normalized_word) > 2 && $word !== $normalized_word){
                                    $words[$normalized_word] = 1;
                                }
                            }
                        }
                    
                        $data[$pid] = array(
                            'ai_score' => $score,
                            'post' => $posts[$pid],
                            //'title_words' => $posts[$pid]
                            'words' => array_keys($words)
                        );
                    }
                }

                if(empty($ai_post_relations[$sentence])){
                    unset($ai_post_relations[$sentence]);
                }
            }*/
            
            // save the title words if this is an Inbound Search // TODO: Remove at update 2.3.3 if no one complains about slower inbound Internal suggestions // wish I could remember why I disabled this!
            if(!empty($target)){
                set_transient('wpil_inbound_title_words_' . $process_key, $ai_post_relations, MINUTE_IN_SECONDS * 15);
            }
        }

        return $words;
    }

    public static function getExternalSiteSuggestions($post, $all = false, $keyword = null, $count = null, $process_key = 0){
        $ignored_words = Wpil_Settings::getIgnoreWords();
        $use_slug = Wpil_Settings::use_post_slug_for_suggestions();

        $link_index = get_transient('wpil_external_post_link_index_' . $process_key);

        if(empty($link_index)){
            $external_links = Wpil_Report::getOutboundLinks($post, true);
            $link_index = array();
            if(isset($external_links['external'])){
                foreach($external_links['external'] as $link){
                    $link_index[$link->url] = true;
                }
            }
            unset($external_links);
            set_transient('wpil_external_post_link_index_' . $process_key, Wpil_Toolbox::compress($link_index), MINUTE_IN_SECONDS * 15);
        }else{
            $link_index = Wpil_Toolbox::decompress($link_index);
        }

        //get all possible words from external post titles
        $words_to_posts = self::getExternalTitleWords(false, false, $count, $link_index);

        $used_posts = array();

        $phrases = self::getOutboundPhrases($post, $process_key);

        //divide text to phrases
        foreach ($phrases as $key_phrase => $phrase) {

            $suggestions = [];
            foreach ($phrase->words_uniq as $word) {
                if (empty($_REQUEST['keywords']) && in_array($word, $ignored_words)) {
                    continue;
                }

                //skip word if no one post title has this word
                if (empty($words_to_posts[$word])) {
                    continue;
                }

                //create array with all possible posts for current word
                foreach ($words_to_posts[$word] as $p) {
                    $key = $p->type == 'term' ? 'ext_cat' . $p->id : 'ext_post' . $p->id;

                    //create new suggestion
                    if (empty($suggestions[$key])) {
                        $suggestion_post = $p;
                
                        $suggestions[$key] = [
                            'post' => $suggestion_post,
                            'post_score' => 0,
                            'words' => []
                        ];
                    }

                    //add new word to suggestion
                    if (!in_array($word, $suggestions[$key]['words'])) {
                        if(!self::isAsianText()){
                            $suggestions[$key]['words'][] = $word;
                        }else{
                            $suggestions[$key]['words'] = mb_str_split($word);
                        }

                        $suggestions[$key]['post_score'] += 1;
                    }
                }
            }

            //check if suggestion has at least 2 words & is less than 10 words long, and then calculate count of close words
            foreach ($suggestions as $key => $suggestion) {
                if ((!empty($_REQUEST['keywords']) && count($suggestion['words']) != count(array_unique(explode(' ', $keyword))))
                    || (empty($_REQUEST['keywords']) && count($suggestion['words']) < 2)
                ) {
                    unset ($suggestions[$key]);
                    continue;
                }

                // get the suggestion's current length
                $suggestion['length'] = self::getSuggestionAnchorLength($phrase, $suggestion['words']);

                // if the suggestion isn't long enough
                if($suggestion['length'] < self::get_min_anchor_length()){
                    // remove it and continue to the next
                    unset ($suggestions[$key]);
                    continue;
                }

                // if the suggested anchor is longer than 10 words
                if(self::get_max_anchor_length() < $suggestion['length']){
                    // see if we can trim up the suggestion to get under the limit
                    $trimmed_suggestion = self::adjustTooLongSuggestion($phrase, $suggestion);

                    // if we can
                    if( self::get_max_anchor_length() >= $trimmed_suggestion['length'] && 
                        count($suggestion['words']) >= 2)
                    {
                        // update the suggestion
                        $suggestion = $trimmed_suggestion;
                    }else{
                        // if we can't, remove the suggestion
                        unset($suggestions[$key]);
                        continue;
                    }
                }

                sort($suggestion['words']);

                if($use_slug){
                    $title_words = $suggestion['post']->getSlugWords();
                }else{
                    $title_words = $suggestion['post']->getTitle();
                }

                $close_words = self::getMaxCloseWords($suggestion['words'], $title_words);

                if ($close_words > 1) {
                    $suggestion['post_score'] += $close_words;
                }

                //calculate anchor score
                $close_words = self::getMaxCloseWords($suggestion['words'], $phrase->text);
                $suggestion['anchor_score'] = count($suggestion['words']);
                if ($close_words > 1) {
                    $suggestion['anchor_score'] += $close_words * 2;
                }
                $suggestion['total_score'] = $suggestion['anchor_score'] + $suggestion['post_score'];

                $phrase->suggestions[$key] = new Wpil_Model_Suggestion($suggestion);
            }

            if (!count($phrase->suggestions)) {
                unset($phrases[$key_phrase]);
                continue;
            }

            usort($phrase->suggestions, function ($a, $b) {
                if ($a->total_score == $b->total_score) {
                    return 0;
                }
                return ($a->total_score > $b->total_score) ? -1 : 1;
            });
        }

        //remove same suggestions on top level
        foreach ($phrases as $key => $phrase) {
            $post_key = ($phrase->suggestions[0]->post->type=='term'?'ext_cat':'ext_post') . $phrase->suggestions[0]->post->id;
            if (!empty($target) || !in_array($post_key, $used_posts)) {
                $used_posts[] = $post_key;
            } else {
                if (!empty(self::$undeletable)) {
                    $phrase->suggestions[0]->opacity = .5;
                } else {
                    unset($phrase->suggestions[0]);
                }

            }

            if (!count($phrase->suggestions)) {
                unset($phrases[$key]);
            } else {
                if (!empty(self::$undeletable)) {
                    $i = 1;
                    foreach ($phrase->suggestions as $suggestion) {
                        $i++;
                        if ($i > 10) {
                            $suggestion->opacity = .5;
                        }
                    }
                } else {
                    if (!$all) {
                        $phrase->suggestions = array_slice($phrase->suggestions, 0, 10);
                    }else{
                        $phrase->suggestions = array_values($phrase->suggestions);
                    }
                }
            }
        }

        $phrases = self::deleteWeakPhrases($phrases);

        return $phrases;
    }

    /**
     * Divide text to sentences
     *
     * @param $content
     * @param $with_links
     * @param $word_segments
     * @param $single_words
     * @return array
     */
    public static function getPhrases($content, $with_links = false, $word_segments = array(), $single_words = false, $ignore_text = array(), $full_sentences = false)
    {
        // get the section skip type and counts
        $section_skip_type = Wpil_Settings::getSkipSectionType();

        // replace unicode chars with their decoded forms
        $replace_unicode = array('\u003c', '\u003', '\u0022');
        $replacements = array('<', '>', '"');

        $content = str_ireplace($replace_unicode, $replacements, $content);

        // replace any base64ed image urls
        $content = preg_replace('`src="data:image\/(?:png|jpeg);base64,[\s]??[a-zA-Z0-9\/+=]+?"`', '', $content);
        $content = preg_replace('`alt="Source: data:image\/(?:png|jpeg);base64,[\s]??[a-zA-Z0-9\/+=]+?"`', '', $content);

        // decode page builder encoded sections
        $content = self::decode_page_builder_content($content);

        // remove the heading tags from the text
        $content = mb_ereg_replace('<h1(?:[^>]*)>(.*?)<\/h1>|<h2(?:[^>]*)>(.*?)<\/h2>|<h3(?:[^>]*)>(.*?)<\/h3>|<h4(?:[^>]*)>(.*?)<\/h4>|<h5(?:[^>]*)>(.*?)<\/h5>|<h6(?:[^>]*)>(.*?)<\/h6>', '', $content);

        // remove the head tag if it's present. It should only be present if processing a full page stored in the content
        if(false !== strpos($content, '<head')){
            $content = mb_ereg_replace('<head(?:[^>]*)>(.*?)<\/head>', '', $content);
        }

        // remove any title tags that might be present. These should only be present if processing a full page stored in the content
        if(false !== strpos($content, '<title')){
            $content = mb_ereg_replace('<title(?:[^>]*)>(.*?)<\/title>', '', $content);
        }

        // remove any meta tags that might be present. These should only be present if processing a full page stored in the content
        if(false !== strpos($content, '<meta')){
            $content = mb_ereg_replace('<meta(?:[^>]*)>(.*?)<\/meta>', '', $content);
        }

        // remove any link tags that might be present. These should only be present if processing a full page stored in the content
        if(false !== strpos($content, '<link')){
            $content = mb_ereg_replace('<link(?:[^>]*)>(.*?)<\/link>', '', $content);
        }

        // remove any script tags that might be present. We really don't want to suggest links for schema sections
        if(false !== strpos($content, '<script')){
            $content = mb_ereg_replace('<script(?:[^>]*)>(.*?)<\/script>', '', $content);
        }

        // remove any YooTheme JSON that's in the content
        if( false !== strpos($content, '<!--more-->') && (false !== strpos($content, '<!--') || false !== strpos($content, '<!-- ')) && Wpil_Editor_YooTheme::yoo_active()){
            $content = mb_ereg_replace('<!--\s*?(\{(?:.*?)\})\s*?-->', '', $content);
        }

        // if there happen to be any css tags, remove them too
        if(false !== strpos($content, '<style')){
            $content = mb_ereg_replace('<style(?:[^>]*)>(.*?)<\/style>', '', $content);
        }

        // if there are any 'pre' tags, remove them from the content
        if(false !== strpos($content, '<pre')){
            $content = mb_ereg_replace('<pre(?:[^>]*)>(.*?)<\/pre>', "\n", $content);
        }

        // remove any shortcodes that the user has defined
        $content = self::removeShortcodes($content);

        // remove page builder modules that will be turned into things like headings, buttons, and links
        $content = self::removePageBuilderModules($content);

        // remove elements that have certain classes
        $content = self::removeClassedElements($content);

        // remove any tags that the user doesn't want to create links in
        $content = self::removeIgnoredContentTags($content);

        // encode the links in the content to avoid breaking them. (And to avoid cases where there's a line break char in the anchor, and when the text is split, we get half a linmk in two sentences... which are then discarded for safety!)
        $content = preg_replace_callback('|<a\s[^><]*?href=[\'\"][^><\'\"]*?[\'\"][^><]*?>[\s\S]*?<\/a>|i', function($i){ return str_replace($i[0], 'wpil-link-replace_' . base64_encode($i[0]), $i[0]); }, $content);

        // encode the contents of attributes so we don't have mistakes when breaking the content into sentences
        $content = preg_replace_callback('|(?:[a-zA-Z-]*?=["]([^"]*?)["])[^<>]*?|i', function($i){ return str_replace($i[1], 'wpil-attr-replace_' . base64_encode($i[1]), $i[0]); }, $content);
        $content = preg_replace_callback('/(?:[a-zA-Z-_0-9]*?=[\']((?:[\\]+?[\']|[^\'])*?)[\'])[^<>]*?/i', function($i){ return str_replace($i[1], 'wpil-attr-replace_' . base64_encode($i[1]), $i[0]); }, $content);

        // encode any supplied ignore text so we don't split sentences that are supposed to contain punctuation. (EX: Autolink keywords or sentences that contain Dr. or Mr.)
        $ignore_text = (is_string($ignore_text)) ? array($ignore_text): $ignore_text;
        $ignore_text = array_merge($ignore_text, self::getIgnoreTextDefaults());
        foreach($ignore_text as $text){
            $i_text = "(?<![[:alpha:]<>-_1-9])" . preg_quote($text, '/') . "(?![[:alpha:]<>-_1-9])";
            $content = preg_replace_callback('/' . $i_text . '/i' , function($i){ return str_replace($i[0], 'wpil-ignore-replace_' . base64_encode($i[0]), $i[0]); }, $content);
        }

        $i_text = "(?<![[:alpha:]<>-_1-9])(?:[A-Za-z]\.){2,20}(?![[:alpha:]<>-_1-9])"; // also run a regex for searching common abbreviations
        $content = preg_replace_callback('/' . $i_text . '/i' , function($i){ return str_replace($i[0], 'wpil-ignore-replace_' . base64_encode($i[0]), $i[0]); }, $content);

        // if the user want's to skip paragraphs
        if('paragraphs' === $section_skip_type){
            // remove the number he's selected
            $content = self::removeParagraphs($content);
        }

        //divide text to sentences
        $replace = [
            ['.<', '. ', '. ', '.&nbsp;', '.\\', '!<', '! ', '! ', '!\\', '?<', '? ', '? ', '?\\', '<div', '<br', '<li', '<p', '<h1', '<h2', '<h3', '<h4', '<h5', '<h6', '。', '<td', '</td>', '<ul', '</ul>', '<ol', '</ol>'],
            [".\n<", ". \n", ".\n", ".\n&nbsp;", ".\n\\", "!\n<", "! \n", "!\n", "!\n\\", "?\n<", "? \n", "?\n", "?\n\\", "\n<div", "\n<br", "\n<li", "\n<p", "\n<h1", "\n<h2", "\n<h3", "\n<h4", "\n<h5", "\n<h6", "\n。", "\n<td", "</td>\n", "\n<ul", "</ul>\n", "\n<ol", "</ol>\n"]
        ];
        $content = str_ireplace($replace[0], $replace[1], $content);
        $content = preg_replace('|\.([A-Z]{1})|', ".\n$1", $content);
        $content = preg_replace('|\[[^\]]+\]|i', "\n", $content);

        $list = explode("\n", $content);


        foreach($list as $key => $item){
            // decode all the attributes now that the content has been broken into sentences
            if(false !== strpos($item, 'wpil-attr-replace_')){
                $list[$key] = preg_replace_callback('|(?:[a-zA-Z-]*=["\'](wpil-attr-replace_([^"\']*?))["\'])[^<>]*?|i', function($i){
                    return str_replace($i[1], base64_decode($i[2]), $i[0]);
                }, $item);
            }

            // also decode any links
            if(false !== strpos($item, 'wpil-link-replace_')){
                $list[$key] = preg_replace_callback('/(?:wpil-link-replace_([A-z0-9=\/+]*))/', function($i){
                    return str_replace($i[0], base64_decode($i[1]), $i[0]);
                }, $item);
            }
        }

        $list = self::mergeSplitSentenceTags($list);
        self::removeEmptySentences($list, $with_links);
        self::trimTags($list, $with_links);

        // if the user want's to skip sentences
        if('sentences' === $section_skip_type){
            // remove the number he's selected
            $list = array_slice($list, Wpil_Settings::getSkipSentences());
        }

        $phrases = [];
        foreach ($list as $item) {
            $item = trim($item);
/* TODO: Review and see if this is still needed
            if(!empty($word_segments)){
                // check if the phrase contains 2 title words
                $wcount = 0;
                foreach($word_segments as $seg){
                    if(false !== stripos($item, $seg)){
                        $wcount++;
                        if($wcount > 1){
                            break;
                        }
                    }
                }
                if($wcount < 2){
                    continue;
                }
            }*/

            if (in_array(substr($item, -1), ['.', ',', '!', '?', '。'])) {
                $item = substr($item, 0, -1);
            }

            // save the src before we decode the ignored txt
            $src_raw = $item;
            // decode the ignored txt
            $item = self::decodeIgnoredText($item);

            $sentence = [
                'src_raw' => $src_raw,
                'src' => $item,
                'text' => trim(strip_tags(htmlspecialchars_decode($item)))
            ];

            if($full_sentences){
                $phrases = array_merge($phrases, self::getSentences($sentence));
            }else

            //add sentence to array if it has at least 2 words
            if (!empty($sentence['text']) && ($single_words || count(explode(' ', $sentence['text'])) > 1)) {
                $phrases = array_merge($phrases, self::getPhrasesFromSentence($sentence, true));
            }
        }


        return $phrases;
    }

    /**
     * Decodes sections in the content that were created by page builders so we can accurately split the content up into phrases.
     * As always, this content is not meant to be saved, so it's alright if we change the formatting so we can process it.
     * We won't be decoding shortcodes because it's not possible to save links to shortcodes.
     * @param string $content The content to maybe decode content sections from
     * @return string $content The content that may have had it's content decoded
     **/
    public static function decode_page_builder_content($content = ''){
        // if the content contains a Thrive raw content shortcode, decode the shortcode content.
        if(false !== strpos($content, '___TVE_SHORTCODE_RAW__')){
            list( $start, $end ) = array(
                '___TVE_SHORTCODE_RAW__',
                '__TVE_SHORTCODE_RAW___',
            );
            if ( ! preg_match_all( "/{$start}((<p>)?(.+?)(<\/p>)?){$end}/s", $content, $matches, PREG_OFFSET_CAPTURE ) ) {
                return $content;
            }
        
            $position_delta = 0;
            foreach ( $matches[1] as $i => $data ) {
                $raw_shortcode = $data[0]; // the actual matched regexp group
                $position      = $matches[0][ $i ][1] + $position_delta; //the index at which the whole group starts in the string, at the current match
                $whole_group   = $matches[0][ $i ][0];
        
                $raw_shortcode = html_entity_decode( $raw_shortcode );//we keep the code encoded and now we need to decode
        
                $replacement = '</div><div class="tve_shortcode_rendered">' . $raw_shortcode;
        
                $content     = substr_replace( $content, $replacement, $position, strlen( $whole_group ) );
                /* increment the positioning offsets for the string with the difference between replacement and original string length */
                $position_delta += strlen( $replacement ) - strlen( $whole_group );
            }
        }

        return $content;
    }

    /**
     * Removes page builder created modules from the text content.
     * Since these heading elements are rendered by the builder, the normal HTML heading/link remover doesn't catch these.
     * Checks the text for the presence of the modules so we're not regexing the text unnecessarily
     * 
     * @param string $content The post content.
     * @return string $content The processed post content
     **/
    public static function removePageBuilderModules($content = ''){


        $fusion_regex = '';
        // remove fusion builder (Avada) titles if present
        if(false !== strpos($content, 'fusion_title')){
            $fusion_regex .= '\[fusion_title(?:[^\]]*)\](.*?)\[\/fusion_title\]';
        }
        if(false !== strpos($content, 'fusion_imageframe')){
            $fusion_regex .= '|\[fusion_imageframe(?:[^\]]*)\](.*?)\[\/fusion_imageframe\]';
        }
        if(false !== strpos($content, 'fusion_button')){
            $fusion_regex .= '|\[fusion_button(?:[^\]]*)\](.*?)\[\/fusion_button\]';
        }
        if(false !== strpos($content, 'fusion_gallery')){
            $fusion_regex .= '|\[fusion_gallery(?:[^\]]*)\](.*?)\[\/fusion_gallery\]';
        }
        if(false !== strpos($content, 'fusion_code')){
            $fusion_regex .= '|\[fusion_code(?:[^\]]*)\](.*?)\[\/fusion_code\]';
        }
        if(false !== strpos($content, 'fusion_modal')){
            $fusion_regex .= '|\[fusion_modal(?:[^\]]*)\](.*?)\[\/fusion_modal\]';
        }
        if(false !== strpos($content, 'fusion_menu')){
            $fusion_regex .= '|\[fusion_menu(?:[^\]]*)\](.*?)\[\/fusion_menu\]';
        }
        if(false !== strpos($content, 'fusion_modal_text_link')){
            $fusion_regex .= '|\[fusion_modal_text_link(?:[^\]]*)\](.*?)\[\/fusion_modal_text_link\]';
        }
        if(false !== strpos($content, 'fusion_vimeo')){
            $fusion_regex .= '|\[fusion_vimeo(?:[^\]]*)\](.*?)\[\/fusion_vimeo\]';
        }
        // if there is Fusion (Avada) content
        if(!empty($fusion_regex)){
            // remove any leading "|" since it would be bad to have it...
            $fusion_regex = ltrim($fusion_regex, '|');
            // remove the items we don't want to add links to
            $content = mb_ereg_replace($fusion_regex, "\n", $content);
        }

        $cornerstone_regex = '';
        // if a Cornerstone heading is present in the text
        if(false !== strpos($content, 'cs_element_headline')){
            $cornerstone_regex .= '\[cs_element_headline(?:[^\]]*)\]\[cs_content_seo\](.*?)\[\/cs_content_seo\]';
        }
        if(false !== strpos($content, 'x_custom_headline')){
            $cornerstone_regex .= '|\[x_custom_headline(?:[^\]]*)\](.*?)\[\/x_custom_headline\]';
        }
        if(false !== strpos($content, 'x_image')){
            $cornerstone_regex .= '|\[x_image(?:[^\]]*)\](.*?)\[\/x_image\]';
        }
        if(false !== strpos($content, 'x_button')){
            $cornerstone_regex .= '|\[x_button(?:[^\]]*)\](.*?)\[\/x_button\]';
        }
        if(false !== strpos($content, 'cs_element_card')){
            $cornerstone_regex .= '|\[cs_element_card(?:[^\]]*)\]\[cs_content_seo\](.*?)\[\/cs_content_seo\]';
        }

        // if there is Cornerstone content
        if(!empty($cornerstone_regex)){
            // remove any leading "|" since it would be bad to have it...
            $cornerstone_regex = ltrim($cornerstone_regex, '|');
            // remove the items we don't want to add links to
            $content = mb_ereg_replace($cornerstone_regex, "\n", $content); // Remove Cornerstone/X|Pro theme headings and items with links
        }

        return $content;
    }

    /**
     * Removes elements from the content that have certain classes
     **/
    public static function removeClassedElements($content = ''){
        if(empty($content)){
            return $content;
        }

        // remove the twitter-tweet element as a standard remove if it's in the content
        if(!empty($content) && false !== strpos($content, 'blockquote') && false !== strpos($content, 'twitter-tweet')){
            $content = mb_ereg_replace('<blockquote[\s][^>]*?(class=["\'][^"\']*?(twitter-tweet)[^"\']*?["\'])[^>]*?>.*?<(\/blockquote|\\\/blockquote)', '', $content);
        }

        // get the classes to ignore
        $remove_classes = Wpil_Settings::get_ignored_element_classes();

        // exit if the user isn't ignoring any classed elements
        if(empty($remove_classes)){
            return $content;
        }

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
            $regex = '<([a-zA-Z]*?[^\s<>]*?)[\s][^<>]*?(class=' . $regex_class . ')[^<>]*?>[\s\S]*?<\/\1>|<img[\s][^<>]*?(class=' . $regex_class . ')[^<>]*?>';
            // NOTE: This regex will remove the opening tag that contains the class and any closing tag that matches the opening tag's name.
            // So it's possible to remove everything between the first opening tag in a bunch of nested divs and the closing tag of the innermost element.
            // Leaving behind a number of unaffiliated closing divs.
            // Since we don't directly use most of the content we process, this _shouldn't_ be an issue for suggestions.
            // But it does mean that the reach of this is somewhat limited.
            // And if we do make the mistake of using phrase data for saving purposes, it could cause some issues.

            // remove the classed elements from the content
            $content = mb_ereg_replace($regex, "\n", $content);
        }

        return $content;
    }

    /**
     * Removes user-ignored shortcodes from supplied content so we don't process their content
     **/
    public static function removeShortcodes($content = ''){
        if(empty($content)){
            return $content;
        }

        // get the shortcodes to ignore
        $shortcode_names = Wpil_Settings::get_ignored_shortcode_names();

        // exit if the user isn't ignoring any shortcodes
        if(empty($shortcode_names)){
            return $content;
        }

        // go over the shortcode names
        foreach($shortcode_names as $index => $name){
            // if there's no name, or it's not in the content
            if(empty($name) || false === strpos($content, '[' . $name)){ // we're checking for the opening tag
                // skip to the next shortcode
                continue;
            }
            
            // remove any opening/closing shortcode pairs, and the content then contain
            $regex = '\[' . preg_quote($name) . '(?:[ ][^\[\]]*\]|\])[\s\S]*?\[\/' . preg_quote($name) . '\]';
            $content = mb_ereg_replace($regex, "\n", $content);

            // if there are still shortcodes in the content
            if(false !== strpos($content, '[' . $name)){ // again checking for the opening tag
                // try removing singular shortcode tags
                $regex = '\[' . preg_quote($name) . '(?:[ ][^\[\]]*\]|\])';
                $content = mb_ereg_replace($regex, "\n", $content);
            }
        }

        return $content;
    }

    /**
     * Removes user-ignored HTML tags so we don't stick links in them
     **/
    public static function removeIgnoredContentTags($content = ''){
        if(empty($content)){
            return $content;
        }

        // get the shortcodes to ignore
        $ignored_tags = Wpil_Settings::getIgnoreLinkingTags();

        // exit if the user isn't ignoring any shortcodes
        if(empty($ignored_tags)){
            return $content;
        }

        // set up the tag removing regex
        $tag_regex = '';

        // go over the tags
        foreach($ignored_tags as $index => $tag){
            // if there's no tag, or it's not in the content
            if(empty($tag) || false === strpos($content, '</' . $tag . '>')){ // we're checking for the closing tag since that won't have classes or other random attributes
                // skip to the next tag
                continue;
            }
            
            // if we've got a tag, add it to the regex
            $tag_regex .= '<' . $tag . '(?:[^>]*)>(.*?)<\/' . $tag . '>|';
        }

        // trim off any pipes
        $tag_regex = (!empty($tag_regex)) ? trim($tag_regex, '|'): '';

        // if we've got a regex to work with
        if(!empty($tag_regex)){
            // use it on the content
            $content = mb_ereg_replace($tag_regex, "\n", $content); // replace the tag with a newline so that we split the sentence and don't accidentally create a suggestion for a sentence that has a slice of HTML chopped out of it.
        }

        // and return the content
        return $content;
    }

    /**
     * Returns a filterable list of abbreviations that the sentence splitter should ignore.
     * Contains abbreviations that don't follow the (Letter + Period * (Repeat?)) pattern used for common abbrs. like "U.S.A."
     * There's a regex up in the sentence splitter that handles that.
     * This is for abbrs. that come in a specific format like "Prof." or "Ave."
     * @return array
     **/
    public static function getIgnoreTextDefaults(){
        return apply_filters('wpil_phrase_abbreviation_list', 
            array(  'Mr.', 'Mrs.', 'Ms.', 'Mssr.', 'Dr.', 'Prof.', 'Rev.', 'St.',
                    'Gen.',  'Col.', 'Maj.', 'Capt.', 'Lt.', 'Sgt.', 'Sr.', 'Jr.',
                    'Ave.', 'Blvd.', 'Co.', 'Inc.', 'Ltd.', 'Esq.', 'etc.', 'EX:',
                    'vs.', 'Dept.', 'Sec.', 'Treas.', 'Vol.', 'Ed.',
            )
        );
    }

    /**
     * Removes the user's selected number of paragraphs from the start of the post content
     **/
    public static function removeParagraphs($content){
        $skip_count = Wpil_Settings::getSkipSentences();

        if(empty($skip_count)){
            return $content;
        }

        $pos = self::get_paragraph_offset($content, $skip_count);

        // make sure that content is longer than the skip pos
        if(mb_strlen($content) > $pos){
            $content = mb_substr($content, $pos);
        }

        return $content;
    }

    /**
     * Gets the paragraph offset for a specific piece of text so we can tell how far down a specific paragraph break is
     **/
    public static function get_paragraph_offset($content, $paragraph_num = 0, $reverse = false) {
        // create an offset index for the tags we're searching for
        $char_count = array(
            'p' => 4,
            'div' => 6,
            'newline' => 2,
            'blockquote' => 12
        );

        // if we're counting backwards
        if($reverse){
            // add an extra loop to account for the fact the search works from the end of a paragraph.
            // That way, entering a 2 for $paragraph_num will give us "before the second to last paragraph"
            $paragraph_num++;
        }

        $i = 0;
        $len = mb_strlen($content);
        $pos = 0;
        while($i < $paragraph_num && $pos >= 0 && $pos < $len){

            $reverse_search = ($pos) ? ($len - $pos) * -1: $pos;

            // search for the possible paragraph endings
            $pos_search = array(
                'p' => $reverse ? Wpil_Word::mb_strrpos($content, '</p>', $reverse_search) : Wpil_Word::mb_strpos($content, '</p>', $pos),
                'div' => $reverse ? Wpil_Word::mb_strrpos($content, '</div>', $reverse_search) : Wpil_Word::mb_strpos($content, '</div>', $pos),
                'newline' => $reverse ? Wpil_Word::mb_strrpos($content, '\n', $reverse_search) : Wpil_Word::mb_strpos($content, '\n', $pos),
                'blockquote' => $reverse ? Wpil_Word::mb_strrpos($content, '</blockquote>', $reverse_search) : Wpil_Word::mb_strpos($content, '</blockquote>', $pos)
            );

            // sort the results and remove the empties
            asort($pos_search);
            $pos_search = array_filter($pos_search);

            // if nothing is found
            if(empty($pos_search)){
                // and we're going backwards AND there are more paragraphs to go or we're at the limit
                if($reverse && $i <= $paragraph_num){
                    // set the position for start
                    $pos = 0;
                }

                // exit the loop
                break;
            }

            // get the closest paragraph ending and its type
            $temp_pos = reset($pos_search);
            $temp_ind = key($pos_search);
    
            // if the ending was a div
            if($temp_ind === 'div'){
                // see if there's an opening tag before the last pos
                $div_pos = $reverse ? Wpil_Word::mb_strrpos($content, '<div', $temp_pos) : Wpil_Word::mb_strpos($content, '<div', $pos);

                // if there is
                if (false !== $div_pos) {
                    // check if there's any text between the tags
                    $div_content = mb_substr($content, $div_pos, ($temp_pos - $div_pos)); // full-length string ending - div start == div_content. If we don't remove the div start the string is too long
                    $div_content = trim(strip_tags(mb_ereg_replace('<a[^>]*>.*?</a>|<h[1-6][^>]*>.*?</h[1-6]>', '', $div_content))); // remove links, headings, strip tags that might have text attrs, and trim

                    // if there isn't any content, but there is a runner-up tag
                    if (empty($div_content) && count($pos_search) > 1) {
                        $slice = array_slice($pos_search, 1, 1);
                        $temp_pos = reset($slice);
                        $temp_ind = key($slice);
                    }
                }else{
                    // if there isn't an opening div tag, we're holding the tail of a container.
                    // So go with the runner-up tag
                    if(count($pos_search) > 1){
                        $slice = array_slice($pos_search, 1, 1);
                        $temp_pos = reset($slice);
                        $temp_ind = key($slice);
                    }
                }
            }

            $i++;

            if($reverse && $i < $paragraph_num && $pos >= 0 && $pos < $len) {
                $pos = ($temp_pos - $char_count[$temp_ind]);
            }else {
                $pos = ($temp_pos + $char_count[$temp_ind]);
            }
        }

        return $pos;
    }

    /**
     * Get phrases from sentence
     */
    public static function getPhrasesFromSentence($sentence, $one_word = false)
    {
        $phrases = [];
        $replace = [', ', ': ', '; ', ' – ', ' (', ') ', ' {', '} '];
        $exceptions = ['&amp;' => '%%wpil-amp%%'];
        $src = $sentence['src_raw'];

        //change divided symbols inside tags to special codes
        preg_match_all('|<[^>]+>|', $src, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $tag) {
                $tag_replaced = $tag;
                foreach ($replace as $key => $value) {
                    if (strpos($tag, $value) !== false) {
                        $tag_replaced = str_replace($value, "[rp$key]", $tag_replaced);
                    }
                }

                if ($tag_replaced != $tag) {
                    $src = str_replace($tag, $tag_replaced, $src);
                }
            }
        }

        // except the exceptables
        foreach($exceptions as $key => $value){
            $src = str_replace($key, $value, $src);
        }

        //divide sentence to phrases
        $src = str_ireplace($replace, "\n", $src);

        // de-except the exceptables
        foreach(array_flip($exceptions) as $key => $value){
            $src = str_replace($key, $value, $src);
        }

        //change special codes to divided symbols inside tags
        foreach ($replace as $key => $value) {
            $src = str_replace("[rp$key]", $value, $src);
        }

        $list = explode("\n", $src);

        foreach ($list as $item) {
            $item = self::decodeIgnoredText($item);
            $phrase = new Wpil_Model_Phrase([
                'text' => trim(strip_tags(htmlspecialchars_decode($item))),
                'src' => $item,
                'sentence_text' => $sentence['text'],
                'sentence_src' => $sentence['src'],
            ]);

            if (!empty($phrase->text) && ($one_word || count(explode(' ', $phrase->text)) > 1)) {
                $phrases[] = $phrase;
            }
        }

        return $phrases;
    }

    /**
     * Decodes any ignored text.
     * @param string The text that is to be decoded
     **/
    public static function decodeIgnoredText($text = ''){
        if(false !== strpos($text, 'wpil-ignore-replace_')){
            $text = preg_replace_callback('/(?:wpil-ignore-replace_([A-z0-9=\/+]*))/', function($i){
                return str_replace($i[0], base64_decode($i[1]), $i[0]);
            }, $text);
        }

        return $text;
    }

    /**
     * Get processed sentences so we can use the whole sentence text in our suggestions.
     */
    public static function getSentences($sentence, $one_word = false)
    {
        $phrases = [];
        $src = $sentence['src_raw'];

        $item = self::decodeIgnoredText($src);
        $phrase = new Wpil_Model_Phrase([
            'text' => trim(strip_tags(htmlspecialchars_decode($item))),
            'src' => $item,
            'sentence_text' => $sentence['text'],
            'sentence_src' => $sentence['src'],
        ]);

        $phrases[] = $phrase;

        return $phrases;
    }

    /**
     * Collect uniques words from all post titles
     *
     * @param $post_id
     * @param null $target
     * @return array
     */
    public static function getTitleWords($post, $target = null, $keyword = null, $count = null, $process_key = 0)
    {
        global $wpdb;
        $link_report_table = $wpdb->prefix . 'wpil_report_links';

        $ignore_words = Wpil_Settings::getIgnoreWords();
        $ignore_posts = Wpil_Settings::getIgnorePosts();
        $ignore_categories_posts = Wpil_Settings::getIgnoreCategoriesPosts();
        $ignore_numbers = get_option(WPIL_OPTION_IGNORE_NUMBERS, 1);
        $only_show_cornerstone = (get_option('wpil_link_to_yoast_cornerstone', false) && empty($target));
        $outbound_selected_posts = Wpil_Settings::getOutboundSuggestionPostIds();
        $inbound_link_limit = (int)get_option('wpil_max_inbound_links_per_post', 0);
        $post_embeddings = Wpil_AI::get_embedding_relatedness_data($post->id, $post->type, true);
        $has_api_key = Wpil_Settings::getOpenAIKey();
        $relatedness_threshold = Wpil_Settings::get_suggestion_filter('ai_relatedness_threshold');

        $posts = [];
        if (!is_null($target)) {
            $posts[] = $target;
        } else {
            $limit  = Wpil_Settings::getProcessingBatchSize();
            $post_ids = get_transient('wpil_title_word_ids_' . $process_key);
            if(empty($post_ids) && !is_array($post_ids)){
                //add all posts to array
                $exclude = self::getTitleQueryExclude($post);
                $post_types = implode("','", self::getSuggestionPostTypes());

                // get all posts in the same language if translation active
                $include = "";
                $check_language_ids = false;
                $ids = array();
                if (Wpil_Settings::translation_enabled()) {
                    $ids = Wpil_Post::getSameLanguagePosts($post->id);

                    if(!empty($ids) && count($ids) < $limit){
                        $include = " AND ID IN (" . implode(', ', $ids) . ") ";
                    }elseif(!empty($ids) && count($ids) > $limit){
                        $check_language_ids = true;
                    }else{
                        $include = " AND ID IS NULL ";
                    }
                }

                // get the age query if the user is limiting the range for linking
                $age_string = Wpil_Query::getPostDateQueryLimit();

                $blood_relative = '';
                if($post->type === 'post' && !empty(Wpil_Settings::get_suggestion_filter('same_parent'))){
                    $related_ids = Wpil_Toolbox::get_related_post_ids($post);
                    if(!empty($related_ids)){
                        $blood_relative = 'AND ID IN (' . implode(',', $related_ids) . ')';
                    }
                }

                $user_role_ignore = Wpil_Query::get_ignore_user_role_suggestions_query();

                $statuses_query = Wpil_Query::postStatuses();
                $post_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE 1=1 $exclude AND post_type IN ('{$post_types}') $statuses_query {$blood_relative} {$age_string} {$user_role_ignore}" . $include);

/*
                // if we're checking for semantically related posts
                if(!empty($has_api_key) && !empty($post_embeddings)){
                    if(!empty($post_embeddings)){
                        // get our relatedness threashold
                        foreach($post_ids as $key => $post_id){
                            $id = 'post_' . $post_id;
                            if(isset($post_embeddings[$id]) && $post_embeddings[$id] < $relatedness_threshold){
                                unset($post_ids[$key]);
                            }
                        }
                    }
                }*/

                if(!empty($post_ids) && !empty(Wpil_Settings::get_suggestion_filter('link_orphaned'))){
                    $post_ids = implode(',', $post_ids);

                    if(Wpil_Settings::use_link_table_for_data()){
                        $post_ids = $wpdb->get_col("SELECT `post_id` as 'ID' FROM {$link_report_table} WHERE `target_id` NOT IN ({$post_ids}) AND `target_type` = 'post'");
                    }else{

                        $post_ids = $wpdb->get_col("SELECT `post_id` as 'ID' FROM {$wpdb->postmeta} WHERE `post_id` IN ({$post_ids}) AND `meta_key` = 'wpil_links_inbound_internal_count' AND `meta_value` = 0");
                    }
                }elseif(!empty($post_ids) && !empty($inbound_link_limit)){
                    $post_ids = implode(',', $post_ids);
                    if(Wpil_Settings::use_link_table_for_data()){
                        $post_ids = $wpdb->get_col("SELECT `post_id` as 'ID' FROM {$link_report_table} WHERE `target_id` IN ({$post_ids}) AND `target_type` = 'post' GROUP BY target_id HAVING COUNT(post_id) < {$inbound_link_limit}");
                    }else{
                        $post_ids = $wpdb->get_col("SELECT `post_id` as 'ID' FROM {$wpdb->postmeta} WHERE `post_id` IN ({$post_ids}) AND `meta_key` = 'wpil_links_inbound_internal_count' AND `meta_value` < {$inbound_link_limit}");
                    }
                }

                if(empty($post_ids)){
                    $post_ids = array();
                }

                // if we have a lot of language ids to check
                if(!empty($post_ids) && $check_language_ids){
                    // find all the ids that are in the same language
                    $post_ids = array_intersect($post_ids, $ids);
                }

                if(!empty($post_ids) && Wpil_Settings::removeNoindexFromSuggestions()){
                    $post_ids = Wpil_Query::remove_noindex_ids($post_ids);
                }

                // if the user wants to limit the number of posts searched for suggestions
                $max_count = Wpil_Settings::get_max_suggestion_post_count();
                if(!empty($max_count) && !empty($post_ids)){
                    // shuffle the ids so we don't consistently miss posts
                    shuffle($post_ids);
                    // obtain the number of posts that the user wants
                    $post_ids = array_slice($post_ids, 0, $max_count);
                }

                set_transient('wpil_title_word_ids_' . $process_key, $post_ids, MINUTE_IN_SECONDS * 15);
            }

            // if we're only supposed to show links to the Yoast cornerstone content
            if($only_show_cornerstone && !empty($result)){
                // get the ids from the initial query
                $ids = array();
                foreach($result as $item){
                    $ids[] = $item->ID;
                }

                // query the meta to see what posts have been set as cornerstone content
                $result = $wpdb->get_results("SELECT `post_id` AS ID FROM {$wpdb->postmeta} WHERE `post_id` IN (" . implode(', ', $ids) . ") AND `meta_key` = '_yoast_wpseo_is_cornerstone'");
            }

            // if we're limiting outbound suggestions to specfic posts
            if(empty($target) && !empty($outbound_selected_posts) && !empty($result)){
                // get all of the ids that the user wants to make suggestions to
                $ids = array();
                foreach($outbound_selected_posts as $selected_post){
                    if(false !== strpos($selected_post, 'post_')){
                        $ids[substr($selected_post, 5)] = true;
                    }
                }

                // filter out all the items that aren't in the outbound suggestion limits
                $result_items = array();
                foreach($result as $item){
                    if(isset($ids[$item->ID])){
                        $result_items[] = $item;
                    }
                }

                // update the results with the filtered ids
                $result = $result_items;
            }

            $posts = [];
            $process_ids = array_slice($post_ids, 0, $limit);

            if(!empty($process_ids)){
                $process_ids = implode("', '", $process_ids);
                $result = $wpdb->get_results("SELECT ID, post_title, post_name FROM {$wpdb->posts} WHERE ID IN ('{$process_ids}')");

                foreach ($result as $item) {
                    if (!in_array('post_' . $item->ID, $ignore_posts) && !in_array($item->ID, $ignore_categories_posts)) {
                        $post_obj = new Wpil_Model_Post($item->ID);
                        $post_obj->title = $item->post_title;
                        $post_obj->slug = $item->post_name;

                        $posts[] = $post_obj;
                    }
                }

                // remove this batch of post ids from the list and save the list
                $save_ids = array_slice($post_ids, $limit);
                set_transient('wpil_title_word_ids_' . $process_key, $save_ids, MINUTE_IN_SECONDS * 15);
            }

            // if terms are to be scanned, but the user is restricting suggestions by term, don't search for terms to link to. Only search for terms if:
            if (    !empty(Wpil_Settings::getTermTypes()) && // terms have been selected
                    empty(Wpil_Settings::get_suggestion_filter('same_category')) && // we're not restricting by category
                    empty(Wpil_Settings::get_suggestion_filter('same_tag')) && // we're not restricting by tag
                    empty(Wpil_Settings::get_suggestion_filter('same_parent')) && // we're not restricting to the post's family
                    empty($only_show_cornerstone)) // the user hasn't set LW to only process cornerstone content
            {
                if (is_null($count) || $count == 0) {
                    //add all categories to array
                    $exclude = "";
                    if ($post->type == 'term') {
                        $exclude = " AND t.term_id != {$post->id} ";
                    }

                    $taxonomies = Wpil_Settings::getTermTypes();
                    $result = $wpdb->get_results("SELECT t.term_id FROM {$wpdb->prefix}term_taxonomy tt LEFT JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id WHERE tt.taxonomy IN ('" . implode("', '", $taxonomies) . "') $exclude");

                    // if the user only wants to make outbound suggestions to specific categories
                    if(empty($target) && !empty($outbound_selected_posts) && !empty($result)){
                        // get all of the ids that the user wants to make suggestions to
                        $ids = array();
                        foreach($outbound_selected_posts as $selected_term){
                            if(false !== strpos($selected_term, 'term_')){
                                $ids[substr($selected_term, 5)] = true;
                            }
                        }

                        foreach($result as $key => $item){
                            if(!isset($ids[$item->term_id])){
                                unset($result[$key]);
                            }
                        }
                    }

                    // if we're checking for semantically related posts
                    /*if($has_api_key){ // todo: make into actual setting
                        if(!empty($post_embeddings)){
                            foreach($result as $key => $item){
                                $id = 'term_' . $item->term_id;
                                if(isset($post_embeddings[$id]) && $post_embeddings[$id] < $relatedness_threshold){
                                    unset($result[$key]);
                                }
                            }
                        }
                    }*/

                    foreach ($result as $term) {
                        if (!in_array('term_' . $term->term_id, $ignore_posts)) {
                            $posts[] = new Wpil_Model_Post($term->term_id, 'term');
                        }
                    }
                }
            }
        }

        if(!empty($target) && $words = get_transient('wpil_inbound_title_words_' . $process_key)){// TODO: Remove at update 2.3.3 if no one complains about slower inbound Internal suggestions // renabling since I think there has been a slowdown for some users. I think that I disabled the check because a user was having issues with the keyword searching not updating. I think that's been fixed now, so it should be safe to set this going again.
            return $words;
        }else{
            // get if the user is only matching with part of the post titles
            $partial_match = Wpil_Settings::matchPartialTitles();
            // get if the user wants to use the slug for suggestions instead of the title
            $use_slug = Wpil_Settings::use_post_slug_for_suggestions();

            $words = [];
            foreach ($posts as $key => $p) {
                //get unique words from post title
                if (!empty($keyword)) { 
                    $title_words = array_unique(Wpil_Word::getWords($keyword));
                } else {
                    if($use_slug){
                        $title = $p->getSlugWords();
                    }else{
                        $title = $p->getTitle();
                    }
                    if($partial_match){
                        $title = self::getPartialTitleWords($title);
                    }

                    // if these are outbound suggestions
                    if(empty($target)){
                        // get any target keywords the post has and add them to the title so we can make outbound matches based on them
                        $title .= Wpil_TargetKeyword::get_active_keyword_string($p->id, $p->type);
                    }

                    $title_words = array_map(function($w){ return trim(trim($w, '[]{}\'"()$&|'));}, array_unique(Wpil_Word::getWords($title)));
                }

                foreach ($title_words as $word) {
                    $normalized_word = Wpil_Stemmer::Stem(Wpil_Word::remove_accents(Wpil_Word::strtolower($word)), true, true);
                    $word = Wpil_Stemmer::Stem(Wpil_Word::strtolower($word));

                    //check if word is not a number and is not in the ignore words list
                    if (!empty($_REQUEST['keywords']) ||
                        (strlen($word) > 2 && !in_array($word, $ignore_words) && (!$ignore_numbers || !is_numeric(str_replace(['.', ',', '$'], '', $word))))
                    ) {
                        $words[$word][] = $p;

                        if(strlen($normalized_word) > 2 && $word !== $normalized_word){
                            $words[$normalized_word][] = $p;
                        }
                    }
                }
            }

            // save the title words if this is an Inbound Search // TODO: Remove at update 2.3.3 if no one complains about slower inbound Internal suggestions // wish I could remember why I disabled this!
            if(!empty($target)){
                set_transient('wpil_inbound_title_words_' . $process_key, $words, MINUTE_IN_SECONDS * 15);
            }
        }

        return $words;
    }

    /**
     * Gets the section of title words that the user selected from the settings.
     * @param string $title The unchanged post title straigt from the db.
     * @return string $title The post title after we've applied the user's rules to it and removed any words he doesn't want to match with.
     **/
    public static function getPartialTitleWords($title){
        $partial_match_basis = get_option('wpil_get_partial_titles', false);

        // if the user hasn't set a basis, return the title unchanged
        if(empty($partial_match_basis)){
            return $title;
        }

        // if the user wants to only match with a limited number of words from the front or back of the title
        if($partial_match_basis === '1' || $partial_match_basis === '2'){
            // get the number of words he's selected
            $word_count = get_option('wpil_partial_title_word_count', 0);

            if(!empty($word_count)){
                $title_words = mb_split('\s', $title);

                // if we're supposed to remove words from the front of the title
                if($partial_match_basis === '1'){
                    $title_words = array_splice($title_words, 0, $word_count);
                }else{
                    $title_words = array_splice($title_words, count($title_words) - $word_count);
                }

                $title = implode(' ', $title_words);
            }

        }elseif($partial_match_basis === '3' || $partial_match_basis === '4'){ // if the user wants the title words before or after a split char
            $split_char = get_option('wpil_partial_title_split_char', '');

            // if the user has specified a split char and it's present in the title
            if(!empty($split_char) && false !== Wpil_Word::mb_strpos($title, $split_char)){
                $title_words = mb_split(preg_quote($split_char), $title);

                // if we're returning words beofre the split
                if($partial_match_basis === '3'){
                    $title = $title_words[0];
                }else{
                    $title = end($title_words);
                }

            }
        }

        return trim($title);
    }

    public static function getExternalTitleWords($post, $keyword = null, $count = null, $external_links = array()){
        global $wpdb;

        $start = microtime(true);
        $ignore_words = Wpil_Settings::getIgnoreWords();
        $ignore_numbers = get_option(WPIL_OPTION_IGNORE_NUMBERS, 1);
        $ignore_posts = Wpil_Settings::getIgnoreExternalPosts();
        $external_site_data = $wpdb->prefix  . 'wpil_site_linking_data';

        $posts = [];

        //add all posts to array
        $limit  = Wpil_Settings::getProcessingBatchSize();
        $offset = intval($count) * $limit;

        // check if the user has disabled suggestions for an external site
        $no_suggestions = get_option('wpil_disable_external_site_suggestions', array());
        $ignore_sites = '';
        if(!empty($no_suggestions)){
            $urls = array_keys($no_suggestions);
            $ignore = implode('\', \'', $urls);
            $ignore_sites = "WHERE `site_url` NOT IN ('$ignore')";
        }

        $result = $wpdb->get_results("SELECT * FROM {$external_site_data} {$ignore_sites} LIMIT {$limit} OFFSET {$offset}");

        $posts = [];
        foreach ($result as $item) {
            // if the object's url isn't being ignored
            if (!in_array('post_' . $item->post_id, $ignore_posts, true) && $item->type === 'post' ||
                !in_array('term_' . $item->post_id, $ignore_posts, true) && $item->type === 'term')
            {
                $posts[] = new Wpil_Model_ExternalPost($item); // pass the whole object from the db
            }
        }

        $words = [];
        foreach ($posts as $key => $p) {
            //get unique words from post title
            if (!empty($keyword)) {
                $title_words = array_unique(Wpil_Word::getWords($keyword));
            } else {
                $title_words = array_unique(explode(' ', $p->stemmedTitle));
            }

            foreach ($title_words as $word) {
                //check if word is not a number and is not in the ignore words list
                if (!empty($_REQUEST['keywords']) ||
                    (strlen($word) > 2 && !in_array($word, $ignore_words) && (!$ignore_numbers || !is_numeric(str_replace(['.', ',', '$'], '', $word))))
                ) {
                    $words[$word][] = $p;
                }
            }

            if ($key % 100 == 0 && microtime(true) - $start > 10) {
                break;
            }
        }

        return $words;
    }

    /**
     * Gets all the keywords the post is trying to rank for.
     * First checks to see if the keywords have been cached before running through the keyword loading process.
     * 
     * @param object $post The Wpil post object that we're getting the keywords for.
     * @param int $key The key assigned to this suggestion processing program.
     * @return array $all_keywords All the keywords the current post has.
     **/
    public static function getPostKeywords($post, $process_key = 0){
        $all_keywords = get_transient('wpil_post_suggestions_keywords_' . $process_key);
        if(empty($all_keywords)){
            // get the target keywords for the current post
            $target_keywords = self::getInboundTargetKeywords($post);

            // if there's no target keywords
            if(empty($target_keywords)/* && in_array('post-content', Wpil_TargetKeyword::get_active_keyword_sources())*/){
                // get the post's possible keywords from the content and url
                $content_keywords = self::getContentKeywords($post);
            }else{
                $content_keywords = array();
            }

            // merge the keywords together
            $all_keywords = array_merge($target_keywords, $content_keywords);

            set_transient('wpil_post_suggestions_keywords_' . $process_key, $all_keywords, MINUTE_IN_SECONDS * 10);
        }

        return $all_keywords;
    }

    /**
     * Gets all keywords belonging to posts that the current one could link to.
     * Removes any keywords that match the current post's keywords
     *
     * @param array $words_to_posts The list of posts that share common words with the current post
     * @param array $words_to_posts The list of posts that share common words with the current post
     * @return array $all_keywords All the keywords the current post has.
     **/
    public static function getOutboundPostKeywords($words_to_posts = array(), $post_keywords = array()){
        if(empty($words_to_posts)){
            return array();
        }

        // obtain the post ids from the post word data
        $id_data = array();
        foreach($words_to_posts as $word_data){
            foreach($word_data as $post){
                $id_data[$post->type][$post->id] = true;
            }
        }

        // create a list of all the stemmed post keywords
        $post_keyword_list = array();
        foreach($post_keywords as $keyword){
            $post_keyword_list[$keyword->stemmed] = true;
        }

        $all_keywords = array();
        foreach($id_data as $type => $ids){
            $keywords = Wpil_TargetKeyword::get_active_keywords_by_post_ids(array_keys($ids), $type);

            if(!empty($keywords)){
                foreach($keywords as $keyword){
                    $kword = new Wpil_Model_Keyword($keyword);
                    if(!isset($post_keyword_list[$kword->stemmed])){
                        // if the keyword is gsc
                        if($kword->keyword_type === 'gsc-keyword'){
                            // add it to the list without asking
                            $all_keywords[] = $kword;
                        }else{
                            // if it's not, key the keyword to the keywords so that duplicates are filtered out
                            $all_keywords[$kword->keywords] = $kword;
                        }
                    }
                }
            }
        }

        return !empty($all_keywords) ? array_values($all_keywords): array();
    }

    /**
     * Creates a list of keywords for potential suggestion posts that are more specific than the keywords for the current post.
     * So for example, if the current post has a keyword of "House", but another post has "Dog House", this will add "Dog House" as a more specific keyword and allow matches to be made to that post.
     * Without this, all sentences that contain the word "House" will be removed from the suggestions, and "Dog House", "House Repair", and "House Fire" posts won't get suggestions.
     * 
     * All specific keywords are stored in subarrays that are keyed with the less specific keyword.
     * That way we don't have to loop through all of the keywords to see if one of there's a more specific keyword.
     * 
     * @param array $target_keywords The full list of keywords that belong to posts that could be linked
     * @param array $post_keywords The current post's keywords
     * @return array $specific_keywords The list of more specific keywords
     **/
    public static function getMoreSpecificKeywords($target_keywords, $post_keywords){
        $specific_keywords = array();

        foreach($target_keywords as $target_keyword){
            foreach($post_keywords as $post_keyword){
                if( isset($post_keyword->stemmed) && !empty($post_keyword->stemmed) &&
                    $target_keyword->stemmed !== $post_keyword->stemmed &&
                    false !== Wpil_Word::mb_strpos($target_keyword->stemmed, $post_keyword->stemmed))
                {
                    if(!isset($specific_keywords[$post_keyword->stemmed])){
                        $specific_keywords[$post_keyword->stemmed] = array($target_keyword);
                    }else{
                        $specific_keywords[$post_keyword->stemmed][] = $target_keyword;
                    }
                }
            }
        }

        return $specific_keywords;
    }


    /**
     * Gets the target keyword data from the database for the current post, formatted for use in the inbound suggestions.
     **/
    public static function getInboundTargetKeywords($post){
        $keywords = Wpil_TargetKeyword::get_active_keywords_by_post_ids($post->id, $post->type);
        if(empty($keywords)){
            return array();
        }

        $keyword_list = array();
        foreach($keywords as $keyword){
            $keyword_list[] = new Wpil_Model_Keyword($keyword);
        }

        return $keyword_list;
    }

    /**
     * Extracts the post's keywords from the post's content and url.
     * 
     **/
    public static function getContentKeywords($post_obj){
        if(empty($post_obj)){
            return array();
        }

        $post_keywords = array();

        // first get the keyword data from the url/slug
        if($post_obj->type === 'post'){
            $post = get_post($post_obj->id);
            $url_words = $post->post_name;
        }else{
            $post = get_term($post_obj->id);
            $url_words = $post->slug;
        }

        $keywords = implode(' ', explode('-', $url_words));

        $data = array(
            'keyword_index' => self::get_dummy_index(),
            'post_id' => $post_obj->id, 
            'post_type' => $post_obj->type, 
            'keyword_type' => 'post-keyword', 
            'keywords' => $keywords,
            'checked' => 1 // the keyword is effectively checked since it's in the url
        );

        // create the url keyword object and add it to the list of keywords
        $post_keywords[] = new Wpil_Model_Keyword($data);

        // now pull the keywords from the h1 tags if present
        if($post_obj->type === 'post'){
            $content = $post->post_title;
        }else{
            $content = $post->name;
        }

        $data = array(
            'keyword_index' => self::get_dummy_index(),
            'post_id' => $post_obj->id, 
            'post_type' => $post_obj->type, 
            'keyword_type' => 'post-keyword', 
            'keywords' => strip_tags($content),
            'checked' => 1 // the keyword is effectively checked since it's in the header
        );

        // create the h1 keyword object and add it to the list of keywords
        $post_keywords[] = new Wpil_Model_Keyword($data);

/*        
        preg_match('/<h1[^>]*.?>([[:alpha:]\s]*.?)(<\/h1>)/', $content, $matches);
error_log(print_r($post, true));
        if(!empty($matches) && isset($matches[1])){
            $data = array(
                'post_id' => $post_obj->id, 
                'post_type' => $post_obj->type, 
                'keyword_type' => 'post-keyword', 
                'keywords' => strip_tags($matches[1]),
                'checked' => 1 // the keyword is effectively checked since it's in the header
            );

            // create the h1 keyword object and add it to the list of keywords
            $post_keywords[] = new Wpil_Model_Keyword($data);
        }*/

        return $post_keywords;
    }

    /**
     * Gets a dummy index for the post content keywords so that we have a way of keeping track of their use
     **/
    private static function get_dummy_index(){
        // if there's no dummy index
        if(empty(self::$keyword_dummy_index)){
            // create one so we can keep track of keyword use
            self::$keyword_dummy_index = time();
        }

        self::$keyword_dummy_index += 1;
        return self::$keyword_dummy_index;
    }

    /**
     * Creates a list of all the unique keyword words for use in comparisons.
     * So far only used in loose keyword matching. (Currently disabled)
     **/
    public static function getPostUniqueKeywords($target_keywords = array(), $process_key = 0){
        if(empty($target_keywords) || empty($process_key)){
            return array();
        }

        $keywords = get_transient('wpil_post_suggestions_unique_keywords_' . $process_key);
        if(empty($keywords)){
            $keywords = array();
            // add all the keywords to the array
            foreach($target_keywords as $keyword){
                $words = explode(' ', $keyword->stemmed);
                $keywords = array_merge($keywords, $words);
            }

            // at the end, do a flip flip to get the unique words
            $keywords = array_flip(array_flip($keywords));

            // save the results to the cache
            set_transient('wpil_post_suggestions_unique_keywords_' . $process_key, $keywords, MINUTE_IN_SECONDS * 10);
        }

        return $keywords;
    }

    /**
     * Gets the target keyword data from the database for all the posts, formatted for use in the inbound suggestions.
     * Not currently used.
     **/
    public static function getOutboundTargetKeywords($ignore_ids = array(), $ignore_item_types = array()){
        if(isset($_REQUEST['type']) && 'inbound_suggestions' === $_REQUEST['type']){
            $keywords = Wpil_TargetKeyword::get_all_active_keywords($ignore_ids, $ignore_item_types);
            if(empty($keywords)){
                return array();
            }

            $keyword_index = array();
            foreach($keywords as $keyword){
                $words = explode(' ', $keyword->keywords);
                foreach($words as $word){
                    $keyword_index[Wpil_Stemmer::Stem(Wpil_Word::strtolower($word))][$keyword->keyword_index] = $keyword;
                }
            }

            return $keyword_index;
        }else{
            return array();
        }
    }

    /**
     * Get max amount of words in group between sentence
     *
     * @param $words
     * @param $title
     * @return int
     */
    public static function getMaxCloseWords($words_used_in_suggestion, $phrase_text)
    {
        // get the individual words in the source phrase, cleaned of puncuation and spaces
        $phrase_text = Wpil_Word::getWords($phrase_text);

        // stem each word in the phrase text
        foreach ($phrase_text as $key => $value) {
            $phrase_text[$key] = Wpil_Stemmer::Stem(Wpil_Word::strtolower($value));
        }

        // loop over the phrase words, and find the largest grouping of the suggestion's words that occur in sequence in the phrase
        $max = 0;
        $temp_max = 0;
        foreach($phrase_text as $key => $phrase_word){
            if(in_array($phrase_word, $words_used_in_suggestion)){
                $temp_max++;
                if($temp_max > $max){
                    $max = $temp_max;
                }
            }else{
                if($temp_max > $max){
                    $max = $temp_max;
                }
                $temp_max = 0;
            }
        }

        return $max;
    }

    /**
     * Measures how long an anchor text suggestion would be based on the words from the match
     **/
    public static function getSuggestionAnchorLength($phrase = '', $words = array()){
        // get the anchor word indexes
        $anchor_words = self::getSuggestionAnchorWords($phrase->text, $words, true);

        // return the lenght of the anchor if we have the indexes, and zero if we don't
        return !empty($anchor_words) ? $anchor_words['max'] - $anchor_words['min'] + 1: 0;
    }

    /**
     * Adjusts long suggestions so they're shorter by removing words that aren't required for making suggestions.
     * Since LW uses ALL possible common words in making suggestions, it's possible that the matches will contain so many words that it trips the max anchor length check.
     * So an extremly valid suggestion will be removed because it's over qualified.
     * This function's job is to remove extra words that are less important to the overall suggestion to hopefully get under the limit.
     * 
     * @param object $phrase
     * @param array $suggestion The suggestion data that we're going to adjust. This is before the data is put into a suggestion object, so we're going to be dealing with an array
     **/
    public static function adjustTooLongSuggestion($phrase = array(), $suggestion = array()){
        if(empty($suggestion)){
            return $suggestion;
        }

        // get the suggested anchor words
        $anchor_words = self::getSuggestionAnchorWords($phrase->text, $suggestion['words']);

        if(empty($anchor_words)){
            return $suggestion;
        }

        // get if the user wants to use the slug for suggestions instead of the title
        $use_slug = Wpil_Settings::use_post_slug_for_suggestions();

        // create a list of the matching words
        $temp_sentence = implode(' ', $anchor_words);
        // and create the inital map of the words
        $word_positions = array_map(function($word){ 
            return array(
                'word' => $word,            // the stemmed anchor word
                'value' => 0,               // the words matching value
                'significance' => array(),  // what gives the word meaning. (keyword|title match)
                'keyword_class' => array());// tag so we can tell if words are part of a keyword
            }, $anchor_words);

        // first, find any target keyword matches
        if(!empty($suggestion['matched_target_keywords'])){
            foreach($suggestion['matched_target_keywords'] as $kword_ind => $keyword){
                $pos = Wpil_Word::mb_strpos($temp_sentence, $keyword->stemmed);
                if(false !== $pos){
                    // get the string before and after the keyword
                    $before = mb_substr($temp_sentence, 0, $pos);
                    $after  = mb_substr($temp_sentence, ($pos + mb_strlen($keyword->stemmed)));

                    // now explode all of the strings to get the positions
                    $before = explode(' ', $before);
                    $keyword_bits = explode(' ', $keyword->stemmed);
                    $after  = explode(' ', $after);

                    //
                    $offset = (count($before) > 0) ? count($before) - 1: 0;

                    // and map the pieces so we can tell where the keyword is
                    foreach($keyword_bits as $ind => $bit){
                        $ind2 = ($ind + $offset);
                        $word_positions[$ind2]['value'] += 20;
                        $word_positions[$ind2]['significance'][] = 'keyword';
                        $word_positions[$ind2]['keyword_class'][] = 'keyword-' . $kword_ind;
                    }
                }
            }
        }

        // next get the matched title words
        if($use_slug){
            $title_words = $suggestion['post']->getSlugWords();
        }else{
            $title_words = $suggestion['post']->getTitle();
        }
        $stemmed_title_words = Wpil_Word::getStemmedWords(Wpil_Word::strtolower($title_words));
        $title_count = count($stemmed_title_words);
        $position_count = count($word_positions);

        foreach($stemmed_title_words as $title_ind => $title_word){
            foreach($word_positions as $ind => $dat){
                if($dat['word'] === $title_word){
                    $word_positions[$ind]['value'] += 1;
                    $word_positions[$ind]['significance'][] = 'title-word';

                    // if this is the first word in the anchor & the first word in the post title
                    if($title_ind === 0 && $ind === 0){
                        // note it since it's more likely to be important
                        $word_positions[$ind]['significance'][] = 'first-title-word';
                        $word_positions[$ind]['significance'][] = 'title-position-match';
                        $word_positions[$ind]['value'] += 1;
                    }elseif($title_ind === ($title_count - 1) && $ind === ($position_count - 1)){
                        // if this is the last word anchor & the last word in the post title
                        // note it since it's more likely to be important
                        $word_positions[$ind]['significance'][] = 'last-title-word';
                        $word_positions[$ind]['significance'][] = 'title-position-match';
                        $word_positions[$ind]['value'] += 1;
                    }
                }
            }
        }

        // now that we've mapped the word positions, it's time to begin working on what words to remove.
        // first check the easy ones, are there any words on the edges of the sentence that aren't keywords
        $end = end($word_positions);
        $start = reset($word_positions);

        // check the end first
        if( in_array('title-word', $end['significance'], true) &&           // if it's a title word
            !in_array('last-title-word', $end['significance'], true) &&     // it's not the word in the post title
            !in_array('keyword', $end['significance'], true)                // and it's not a keyword
        ){
            // remove the last word from the possible anchor
            $word_positions = array_slice($word_positions, 0, count($word_positions) - 1);

            // remove any insignificant words that result
            $end = end($word_positions);
            if(empty($end['significance'])){
                $word_ind = (count($word_positions) - 1);
                while(empty($word_positions[$word_ind]) && $word_ind > 0){
                    $word_positions = array_slice($word_positions, 0, $word_ind);
                    $word_ind--;
                }
            }

            // if the proposed anchor is shorter than the limit
            if(count($word_positions) <= self::get_max_anchor_length()){
                // update the suggestion words
                $new_words = array_filter(array_map(function($data){ return (!empty($data['significance'])) ? $data['word']: false; }, $word_positions));
                $suggestion['words'] = $new_words;
                // say how long the suggestion is
                $suggestion['length'] = count($word_positions);
                // and return the suggestion
                return $suggestion;
            }
        }

        if( in_array('title-word', $start['significance'], true) && 
            !in_array('first-title-word', $start['significance'], true) && 
            !in_array('keyword', $start['significance'], true)
        ){
            // remove the first word from the possible anchor
            $word_positions = array_slice($word_positions, 1);

            // remove any insignificant words that result
            $start = reset($word_positions);
            if(empty($start['significance'])){
                $word_count = count($word_positions);
                $loop = 0;
                while(empty($word_positions[0]) && $loop < $word_count){
                    $word_positions = array_slice($word_positions, 1);
                    $loop++;
                }
            }

            // if the proposed anchor is shorter than the limit
            if(count($word_positions) <= self::get_max_anchor_length()){
                // update the suggestion words
                $new_words = array_filter(array_map(function($data){ return (!empty($data['significance'])) ? $data['word']: false; }, $word_positions));
                $suggestion['words'] = $new_words;
                // say how long the suggestion is
                $suggestion['length'] = count($word_positions);
                // and return the suggestion
                return $suggestion;
            }
        }

        // if we've made it this far, we weren't able to remove enough words to fit within the limit
        // Now we have to try and judge which words are most likely to not be missed

        // we'll go around 5 times at the most
        for($run = 0; $run < 5; $run++){
            // get the first and last words in the suggested anchor
            $first = $word_positions[0];
            $last = end($word_positions);

            // check if they're both keyword-words
            if( in_array('keyword', $first['significance'], true) &&
                in_array('keyword', $last['significance'], true))
            {
                // if they are, figure out which is the less valuable keyword and remove it
                $first_score = 0;
                $last_score = 0;

                foreach($word_positions as $dat){
                    $first_class = array_intersect($dat['keyword_class'], $first['keyword_class']);
                    $last_class = array_intersect($dat['keyword_class'], $last['keyword_class']);

                    if(!empty($first_class)){
                        $first_score += $dat['value'];
                    }

                    if(!empty($last_class)){
                        $last_score += $dat['value'];
                    }
                }

                // if the first word's score is lower than the last
                if($first_score < $last_score){
                    // remove words from the start
                    $temp = self::removeStartingWords($first, $word_positions);
                }else{
                    // if the last score is smaller or the same as the first, remove the words from the end
                    $temp = self::removeEndingWords($last, $word_positions);
                }
            }elseif($last['value'] < $first['value']){
                $temp = self::removeEndingWords($last, $word_positions);
            }elseif($last['value'] > $first['value']){
                $temp = self::removeStartingWords($first, $word_positions);
            }else{
                // if both words have the same value, remove the word(s) from the end of the anchor
                $temp = self::removeEndingWords($last, $word_positions);
            }

            // exit the loop if all we've managed to do is delete the text
            if(empty($temp)){
                break;
            }

            // update the word positions with the temp data
            $word_positions = $temp;

            // if the proposed anchor is shorter than the limit
            if(count($word_positions) <= self::get_max_anchor_length()){
                // update the suggestion words
                $new_words = array_filter(array_map(function($data){ return (!empty($data['significance'])) ? $data['word']: false; }, $word_positions));
                $suggestion['words'] = $new_words;
                // say how long the suggestion is
                $suggestion['length'] = count($word_positions);
                // and break out of the loop
                break;
            }
        }

        // now that we're done chewing the anchor text, return the suggestion
        return $suggestion;
    }

    /**
     * Removes words from the start of the suggested anchor
     **/
    private static function removeStartingWords($first, $word_positions){
        $anchr_len = count($word_positions);

        // if the first word is part of a keyword
        if( in_array('keyword', $first['significance'], true) && 
            isset($word_positions[1]) && 
            in_array('keyword', $word_positions[1]['significance'], true)
        ){
            // remove all of the words in this keyword and any insignificant words following it
            $ind = 0;
            $kword_class = $word_positions[$ind]['keyword_class'];
            while($ind < $anchr_len && (in_array('keyword', $word_positions[0]['significance'], true) || empty($word_positions[0]['significance']))){
                $same_class = array_intersect($word_positions[0]['keyword_class'], $kword_class);

                // if the word is part of the keyword(s) we started with or is just a filler word
                if(!empty($same_class) || empty($word_positions[0]['significance'])){
                    // remove it
                    $word_positions = array_slice($word_positions, 1);
                    // and increment the index for another pass
                    $ind++;
                }else{
                    // if we can't remove any more words, exit
                    break;
                }
            }
        }else{
            // if the word isn't part of a keyword, remove it and any insignificant words following it
            $ind = 0;
            $word_positions = array_slice($word_positions, 1);
            while($ind < ($anchr_len - 1) && empty($word_positions[0]['significance'])){
                // if the word is just a filler word
                if(empty($word_positions[0]['significance'])){
                    // remove it
                    $word_positions = array_slice($word_positions, 1);
                    // and increment the index for another pass
                    $ind++;
                }else{
                    break;
                }
            }
        }

        return $word_positions;
    }

    /**
     * Removes words from the end of the suggested anchor
     **/
    private static function removeEndingWords($last, $word_positions){
        $ind = count($word_positions) - 1;

        // if the last word is part of a keyword
        if( in_array('keyword', $last['significance'], true) && 
            isset($word_positions[$ind]) && 
            in_array('keyword', $word_positions[$ind]['significance'], true)
        ){
            // remove all of the words in this keyword and any insignificant words preceeding it
            $kword_class = $word_positions[$ind]['keyword_class'];
            while($ind >= 0 && (in_array('keyword', $word_positions[$ind]['significance'], true) || empty($word_positions[$ind]['significance']))){
                $same_class = array_intersect($word_positions[$ind]['keyword_class'], $kword_class);

                // if the word is part of the keyword(s) we started with or is just a filler word
                if(!empty($same_class) || (isset($word_positions[$ind]) && empty($word_positions[$ind]['significance']))){
                    // remove it
                    $word_positions = array_slice($word_positions, 0, $ind);
                    // and decrement the index for another pass
                    $ind--;
                }else{
                    // if we can't remove any more words, exit
                    break;
                }
            }
        }else{
            // if the word isn't part of a keyword, remove it and any insignificant words preceeding it
            $word_positions = array_slice($word_positions, 0, $ind);
            $ind--;
            while($ind >= 0 && empty($word_positions[$ind]['significance'])){
                // if the word is just a filler word
                if(isset($word_positions[$ind]) && empty($word_positions[$ind]['significance'])){
                    // remove it
                    $word_positions = array_slice($word_positions, 0, $ind);
                    // and decrement the index for another pass
                    $ind--;
                }else{
                    break;
                }
            }
        }

        return $word_positions;
    }

    /**
     * Add anchors to sentences
     *
     * @param $sentences
     * @return mixed
     */
    public static function addAnchors($phrases, $outbound = false)
    {
        if(empty($phrases)){
            return array();
        }

        $post = Wpil_Base::getPost();
        $used_anchors = ($_POST['type'] === 'outbound_suggestions') ? Wpil_Post::getAnchors($post) : array();
        $nbsp = urldecode('%C2%A0');
        $post_target_keywords = Wpil_Word::strtolower(implode(' ', Wpil_TargetKeyword::get_active_keyword_list($post->id, $post->type)));

        $ignored_words = Wpil_Settings::getIgnoreWords();
        foreach ($phrases as $key_phrase => $phrase) {
            //prepare rooted words array from phrase
            $words = trim(preg_replace('/\s+|'.$nbsp.'/', ' ', $phrase->text));
            $words = $words_real = (!self::isAsianText()) ? array_map(function($a){ return trim($a, '():\''); /* remove the letters that going to be marked as "non-word chars" later */}, explode(' ', $words)) : mb_str_split(trim($words));
            foreach ($words as $key => $value) {
                $value = Wpil_Word::removeEndings($value, ['[', ']', '(', ')', '{', '}', '.', ',', '!', '?', '\'', ':', '"']);
                if (!empty($_REQUEST['keywords']) || !in_array($value, $ignored_words) || false !== strpos($post_target_keywords, Wpil_Word::strtolower($value))) {
                    $words[$key] = Wpil_Stemmer::Stem(Wpil_Word::strtolower(strip_tags($value)));
                } else {
                    unset($words[$key]);
                }
            }

            foreach ($phrase->suggestions as $suggestion_key => $suggestion) {
                //get min and max words position in the phrase
                $anchor_indexes = self::getSuggestionAnchorWords($words, $suggestion->words, true);

                if(empty($anchor_indexes) || (empty($anchor_indexes['min']) && empty($anchor_indexes['max']))){
                    // if it can't, remove it from the list
                    unset($phrase->suggestions[$suggestion_key]);
                    // and proceed
                    continue;
                }

                $min = $anchor_indexes['min'];
                $max = $anchor_indexes['max'];

                // check to see if we can get a link in this suggestion
                $has_words = array_slice($words_real, $min, $max - $min + 1); // TODO: CHECK THIS AND MAKE SURE I DON"T HAVE IT BACKWARDS AND I SHOULD BE CHECKING MIN SIZE!
                if(empty($has_words) || ($max - $min) > Wpil_Settings::getSuggestionMaxAnchorSize()){
                    // if it can't, remove it from the list
                    unset($phrase->suggestions[$suggestion_key]);
                    // and proceed
                    continue;
                }

                //get anchors and sentence with anchor
                $anchor = ''; // TODO: Maybe rework this so that we first insert the link in the ->src, and then insert that in the ->sentence_src. Curently, it's possible to have sentences that contain 2 of the same keyword, but split on styling tags to show up at once. To fix this now, I'll filter out the duplicates, but since the secondary sentence was found, it is something to think aobut. Also I want to remember this just in case I have to go chasing duplicate suggestions again!
                $sentence_with_anchor = '<span class="wpil_word">' . implode('</span> <span class="wpil_word">', explode(' ', str_replace($nbsp, ' ', strip_tags($phrase->sentence_src, '<b><i><u><strong><em><code>')))) . '</span>';
                $sentence_with_anchor = str_replace(
                    [   ',</span>', 
                        '<span class="wpil_word">(', 
                        ')</span>', 
                        ':</span>', 
                        '<span class="wpil_word">\'', 
                        '\'</span>'
                    ], 
                    [   '</span><span class="wpil_word no-space-left wpil-non-word">,</span>', 
                        '<span class="wpil_word no-space-right wpil-non-word">(</span><span class="wpil_word">', 
                        '</span><span class="wpil_word no-space-left wpil-non-word">)</span>', 
                        '</span><span class="wpil_word no-space-left wpil-non-word">:</span>', 
                        '<span class="wpil_word no-space-right wpil-non-word">\'</span><span class="wpil_word">', 
                        '</span><span class="wpil_word no-space-left wpil-non-word">\'</span>'
                    ], $sentence_with_anchor);
                $sentence_with_anchor = self::formatTags($sentence_with_anchor);
                if ($max >= $min) {
                    if ($max == $min) {
                        $anchor = '<span class="wpil_word">' . $words_real[$min] . '</span>';
                        $to = '<a href="%view_link%">' . $anchor . '</a>';
                        $sentence_with_anchor = preg_replace('/'.preg_quote($anchor, '/').'/', $to, $sentence_with_anchor, 1);
                    } else {
                        $anchor = '<span class="wpil_word">' . implode('</span> <span class="wpil_word">', array_slice($words_real, $min, $max - $min + 1)) . '</span>';
                        $from = [
                            '<span class="wpil_word">' . $words_real[$min] . '</span>',
                            '<span class="wpil_word">' . $words_real[$max] . '</span>'
                        ];
                        $to = [
                            '<a href="%view_link%"><span class="wpil_word">' . $words_real[$min] . '</span>',
                            '<span class="wpil_word">' . $words_real[$max] . '</span></a>'
                        ];

                        $start_counts = substr_count($sentence_with_anchor, $from[0]);
                        if($start_counts > 1){
                            preg_match_all('/'.preg_quote($from[0], '/').'/', $sentence_with_anchor, $starts, PREG_OFFSET_CAPTURE);
                            preg_match('/'.preg_quote($from[1], '/').'/', $sentence_with_anchor, $end, PREG_OFFSET_CAPTURE);

                            if(!empty($starts) && !empty($end)){
                                $closest = 0;

                                foreach($starts[0] as $start){
                                    if($start[1] > $closest && $start[1] < $end[0][1]){
                                        $closest = $start[1];
                                    }
                                }

                                $maybe_start = substr($sentence_with_anchor, 0, $closest);

                                if(!empty($maybe_start) && !empty(preg_match('/'.preg_quote($maybe_start . $from[0], '/').'/', $sentence_with_anchor))){
                                    $from[0] = ($maybe_start . $from[0]);
                                    $to[0] = ($maybe_start . $to[0]);
                                }
                            }
                        }

                        $sentence_with_anchor = preg_replace('/'.preg_quote($from[0], '/').'/', $to[0], $sentence_with_anchor, 1);
                        $begin = strpos($sentence_with_anchor, '<a ');
                        if ($begin !== false) {
                            $first = substr($sentence_with_anchor, 0, $begin);
                            $second = substr($sentence_with_anchor, $begin);
                            $second = preg_replace('/'.preg_quote($from[1], '/').'/', $to[1], $second, 1);
                            $sentence_with_anchor = $first . $second;
                        }
                    }
                }

                // if a link couldn't be added to the sentence
                if(false === strpos($sentence_with_anchor, '<a ') || false === strpos($sentence_with_anchor, '</a>')){
                    // remove it from the list
                    unset($phrase->suggestions[$suggestion_key]);
                    // and proceed
                    continue;
                }

                self::setSentenceSrcWithAnchor($suggestion, $phrase->sentence_src, $words_real[$min], $words_real[$max]);

                //add results to suggestion
                $suggestion->anchor = $anchor;

                if (in_array(strip_tags($anchor), $used_anchors)) {
                    unset($phrases[$key_phrase]);
                }

                $suggestion->sentence_with_anchor = self::setSuggestionTags($sentence_with_anchor);
                $suggestion->original_sentence_with_anchor = $suggestion->sentence_with_anchor;
            }

            // if there are no suggestions, remove the phrase
            if(empty($phrase->suggestions)){
                unset($phrases[$key_phrase]);
            }
        }

        // if these are outbound suggestions
        if($outbound){
            // merge different suggestions from the same source sentence into the same sentence
            $phrases = self::mergeSourceTextPhrases($phrases);
        }else{
            // remove any suggestions that have the exact same suggestion text and anchor
            $known_suggestions = array();
            foreach($phrases as $key_phrase => $phrase){
                foreach($phrase->suggestions as $ind => $suggestion){
                    $key = md5($suggestion->original_sentence_with_anchor . $suggestion->post->id);
                    if(!isset($known_suggestions[$key])){
                        $known_suggestions[$key] = true;
                    }else{
                        unset($phrase->suggestions[$ind]);
                    }
                }

                // if there are no suggestions, remove the phrase
                if(empty($phrase->suggestions)){
                    unset($phrases[$key_phrase]);
                }
            }
        }

        return $phrases;
    }

    public static function formatTags($sentence_with_anchor){
        $tags = array(
            '<span class="wpil_word"><b>',
            '<span class="wpil_word"><i>',
            '<span class="wpil_word"><u>',
            '<span class="wpil_word"><strong>',
            '<span class="wpil_word"><em>',
            '<span class="wpil_word"><code>',
            '<span class="wpil_word"></b>',
            '<span class="wpil_word"></i>',
            '<span class="wpil_word"></u>',
            '<span class="wpil_word"></strong>',
            '<span class="wpil_word"></em>',
            '<span class="wpil_word"></code>',
            '<b></span>',
            '<i></span>',
            '<u></span>',
            '<strong></span>',
            '<em></span>',
            '<code></span>',
            '</b></span>',
            '</i></span>',
            '</u></span>',
            '</strong></span>',
            '</em></span>',
            '</code></span>'
        );

        // the replace tokens of the tags
        $replace_tags = array(
            '<span class="wpil_word wpil_suggestion_tag open-tag wpil-bold-open wpil-bold">PGI+</span><span class="wpil_word">', // these are the base64ed versions of the tags so we can process them later
            '<span class="wpil_word wpil_suggestion_tag open-tag wpil-ital-open wpil-ital">PGk+</span><span class="wpil_word">',
            '<span class="wpil_word wpil_suggestion_tag open-tag wpil-under-open wpil-under">PHU+</span><span class="wpil_word">',
            '<span class="wpil_word wpil_suggestion_tag open-tag wpil-strong-open wpil-strong">PHN0cm9uZz4=</span><span class="wpil_word">',
            '<span class="wpil_word wpil_suggestion_tag open-tag wpil-em-open wpil-em">PGVtPg==</span><span class="wpil_word">',
            '<span class="wpil_word wpil_suggestion_tag open-tag wpil-code-open wpil-code">PGNvZGU+</span><span class="wpil_word">',
            '<span class="wpil_word wpil_suggestion_tag close-tag wpil-bold-close wpil-bold">PC9iPg==</span><span class="wpil_word">',
            '<span class="wpil_word wpil_suggestion_tag close-tag wpil-ital-close wpil-ital">PC9pPg==</span><span class="wpil_word">',
            '<span class="wpil_word wpil_suggestion_tag close-tag wpil-under-close wpil-under">PC91Pg==</span><span class="wpil_word">',
            '<span class="wpil_word wpil_suggestion_tag close-tag wpil-strong-close wpil-strong">PC9zdHJvbmc+</span><span class="wpil_word">',
            '<span class="wpil_word wpil_suggestion_tag close-tag wpil-em-close wpil-em">PC9lbT4=</span><span class="wpil_word">',
            '<span class="wpil_word wpil_suggestion_tag close-tag wpil-code-close wpil-code">PC9jb2RlPg==</span><span class="wpil_word">',
            '</span><span class="wpil_word wpil_suggestion_tag open-tag wpil-bold-open wpil-bold">PGI+</span>', 
            '</span><span class="wpil_word wpil_suggestion_tag open-tag wpil-ital-open wpil-ital">PGk+</span>',
            '</span><span class="wpil_word wpil_suggestion_tag open-tag wpil-under-open wpil-under">PHU+</span>',
            '</span><span class="wpil_word wpil_suggestion_tag open-tag wpil-strong-open wpil-strong">PHN0cm9uZz4=</span>',
            '</span><span class="wpil_word wpil_suggestion_tag open-tag wpil-em-open wpil-em">PGVtPg==</span>',
            '</span><span class="wpil_word wpil_suggestion_tag open-tag wpil-code-open wpil-code">PGNvZGU+</span>',
            '</span><span class="wpil_word wpil_suggestion_tag close-tag wpil-bold-close wpil-bold">PC9iPg==</span>',
            '</span><span class="wpil_word wpil_suggestion_tag close-tag wpil-ital-close wpil-ital">PC9pPg==</span>',
            '</span><span class="wpil_word wpil_suggestion_tag close-tag wpil-under-close wpil-under">PC91Pg==</span>',
            '</span><span class="wpil_word wpil_suggestion_tag close-tag wpil-strong-close wpil-strong">PC9zdHJvbmc+</span>',
            '</span><span class="wpil_word wpil_suggestion_tag close-tag wpil-em-close wpil-em">PC9lbT4=</span>',
            '</span><span class="wpil_word wpil_suggestion_tag close-tag wpil-code-close wpil-code">PC9jb2RlPg==</span>',
        );

        return str_replace($tags, $replace_tags, $sentence_with_anchor);
    }

    /**
     * Add anchor to the sentence source
     *
     * @param $suggestion
     * @param $sentence
     * @param $word_start
     * @param $word_end
     */
    public static function setSentenceSrcWithAnchor(&$suggestion, $sentence, $word_start, $word_end)
    {
        $sentence .= ' ';
        $begin = strpos($sentence, $word_start . ' ');
        if($begin === false){
            $begin = strpos($sentence, $word_start);
        }
        while($begin && substr($sentence, $begin - 1, 1) !== ' ') {
            $begin--;
        }

        $end = strpos($sentence, $word_end . ' ', $begin);
        if(false === $end){
            $end = strpos($sentence, $word_end, $begin);
        }
        $end += strlen($word_end);
        while($end < strlen($sentence) && substr($sentence, $end, 1) !== ' ') {
            $end++;
        }

        $anchor = substr($sentence, $begin, $end - $begin);
        $replace = '<a href="%view_link%">' . $anchor . '</a>';
        $suggestion->sentence_src_with_anchor = preg_replace('/'.preg_quote($anchor, '/').'/', $replace, trim($sentence), 1);

    }

    public static function setSuggestionTags($sentence_with_anchor){
        // if there isn't a tag inside the suggested text, return it
        if(false === strpos($sentence_with_anchor, 'wpil_suggestion_tag')){
            return $sentence_with_anchor;
        }

        // see if the tag is inside the link
        $link_start = Wpil_Word::mb_strpos($sentence_with_anchor, '<a href="%view_link%"');
        $link_end = Wpil_Word::mb_strpos($sentence_with_anchor, '</a>', $link_start);
        $link_length = ($link_end + 4 - $link_start);
        $link = mb_substr($sentence_with_anchor, $link_start, $link_length);

        // if it's not or the open and close tags are in the link, return the link
        if(false === strpos($link, 'wpil_suggestion_tag') || (false !== strpos($link, 'open-tag') && false !== strpos($link, 'close-tag'))){ // todo make this tag specific. As it is now, we _could_ get the opening of one tag and the closing tag of another one since we're only looking for open and close tags. But considering that we've not had much trouble at all from the prior system, this isn't a priority.
            return $sentence_with_anchor;
        }

        // if we have the opening tag inside the link, move it right until it's outside the link
        if(false !== strpos($link, 'open-tag')){
            // get the tag start
            $open_tag = Wpil_Word::mb_strpos($sentence_with_anchor, '<span class="wpil_word wpil_suggestion_tag open-tag', $link_start);
            // extract the tag
            $tag = mb_substr($sentence_with_anchor, $open_tag, (Wpil_Word::mb_strpos($sentence_with_anchor, '</span>', $open_tag) + 7) - $open_tag);

            // get the text before the link
            $before = mb_substr($sentence_with_anchor, 0, $link_start);
            // get the text after the link
            $after = mb_substr($sentence_with_anchor, ($link_end + 4));
            // replace the tag in the link
            $link = mb_ereg_replace(preg_quote($tag), '', $link);
            // rebuild the sentence with the tag out side the link
            $sentence_with_anchor = ($before . $link . $tag . $after);

            // and re calibrate the search variables in case there's more that needs to be done
            $link_start = Wpil_Word::mb_strpos($sentence_with_anchor, '<a href="%view_link%"');
            $link_end = Wpil_Word::mb_strpos($sentence_with_anchor, '</a>', $link_start);
            $link_length = ($link_end + 4 - $link_start);
            $link = mb_substr($sentence_with_anchor, $link_start, $link_length);
        }

        // if we have the closing tag inside the link, move it left until it's outside the link
        if(false !== strpos($link, 'close-tag')){
            // get the tag start
            $close_tag = Wpil_Word::mb_strpos($sentence_with_anchor, '<span class="wpil_word wpil_suggestion_tag close-tag', $link_start);
            // extract the tag
            $tag = mb_substr($sentence_with_anchor, $close_tag, (Wpil_Word::mb_strpos($sentence_with_anchor, '</span>', $close_tag) + 7) - $close_tag);
            // replace the tag
            $sentence_with_anchor = mb_ereg_replace(preg_quote($tag), '', $sentence_with_anchor);
            // get the points before and after the link opening tag
            $before = mb_substr($sentence_with_anchor, 0, $link_start);
            $after = mb_substr($sentence_with_anchor, $link_start);
            // and insert the cloasing tag just before the link
            $sentence_with_anchor = ($before . $tag . $after);
        }

        return $sentence_with_anchor;
    }

    /**
     * Calculates the words used for anchor texts
     **/
    public static function getSuggestionAnchorWords($text, $words = array(), $return_indexes = false){
        // make sure that we're dealing with an array of words
        if(is_string($text) && !empty($text)){
            $text = Wpil_Word::getWords($text);
        }

        // stem the sentence words
        if(is_array($text) && !empty($text)){
            $stemmed_phrase_words = array_map(array('Wpil_Stemmer', 'Stem'), $text);
        }else{
            return false;
        }

        // make sure that the words we're looking at are stemmed too
        $words = array_map(array('Wpil_Stemmer', 'Stem'), $words);

        $min = count($stemmed_phrase_words);
        $max = 0;
        foreach ($words as $word) {
            if (in_array($word, $stemmed_phrase_words)) {
                $pos = array_search($word, $stemmed_phrase_words);
                $min = $pos < $min ? $pos : $min;
                $max = $pos > $max ? $pos : $max;
            }
        }
        $i = 0;
        while($i < 5 && $word_occurances = array_count_values(array_slice(array_filter($stemmed_phrase_words), $min, $max - $min + 1))){ // make sure that we're checking the current sentence for duplicate words
            if( array_key_exists($min, $stemmed_phrase_words) && 
                isset($word_occurances[$stemmed_phrase_words[$min]]) &&
                $word_occurances[$stemmed_phrase_words[$min]] > 1
            ){
                // get a list of the words past the current min
                $word_map = array_slice($stemmed_phrase_words, $min + 1, null, true);
                // go over each word
                foreach($word_map as $key => $stemmed_word){
                    // when we've found the next word in the sentence that is also one of the suggestion words
                    if (in_array($stemmed_word, $words)) {
                        // set the min for that word
                        $min = $key;
                        // and break the loop so we can check the next word
                        break;
                    }
                }
            }elseif( array_key_exists($max, $stemmed_phrase_words) && 
                isset($word_occurances[$stemmed_phrase_words[$max]]) &&
                $word_occurances[$stemmed_phrase_words[$max]] > 1
            ){
                $old_max = $max;
                $max = 0;
                foreach ($words as $word) {
                    if (in_array($word, $stemmed_phrase_words)) {
                        $pos = array_search($word, $stemmed_phrase_words);
                        $max = $pos > $max && $pos < $old_max ? $pos : $max;
                    }
                }
            }else{
                break;
            }

            $i++;
        }

        // if min is larger than max
        if($min > $max){
            // reverse them
            $old_max = $max;
            $max = $min;
            $min = $old_max;
        }

        // try obtaining the anchor text
        $anchor_words = array_slice($stemmed_phrase_words, $min, $max - $min + 1);

        // if we aren't able to
        if(empty($anchor_words)){
            // get the suggestion text start and end points
            $word_occurances = array_count_values(array_filter($stemmed_phrase_words));

            // try the old method of getting anchor text
            $intersect = array_keys(array_intersect($stemmed_phrase_words, $words));
            $min = current($intersect);
            $max = end($intersect);

            // check to make sure that if there's duplicates of the words
            // we use the anchor text with the smallest possible length
            $j = 0;
            while($j < 5){
                if( $min !== false &&
                    array_key_exists($min, $stemmed_phrase_words) && 
                    isset($word_occurances[$stemmed_phrase_words[$min]]) &&
                    $word_occurances[$stemmed_phrase_words[$min]] > 1
                ){
                    reset($intersect);
                    $min = next($intersect);
                }elseif( 
                    $max !== false && 
                    array_key_exists($max, $stemmed_phrase_words) && 
                    isset($word_occurances[$stemmed_phrase_words[$max]]) &&
                    $word_occurances[$stemmed_phrase_words[$max]] > 1
                ){
                    reset($intersect);
                    end($intersect);
                    $max = prev($intersect);
                }else{
                    break;
                }

                $j++;
            }

            $anchor_words = array_slice($stemmed_phrase_words, $min, $max - $min + 1);
        }

        if($return_indexes){
            return array('min' => $min, 'max' => $max);
        }else{
            return $anchor_words;
        }
    }

    /**
     * Merges phrases with the same source text into the same suggestion pool.
     * That way, users get a dropdown of different suggestions instead of a number of loose suggestions.
     * 
     * @param array $phrases 
     * @return array $merged_phrases
     **/
    public static function mergeSourceTextPhrases($phrases){
        $merged_phrases = array();
        $phrase_key_index = array();
        foreach($phrases as $phrase_key => $data){
            $phrase_key_index[$data->sentence_text] = $phrase_key;
        }

        foreach($phrases as $phrase_key => $data){
            $key = $phrase_key_index[$data->sentence_text];
            if(isset($merged_phrases[$key])){
                $merged_phrases[$key]->suggestions = array_merge($merged_phrases[$key]->suggestions, $data->suggestions);
            }else{
                $merged_phrases[$key] = $data;
            }
        }

        return $merged_phrases;
    }

    /**
     * Get Inbound internal links page search keywords
     *
     * @param $post
     * @return array
     */
    public static function getKeywords($post, $include_target_keywords = false)
    {
        $keywords = array();
        if(!empty($_REQUEST['keywords'])){
            $keywords = array_map(function($word){ return trim($word); }, explode(";", sanitize_text_field($_POST['keywords'])));
        }

        $keywords = array_filter($keywords);

        if(empty($keywords)){
            // get if the user wants to use the slug for suggestions instead of the title
            $use_slug = Wpil_Settings::use_post_slug_for_suggestions();

            if($use_slug){
                $words = self::getPartialTitleWords($post->getSlugWords());
            }else{
                $words = self::getPartialTitleWords($post->getTitle());
            }

            if($include_target_keywords){
                $keyword_string = Wpil_TargetKeyword::get_active_keyword_string($post->id, $post->type);
                $words .= ' ' . $keyword_string;
                $words .= ' ' . Wpil_Word::getStemmedSentence(Wpil_Word::remove_accents($keyword_string), true);
            }

            $words = array_flip(array_flip(Wpil_Word::cleanIgnoreWords(explode(' ', Wpil_Word::strtolower($words)))));
            $words = array_filter($words, function($word){ return (mb_strlen($word) > 2) ? true: false;});
            $keywords = array(implode(' ', $words));
        }

        return $keywords;
    }

    /**
     * Search posts with common words in the content and return an array of all found post ids
     *
     * @param $keyword
     * @param $excluded_posts
     * @return array
     */
    public static function getInboundSuggestedPosts($keyword, $excluded_posts)
    {
        global $wpdb;

        $post_types = implode("','", self::getSuggestionPostTypes());

        $search_terms = '';
        $selected_terms = '';
        $term_taxonomy_ids = array();
        $cat_ids = array();
        $tag_ids = array();
        if (!empty(Wpil_Settings::get_suggestion_filter('same_category'))) {
            $post = Wpil_Base::getPost();
            if ($post->type === 'post') {
                if (!empty(Wpil_Settings::get_suggestion_filter('selected_category'))) {
                    $cat_ids = array_merge($cat_ids, self::get_selected_categories());
                } else {
                    $taxes = get_object_taxonomies(get_post($post->id));
                    $query_taxes = array();
                    foreach($taxes as $tax){
                        if(get_taxonomy($tax)->hierarchical){
                            $query_taxes[] = $tax;
                        }
                    }
                    $categories = wp_get_object_terms($post->id, $query_taxes, ['fields' => 'tt_ids']);
                    if(!empty($categories) && !is_a($categories, 'WP_Error')){
                        $cat_ids = array_merge($cat_ids, $categories);
                    }
                }
            }

            if(!empty($cat_ids)){
                $term_taxonomy_ids[] = $cat_ids;
            }
        }

        if (!empty(Wpil_Settings::get_suggestion_filter('same_tag'))) {
            $post = Wpil_Base::getPost();
            if ($post->type === 'post') {
                if (!empty(Wpil_Settings::get_suggestion_filter('selected_tag'))) {
                    $tag_ids = array_merge($tag_ids, self::get_selected_tags());
                } else {
                    $taxes = get_object_taxonomies(get_post($post->id));
                    $query_taxes = array();
                    foreach($taxes as $tax){
                        if(empty(get_taxonomy($tax)->hierarchical)){
                            $query_taxes[] = $tax;
                        }
                    }
                    $tags = wp_get_object_terms($post->id, $query_taxes, ['fields' => 'tt_ids']);
                    if(!empty($tags) && !is_a($tags, 'WP_Error')){
                        $tag_ids = array_merge($tag_ids, $tags);
                    }
                }
            }

            if(!empty($tag_ids)){
                $term_taxonomy_ids[] = $tag_ids;
            }
        }

        $selected_terms .= (isset($term_taxonomy_ids[0])) ? " AND ID in (select object_id from {$wpdb->term_relationships} where term_taxonomy_id in (" . implode(',', $term_taxonomy_ids[0]) . ")) " : "";
        $selected_terms .= (isset($term_taxonomy_ids[1])) ? " AND ID in (select object_id from {$wpdb->term_relationships} where term_taxonomy_id in (" . implode(',', $term_taxonomy_ids[1]) . ")) " : "";

        //get all posts contains words from post title
        $post_content = self::getInboundPostContent($keyword);

        $include_ids = array();
        $custom_fields = self::getInboundCustomFields($keyword, $term_taxonomy_ids);
        if (!empty($custom_fields)) {
            $posts = $custom_fields;
            if(!empty($excluded_posts)){
                foreach ($posts as $key => $included_post) {
                    if (in_array($included_post, $excluded_posts)) {
                        unset($posts[$key]);
                    }
                }
            }

            if (!empty($posts)) {
                $include_ids = $posts;
            }
        }

        //WPML
        $post = Wpil_Base::getPost();
        $same_language_posts = array();
        $multi_lang = false;
        if ($post->type == 'post') {
            if (Wpil_Settings::translation_enabled()) {
                $multi_lang = true;
                $same_language_posts = Wpil_Post::getSameLanguagePosts($post->id);
            }
        }

        $statuses_query = Wpil_Query::postStatuses();

        $related_ids = array();
        if($post->type === 'post' && !empty(Wpil_Settings::get_suggestion_filter('same_parent'))){
            $related_ids = Wpil_Toolbox::get_related_post_ids($post);
        }

        // create the array of posts
        $posts = array();

        // create the string of excluded posts
        $excluded_posts = implode(',', $excluded_posts);

        // if the user is age limiting the posts, get the query limit string
        $age_query = Wpil_Query::getPostDateQueryLimit();

        // and if the user is restricting suggestions from posts created by specific roles
        $user_role_ignore = Wpil_Query::get_ignore_user_role_suggestions_query();

        // if there are ids to process
        if(!empty($same_language_posts) && $multi_lang){
            // if there are related post ids
            if(!empty($related_ids)){
                // make sure that we only search for those
                $same_language_posts = array_intersect($same_language_posts, $related_ids);
            }

            // chunk the ids to query so we don't ask for too many
            $id_batches = array_chunk($same_language_posts, 2000);
            foreach($id_batches as $batch){
                $include = " AND ID IN (" . implode(', ', $batch) . ") ";
                $batch_ids = $wpdb->get_col("SELECT `ID` FROM {$wpdb->posts} WHERE post_type IN ('{$post_types}') $statuses_query AND ID NOT IN ({$excluded_posts}) {$age_query} {$user_role_ignore} {$selected_terms} {$post_content} $include ORDER BY ID DESC");

                if(!empty($batch_ids)){
                    $posts = array_merge($posts, $batch_ids);
                }
            }
        }elseif(empty($multi_lang)){
            $related = '';
            if(!empty($related_ids)){
                $related = 'AND ID IN (' . implode(',', $related_ids) . ')';
            }
            $posts = $wpdb->get_col("SELECT `ID` FROM {$wpdb->posts} WHERE post_type IN ('{$post_types}') $statuses_query AND ID NOT IN ({$excluded_posts}) {$age_query} {$user_role_ignore} {$selected_terms} {$post_content} {$related} ORDER BY ID DESC");
        }

        if(!empty($include_ids)){
            $posts = array_merge($posts, $include_ids);
        }

        // get any posts from alternate storage locations
        $posts = self::getPostsFromAlternateLocations($posts, $keyword, $excluded_posts, $term_taxonomy_ids);

        // if there are posts found, remove any duplicate ids and posts hidden by redirects
        if(!empty($posts)){
            $redirected = Wpil_Settings::getRedirectedPosts(true); // TODO: Rework this so that posts are only removed if the redirect is hiding hte post. Some simply have updated URLs and the redirect is to accomodate that
            $post_ids = array();
            foreach($posts as $post){
                // if the post isn't hidden behind a redirect
                if(!isset($redirected[$post])){
                    // if we're doing multi-lang suggestions and this is in the language list
                    if($multi_lang && in_array($post, $same_language_posts)){
                        // add it to the list of posts to process
                        $post_ids[$post] = $post;
                    }elseif(false === $multi_lang){
                        // if we're not doing multilanguage processing, add the post directly
                        $post_ids[$post] = $post;
                    }
                }
            }

            $posts = array_values($post_ids);
        }

        return $posts;
    }

    public static function getInboundPostContent($keyword, $column = 'post_content')
    {
        global $wpdb;

        //get unique words from post title
        $words = (!self::isAsianText()) ? Wpil_Word::getWords($keyword) : mb_str_split(trim($keyword));
        $words = Wpil_Word::cleanIgnoreWords(array_unique($words));
        $words = array_values(array_filter($words));

        if (empty($words)) {
            return '';
        }

        if($column !== 'post_content'){
            $column = sanitize_text_field($column);
        }

        $post_content = "";
        foreach($words as $ind => $word){
            $escaped = "%" . $wpdb->esc_like($word) . "%";
            if($ind < 1){
                $post_content .= $wpdb->prepare("AND ({$column} LIKE %s", $escaped);
            }else{
                $post_content .= $wpdb->prepare(" OR {$column} LIKE %s", $escaped);
            }
        }

        // if we have a post content query string
        if(!empty($post_content)){
            // add the closing bracket for the end of the AND
            $post_content .= ')';
        }

        return $post_content;
    }

    /**
     * Gets the Inbound Suggestable terms for the Inbound Suggestions
     * 
     **/
    public static function get_inbound_suggested_terms($keyword, $post){
        global $wpdb;

        $terms = array();

        // if terms are to be scanned, but the user is restricting suggestions by term, don't search for terms to link to. Only search for terms if:
        if(!empty(Wpil_Settings::getTermTypes()) && // terms have been selected
            empty(Wpil_Settings::get_suggestion_filter('same_category')) && // we're not restricting by category
            empty(Wpil_Settings::get_suggestion_filter('same_parent'))) // we're not restricting to the post's family
        {
            $ignore_posts = Wpil_Settings::getIgnorePosts();
            $results = array();
            $exclude_ids = array();

            if(!empty($ignore_posts)){
                foreach($ignore_posts as $dat){
                    if(false !== strpos($dat, 'term_')){
                        $bits = explode('_', $dat);
                        if(!empty($bits[1])){
                            $exclude_ids[] = $bits[1];
                        }
                    }
                }
            }

            $exclude = "";
            if(!empty($exclude_ids)){
                $exclude_ids = implode(',', $exclude_ids);
                $exclude = " AND t.term_id NOT IN ({$exclude_ids}) ";
            }

            $content = self::getInboundPostContent($keyword, 'tt.description');

            //WPML
            $same_language_terms = array();
            $multi_lang = false;
            $language_ids = "";
            if ($post->type == 'post' && Wpil_Settings::translation_enabled()) {
                $multi_lang = true;
                $same_language_terms = Wpil_Post::getSameLanguageTerms($post->id);
            }

            if($multi_lang){
                $language_ids .= " AND t.term_id IN (" . implode(',', $same_language_terms) . ")";
            }

            $taxonomies = Wpil_Settings::getTermTypes();

            // if there are ids to process
            if(!empty($same_language_terms) && $multi_lang){
                // chunk the ids to query so we don't ask for too many
                $id_batches = array_chunk($same_language_terms, 2000);
                foreach($id_batches as $batch){
                    $include = " AND t.term_id IN (" . implode(', ', $batch) . ") ";
                    $batch_ids = $wpdb->get_col("SELECT t.term_id FROM {$wpdb->prefix}term_taxonomy tt LEFT JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id WHERE tt.taxonomy IN ('" . implode("', '", $taxonomies) . "') {$content} {$include} $exclude");

                    if(!empty($batch_ids)){
                        $results = array_merge($results, $batch_ids);
                    }
                }
            }elseif(empty($multi_lang)){
                $results = $wpdb->get_col("SELECT t.term_id FROM {$wpdb->prefix}term_taxonomy tt LEFT JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id WHERE tt.taxonomy IN ('" . implode("', '", $taxonomies) . "') {$content} {$language_ids} $exclude");
            }

            foreach ($results as $term_id) {
                if(!$multi_lang || ($multi_lang && in_array($term_id, $same_language_terms, true)) ){
                    $terms[$term_id] = $term_id;
                }
            }

            // if there are terms found, pull the id values from the list
            if(!empty($terms)){
                $terms = array_values($terms);
            }
        }

        return $terms;
    }

    /**
     * Gets the posts that store content in locations other than the post_content.
     * Most page builders update post_content as a fallback measure, so we can typically get the content that way.
     * But some items are unique and don't update the post_content.
     **/
    public static function getPostsFromAlternateLocations($posts, $keyword, $exclude_ids, $term_taxonomy_ids = array()){
        global $wpdb;

        $active_post_types = self::getSuggestionPostTypes();

        // if WP Recipes is active and the user wants to add links to the recipe notes
        if(defined('WPRM_POST_TYPE') && in_array('wprm_recipe', $active_post_types)){

            //get unique words from post title
            $words = Wpil_Word::getWords($keyword);
            $words = Wpil_Word::cleanIgnoreWords(array_unique($words));
            $words = array_filter($words);

            if(!empty($words)){
                $selected_terms = "";
                $selected_terms .= (isset($term_taxonomy_ids[0])) ? " AND m.post_id in (select object_id from {$wpdb->term_relationships} where term_taxonomy_id in (" . implode(',', $term_taxonomy_ids[0]) . ")) " : "";
                $selected_terms .= (isset($term_taxonomy_ids[1])) ? " AND m.post_id in (select object_id from {$wpdb->term_relationships} where term_taxonomy_id in (" . implode(',', $term_taxonomy_ids[1]) . ")) " : "";

                $keys = Wpil_Editor_WPRecipe::get_selected_fields();

                if(!empty($keys)){
                    $keys = "'" . implode("','", array_keys($keys)) . "'";
                    $like = '';
                    for($i = 1; $i < count($words); $i++){
                        $like .= 'OR meta_value LIKE %s ';
                    }
                    $meta = $wpdb->prepare("meta_value LIKE %s {$like}", array_map(array('Wpil_Toolbox', 'esc_like'), $words));
                    $results = $wpdb->get_col("SELECT DISTINCT m.post_id AS ID FROM {$wpdb->postmeta} m WHERE `meta_key` IN ({$keys}) AND m.post_id NOT IN ($exclude_ids) {$selected_terms} AND ({$meta})");

                    if(!empty($results)){
                        $posts = array_merge($posts, $results);
                    }
                }
            }
        }

        // get the metafields for the standard builders
        $builder_meta = Wpil_Post::get_builder_meta_keys();

        // if we have builders
        if(!empty($builder_meta)){
            //get unique words from post title
            $words = Wpil_Word::getWords($keyword);
            $words = Wpil_Word::cleanIgnoreWords(array_unique($words));
            $words = array_filter($words);

            // assemble the builder meta into a key array
            $builder_meta = "'" . implode("', '", $builder_meta) . "'";

            // get the age query if the user is limiting the range for linking
            $age_string = Wpil_Query::getPostDateQueryLimit('p');

            // get if the user is restricting suggestions from posts created by specific roles
            $user_role_ignore = Wpil_Query::get_ignore_user_role_suggestions_query('p');

            if(!empty($words)){
                $post_types_p = Wpil_Query::postTypes('p');
                $statuses_query_p = Wpil_Query::postStatuses('p');

                $selected_terms = "";
                $selected_terms .= (isset($term_taxonomy_ids[0])) ? " AND m.post_id in (select object_id from {$wpdb->term_relationships} where term_taxonomy_id in (" . implode(',', $term_taxonomy_ids[0]) . ")) " : "";
                $selected_terms .= (isset($term_taxonomy_ids[1])) ? " AND m.post_id in (select object_id from {$wpdb->term_relationships} where term_taxonomy_id in (" . implode(',', $term_taxonomy_ids[1]) . ")) " : "";

                $like = '';
                for($i = 1; $i < count($words); $i++){
                    $like .= 'OR m.meta_value LIKE %s ';
                }
                $meta = $wpdb->prepare("m.meta_value LIKE %s {$like}", array_map(array('Wpil_Toolbox', 'esc_like'), $words));
                $results = $wpdb->get_col("SELECT DISTINCT m.post_id AS ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE m.meta_key IN ({$builder_meta}) {$selected_terms} {$age_string} {$user_role_ignore} {$post_types_p} {$statuses_query_p} AND ({$meta})");

                if(!empty($results)){
                    $posts = array_merge($posts, $results);
                }
            }
        }

        // if WooCommerce is active
        if(defined('WC_PLUGIN_FILE') && in_array('product', $active_post_types)){
            //get unique words from post title
            $words = Wpil_Word::getWords($keyword);
            $words = Wpil_Word::cleanIgnoreWords(array_unique($words));
            $words = array_filter($words);

            // get the age query if the user is limiting the range for linking
            $age_string = Wpil_Query::getPostDateQueryLimit('p');

            // get if the user is restricting suggestions from posts created by specific roles
            $user_role_ignore = Wpil_Query::get_ignore_user_role_suggestions_query('p');

            $selected_terms = "";
            $selected_terms .= (isset($term_taxonomy_ids[0])) ? " AND ID in (select object_id from {$wpdb->term_relationships} where term_taxonomy_id in (" . implode(',', $term_taxonomy_ids[0]) . ")) " : "";
            $selected_terms .= (isset($term_taxonomy_ids[1])) ? " AND ID in (select object_id from {$wpdb->term_relationships} where term_taxonomy_id in (" . implode(',', $term_taxonomy_ids[1]) . ")) " : "";

            if(!empty($words)){
                $like = '';
                for($i = 1; $i < count($words); $i++){
                    $like .= 'OR p.post_excerpt LIKE %s ';
                }
                $exerpt = $wpdb->prepare("p.post_excerpt LIKE %s {$like}", array_map(array('Wpil_Toolbox', 'esc_like'), $words));
                $results = $wpdb->get_col("SELECT DISTINCT ID FROM {$wpdb->posts} p WHERE p.post_type = 'product' {$selected_terms} {$age_string} {$user_role_ignore} AND ({$exerpt})");

                if(!empty($results)){
                    $posts = array_merge($posts, $results);
                }
            }
        }

        // if the user has defined custom fields to process (non-ACF)
        $fields = Wpil_Post::getMetaContentFieldList('post');
        if(!empty($fields)){
            //get unique words from post title
            $words = Wpil_Word::getWords($keyword);
            $words = Wpil_Word::cleanIgnoreWords(array_unique($words));
            $words = array_filter($words);

            // get the age query if the user is limiting the range for linking
            $age_string = Wpil_Query::getPostDateQueryLimit('p');

            // get if the user is restricting suggestions from posts created by specific roles
            $user_role_ignore = Wpil_Query::get_ignore_user_role_suggestions_query('p');

            if(!empty($words)){
                $post_types_p = Wpil_Query::postTypes('p');
                $statuses_query_p = Wpil_Query::postStatuses('p');

                $selected_terms = "";
                $selected_terms .= (isset($term_taxonomy_ids[0])) ? " AND m.post_id in (select object_id from {$wpdb->term_relationships} where term_taxonomy_id in (" . implode(',', $term_taxonomy_ids[0]) . ")) " : "";
                $selected_terms .= (isset($term_taxonomy_ids[1])) ? " AND m.post_id in (select object_id from {$wpdb->term_relationships} where term_taxonomy_id in (" . implode(',', $term_taxonomy_ids[1]) . ")) " : "";
    
                $like = '';
                for($i = 1; $i < count($words); $i++){
                    $like .= 'OR meta_value LIKE %s ';
                }
                $meta = $wpdb->prepare("meta_value LIKE %s {$like}", array_map(array('Wpil_Toolbox', 'esc_like'), $words));
                $results = $wpdb->get_col("SELECT DISTINCT m.post_id AS ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE m.meta_key IN ('" . implode("', '", $fields) . "') {$selected_terms} {$age_string} {$user_role_ignore} {$post_types_p} {$statuses_query_p} AND ({$meta})");

                if(!empty($results)){
                    $posts = array_merge($posts, $results);
                }
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
                        $posts[] = $id;
                    }
                }
            }
        }

        return $posts;
    }

    public static function getKeywordsUrl()
    {
        $url = '';
        if (!empty($_POST['keywords'])) {
            $url = '&keywords=' . str_replace("\n", ";", $_POST['keywords']);
        }

        return $url;
    }

    /**
     * Removes all repeat toplevel suggestions leaving only the best one to be presented to the user.
     * Loops around multiple times to make sure that we're only showing the top one per post on the top level.
     **/
    public static function remove_top_level_suggested_post_repeats($phrases){
        $count = 0;
        do{
            // sort the top-level suggestions so we can tell which suggestion has the highest score between the different phrases so we can remove the lower ranking ones
            $top_level_posts = array();
            foreach($phrases as $key => $phrase){
                if(empty($phrase->suggestions)){
                    unset($phrases[$key]);
                    continue;
                }

                $post_key = ($phrase->suggestions[0]->post->type=='term'?'cat':'') . $phrase->suggestions[0]->post->id;
                $tk_match = (isset($phrase->suggestions[0]->passed_target_keywords) && !empty($phrase->suggestions[0]->passed_target_keywords)) ? true: false;
                $top_level_posts[$post_key][] = (object)array('key' => $key, 'total_score' => $phrase->suggestions[0]->total_score, 'target_match' => $tk_match);
            }

            // sort the top-level posts so we can find the best suggestion for each phrase and build the list of suggestions to remove
            $remove_suggestions = array();
            foreach($top_level_posts as $post_key => $dat){
                // skip suggestions that are the only ones for their posts
                if(count($dat) < 2){
                    continue;
                }

                usort($top_level_posts[$post_key], function ($a, $b) {
                    if ($a->total_score == $b->total_score) {
                        return 0;
                    }
                    return ($a->total_score > $b->total_score) ? -1 : 1;
                });

                // remove the top suggestion from the list of suggestions to remove and make a list of the phrase keys
                $remove_suggestions[$post_key] = array_map(function($var){ return $var->key; }, array_slice($top_level_posts[$post_key], 1));
            }

            // go over the phrases and remove any suggested links that are not the top-level ones
            foreach($phrases as $key => $phrase){
                $post_key = ($phrase->suggestions[0]->post->type=='term'?'cat':'') . $phrase->suggestions[0]->post->id;

                // skip any that aren't on the list
                if(!isset($remove_suggestions[$post_key])){
                    continue;
                }

                // if the phrase is listed in the remove list
                if(in_array($key, $remove_suggestions[$post_key], true)){

                    // remove the suggestion
                    $suggestions = array_slice($phrase->suggestions, 1);
                    // if this is the only suggestion
                    if(empty($suggestions)){
                        // remove the phrase from the list to consider
                        unset($phrases[$key]);
                    }else{
                        // if it wasn't the only suggestion for the phrase, update the list of suggestions
                        $phrases[$key]->suggestions = $suggestions;
                    }
                }
            }

            // exit if we've gotten into endless looping
            if($count > 100){ // todo: create some kind of "Link Whisper System Instability Report" that will tell users if the plugin is getting wasty or caught in loops or anything like that.
                break;
            }
            $count++;
        }while(!empty($remove_suggestions));

        return $phrases;
    }

    /**
     * Delete phrases with sugeestion point < 3
     *
     * @param $phrases
     * @return array
     */
    public static function deleteWeakPhrases($phrases)
    {
        // return the phrases without trimming if there's less than 10 of them
        if (count($phrases) <= 10) {
            return $phrases;
        }

        // go over the phrases and count how many have > 3 points in post score
        $three_and_more = 0;
        foreach ($phrases as $key => $phrase) {
            if(!isset($phrase->suggestions[0])){
                unset($phrases[$key]);
                continue;
            }
            if ($phrase->suggestions[0]->post_score >=3) {
                $three_and_more++;
            }
        }

        // if we have less than 10 posts scoring 3 or higher
        if ($three_and_more < 10) {
            foreach ($phrases as $key => $phrase) {
                // find the suggestions that score less than 3 points
                if ($phrase->suggestions[0]->post_score < 3) {
                    // if we still don't have 10 suggestions
                    if ($three_and_more < 10) {
                        // increment the 3 and more counter so that this suggestion is allowed
                        $three_and_more++;
                    } else {
                        // when we've hit the limit, trim off all the other suggestions
                        unset($phrases[$key]);
                    }
                }
            }
        } else {
            // if we have 10 posts that score 3 or more
            foreach ($phrases as $key => $phrase) {
                // trimm off all the suggestions that score less than 3 points
                if ($phrase->suggestions[0]->post_score < 3) {
                    unset($phrases[$key]);
                }
            }
        }

        return $phrases;
    }

    /**
     * Get post IDs from inbound custom fields
     *
     * @param $keyword
     * @return array
     */
    public static function getInboundCustomFields($keyword, $term_taxonomy_ids = array())
    {
        global $wpdb;
        $posts = [];

        if(!class_exists('ACF') || get_option('wpil_disable_acf', false)){
            return $posts;
        }

        // get the age query if the user is limiting the range for linking
        $age_string = Wpil_Query::getPostDateQueryLimit('p');

        // and if the user is restricting suggestions from posts created by specific roles
        $user_role_ignore = Wpil_Query::get_ignore_user_role_suggestions_query('p');

        $post_content = str_replace('post_content', 'm.meta_value', self::getInboundPostContent($keyword));
        $statuses_query = Wpil_Query::postStatuses('p');
        $post_types = implode("','", self::getSuggestionPostTypes());
        $fields = Wpil_Post::getAllCustomFields();


        $selected_terms = "";
        $selected_terms .= (isset($term_taxonomy_ids[0])) ? " AND p.ID in (select object_id from {$wpdb->term_relationships} where term_taxonomy_id in (" . implode(',', $term_taxonomy_ids[0]) . ")) " : "";
        $selected_terms .= (isset($term_taxonomy_ids[1])) ? " AND p.ID in (select object_id from {$wpdb->term_relationships} where term_taxonomy_id in (" . implode(',', $term_taxonomy_ids[1]) . ")) " : "";
        if(count($fields) < 100){
            $fields = !empty($fields) ? " AND m.meta_key in ('" . implode("', '", $fields) . "') " : '';
            $posts_query = $wpdb->get_results("SELECT m.post_id FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID WHERE p.post_type IN ('{$post_types}') {$age_string} {$user_role_ignore} {$selected_terms} $fields $post_content $statuses_query");
            foreach ($posts_query as $post) {
                $posts[] = $post->post_id;
            }
        }else{
            // chunk the fields to query so we don't ask for too many at once
            $field_batches = array_chunk($fields, 100);
            foreach($field_batches as $batch){
                $fields = !empty($batch) ? " AND m.meta_key in ('" . implode("', '", $batch) . "') " : '';
                $posts_query = $wpdb->get_results("SELECT m.post_id FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID WHERE p.post_type IN ('{$post_types}') {$age_string} {$user_role_ignore} {$selected_terms}  $fields $post_content $statuses_query");
                foreach ($posts_query as $post) {
                    $posts[] = $post->post_id;
                }
            }
        }

        return $posts;
    }

    /**
     * Group inbound suggestions by post ID
     *
     * @param $phrases
     * @return array
     */
    public static function getInboundGroups($phrases)
    {
        $groups = [];
        foreach ($phrases as $phrase) {
            $post_id = $phrase->suggestions[0]->post->id;
            if (empty($groups[$post_id])) {
                $groups[$post_id] = [$phrase];
            } else {
                $groups[$post_id][] = $phrase;
            }
        }

        return $groups;
    }

    /**
     * Merges sentences that have been split on styling tags back into a single sentence
     **/
    public static function mergeSplitSentenceTags($list = array()){
        if(empty($list)){
            return $list;
        }

        $tags = array(
            'b' => array('opening' => array('<b>'), 'closing' => array('</b>', '<\/b>')),
            'strong' => array('opening' => array('<strong>'), 'closing' => array('</strong>', '<\/strong>')),
            'code' => array('opening' => array('<code>'), 'closing' => array('</code>', '<\/code>'))
        );

        $updated_list = array();
        $skip_until = false;
        foreach($list as $key => $item){
            if($skip_until !== false && $skip_until >= $key){
                continue;
            }

            $sentence = '';
            // look over the tags
            foreach($tags as $tag => $dat){
                // if the string has an opening tab, but not a closing one
                if(self::hasStringFromArray($item, $dat['opening']) && !self::hasStringFromArray($item, $dat['closing'])){
                    // find a string that contains the closing tag
                    foreach(array_slice($list, $key, null, true) as $sub_key => $sub_item){
                        $sentence .= $sub_item;
                        $skip_until = $sub_key;
                        if(self::hasStringFromArray($sub_item, $dat['closing'])){
                            break 2;
                        }
                    }
                }
            }

            // add the sentence to the list
            $updated_list[$key] = !empty($sentence) ? $sentence: $item;
        }

        return $updated_list;
    }

    /**
     * Searches a string for the presence of any strings from an array.
     * 
     * @return bool Returns true if the string contains a string from the search array. Returns false if the string does not contain a search string or is empty.
     **/
    public static function hasStringFromArray($string = '', $search = array()){
        if(empty($string) || empty($search)){
            return false;
        }

        foreach($search as $s){
            // if the string contains the searched string, return true
            if(false !== strpos($string, $s)){
                return true;
            }
        }

        return false;
    }

    /**
     * Remove empty sentences from the list
     *
     * @param $sentences
     */
    public static function removeEmptySentences(&$sentences, $with_links = false)
    {
        $prev_key = null;
        foreach ($sentences as $key => $sentence)
        {
            //Remove text from alt and title attributes
            $pos = 0;
            // if the previous sentence has an alt or title attr
            if ($prev_key && ($pos = strpos($prev_sentence, 'alt="') !== false || $pos = strpos($prev_sentence, 'title="') !== false)) {
                // if there's a closing quote after the 
                if (isset($sentences[$prev_key]) && strpos($sentences[$prev_key], '"', $pos) == false) {
                    $pos = strpos($sentence, '"');
                    if ($pos !== false) {
                        $sentences[$key] = substr($sentence, $pos + 1);
                    } else {
                        unset ($sentences[$key]);
                    }
                }
            }
            $prev_sentence = $sentence;

            $endings = ['</h1>', '</h2>', '</h3>'];

            if (!$with_links) {
                $endings[] = '</a>';
            }

            if (in_array(trim($sentence), $endings) && $prev_key) {
                unset($sentences[$prev_key]);
            }
            if (empty(trim(strip_tags($sentence)))) {
                unset($sentences[$key]);
            }

            if (substr($sentence, 0, 5) == '<!-- ' && substr($sentence, 0, -4) == ' -->') {
                unset($sentences[$key]);
            }

            $cleaned = trim(Wpil_Word::clearFromUnicode($sentence));
            if('&nbsp;' === $cleaned || empty($cleaned)){
                unset($sentences[$key]);
            }

            $prev_key = $key;
        }
    }

    /**
     * Remove tags from the beginning and the ending of the sentence
     *
     * @param $sentences
     */
    public static function trimTags(&$sentences, $with_links = false)
    {
        foreach ($sentences as $key => $sentence)
        {
            if (strpos($sentence, '<h') !== false || strpos($sentence, '</h') !== false) {
                unset($sentences[$key]);
                continue;
            }

            if (!$with_links && (
                strpos($sentence, '<a ') !== false || strpos($sentence, '</a>') !== false ||
                strpos($sentence, '<ta ') !== false || strpos($sentence, '</ta>') !== false ||
                strpos($sentence, '<img ') !== false || strpos($sentence, 'wp:image') !== false)
            ){
                unset($sentences[$key]);
                continue;
            }

            if (substr_count($sentence, '<a ') >  substr_count($sentence, '</a>')
            ) {
                // check and see if we've split the anchor by mistake
                if( isset($sentences[$key + 1]) && // if there's a sentence after this one
                    substr_count($sentence . $sentences[$key + 1], '<a ') === substr_count($sentence . $sentences[$key + 1], '</a>') // and adding that sentence to this one equalizes the tag count
                ){
                    // update the next sentence with this full one so we can process it in it's entirety
                    $sentences[$key + 1] = $sentence . $sentences[$key + 1];
                }

                // then unset the current sentence and skip to the next one
                unset($sentences[$key]);
                continue;
            }

            if (substr_count($sentence, '<ta ') >  substr_count($sentence, '</ta>')
            ) {
                // check and see if we've split the anchor by mistake
                if( isset($sentences[$key + 1]) && // if there's a sentence after this one
                    substr_count($sentence . $sentences[$key + 1], '<ta ') === substr_count($sentence . $sentences[$key + 1], '</ta>') // and adding that sentence to this one equalizes the tag count
                ){
                    // update the next sentence with this full one so we can process it in it's entirety
                    $sentences[$key + 1] = $sentence . $sentences[$key + 1];
                }

                // then unset the current sentence and skip to the next one
                unset($sentences[$key]);
                continue;
            }

            $sentence = trim($sentences[$key]);
            while (substr($sentence, 0, 1) == '<' || substr($sentence, 0, 1) == '[') {
                $end_char = substr($sentence, 0, 1) == '<' ? '>' : ']';
                $end = strpos($sentence, $end_char);
                $tag = substr($sentence, 0, $end + 1);
                if (in_array($tag, ['<b>', '<i>', '<u>', '<strong>', '<em>', '<code>'])) {
                    break;
                }
                if (substr($tag, 0, 3) == '<a ' || substr($tag, 0, 4) == '<ta ') {
                    break;
                }
                $sentence = trim(trim(trim(substr($sentence, $end + 1)), ','));
            }

            while (substr($sentence, -1) == '>' || substr($sentence, -1) == ']') {
                $start_char = substr($sentence, -1) == '>' ? '<' : '[';
                $start = strrpos($sentence, $start_char);
                $tag = substr($sentence, $start);
                if (in_array($tag, ['</b>', '</i>', '</u>', '</strong>', '<em>', '<code>', '</a>', '</ta>'])) {
                    break;
                }
                $sentence = trim(trim(trim(substr($sentence, 0, $start)), ','));
            }

            $sentences[$key] = $sentence;
        }
    }

    /**
     * Generate subquery to search posts or products only with same categories
     *
     * @param $post
     * @return string
     */
    public static function getTitleQueryExclude($post)
    {
        global $wpdb;

        $exclude = "";
        $linked_posts = array();
        if(get_option('wpil_prevent_two_way_linking', false)){
            $linked_posts = Wpil_Post::getLinkedPostIDs($post, false);
        }
        if ($post->type == 'post') {
            $redirected = Wpil_Settings::getRedirectedPosts();  // ignore any posts that are hidden by redirects
            $redirected[] = $post->id;                          // ignore the current post
            foreach($linked_posts as $link){
                if(!empty($link->post) && $link->post->type === 'post'){
                    $redirected[] = $link->post->id;
                }
            }
            $redirected = implode(', ', $redirected);
            $exclude .= " AND ID NOT IN ({$redirected}) ";
        }

        if (!empty(Wpil_Settings::get_suggestion_filter('same_category'))) {
            if ($post->type === 'post') {
                if (!empty(Wpil_Settings::get_suggestion_filter('selected_category'))) {
                    $categories = self::get_selected_categories();
                } else {
                    $taxes = get_object_taxonomies(get_post($post->id));
                    $query_taxes = array();
                    foreach($taxes as $tax){
                        if(get_taxonomy($tax)->hierarchical){
                            $query_taxes[] = $tax;
                        }
                    }
                    $categories = wp_get_object_terms($post->id, $query_taxes, ['fields' => 'tt_ids']);
                }
                foreach($linked_posts as $link){
                    if(!empty($link->post) && $link->post->type === 'term'){
                        $categories[] = $link->post->id;
                    }
                }
                $categories = count($categories) ? implode(',', $categories) : "''";
                $exclude .= " AND ID in (select object_id from {$wpdb->prefix}term_relationships where term_taxonomy_id in ($categories))";
            }
        }

        if (!empty(Wpil_Settings::get_suggestion_filter('same_tag'))) {
            if ($post->type === 'post') {
                if (!empty(Wpil_Settings::get_suggestion_filter('selected_tag'))) {
                    $tags = self::get_selected_tags();
                } else {
                    $taxes = get_object_taxonomies(get_post($post->id));
                    $query_taxes = array();
                    foreach($taxes as $tax){
                        if(empty(get_taxonomy($tax)->hierarchical)){
                            $query_taxes[] = $tax;
                        }
                    }
                    $tags = wp_get_object_terms($post->id, $query_taxes, ['fields' => 'tt_ids']);
                }
                foreach($linked_posts as $link){
                    if(!empty($link->post) && $link->post->type === 'term'){
                        $tags[] = $link->post->id;
                    }
                }
                $tags = count($tags) ? implode(',', $tags) : "''";
                $exclude .= " AND ID in (select object_id from {$wpdb->prefix}term_relationships where term_taxonomy_id in ($tags))";
            }
        }

        return $exclude;
    }

    /**
     * Gets the post types for use in making suggestions.
     * Looks to see if the user has selected any post types from the suggestion panel.
     * If he has, then it returns the post types the user has selected. Otherwise, it returns the post types from the LW Settings
     **/
    public static function getSuggestionPostTypes(){

        // get the post types from the settings
        $post_types = Wpil_Settings::getPostTypes();

        // if the user has selected post types from the suggestion panel
        if( Wpil_Settings::get_suggestion_filter('select_post_types'))
        {
            // obtain the selected post types
            $user_selected = Wpil_Settings::get_suggestion_filter('selected_post_types');
            if(!empty($user_selected)){
                // check to make sure the supplied post types are ones that are in the settings
                $potential_types = array_intersect($post_types, $user_selected);

                // if there are post types, set the current post types for the selected ones
                if(!empty($potential_types)){
                    $post_types = $potential_types;
                }
            }
        }

        return $post_types;
    }

    /**
     * Gets the phrases from the current post for use in outbound linking suggestions.
     * Caches the phrase data so subsequent requests are faster
     * 
     * @param $post The post object we're getting the phrases from.
     * @param int $process_key The ajax processing key for the current process.
     * @return array $phrases The phrases from the given post
     **/
    public static function getOutboundPhrases($post, $process_key, $full_sentences = false){
        // try getting cached phrase data
        $phrases = get_transient('wpil_processed_phrases_' . $process_key);

        // if there aren't any phrases, process them now
        if(empty($phrases)){
            $phrases = self::getPhrases($post->getContent(), false, array(), false, array(), $full_sentences);

            //divide text to phrases
            foreach ($phrases as $key_phrase => &$phrase) {
                // replace any punctuation in the text and lower the string
                $text = Wpil_Word::strtolower(Wpil_Word::removeEndings($phrase->text, ['.','!','?','\'',':','"']));

                //get array of unique sentence words cleared from ignore phrases
                if (!empty($_REQUEST['keywords'])) {
                    $sentence = trim(preg_replace('/\s+/', ' ', $text));
                    $words_uniq = Wpil_Word::getWords($sentence);
                } else {
                    $words_uniq = Wpil_Word::cleanIgnoreWords(Wpil_Word::cleanFromIgnorePhrases($text)); // NOTE: If we get a lot of customers asking where their suggestions went in ver 2.3.5, "CleaningTheIgnoreWords" is the most likely cause.
                }

                // remove words less than 3 letters long and stem the words
                foreach($words_uniq as $key => $word){
                    if(strlen($word) < 3){
                        unset($words_uniq[$key]);
                        continue;
                    }

                    $words_uniq[$key] = Wpil_Stemmer::Stem($word);
                }

                $phrase->words_uniq = $words_uniq;
            }

            $save_phrases = Wpil_Toolbox::compress($phrases);
            set_transient('wpil_processed_phrases_' . $process_key, $save_phrases, MINUTE_IN_SECONDS * 15);
            reset($phrases);
            unset($save_phrases);
        }else{
            $phrases = Wpil_Toolbox::decompress($phrases);
        }

        return $phrases;
    }

    /**
     * Gets the categories that are assigned to the current post.
     * If we're doing an outbound scan, it caches the cat ids so they can be pulled up without a query
     **/
    public static function getSameCategories($post, $process_key = 0, $is_outbound = false){
        global $wpdb;

        if($is_outbound){
            $cats = get_transient('wpil_post_same_categories_' . $process_key);
            if(empty($cats) && !is_array($cats)){
                $cats = $wpdb->get_results("SELECT object_id FROM {$wpdb->prefix}term_relationships where term_taxonomy_id in (SELECT r.term_taxonomy_id FROM {$wpdb->prefix}term_relationships r inner join {$wpdb->prefix}term_taxonomy t on t.term_taxonomy_id = r.term_taxonomy_id where r.object_id = {$post->id} and t.taxonomy = 'category')");
            
                if(empty($cats)){
                    $cats = array();
                }

                set_transient('wpil_post_same_categories_' . $process_key, $cats, MINUTE_IN_SECONDS * 15);
            }
            
        }else{
            $cats = $wpdb->get_results("SELECT object_id FROM {$wpdb->prefix}term_relationships where term_taxonomy_id in (SELECT r.term_taxonomy_id FROM {$wpdb->prefix}term_relationships r inner join {$wpdb->prefix}term_taxonomy t on t.term_taxonomy_id = r.term_taxonomy_id where r.object_id = {$post->id} and t.taxonomy = 'category')");
        }

        return $cats;
    }

    /**
     * Checks to see if the current sentence contains any of the post's keywords.
     * Performs a strict check and a loose keyword check.
     * The strict check examines the sentence to see if there's an exact match of the keywords in the sentence.
     * The loose checks sees if the sentence contains any matching words from the keyword in any order.
     * 
     * @param string $sentence The sentence to examine
     * @param array $target_keywords The processed keywords to check for
     * 
     * @return object|bool Returns the keyword if the sentence contains any of the keywords, False if the sentence doesn't contain any keywords.
     **/
    public static function checkSentenceForKeywords($sentence = '', $target_keywords = array(), $unique_keywords = array(), $more_specific_keywords = array()){
        $stemmed_sentence = Wpil_Word::getStemmedSentence($sentence);
        $loose_match_count = false; // get_option('wpil_loose_keyword_match_count', 0);

        // if we're supposed to check for loose matching of the keywords
        if(!empty($loose_match_count) && !empty($unique_keywords)){
            // find out how many times the keywords show up in the sentence
            $words = array_flip(explode(' ', $stemmed_sentence));
            $unique_keywords = array_flip($unique_keywords);

            $matches = array_intersect_key($unique_keywords, $words);

            // if the count is more than what the user has set
            if(count($matches) > $loose_match_count){
                // report that the sentence contains the keywords
                return true;
            }
        }

        foreach($target_keywords as $keyword){
            // skip the keyword if it's only 2 chars long or it's an ai generated keyword
            if(3 > strlen($keyword->keywords) || $keyword->keyword_type === 'ai-generated-keyword'){
                continue;
            }

            // if the keyword is in the phrase
            if(false !== strpos($stemmed_sentence, $keyword->stemmed)){
                // check if it participates in a more specific keyword
                if(isset($more_specific_keywords[$keyword->stemmed])){
                    foreach($more_specific_keywords[$keyword->stemmed] as $dat){
                        // if it does, skip to the next keyword
                        if(false !== Wpil_Word::mb_strpos($stemmed_sentence, $dat->stemmed)){
                            continue 2;
                        }
                    }
                }
                return true;
            }

            // if the keyword is a post content keyword, 
            // check if the there's at least 2 consecutively matching words between the sentence and the keyword
            if($keyword->keyword_type === 'post-keyword'){
                $k_words = explode(' ', $keyword->keywords);
                $s_words = explode(' ', $stemmed_sentence);

                foreach($k_words as $key => $word){
                    // see if the current word is in the stemmed sentence
                    $pos = array_search($word, $s_words, true);

                    // if it is, see if the next word is the same for both strings
                    if( false !== $pos && 
                        isset($s_words[$pos + 1]) &&
                        isset($k_words[$key + 1]) &&
                        ($s_words[$pos + 1] === $k_words[$key + 1])
                    ){
                        // if it is, return true
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Clears the cached data when the suggestion processing is complete
     * 
     * @param int $processing_key The id of the suggestion processing run.
     **/
    public static function clearSuggestionProcessingCache($processing_key = 0, $post_id = 0){
        // clear the suggestions
        delete_transient('wpil_post_suggestions_' . $processing_key);
        // clear the inbound suggestion ids
        delete_transient('wpil_inbound_suggested_post_ids_' . $processing_key);
        // clear the keyword cache
        delete_transient('wpil_post_suggestions_keywords_' . $processing_key);
        // clear the unique keyword cache
        delete_transient('wpil_post_suggestions_unique_keywords_' . $processing_key);
        // clear any cached inbound links cache
        delete_transient('wpil_stored_post_internal_inbound_links_' . $post_id);
        // clear the processed phrase cache
        delete_transient('wpil_processed_phrases_' . $processing_key);
        // clear the post link cache
        delete_transient('wpil_external_post_link_index_' . $processing_key);
        // clear the outbound post link cache
        delete_transient('wpil_outbound_post_links' . $processing_key);
        // clear the post category cache
        delete_transient('wpil_post_same_categories_' . $processing_key);
        // clear the post title word cache
        delete_transient('wpil_inbound_title_words_' . $processing_key);
    }

    /**
     * Checks to see if we're dealing with Asian caligraphics
     * Todo: Not currently active
     **/
    public static function isAsianText(){
        return false;
    }

    /**
     * Gets the currently selected categories for suggestion matching.
     * Pulls data from the $_POST variable or the stored filtering settings
     * @return array
     **/
    public static function get_selected_categories(){
        return Wpil_Settings::get_suggestion_filter('selected_category');
    }

    /**
     * Gets the currently selected tags for suggestion matching.
     * Pulls data from the $_POST variable or the stored filtering settings
     * @return array
     **/
    public static function get_selected_tags(){
        return Wpil_Settings::get_suggestion_filter('selected_tag');
    }

    /**
     * Gets the max length for a suggested anchor
     **/
    public static function get_max_anchor_length(){
        if(empty(self::$max_anchor_length)){
            self::$max_anchor_length = Wpil_Settings::getSuggestionMaxAnchorSize();
        }

        return self::$max_anchor_length;
    }

    /**
     * Gets the min length for a suggested anchor
     **/
    public static function get_min_anchor_length(){
        if(empty(self::$min_anchor_length)){
            self::$min_anchor_length = Wpil_Settings::getSuggestionMinAnchorSize();
        }

        return self::$min_anchor_length;
    }
}
