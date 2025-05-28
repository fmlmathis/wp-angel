<?php

/**
 * Work with URL Changer
 */
class Wpil_URLChanger
{
    /**
     * Register hooks
     */
    public function register()
    {
        add_action('wp_ajax_wpil_url_changer_delete', [$this, 'delete']);
        add_action('wp_ajax_wpil_url_changer_reset', [$this, 'reset']);
    }

    /**
     * Show table page
     */
    public static function init()
    {
        if (!empty($_POST['old']) && !empty($_POST['new'])) { //TODO: update this from "old" and "new" to avoid collisions from anyone else that is using the same variable names...
            if(false !== strpos($_POST['old'], ',')){
                $olds = explode(',', $_POST['old']);
                foreach($olds as $old){
                    // exit if we're running close to the limit
                    if(Wpil_Base::overTimeLimit(15)){
                        break;
                    }
                    $url = self::store($old);
                    self::replaceURL($url);
                    Wpil_Telemetry::log_event('url_changer_rule_create');
                }
            }else{
                $url = self::store();
                self::replaceURL($url);
                Wpil_Telemetry::log_event('url_changer_rule_create');
            }
        }

        $user = wp_get_current_user();
        $reset = !empty(get_option('wpil_url_changer_reset'));
        $table = new Wpil_Table_URLChanger();
        $table->prepare_items();
        include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/url_changer.php';
    }

    public static function reset()
    {
        global $wpdb;

        //verify input data
        Wpil_Base::verify_nonce('wpil_url_changer');
        if (empty($_POST['count']) || (int)$_POST['count'] > 9999) {
            wp_send_json([
                'nonce' => $_POST['nonce'],
                'finish' => true
            ]);
        }

        $start = microtime(true);
        $memory_break_point = Wpil_Report::get_mem_break_point();
        $total = !empty($_POST['total']) ? (int)$_POST['total'] : 1;

        if ($_POST['count'] == 1) {
            //make matched posts array on the first call
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}wpil_url_links");
            $statuses_query = Wpil_Query::postStatuses();
            $posts = $wpdb->get_results("SELECT ID as id, 'post' as type FROM {$wpdb->posts} WHERE post_content LIKE '%data-wpil=\"url\"%' $statuses_query");
            $posts = self::getLinkedPostsFromAlternateLocations($posts);
            $taxonomies = Wpil_Settings::getTermTypes();
            $terms = array();
            if(!empty($taxonomies)){
                $taxonomies = implode("','", $taxonomies);
                $terms = $wpdb->get_results("SELECT term_id as id, 'term' as type FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ('{$taxonomies}') AND description LIKE '%data-wpil=\"url\"%'");
            }
            $posts = array_merge($posts, $terms);
            $total = count($posts);
        } else {
            //get unprocessed posts
            $posts = get_option('wpil_url_changer_reset', []);
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
            // todo: continue making the URLChanging able to handle wildcard matching. in this section, work on the base64ing of the old_url parameter in the URL
            preg_match_all('`data-wpil=\"url\" (?:data-wpil-url-old=[\'\"]([a-zA-Z0-9+=]*?)[\'\"] )*(href|url)=[\'\"]([^\'\"]*?)[\'\"][^>]*?(?:[>\]]|&gt;)(.*?)(?:</a|&lt;/a&gt;)|(?:<a|&lt;a)[^>&]*?(href|url)=[\'\"]([^\'\"]*?)[\'\"][^>&]*?data-wpil=\"url\"[^>]*?(?:[>\]]|&gt;)(.*?)(?:[\[]|</a>|&lt;/a&gt;)`i', $content, $matches);
            for ($i = 0; $i < count($matches[0]); $i++) {
                $m1 = (!empty($matches[3][$i]) && !empty($matches[4][$i]));
                $m2 = (!empty($matches[6][$i]) && !empty($matches[7][$i]));
                if ($m1 || $m2) {
                    $link = ($m1) ? $matches[3][$i]: $matches[6][$i];
                    $link2 = substr($link, -1) == '/' ? substr($link, 0, -1) : $link . '/';
                    $anchor = ($m1) ? $matches[4][$i]: $matches[7][$i];
                    $original_url = (!empty($matches[1][$i])) ? sanitize_text_field(base64_decode($matches[1][$i])): '';

                    $url_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpil_urls WHERE new = '$link' OR new = '$link2'");
                    if (!empty($url_id)) {
                        $wpdb->insert($wpdb->prefix . 'wpil_url_links', [
                            'url_id' => $url_id,
                            'post_id' => $post->id,
                            'post_type' => $post->type,
                            'anchor' => $anchor,
                            'original_url' => $original_url,
                            'relative_link' => Wpil_Link::isRelativeLink($link)
                        ]);
                    }
                }
            }

            unset($posts[$key]);

            //break process if limits were reached
            if (microtime(true) - $start > 10 || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point)) {
                update_option('wpil_url_changer_reset', $posts);
                break;
            }
        }

        if (empty($posts)) {
            update_option('wpil_url_changer_reset', []);
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
     * Create tables if they not exists
     */
    public static function prepareTable()
    {
        global $wpdb;
        $table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wpil_urls'");
        if ($table != $wpdb->prefix . 'wpil_urls') {
            $wpil_link_table_query = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpil_urls (
                                    id int(10) unsigned NOT NULL AUTO_INCREMENT,
                                    old varchar(255) NOT NULL,
                                    new varchar(255) NOT NULL,
                                    wildcard_match tinyint(1) DEFAULT 0,
                                    PRIMARY KEY  (id)
                                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

            // create DB table if it doesn't exist
            require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($wpil_link_table_query);
        }

        $table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wpil_url_links'");
        if ($table != $wpdb->prefix . 'wpil_url_links') {
            $wpil_link_table_query = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpil_url_links (
                                    id int(10) unsigned NOT NULL AUTO_INCREMENT,
                                    url_id int(10) unsigned NOT NULL,
                                    post_id bigint(20) unsigned NOT NULL,
                                    post_type varchar(10) NOT NULL,
                                    anchor varchar(255) NOT NULL,
                                    original_url text NOT NULL,
                                    relative_link tinyint(1) DEFAULT 0,
                                    PRIMARY KEY  (id)
                                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

            // create DB table if it doesn't exist
            require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($wpil_link_table_query);
        }

        Wpil_Base::fixCollation($wpdb->prefix . 'wpil_urls');
        Wpil_Base::fixCollation($wpdb->prefix . 'wpil_url_links');
    }

    /**
     * Get data for table
     *
     * @param $per_page
     * @param $page
     * @param $search
     * @param string $orderby
     * @param string $order
     * @return array
     */
    public static function getData($per_page, $page, $search,  $orderby = '', $order = '')
    {
        global $wpdb;
        self::prepareTable();
        $limit = " LIMIT " . (($page - 1) * $per_page) . ',' . $per_page;

        $orderby = (!empty($orderby)) ? ($orderby === 'new' ? 'new': 'old') : '';
        $order = (!empty($order)) ? ($order === 'desc' ? 'desc': 'asc') : '';

        $sort = " ORDER BY id DESC ";
        if ($orderby && $order) {
            $sort = " ORDER BY $orderby $order ";
        }

        $search = !empty($search) ? $wpdb->prepare(" AND (old LIKE %s OR new LIKE %s) ", Wpil_Toolbox::esc_like($search), Wpil_Toolbox::esc_like($search)) : '';
        $total = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}wpil_urls");
        $urls = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpil_urls WHERE 1 $search $sort $limit");

        //get posts with inserted links
        foreach ($urls as $key => $url) {
            $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpil_url_links WHERE url_id = " . $url->id);
            $links = [];
            foreach ($result as $r) {
                $links[] = (object)[
                    'post' => new Wpil_Model_Post($r->post_id, $r->post_type),
                    'anchor' => $r->anchor,
                    'url' => $url->new,
                ];
            }
            $urls[$key]->links = $links;
        }

        return [
            'total' => $total,
            'urls' => $urls
        ];
    }

    /**
     * Save URL to DB
     *
     * @return object
     */
    public static function store($old = null)
    {
        global $wpdb;
        self::prepareTable();
        $old_url = !empty($old) ? $old: $_POST['old'];
        $wildcard_match = (substr($old_url, -1, 1) === '*') ? 1: 0;

        $wpdb->insert($wpdb->prefix . 'wpil_urls', [
            'old' => rtrim($old_url, '*'),
            'new' => $_POST['new'],
            'wildcard_match' => $wildcard_match,
        ]);

        return self::getURL($wpdb->insert_id);
    }

    /**
     * Get URL by ID
     *
     * @param $id
     * @return object
     */
    public static function getURL($id)
    {
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wpil_urls WHERE id = " . $id);
    }

    /**
     * Delete URL
     */
    public static function delete()
    {
        if (!empty($_POST['id'])) {
            global $wpdb;
            $url = self::getURL((int)$_POST['id']);
            $links = $wpdb->get_results("SELECT post_id, post_type FROM {$wpdb->prefix}wpil_url_links WHERE url_id = {$url->id} GROUP BY post_id, post_type");
            foreach ($links as $link) {
                $post = new Wpil_Model_Post($link->post_id, $link->post_type);
                $content = $post->getCleanContent();
                if ($post->type == 'post') {
                    Wpil_Post::editors('revertURLs', [$post, $url]);
                    Wpil_Editor_Kadence::revertURLs($content, $url);
                    Wpil_Editor_YooTheme::revertURLs($content, $url);
                }
                self::revertURL($content, $url);
                $post->updateContent($content);

                self::revertACFContent($post, $url);

                // if the url has been reverted
                if(Wpil_Base::action_happened('url_reverted')){
                    $post->getFreshContent();
                    // update the links stored in the link table
                    Wpil_Report::update_post_in_link_table($post);
                    // update the meta data for the post
                    Wpil_Report::statUpdate($post, true);
                    // and update the link counts for the posts that this one links to
                    Wpil_Report::updateReportInternallyLinkedPosts($post);

                    $origin_post = Wpil_Post::getPostByLink($url->old);
                    if(!empty($origin_post)){
                        $origin_post->getFreshContent();
                        // update the links stored in the link table
                        Wpil_Report::update_post_in_link_table($origin_post);
                        // update the meta data for the post
                        Wpil_Report::statUpdate($origin_post, true);
                        // and update the link counts for the posts that this one links to
                        Wpil_Report::updateReportInternallyLinkedPosts($origin_post);
                    }

                    $target_post = Wpil_Post::getPostByLink($url->new);
                    if(!empty($target_post)){
                        $target_post->getFreshContent();
                        // update the links stored in the link table
                        Wpil_Report::update_post_in_link_table($target_post);
                        // update the meta data for the post
                        Wpil_Report::statUpdate($target_post, true);
                        // and update the link counts for the posts that this one links to
                        Wpil_Report::updateReportInternallyLinkedPosts($target_post);
                    }
                }
            }
            $wpdb->delete($wpdb->prefix . 'wpil_urls', ['id' => $url->id]);
            $wpdb->delete($wpdb->prefix . 'wpil_url_links', ['url_id' => $url->id]);
        }
    }

    /**
     * Revert link URL
     *
     * @param $content
     * @param $url
     * @param $anchor
     */
    public static function revertURL(&$content, $url)
    {
        $before = md5($content);
        if($url->wildcard_match){
            if(preg_match_all('`data-wpil=\"url\" (?:data-wpil-url-old=[\'\"]([a-zA-Z0-9+=]*?)[\'\"] )*(href|url)=([\'\"])' . preg_quote($url->new, '`') . '\/*([\'\"])`i', $content, $matches)){
                foreach($matches[0] as $key => $match){
                    if(!empty($matches[1][$key])){
                        $encoded = $matches[1][$key];
                        $decoded = sanitize_text_field(base64_decode($encoded));
                        // if there is indeed an old url in the attribute, and the url contains the current old url, replace it with the new one
                        if(!empty($decoded) && false !== strpos($decoded, $url->old)){
                            if(false !== strpos($content, $url->new)){
                                $content = preg_replace('`data-wpil=\"url\" (?:data-wpil-url-old=[\'\"]' . preg_quote($encoded, '`') . '[\'\"] )*(href|url)=([\'\"])' . preg_quote($url->new, '`') . '\/*([\'\"])`i', '$1=$2' . $decoded . '$3', $content, 1);
                                $content = preg_replace('`(<a[^>]*?)(href|url)=([\'\"])' . preg_quote($url->new, '`') . '([\'\"])([^>]*?)data-wpil=\"url\"`i', '$1$2=$3' . $decoded . '$4$5', $content, 1);
                                $content = preg_replace('`(&lt;a[^&]*?)(href|url)=([\'\"])' . preg_quote($url->new, '`') . '([\'\"])([^&]*?)data-wpil=\"url\"`i', '$1$2=$3' . $decoded . '$4$5', $content, 1);
                            }
                
                            if(false !== strpos($content, htmlentities($url->new))){
                                $new_encoded = htmlentities($url->new);
                                $old_encoded = htmlentities($decoded);
                                $content = preg_replace('`data-wpil=\"url\" (?:data-wpil-url-old=[\'\"]' . preg_quote($encoded, '`') . '[\'\"] )*(href|url)=([\'\"])' . preg_quote($new_encoded, '`') . '\/*([\'\"])`i', '$1=$2' . $old_encoded . '$3', $content, 1);
                                $content = preg_replace('`(<a[^>]*?)(href|url)=([\'\"])' . preg_quote($new_encoded, '`') . '([\'\"])([^>]*?)data-wpil=\"url\"`i', '$1$2=$3' . $old_encoded . '$4$5', $content, 1);
                                $content = preg_replace('`(&lt;a[^&]*?)(href|url)=([\'\"])' . preg_quote($new_encoded, '`') . '([\'\"])([^&]*?)data-wpil=\"url\"`i', '$1$2=$3' . $old_encoded . '$4$5', $content, 1);
                            }
                
                            $url2 = (object)array('old' => $decoded, 'new' => $url->new);
                            self::prepareLinks($url2);

                            if(false !== strpos($content, $url2->new)){
                                $content = preg_replace('`data-wpil=\"url\" (?:data-wpil-url-old=[\'\"]' . preg_quote($encoded, '`') . '[\'\"] )*(href|url)=([\'\"])' . preg_quote($url2->new, '`') . '\/*([\'\"])`i', '$1=$2' . $url2->old . '$3', $content, 1);
                                $content = preg_replace('`(<a[^>]*?)(href|url)=([\'\"])' . preg_quote($url2->new, '`') . '([\'\"])([^>]*?)data-wpil=\"url\"`i', '$1$2=$3' . $url2->old . '$4$5', $content, 1);
                                $content = preg_replace('`(&lt;a[^&]*?)(href|url)=([\'\"])' . preg_quote($url2->new, '`') . '([\'\"])([^&]*?)data-wpil=\"url\"`i', '$1$2=$3' . $url2->old . '$4$5', $content, 1);
                            }
                
                            if(false !== strpos($content, htmlentities($url2->new))){
                                $new_encoded = htmlentities($url2->new);
                                $old_encoded = htmlentities($url2->old);
                                $content = preg_replace('`data-wpil=\"url\" (?:data-wpil-url-old=[\'\"]' . preg_quote($encoded, '`') . '[\'\"] )*(href|url)=([\'\"])' . preg_quote($new_encoded, '`') . '\/*([\'\"])`i', '$1=$2' . $old_encoded . '$3', $content, 1);
                                $content = preg_replace('`(<a[^>]*?)(href|url)=([\'\"])' . preg_quote($new_encoded, '`') . '([\'\"])([^>]*?)data-wpil=\"url\"`i', '$1$2=$3' . $old_encoded . '$4$5', $content, 1);
                                $content = preg_replace('`(&lt;a[^&]*?)(href|url)=([\'\"])' . preg_quote($new_encoded, '`') . '([\'\"])([^&]*?)data-wpil=\"url\"`i', '$1$2=$3' . $old_encoded . '$4$5', $content, 1);
                            }
                        }
                    }
                }
            }
        }else{
            if(false !== strpos($content, $url->new)){
                $content = preg_replace('`data-wpil=\"url\" (?:data-wpil-url-old=[\'\"](?:[a-zA-Z0-9+=]*?)[\'\"] )*(href|url)=([\'\"])' . preg_quote($url->new, '`') . '\/*([\'\"])`i', '$1=$2' . $url->old . '$3', $content);
                $content = preg_replace('`(<a[^>]*?)(href|url)=([\'\"])' . preg_quote($url->new, '`') . '([\'\"])([^>]*?)data-wpil=\"url\"`i', '$1$2=$3' . $url->old . '$4$5', $content);
                $content = preg_replace('`(&lt;a[^&]*?)(href|url)=([\'\"])' . preg_quote($url->new, '`') . '([\'\"])([^&]*?)data-wpil=\"url\"`i', '$1$2=$3' . $url->old . '$4$5', $content);
            }

            if(false !== strpos($content, htmlentities($url->new))){
                $new_encoded = htmlentities($url->new);
                $old_encoded = htmlentities($url->old);
                $content = preg_replace('`data-wpil=\"url\" (href|url)=([\'\"])' . preg_quote($new_encoded, '`') . '\/*([\'\"])`i', '$1=$2' . $old_encoded . '$3', $content);
                $content = preg_replace('`(<a[^>]*?)(href|url)=([\'\"])' . preg_quote($new_encoded, '`') . '([\'\"])([^>]*?)data-wpil=\"url\"`i', '$1$2=$3' . $old_encoded . '$4$5', $content);
                $content = preg_replace('`(&lt;a[^&]*?)(href|url)=([\'\"])' . preg_quote($new_encoded, '`') . '([\'\"])([^&]*?)data-wpil=\"url\"`i', '$1$2=$3' . $old_encoded . '$4$5', $content);
            }

            self::prepareLinks($url);

            if(false !== strpos($content, $url->new)){
                $content = preg_replace('`data-wpil=\"url\" (href|url)=([\'\"])' . preg_quote($url->new, '`') . '\/*([\'\"])`i', '$1=$2' . $url->old . '$3', $content);
                $content = preg_replace('`(<a[^>]*?)(href|url)=([\'\"])' . preg_quote($url->new, '`') . '([\'\"])([^>]*?)data-wpil=\"url\"`i', '$1$2=$3' . $url->old . '$4$5', $content);
                $content = preg_replace('`(&lt;a[^&]*?)(href|url)=([\'\"])' . preg_quote($url->new, '`') . '([\'\"])([^&]*?)data-wpil=\"url\"`i', '$1$2=$3' . $url->old . '$4$5', $content);
            }

            if(false !== strpos($content, htmlentities($url->new))){
                $new_encoded = htmlentities($url->new);
                $old_encoded = htmlentities($url->old);
                $content = preg_replace('`data-wpil=\"url\" (href|url)=([\'\"])' . preg_quote($new_encoded, '`') . '\/*([\'\"])`i', '$1=$2' . $old_encoded . '$3', $content);
                $content = preg_replace('`(<a[^>]*?)(href|url)=([\'\"])' . preg_quote($new_encoded, '`') . '([\'\"])([^>]*?)data-wpil=\"url\"`i', '$1$2=$3' . $old_encoded . '$4$5', $content);
                $content = preg_replace('`(&lt;a[^&]*?)(href|url)=([\'\"])' . preg_quote($new_encoded, '`') . '([\'\"])([^&]*?)data-wpil=\"url\"`i', '$1$2=$3' . $old_encoded . '$4$5', $content);
            }
        }

        // if the content has been changed
        if($before !== md5($content)){
            // log the revert action
            Wpil_Base::track_action('url_reverted', true);
        }
    }

    /**
     * Revert link URL
     *
     * @param $content
     * @param $url
     * @param $anchor
     */
    public static function revertACFContent($post, $url){

        // exit if ACF isn't active or supposed to be processed
        if(!class_exists('ACF') || get_option('wpil_disable_acf', false)){
            return false;
        }

        // first, pull the displayable post content and check it for links
        $field_content = $post->getAdvancedCustomFields();

        // if there's no data, exit
        if(empty($field_content)){
            return false;
        }

        $changed = false;

        // check to see if a changed link is present in the ACF content
        if (false !== strpos($field_content, 'data-wpil="url"') || false !== strpos($field_content, 'data-wpil=\"url\"')) {
            // if it is, go over all the acf fields and replace any instances of the new url
            if($post->type === 'post'){
                foreach (Wpil_Post::getAdvancedCustomFieldsList($post->id) as $field) {
                    if ($c = get_post_meta($post->id, $field, true)) {
                        if(!is_string($c) || empty($c)){
                            continue;
                        }
    
                        $before = md5($c);
                        self::revertURL($c, $url);
                        $after = md5($c);
                        if($before !== $after){
                            update_post_meta($post->id, $field, $c);
                            $changed = true;
                        }
                    }
                }
            }else{
                foreach (Wpil_Term::getAdvancedCustomFieldsList($post->id) as $field) {
                    if ($c = get_term_meta($post->id, $field, true)) {
                        if(!is_string($c) || empty($c)){
                            continue;
                        }
    
                        $before = md5($c);
                        self::revertURL($c, $url);
                        $after = md5($c);
                        if($before !== $after){
                            update_term_meta($post->id, $field, $c);
                            $changed = true;
                        }
                    }
                }
            }
        }

        // if we've updated the content
        if($changed){
            // clear the cache so that pulling data in the future will pull the updated content
            $post->clearMetaCache();
            // track the change action
            Wpil_Base::track_action('url_reverted', true);
        }
    }

    /**
     * Replace URL for all posts
     *
     * @param $url
     */
    public static function replaceURL($url)
    {
        global $wpdb;

        $posts_relative = '';
        $meta_relative = '';
        if(Wpil_Link::isRelativeLink($url->old)){
            $unprepared_url = unserialize(serialize($url));
            $posts_relative =  "OR post_content LIKE '%href=\\\"{$wpdb->esc_like($url->old)}\\\"%' OR post_content LIKE '%href=\\\"{$wpdb->esc_like(htmlentities($url->old))}\\\"%'";
            $meta_relative = "OR m.meta_value LIKE '%href=\\\"{$wpdb->esc_like($url->old)}\\\"%' OR m.meta_value LIKE '%href=\\\"{$wpdb->esc_like(htmlentities($url->old))}\\\"%'";
        }

        $ignore_posts = Wpil_Settings::getIgnoreKeywordsPosts();
        update_option('wpil_post_procession', time());
        Wpil_Base::update_option_cache('wpil_post_procession', time());
        //get matched posts and categories
        $posts = [];
        self::prepareLinks($url);
        $post_type_query = Wpil_Query::postTypes();
        $statuses_query = Wpil_Query::postStatuses();
        $results = $wpdb->get_results("SELECT ID FROM {$wpdb->posts} WHERE 1=1 {$post_type_query} {$statuses_query} AND (post_content LIKE '%{$wpdb->esc_like($url->old)}%' OR post_content LIKE '%{$wpdb->esc_like(htmlentities($url->old))}%' $posts_relative)");
        $results = self::getPostsFromAlternateLocations($results, $url, $meta_relative);
        foreach ($results as $post) {
            $posts[] = new Wpil_Model_Post($post->ID);
        }

        $taxonomy_query = "";
        $taxonomies = implode("','", Wpil_Settings::getTermTypes());
        if (!empty($taxonomies)) {
            $taxonomy_query = " taxonomy IN ('{$taxonomies}') AND ";
        }

        $results = $wpdb->get_results("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE $taxonomy_query (`description` LIKE '%{$wpdb->esc_like($url->old)}%' OR `description` LIKE '%{$wpdb->esc_like(htmlentities($url->old))}%')");
        foreach ($results as $category) {
            $posts[] = new Wpil_Model_Post($category->term_id, 'term');
        }

        //proceed posts
        foreach ($posts as $post) {
            if (!in_array($post->type . '_' . $post->id, $ignore_posts)) {
                if(!empty($posts_relative)){
                    self::checkLink($unprepared_url, $post);
                }

                self::checkLink($url, $post);

                // if the post is published
                // trigger the post update so the save_post hook if fired and so everything hooked to it can handle the update routine
                $status = $post->getStatus();
                if( !empty($status) && 
                    'publish' === $status && 
                    !Wpil_Settings::disable_followup_post_updating() && 
                    Wpil_Base::action_happened('url_changed'))
                {

                    wp_cache_delete($post->id, 'posts');
                    $post_arr = array('ID' => $post->id);

                    // if we're not updating the post's modified date
                    if( !Wpil_Settings::updatePostModifiedDate() && 
                        !Wpil_Base::action_happened('doing_post_update'))
                    {
                        $wp_post = get_post($post->id);
                        // set the current modified date for it
                        $post_arr['post_modified']      = $wp_post->post_modified;
                        $post_arr['post_modified_gmt']  = $wp_post->post_modified_gmt;
                        // and set a filter to specifically prevent date updating on the insert pass
                        add_filter('wp_insert_post_data', array('Wpil_Post', 'prevent_date_modifying'), 10, 3);
                    }

                    wp_update_post($post_arr);

                    // and remove the date keeper
                    remove_filter('wp_insert_post_data', array('Wpil_Post', 'prevent_date_modifying'));

                    // make sure that the post stats are updated
                    // update the links stored in the link table
                    Wpil_Report::update_post_in_link_table($post);
                    // update the meta data for the post
                    Wpil_Report::statUpdate($post, true);
                    // and update the link counts for the posts that this one links to
                    Wpil_Report::updateReportInternallyLinkedPosts($post);
                }
            }
        }

        // if the url has been successfully changed
        if(Wpil_Base::action_happened('url_changed')){
            // update the link stats for the old & new posts if they are internal
            $origin_post = Wpil_Post::getPostByLink($url->old);
            if(!empty($origin_post)){
                // update the links stored in the link table
                Wpil_Report::update_post_in_link_table($origin_post);
                // update the meta data for the post
                Wpil_Report::statUpdate($origin_post, true);
                // and update the link counts for the posts that this one links to
                Wpil_Report::updateReportInternallyLinkedPosts($origin_post);
            }

            $target_post = Wpil_Post::getPostByLink($url->new);
            if(!empty($target_post)){
                // update the links stored in the link table
                Wpil_Report::update_post_in_link_table($target_post);
                // update the meta data for the post
                Wpil_Report::statUpdate($target_post, true);
                // and update the link counts for the posts that this one links to
                Wpil_Report::updateReportInternallyLinkedPosts($target_post);
            }
        }

        update_option('wpil_post_procession', 0);
        Wpil_Base::update_option_cache('wpil_post_procession', 0);
    }

    /**
     * Get all URLs
     *
     * @return array
     */
    public static function getURLs()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpil_urls ORDER BY id ASC");
    }

    /**
     * Checks to see if there are active rules
     */
    public static function hasURLs()
    {
        global $wpdb;
        $table = $wpdb->prefix . "wpil_urls";
        return $table === $wpdb->get_var("SHOW TABLES LIKE '{$table}'") && !empty($wpdb->get_results("SELECT id FROM {$table} LIMIT 1"));
    }

    /**
     * Replace URLs for certain post
     *
     * @param $post
     */
    public static function replacePostURLs($post)
    {
        if (in_array($post->type . '_' . $post->id, Wpil_Settings::getIgnoreKeywordsPosts())) {
            return;
        }

        self::prepareTable();
        $content = $post->getCleanContent();
        foreach (self::getURLs() as $url) {
            if(Wpil_Link::isRelativeLink($url->old) && strpos($content, $url->old)){
                self::checkLink($url, $post);
            }elseif(Wpil_Link::isRelativeLink($url->old) && strpos($content, htmlentities($url->old))){
                // if the URL encoded version of the link is in the content, encode the URL and replace it
                $encoded_url = $url;
                $encoded_url->old = htmlentities($url->old);
                $encoded_url->new = htmlentities($url->new);
                self::checkLink($encoded_url, $post);
            }

            self::prepareLinks($url);
            if (strpos($content, $url->old)) {
                self::checkLink($url, $post);
            }elseif(strpos($content, htmlentities($url->old))){
                $encoded_url = $url;
                $encoded_url->old = htmlentities($url->old);
                $encoded_url->new = htmlentities($url->new);
                self::checkLink($encoded_url, $post);
            }
        }

        // if the post is published and is one of the ones that we process
        // trigger the post update so the save_post hook is fired and everything hooked to it can handle the update routine
        $status = $post->getStatus();
        if( !empty($status) && 
            'publish' === $status && 
            Wpil_Base::action_happened('url_changed') &&
            in_array($post->getRealType(), Wpil_Settings::getAllTypes(), true) && 
            !Wpil_Settings::disable_followup_post_updating())
        {

            update_option('wpil_post_procession', time());
            Wpil_Base::update_option_cache('wpil_post_procession', time());
            wp_cache_delete($post->id, 'posts');
            $post_arr = array('ID' => $post->id);

            // if we're not updating the post's modified date
            if( !Wpil_Settings::updatePostModifiedDate() &&
                !Wpil_Base::action_happened('doing_post_update'))
            {
                $wp_post = get_post($post->id);
                // set the current modified date for it
                $post_arr['post_modified']      = $wp_post->post_modified;
                $post_arr['post_modified_gmt']  = $wp_post->post_modified_gmt;
                // and set a filter to specifically prevent date updating on the insert pass
                add_filter('wp_insert_post_data', array('Wpil_Post', 'prevent_date_modifying'), 10, 3);
            }

            wp_update_post($post_arr);

            // and remove the date keeper
            remove_filter('wp_insert_post_data', array('Wpil_Post', 'prevent_date_modifying')); 

            update_option('wpil_post_procession', 0);
            Wpil_Base::update_option_cache('wpil_post_procession', 0);
        }

        self::checkLinksCount($post);
    }

    /**
     * Check if content has certain URL
     *
     * @param $content
     * @param $url
     * @param $post
     */
    public static function checkLink($url, $post)
    {
        $content = $post->getCleanContent();

        if (self::hasUrl($content, $url)) {
            self::replaceLink($content, $url, true, $post);

            if ($post->type == 'post') {
                Wpil_Post::editors('replaceURLs', [$post, $url]);
                Wpil_Editor_Kadence::replaceURLs($content, $url);
                Wpil_Editor_YooTheme::replaceURLs($content, $url);
            }

            $post->updateContent($content);
        } elseif (self::hasUrl(Wpil_Editor_Themify::getContent($post->id), $url)) {
            Wpil_Editor_Themify::replaceURLs($post, $url);
        } elseif (self::hasUrl(Wpil_Editor_Oxygen::getContent($post->id), $url)) {
            Wpil_Editor_Oxygen::replaceURLs($post, $url);
        } elseif (self::hasUrl(Wpil_Editor_Muffin::getContent($post->id), $url)) {
            Wpil_Editor_Muffin::replaceURLs($post, $url);
        } elseif (defined('GDLR_CORE_LOCAL') && self::hasUrl(Wpil_Editor_Goodlayers::getContent($post->id), $url)) {
            Wpil_Editor_Goodlayers::replaceURLs($post, $url);
        } elseif(defined('WPRM_POST_TYPE') && in_array('wprm_recipe', Wpil_Settings::getPostTypes()) && 'post' === $post->type && 'wprm_recipe' === get_post_type($post->id)){
            Wpil_Editor_WPRecipe::replaceURLs($post, $url);
        } elseif(defined('ET_SHORTCODES_VERSION') && self::hasUrl(Wpil_Editor_Divi::getContent($post->id), $url)){
            Wpil_Editor_Divi::replaceURLs($post, $url);
        }

        // check the ACF fields
        self::checkACFContent($url, $post);
    }

    /**
     * Check if content has URL
     *
     * @param $content
     * @param $url
     * @return bool
     */
    public static function hasUrl($content, $url)
    {
        // if the user is doing wildcard matching
        if($url->wildcard_match){
            preg_match('`(href|url)=[\'\"]((?:' . preg_quote($url->old, '`') . '|' . preg_quote(htmlentities($url->old), '`') . ')[^\'\"]*?)[\'\"].*?([>\]]|&gt;)(.*?)([<\[]|&lt;)`i', $content, $matches);
        }else{
            preg_match('`(href|url)=[\'\"](' . preg_quote($url->old, '`') . '|' . preg_quote(htmlentities($url->old), '`') . ')\/*[\'\"].*?([>\]]|&gt;)(.*?)([<\[]|&lt;)`i', $content, $matches);
        }

        return !empty($matches);
    }

    /**
     * Replace certain URL
     *
     * @param $content
     * @param $url
     * @param bool $db_insert
     * @param null|object $post
     */
    public static function replaceLink(&$content, $url, $db_insert = false, $post = null)
    {
        // search with both the url as inserted in the DB
        $urls[] = $url;
        // and the htmlentity encoded version
        $urls[] = self::encode_url($url);

        foreach($urls as $url){
            // if the user is doing wildcard matching
            if($url->wildcard_match){
                // search for the urls with a broad matching basis
                preg_match_all('`(href|url)=[\'\"](' . preg_quote($url->old, '`') . '[^\'\"]*?)[\'\"].*?(?:[>\]]|&gt;)(.*?)(?:[<\[]|&lt;)`i', $content, $matches);
            }else{
                preg_match_all('`(href|url)=[\'\"](' . preg_quote($url->old, '`') . '\/*)[\'\"].*?(?:[>\]]|&gt;)(.*?)(?:[<\[]|&lt;)`i', $content, $matches);
            }

            foreach ($matches[3] as $key => $anchor) {
                // check if the current link is an autolink
                if(preg_match('/(?:<|&lt;)a [^><]*?' . preg_quote($matches[0][$key], '/') . '/', $content, $full_link)){
                    // if it is
                    if(isset($full_link[0]) && !empty($full_link[0]) && false !== strpos($full_link[0], 'wpil_keyword_link')){
                        // skip over the link
                        continue;
                    }
                }

                // TODO: MAKE SURE ALL EDITORS USE THIS METHOD
                $text = 'data-wpil="url" ';

                $content_before = md5($content);
                $old_url = ($url->wildcard_match) ? $matches[2][$key]: $url->old;
                $text .= ($url->wildcard_match) ? 'data-wpil-url-old="' . base64_encode($old_url) . '" ': ''; // if we're doing wildcard matching, include an encoded version of the old url in an attribute
                $link = str_replace(['href=', 'url=', $old_url, '///"', '//"'], [$text . 'href=', $text . 'url=', $url->new, '/"', '/"'], $matches[0][$key]); // tag the link as changed, replace the link, and normalize the trailing slashes
                $content = str_replace($matches[0][$key], $link, $content);

                // make sure that the URL Change identifyer attribute doesn't multiply all over the place
                while(false !== strpos($content, 'data-wpil="url" data-wpil="url" ')){
                    $content = str_replace('data-wpil="url" data-wpil="url" ', 'data-wpil="url" ', $content); 
                }

                $content_after = md5($content);

                if($content_before !== $content_after){
                    Wpil_Base::track_action('url_changed', true);

                    if ($db_insert) { // if we're logging the change and the URL has been changed
                        global $wpdb;
                        $si = $wpdb->insert($wpdb->prefix . 'wpil_url_links', [
                            'url_id' => $url->id,
                            'post_id' => $post->id,
                            'post_type' => $post->type,
                            'anchor' => $anchor,
                            'original_url' => $old_url,
                            'relative_link' => Wpil_Link::isRelativeLink($url->old) // todo add setting
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Checks the available ACF content to see if there's any URLs that need updating
     *
     * @param $url
     * @param $post
     */
    public static function checkACFContent($url, $post){

        // exit if ACF isn't active or supposed to be processed
        if(!class_exists('ACF') || get_option('wpil_disable_acf', false)){
            return false;
        }

        // first, pull the displayable post content and check it for links
        $field_content = $post->getAdvancedCustomFields();

        // if there's no data, exit
        if(empty($field_content)){
            return false;
        }

        $changed = false;

        // check to see if the link is present in the ACF content
        if (self::hasUrl($field_content, $url)) {
            // if it is, go over all the acf fields and replace the old urls
            if($post->type === 'post'){
                foreach (Wpil_Post::getAdvancedCustomFieldsList($post->id) as $field) {
                    if ($c = get_post_meta($post->id, $field, true)) {
                        if(!is_string($c) || empty($c) || !self::hasUrl($c, $url)){
                            continue;
                        }
    
                        $before = md5($c);
                        self::replaceLink($c, $url, true, $post);
                        $after = md5($c);
                        if($before !== $after){
                            update_post_meta($post->id, $field, $c);
                            $changed = true;
                        }
                    }
                }
            }else{
                foreach (Wpil_Term::getAdvancedCustomFieldsList($post->id) as $field) {
                    if ($c = get_term_meta($post->id, $field, true)) {
                        if(!is_string($c) || empty($c) || !self::hasUrl($c, $url)){
                            continue;
                        }
    
                        $before = md5($c);
                        self::replaceLink($c, $url, true, $post);
                        $after = md5($c);
                        if($before !== $after){
                            update_term_meta($post->id, $field, $c);
                            $changed = true;
                        }
                    }
                }
            }
        }

        // if we've updated the content
        if($changed){
            // clear the cache so that pulling data in the future will pull the updated content
            $post->clearMetaCache();
        }
    }

    /**
     * Remove ghost DB link records
     *
     * @param $post
     */
    public static function checkLinksCount($post)
    {
        global $wpdb;

        $links = $wpdb->get_results("SELECT url_id, anchor, count(*) as cnt FROM {$wpdb->prefix}wpil_url_links WHERE post_id = {$post->id} AND post_type = '{$post->type}' GROUP BY anchor");
        foreach ($links as $link) {
            $url = self::getURL($link->url_id);
            $unprepared_url = unserialize(serialize($url));
            self::prepareLinks($url);
            if(Wpil_Link::isRelativeLink($unprepared_url->old)){
                $regex = '`(href|url)=[\'\"](' . preg_quote($url->new, '|') . '|' . preg_quote($unprepared_url->new, '|') . '|' . preg_quote(htmlentities($url->new), '|') . ')\/*[\'\"].*?[>\]]' . preg_quote($link->anchor, '|') . '[<\[]`i';
            }else{
                $regex = '`(href|url)=[\'\"](' . preg_quote($url->new, '|') . '|' . preg_quote(htmlentities($url->new), '|') . ')\/*[\'\"].*?[>\]]' . preg_quote($link->anchor, '|') . '[<\[]`i';
            }

            preg_match_all($regex, $post->getCleanContent() . $post->getAdvancedCustomFields(), $matches);
            if (count($matches[0]) < $link->cnt) {
                $link_ids = [];
                $result = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}wpil_url_links WHERE post_id = {$post->id} AND post_type = '{$post->type}' AND url_id = {$url->id} ORDER BY id");
                foreach ($result as $r) {
                    $link_ids[] = $r->id;
                }
                $link_ids = array_slice($link_ids, count($matches[0]));
                if(!empty($link_ids)){
                    $wpdb->query("DELETE FROM {$wpdb->prefix}wpil_url_links WHERE id IN (" . implode(', ', $link_ids) . ")");
                }
            }
        }
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
     * Transform link to the common view
     *
     * @param $link
     * @return string
     */
    public static function prepareLink(&$link)
    {
        if (strpos($link, 'http') !== 0) {
            $link = site_url($link);
        }
        if (substr($link, -1) == '/') {
            $link = substr($link, 0, -1);
        }

        return $link;
    }

    /**
     * Prepare both links and check if they are not the same
     *
     * @param $url
     */
    public static function prepareLinks(&$url) {
        $insert_relative = get_option('wpil_insert_links_as_relative', false);
        $old = $url->old;
        $new = $url->new;

        self::prepareLink($old);

        // if the link isn't relative or the user hasn't chosen to only insert relative links
        if(!Wpil_Link::isRelativeLink($new) || empty($insert_relative)){
            // prepare the new link
            self::prepareLink($new);
        }else{
            // make sure there's only one slash
            $new = ltrim($new, '/');
            $new = '/' . $new;
        }

        if ($old !== $new) {
            $url->old = $old;
            $url->new = $new;
        }
    }

    public static function getLinkedPostsFromAlternateLocations($posts){
        global $wpdb;

        $found_posts = false;
        $active_post_types = Wpil_Settings::getPostTypes();

        // if WP Recipes is active and the user wants to add links to the recipe notes
        if(defined('WPRM_POST_TYPE') && in_array('wprm_recipe', $active_post_types)){
            $keys = Wpil_Editor_WPRecipe::get_selected_fields();
            if(!empty($keys)){
                $keys = "'" . implode("','", array_keys($keys)) . "'";
                $results = $wpdb->get_results("SELECT DISTINCT m.post_id AS id, 'post' AS type, 1 AS alt FROM {$wpdb->postmeta} m WHERE `meta_key` IN ({$keys}) AND meta_value LIKE '%data-wpil=\"url\"%'");
                if(!empty($results)){
                    $found_posts = true;
                    $posts = array_merge($posts, $results);
                }
            }
        }

        // get the active builder meta keys
        $builder_meta = Wpil_Post::get_builder_meta_keys();

        // if we have builders to search for
        if(!empty($builder_meta)){
            $builder_meta = "('" . implode("','", $builder_meta) . "')";
            $post_types_p = Wpil_Query::postTypes('p');
            $statuses_query_p = Wpil_Query::postStatuses('p');
            $results = $wpdb->get_results("SELECT DISTINCT m.post_id AS id, 'post' AS type, 1 AS alt FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE m.meta_key IN {$builder_meta} {$post_types_p} {$statuses_query_p} AND m.meta_value LIKE '%data-wpil=\"url\"%'");
            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }

        // if WooCommerce is active
        if(defined('WC_PLUGIN_FILE') && in_array('product', $active_post_types)){
            $results = $wpdb->get_results("SELECT DISTINCT ID AS id, 'post' AS type, 1 AS alt FROM {$wpdb->posts} p WHERE p.post_type = 'product' AND p.post_excerpt LIKE '%data-wpil=\"url\"%'");

            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }

        // if ACF is active
        if(class_exists('ACF') && !get_option('wpil_disable_acf', false)){
            $post_types_p = Wpil_Query::postTypes('p');
            $statuses_query_p = Wpil_Query::postStatuses('p');
            $acf_fields = Wpil_Query::querySpecifiedAcfFields();
            $results = $wpdb->get_results("SELECT DISTINCT m.post_id AS id, 'post' AS type, 1 AS alt FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE m.meta_key IN (SELECT DISTINCT SUBSTR(meta_key, 2) as `name` FROM {$wpdb->postmeta} WHERE meta_value LIKE 'field_%' AND SUBSTR(meta_key, 2) != '' {$acf_fields}) {$post_types_p} {$statuses_query_p} AND meta_value LIKE '%data-wpil=\"url\"%'");

            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }
        // TODO: Add support for custom registered meta fields

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

    public static function getPostsFromAlternateLocations($posts, $url, $meta_relative){
        global $wpdb;

        $found_posts = false;
        $active_post_types = Wpil_Settings::getPostTypes();

        // if WP Recipes is active and the user wants to add links to the recipe notes
        if(defined('WPRM_POST_TYPE') && in_array('wprm_recipe', $active_post_types)){
            $keys = Wpil_Editor_WPRecipe::get_selected_fields();
            if(!empty($keys)){
                $keys = "'" . implode("','", array_keys($keys)) . "'";
                $results = $wpdb->get_results("SELECT DISTINCT m.post_id AS ID FROM {$wpdb->postmeta} m WHERE `meta_key` IN ({$keys}) AND (meta_value LIKE '%{$wpdb->esc_like($url->old)}%' OR meta_value LIKE '%{$wpdb->esc_like(htmlentities($url->old))}%' $meta_relative)");

                if(!empty($results)){
                    $found_posts = true;
                    $posts = array_merge($posts, $results);
                }
            }
        }

        // get the active builder meta keys
        $builder_meta = Wpil_Post::get_builder_meta_keys();

        // if we have builders to search for
        if(!empty($builder_meta)){
            $builder_meta = "('" . implode("','", $builder_meta) . "')";

            $post_types_p = Wpil_Query::postTypes('p');
            $statuses_query_p = Wpil_Query::postStatuses('p');
            $results = $wpdb->get_results("SELECT DISTINCT m.post_id AS ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE m.meta_key IN {$builder_meta} {$post_types_p} {$statuses_query_p} AND (meta_value LIKE '%{$wpdb->esc_like($url->old)}%' OR meta_value LIKE '%{$wpdb->esc_like(htmlentities($url->old))}%' {$meta_relative})");

            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }

        // if WooCommerce is active
        if(defined('WC_PLUGIN_FILE') && in_array('product', $active_post_types)){
            $posts_relative = '';
            if(Wpil_Link::isRelativeLink($url->old)){
                $posts_relative =  "OR p.post_excerpt LIKE '%href=\\\"{$wpdb->esc_like($url->old)}\\\"%'";
            }

            $results = $wpdb->get_results("SELECT DISTINCT ID FROM {$wpdb->posts} p WHERE p.post_type = 'product' AND (p.post_excerpt LIKE '%{$wpdb->esc_like($url->old)}%' OR p.post_excerpt LIKE '%{$wpdb->esc_like($url->old)}%' {$posts_relative})");

            if(!empty($results)){
                $found_posts = true;
                $posts = array_merge($posts, $results);
            }
        }

        // if ACF is active
        if(class_exists('ACF') && !get_option('wpil_disable_acf', false)){
            $post_types_p = Wpil_Query::postTypes('p');
            $statuses_query_p = Wpil_Query::postStatuses('p');
            $acf_fields = Wpil_Query::querySpecifiedAcfFields();
            $results = $wpdb->get_results("SELECT DISTINCT m.post_id AS ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE m.meta_key IN (SELECT DISTINCT SUBSTR(meta_key, 2) as `name` FROM {$wpdb->postmeta} WHERE meta_value LIKE 'field_%' AND SUBSTR(meta_key, 2) != '' {$acf_fields}) {$post_types_p} {$statuses_query_p} AND (meta_value LIKE '%{$wpdb->esc_like($url->old)}%' OR meta_value LIKE '%{$wpdb->esc_like(htmlentities($url->old))}%' {$meta_relative})");

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
                $post_ids[$post->ID] = $post;
            }

            $posts = array_values($post_ids);
        }

        return $posts;
    }

    /**
     * Helper function to create html encoded versions of urls.
     * Encodes both the old url and the new url parameters
     * 
     * @param object $url The url object to encode
     **/
    public static function encode_url($url){
        $new_url = json_decode(json_encode($url));
        $new_url->new = str_replace('&amp;amp;', '&amp;', htmlentities($url->new));
        $new_url->old = str_replace('&amp;amp;', '&amp;', htmlentities($url->old));
        return $new_url;
    }
}
