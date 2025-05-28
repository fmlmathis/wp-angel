<?php

/**
 * Recipe editor
 *
 * Class Wpil_Editor_WPRecipe
 */
class Wpil_Editor_WPRecipe
{

    private static $action_tracker = array();

    /**
     * Obtains the WP Recipe content from the fields that the user has selected.
     * Not intended for saving!
     **/
    public static function getPostContent($post_id){
        $content = '';

        // try getting the fields that are available
        $fields = Wpil_Editor_WPRecipe::get_selected_fields();

        // if we have fields
        if(!empty($fields)){
            // look over each of them
            foreach($fields as $field_name => $field_indexes){
                // try pulling the data for them
                $field_data = get_post_meta($post_id, $field_name, true);
                if(empty($field_data)){
                    continue;
                }

                // if we're dealing with an array of indexes
                if(is_array($field_indexes)){
                    // go over each index
                    foreach($field_indexes as $index => $val){
                        // "unkey" the index so we can search the arrayy data for it
                        $ind = str_replace('wprm_', '', $index);
                        // and over each data row
                        foreach($field_data as $key => $data){
                            $field_key = str_replace('wprm_', '', $field_name);
                            if(!empty($field_key) && is_array($data) && isset($data[$field_key])){
                                foreach($data[$field_key] as $inst_index => $inst_data){
                                    // and check to see if there's text in our selected index
                                    if(isset($inst_data[$ind]) && is_string($inst_data[$ind]) && !empty($inst_data[$ind])){
                                        $content .= "\n" . $inst_data[$ind];
                                    }
                                }
                            }else{
                                // and check to see if there's text in our selected index
                                if(isset($data[$ind]) && is_string($data[$ind]) && !empty($data[$ind])){
                                    $content .= "\n" . $data[$ind];
                                }
                            }
                        }
                    }
                }elseif(is_string($field_data) && !empty($field_data)){
                    $content .= "\n" . $field_data;
                }
            }
        }

        return $content;
    }

    /**
     * Add links
     *
     * @param $meta
     * @param $post_id
     */
    public static function addLinks($meta, $post_id, &$content)
    {
        if (self::wprm_active($post_id)) {
            // try getting the fields that are available
            $fields = Wpil_Editor_WPRecipe::get_selected_fields();

            // if we have fields
            if(!empty($fields)){
                // look over each of them
                foreach($fields as $field_name => $field_indexes){
                    // try pulling the data for them
                    $field_data = get_post_meta($post_id, $field_name, true);
                    if(empty($field_data)){
                        continue;
                    }

                    // set the linking flag so we can know if we need to update the data
                    $inserted = false;

                    foreach ($meta as $link) {
                        $force_insert = (isset($link['keyword_data']) && !empty($link['keyword_data']->force_insert)) ? true: false;
                        $changed_sentence = Wpil_Post::getSentenceWithAnchor($link);

                        // if we're dealing with an array of indexes
                        if(is_array($field_indexes)){
                            // go over each index
                            foreach($field_indexes as $index => $val){
                                // "unkey" the index so we can search the arrayy data for it
                                $ind = str_replace('wprm_', '', $index);
                                // and over each data row
                                foreach($field_data as &$data){
                                    $field_key = str_replace('wprm_', '', $field_name);
                                    if(!empty($field_key) && is_array($data) && isset($data[$field_key])){
                                        foreach($data[$field_key] as &$inst_data){
                                            // and check to see if there's text in our selected index
                                            if( isset($inst_data[$ind]) && 
                                                is_string($inst_data[$ind]) && 
                                                !empty($inst_data[$ind]) && 
                                                mb_strpos($inst_data[$ind], $link['sentence']) !== false)
                                            {
                                                $inserted = Wpil_Post::insertLink($inst_data[$ind], $link['sentence'], $changed_sentence, $force_insert);
                                                self::track_action('link_inserted', $inserted);
                                                if($inserted){
                                                    break 3;
                                                }
                                            }
                                        }
                                    }else{
                                        // and check to see if there's text in our selected index
                                        if(isset($data[$ind]) && is_string($data[$ind]) && !empty($data[$ind]) && mb_strpos($data[$ind], $link['sentence']) !== false){
                                            $inserted = Wpil_Post::insertLink($data[$ind], $link['sentence'], $changed_sentence, $force_insert);
                                            self::track_action('link_inserted', $inserted);
                                            if($inserted){
                                                break 2;
                                            }
                                        }
                                    }
                                }
                            }
                        }elseif(is_string($field_data) && !empty($field_data) && mb_strpos($field_data, $link['sentence']) !== false){
                            $inserted = Wpil_Post::insertLink($field_data, $link['sentence'], $changed_sentence, $force_insert);
                            self::track_action('link_inserted', $inserted);
                        }
                    }
                    if(self::action_happened('link_inserted')){
                        Wpil_Base::track_action('link_inserted', true);
                        update_post_meta($post_id, $field_name, $field_data);
                        self::clear_tracked_action('link_inserted');
                    }
                }
            }
        }
    }

    /**
     * Delete link
     *
     * @param $post_id
     * @param $url
     * @param $anchor
     */
    public static function deleteLink($post_id, $url, $anchor)
    {
        if (self::wprm_active($post_id)) {
            // try getting the fields that are available
            $fields = Wpil_Editor_WPRecipe::get_selected_fields();

            // if we have fields
            if(!empty($fields)){
                // look over each of them
                foreach($fields as $field_name => $field_indexes){
                    // try pulling the data for them
                    $field_data = get_post_meta($post_id, $field_name, true);
                    if(empty($field_data)){
                        continue;
                    }

                    // if we're dealing with an array of indexes
                    if(is_array($field_indexes)){
                        // go over each index
                        foreach($field_indexes as $index => $val){
                            // "unkey" the index so we can search the arrayy data for it
                            $ind = str_replace('wprm_', '', $index);
                            // and over each data row
                            foreach($field_data as &$data){
                                $field_key = str_replace('wprm_', '', $field_name);
                                if(!empty($field_key) && is_array($data) && isset($data[$field_key])){
                                    foreach($data[$field_key] as &$inst_data){
                                        // and check to see if there's text in our selected index
                                        if( isset($inst_data[$ind]) && 
                                            is_string($inst_data[$ind]) && 
                                            !empty($inst_data[$ind]))
                                        {
                                            $before = md5($inst_data[$ind]);
                                            $inst_data[$ind] = Wpil_Link::deleteLink(false, $url, $anchor, $inst_data[$ind], false);
                                            $after = md5($inst_data[$ind]);
                                            
                                            if($before !== $after){
                                                self::track_action('link_deleted', true);
                                                break 3;
                                            }
                                        }
                                    }
                                }else{
                                    // and check to see if there's text in our selected index
                                    if(isset($data[$ind]) && is_string($data[$ind]) && !empty($data[$ind])){
                                        $before = md5($data[$ind]);
                                        $data[$ind] = Wpil_Link::deleteLink(false, $url, $anchor, $data[$ind], false);
                                        $after = md5($data[$ind]);
                                        
                                        if($before !== $after){
                                            self::track_action('link_deleted', true);
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }elseif(is_string($field_data) && !empty($field_data)){
                        $before = md5($field_data);
                        $field_data = Wpil_Link::deleteLink(false, $url, $anchor, $field_data, false);
                        $after = md5($field_data);
                        
                        if($before !== $after){
                            self::track_action('link_deleted', true);
                        }
                    }

                    if(self::action_happened('link_deleted')){
                        update_post_meta($post_id, $field_name, $field_data);
                        break;
                    }
                }
            }
        }
    }

    /**
     * Remove keyword links
     * NOTE: Currently removing all autolinks on autolink update/delete until a better form of updator can be created
     *
     * @param $keyword
     * @param $post_id
     * @param bool $left_one
     */
    public static function removeKeywordLinks($keyword, $post_id, $left_one = false)
    {
        if (self::wprm_active($post_id)) {
            // try getting the fields that are available
            $fields = Wpil_Editor_WPRecipe::get_selected_fields();

            // if we have fields
            if(!empty($fields)){
                // look over each of them
                foreach($fields as $field_name => $field_indexes){
                    // try pulling the data for them
                    $field_data = get_post_meta($post_id, $field_name, true);
                    if(empty($field_data)){
                        continue;
                    }

                    // if we're dealing with an array of indexes
                    if(is_array($field_indexes)){
                        // go over each index
                        foreach($field_indexes as $index => $val){
                            // "unkey" the index so we can search the arrayy data for it
                            $ind = str_replace('wprm_', '', $index);
                            // and over each data row
                            foreach($field_data as &$data){
                                $field_key = str_replace('wprm_', '', $field_name);
                                if(!empty($field_key) && is_array($data) && isset($data[$field_key])){
                                    foreach($data[$field_key] as &$inst_data){
                                        // and check to see if there's text in our selected index
                                        if( isset($inst_data[$ind]) && 
                                            is_string($inst_data[$ind]) && 
                                            !empty($inst_data[$ind]))
                                        {
                                            $before = md5($inst_data[$ind]);
                                            $inst_data[$ind] = self::remove_keywords($keyword, false, $inst_data[$ind]);
                                            $after = md5($inst_data[$ind]);
                                            
                                            if($before !== $after){
                                                self::track_action('autolink_deleted', true);
                                            }
                                        }
                                    }
                                }else{
                                    // and check to see if there's text in our selected index
                                    if(isset($data[$ind]) && is_string($data[$ind]) && !empty($data[$ind])){
                                        $before = md5($data[$ind]);
                                        $data[$ind] = self::remove_keywords($keyword, false, $data[$ind]);
                                        $after = md5($data[$ind]);
                                        
                                        if($before !== $after){
                                            self::track_action('autolink_deleted', true);
                                        }
                                    }
                                }
                            }
                        }
                    }elseif(is_string($field_data) && !empty($field_data)){
                        $before = md5($field_data);
                        $field_data = self::remove_keywords($keyword, false, $field_data);
                        $after = md5($field_data);
                        
                        if($before !== $after){
                            self::track_action('autolink_deleted', true);
                        }
                    }

                    if(self::action_happened('autolink_deleted')){
                        update_post_meta($post_id, $field_name, $field_data);
                        self::clear_tracked_action('autolink_deleted');
                    }
                }
            }
        }
    }

    private static function remove_keywords($keyword, $left_one, $content){
        $matches = Wpil_Keyword::findKeywordLinks($keyword, $content);
        if (!empty($matches[0])) {
            $keyword->link = addslashes($keyword->link);
            $keyword->keyword = addslashes($keyword->keyword);
        }

        if ($left_one) {
            Wpil_Keyword::removeNonFirstLinks($keyword, $content);
        } else {
            Wpil_Keyword::removeAllLinks($keyword, $content);
        }

        return $content;
    }

    /**
     * Replace URLs
     *
     * @param $post
     * @param $url
     */
    public static function replaceURLs($post, $url)
    {
        if (self::wprm_active($post->id)) {
            // try getting the fields that are available
            $fields = Wpil_Editor_WPRecipe::get_selected_fields();

            // if we have fields
            if(!empty($fields)){
                // look over each of them
                foreach($fields as $field_name => $field_indexes){
                    // try pulling the data for them
                    $field_data = get_post_meta($post->id, $field_name, true);
                    if(empty($field_data)){
                        continue;
                    }

                    // if we're dealing with an array of indexes
                    if(is_array($field_indexes)){
                        // go over each index
                        foreach($field_indexes as $index => $val){
                            // "unkey" the index so we can search the arrayy data for it
                            $ind = str_replace('wprm_', '', $index);
                            // and over each data row
                            foreach($field_data as &$data){
                                $field_key = str_replace('wprm_', '', $field_name);
                                if(!empty($field_key) && is_array($data) && isset($data[$field_key])){
                                    foreach($data[$field_key] as &$inst_data){
                                        // and check to see if there's text in our selected index
                                        if( isset($inst_data[$ind]) && 
                                            is_string($inst_data[$ind]) && 
                                            !empty($inst_data[$ind]))
                                        {
                                            $before = md5($inst_data[$ind]);
                                            Wpil_URLChanger::replaceLink($inst_data[$ind], $url, true, $post);
                                            $after = md5($inst_data[$ind]);
                                            
                                            if($before !== $after){
                                                self::track_action('url_changed', true);
                                            }
                                        }
                                    }
                                }else{
                                    // and check to see if there's text in our selected index
                                    if(isset($data[$ind]) && is_string($data[$ind]) && !empty($data[$ind])){
                                        $before = md5($data[$ind]);
                                        Wpil_URLChanger::replaceLink($data[$ind], $url, true, $post);
                                        $after = md5($data[$ind]);
                                        
                                        if($before !== $after){
                                            self::track_action('url_changed', true);
                                        }
                                    }
                                }
                            }
                        }
                    }elseif(is_string($field_data) && !empty($field_data)){
                        $before = md5($field_data);
                        Wpil_URLChanger::replaceLink($field_data, $url, true, $post);
                        $after = md5($field_data);
                        
                        if($before !== $after){
                            self::track_action('url_changed', true);
                        }
                    }

                    if(self::action_happened('url_changed')){
                        update_post_meta($post->id, $field_name, $field_data);
                        Wpil_Base::track_action('url_changed', true);
                        self::clear_tracked_action('url_changed');
                    }
                }
            }
        }
    }

    /**
     * Revert URLs
     *
     * @param $post
     * @param $url
     */
    public static function revertURLs($post, $url)
    {
        if (self::wprm_active($post->id)) {
            // try getting the fields that are available
            $fields = Wpil_Editor_WPRecipe::get_selected_fields();

            // if we have fields
            if(!empty($fields)){
                // look over each of them
                foreach($fields as $field_name => $field_indexes){
                    // try pulling the data for them
                    $field_data = get_post_meta($post->id, $field_name, true);
                    if(empty($field_data)){
                        continue;
                    }

                    // if we're dealing with an array of indexes
                    if(is_array($field_indexes)){
                        // go over each index
                        foreach($field_indexes as $index => $val){
                            // "unkey" the index so we can search the arrayy data for it
                            $ind = str_replace('wprm_', '', $index);
                            // and over each data row
                            foreach($field_data as &$data){
                                $field_key = str_replace('wprm_', '', $field_name);
                                if(!empty($field_key) && is_array($data) && isset($data[$field_key])){
                                    foreach($data[$field_key] as &$inst_data){
                                        // and check to see if there's text in our selected index
                                        if( isset($inst_data[$ind]) && 
                                            is_string($inst_data[$ind]) && 
                                            !empty($inst_data[$ind]))
                                        {
                                            $before = md5($inst_data[$ind]);
                                            Wpil_URLChanger::revertURL($inst_data[$ind], $url);
                                            $after = md5($inst_data[$ind]);

                                            if($before !== $after){
                                                self::track_action('url_reverted', true);
                                            }
                                        }
                                    }
                                }else{
                                    // and check to see if there's text in our selected index
                                    if(isset($data[$ind]) && is_string($data[$ind]) && !empty($data[$ind])){
                                        $before = md5($data[$ind]);
                                        Wpil_URLChanger::revertURL($data[$ind], $url);
                                        $after = md5($data[$ind]);

                                        if($before !== $after){
                                            self::track_action('url_reverted', true);
                                        }
                                    }
                                }
                            }
                        }
                    }elseif(is_string($field_data) && !empty($field_data)){
                        $before = md5($field_data);
                        Wpil_URLChanger::revertURL($field_data, $url);
                        $after = md5($field_data);

                        if($before !== $after){
                            self::track_action('url_reverted', true);
                        }
                    }

                    if(self::action_happened('url_reverted')){
                        update_post_meta($post->id, $field_name, $field_data);
                        self::clear_tracked_action('url_reverted');
                    }
                }
            }
        }
    }

    /**
     * Updates the urls of existing links on a link-by-link basis.
     * For use with the Ajax URL updating functionality
     *
     * @param Wpil_Model_Post $post
     * @param $old_link
     * @param $new_link
     * @param $anchor
     */
    public static function updateExistingLink($post, $old_link, $new_link, $anchor)
    {
        // exit if this is a term or there's no post data
        if(empty($post) || $post->type !== 'post'){
            return;
        }

        if (self::wprm_active($post->id)) {
            // try getting the fields that are available
            $fields = Wpil_Editor_WPRecipe::get_selected_fields();

            // if we have fields
            if(!empty($fields)){
                // look over each of them
                foreach($fields as $field_name => $field_indexes){
                    // try pulling the data for them
                    $field_data = get_post_meta($post->id, $field_name, true);
                    if(empty($field_data)){
                        continue;
                    }

                    // if we're dealing with an array of indexes
                    if(is_array($field_indexes)){
                        // go over each index
                        foreach($field_indexes as $index => $val){
                            // "unkey" the index so we can search the arrayy data for it
                            $ind = str_replace('wprm_', '', $index);
                            // and over each data row
                            foreach($field_data as &$data){
                                $field_key = str_replace('wprm_', '', $field_name);
                                if(!empty($field_key) && is_array($data) && isset($data[$field_key])){
                                    foreach($data[$field_key] as &$inst_data){
                                        // and check to see if there's text in our selected index
                                        if( isset($inst_data[$ind]) && 
                                            is_string($inst_data[$ind]) && 
                                            !empty($inst_data[$ind]))
                                        {
                                            $before = md5($inst_data[$ind]);
                                            Wpil_Link::updateLinkUrl($inst_data[$ind], $old_link, $new_link, $anchor);
                                            $after = md5($inst_data[$ind]);

                                            if($before !== $after){
                                                self::track_action('link_url_updated', true);
                                            }
                                        }
                                    }
                                }else{
                                    // and check to see if there's text in our selected index
                                    if(isset($data[$ind]) && is_string($data[$ind]) && !empty($data[$ind])){
                                        $before = md5($data[$ind]);
                                        Wpil_Link::updateLinkUrl($data[$ind], $old_link, $new_link, $anchor);
                                        $after = md5($data[$ind]);

                                        if($before !== $after){
                                            self::track_action('link_url_updated', true);
                                        }
                                    }
                                }
                            }
                        }
                    }elseif(is_string($field_data) && !empty($field_data)){
                        $before = md5($field_data);
                        Wpil_Link::updateLinkUrl($field_data, $old_link, $new_link, $anchor);
                        $after = md5($field_data);

                        if($before !== $after){
                            self::track_action('link_url_updated', true);
                        }
                    }

                    if(self::action_happened('link_url_updated')){
                        update_post_meta($post->id, $field_name, $field_data);
                        self::clear_tracked_action('link_url_updated');
                        Wpil_Base::$action_tracker['link_url_updated'] = true; // For reasons not yet clear to me, I need to reach across space and time to update the tracker directly, but in other places, the normal tracker functions work just fine.
                    }
                }
            }
        }
    }

    public static function get_insertable_fields(){
        return array(
            'wprm_notes' => 'Recipe Notes', // Title strings for simple data, array for data with subfields
            'wprm_equipment' => array('name' => __('Equipment Name', ''), 'notes' => __('Equipment Notes', '')),
            'wprm_ingredients' => array('name' => __('Ingredients Name', ''), 'notes' => __('Ingredients Notes', '')),
            'wprm_instructions' => array('name' => __('Instructions Name', ''), 'text' => __('Instructions Notes', '')) // the 'instructions' call the instructing text 'text' in the database, but we'll keep calling it notes for consistency
        );
    }

    public static function get_selected_fields($unkey_indexes = false){
        $fields = get_option('wpil_suggestion_wp_recipe_fields', array('wprm_notes' => 'Recipe Notes'));

        if($unkey_indexes){
            $fields = self::unkey_indexes($fields);
        }

        return $fields;
    }

    private static function unkey_indexes($fields){
        if(!is_array($fields)){
            return $fields;
        }

        $rekeyed = array();
        foreach($fields as $index => $data){
            $ind = str_replace('wprm_', '', $index);
            if(is_array($data)){
                $rekeyed[$ind] = self::unkey_indexes($data);
            }else{
                $rekeyed[$ind] = $data;
            }
        }

        return $rekeyed;
    }

    public static function wprm_active($post_id = false){
        $active = defined('WPRM_POST_TYPE') && in_array('wprm_recipe', Wpil_Settings::getPostTypes());

        if(empty($post_id) || empty($active)){
            return $active;
        }
        return ('wprm_recipe' === get_post_type($post_id));
    }

    /**
     * Tracks if something happened inside this instance so that we don't pull from the global scope using the main tracker.
     * That way, we can only update the fields that need updating and we can skip over the rest.
     **/
    private static function track_action($action = '', $value = false){
        if(empty($action) || !is_string($action)){
            return;
        }

        if(isset(self::$action_tracker[$action]) && !empty(self::$action_tracker[$action])){
            self::$action_tracker[$action] = $value;
        }elseif(!array_key_exists($action, self::$action_tracker)){
            self::$action_tracker[$action] = $value;
        }
    }

    private static function action_happened($action = '', $return_result = true){
        if(empty($action) || !is_string($action)){
            return false;
        }

        $logged = array_key_exists($action, self::$action_tracker);

        if(!$logged){
            return false;
        }

        return ($return_result) ? self::$action_tracker[$action]: $logged;
    }

    private static function clear_tracked_action($action = ''){
        if(empty($action) || !is_string($action)){
            return;
        }

        if(array_key_exists($action, self::$action_tracker)){
            unset(self::$action_tracker[$action]);
        }
    }

}
