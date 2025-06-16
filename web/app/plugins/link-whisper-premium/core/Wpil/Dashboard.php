<?php

/**
 * Class Wpil_Dashboard
 */
class Wpil_Dashboard
{
    /**
     * Get posts count with selected types
     *
     * @return string|null
     */
    public static function getPostCount()
    {
        global $wpdb;
        $report_table = $wpdb->prefix . 'wpil_report_links';
        $post_types = implode("','", Wpil_Settings::getPostTypes());
        $statuses_query = Wpil_Query::postStatuses();
        $ignoring = Wpil_Settings::hideIgnoredPosts();

        // if the user is removing ignored posts from the reports
        $ignored = "";
        if($ignoring){
            $ignored = Wpil_Query::ignoredPostIds();
        }

        $count = 0;
        // if we're using the link table for our data
        if(Wpil_Settings::use_link_table_for_data()){
            // lets save oursevles some headaches with giant metatables by just using the report link table
            $ignored = (!empty($ignored)) ? str_replace('p.ID', 'post_id', $ignored): "";
            $count = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$report_table} WHERE `post_type` = 'post' {$ignored}");
            $taxonomies = Wpil_Settings::getTermTypes();
            if (!empty($taxonomies)) {
                $ignored = "";
                if($ignoring){
                    $ignored = Wpil_Query::ignoredTermIds();
                    $ignored = (!empty($ignored)) ? str_replace('t.term_id', 'post_id', $ignored): "";
                }
                $count += $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$report_table} WHERE `post_type` = 'term' {$ignored}");
            }
        }else{
            $count = $wpdb->get_var("SELECT count(p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE post_type IN ('$post_types') $statuses_query {$ignored} AND meta_key = 'wpil_sync_report3' AND meta_value = '1'");
            $taxonomies = Wpil_Settings::getTermTypes();
            if (!empty($taxonomies)) {
                $ignored = "";
                if($ignoring){
                    $ignored = Wpil_Query::ignoredTermIds();
                }
                $count += $wpdb->get_var("SELECT count(*) FROM {$wpdb->term_taxonomy} t WHERE t.taxonomy IN ('" . implode("', '", $taxonomies) . "') {$ignored}");
            }
        }

        return $count;
    }

    /**
     * Get all links count
     *
     * @return string|null
     */
    public static function getLinksCount()
    {
        if (!Wpil_Report::link_table_is_created()) {
            return 0;
        }

        global $wpdb;

        // if the user is hiding the ignored posts, get the posts to ignore
        $ignored = Wpil_Query::getReportLinksIgnoreQueryStrings();

        return $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}wpil_report_links WHERE `has_links` = 1 {$ignored}");
    }

    /**
     * Get internal links count
     *
     * @return string|null
     */
    public static function getInternalLinksCount()
    {
        if (!Wpil_Report::link_table_is_created()) {
            return 0;
        }

        global $wpdb;

        // if the user is hiding the ignored posts, get the posts to ignore
        $ignored = Wpil_Query::getReportLinksIgnoreQueryStrings();

        return $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}wpil_report_links WHERE internal = 1 {$ignored}");
    }

    /**
     * Get posts count without inbound internal links
     *
     * @return string|null
     */
    public static function getOrphanedPostsCount()
    {
        global $wpdb;
        $link_report_table = $wpdb->prefix . 'wpil_report_links';

        $ignore_string = '';
        $ignored_ids = Wpil_Settings::getItemTypeIds(Wpil_Settings::getIgnoreOrphanedPosts(), 'post');

        if(Wpil_Settings::use_link_table_for_data()){
            if(!empty($ignored_ids)){
                $ignore_string = " AND ID NOT IN ('" . implode("', '", $ignored_ids) . "')";
            }
    
            $statuses_query = Wpil_Query::postStatuses('a');
            $post_types = Wpil_Query::postTypes('a');

            $ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} a WHERE a.ID NOT IN (select distinct `target_id` from {$link_report_table} where `target_type` = 'post' and has_links > 0) {$ignore_string} {$ignore_string} {$statuses_query} {$post_types}");
        }else{
            if(!empty($ignored_ids)){
                $ignore_string = " AND m.post_id NOT IN ('" . implode("', '", $ignored_ids) . "')";
            }
    
            $statuses_query = Wpil_Query::postStatuses('p');
            $post_types = Wpil_Query::postTypes('p');

            $ids = $wpdb->get_col(
                "SELECT DISTINCT m.post_id FROM {$wpdb->postmeta} m 
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID 
                    WHERE m.meta_key = 'wpil_links_inbound_internal_count' AND m.meta_value = 0 $ignore_string $statuses_query $post_types");
        }
        if(!empty($ids)){

            // if RankMath is active, remove any ids that are set to "noIndex"
            if(defined('RANK_MATH_VERSION')){
                $rank_math_meta = $wpdb->get_results("SELECT `post_id`, `meta_value` FROM {$wpdb->postmeta} WHERE `meta_key` = 'rank_math_robots'");
                $ids = array_flip($ids);
                foreach($rank_math_meta as $data){
                    if(false !== strpos($data->meta_value, 'noindex')){ // we can check the unserialized data because Rank Math uses a simple flag like structure to the saved data.
                        unset($ids[$data->post_id]);
                    }
                }
                $ids = array_flip($ids);
            }

            // if Yoast is active, remove any posts that are set to "noIndex"
            if(defined('WPSEO_VERSION')){
                $no_index_ids = $wpdb->get_col("SELECT DISTINCT `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = '_yoast_wpseo_meta-robots-noindex' AND meta_value = '1'");
                $ids = array_diff($ids, $no_index_ids);
            }

            // also remove any posts that are hidden by redirects
            $redirected = Wpil_Settings::getRedirectedPosts();
            $ids = array_diff($ids, $redirected);
        }

        // count the remaining ids
        $count = count($ids);

        // get if the user wants to include categories in the report
        $options = get_user_meta(get_current_user_id(), 'report_options', true);
        $show_categories = (!empty($options['show_categories']) && $options['show_categories'] == 'off') ? false : true;

        // if there are terms selected in the settings
        if (!empty(Wpil_Settings::getTermTypes()) && $show_categories) {

            if(Wpil_Settings::use_link_table_for_data()){
                $term_ids = $wpdb->get_col("SELECT a.term_id FROM {$wpdb->terms} a WHERE a.term_id NOT IN (select distinct `target_id` from {$link_report_table} where `target_type` = 'term' and has_links > 0)");
            }else{
                $term_ids = $wpdb->get_col("SELECT DISTINCT term_id FROM {$wpdb->prefix}termmeta WHERE meta_key = 'wpil_links_inbound_internal_count' AND meta_value = 0");
            }

            $ignored_ids = Wpil_Settings::getItemTypeIds(Wpil_Settings::getIgnoreOrphanedPosts(), 'term');
            if(!empty($ignored_ids)){
                $term_ids = array_diff($term_ids, $ignored_ids);
            }

            // if RankMath is active, remove any ids that are set to "noIndex"
            if(defined('RANK_MATH_VERSION')){
                foreach($term_ids as $key => $id){
                    $term = get_term($id);
                    if(is_a($term, 'WP_Error') || empty(\RankMath\Helper::is_term_indexable($term))){
                        unset($term_ids[$key]);
                    }
                }
            }

            // if Yoast is active rmeove any ids that are set to "noIndex"
            if(defined('WPSEO_VERSION')){
                $yoast_taxonomy_data = get_site_option('wpseo_taxonomy_meta');
                if(!empty($yoast_taxonomy_data)){
                    foreach($term_ids as $key => $id){
                        // if the category has been set to noIndex
                        if( isset($yoast_taxonomy_data[$id]) &&
                            isset($yoast_taxonomy_data[$id]['wpseo_noindex']) && 
                            'noindex' === $yoast_taxonomy_data[$id]['wpseo_noindex'])
                        {
                            // remove the id from the list
                            unset($term_ids[$key]);
                        }
                    }
                }
            }

            if(!empty($term_ids)){
                $taxonomies = Wpil_Query::taxonomyTypes();
                $term_ids = implode(',', $term_ids);
                $count += $wpdb->get_var("SELECT count(term_id) FROM {$wpdb->term_taxonomy} WHERE term_id IN ({$term_ids}) {$taxonomies}");
            }
        }

        return $count;
    }

    /** 
     * Really simple check if there's orphaned posts in the database.
     * Meant to be fast, so it only checks if there's a post/term that doens't have inbound internal links
     **/
    public static function hasOrphanedPosts(){
        global $wpdb;
        $link_report_table = $wpdb->prefix . 'wpil_report_links';

        $has_orphaned = $wpdb->get_row("SELECT * FROM {$wpdb->postmeta} WHERE meta_key = 'wpil_links_inbound_internal_count' AND meta_value = 0 LIMIT 1");

        if(empty($has_orphaned)){
            $has_orphaned = $wpdb->get_row("SELECT * FROM {$wpdb->termmeta} WHERE meta_key = 'wpil_links_inbound_internal_count' AND meta_value = 0 LIMIT 1");
        
            if(empty($has_orphaned)){
                $has_orphaned = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE `ID` NOT IN (select distinct `target_id` from {$link_report_table} where `target_type` = 'post') LIMIT 1");
            }
        }

        return !empty($has_orphaned);
    }

    /**
     * Get 10 most used domains from external links
     *
     * @return array
     */
    public static function getTopDomains()
    {
        if (!Wpil_Report::link_table_is_created()) {
            return [];
        }

        global $wpdb;

        // if the user is hiding the ignored posts, get the posts to ignore
        $ignored = Wpil_Query::getReportLinksIgnoreQueryStrings();

        $result = $wpdb->get_results("SELECT host, count(*) as `cnt` FROM {$wpdb->prefix}wpil_report_links WHERE host IS NOT NULL {$ignored} GROUP BY host ORDER BY count(*) DESC LIMIT 10");

        return $result;
    }

    /**
     * Get broken external links count
     *
     * @return string|null
     */
    public static function getBrokenLinksCount()
    {
        global $wpdb;
        Wpil_Error::prepareTable(false);
        if(!empty(get_option('wpil_site_db_version', false))){
            return $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}wpil_broken_links WHERE `ignore_link` != 1 AND (`code` < 200 OR `code` > 299)");
        }else{
            return $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}wpil_broken_links WHERE `code` < 200 OR `code` > 299");
        }
    }

    /**
     * Get broken external links count
     *
     * @return string|null
     */
    public static function getBrokenVideoLinksCount()
    {
        global $wpdb;
        if(!empty(get_option('wpil_site_db_version', false))){
            return $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}wpil_broken_links WHERE `ignore_link` != 1 AND (`code` = 825)");
        }else{
            return $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}wpil_broken_links WHERE `code` < 825");
        }
    }

    /**
     * Get broken external links count
     *
     * @return array|null
     */
    public static function getAllErrorCodes()
    {
        global $wpdb;
        Wpil_Error::prepareTable(false);
        if(!empty(get_option('wpil_site_db_version', false))){
            return $wpdb->get_col("SELECT DISTINCT `code` FROM {$wpdb->prefix}wpil_broken_links WHERE `code` != 768");
        }

        return array();
    }

    /**
     * Get broken internal links count
     *
     * @return string
     */
    public static function get404LinksCount()
    {
        global $wpdb;
        return $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}wpil_broken_links WHERE code = 404");
    }

    /**
     * Get data for domains table
     *
     * @param $per_page
     * @param $page
     * @param $search
     * @return array
     */
    public static function getDomainsData($per_page, $page, $search, $search_type = 'domain', $show_attributes = true, $list_all = false, $show_untargeted = false)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wpil_report_links';

        $domains = [];
        $host_search = "";
        if(!empty($search)){
            if($search_type === 'domain'){
                $multi = false;
                if(false !== strpos($search, ',')){
                    $bits = explode(',', $search);
                    if(!empty($bits)){
                        $domain_search = array();
                        foreach($bits as $bit){
                            $bit = trim($bit);
                            if(!empty($bit)){
                                $domain_search[] = $wpdb->prepare("host LIKE %s", Wpil_Toolbox::esc_like($bit));
                            }
                        }

                        if(!empty($domain_search)){
                            $search = " AND " . implode(" OR ", $domain_search);
                            $multi = true;
                        }
                    }
                }

                if(empty($multi)){
                    $search = $wpdb->prepare(" AND host LIKE %s", Wpil_Toolbox::esc_like($search));
                }

            }elseif($search_type === 'links'){
                $multi = false;
                if(false !== strpos($search, ',')){
                    $bits = explode(',', $search);
                    if(!empty($bits)){
                        $links = array();
                        foreach($bits as $bit){
                            $bit = trim($bit);
                            if(!empty($bit)){
                                $links[] = $wpdb->prepare("raw_url LIKE %s", Wpil_Toolbox::esc_like(mb_ereg_replace('&', '&amp;', $bit)));
                            }
                        }

                        if(!empty($links)){
                            $search = " AND " . implode(" OR ", $links);
                            $multi = true;
                        }
                    }
                }

                if(empty($multi)){
                    $search = $wpdb->prepare(" AND raw_url LIKE %s", Wpil_Toolbox::esc_like(mb_ereg_replace('&', '&amp;', $search)));
                }
            }else{
                $search = '';
            }
        }else{
            $search = '';
        }

        $untargeted = '';
        if($show_untargeted){
            $untargeted = "AND link_id IN (SELECT d.link_id FROM {$table} d LEFT JOIN {$wpdb->posts} p ON d.target_id = p.ID WHERE (d.internal = 1 AND d.target_id = 0) OR (d.target_id > 0 AND d.target_type = 'post' AND p.ID IS NULL))";
        }

        $offset = $page > 0 ? (((int)$page - 1) * (int)$per_page) : 0;
        $limit = "LIMIT " . (int)$per_page . " OFFSET {$offset}";
        $hosts = $wpdb->get_results("SELECT host, count(host) as 'host_count' from {$table} WHERE host IS NOT NULL {$search} GROUP BY host ORDER BY host_count DESC {$limit}");

        if(!empty($hosts)){
            $host_search = array();
            foreach($hosts as $host){
                $host_search[] = $host->host;
            }
            $host_search = " AND host IN ('" . implode('\', \'', array_flip(array_flip($host_search))) . "')";
        }

        $ignored = Wpil_Query::getReportLinksIgnoreQueryStrings();
        $result = (!empty($host_search)) ? $wpdb->get_results("SELECT * FROM {$table} WHERE host IS NOT NULL {$host_search} {$untargeted} {$search} {$ignored}"): array();
        $post_objs = array();
        foreach ($result as $link) {
            $host = $link->host;
            $id = $link->post_id;
            $type = $link->post_type;
            $cache_id = $type . $id;

            // if we haven't used this post yet
            if(!isset($post_objs[$cache_id])){
                // create it fresh for the post var
                $p = new Wpil_Model_Post($id, $type);
                // and then add it to the object array so we can use it later
                $post_objs[$cache_id] = $p;
            }else{
                // if we have used this post, obtain it from the object list
                $p = $post_objs[$cache_id];
            }

            if (empty($domains[$host])) {
                $dat = ['host' => $host, 'posts' => [], 'links' => []];
                if($show_attributes){
                    $dat['attributes'] = Wpil_Settings::get_active_link_attrs($host, true, true);
                }
                $domains[$host] = $dat;
            }

            if (empty($domains[$host]['posts'][$id])) {
                $domains[$host]['posts'][$id] = $p;
            }

            if(count($domains[$host]['links']) < 100 || $list_all){
                $domains[$host]['links'][] = new Wpil_Model_Link([
                    'link_id' => $link->link_id,
                    'url' => $link->raw_url,
                    'anchor' => strip_tags($link->anchor),
                    'post' => $p,
                    'link_whisper_created' => (isset($link->link_whisper_created) && !empty($link->link_whisper_created)) ? 1: 0,
                    'is_autolink' => (isset($link->is_autolink) && !empty($link->is_autolink)) ? 1: 0,
                    'tracking_id' => (isset($link->tracking_id) && !empty($link->tracking_id)) ? $link->tracking_id: 0,
                    'module_link' => (isset($link->module_link) && !empty($link->module_link)) ? $link->module_link: 0,
                    'link_context' => (isset($link->link_context) && !empty($link->link_context)) ? $link->link_context: 0,
                ]);
            }else{
                $domains[$host]['links'][] = false;
            }

            // get the protocol for the domain as best we can
            if(!isset($domains[$host]['protocol']) || 'https://' !== $domains[$host]['protocol']){
                if(false !== strpos($link->clean_url, 'https:')){
                    $domains[$host]['protocol'] = 'https://';
                }else{
                    $domains[$host]['protocol'] = 'http://';
                }
            }

        }

        usort($domains, function($a, $b){
            if (count($a['links']) == count($b['links'])) {
                return 0;
            }

            return (count($a['links']) < count($b['links'])) ? 1 : -1;
        });

        $total = $wpdb->get_var("SELECT COUNT(DISTINCT `host`) FROM {$table} WHERE host IS NOT NULL");

        return [
            'total' => $total,
            'domains' => $domains
        ];
    }

    /**
     * Gets the data for the domain dropdown via Ajax!
     * Intended to work in batches, so an offset is used to determine what links to pull
     * 
     **/
    public static function ajax_get_domains_dropdown_data(){
        global $wpdb;

        Wpil_Base::verify_nonce('wpil-collapsible-nonce');

        if(!isset($_POST['dropdown_type']) || !isset($_POST['host']) || !isset($_POST['item_count'])){
            wp_send_json(array('error' => array('title' => __('Data Missing', 'wpil'), 'text' => __('Some of the data required to load the rest of the dropdown is missing. Please reload the page and try opening the dropdown again.', 'wpil'))));
        }

        $search = (isset($_POST['search']) && !empty($_POST['search'])) ? trim($_POST['search']): '';
        $search_type = (isset($_POST['search_type']) && !empty($_POST['search_type'])) ? $_POST['search_type']: false;

        if(!empty($search)){
            if($search_type === 'domain'){
                $search = $wpdb->prepare(" AND host LIKE %s", Wpil_Toolbox::esc_like($search));
            }elseif($search_type === 'links'){
                $multi = false;
                if(false !== strpos($search, ',')){
                    $bits = explode(',', $search);
                    if(!empty($bits)){
                        $links = array();
                        foreach($bits as $bit){
                            $bit = trim($bit);
                            if(!empty($bit)){
                                $links[] = $wpdb->prepare("raw_url LIKE %s", Wpil_Toolbox::esc_like(mb_ereg_replace('&', '&amp;', $bit)));
                            }
                        }

                        if(!empty($links)){
                            $search = " AND " . implode(" OR ", $links);
                            $multi = true;
                        }
                    }
                }

                if(empty($multi)){
                    $search = $wpdb->prepare(" AND raw_url LIKE %s", Wpil_Toolbox::esc_like(mb_ereg_replace('&', '&amp;', $search)));
                }
            }else{
                $search = '';
            }
        }else{
            $search = '';
        }

        $offset = (isset($_POST['item_count'])) ? (int) $_POST['item_count']: 0;
        $limit = "LIMIT 200 OFFSET {$offset} ";
        $host = "AND host = '" . wp_parse_url(esc_url_raw($_POST['host']), PHP_URL_HOST) . "'";

        $ignored = Wpil_Query::getReportLinksIgnoreQueryStrings();
        $post_check = ($_POST['dropdown_type'] === 'posts') ? "GROUP BY post_id ORDER BY link_id ASC": "";
        $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpil_report_links WHERE host IS NOT NULL {$host} {$search} {$ignored} {$post_check} {$limit}");

        $post_objs = array();
        $domain = array();
        foreach ($result as $link) {
            $id = $link->post_id;
            $type = $link->post_type;
            $cache_id = $type . $id;

            // if we haven't used this post yet
            if(!isset($post_objs[$cache_id])){
                // create it fresh for the post var
                $p = new Wpil_Model_Post($id, $type);
                // and then add it to the object array so we can use it later
                $post_objs[$cache_id] = $p;
            }else{
                // if we have used this post, obtain it from the object list
                $p = $post_objs[$cache_id];
            }

            if (empty($domain['posts'][$id])) {
                $domain['posts'][$id] = $p;
            }

            // get the protocol for the domain as best we can
            if(!isset($domain['protocol']) || 'https://' !== $domain['protocol']){
                if(false !== strpos($link->clean_url, 'https:')){
                    $domain['protocol'] = 'https://';
                }else{
                    $domain['protocol'] = 'http://';
                }
            }

            $domain['links'][] = new Wpil_Model_Link([
                'link_id' => $link->link_id,
                'url' => $link->raw_url,
                'anchor' => strip_tags($link->anchor),
                'post' => $p,
                'link_whisper_created' => (isset($link->link_whisper_created) && !empty($link->link_whisper_created)) ? 1: 0,
                'is_autolink' => (isset($link->is_autolink) && !empty($link->is_autolink)) ? 1: 0,
                'tracking_id' => (isset($link->tracking_id) && !empty($link->tracking_id)) ? $link->tracking_id: 0,
                'module_link' => (isset($link->module_link) && !empty($link->module_link)) ? $link->module_link: 0,
                'link_context' => (isset($link->link_context) && !empty($link->link_context)) ? $link->link_context: 0
            ]);
        }

        // now that we have the data, it's time to format it!
        $response = '';
        $county = 0;
        if($_POST['dropdown_type'] === 'posts'){
            $county = count($domain['posts']);
            foreach($domain['posts'] as $post){
                $response .= '<li>'
                . esc_html($post->getTitle()) . '<br>
                <a href="' . admin_url('post.php?post=' . (int)$post->id . '&action=edit') . '" target="_blank">[edit]</a> 
                <a href="' . esc_url($post->getLinks()->view) . '" target="_blank">[view]</a><br><br>
              </li>';
            }
        }else{
            $county = count($domain['links']);
            foreach($domain['links'] as $link){
                $response .= '<li>
                    <input type="checkbox" class="wpil_link_select" data-post_id="'.$link->post->id.'" data-post_type="'.$link->post->type.'" data-anchor="' . esc_attr(base64_encode($link->anchor)) . '" data-url="'.base64_encode($link->url).'">
                    <div>
                        <div style="margin: 3px 0;"><b>Post Title:</b> <a href="' . esc_url($link->post->getLinks()->view) . '" target="_blank">' . esc_html($link->post->getTitle()) . '</a></div>
                        <div style="margin: 3px 0;"><b>URL:</b> <a href="' . esc_url($link->url) . '" target="_blank">' . esc_html($link->url) . '</a></div>
                        <div style="margin: 3px 0;"><b>Anchor Text:</b> <a href="' . esc_url(add_query_arg(['wpil_admin_frontend' => '1', 'wpil_admin_frontend_data' => $link->create_scroll_link_data()], $link->post->getLinks()->view)) . '" target="_blank">' . esc_html($link->anchor) . ' <span class="dashicons dashicons-external" style="position: relative;top: 3px;"></span></a></div>
                        ' . Wpil_Report::get_dropdown_icons($link->post, $link);

                        if('related-post-link' !== Wpil_Toolbox::get_link_context($link->link_context)){
                $response .= '<a href="#" class="wpil_edit_link" target="_blank">[' . __('Edit URL', 'wpil') . ']</a>
                                <div class="wpil-domains-report-url-edit-wrapper">
                                    <input class="wpil-domains-report-url-edit" type="text" value="' . esc_attr($link->url) . '">
                                    <button class="wpil-domains-report-url-edit-confirm wpil-domains-edit-link-btn" data-link_id="' . $link->link_id . '" data-post_id="'.$link->post->id.'" data-post_type="'.$link->post->type.'" data-anchor="' . esc_attr($link->anchor) . '" data-url="'.esc_url($link->url).'" data-nonce="' . wp_create_nonce('wpil_report_edit_' . $link->post->id . '_nonce_' . $link->link_id) . '">
                                        <i class="dashicons dashicons-yes"></i>
                                    </button>
                                    <button class="wpil-domains-report-url-edit-cancel wpil-domains-edit-link-btn">
                                        <i class="dashicons dashicons-no"></i>
                                    </button>
                                </div>';
                        }
                $response .= '
                    </div>
                </li>';
            }
        }

        wp_send_json(array('success' => array('item_data' => $response, 'item_count' => $county)));
    }
}
