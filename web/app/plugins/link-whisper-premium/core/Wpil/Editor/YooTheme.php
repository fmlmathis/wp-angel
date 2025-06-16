<?php

/**
 * YooTheme editor
 *
 * Class Wpil_Editor_YooTheme
 */
class Wpil_Editor_YooTheme
{
    public static $link_processed;
    public static $keyword_links_count;
    public static $link_confirmed;
    public static $document;
    public static $current_id;
    public static $remove_unprocessable = true;
    public static $force_insert_link;
    public static $yoo_active = null;
    public static $ignore_types = array(
        'button', 
        'button_item', 
        'headline', 
        'gallery',
        'gallery_item',
        'nav', 
        'nav_item',
        'overlay',
        'overlay-slider',
        'overlay-slider_item',
        'slideshow',
        'slideshow_item',
        'subnav',
        'subnav_item',
        'switcher',
        'switcher_item'
    );

    public static $pattern = '/<!--\s*?(\{(?:.*?)\})\s*?-->/';

    public static function matchContent($content)
    {
        return str_contains((string) $content, '<!--') &&
            preg_match(self::$pattern, $content, $matches)
            ? $matches[1]
            : null;
    }

    /**
     * Gets the YooTheme content for making suggestions
     *
     * @param int $post_id The id of the post that we're trying to get information for.
     */
    public static function getContent($post_id, $remove_unprocessable = true, $return_json = false)
    {
        $post = get_post($post_id);
        $content = '';

        if(empty($post) || empty($post->post_content)){
            return $content;
        }

        $pulled_content = self::matchContent($post->post_content);

        if(empty($pulled_content)){
            return $content;
        }

        $pulled_content = json_decode($pulled_content);

        if(empty($pulled_content) || !is_object($pulled_content)){
            return $content;
        }

        if($return_json){
            return $pulled_content;
        }

        self::$remove_unprocessable = $remove_unprocessable;
        self::getProcessableData($pulled_content, $content, $post_id);
        self::$remove_unprocessable = true;

        return $content;
    }

    public static function clean_json($json_string) {
        // Unescape double quotes
        $json_string = preg_replace('/(?<!\\\\)\\\\(?=")/', '', $json_string);
        // Correct escaped characters (reduce double backslashes)
        $json_string = preg_replace('/\\\\\\\\(n|t|r|b|f|\')/', '\\\\$1', $json_string);
        return $json_string;
    }

    public static function add_extra_slash($json_string) {
        // Add an extra slash to escaped formatting characters
        $json_string = preg_replace('/(?<!\\\\)\\\\(n|t|r|b|f)/', '\\\\\\\\$1', $json_string);
        return $json_string;
    }

    /**
     * Add links
     *
     * @param $meta
     * @param $post_id
     */
    public static function addLinks($meta, $post_id, $content)
    {
        if( !self::yoo_active() ||
            empty($post_id))
        {
            return;
        }

        $post = get_post($post_id);

        if(empty($post) || empty($post->post_content)){
            return $content;
        }

        $pulled_content = self::matchContent($post->post_content);
        $orig = $pulled_content;

        if(empty($pulled_content)){
            return;
        }

        $pulled_content = json_decode($pulled_content);

        if(empty($pulled_content) || !is_object($pulled_content)){
            return;
        }

        $changed = false;
        foreach ($meta as $link) {
            self::$force_insert_link = (isset($link['keyword_data']) && !empty($link['keyword_data']->force_insert)) ? true: false;
            $before = md5(json_encode($pulled_content));

            self::manageLink($pulled_content, [
                'action' => 'add',
                'sentence' => Wpil_Word::replaceUnicodeCharacters($link['sentence']),
                'replacement' => Wpil_Post::getSentenceWithAnchor($link)
            ]);

            $after = md5(json_encode($pulled_content));

            // if the link hasn't been added to the Yoo module
            if($before === $after && empty(self::$link_confirmed) && empty(self::$link_processed)){
                // remove the link from the post content
                $post->post_content = self::removeLinkFromPostContent($link, $post->post_content);
            }
            
            if($before !== $after){
                $changed = true;
            }
        }

        if($changed){
            $pulled_content = json_encode($pulled_content);
            if(!empty($pulled_content)){
                $post->post_content = preg_replace('/' . preg_quote($orig, '/') . '/', $pulled_content, $post->post_content);
                if(!empty($post->post_content)){
                    // finally update the post
                    $posty = new Wpil_Model_Post($post_id);
                    $posty->updateContent($post->post_content);
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
    public static function deleteLink(&$content, $url, $anchor)
    {
        if( !self::yoo_active() ||
            empty($content))
        {
            return;
        }

        $pulled_content = self::matchContent($content);
        $orig = $pulled_content;

        if(empty($pulled_content)){
            return;
        }

        $pulled_content = json_decode($pulled_content);

        if(empty($pulled_content) || !is_object($pulled_content)){
            return;
        }

        self::manageLink($pulled_content, [
            'action' => 'remove',
            'url' => Wpil_Word::replaceUnicodeCharacters($url),
            'anchor' => Wpil_Word::replaceUnicodeCharacters($anchor)
        ]);

        $updated = json_encode($pulled_content);

        if(!empty($updated) && $updated !== $orig){
            $updated = preg_replace('/' . preg_quote($orig, '/') . '/', $updated, $content);
            if(!empty($updated)){
                $content = $updated;
            }
        }
    }

    /**
     * Remove keyword links
     *
     * @param $keyword
     * @param $post_id
     * @param bool $left_one
     */
    public static function removeKeywordLinks(&$content, $keyword, $left_one = false)
    {
        if( !self::yoo_active() ||
            empty($content))
        {
            return;
        }

        $pulled_content = self::matchContent($content);
        $orig = $pulled_content;

        if(empty($pulled_content)){
            return;
        }

        $pulled_content = json_decode($pulled_content);

        if(empty($pulled_content) || !is_object($pulled_content)){
            return;
        }

        $keyword->link = Wpil_Word::replaceUnicodeCharacters($keyword->link);
        $keyword->keyword = Wpil_Word::replaceUnicodeCharacters($keyword->keyword);
        self::$keyword_links_count = 0;
        self::manageLink($pulled_content, [
            'action' => 'remove_keyword',
            'keyword' => $keyword,
            'left_one' => $left_one
        ]);

        $updated = json_encode($pulled_content);

        if(!empty($updated) && $updated !== $orig){
            $updated = preg_replace('/' . preg_quote($orig, '/') . '/', $updated, $content);
            if(!empty($updated)){
                $content = $updated;
            }
        }
    }

    /**
     * Replace URLs
     *
     * @param $post
     * @param $url
     */
    public static function replaceURLs(&$content, $url)
    {
        if( !self::yoo_active() ||
            empty($content))
        {
            return;
        }

        $pulled_content = self::matchContent($content);
        $orig = $pulled_content;

        if(empty($pulled_content)){
            return;
        }

        $pulled_content = json_decode($pulled_content);

        if(empty($pulled_content) || !is_object($pulled_content)){
            return;
        }

        $url->old = Wpil_Word::replaceUnicodeCharacters($url->old);
        $url->new = Wpil_Word::replaceUnicodeCharacters($url->new);
        self::manageLink($pulled_content, [
            'action' => 'replace_urls',
            'url' => $url,
        ]);

        $updated = json_encode($pulled_content);

        if(!empty($updated) && $updated !== $orig){
            $updated = preg_replace('/' . preg_quote($orig, '/') . '/', $updated, $content);
            if(!empty($updated)){
                $content = $updated;
            }
        }
    }

    /**
     * Revert URLs
     *
     * @param $post
     * @param $url
     */
    public static function revertURLs(&$content, $url)
    {
        if( !self::yoo_active() ||
            empty($content))
        {
            return;
        }

        $pulled_content = self::matchContent($content);
        $orig = $pulled_content;

        if(empty($pulled_content)){
            return;
        }

        $pulled_content = json_decode($pulled_content);

        if(empty($pulled_content) || !is_object($pulled_content)){
            return;
        }

        $url->old = Wpil_Word::replaceUnicodeCharacters($url->old);
        $url->new = Wpil_Word::replaceUnicodeCharacters($url->new);
        self::manageLink($pulled_content, [
            'action' => 'revert_urls',
            'url' => $url,
        ]);

        $updated = json_encode($pulled_content);

        if(!empty($updated) && $updated !== $orig){
            $updated = preg_replace('/' . preg_quote($orig, '/') . '/', $updated, $content);
            if(!empty($updated)){
                $content = $updated;
            }
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
    public static function updateExistingLink(&$content, $old_link, $new_link, $anchor)
    {
        // exit if this is a term or there's no post data
        if(empty($content) || !self::yoo_active()){
            return;
        }

        $pulled_content = self::matchContent($content);
        $orig = $pulled_content;

        if(empty($pulled_content)){
            return;
        }

        $pulled_content = json_decode($pulled_content);

        if(empty($pulled_content) || !is_object($pulled_content)){
            return;
        }

        self::manageLink($pulled_content, [
            'action' => 'update_existing_link',
            'old_link' => Wpil_Word::replaceUnicodeCharacters($old_link),
            'new_link' => Wpil_Word::replaceUnicodeCharacters($new_link),
            'anchor' => $anchor,
        ]);

        $updated = json_encode($pulled_content);

        if(!empty($updated) && $updated !== $orig){
            $updated = preg_replace('/' . preg_quote($orig, '/') . '/', $updated, $content);
            if(!empty($updated)){
                $content = $updated;
            }
        }
    }

    /**
     * Find all text elements
     *
     * @param $data
     * @param $params
     */
    public static function manageLink(&$data, $params)
    {
        self::$link_processed = false;
        self::$link_confirmed = false;
        self::$remove_unprocessable = true;
        self::checkItem($data, $params);
    }

    /**
     * Check certain text element
     *
     * @param $item
     * @param $params
     */
    public static function checkItem(&$item, $params)
    {
        if(isset($item->children)){
            if(is_array($item->children)){
                foreach($item->children as &$dat){
                    if( isset($dat->props) && !empty($dat->props) &&
                        isset($dat->props->content) && !empty($dat->props->content) &&
                        isset($dat->type) && !in_array($dat->type, self::$ignore_types)
                    ){
                        self::manageBlock($dat->props->content, $params);
                    }

                    if (isset($dat->children) && !empty($dat->children)) {
                        foreach ($dat->children as $e) {
                            if (!self::$link_processed) {
                                self::checkItem($e, $params);
                            }
                        }
                    }
                }
            }
        }

        if(isset($item->content) && !empty($item->content) && isset($item->type) && !in_array($item->type, self::$ignore_types)){
            self::manageBlock($item->content, $params);
        }

        if (isset($item->children) && !empty($item->children)) {
            foreach ($item->children as $element) {
                self::checkItem($element, $params);
            }
        }
    }

    /**
     * Remove links from the post content when they're not added to the YooTheme content
     **/
    public static function removeLinkFromPostContent($link, $content){
        $sentence_with_anchor = Wpil_Post::getSentenceWithAnchor($link);

        if(!empty($sentence_with_anchor) && false !== strpos($content, $sentence_with_anchor)){
            $content2 = preg_replace('`' . preg_quote($sentence_with_anchor, '`') . '`', $link['sentence'], $content, 1);
            if(!empty($content2)){
                $content = $content2;
            }
        }

        return $content;
    }

    /**
     * Route current action
     *
     * @param $block
     * @param $params
     */
    public static function manageBlock(&$block, $params)
    {
        if ($params['action'] == 'add') {
            self::addLinkToBlock($block, $params['sentence'], $params['replacement']);
        } elseif ($params['action'] == 'remove') {
            self::removeLinkFromBlock($block, $params['url'], $params['anchor']);
        } elseif ($params['action'] == 'remove_keyword') {
            self::removeKeywordFromBlock($block, $params['keyword'], $params['left_one']);
        } elseif ($params['action'] == 'replace_urls') {
            self::replaceURLInBlock($block, $params['url']);
        } elseif ($params['action'] == 'revert_urls') {
            self::revertURLInBlock($block, $params['url']);
        } elseif ($params['action'] == 'update_existing_link') {
            self::updateURLInBlock($block, $params['old_link'], $params['new_link'], $params['anchor']);
        }
    }

    /**
     * Insert link into block
     *
     * @param $block
     * @param $sentence
     * @param $replacement
     */
    public static function addLinkToBlock(&$block, $sentence, $replacement)
    {
        $block_unicode = Wpil_Word::replaceUnicodeCharacters($block);
        if (strpos($block_unicode, $sentence) !== false) {
            $block = $block_unicode;
            Wpil_Post::insertLink($block, $sentence, $replacement, self::$force_insert_link);
            $block = Wpil_Word::replaceUnicodeCharacters($block, true);
            self::$link_processed = true;
        }elseif(false !== strpos($block_unicode, Wpil_Word::replaceUnicodeCharacters($replacement)) || false !== strpos($block_unicode, 'wpil_keyword_link') || false !== strpos($block_unicode, 'data-wpil-keyword-link')){
            self::$link_confirmed = true;
        }
    }

    /**
     * Remove link from block
     *
     * @param $block
     * @param $url
     * @param $anchor
     */
    public static function removeLinkFromBlock(&$block, $url, $anchor)
    {
        // decode the url if it's base64 encoded
        if(base64_encode(base64_decode($url, true)) === $url){
            $url = base64_decode($url);
        }

        $block_unicode = Wpil_Word::replaceUnicodeCharacters($block);

        // if we have an anchor
        if(!empty($anchor)){
            // setup that regex since it's more precise
            $regex = '`<a [^><]*?' . preg_quote(Wpil_Word::replaceUnicodeCharacters($url), '`') . '[^><]*?>(?:<(?!a)[a-zA-Z]+?[^><]*?>)*?' . preg_quote(Wpil_Word::replaceUnicodeCharacters($anchor), '`') . '(?:<(?!a)[a-zA-Z/]+?[^><]*?>)*?</a>`i';
        }else{
            // if we have no anchor, look for a more general link
            $regex = '`<a [^><]*?' . preg_quote(Wpil_Word::replaceUnicodeCharacters($url), '`') . '[^><]*?>(?:<(?!a)[a-zA-Z]+?[^><]*?>)*(.*?)(?:<(?!a)[a-zA-Z/]+?[^><]*?>)*</a>`i';
        }

        preg_match($regex, $block_unicode,  $matches);

        if (!empty($matches[0])) {
            $block = $block_unicode;
            $before = md5($block);
            $block = Wpil_Link::deleteLink(false, $url, $anchor, $block, false);
            $after = md5($block);
            $block = Wpil_Word::replaceUnicodeCharacters($block, true);
            if($before !== $after){
                self::$link_processed = true;
            }
        }
    }

    /**
     * Remove keyword links
     *
     * @param $block
     * @param $keyword
     * @param $left_one
     */
    public static function removeKeywordFromBlock(&$block, $keyword, $left_one)
    {
        $block_unicode = Wpil_Word::replaceUnicodeCharacters($block);
        $matches = Wpil_Keyword::findKeywordLinks($keyword, $block_unicode);
        if (!empty($matches[0])) {
            $block = $block_unicode;
            if (!$left_one || self::$keyword_links_count) {
                Wpil_Keyword::removeAllLinks($keyword, $block);
            }
            if($left_one && self::$keyword_links_count == 0 and count($matches[0]) > 1) {
                Wpil_Keyword::removeNonFirstLinks($keyword, $block);
            }
            self::$keyword_links_count += count($matches[0]);
            $block = Wpil_Word::replaceUnicodeCharacters($block, true);
        }
    }


    /**
     * Replace URL in block
     *
     * @param $block
     * @param $url
     */
    public static function replaceURLInBlock(&$block, $url)
    {
        $block_unicode = Wpil_Word::replaceUnicodeCharacters($block);

        if (Wpil_URLChanger::hasUrl($block_unicode, $url)) {
            $block = $block_unicode;
            Wpil_URLChanger::replaceLink($block, $url);
            $block = Wpil_Word::replaceUnicodeCharacters($block, true);
        }
    }

    /**
     * Revert URL in block
     *
     * @param $block
     * @param $url
     */
    public static function revertURLInBlock(&$block, $url)
    {
        $block_unicode = Wpil_Word::replaceUnicodeCharacters($block);

        preg_match('`data\\\u2013wpil=\"url\" (href|url)=[\'\"]' . preg_quote($url->new, '`') . '\/*[\'\"]`i', $block_unicode, $matches);
        if (!empty($matches)) {
            $block = $block_unicode;
            $block = preg_replace('`data\\\u2013wpil=\"url\" (href|url)=([\'\"])' . $url->new . '\/*([\'\"])`i', '$1=$2' . $url->old . '$3', $block);
            $block = Wpil_Word::replaceUnicodeCharacters($block, true);
        }
    }

    public static function updateURLInBlock(&$block, $old_link, $new_link, $anchor){
        $block_unicode = Wpil_Word::replaceUnicodeCharacters($block);

        preg_match('`(href|url)=[\'\"]' . preg_quote($old_link, '`') . '\/*[\'\"]`i', $block_unicode, $matches);
        if (!empty($matches)) {
            $block = $block_unicode;
            Wpil_Link::updateLinkUrl($block, $old_link, $new_link, $anchor);
            $block = Wpil_Word::replaceUnicodeCharacters($block, true);
        }
    }

    /**
     * Check certain text element
     *
     * @param $item
     * @param $params
     */
    public static function getProcessableData($item, &$content, $post_id)
    {
        if(self::$remove_unprocessable && isset($item->type) && in_array($item->type, self::$ignore_types)){
            return;
        }

        if(isset($item->children) && is_array($item->children)){
            foreach($item->children as $dat){
                if( isset($dat->props) && !empty($dat->props) &&
                    isset($dat->props->content) && !empty($dat->props->content) &&
                    isset($dat->type) && (!self::$remove_unprocessable && !in_array($dat->type, self::$ignore_types))
                ){
                    $content .= ("\n" . $dat->props->content);
                }

                if (isset($dat->children) && !empty($dat->children)) {
                    foreach ($dat->children as $e) {
                        self::getProcessableData($e, $content, $post_id);
                    }
                }
            }
        }

        if(isset($item->content) && !empty($item->content)){
            $content .= "\n" . $item->content;
        }

        if (isset($item->children) && !empty($item->children)) {
            foreach ($item->children as $element) {
                self::getProcessableData($element, $content, $post_id);
            }
        }
    }

    public static function yoo_active(){
        if(!is_null(self::$yoo_active)){
            return self::$yoo_active;
        }

        // get the currently active theme
        $theme = wp_get_theme();

        if(!empty($theme) && $theme->exists() &&
            (false !== stripos($theme->name, 'YOOtheme') ||
            (!empty($theme->parent_theme) && false !== stripos($theme->parent_theme, 'YOOtheme')))
        ){
            self::$yoo_active = true;
        }else{
            self::$yoo_active = false;
        }

        return self::$yoo_active;
    }
}