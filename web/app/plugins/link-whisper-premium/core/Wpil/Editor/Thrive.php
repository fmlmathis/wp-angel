<?php

/**
 * Thrive editor
 *
 * Class Wpil_Editor_Thrive
 */
class Wpil_Editor_Thrive
{
    /**
     * Gets the Thrive content for diagnostic purposes.
     * Not intended for adding links to this content!
     *
     * @param $post_id
     */
    public static function getThriveContent($post_id)
    {
        // If thrive's not active, return string
        if(empty(get_post_meta($post_id, 'tcb_editor_enabled', true))){
            return "";
        }

        $content_key = 'tve_updated_post';
        $thrive_template = get_post_meta($post_id, 'tve_landing_page', true);

        // see if this is a thrive templated page
        if(!empty($thrive_template)){
            // get the template key
            $content_key = 'tve_updated_post_' . $thrive_template;
        }

        $thrive = get_post_meta($post_id, $content_key, true);

        return !(empty($thrive)) ? $thrive: "";
    }
    
    /**
     * Gets the Thrive content for diagnostic purposes.
     * Not intended for adding links to this content!
     * Can handle an array of post ids!
     *
     * @param $post_ids
     */
    public static function getThriveContentMultiple($post_ids)
    {
        global $wpdb;
        $posts = array();

        $post_ids = array_map(function($id){ return (int) $id; }, $post_ids);

        $ids = implode(',', $post_ids);
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} WHERE `post_id` IN ($ids) AND `meta_key` = 'tcb_editor_enabled'");

        // TODO fill out later!
        // If thrive's not active, return string
        if(empty(get_post_meta($post_id, 'tcb_editor_enabled', true))){
            return "";
        }

        $content_key = 'tve_updated_post';
        $thrive_template = get_post_meta($post_id, 'tve_landing_page', true);

        // see if this is a thrive templated page
        if(!empty($thrive_template)){
            // get the template key
            $content_key = 'tve_updated_post_' . $thrive_template;
        }

        $thrive = get_post_meta($post_id, $content_key, true);

        return !(empty($thrive)) ? $thrive: "";
    }

    /**
     * Add links
     *
     * @param $meta
     * @param $post_id
     */
    public static function addLinks($meta, $post_id, &$content)
    {
        $content_key = 'tve_updated_post';
        $before_more_key = 'tve_content_before_more';

        $thrive = get_post_meta($post_id, $content_key, true);
        $thrive_template = get_post_meta($post_id, 'tve_landing_page', true);

        // see if this is a thrive templated page
        if(!empty($thrive_template)){
            // if it is, get the template content
            $thrive = get_post_meta($post_id, 'tve_updated_post_' . $thrive_template, true);
            // and update the keys
            $content_key = 'tve_updated_post_' . $thrive_template;
            $before_more_key = 'tve_content_before_more_' . $thrive_template;
        }

        if (!empty($thrive)) {
            $thrive_before = get_post_meta($post_id, $before_more_key, true);
            foreach ($meta as $link) {
                $force_insert = (isset($link['keyword_data']) && !empty($link['keyword_data']->force_insert)) ? true: false;
                $changed_sentence = Wpil_Post::getSentenceWithAnchor($link);

                // if the source sentence is in an encoded section
                if(self::source_sentence_is_encoded($link['sentence'], $thrive)){
                    // encode any of those stylized u/2019 quotes that Thrive uses so we don't break them
                    $quote_changed = false;
                    
                    if(mb_strpos($link['sentence'], '’')){
                        $link['sentence'] = mb_ereg_replace('’', '{{wpil-quote}}', $link['sentence']);
                        $changed_sentence = mb_ereg_replace('’', '{{wpil-quote}}', $changed_sentence);
                        $quote_changed = true;
                    }

                    // html encode the sentence and sentence + anchor
                    $changed_sentence = htmlentities($changed_sentence);
                    $link['sentence'] = htmlentities($link['sentence']);

                    // decode the stylized quotes
                    if($quote_changed){
                        $link['sentence'] = mb_ereg_replace('{{wpil-quote}}', '’', $link['sentence']);
                        $changed_sentence = mb_ereg_replace('{{wpil-quote}}', '’', $changed_sentence);

                        // make really sure they're gone
                        if( false === mb_strpos($link['sentence'], 'wpil-quote') && 
                            false === mb_strpos($changed_sentence, 'wpil-quote'))
                        {
                            // if they are, say the the quotes are unchanged
                            $quote_changed = false;
                        }
                    }

                    // if for some reason the quotes couldn't be removed
                    if($quote_changed){
                        // dont attempt to insert the link so that we don't insert placeholder text in the page!
                        continue;
                    }

                }else if (strpos($thrive, $link['sentence']) === false) {
                    $link['sentence'] = addslashes($link['sentence']);
                }
                Wpil_Post::insertLink($thrive_before, $link['sentence'], $changed_sentence, $force_insert);
                Wpil_Post::insertLink($thrive, $link['sentence'], $changed_sentence, $force_insert);
            }

            update_post_meta($post_id, $content_key, $thrive);
            update_post_meta($post_id, $before_more_key, $thrive_before);
        }

        $template = get_post_meta($post_id, 'tve_landing_page', true);
        // if the post has the Thrive Template active
        if($template){
            $thrive = get_post_meta($post_id, 'tve_updated_post_' . $template, true);

            if($thrive){
                $thrive_before = get_post_meta($post_id, 'tve_content_before_more_', true);
                foreach ($meta as $link) {
                    $force_insert = (isset($link['keyword_data']) && !empty($link['keyword_data']->force_insert)) ? true: false;
                    $changed_sentence = Wpil_Post::getSentenceWithAnchor($link);
                    // if the source sentence is in an encoded section
                    if(self::source_sentence_is_encoded($link['sentence'], $thrive)){
                        // encode any of those stylized u/2019 quotes that Thrive uses so we don't break them
                        $quote_changed = false;
                        
                        if(mb_strpos($link['sentence'], '’')){
                            $link['sentence'] = mb_ereg_replace('’', '{{wpil-quote}}', $link['sentence']);
                            $changed_sentence = mb_ereg_replace('’', '{{wpil-quote}}', $changed_sentence);
                            $quote_changed = true;
                        }
    
                        // html encode the sentence and sentence + anchor
                        $changed_sentence = htmlentities($changed_sentence);
                        $link['sentence'] = htmlentities($link['sentence']);
    
                        // decode the stylized quotes
                        if($quote_changed){
                            $link['sentence'] = mb_ereg_replace('{{wpil-quote}}', '’', $link['sentence']);
                            $changed_sentence = mb_ereg_replace('{{wpil-quote}}', '’', $changed_sentence);
    
                            // make really sure they're gone
                            if( false === mb_strpos($link['sentence'], 'wpil-quote') && 
                                false === mb_strpos($changed_sentence, 'wpil-quote'))
                            {
                                // if they are, say the the quotes are unchanged
                                $quote_changed = false;
                            }
                        }
    
                        // if for some reason the quotes couldn't be removed
                        if($quote_changed){
                            // dont attempt to insert the link so that we don't insert placeholder text in the page!
                            continue;
                        }
    
                    }else if (strpos($thrive, $link['sentence']) === false) {
                        $link['sentence'] = addslashes($link['sentence']);
                    }
                    Wpil_Post::insertLink($thrive_before, $link['sentence'], $changed_sentence, $force_insert);
                    Wpil_Post::insertLink($thrive, $link['sentence'], $changed_sentence, $force_insert);
                }

                update_post_meta($post_id, 'tve_updated_post_' . $template, $thrive);
                update_post_meta($post_id, 'tve_content_before_more_', $thrive_before);
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
        $content_key = 'tve_updated_post';
        $before_more_key = 'tve_content_before_more';

        $thrive = get_post_meta($post_id, $content_key, true);
        $thrive_template = get_post_meta($post_id, 'tve_landing_page', true);

        // see if this is a thrive templated page
        if(!empty($thrive_template)){
            // if it is, get the template content
            $thrive = get_post_meta($post_id, 'tve_updated_post_' . $thrive_template, true);
            // and update the keys
            $content_key = 'tve_updated_post_' . $thrive_template;
            $before_more_key = 'tve_content_before_more_' . $thrive_template;
        }

        if (!empty($thrive)) {
            $thrive_before = get_post_meta($post_id, $before_more_key, true);

            preg_match('|<a .+'.$url.'.+>'.$anchor.'</a>|i', $thrive,  $matches);
            if (!empty($matches[0])) {
                $url = addslashes($url);
                $anchor = addslashes($anchor);
            }

            $post = new Wpil_Model_Post($post_id); // post is just for compatibility
            $thrive_before = Wpil_Link::deleteLink($post, $url, $anchor, $thrive_before, false);
            $thrive = Wpil_Link::deleteLink($post, $url, $anchor, $thrive, false);

            update_post_meta($post_id, $content_key, $thrive);
            update_post_meta($post_id, $before_more_key, $thrive_before);
        }
    }

    /**
     * Remove keyword links
     *
     * @param $keyword
     * @param $post_id
     * @param bool $left_one
     */
    public static function removeKeywordLinks($keyword, $post_id, $left_one = false)
    {
        $content_key = 'tve_updated_post';
        $before_more_key = 'tve_content_before_more';

        $thrive = get_post_meta($post_id, $content_key, true);
        $thrive_template = get_post_meta($post_id, 'tve_landing_page', true);

        // see if this is a thrive templated page
        if(!empty($thrive_template)){
            // if it is, get the template content
            $thrive = get_post_meta($post_id, 'tve_updated_post_' . $thrive_template, true);
            // and update the keys
            $content_key = 'tve_updated_post_' . $thrive_template;
            $before_more_key = 'tve_content_before_more_' . $thrive_template;
        }

        if (!empty($thrive)) {
            $thrive_before = get_post_meta($post_id, $before_more_key, true);
            $matches = Wpil_Keyword::findKeywordLinks($keyword, $thrive);
            if (!empty($matches[0])) {
                $keyword->link = addslashes($keyword->link);
                $keyword->keyword = addslashes($keyword->keyword);
            }

            if ($left_one) {
                Wpil_Keyword::removeNonFirstLinks($keyword, $thrive_before);
                Wpil_Keyword::removeNonFirstLinks($keyword, $thrive);
            } else {
                Wpil_Keyword::removeAllLinks($keyword, $thrive_before);
                Wpil_Keyword::removeAllLinks($keyword, $thrive);
            }

            update_post_meta($post_id, $content_key, $thrive);
            update_post_meta($post_id, $before_more_key, $thrive_before);
        }
    }

    /**
     * Replace URLs
     *
     * @param $post
     * @param $url
     */
    public static function replaceURLs($post, $url)
    {
        $content_key = 'tve_updated_post';
        $before_more_key = 'tve_content_before_more';

        $thrive = get_post_meta($post->id, $content_key, true);
        $thrive_template = get_post_meta($post->id, 'tve_landing_page', true);

        // see if this is a thrive templated page
        if(!empty($thrive_template)){
            // if it is, get the template content
            $thrive = get_post_meta($post->id, 'tve_updated_post_' . $thrive_template, true);
            // and update the keys
            $content_key = 'tve_updated_post_' . $thrive_template;
            $before_more_key = 'tve_content_before_more_' . $thrive_template;
        }

        if (!empty($thrive)) {
            $thrive_before = get_post_meta($post->id, $before_more_key, true);
            Wpil_URLChanger::replaceLink($thrive, $url);
            Wpil_URLChanger::replaceLink($thrive_before, $url);

            update_post_meta($post->id, $content_key, $thrive);
            update_post_meta($post->id, $before_more_key, $thrive_before);
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
        $content_key = 'tve_updated_post';
        $before_more_key = 'tve_content_before_more';

        $thrive = get_post_meta($post->id, $content_key, true);
        $thrive_template = get_post_meta($post->id, 'tve_landing_page', true);

        // see if this is a thrive templated page
        if(!empty($thrive_template)){
            // if it is, get the template content
            $thrive = get_post_meta($post->id, 'tve_updated_post_' . $thrive_template, true);
            // and update the keys
            $content_key = 'tve_updated_post_' . $thrive_template;
            $before_more_key = 'tve_content_before_more_' . $thrive_template;
        }

        if (!empty($thrive)) {
            $thrive_before = get_post_meta($post->id, $before_more_key, true);
            Wpil_URLChanger::revertURL($thrive, $url);
            Wpil_URLChanger::revertURL($thrive_before, $url);

            update_post_meta($post->id, $content_key, $thrive);
            update_post_meta($post->id, $before_more_key, $thrive_before);
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

        $content_key = 'tve_updated_post';
        $before_more_key = 'tve_content_before_more';

        $thrive = get_post_meta($post->id, $content_key, true);
        $thrive_template = get_post_meta($post->id, 'tve_landing_page', true);

        // see if this is a thrive templated page
        if(!empty($thrive_template)){
            // if it is, get the template content
            $thrive = get_post_meta($post->id, 'tve_updated_post_' . $thrive_template, true);
            // and update the keys
            $content_key = 'tve_updated_post_' . $thrive_template;
            $before_more_key = 'tve_content_before_more_' . $thrive_template;
        }

        if (!empty($thrive)) {
            $thrive_before = get_post_meta($post->id, $before_more_key, true);
            Wpil_Link::updateLinkUrl($thrive, $old_link, $new_link, $anchor);
            Wpil_Link::updateLinkUrl($thrive_before, $old_link, $new_link, $anchor);

            update_post_meta($post->id, $content_key, $thrive);
            update_post_meta($post->id, $before_more_key, $thrive_before);
        }
    }

    /**
     * Checks to see if the link to insert should be html encoded before inserting in the content.
     * Thrive encodes WP editor content before savign it in a special shortcode, and we need to account for this.
     * @param string $sentence The original sentence without the anchor
     * @param string $content The content to check
     * @return bool
     **/
    public static function source_sentence_is_encoded($sentence, $content){
        if(false !== strpos($content, '___TVE_SHORTCODE_RAW__')){
            list( $start, $end ) = array(
                '___TVE_SHORTCODE_RAW__',
                '__TVE_SHORTCODE_RAW___',
            );
            if ( ! preg_match_all( "/{$start}((<p>)?(.+?)(<\/p>)?){$end}/s", $content, $matches, PREG_OFFSET_CAPTURE ) ) {
                return false;
            }

            foreach ( $matches[1] as $i => $data ) {
                $raw_shortcode = $data[0]; // the actual matched regexp group
                $raw_shortcode = html_entity_decode( $raw_shortcode );//we keep the code encoded and now we need to decode
        
                // if the sentence is present in the decoded content
                if(false !== strpos($raw_shortcode, $sentence)){
                    // we should encode the content
                    return true;
                }
            }
        }

        return false;
    }

}
