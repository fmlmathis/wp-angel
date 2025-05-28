<?php

/**
 * Divi single TEMPLATE editor
 * Since Divi uses a complex templating system in it's theme builder, we need to have a specific editor class to support it.
 * The non-template Divi content is still handled by the normal processes.
 *
 * Class Wpil_Editor_Divi
 */
class Wpil_Editor_Divi
{
    public static $force_insert_link;
    public static $divi_active = null;

    public static function get_divi_template_id($post_id = 0){
        global $wpdb;

        if(!self::divi_active() || empty($post_id)){
            return false;
        }

        $metas = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE `meta_key` = '_et_use_on'");
    
        $ids = array();
        if(!empty($metas)){
            foreach($metas as $meta){
                if(false !== strpos($meta->meta_value, ':id:' . $post_id)){
                    $ids[] = $meta->post_id;
                }
            }
        }

        if(!empty($ids)){
            $posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE `post_id` = %d AND `meta_key` = '_et_body_layout_id'", max($ids)));
            if(!empty($posts)){
                $ids = array();
                foreach($posts as $post){
                    $ids[] = $post->meta_value;
                }

                if(!empty($ids)){
                    return max($ids);
                }
            }
        }

        return false;
    }

    /**
     * Gets the Divi Template content for making suggestions
     *
     * @param int $post_id The id of the post that we're trying to get information for.
     */
    public static function getContent($post_id)
    {
        global $wpdb;
        $content = '';

        if(!self::divi_active()){
            return $content;
        }

        $template_id = self::get_divi_template_id($post_id);

        if(!empty($template_id)){
            $layout = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE `ID` = %d", $template_id));
            if(!empty($layout) && !empty($layout->post_content)){
                $content = $layout->post_content;
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
    public static function addLinks($meta, $post_id = 0, $content = '')
    {
        if(!self::divi_active() || empty($meta) || empty($post_id)){
            return;
        }

        $template_id = self::get_divi_template_id($post_id);
        if(empty($template_id)){
            return;
        }

        $post = get_post($template_id);

        if(empty($post) || empty($post->post_content)){
            return;
        }

        $pulled_content = $post->post_content;

        $changed = false;
        foreach ($meta as $link) {
            self::$force_insert_link = (isset($link['keyword_data']) && !empty($link['keyword_data']->force_insert)) ? true: false;
            $before = md5($pulled_content);

            Wpil_Post::insertLink(
                $pulled_content, 
                Wpil_Word::replaceUnicodeCharacters($link['sentence']), 
                Wpil_Post::getSentenceWithAnchor($link), 
                self::$force_insert_link
            );

            $after = md5(json_encode($pulled_content));
            
            if($before !== $after){
                $changed = true;
            }
        }

        if($changed && !empty($pulled_content)){
            // finally update the post
            $posty = new Wpil_Model_Post($template_id);
            $posty->updateContent($pulled_content);
        }

        return;
    }

    /**
     * Delete link
     *
     * @param $post_id
     * @param $url
     * @param $anchor
     */
    public static function deleteLink($post_id = 0, $url = '', $anchor = '')
    {
        if(!self::divi_active() || empty($post_id) || empty($url)){
            return;
        }

        $template_id = self::get_divi_template_id($post_id);
        if(empty($template_id)){
            return;
        }

        $post = get_post($template_id);

        if(empty($post) || empty($post->post_content)){
            return;
        }

        $pulled_content = $post->post_content;

        $before = md5($pulled_content);
        $pulled_content = Wpil_Link::deleteLink(false, $url, $anchor, $pulled_content, false);
        $after = md5($pulled_content);

        if($before !== $after){
            $posty = new Wpil_Model_Post($template_id);
            $posty->updateContent($pulled_content);
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
        if(!self::divi_active() || empty($post_id) || empty($keyword)){
            return;
        }

        $template_id = self::get_divi_template_id($post_id);
        if(empty($template_id)){
            return;
        }

        $post = get_post($template_id);

        if(empty($post) || empty($post->post_content)){
            return;
        }

        $pulled_content = $post->post_content;

        $before = md5($pulled_content);

        $matches = Wpil_Keyword::findKeywordLinks($keyword, $pulled_content);
        if (!empty($matches[0])) {
            if(!$left_one){
                Wpil_Keyword::removeAllLinks($keyword, $pulled_content);
            }elseif($left_one){
                Wpil_Keyword::removeNonFirstLinks($keyword, $pulled_content);
            }
        }

        $after = md5($pulled_content);

        if($before !== $after){
            $posty = new Wpil_Model_Post($template_id);
            $posty->updateContent($pulled_content);
        }
    }

    /**
     * Replace URLs
     *
     * @param $post
     * @param $url
     */
    public static function replaceURLs($wpil_post, $url)
    {
        if(!self::divi_active() || empty($wpil_post) || empty($url)){
            return;
        }
        
        $template_id = self::get_divi_template_id($wpil_post->id);
        if(empty($template_id)){
            return;
        }

        $post = get_post($template_id);
        if(empty($post) || empty($post->post_content)){
            return;
        }

        $pulled_content = $post->post_content;

        $before = md5($pulled_content);        

        if(Wpil_URLChanger::hasUrl($pulled_content, $url)){
            Wpil_URLChanger::replaceLink($pulled_content, $url, true, $wpil_post);
        }

        $after = md5($pulled_content);

        if($before !== $after){
            $posty = new Wpil_Model_Post($template_id);
            $posty->updateContent($pulled_content);
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
        if(!self::divi_active() || empty($post) || empty($url)){
            return;
        }

        $template_id = self::get_divi_template_id($post->id);
        if(empty($template_id)){
            return;
        }

        $post = get_post($template_id);
        if(empty($post) || empty($post->post_content)){
            return;
        }

        $pulled_content = $post->post_content;

        $before = md5($pulled_content);

        preg_match('`data-wpil="url" (?:data-wpil-url-old=[\'\"]([a-zA-Z0-9+=]*?)[\'\"] )*(href|url)=[\'\"]' . preg_quote($url->new, '`') . '\/*[\'\"]`i', $pulled_content, $matches);
        if (!empty($matches)) {
            $pulled_content = preg_replace('`data-wpil="url" (?:data-wpil-url-old=[\'\"]([a-zA-Z0-9+=]*?)[\'\"] )*(href|url)=([\'\"])' . $url->new . '\/*([\'\"])`i', '$2=$3' . $url->old . '$4', $pulled_content);
        }

        $after = md5($pulled_content);

        if($before !== $after){
            $posty = new Wpil_Model_Post($template_id);
            $posty->updateContent($pulled_content);
        }
    }

    /**
     * Updates the urls of existing links on a link-by-link basis.
     * For use with the Ajax URL updating functionality
     *
     * @param Wpil_Model_Post $wpil_post
     * @param string $old_link
     * @param string $new_link
     * @param string $anchor
     */
    public static function updateExistingLink($post, $old_link, $new_link, $anchor)
    {
        // exit if this is a term or there's no post data
        if(empty($post) || !self::divi_active()){
            return;
        }

        $template_id = self::get_divi_template_id($post->id);
        if(empty($template_id)){
            return;
        }

        $post = get_post($template_id);
        if(empty($post) || empty($post->post_content)){
            return;
        }

        $pulled_content = $post->post_content;

        $before = md5($pulled_content);
        preg_match('`(href|url)=[\'\"]' . preg_quote($old_link, '`') . '\/*[\'\"]`i', $pulled_content, $matches);
        if(!empty($matches)){
            Wpil_Link::updateLinkUrl($pulled_content, $old_link, $new_link, $anchor);
        }

        $after = md5($pulled_content);

        if($before !== $after){
            $posty = new Wpil_Model_Post($template_id);
            $posty->updateContent($pulled_content);
        }
    }

    public static function divi_active(){
        if(!is_null(self::$divi_active)){
            return self::$divi_active;
        }

        self::$divi_active = (defined('ET_SHORTCODES_VERSION')) ? true: false;

        return self::$divi_active;
    }
}