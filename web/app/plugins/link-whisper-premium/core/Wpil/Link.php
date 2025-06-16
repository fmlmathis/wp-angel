<?php

/**
 * Work with links
 */
class Wpil_Link
{
    static $url_redirect_cache = array();

    /**
     * Register services
     */
    public function register()
    {
        add_action('wp_ajax_wpil_save_linking_references', [$this, 'addLinks']);
        add_action('wp_ajax_wpil_continue_inbound_saving_process', [__CLASS__, 'ajax_continue_inbound_saving_process']);
        add_action('wp_ajax_wpil_get_link_title', [$this, 'getLinkTitle']);
        add_action('wp_ajax_wpil_add_link_to_ignore', [$this, 'addLinkToIgnore']);
        add_action('wp_ajax_wpil_delete_selected_links', [$this, 'ajax_delete_selected_links']);
    }

    /**
     * Update post links
     */
    function addLinks()
    {
        $err_msg = false;

        //check if request has needed data
        if (empty($_POST['data'])) {
            $err_msg = "No links selected";
        } elseif (empty($_POST['id']) || empty($_POST['type']) || empty($_POST['page'])){
            $err_msg = "Broken links data";
        } else {
            $page = $_POST['page'];

            foreach ($_POST['data'] as $item) {
                $id = !empty($item['id']) ? (int)$item['id'] : (int)$_POST['id'];
                $type = !empty($item['type']) ? $item['type'] : $_POST['type'];

                $links = $item['links'];
                //trim sentences
                foreach ($links as $key => $link) {
                    if ($page == 'inbound') {
                        $link['id'] = (int)$_POST['id'];
                        $link['type'] = sanitize_text_field($_POST['type']);
                    }

                    $external = (isset($link['post_origin']) && $link['post_origin'] === 'external') ? true: false;

                    if (!empty($link['custom_link'])) {
                        $view_link = $link['custom_link'];
                    } elseif($external) {
                        $item = new Wpil_Model_ExternalPost(array('post_id' => (int)$link['id'], 'type' => $link['type'], 'site_url' => esc_url_raw($link['site_url'])));
                        
                        $view_link = $item->getLinks()->view;
                    } elseif ($link['type'] == 'term') {
                        $post = new Wpil_Model_Post((int)$link['id'], 'term');
                        $view_link = $post->getViewLink();
                    } else {
                        $post = new Wpil_Model_Post((int)$link['id']);
                        $view_link = $post->getViewLink();
                    }

                    $links[$key]['sentence'] = trim(base64_decode($link['sentence']));
                    $links[$key]['sentence_with_anchor'] = trim(str_replace('%view_link%', $view_link, $link['sentence_with_anchor']));

                    if (!empty($link['custom_sentence'])) {
                        $links[$key]['custom_sentence'] = trim(str_replace('|href="([^"]+)"|', $view_link, base64_decode($link['custom_sentence'])));

                        if (!empty($link['custom_link'])) {
                            $links[$key]['custom_sentence'] = preg_replace('|href="([^"]+)"|', 'href="'.$link['custom_link'].'"', $links[$key]['custom_sentence']);
                        }
                    }

                    update_post_meta($link['id'], 'wpil_sync_report3', 0);
                }

                if ($type == 'term') {
                    $existing_links = Wpil_Toolbox::get_encoded_term_meta($id, 'wpil_links', true);
                    $links = (!empty($existing_links) && is_array($existing_links)) ? array_merge($links, $existing_links): $links;
                    Wpil_Toolbox::update_encoded_term_meta($id, 'wpil_links', $links);
                } else {
                    $existing_links = Wpil_Toolbox::get_encoded_post_meta($id, 'wpil_links', true);
                    $links = (!empty($existing_links) && is_array($existing_links)) ? array_merge($links, $existing_links): $links;
                    Wpil_Toolbox::update_encoded_post_meta($id, 'wpil_links', $links);

                    if ($page == 'outbound') {
                        //create DB record with success flag
                        update_post_meta($id, 'wpil_is_outbound_links_added', '1');

                        // log the event
                        Wpil_Telemetry::log_event('outbound_suggestion_link_add', array('count' => count($links)));

                        //create DB record to refresh page after post update if Gutenberg is active
                        if (!empty($_POST['gutenberg']) && $_POST['gutenberg'] == 'true') {
                            update_post_meta($id, 'wpil_gutenberg_restart', '1');
                        }
                    }
                }
            }

            //add links to content
            if ($page == 'inbound') {
                // get the memory limit
                $memory_break_point = Wpil_Report::get_mem_break_point();

                // so that we can support huge numbers of inserted links, create an index of the possible posts and terms that we'll be inserting links into
                $item_index = array('term' => array(), 'post' => array());
                foreach($_POST['data'] as $item){
                    if($item['type'] == 'term'){
                        $item_index['term'][$item['id']] = $item['id'];
                    }else{
                        $item_index['post'][$item['id']] = $item['id'];
                    }
                }

                // log the event
                Wpil_Telemetry::log_event('inbound_suggestion_link_add', array('count' => count($_POST['data'])));

                $has_inserted = false;
                foreach ($_POST['data'] as $item) {
                    if ($item['type'] == 'term') {
                        Wpil_Term::addLinksToTerm($item['id']);
                    } else {
                        ob_start();
                        Wpil_Post::addLinksToContent(null, ['ID' => $item['id']], array(), false, true);
                        ob_end_clean();
                    }

                    // if the link was inserted
                    if(Wpil_Base::action_happened('link_inserted')){
                        // set the insertion flag
                        $has_inserted = true;
                        // remove the item from the index
                        unset($item_index[$item['type']][$item['id']]);
                        // unset the action tracker
                        Wpil_Base::clear_tracked_action('link_inserted');
                    }

                    // if we've come up against a limit
                    if(Wpil_Base::overTimeLimit(15, 29) || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point)){
                        // note if we've inserted links
                        if($has_inserted){
                            if ($item['type'] == 'term') {
                                update_term_meta((int)$_POST['id'], 'wpil_is_inbound_links_added', '1');
                            } else {
                                update_post_meta((int)$_POST['id'], 'wpil_is_inbound_links_added', '1');
                            }
                        }

                        // output the post index so we can continue the process in a separate loop
                        wp_send_json(array('further_processing' => true, 'data' => $item_index));
                    }
                }

                if($has_inserted){
                    if ($item['type'] == 'term') {
                        update_term_meta((int)$_POST['id'], 'wpil_is_inbound_links_added', '1');
                    } else {
                        update_post_meta((int)$_POST['id'], 'wpil_is_inbound_links_added', '1');
                    }
                }
            }
        }

        //return response
        wp_send_json(array('err_msg' => $err_msg));
    }

    public static function ajax_continue_inbound_saving_process(){
        if(!isset($_POST['data']) || empty($_POST['data']) || !isset($_POST['target_id']) || !isset($_POST['target_type'])){
            wp_send_json(array('success' => true));
        }

        // create a new index in case we need to do this repeatedly
        $item_index = $_POST['data'];
        // set up an insertion flag for notifications
        $has_inserted = false;
        // get the memory limit
        $memory_break_point = Wpil_Report::get_mem_break_point();

        foreach($_POST['data'] as $type => $dat){
            foreach($dat as $id){
                if ($type == 'term') {
                    Wpil_Term::addLinksToTerm($id);
                } else {
                    ob_start();
                    Wpil_Post::addLinksToContent(null, ['ID' => $id], array(), false, true);
                    ob_end_clean();
                }

                // if the link was inserted
                if(Wpil_Base::action_happened('link_inserted')){
                    // set the insertion flag
                    $has_inserted = true;
                    // unset the action tracker
                    Wpil_Base::clear_tracked_action('link_inserted');
                }

                // remove the item from the index regardless of the insertion status to avoid infinite loops
                unset($item_index[$type][$id]);

                // if the type is empty, remove that too
                if(empty($item_index[$type])){
                    unset($item_index[$type]);
                }

                // if we've come up against a limit
                if(Wpil_Base::overTimeLimit(15, 29) || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point)){
                    // break out of the loops
                    break 2;
                }
            }
        }

        // note if we've inserted links
        if($has_inserted){
            if ($_POST['target_type'] == 'term') {
                update_term_meta((int)$_POST['target_id'], 'wpil_is_inbound_links_added', '1');
            } else {
                update_post_meta((int)$_POST['target_id'], 'wpil_is_inbound_links_added', '1');
            }
        }

        // output the if there's more processing to do so we can tell if we're coming back around again
        wp_send_json(array('further_processing' => !empty($item_index), 'data' => $item_index));
    }

    /**
     * Delete link from post
     * @param null $params
     */
    public static function delete($params = null, $no_die = false, $skip_cleanup = false)
    {
        foreach (['post_id', 'post_type', 'url', 'anchor', 'url_anchor', 'link_id'] as $key) {
            $$key = self::getDeleteParam($params, $key);
        }
        $anchor = !empty($anchor) ? base64_decode($anchor) : null;

        if ($post_id && $post_type && $url) {
            $post = new Wpil_Model_Post($post_id, $post_type);
            $content = $post->getCleanContent();
            $excerpt = $post->maybeGetExcerpt();

            // create the search content so we can examine more than just the post content for the link
            $search_content = trim($content . ' ' . $excerpt);

            if(self::checkIfBase64ed($url)){
                $url = base64_decode($url);
            }

            // if the url isn't in the content, check if the url in the content is relative
            if(false === strpos($search_content, '"' . $url . '"')){
                $site_url = get_home_url();
                $relative = wp_make_link_relative($url);
                // if it is, make the url the relative version
                if(false !== strpos($search_content, '"' . $relative . '"')){
                    $url = $relative;
                }elseif(false !== strpos($url, $site_url)){ // if the wp_relative function didn't work, try removing the site URL from the link and see if that works
                    // create a new relative version of the link
                    $relative = ltrim(str_replace($site_url, '', $url), '/');
                    // if the link is more than just a directory separator and does appear in the content
                    if(strlen($relative) > 1 && false !== strpos($search_content, '"/' . $relative . '"')){
                        // go with this version
                        $url = ('/' . $relative);
                    }elseif(strlen($relative) > 1 && false !== strpos($search_content, '"' . $relative . '"')){ // if the link is more than just a directory separator, and appears in the content without a leading slash
                        // go with this version
                        $url = $relative;
                    }
                }
            }

            // check if the current URL is for an image
            $is_image = false;
            if((preg_match('/\.jpg|\.jpeg|\.svg|\.png|\.gif|\.ico|\.webp/i', $url) || false !== strpos($url, '/nextgen-attach_to_post/preview/')) && empty($anchor)){ // we're checking for normal image extensions and if the URL points to a "NextGEN Gallery" image
                // if it is, check to see if there's an image tag with this URL in the post // Since the link is already for an image, we'll check if there's an image tag on the assumption that the user is deleting an image.
                if(preg_match('`<img [^><]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^><]*>|&lt;img [^&>]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^&>]*&gt;`', $content)){
                    $is_image = true;
                }
            }

            if($is_image){
                $content = self::deleteImage($post, $url, $content);
                $meta_deleted = self::deleteLinkFromMetaFields($post, $url, '', true);
                $excerpt = self::deleteImage($post, $url, $excerpt);
            }else{
                $content = self::deleteLink($post, $url, $anchor, $content);
                $meta_deleted = self::deleteLinkFromMetaFields($post, $url, $anchor);
                $excerpt = self::deleteLink($post, $url, $anchor, $excerpt);
            }

            $updated = $post->updateContent($content, $excerpt);

            if($updated){
                $post->setContent($content);
                $post->clearPostCache();
            }

            //delete link record from wpil_broken_links table
            if (!empty($link_id)) {
                Wpil_Error::deleteLink($link_id);
            }

            if (WPIL_STATUS_LINK_TABLE_EXISTS){
                Wpil_Report::update_post_in_link_table($post);
            }

            // if we're supposed to update the link stats so they reflect the newly removed link
            if(!$skip_cleanup){
                // update this post's stats
                Wpil_Report::statUpdate($post);

                //update second post if link was internal inbound
                $second_post = Wpil_Post::getPostByLink($url);
                if (!empty($second_post)) {
                    Wpil_Report::statUpdate($second_post);
                }
            }

            if($updated){
                // update the post if the user has opted to do so
                Wpil_Toolbox::trigger_clean_post_update($post->id, $post->type);
                // and clear the cache/cdn
                Wpil_Toolbox::attempt_cdn_clearing($post->id, $post->type);
            }
        }elseif ($post_id && $post_type && $url_anchor) {
            $post = new Wpil_Model_Post($post_id, $post_type);
            $content = $post->getCleanContent();
            $excerpt = $post->maybeGetExcerpt();

            // create the search content so we can examine more than just the post content for the link
            $search_content = trim($content . ' ' . $excerpt);

            // create a list of removed urls
            $removed_urls = array();

            foreach($url_anchor as $data){
                $url = $data[0];
                $anchor = $data[1];

                if(Wpil_Base::overTimeLimit(0, 11)){
                    break;
                }

                if(self::checkIfBase64ed($url)){
                    $url = base64_decode($url);
                }

                if(self::checkIfBase64ed($anchor)){
                    $anchor = base64_decode($anchor);
                }

                $url = self::attempt_url_compatibility($search_content, $url);

                // check if the current URL is for an image
                $is_image = false;
                if((preg_match('/\.jpg|\.jpeg|\.svg|\.png|\.gif|\.ico|\.webp/i', $url) || false !== strpos($url, '/nextgen-attach_to_post/preview/')) && empty($anchor)){ // we're checking for normal image extensions and if the URL points to a "NextGEN Gallery" image
                    // if it is, check to see if there's an image tag with this URL in the post // Since the link is already for an image, we'll check if there's an image tag on the assumption that the user is deleting an image.
                    if(preg_match('`<img [^><]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^><]*>|&lt;img [^&>]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^&>]*&gt;`', $content)){
                        $is_image = true;
                    }
                }

                $before = md5($content);
                $before_excerpt = md5($excerpt);

                if($is_image){
                    $content = self::deleteImage($post, $url, $content);
                    $meta_deleted = self::deleteLinkFromMetaFields($post, $url, '', true);
                    $excerpt = self::deleteImage($post, $url, $excerpt);
                }else{
                    $content = self::deleteLink($post, $url, $anchor, $content);
                    $meta_deleted = self::deleteLinkFromMetaFields($post, $url, $anchor);
                    $excerpt = self::deleteLink($post, $url, $anchor, $excerpt);
                }

                // check if the url has been removed from any content
                if(md5($content) !== $before || $meta_deleted || $before_excerpt !== md5($excerpt)){
                    $removed_urls[] = array('url' => $data[0], 'anchor' => $data[1], 'post_id' => $post->id, 'post_type' => $post->type); // save the original versions of the URL+anchor pair and the post id so that we can remove them from the dropdown
                }
            }
            $updated = $post->updateContent($content, $excerpt);

            if($updated){
                $post->setContent($content);
                $post->clearPostCache();
            }

            //delete link record from wpil_broken_links table
            if (!empty($link_id)) {
                Wpil_Error::deleteLink($link_id);
            }

            if (WPIL_STATUS_LINK_TABLE_EXISTS){
                Wpil_Report::update_post_in_link_table($post);
            }

            Wpil_Report::statUpdate($post);

            //update second post(s) if link was internal inbound
            $second_posts = array();
            foreach($removed_urls as $removed_url){
                if(self::checkIfBase64ed($removed_url['url'])){
                    $url2 = base64_decode($removed_url['url']);
                }
                $second_post = Wpil_Post::getPostByLink($url2);
                if (!empty($second_post)) {
                    // make sure we only update a post's stats once
                    $pid = $second_post->type . '_' . $second_post->id;
                    if(!in_array($pid, $second_posts, true)){
                        $second_posts[] = $pid;
                        // update the second post's stats
                        Wpil_Report::statUpdate($second_post);
                        // and update it's status in the link table
                        Wpil_Report::update_post_in_link_table($second_post);
                    }
                }
            }

            if($updated){
                // update the post if the user has opted to do so
                Wpil_Toolbox::trigger_clean_post_update($post->id, $post->type);
                // and clear the cache/cdn
                Wpil_Toolbox::attempt_cdn_clearing($post->id, $post->type);
            }

            return $removed_urls;
        }

        if (!$no_die) {
            die;
        }
    }

    /**
     * Attempts to adjust the provided URL so that it matches the URL in teh post content.
     * @param $search_content The content area to check for the URL
     * @param $url The unchanged URL that we're checking to see if it needs compatibility adjusting
     **/
    public static function attempt_url_compatibility($search_content, $url){
        // if the url isn't in the content...
        if(false === strpos($search_content, '"' . $url . '"')){
            // get the url's current protocol
            $current_protocol = parse_url($url, PHP_URL_SCHEME); // returns False if there's no protocol
            // and change it's "s" status
            $protocol_to_try = ( $current_protocol == 'https' ) ? 'http' : 'https';
            $changed_protocol = preg_replace('/^' . $current_protocol . '/', $protocol_to_try, $url);
            // check if a change of protocol works
            if(!empty($current_protocol) && false !== strpos($search_content, '"' . $changed_protocol . '"')){
                $url = $changed_protocol;
            }else{  // otherwise, check if the url in the content is relative
                $site_url = get_home_url();
                $relative = wp_make_link_relative($url);
                // if it is, make the url the relative version
                if(false !== strpos($search_content, '"' . $relative . '"')){
                    $url = $relative;
                }elseif(false !== strpos($url, $site_url)){ // if the wp_relative function didn't work, try removing the site URL from the link and see if that works
                    // create a new relative version of the link
                    $relative = ltrim(str_replace($site_url, '', $url), '/');
                    // if the link is more than just a directory separator and does appear in the content
                    if(strlen($relative) > 1 && false !== strpos($search_content, '"/' . $relative . '"')){
                        // go with this version
                        $url = ('/' . $relative);
                    }elseif(strlen($relative) > 1 && false !== strpos($search_content, '"' . $relative . '"')){ // if the link is more than just a directory separator, and appears in the content without a leading slash
                        // go with this version instead
                        $url = $relative;
                    }
                }
            }
        }

        return $url;
    }

    /**
     * Deletes a link from the supplied post content.
     * @param $post The Wpil post object that we're deleting the link from
     * @param string $url The url of the link that we're removing
     * @param string|null $anchor The anchor text of the link we're removing
     * @param string $content The post content that we're removing the link from
     * @param string $run_editors Should we also tell the editors to remove the link? Defaults to true
     * @return string $content The content with the link removed.
     **/
    public static function deleteLink($post, $url, $anchor, $content, $run_editors = true){
        if (!empty($post) && $post->type == 'post' && $run_editors && is_string($content)) {
            Wpil_Post::editors('deleteLink', [$post->id, $url, $anchor]);
            Wpil_Editor_Kadence::deleteLink($content, $url, $anchor);
            Wpil_Editor_YooTheme::deleteLink($content, $url, $anchor);
        }

        // if there's no content to process, exit here
        if(empty($content)){
            return $content;
        }

        $has_url = ($url !== '{{wpil-empty-url}}') ? true: false; // check and see if the current link has no attributes, including a url...
        $has_anchor = !empty($anchor);
        $old_content = md5($content);
        $remove_inner_html = Wpil_Settings::delete_link_inner_html();

        //delete link from post content
        if($has_anchor){
            if($has_url){
                $search = '`<a [^>]+(\'|\")(?:/)?' . preg_quote(trim($url, '"\\/'), '`') . '(?:/)*(\'|\"|\\\")[^>]*>' . preg_quote($anchor, '`') . '</a>`i';
            }else{
                // if the link has no url, make a really simple regex to search with
                $search = '`<a>' . preg_quote($anchor, '`') . '</a>`i';
            }

            $content = preg_replace($search, $anchor,  $content, 1);

            // if the link hasn't been removed
            if($old_content === md5($content)){
                // maybe the link has some HTML tags inside the anchor tag... so what we'll do is check to see if there's HTML inside the tag
                preg_match_all('`<a [^><]*?href=(?:\'|\")' . preg_quote(trim($url, '"\\/'), '`') . '(?:/)*(?:\'|\"|\\\")[^><]*?>((?:<(?!a)[a-zA-Z]+?[^><]*?>)*?' . preg_quote($anchor, '`') . '(?:<(?!a)[a-zA-Z/]+?[^><]*?>)*?)</a>`i', $content, $matches);
                // if there appears to be
                if(!empty($matches)){
                    // go over each result and check to see if we have a match
                    foreach($matches[0] as $key => $link){
                        $found_link_with_anchor = $matches[0][$key];
                        $found_anchor = $matches[1][$key];
                        // if the anchor isn't empty and if it is the same as the supplied anchor when we remove tags
                        if(!empty($found_anchor) && strip_tags($found_anchor) === $anchor){
                            // IT"S AN UNDENIABLE CERTAINTY THAT IT"S THE LINK TO REMOVE
                            // so replace the link with the anchor contents
                            $found_anchor = ($remove_inner_html) ? strip_tags($found_anchor): $found_anchor; // but first check if the user wants to remove the inner HTML
                            $content = preg_replace('`' . preg_quote($found_link_with_anchor, '`') . '`', $found_anchor,  $content, 1);
                            // if we met with success, exit the loop
                            if($old_content !== md5($content)){
                                break;
                            }
                        }
                    }
                }

                // if that still didn't work, try removing encoded anchors
                if($old_content === md5($content)){
                    $content = preg_replace('`&lt;a [^&]+(\'|\"|&quot;)' . preg_quote(trim($url, '"\\/'), '`') . '(?:/)*(\'|\"|\\\"|&quot;)[^>]*&gt;' . preg_quote($anchor, '`') . '&lt;/a&gt;`i', $anchor,  $content, 1);

                    // if THAT doesn't work
                    if($old_content === md5($content) && false){ // todo: disabling pending review
                        // use a more aggresive regex to remove it
                        $content = preg_replace('`<a [^>]+(\'|\")' . preg_quote(trim($url, '"\\/'), '`') . '(?:/)*(\'|\"|\\\")[^>]*>(.*?)' . preg_quote($anchor, '`') . '(.*?)</a>`i', $anchor,  $content, 1);
                    }
                }
            }
        }

        // if there's no anchor or the link couldn't be deleted
        if (empty($has_anchor) || md5($content) === $old_content) {
            // if there's no anchor and it has no URL
            if(empty($has_url)){
                // try a really simple replace that only removes anchors with no text or urls
                $content = preg_replace('`<a></a>|<a><\\/a>`', '',  $content, 1);
                // there's no reason to try out the stuff in "else" since the URL that we're working with will be "{{wpil-empty-url}}"
            }else{
                $content = preg_replace('`<a [^>]+(\'|\")' . preg_quote(trim($url, '"\\/'), '`') . '(?:/)*(\'|\"|\\\")[^>]*>([\s\S]*?)</a>`i', '$3',  $content, 1);
            
                // if the link hasn't been removed
                if($old_content === md5($content)){
                    // try removing the encoded version of the anchor
                    $content = preg_replace('`&lt;a [^&]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^>]*&gt;([\s\S]*?)&lt;/a&gt;`i', '$3',  $content, 1);
                }
            }
        }

        return $content;
    }

    /**
     * Deletes a specific image from a post.
     * @param $post The Wpil post object that we're deleting the link from
     * @param string $url The url of the link that we're removing
     * @param string $content The post content that we're removing the link from
     * @return string $content The content with the link removed.
     **/
    public static function deleteImage($post, $url, $content){
        /*
        if ($post->type == 'post') { // todo: look into if needed
            Wpil_Post::editors('deleteLink', [$post->id, $url]);
            Wpil_Editor_Kadence::deleteLink($content, $url);
        }*/

        // if there's no content to process, exit here
        if(empty($content)){
            return $content;
        }

        $old_content = md5($content);

        // try removing the image
        $content = preg_replace('`<img [^><]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^><]*>`i', '',  $content);

        // if the image hasn't been removed
        if($old_content === md5($content)){
            // try removing the encoded version of the tag
            $content = preg_replace('`&lt;img [^&>]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^&>]*&gt;`i', '',  $content);
        }

        return $content;
    }

    public static function getDeleteParam($params, $key)
    {
        if (!empty($params[$key])) {
            return $params[$key];
        } elseif (!empty($_POST[$key])) {
            return $_POST[$key];
        } else {
            return null;
        }
    }

    /**
     * Deletes a given link from the post's metafields
     **/
    public static function deleteLinkFromMetaFields($post, $url, $anchor = '', $is_image = false){
        $fields = Wpil_Post::getMetaContentFieldList($post->type);

        // if this is a post, include any ACF fields that may exist
        if($post->type === 'post'){
            $fields = array_merge($fields, Wpil_Post::getAdvancedCustomFieldsList($post->id));
        }

        // set a flag for tracking if the link has been removed from the content
        $deleted = false;

        if(!empty($fields)){
            foreach($fields as $field){
                if($post->type === 'post'){
                    $content = get_post_meta($post->id, $field, true);
                }else{
                    $content = get_term_meta($post->id, $field, true);
                }

                // if the content is an array, skip to the next item
                if(is_array($content) || !is_string($content)){
                    continue;
                }

                $old_content = md5($content);

                if(!$is_image){
                    // check the url for compatibility.
                    // since each field is it's own text area, we need to check each one for the link
                    $url = self::attempt_url_compatibility($content, $url);
                    $content = self::deleteLink($post, $url, $anchor, $content);
                }else{
                    $content = self::deleteImage($post, $url, $content);
                }

                // if we've managed to delete the link
                if(md5($content) !== $old_content){
                    // set a flag saying we've done it
                    $deleted = true;
                    // update the meta fields
                    if($post->type === 'post'){
                        update_post_meta($post->id, $field, $content);
                    }else{
                        update_term_meta($post->id, $field, $content);
                    }
                    // and exit the loop
                    break;
                }
            }
        }

        /**
         * Allows the user to delete links from a custom content location
         **/
        do_action('wpil_meta_content_data_delete_link', $post->id, $post->type, $url, $anchor, $is_image);

        return $deleted;
    }

    public static function ajax_delete_selected_links(){
        Wpil_Base::verify_nonce('delete-selected-links');

        if(!isset($_POST['links']) || empty($_POST['links'])){
            wp_send_json(array('error' => array(
                'title' => __('Data Error', 'wpil'),
                'text'  => __('No links were selected, please reload the page and try again.', 'wpil'),
            )));
        }

        // ignore the object cache if it's present
        Wpil_Base::ignore_external_object_cache();

        // Remove any hooks that may interfere with AJAX requests
        Wpil_Base::remove_problem_hooks();

        // sort the links by post so that we get a higher efficiency out of removing them
        $links = array();
        foreach($_POST['links'] as $link){
            $pid = $link['post_id'] . '_' . $link['post_type'];
            if(!isset($links[$pid])){
                $links[$pid] = array('post_id' => $link['post_id'], 'post_type' => $link['post_type'], 'url_anchor' => array());
            }
            $links[$pid]['url_anchor'][] = array($link['url'], $link['anchor']);
        }

        // now remove as many links as we can in 10 seconds
        $removed = array();
        foreach($links as $dat){
            if(!Wpil_Base::overTimeLimit(0, 10)){
                $deleted = self::delete($dat);
                if(!empty($deleted)){
                    $removed[] = $deleted;
                }
            }
        }

        // if we've removed the links
        if(!empty($removed) && !empty($removed[0])){
            Wpil_Telemetry::log_event('delete_selected_links', array('count' => count($removed)));

            // send back the list of links that have been removed
            wp_send_json(array('progress' => $removed));
        }else{
            // If no links have been removed, send back the completion message
            wp_send_json(array('success' => array(
                'title' => __('Success', 'wpil'),
                'text'  => __('All of the links that could be removed have been removed.', 'wpil'),
            )));
        }
    }


    /**
     * Check if link is internal
     *
     * @param $url
     * @return bool
     */
    public static function isInternal($url)
    {
        // it's internal if there are no protocol slashes or the first char is a single slash
        if (strpos($url, '//') === false || 0 === strpos($url, '/')) {
            return true;
        }

        if (self::markedAsExternal($url)) {
            return false;
        }

        if(self::isAffiliateLink($url)){
            return false;
        }

        $localhost = parse_url(get_home_url(), PHP_URL_HOST);
        $host = parse_url($url, PHP_URL_HOST);

        if (!empty($localhost) && !empty($host)) {
            $localhost = str_replace('www.', '', $localhost);
            $host = str_replace('www.', '', $host);
            if ($localhost == $host) {
                return true;
            }

            $internal_domains = Wpil_Settings::getInternalDomains();

            if(in_array($host, $internal_domains, true)){
                return true;
            }
        }

        // if the user is filtering staging, check if this is a filtered staging url
        $filtered_url = self::filter_live_to_staging_domain($url);
        $host = parse_url($filtered_url, PHP_URL_HOST);
        if($filtered_url !== $url && !empty($localhost) && !empty($host)){
            $localhost = str_replace('www.', '', $localhost);
            $host = str_replace('www.', '', $host);
            if ($localhost == $host) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks to see if the url can be traced to a post.
     * The main idea is to cut down on the number of external links that are sent through the URL-to-post functionality,
     * so this doesn't have to be an exhaustive search with complete validation.
     * 
     * @param string $url The url to check
     * @return bool Is the url one that we think we can trace?
     **/
    public static function is_traceable($url = ''){
        if(empty($url)){
            return false;
        }

        // clean up the url a little bit for consistent searching
        $url = str_replace('www.', '', $url);

        $host = parse_url($url, PHP_URL_HOST);

        // if there's no host
        if(empty($host)){
            // most likely it's traceable because it _should_ be relative
            return true;
        }

        $localhost = parse_url(get_home_url(), PHP_URL_HOST);

        // if the host matches localhost
        if(empty($localhost) || $localhost === $host || $host === str_replace('www.', '', $localhost)){
            // it is tracable
            return true;
        }

        // if the user is filtering staging, check if this is a filtered staging url
        $filtered_url = self::filter_live_to_staging_domain($url);
        $host = parse_url($filtered_url, PHP_URL_HOST);
        if($filtered_url !== $url && !empty($localhost) && !empty($host)){
            $localhost = str_replace('www.', '', $localhost);
            $host = str_replace('www.', '', $host);
            if ($localhost == $host) {
                return true;
            }
        }

        /* return false if:
            * there's is a host
            * it doesn't match the home site's
            * and trying to filter it doesn't work
        */
        return false;
    }

    /**
     * Checks if the url is a known cloaked affiliate link.
     * 
     * @param string $url The url to be checked
     * @return bool Whether or not the url is to a cloaked affiliate link. 
     **/
    public static function isAffiliateLink($url){
        // if ThirstyAffiliates is active
        if(class_exists('ThirstyAffiliates')){
            $links = self::getThirstyAffiliateLinks();

            if(isset($links[$url])){
                return true;
            }
        }


        return false;
    }

    /**
     * Checks to see if the given url goes to a sponsored domain
     **/
    public static function isSponsoredLink($url){
        $domains = Wpil_Settings::getSponsoredDomains();

        // if there are no sponsored domains, return false now
        if(empty($domains)){
            return false;
        }

        // get the url's domain
        $url_domain = wp_parse_url(str_replace('://www.', '://', $url), PHP_URL_HOST);

        if(empty($url_domain)){
            return false;
        }

        return (in_array($url_domain, $domains, true)) ? true: false;
    }

    /**
     * Check if link is broken
     *
     * @param $url
     * @return bool|int
     */
    public static function getResponseCode($url)
    {
        // if a url was provided and it's formatted correctly
        if(!empty($url) && (parse_url($url, PHP_URL_SCHEME) || substr($url, 0, 1) == '/') ){

            // make sure the url is absolute so cURL doesn't have a problem with it
            $url = Wpil_Settings::makeLinkAbsolute($url);

            // make the call
            return self::getResponseCodeCurl($url);
        }

        return 925;
    }

    public static function getResponseCodeCurl($url, $follow_youtube_redirects = true) {
        $c = curl_init(html_entity_decode($url));
        $user_ip = get_transient('wpil_site_ip_address');
        
        // if the ip transient isn't set yet
        if(empty($user_ip)){
            // get the site's ip
            $host = gethostname();
            $user_ip = gethostbyname($host);

            // if that didn't work
            if(empty($user_ip)){
                // get the curent user's ip as best we can
                if (!empty($_SERVER['HTTP_CLIENT_IP'])){
                    $user_ip = $_SERVER['HTTP_CLIENT_IP'];
                }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
                    $user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }else{
                    $user_ip = $_SERVER['REMOTE_ADDR'];
                }
            }
        }

        // save the ip so we don't have to look it up next time
        set_transient('wpil_site_ip_address', $user_ip, (10 * MINUTE_IN_SECONDS));

        // create the list of headers to make the cURL request with
        $request_headers = array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
//            'Accept-Encoding: gzip, deflate, br',
            'Accept-Language: en-US,en;q=0.9',
            'Cache-Control: max-age=0, no-cache',
            'Keep-Alive: 300',
            'Pragma: ',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?0',
            'Host: ' . parse_url($url, PHP_URL_HOST),
            'Referer: ' . site_url(),
            'User-Agent: ' . WPIL_DATA_USER_AGENT,
        );

        // if this isn't a youtube link
        if(!self::is_youtube_link($url)){
            // set the encoding headers
            $request_headers[] = 'Accept-Encoding: gzip, deflate, br';
        }

        if(!empty($user_ip)){
            $request_headers[] = 'X-Real-Ip: ' . $user_ip;
        }

        curl_setopt($c, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($c, CURLOPT_HEADER, true);
        curl_setopt($c, CURLOPT_FILETIME, true);
        curl_setopt($c, CURLOPT_HTTPGET, true);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($c, CURLOPT_MAXREDIRS, 30);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($c, CURLOPT_TIMEOUT, 20);
        curl_setopt($c, CURLOPT_COOKIEFILE, null);

        // if this isn't a youtube link
        if(!self::is_youtube_link($url)){
            // don't include the response body
            curl_setopt($c, CURLOPT_NOBODY, true);
        }

        $curl_version = curl_version();
        if (defined('CURLOPT_SSL_FALSESTART') && version_compare(phpversion(), '7.0.7') >= 0 && version_compare($curl_version['version'], '7.42.0') >= 0) {
            curl_setopt($c, CURLOPT_SSL_FALSESTART, true);
        }

        //Set the proxy configuration. The user can provide this in wp-config.php
        if(defined('WP_PROXY_HOST')){
            curl_setopt($c, CURLOPT_PROXY, WP_PROXY_HOST);
        }
        if(defined('WP_PROXY_PORT')){
            curl_setopt($c, CURLOPT_PROXYPORT, WP_PROXY_PORT);
        }
        if(defined('WP_PROXY_USERNAME')){
            $auth = WP_PROXY_USERNAME;
            if(defined('WP_PROXY_PASSWORD')){
                $auth .= ':' . WP_PROXY_PASSWORD;
            }
            curl_setopt($c, CURLOPT_PROXYUSERPWD, $auth);
        }

        //Make CURL return a valid result even if it gets a 404 or other error.
        curl_setopt($c, CURLOPT_FAILONERROR, false);

        $headers = curl_exec($c);
        if(defined('CURLINFO_RESPONSE_CODE')){
            $http_code = intval(curl_getinfo($c, CURLINFO_RESPONSE_CODE));
        }else{
            $info = curl_getinfo($c);
            if(isset($info['http_code']) && !empty($info['http_code'])){
                $http_code = intval($info['http_code']);
            }else{
                $http_code = 0;
            }
        }

        if(self::is_youtube_link($url)){
            $http_code = self::check_youtube_content($headers, $url, $c, $follow_youtube_redirects);
        }

        $curl_error_code = curl_errno($c);

        // if the curl request ultimately got a http code
        if(!empty($http_code)){
            // return the code
            return $http_code;
        }elseif(!empty($curl_error_code)){
            // if we got a curl error, return that
            return $curl_error_code;
        }

        return 925;
    }

    /**
     * Check if link is broken
     *
     * @param $url
     * @return array
     */
    public static function getResponseCodes($urls = array(), $head_call = false)
    {
        $site_protocol = (is_ssl()) ? 'https:': 'http:';
        $return_urls = array();
        $good_urls = array();
        foreach($urls as $url){
            $decoded = urldecode($url); // TODO: review and make sure that there aren't a lot of false positive results after the 2.5.8 update // remove this note when we get to 2.6.3
            if(!empty($decoded) && $decoded !== $url){
                $url = $decoded;
            }

            // if a url was provided and it's formatted correctly, add it to the list to process
            if(!empty($url) && (parse_url($url, PHP_URL_SCHEME) || substr($url, 0, 2) == '//') && parse_url($url, PHP_URL_HOST)){
                // the current URL is using a relative protocol
                if(strpos($url, '//') === 0){
                    // add the current site's protocol to it so cURL doesn't have a problem with it
                    $url = $site_protocol . $url;
                }
                $good_urls[] = $url;
            }elseif(!empty($url) && strpos($url, '/') === 0){
                // if the URL is relative, make it absolute for the so we can scan it
                $good_urls[] = Wpil_Settings::makeLinkAbsolute($url);
            }else{
                // if it wasn't, add it to the return list as a 925
                $return_urls[$url] = 925;
            }
        }

        // if there are good urls
        if(!empty($good_urls)){
            // get the curl response codes for each of them
            $codes = self::getResponseCodesCurl($good_urls, $head_call);
            // and merge the reponses into the return links
            $return_urls = array_merge($return_urls, $codes);
        }

        return $return_urls;
    }

    public static function getResponseCodesCurl($urls, $head_call = false) {
        $start = microtime(true);
        $redirect_codes = array(301, 302, 307);
        $user_ip = get_transient('wpil_site_ip_address');
        $return_urls = array();

        // if the ip transient isn't set yet
        if(empty($user_ip)){
            // get the site's ip
            $host = gethostname();
            $user_ip = gethostbyname($host);

            // if that didn't work
            if(empty($user_ip)){
                // get the curent user's ip as best we can
                if (!empty($_SERVER['HTTP_CLIENT_IP'])){
                    $user_ip = $_SERVER['HTTP_CLIENT_IP'];
                }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
                    $user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }else{
                    $user_ip = $_SERVER['REMOTE_ADDR'];
                }
            }
        }

        // save the ip so we don't have to look it up next time
        set_transient('wpil_site_ip_address', $user_ip, (10 * MINUTE_IN_SECONDS));

        // create the multihandle
        $mh = curl_multi_init();

        // if we're debugging curl
        if(WPIL_DEBUG_CURL){
            // setup the log files
            $verbose = fopen(trailingslashit(WP_CONTENT_DIR) . 'curl_connection_log.log', 'a');     // logs the actions that curl goes through in contacting the server
            $connection = fopen(trailingslashit(WP_CONTENT_DIR) . 'curl_connection_info.log', 'a'); // logs the result of contacting the server.
        }

        $handles = array();
        foreach($urls as $url){
            // create the curl handle and add it to the list keyed with the url its using
            $handles[$url] = curl_init(html_entity_decode($url));

            // create the list of headers to make the cURL request with
            $request_headers = array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
//                'Accept-Encoding: gzip, deflate, br',
                'Accept-Language: en-US,en;q=0.9',
                'Cache-Control: max-age=0, no-cache',
                'Pragma: ',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
                'Sec-Fetch-User: ?0',
                'Host: ' . parse_url($url, PHP_URL_HOST),
                'Referer: ' . site_url(),
                'User-Agent: ' . WPIL_DATA_USER_AGENT,
            );

            // if this isn't a youtube link
            if(!self::is_youtube_link($url)){
                // set the encoding headers
                $request_headers[] = 'Accept-Encoding: gzip, deflate, br';
            }

            if(!empty($user_ip)){
                $request_headers[] = 'X-Real-Ip: ' . $user_ip;
            }

            if($head_call){
                $request_headers[] = 'Connection: close';
            }else{
                $request_headers[] = 'Connection: keep-alive';
                $request_headers[] = 'Keep-Alive: 300';
            }

            curl_setopt($handles[$url], CURLOPT_HTTPHEADER, $request_headers);
            curl_setopt($handles[$url], CURLOPT_HEADER, true);
            curl_setopt($handles[$url], CURLOPT_FILETIME, true);
            curl_setopt($handles[$url], CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($handles[$url], CURLOPT_MAXREDIRS, 10);
            curl_setopt($handles[$url], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handles[$url], CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($handles[$url], CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($handles[$url], CURLOPT_TIMEOUT, 15);
            curl_setopt($handles[$url], CURLOPT_COOKIEFILE, null);
            curl_setopt($handles[$url], CURLOPT_FORBID_REUSE, true);
            curl_setopt($handles[$url], CURLOPT_FRESH_CONNECT, true);
            curl_setopt($handles[$url], CURLOPT_COOKIESESSION, true);
            curl_setopt($handles[$url], CURLOPT_SSL_VERIFYPEER, false);

            // if this isn't a youtube link
            if(!self::is_youtube_link($url)){
                // don't include the response body
                curl_setopt($handles[$url], CURLOPT_NOBODY, true);
            }

            $curl_version = curl_version();
            if (defined('CURLOPT_SSL_FALSESTART') && version_compare(phpversion(), '7.0.7') >= 0 && version_compare($curl_version['version'], '7.42.0') >= 0) {
                curl_setopt($handles[$url], CURLOPT_SSL_FALSESTART, true);
            }

            if(false === $head_call){
                curl_setopt($handles[$url], CURLOPT_HTTPGET, true);
            }

            //Set the proxy configuration. The user can provide this in wp-config.php
            if(defined('WP_PROXY_HOST')){
                curl_setopt($handles[$url], CURLOPT_PROXY, WP_PROXY_HOST);
            }
            if(defined('WP_PROXY_PORT')){
                curl_setopt($handles[$url], CURLOPT_PROXYPORT, WP_PROXY_PORT);
            }
            if(defined('WP_PROXY_USERNAME')){
                $auth = WP_PROXY_USERNAME;
                if(defined('WP_PROXY_PASSWORD')){
                    $auth .= ':' . WP_PROXY_PASSWORD;
                }
                curl_setopt($handles[$url], CURLOPT_PROXYUSERPWD, $auth);
            }

            //Make CURL return a valid result even if it gets a 404 or other error.
            curl_setopt($handles[$url], CURLOPT_FAILONERROR, false);

            // if we're debugging curl
            if(WPIL_DEBUG_CURL){
                // set curl to verbose logging and set where to write it to
                curl_setopt($handles[$url], CURLOPT_VERBOSE, true);
                curl_setopt($handles[$url], CURLOPT_STDERR, $verbose);
            }

            // and add it to the multihandle
            curl_multi_add_handle($mh, $handles[$url]);
        }

        // if there are handles, execute the multihandle
        if(!empty($handles)){
            do {
                $status = curl_multi_exec($mh, $active);
                if ($active) {
                    curl_multi_select($mh);
                }
            } while ($active && $status == CURLM_OK);
        }

        // get any error codes from the operations
        $curl_codes = array();
        foreach($handles as $handle){
            $info = curl_multi_info_read($mh);
            $handle_int = intval($info['handle']);
            if(isset($info['result'])){
                $curl_codes[$handle_int] = $info['result'];
            }else{
                $curl_codes[$handle_int] = 0;
            }
        }

        // when the multihandle is finished, go over the handles and process the responses
        foreach($handles as $handle_url => $handle){
            $handle_int = intval($handle);
            $http_code = intval(curl_getinfo($handle, CURLINFO_RESPONSE_CODE));
            $curl_error_code = (isset($curl_codes[$handle_int])) ? $curl_codes[$handle_int]: 0;

            if(self::is_youtube_link($handle_url)){
                $http_code = self::check_youtube_content(curl_multi_getcontent($handle), $handle_url, $handle);
            }

            // if we're debugging curl
            if(WPIL_DEBUG_CURL){
                // save the results of the connection
                fwrite($connection, print_r(curl_getinfo($handle),true));
            }

            // if the curl request ultimately got a http code
            if(!empty($http_code)){
                // if the code is for a redirect and we have some time to chase it
                if(in_array($http_code, $redirect_codes) && (microtime(true) - $start) < 15){
                    // get the url from the curl data
                    $new_url = trim(curl_getinfo($handle, CURLINFO_EFFECTIVE_URL));
                    if(!empty($new_url)){
                        // call _that_ url to see what happens and add the response to the link list
                        $return_urls[$handle_url] = self::getResponseCodeCurl($new_url);
                    }
                }else{
                    // if the code wasn't a redirect or we don't have the time to check, add the code to the list
                    $return_urls[$handle_url] =  $http_code;
                }
            }elseif(!empty($curl_error_code)){
                // curl error list: https://curl.haxx.se/libcurl/c/libcurl-errors.html
                // useful for diagnosing errors < 100
                $return_urls[$handle_url] = $curl_error_code;
            }

            // if a status hasn't been added to the link yet
            if(!isset($return_urls[$handle_url])){
                // mark it as 925
                $return_urls[$handle_url] = 925;
            }

            // close the current handle
            curl_multi_remove_handle($mh, $handle);
            curl_close($handle);
        }

        // close the multi handle
        curl_multi_close($mh);

        return $return_urls;
    }

    /**
     * Get link title by URL
     */
    public static function getLinkTitle()
    {
        Wpil_Base::verify_nonce('wpil_suggestion_nonce');
        if(!is_admin()){
            die();
        }

        $link = !empty($_POST['link']) ? esc_url_raw(trim($_POST['link'])): '';
        $title = '';
        $id = '';
        $type = '';
        $date = __('Not Available', 'wpil');

        if ($link) {
            if (self::isInternal($link)) {
                $post = Wpil_post::getPostByLink($link);
                if(!empty($post) && isset($post->type)){
                    $title = $post->getTitle();
                    $link = $post->getSlug();
                    $id = $post->id;
                    $type = $post->type;

                    if($post->type === 'post'){
                        $date = get_the_date(get_option('date_format', 'F j, Y'), $post->id);
                    }
                }
            }else{
                $title = __('External Page URL', 'wpil');
            }

            wp_send_json([
                'title' => esc_html($title),
                'link' => esc_url_raw($link),
                'id' => $id,
                'type' => $type,
                'date' => $date
            ]);
        }

        die;
    }

    /**
     * Remove class "wpil_internal_link" from links
     */
    public static function removeLinkClass()
    {
        global $wpdb;

        $wpdb->get_results("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, 'wpil_internal_link', '') WHERE post_content LIKE '%wpil_internal_link%'");
    }

    /**
     * Add link to ignore list
     */
    public static function addLinkToIgnore()
    {
        $error = false;
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $type = !empty($_POST['type']) ? sanitize_text_field($_POST['type']) : null;
        $site_url = (isset($_POST['site_url']) && !empty($_POST['site_url'])) ? esc_url_raw($_POST['site_url']): null;
        $origin = (isset($_POST['post_origin'])) ? $_POST['post_origin']: null;

        if ($id && $type) {
            // if the object is known to be external
            if($origin === 'external'){
                // create an external post object
                $post = new Wpil_Model_ExternalPost(array('post_id' => $id, 'type' => $type, 'site_url' => $site_url));
            }else{
                // otherwise, assume it's an internal post object
                $post = new Wpil_Model_Post($id, $type);
            }

            $link = $post->getLinks()->view;

            if (!empty($link)) {
                $links = get_option('wpil_ignore_links');
                if (!empty($links)) {
                    $links_array = explode("\n", $links);
                    if (!in_array($link, $links_array)) {
                        $links .= "\n" . $link;
                    }
                } else {
                    $links = $link;
                }
                // clear any ignore link cache that exists
                delete_transient('wpil_ignore_links');
                // save the ignore link
                update_option('wpil_ignore_links', $links);
            } else {
                $error = 'Empty post link';
            }
        } else {
            $error = 'Wrong data';
        }

        echo json_encode(['error' => $error]);
        die;
    }

    /**
     * Clean link from trash symbols
     *
     * @param $link
     * @return string
     */
    public static function clean($link)
    {
        $link = str_replace(['http://', 'https://', '//www.'], '//', strtolower(trim($link)));
        if (substr($link, -1) == '/') {
            $link = substr($link, 0, -1);
        }

        return $link;
    }

    /**
     * Processes and formats urls for comparative purposes inside of Link Whisper.
     * That way, we have a nice standard for comparing if links are basicalls the same, even if there's a few differences in text.
     * Not intended for use when inserting links, its just for when checking to see if two links are the same
     **/
    public static function normalize_url($url){
        // first clean the url
        $url = self::clean($url);
        // decode the double encoded & signs
        $url = str_replace(array('&#038;', '&&amp;'), '&', $url);
        // decode the normally encoded & signs
        $url = str_replace(array('#038;', '&amp;'), '&', $url);

        // and return the url
        return $url;
    }

    /**
     * Updates an existing link in a post with a new link
     * 
     * @param $post_id
     * @param $post_type
     * @param $old_link
     * @param $new_link
     * @param $anchor (Only used for anchors with no attrs)
     **/
    public static function updateExistingLink($post_id = 0, $post_type = '', $old_link = '', $new_link = '', $anchor = ''){
        if(empty($post_id) || empty($post_type) || empty($old_link) || empty($new_link)){
            return false;
        }

        // get the post we want to update
        $post = new Wpil_Model_Post($post_id, $post_type);

        // if there is a post, update it with the new link
        if(!empty($post)){
            $content = $post->getCleanContent();
            self::updateLinkUrl($content, $old_link, $new_link, $anchor);
            Wpil_Editor_YooTheme::updateExistingLink($content, $old_link, $new_link, $anchor);
            $updated = $post->updateContent($content);

            // if the link hasn't been updated and ACF is active
            if(!Wpil_Base::action_happened('link_url_updated') && Wpil_Settings::get_acf_active()){
                // see if the link is inside the ACF content
                $updated = Wpil_Post::updateAdvancedCustomFieldLink($post, $old_link, $new_link, $anchor);
            }

            Wpil_Base::clear_tracked_action('link_url_updated');
            Wpil_Post::editors('updateExistingLink', array($post, $old_link, $new_link, $anchor));
            $updated = (!$updated) ? Wpil_Base::action_happened('link_url_updated'): $updated;

            return $updated;
        }

        return false;
    }

    /**
     * Updates the URLs of links inside post content!
     **/
    public static function updateLinkUrl(&$content = '', $old_link = '', $new_link = '', $anchor = ''){
        $old = md5($content);
        if($old_link !== '{{wpil-empty-url}}'){
            if(false !== strpos($content, $old_link)){
                $content = str_replace($old_link, $new_link, $content);
            }elseif(false !== strpos($content, str_replace(array('&', '[', ']', '{', '}'), array('&amp;', '%5B', '%5D', '%7B', '%7D'), $old_link))){ // if the link has been encoded
                $old_link = str_replace(array('&', '[', ']', '{', '}'), array('&amp;', '%5B', '%5D', '%7B', '%7D'), $old_link); // encode the version of the old link that we're working with
                $new_link = str_replace(array('&', '[', ']', '{', '}'), array('&amp;', '%5B', '%5D', '%7B', '%7D'), $new_link); // and encode the new link to maintain formatting
                $content = str_replace($old_link, $new_link, $content);
            }elseif(false !== strpos($content, urldecode($old_link))){ // if the url has been encoded
                $content = str_replace(urldecode($old_link), $new_link, $content); // remove the encoded old url and replace it with the new encoded url
            }
        }elseif($old_link === '{{wpil-empty-url}}' && !empty($anchor)){
            $new_tag = '<a href="' . $new_link . '">' . $anchor . '</a>';
            $content = preg_replace('`<a>' . preg_quote($anchor, '`') . '</a>`i', $new_tag,  $content, 1);
        }

        // log if the url update was successful
        $updated = md5($content) !== $old;
        Wpil_Base::track_action('link_url_updated', $updated);
    }

    /**
     * Check if link was marked as external
     *
     * @param $link
     * @return bool
     */
    public static function markedAsExternal($link)
    {
        $external_links = Wpil_Settings::getMarkedAsExternalLinks();

        if (in_array($link, $external_links)) {
            return true;
        }

        foreach ($external_links as $external_link) {
            if (substr($external_link, -1) == '*' && strpos($link, substr($external_link, 0, -1)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the given post is at the outbound link limit
     *
     * @param $post
     * @return bool Returns true if the post is at the limit and false if it is not.
     */
    public static function at_max_outbound_links($post)
    {
        if(empty($post)){
            return false;
        }

        $max_outbound_links = get_option('wpil_max_links_per_post', 0);

        if(empty($max_outbound_links)){
            return false;
        }

        $post_link = $post->getLinks()->view;
        $ignore_image_urls = true;//!empty(get_option('wpil_ignore_image_urls', false));
        $ignored_links = Wpil_Settings::getIgnoreLinks();
        $content = $post->getContent();

        //get all links from content
        preg_match_all('`<a[^>]*?href=(\"|\')([^\"\']*?)(\"|\')[^>]*?>([\s\w\W]*?)<\/a>|<!-- wp:core-embed\/wordpress {"url":"([^"]*?)"[^}]*?"} -->|(?:>|&nbsp;|\s)((?:(?:http|ftp|https)\:\/\/)(?:[\w_-]+(?:(?:\.[\w_-]+)+))(?:[\w.,@?^=%&:/~+#-]*[\w@?^=%&/~+#-]))(?:<|&nbsp;|\s)`i', $content, $matches);
        // if there are encoded links
        if(false !== strpos($content, '&lt;a') && false !== strpos($content, '&lt;/a&gt;')){
            // try getting encoded links too
            preg_match_all('`&lt;a[^&]*?href=(\"|\')([^\"\']*?)(\"|\')[^&]*?&gt;([\s\w\W]*?)&lt;\/a&gt;`i', $content, $matches2);
            if(!empty($matches2) && !empty($matches2[0])){
                foreach($matches2 as $key => $values){
                    $matches[$key] = array_merge($matches[$key], $values);
                }

                $m_count = count($matches2[0]);
                for($i = 0; $i < $m_count; $i++){
                    $matches[5][] = '';
                    $matches[6][] = '';
                }
            }
        }

        // make a counter for the links
        $outbound_count = 0;

        //make array with results
        foreach ($matches[0] as $key => $value) {
            $url = '';
            if (!empty($matches[2][$key]) && !empty($matches[4][$key]) && !Wpil_Report::isJumpLink($matches[2][$key], $post_link)) {
                $url = trim($matches[2][$key]);
            }elseif(!empty($matches[5][$key]) && !Wpil_Report::isJumpLink($matches[5][$key], $post_link) ||  // if this is an embed link
                    !empty($matches[6][$key]) && !Wpil_Report::isJumpLink($matches[6][$key], $post_link))    // if this is a link that is inserted in the content as a straight url // Mostly this means its an embed but as case history grows I'll come up with a better notice for the user
            {
                if(!empty($matches[5][$key])){
                    $url = trim($matches[5][$key]);
                }else{
                    $url = trim($matches[6][$key]);
                }
            }

            // skip if the url is empty
            if(empty($url)){
                continue;
            }

            // ignore any links that are being used as buttons
            if(false !== strpos($url, 'javascript:void(0)')){
                continue;
            }

            // if we're making a point to ignore image urls
            if($ignore_image_urls){
                // if the link is an image url, skip to the next match
                if(preg_match('/\.jpg|\.jpeg|\.svg|\.png|\.gif|\.ico|\.webp/i', $url)){
                    continue;
                }
            }

            // if we're ignoring links
            if(!empty($ignored_links)){
                // check to see if this link is on the ignore list
                if(!empty(array_intersect($ignored_links, array($url)))){
                    // if it is, skip to the next
                    continue;
                }else{
                    // if the link wasn't detected with the simple check, see if there's a partial match possible. Mostly this is to allow domain-based ignoring
                    foreach($ignored_links as $link){
                        if(false !== strpos($url, $link)){
                            continue 2;
                        }
                    }
                }
            }

            // filter the URLs with an internal check so users can choose to ignore outbound external or outbound internal if they wish.
            /**
             * @param bool $count_link Should the link be counted in the total? Default is true.
             * @param bool $internal If the current link is internal or not.
             * @param string $url The URL we're currently looking at
             **/
            if(!apply_filters('wpil_max_outbound_links_filter_internal', true, self::isInternal($url), $url)){
                continue;
            }

            $outbound_count++;
        }

        return ($outbound_count >= $max_outbound_links) ? true: false;
    }

    /**
     * Checks to see if the current post is at the limit for Inbound Internal links
     * @param $post
     * @return bool
     **/
    public static function at_max_inbound_links($post){
        if(empty($post)){
            return false;
        }

        $max_inbound_links = get_option('wpil_max_inbound_links_per_post', 0);

        if(empty($max_inbound_links)){
            return false;
        }

        // get the inbound link counts from the stored data
        $inbound_count = $post->getInboundInternalLinks(true);

        return ($inbound_count >= $max_inbound_links) ? true: false;
    }

    /**
     * Checks to see if the supplied text contains a link.
     * The check is pretty simple at this point, just seeing if the form of an opening tag or a closing tag is present in the text
     * 
     * @param string $text
     * @return bool
     **/
    public static function hasLink($text = '', $replace_text = ''){

        // if there's no link anywhere to be seen, return false
        if(empty(preg_match('/<a [^><]*?(href|src)[^><]*?>|<\/a>/i', $text))){
            return false;
        }

        // if there is a link in the replace text, return true
        if(preg_match('/<a [^><]*?(href|src)[^><]*?>|<\/a>/i', $replace_text)){
            return true;
        }

        // if there is a link, see if it ends before the replace text
        $replace_start = mb_strpos($text, $replace_text);
        if(preg_match('/<\/a>/i', mb_substr($text, 0, $replace_start)) ){
            // if it does, no worries!
            return false;
        }elseif(preg_match('/<a [^><]*?(href|src)[^><]*?>/i', mb_substr($text, 0, $replace_start)) || preg_match('/<\/a>/i', mb_substr($text, $replace_start)) ){
            // if there's an opening tag before the replace text or somewhere after the start, then presumably the replace text is in the middle of a link
            return true;
        }

        return false;
    }


    /**
     * Checks to see if the supplied text contains a heading tag.
     * The check is pretty simple at this point, just seeing if the form of an opening tag or a closing tag is present in the text
     * 
     * @param string $text
     * @return bool
     **/
    public static function hasHeading($text = '', $replace_text = '', $sentence = ''){
        // if there's no heading anywhere to be seen, return false
        if(empty(preg_match('/<h[1-6][^><]*?>|<\/h[1-6]>|&lt;h[1-6]( |&gt;)|&lt;\/h[1-6]&gt;/i', $text))){
            return false;
        }

        // if there is a heading, see if it ends before the replace text
        $replace_start = mb_strpos($text, $sentence);
        if(preg_match('/<\/h[1-6]>|&lt;\/h[1-6]&gt;/i', mb_substr($text, 0, $replace_start)) ){
            // if it does, no worries!
            return false;
        }elseif(preg_match('/<h[1-6][^><]*?>|&lt;h[1-6].*?&gt;/i', mb_substr($text, 0, $replace_start)) || (preg_match('/<\/h[1-6]>|&lt;\/h[1-6]&gt;/i', mb_substr($text, $replace_start)) && !preg_match('/<h[1-6][^><]*?>|&lt;h[1-6].*?&gt;/i', mb_substr($text, $replace_start)) ) ){
            // if there's an opening tag before the replace text or somewhere after the start, then presumably the replace text is in the middle of a heading
            return true;
        }

        // if there is a heading in the replace text, return true
        if(substr_count($replace_text, $sentence) > 1 && preg_match('/<h[1-6][^><]*?>|<\/h[1-6]>|&lt;h[1-6].*?&gt;|&lt;\/h[1-6]&gt;/i', $replace_text)){
            return true;
        }

        return false;
    }

    /**
     * Checks to see if the supplied text contains a script tag.
     * The check is pretty simple at this point, just seeing if the form of an opening tag or a closing tag is present in the text
     * 
     * @param string $text
     * @return bool
     **/
    public static function hasScript($text = '', $replace_text = '', $sentence = ''){
        // if there's no script tag anywhere to be seen, return false
        if(empty(preg_match('/<script[^><]*?>|<\/script>|&lt;script( |&gt;)|&lt;\/script&gt;/i', $text))){
            return false;
        }

        // if there is a script tag, see if it ends before the replace text
        $replace_start = mb_strpos($text, $sentence);
        if(preg_match('/<\/script>|&lt;\/script&gt;/i', mb_substr($text, 0, $replace_start)) ){
            // if it does, no worries!
            return false;
        }elseif(preg_match('/<script[^><]*?>|&lt;script.*?&gt;/i', mb_substr($text, 0, $replace_start)) || (preg_match('/<\/script>|&lt;\/script&gt;/i', mb_substr($text, $replace_start)) && !preg_match('/<script[^><]*?>|&lt;script.*?&gt;/i', mb_substr($text, $replace_start)) ) ){
            // if there's an opening tag before the replace text or somewhere after the start, then presumably the replace text is in the middle of a script section
            return true;
        }

        // if there is a script tag in the replace text, return true
        if(substr_count($replace_text, $sentence) > 1 && preg_match('/<script[^><]*?>|<\/script>|&lt;script.*?&gt;|&lt;\/script&gt;/i', $replace_text)){
            return true;
        }

        return false;
    }

    /**
     * Checks to see if the current slice of text contains any tags that we don't want to insert a link into
     * 
     * @param string $text
     * @return bool
     **/
    public static function checkForForbiddenTags($text, $replace_text, $sentence, $ignore_links = false){
        if(self::hasLink($text, $replace_text) && !$ignore_links){
            return true;
        }elseif(self::hasHeading($text, $replace_text, $sentence)){
            return true;
        }elseif(self::hasScript($text, $replace_text, $sentence)){
            return true;
        }

        return false;
    }

    public static function remove_all_links_from_text($text = ''){
        if(empty($text)){
            return $text;
        }

        $text = preg_replace('/<a[^>]+>(.*?)<\/a>/', '$1', $text);

        return $text;
    }

    /**
     * Gets all ThirstyAffiliate links in an array keyed with the urls.
     * Caches the results to save processing time later
     **/
    public static function getThirstyAffiliateLinks(){
        global $wpdb;
        $links = get_transient('wpil_thirsty_affiliate_links');

        if(empty($links)){
            // query for the link posts
            $results = $wpdb->get_col("SELECT `ID` FROM {$wpdb->posts} WHERE `post_type` = 'thirstylink'");

            // store a flag if there are no link posts
            if(empty($results)){
                set_transient('wpil_thirsty_affiliate_links', 'no-links', 5 * MINUTE_IN_SECONDS);
                return array();
            }

            // get the urls to the link posts
            $links = array();
            foreach($results as $id){
                $links[] = get_permalink($id);
            }

            // flip the array for easy searching
            $links = array_flip($links);

            // store the results
            set_transient('wpil_thirsty_affiliate_links', $links, 5 * MINUTE_IN_SECONDS);

        }elseif($links === 'no-links'){
            return array();
        }

        return $links;
    }

    /**
     * Checks to see if the supplied text is base64ed.
     * @param string $text The text to check if base64 encoded.
     * @param bool $skip_decode Should we skip the decoding check? Compressed data fails the check, so we need to skip it if the data could be gz-compressed.
     * @return bool True if the text is base64 encoded, false if the string is empty or not encoded
     **/
    public static function checkIfBase64ed($text = '', $skip_decode = false){
        if(empty($text) || !is_string($text)){
            return false;
        }
        $possible = preg_match('`^([A-Za-z0-9+/]{4})*?([A-Za-z0-9+/]{3}=|[A-Za-z0-9+/]{2}==)??$`', $text);

        if($possible === 0){
            return false;
        }

        if($skip_decode || !empty(mb_detect_encoding(base64_decode($text)))){
            return true;
        }

        return false;
    }

    /**
     * Filters the supplied link to change the domain from staging to live.
     * Only changes the site's domain & scheme, otherwise leaves the rest of the URL as is
     * 
     * @param string $url The url to filter
     * @return string $url The filtered URL if it's supposed to be filtered.
     **/
    public static function filter_staging_to_live_domain($url = ''){
        // if there's no url, the user isn't filtering staging urls out or relative link mode is active
        if(empty($url) || !get_option('wpil_filter_staging_url', false) || !empty(get_option('wpil_insert_links_as_relative', false)))
        {
            // return the url
            return $url;
        }

        // get the live site's url
        $live_site_url = trailingslashit(trim(get_option('wpil_live_site_url', false)));
        $staging_site_url = trailingslashit(trim(get_option('wpil_staging_site_url', false)));
        $home_url = get_home_url();

        // if there's no live site url entered, we're actually on the live site, or this isn't a staging site url
        if( empty($live_site_url) ||
            empty($staging_site_url) ||
            $live_site_url === $staging_site_url || // if the urls are the same
            false !== strpos($home_url, $live_site_url) || // if the current site is the live site
            false !== strpos($live_site_url, $home_url) || // if the current site is the live site from a different direction
            false === strpos($url, $staging_site_url) || // if the url isn't pointed at the staging site
            false === strpos($url, $home_url) || // if the url isn't pointed to the current site
            self::isRelativeLink($url) // or if the link is relative
        ){
            // return the url without changing it
            return $url;
        }

        // let's give the user a chance to filter the URL
        $new_url = apply_filters('wpil_filter_staging_url_to_live', $url, $live_site_url, $staging_site_url);
        // if he's changed the URL
        if($new_url !== $url){
            // return it with the changes
            return $new_url;
        }

        // now that we've made it past the checks, lets change the staging domain for the live one
        // first, lets try a simple URL replace and see if it's valid
        $test_url = str_replace($staging_site_url, $live_site_url, $url);

        // if there's a url and it's not changed by sending it through esc_url_raw
        if(!empty($test_url) && $test_url === esc_url_raw($test_url)){
            // it's good
            return $test_url;
        }

        // break it into pieces
        $live_site_url = wp_parse_url(sanitize_text_field($live_site_url));

        // break the staging site url into pieces
        $staging_site_url = wp_parse_url(sanitize_text_field($staging_site_url));

        // exit if either url has no host
        if( !isset($live_site_url['host']) || empty($live_site_url['host']) ||
            !isset($staging_site_url['host']) || empty($staging_site_url['host'])
        ){
            return $url;
        }

        $url = str_replace($staging_site_url['host'], $live_site_url['host'], $url);

        // if the scheme was included in both urls, and they are different
        if( isset($live_site_url['scheme']) && !empty($live_site_url['scheme']) &&
            isset($staging_site_url['scheme']) && !empty($staging_site_url['scheme']) &&
            ($live_site_url['host'] !== $staging_site_url['scheme'])
        ){
            // replace the scheme
            $pos = strpos($url, $staging_site_url['scheme']);
            if($pos !== false){
                $url = substr_replace($url, $live_site_url['scheme'], $pos, strlen($staging_site_url['scheme']));
            }
        }

        return $url;
    }

    /**
     * Filters the supplied link to change the domain from live to staging.
     * Only changes the site's domain & scheme, otherwise leaves the rest of the URL as is
     * 
     * @param string $url The url to filter
     * @return string $url The filtered URL if it's supposed to be filtered.
     **/
    public static function filter_live_to_staging_domain($url = ''){
        // if there's no url, the user isn't filtering staging urls out or relative link mode is active
        if(empty($url) || !get_option('wpil_filter_staging_url', false) || !empty(get_option('wpil_insert_links_as_relative', false)))
        {
            // return the url
            return $url;
        }

        // get the live site's url
        $live_site_url = trailingslashit(trim(get_option('wpil_live_site_url', false)));
        $staging_site_url = trailingslashit(trim(get_option('wpil_staging_site_url', false)));
        $home_url = get_home_url();

        // if there's no live site url entered, we're actually on the live site, or this isn't a staging site url
        if( empty($live_site_url) ||
            empty($staging_site_url) ||
            $live_site_url === $staging_site_url || // if the urls are the same
            false !== strpos($home_url, $live_site_url) || // if the current site is the live site
            false !== strpos($live_site_url, $home_url) || // if the current site is the live site from a different direction
            false === strpos($url, $live_site_url) || // if the url isn't pointed at the live site
            false !== strpos($url, $home_url) || // if the url is pointed to the current site
            self::isRelativeLink($url) // or if the link is relative
        ){
            // return the url without changing it
            return $url;
        }

        // let's give the user a chance to filter the URL
        $new_url = apply_filters('wpil_filter_live_url_to_staging', $url, $live_site_url, $staging_site_url);
        // if he's changed the URL
        if($new_url !== $url){
            // return it with the changes
            return $new_url;
        }

        // now that we've made it past the checks, lets change the live domain for the staging one
        // first, lets try a simple URL replace and see if it's valid
        $test_url = str_replace($live_site_url, $staging_site_url, $url);

        // if there's a url and it's not changed by sending it through esc_url_raw
        if(!empty($test_url) && $test_url === esc_url_raw($test_url)){
            // it's good
            return $test_url;
        }

        // break it into pieces
        $live_site_url = wp_parse_url(sanitize_text_field($live_site_url));

        // break the staging site url into pieces
        $staging_site_url = wp_parse_url(sanitize_text_field($staging_site_url));

        // exit if either url has no host
        if( !isset($live_site_url['host']) || empty($live_site_url['host']) ||
            !isset($staging_site_url['host']) || empty($staging_site_url['host'])
        ){
            return $url;
        }

        $url = str_replace($live_site_url['host'], $staging_site_url['host'], $url);

        // if the scheme was included in both urls, and they are different
        if( isset($live_site_url['scheme']) && !empty($live_site_url['scheme']) &&
            isset($staging_site_url['scheme']) && !empty($staging_site_url['scheme']) &&
            ($live_site_url['host'] !== $staging_site_url['scheme'])
        ){
            // replace the scheme
            $pos = strpos($url, $live_site_url['scheme']);
            if($pos !== false){
                $url = substr_replace($url, $staging_site_url['scheme'], $pos, strlen($live_site_url['scheme']));
            }
        }

        return $url;
    }

    /**
     * Checks if the link is relative
     * 
     * @param string $link
     **/
    public static function isRelativeLink($link = ''){
        if(empty($link) || empty(trim($link))){
            return false;
        }

        if(strpos($link, 'http') === false && substr($link, 0, 1) === '/'){
            return true;
        }

        // parse the URL to see if it only contains a path
        $parsed = wp_parse_url($link);
        if( !isset($parsed['host']) && 
            !isset($parsed['scheme']) && 
            isset($parsed['path']) && !empty($parsed['path'])
        ){
            return true;
        }else{
            return false;
        }
    }

    /**
     * De-translates relative links so we can track the urls to the post they're pointing to.
     * @param string $link The url that we're going to de-translate if possible
     * @return string Returns the de-translated link if we're successful and the original link if we're not
     **/
    public static function clean_translated_relative_links($link = ''){
        
        // if WPML is active and this is a relative permalink
        if(Wpil_Settings::wpml_enabled() && self::isRelativeLink($link)){
            // get the WPML main class
            global $sitepress;

            // if we can check the settings
            if(!empty($sitepress) && method_exists($sitepress, 'get_setting')){
                // find out how we're differnetiating the languages in URLs
                $set = $sitepress->get_setting('language_negotiation_type');
                // if we're doing it with directories ("/en/testing-post")
                if($set === '1'){
                    // grab the first directory since that should be the language directory
                    $bits = explode('/', trim($link, '/'));
                    $dir = isset($bits[0]) ? $bits[0]: false;

                    // if we've got a dir & its for a WPML locale
                    if(Wpil_Settings::is_supported_wpml_local($dir)){
                        // remove the dir from the link
                        $new_link = mb_substr(ltrim($link, '/'), mb_strlen($dir));
                        $link = (0 !== mb_strpos($link, '/')) ? ltrim($new_link, '/'): $new_link;
                    }
                }
            }
        }

        return $link;
    }

    /**
     * Gets the applied url redirect for the given URL.
     * Cleans up the link a little to remove query parameters.
     * @TODO: add regex url chasing support. Since this is checking redirect status of specific link, we could actually apply the rules here
     * @param string $url The url to check
     * @return string|bool Returns the redirected URL if a redirect is active, and FALSE if there's no redirect
     **/
    public static function get_url_redirection($url = ''){
        if(empty($url)){
            return false;
        }

        // return false if there are no redirects active
        if(null === self::$url_redirect_cache){
            return false;
        }elseif(empty(self::$url_redirect_cache)){
            self::$url_redirect_cache = Wpil_Settings::getRedirectionUrls();
        }

        // if the url is being redirected
        if(isset(self::$url_redirect_cache[$url])){
            // return the redirect location
            return self::$url_redirect_cache[$url];
        }

        // if that didn't work, try cleaning up the url a bit to see if that makes the difference
        $url = trailingslashit(strtok($url, '?#'));
        
        // if that works
        if(isset(self::$url_redirect_cache[$url])){
            // return the redirect location
            return self::$url_redirect_cache[$url];
        }

        // otherwise, the url is not being redirected as far as we can tell
        return false;
    }

    /**
     * Checks if the given URL points to the standard "home" locations
     **/
    public static function url_points_home($url = ''){
        if(empty($url)){
            return false;
        }

        // trim the url just in case there's a trailing whitespace or somthing
        $url = trim($url);

        // if the url is pointing to the site root
        if($url === '/'){
            return true;
        }

        // make sure the url is slashed for consistency
        $url = trailingslashit($url);

        // if the url is pointing to the home_url
        if(trailingslashit(get_home_url()) === $url){
            return true;
        }

        // if the url is pointing to the home_url
        if(trailingslashit(get_site_url()) === $url){
            return true;
        }

        // if we haven't caught it, the url probably isn't pointing to the site home url
        return false;
    }

    /**
     * Creates and returns the next tracking id for a link we create.
     * Also notes the time that the id was created and the author id for the user that created it
     **/
    public static function create_next_tracked_link_id(){
        global $wpdb;
        $table = $wpdb->prefix . 'wpil_tracked_link_ids';

        $author = get_current_user_id();
        if(empty($author)){
            $author = 0;
        }

        $wpdb->insert($table, ['creation_time' => current_time('timestamp'), 'author_id' => $author]);
        return !empty($wpdb->insert_id) ? $wpdb->insert_id: 0;
    }

    /**
     * Checks to see if the link is one that points to youtube.
     * Works for both full and shortened links
     **/
    public static function is_youtube_link($url = ''){
        if(empty($url)){
            return false;
        }

        if( false !== strpos($url, 'https://www.youtube.com') || 
            false !== strpos($url, 'https://youtube.com') || 
            false !== strpos($url, 'https://youtu.be/'))
        {
            return true;
        }

        return false;
    }

    /**
     * Checks to see if the youtube page content contains a video.
     * @param string $content
     * @param string $url
     * @param CurlHandle $handle
     **/
    public static function check_youtube_content($content = '', $url = '', $handle = '', $follow = true){
        if(empty($content) || empty($url) || empty($handle)){
            return false;
        }

        // if the URL points directly to youtube
        $slashed = trailingslashit($url);
        if( $slashed === 'https://www.youtube.com/' || 
            $slashed === 'https://youtube.com/' || 
            $slashed === 'https://youtu.be/'
        ){
            // clearly it's a good link!
            return 200;
        }

        $code = 825;

        // pull the url meta property to check it for the link's ID
        preg_match('/<meta property="og:url"[^>]*?>/', $content, $meta_url);
        preg_match('/<meta property="og:video:url"[^>]*?>/', $content, $meta_video_url);
        preg_match('/\?v=([0-9a-zA-Z\-_]*)|\/embed\/([0-9a-zA-Z\-_]*)|youtu\.be\/([0-9a-zA-Z\-_]*)\?/', $url, $matches);
        preg_match('/previewPlayabilityStatus\\\":{\\\"status\\\":\\\"([a-zA-Z]*?)\\\"/', $content, $play_status);

        if(!empty($play_status)){
            $play_status = end($play_status);
        }else{
            $play_status = false;
        }

        // if we do have the proper info
        if(!empty($meta_video_url) && !empty($matches)){
            $video_id = end($matches);
            $meta = end($meta_video_url);

            // and the video key is in the meta
            if(false !== strpos($meta, $video_id)){
                // say the video is good!
                $code = 200;
            }

            // if the video is set to be unlisted
            if(false && false !== strpos($content, '"isUnlisted":true')){
                // list it as unlisted in the system
                $code = 826;
            }
        }elseif(false !== strpos($url, 'youtube.com/embed/')){
            if(!empty($play_status) && strtolower($play_status) === 'ok'){
                $code = 200;
            }

        }else if(false !== strpos($url, 'youtube.com/channel/')){ // if the link is supposed to be pointing to a channel
            // check the last available URL to see if it still points to the channel
            $last_url = curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);

            // if they are pointing to the same url
            if(!empty($last_url) && trailingslashit($last_url) === trailingslashit($url)){

                // if we have a metatag for the page's url
                if(!empty($meta_url)){
                    $meta = end($meta_url);

                    // parse the url from the meta tag
                    if(!empty($meta)){
                        // pull the url out of the meta tag
                        preg_match('/content="(.*?)"/', $meta, $meta_url_s);
                        
                        if(!empty($meta_url_s)){
                            $meta_url_s = end($meta_url_s);
                            if(!empty($meta_url_s)){
                                $meta = $meta_url_s;
                            }
                        }
                    }

                    // if the url is inside the metatag
                    if(!empty($meta) && is_string($meta) && false !== strpos($url, $meta)){
                        // the channel exists!
                        $code = 200;
                    }else{
                        // the channel seems to be gone
                        $code = 827;
                    }
                }else{
                    // if we don't, it seems the channel was removed
                    $code = 827;
                }
            }else{
                // if it doesn't, it seems it was removed
                $code = 827;
            }
        }else if(false !== strpos($url, 'youtube.com/@')){ // if the link is supposed to be pointing to a channel
            // check the last available URL to see if it still points to the channel
            $last_url = curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);

            // if they are pointing to the same url
            if(!empty($last_url) && trailingslashit(str_replace('www.', '', $last_url)) === trailingslashit(str_replace('www.', '', $url))){
                // extract the baseURL if possible
                preg_match('/"canonicalBaseUrl":"(\/@[^"]*?)"/', $content, $base_url);

                // if we have a metatag for the page's url
                if(!empty($base_url)){
                    $meta = end($base_url);

                    // if the url is inside the metatag
                    if(!empty($meta) && is_string($meta) && false !== strpos($url, $meta)){
                        // the channel exists!
                        $code = 200;
                    }else{
                        // the channel seems to be gone
                        $code = 827;
                    }
                }else{
                    // if we don't, it seems the channel was removed
                    $code = 827;
                }
            }else{
                // if it doesn't, it seems it was removed
                $code = 827;
            }
        }

        // if the url appears broken
        if($code === 825 && $follow){
            // get the headers to see if the page is a redirect that wasn't chased
            $headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
            $headers = substr($content, 0, $headerSize);
            if(!empty($headers)){
                $headers = array_map('trim', explode("\r\n", trim($headers)));
                // if this is a redirected page
                if(in_array('HTTP/2 303', $headers)){
                    // see if there's a location to redirect to
                    foreach($headers as $header){
                        // if there is
                        if(false !== strpos($header, 'location:')){
                            // see if it's to a youtube video
                            $bits = explode('location:', $header);
                            $possible_url = trim($bits[1]);
                            if(self::is_youtube_link($possible_url)){
                                // if it is, try pulling the code from the new location and return the result of that
                                return self::getResponseCodeCurl($possible_url, false);
                            }
                        }
                    }
                }
            }
        }

        return $code;
    }
}
