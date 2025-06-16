<?php

/**
 * Work with settings
 */
class Wpil_Settings
{
    public static $ignore_phrases = null;
    public static $stemmed_ignore_phrases = null;
    public static $ignore_words = null;
    public static $stemmed_ignore_words = null;
    public static $keys = [
        'wpil_2_ignore_numbers',
        'wpil_2_post_types',
        'wpil_suggestion_limited_post_types',
        'wpil_2_term_types',
        'wpil_2_post_statuses',
        'wpil_2_links_open_new_tab',
        'wpil_limit_suggestions_to_post_types',
        'wpil_limit_autolinks_to_post_types',
        'wpil_autolink_limited_post_types',
        'wpil_2_debug_mode',
        'wpil_option_update_reporting_data_on_save',
        'wpil_skip_section_type',
        'wpil_skip_sentences',
        'wpil_selected_language',
        'wpil_ignore_links',
        'wpil_ignore_sitemap_posts',
        'wpil_ignore_categories',
        'wpil_dont_show_ignored_posts',
        'wpil_show_all_links',
        'wpil_count_related_post_links',
        'wpil_manually_trigger_suggestions',
        'wpil_disable_outbound_suggestions',
        'wpil_make_suggestion_filtering_persistent',
        'wpil_max_suggestion_post_count',
        'wpil_suggestion_anchor_max_size',
        'wpil_suggestion_anchor_min_size',
        'wpil_full_html_suggestions',
        'wpil_ignore_keywords_posts',
        'wpil_ignore_keywords_posts_by_category',
        'wpil_ignore_orphaned_posts',
        'wpil_ignore_orphaned_posts_by_category',
        'wpil_nofollow_ignore_domains',
        'wpil_new_tab_ignore_domains',
        'wpil_same_tab_ignore_domains',
        'wpil_chat_gpt_api',
        'wpil_ai_batch_processing_limits',
        'wpil_open_ai_api_monthly_limit',
        'wpil_enable_ai_batch_processing',
        'wpil_selected_ai_batch_processes',
        'wpil_ai_generated_keyword_max_count',
        'wpil_use_ai_suggestions',
        'wpil_disable_ai_anchor_building',
        'wpil_restrict_to_top_ai_suggestions',
        'wpil_sitemap_embedding_relatedness_threshold',
        'wpil_new_tab_domains',
        'wpil_same_tab_domains',
        'wpil_links_to_ignore',
        'wpil_broken_links_to_ignore',
        'wpil_ignore_elements_by_class',
        'wpil_ignore_shortcodes_by_name',
        'wpil_ignore_linking_roles',
        'wpil_ignore_tags_from_linking',
        'wpil_ignore_elementor_from_linking',
        'wpil_ignore_pages_completely',
        'wpil_marked_as_external',
        'wpil_disable_acf',
        'wpil_use_seo_titles',
        'wpil_post_slug_for_suggestions',
        'wpil_link_external_sites',
        'wpil_link_external_sites_access_code',
        'wpil_disable_external_site_updating',
        'wpil_external_site_use_json_api',
        'wpil_2_show_all_post_types',
        'wpil_disable_search_update',
        'wpil_domains_marked_as_internal',
        'wpil_custom_fields_to_process',
        'wpil_acf_post_reference_fields',
        'wpil_add_icon_to_external_link',
        'wpil_external_link_icon',
        'wpil_external_link_icon_title',
        'wpil_external_link_icon_size',
        'wpil_external_link_icon_color',
        'wpil_external_link_icon_html_exclude',
        'wpil_external_link_icon_inner_html_exclude',
        'wpil_external_link_icon_post_ignore',
        'wpil_internal_link_icon_post_ignore',
        'wpil_add_icon_to_internal_link',
        'wpil_internal_link_icon',
        'wpil_internal_link_icon_title',
        'wpil_internal_link_icon_size',
        'wpil_internal_link_icon_color',
        'wpil_internal_link_icon_html_exclude',
        'wpil_internal_link_icon_inner_html_exclude',
        'wpil_process_these_acf_fields',
        'wpil_link_to_yoast_cornerstone',
        'wpil_suggest_to_outbound_posts',
        'wpil_sponsored_domains',
        'wpil_nofollow_domains',
        'wpil_dofollow_domains',
        'wpil_only_match_target_keywords',
        'wpil_add_noreferrer',
        'wpil_add_nofollow',
        'wpil_filter_staging_url',
        'wpil_live_site_url',
        'wpil_staging_site_url',
        'wpil_delete_all_data',
        'wpil_enable_telemetry',
        'wpil_external_links_open_new_tab',
        'wpil_insert_links_as_relative',
        'wpil_prevent_two_way_linking',
        'wpil_disable_autolinking_on_post_update',
        'wpil_enable_autolink_cron_task',
        'wpil_disable_autolink_insert_run',
        'wpil_ignore_image_urls',
        'wpil_include_image_src',
        'wpil_use_ugly_permalinks',
        'wpil_delete_link_inner_html',
        'wpil_delete_links_to_post_on_delete',
        'wpil_include_post_meta_in_support_export',
        'wpil_ignore_acf_fields',
        'wpil_ignore_click_links',
        'wpil_open_all_internal_new_tab',
        'wpil_open_all_external_new_tab',
        'wpil_open_all_internal_same_tab',
        'wpil_open_all_external_same_tab',
        'wpil_js_open_new_tabs',
        'wpil_add_destination_title',
        'wpil_disable_broken_link_cron_check',
        'wpil_disable_click_tracking',
        'wpil_delete_old_click_data',
        'wpil_max_links_per_post',
        'wpil_max_inbound_links_per_post',
        'wpil_max_linking_age',
        'wpil_max_suggestion_count',
        'wpil_disable_click_tracking_info_gathering',
        'wpil_autotag_gsc_keywords',
        'wpil_autotag_gsc_keyword_count',
        'wpil_autotag_gsc_keyword_basis',
        'wpil_show_comment_links',
        'wpil_ignore_latest_posts',
        'wpil_update_reusable_block_links',
        'wpil_content_formatting_level',
        'wpil_override_global_post_during_scan',
        'wpil_optimize_link_scan_for_speed',
        'wpil_use_link_data_table',
        'wpil_update_post_edit_date',
        'wpil_remove_noindex_post_suggestions',
        'wpil_force_https_links',
        'wpil_track_all_element_clicks',
        'wpil_clear_cdn_link_delete',
        'wpil_object_cache_flush',
        'wpil_update_post_after_action',
        'wpil_optimize_option_table',
        'wpil_selected_target_keyword_sources',
        'wpil_selected_post_content_target_keyword_sources',
        'wpil_get_partial_titles',
        'wpil_partial_title_word_count',
        'wpil_partial_title_split_char',
        'wpil_activate_related_posts',
        'wpil_related_posts_widget_text',
        'wpil_related_posts_insert_method',
        'wpil_related_post_existing_link_handling',
        'wpil_related_post_link_count',
        'wpil_related_post_widget_layout',
        'wpil_related_post_term_search',
        'wpil_related_post_cat_ignore',
        'wpil_related_post_tag_ignore',
        'wpil_related_post_parent_search',
        'wpil_related_posts_select_method',
        'wpil_related_posts_use_ai_data',
        'wpil_related_posts_ai_relation_threshold',
        'wpil_related_posts_use_thumbnail',
        'wpil_related_posts_use_first_image_as_thumbnail',
        'wpil_related_posts_thumbnail_position',
        'wpil_related_posts_thumbnail_size',
        'wpil_related_posts_active_post_types',
        'wpil_related_posts_styling',
        'wpil_related_posts_hide_empty_widget',
        'wpil_related_posts_sort_order',
        'wpil_related_posts_orphaned_linking',
        'wpil_disable_link_insert_tracking',
        'wpil_filter_out_frontend_link_attributes',
        'wpil_related_posts_widget_placement',
        'wpil_related_posts_widget_placement_paragraphs'
    ];
    public static $link_attrs = null;

    /**
     * Show settings page
     */
    public static function init()
    {
        $types_active = Wpil_Settings::getPostTypes();
        $suggestion_types_active = self::getSuggestionPostTypes();
        $autolink_types_active = self::get_autolinking_post_types();
        $term_types_active = Wpil_Settings::getTermTypes();
        if(empty(get_option('wpil_2_show_all_post_types', false))){
            $types_available = get_post_types(['public' => true]);
        }else{
            $types_available = get_post_types();
        }

        $types_available = Wpil_Settings::getPostTypeLabels($types_available);

        $term_types_available = get_taxonomies();
        $statuses_available = [
            'publish',
            'private',
            'future',
            'pending',
            'draft'
        ];
        $statuses_active = Wpil_Settings::getPostStatuses();

        include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/wpil_settings_v2.php';
    }

    /**
     * Get ignore phrases
     */
    public static function getIgnorePhrases()
    {
        if(is_null(self::$ignore_phrases)){
            $phrases = [];
            $stemmed = array();
            $no_stemmed = is_null(self::$stemmed_ignore_phrases);
            foreach (self::getIgnoreWords() as $word) {
                if (strpos($word, ' ') !== false) {
                    $cleaned = preg_replace('/\s+/', ' ', $word);
                    $phrases[] = $cleaned;
                    if($no_stemmed){
                        $stemmed[] = Wpil_Word::getStemmedSentence($cleaned);
                    }
                }
            }

            self::$ignore_phrases = $phrases;

            if($no_stemmed){
                self::$stemmed_ignore_phrases = $stemmed;
            }
        }

        return self::$ignore_phrases;
    }

    /**
     * Gets the stemmed version of the phrases to ignore
     **/
    public static function getStemmedIgnorePhrases()
    {
        if(is_null(self::$stemmed_ignore_phrases)){
            self::getIgnorePhrases();
        }

        return self::$stemmed_ignore_phrases;
    }

    /**
     * Gets the site's current language as defined in the WP settings
     **/
    public static function getSiteLanguage(){
        $locale = get_locale();

        switch ($locale) {
            case 'en':
            case 'en_AU':
            case 'en_GB':
            case 'en_CA':
            case 'en_NZ':
            case 'en_ZA':
                $language = 'english';
                break;
            case 'es_ES':
            case 'es_AR':
            case 'es_EC':
            case 'es_CO':
            case 'es_VE':
            case 'es_DO':
            case 'es_UY':
            case 'es_PE':
            case 'es_CL':
            case 'es_PR':
            case 'es_CR':
            case 'es_GT':
            case 'es_MX':
                $language = 'spanish';
                break;
            case 'fr_CA':
            case 'fr_FR':
            case 'fr_BE':
                $language = 'french';
                break;
            case 'de_CH_informal':
            case 'de_DE':
            case 'de_CH':
            case 'de_AT':
                $language = 'german';
                break;
            case 'ru_RU':
                $language = 'russian';
                break;
            case 'pt_BR':
            case 'pt_PT_ao90':
            case 'pt_PT':
            case 'pt_AO':
                $language = 'portuguese';
                break;
            case 'nl_NL':
            case 'nl_NL_formal':
            case 'nl_BE':
                $language = 'dutch';
                break;
            case 'da_DK':
                $language = 'danish';
                break;
            case 'it_IT':
                $language = 'italian';
                break;
            case 'pl_PL':
                $language = 'polish';
                break;
            case 'sk_SK':
                $language = 'slovak';
                break;
            case 'nb_NO':
                $language = 'norwegian';
                break;
            case 'sv_SE':
                $language = 'swedish';
                break;
            case 'ar':
            case 'ary':
                $language = 'arabic';
                break;
            case 'sr_RS':
                $language = 'serbian';
                break;
            case 'fi':
                $language = 'finnish';
                break;
            case 'he_IL':
                $language = 'hebrew';
                break;
            case 'hi_IN':
                $language = 'hindi';
                break;
            case 'hu_HU':
                $language = 'hungarian';
                break;
            case 'ro_RO':
                $language = 'romanian';
                break;
            case 'uk':
                $language = 'ukrainian';
                break;
            case 'id_ID':
                $language = 'indonesian';
                break;
            default:
                $language = 'english';
                break;
        }

        return $language;
    }

    /**
     * Get ignore words
     */
    public static function getIgnoreWords()
    {
        if (is_null(self::$ignore_words)) {
            $words = get_option('wpil_2_ignore_words', null);
            // get the user's current language
            $selected_language = self::getSelectedLanguage();

            // if there are no stored words or the current language is different from the selected one
            if (is_null($words) || (WPIL_CURRENT_LANGUAGE !== $selected_language)) {
                $ignore_words_file = self::getIgnoreFile($selected_language);
                $words = file($ignore_words_file);

                foreach($words as $key => $word) {
                    $words[$key] = trim(Wpil_Word::strtolower($word));
                }
            } else {
                $words = explode("\n", $words);
                $words = array_unique($words);
                sort($words);

                foreach($words as $key => $word) {
                    $words[$key] = trim(Wpil_Word::strtolower($word));
                }
            }

            self::$ignore_words = $words;
        }

        return self::$ignore_words;
    }

    /**
     * Get stemmed versions of the ignore words
     */
    public static function getStemmedIgnoreWords()
    {
        if (is_null(self::$stemmed_ignore_words)) {
            $words = self::getIgnoreWords();
            foreach($words as $key => $word) {
                $words[$key] = Wpil_Word::remove_accents(trim(Wpil_Stemmer::Stem($word)));
            }

            // remove any duplicates
            $words = array_keys(array_flip($words));

            self::$stemmed_ignore_words = $words;
        }

        return self::$stemmed_ignore_words;
    }

    /**
     * Gets all current ignore word lists.
     * The word list for the language the user is currently using is loaded from the settings.
     * All other languages are loaded from the word files
     **/
    public static function getAllIgnoreWordLists(){
        $current_language       = self::getSelectedLanguage();
        $supported_languages    = self::getSupportedLanguages();
        $all_ignore_lists       = array();

        // go over all currently supported languages
        foreach($supported_languages as $language_id => $supported_language){

            // if the current language is the user's selected one
            if($language_id === $current_language){

                $words = get_option('wpil_2_ignore_words', null);
                if(is_null($words)){
                    $words = self::getIgnoreWords();
                }else{
                    $words = explode("\n", $words);
                    $words = array_unique($words);
                    sort($words);
                    foreach($words as $key => $word) {
                        $words[$key] = trim(Wpil_Word::strtolower($word));
                    }
                }

                $all_ignore_lists[$language_id] = $words;
            }else{
                $ignore_words_file = self::getIgnoreFile($language_id);
                $words = array();
                if(file_exists($ignore_words_file)){
                    $words = file($ignore_words_file);
                }else{
                    // if there is no word file, skip to the next one
                    continue;
                }
                
                if(empty($words)){
                    $words = array();
                }
                
                foreach($words as $key => $word) {
                    $words[$key] = trim(Wpil_Word::strtolower($word));
                }
                
                $all_ignore_lists[$language_id] = $words;
            }
        }

        return $all_ignore_lists;
    }

    /**
     * Get ignore words file based on current language
     *
     * @param $language
     * @return string
     */
    public static function getIgnoreFile($language)
    {
        switch($language){
            case 'spanish':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/ES_ignore_words.txt';
                break;
            case 'french':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/FR_ignore_words.txt';
                break;
            case 'german':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/DE_ignore_words.txt';
                break;
            case 'russian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/RU_ignore_words.txt';
                break;
            case 'portuguese':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/PT_ignore_words.txt';
                break;
            case 'dutch':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/NL_ignore_words.txt';
                break;
            case 'danish':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/DA_ignore_words.txt';
                break;
            case 'italian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/IT_ignore_words.txt';
                break;
            case 'polish':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/PL_ignore_words.txt';
                break;            
            case 'slovak':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/SK_ignore_words.txt';
                break;
            case 'norwegian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/NO_ignore_words.txt';
                break;
            case 'swedish':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/SW_ignore_words.txt';
                break;            
            case 'arabic':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/AR_ignore_words.txt';
                break;
            case 'serbian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/SR_ignore_words.txt';
                break;
            case 'finnish':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/FI_ignore_words.txt';
                break;
            case 'hebrew':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/HE_ignore_words.txt';
                break;
            case 'hindi':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/HI_ignore_words.txt';
                break;
            case 'hungarian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/HU_ignore_words.txt';
                break;
            case 'romanian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/RO_ignore_words.txt';
                break;
            case 'ukrainian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/UK_ignore_words.txt';
                break;
            case 'indonesian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/ID_ignore_words.txt';
                break;
            default:
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/EN_ignore_words.txt';
                break;
        }

        return $file;
    }

    /**
     * Get selected post types
     *
     * @return mixed|void
     */
    public static function getPostTypes()
    {
        return get_option('wpil_2_post_types', ['post', 'page']);
    }

    /**
     * Get the post types that users have limited the suggestions to
     *
     * @return mixed|void
     */
    public static function getSuggestionPostTypes()
    {
        return get_option('wpil_suggestion_limited_post_types', self::getPostTypes());
    }

    /**
     * Gets the maximum number of words that should go into an anchor.
     * The default is 10
     * 
     * @return int
     */
    public static function getSuggestionMaxAnchorSize(){
        return (int) get_option('wpil_suggestion_anchor_max_size', 10);
    }

    /**
     * Gets the minimum number of words that should go into an anchor.
     * The default is 1 so that single-word target keyword matches can be allowed
     * 
     * @return int
     */
    public static function getSuggestionMinAnchorSize(){
        return (int) get_option('wpil_suggestion_anchor_min_size', 1);
    }

    /**
     * Get merged array of post types and term types
     *
     * @return array
     */
    public static function getAllTypes()
    {
        return array_merge(self::getPostTypes(), self::getTermTypes());
    }

    /**
     * Get selected post statuses
     *
     * @return array
     */
    public static function getPostStatuses()
    {
        return get_option('wpil_2_post_statuses', ['publish']);
    }

    public static function getInternalDomains(){
        $domains = get_transient('wpil_domains_marked_as_internal');
        if(empty($domains) && $domains === false){
            $domains = array();
            $domain_data = get_option('wpil_domains_marked_as_internal');
            $domain_data = explode("\n", $domain_data);
            foreach ($domain_data as $domain) {
                $pieces = wp_parse_url(trim($domain));
                if(!empty($pieces) && isset($pieces['host'])){
                    $domains[] = str_replace('www.', '', $pieces['host']);
                }
            }

            set_transient('wpil_domains_marked_as_internal', $domains, 15 * MINUTE_IN_SECONDS);
        }

        return $domains;
    }

    /**
     * Checks to see if ACF is installed on the site and if the user has disabled ACF processing or not
     * @return bool
     **/
    public static function get_acf_active(){
        if(!class_exists('ACF') || get_option('wpil_disable_acf', false)){
            return false;
        }

        return true;
    }

    /**
     * Gets any ACF fields that the user has specified as the only ones to process.
     * @return array $fields Returns an array if there's fields, and an empty array if there's no fields.
     **/
    public static function getACFFieldsToProcess(){
        $fields = get_transient('wpil_process_these_acf_fields');
        if(empty($fields)){
            $fields = get_option('wpil_process_these_acf_fields', array());

            if(empty($fields)){
                $fields = 'no-fields';
            }else{
                $fields = explode("\n", $fields);
                if(!empty($fields)){
                    $fields = array_filter(array_map('trim', $fields));
                }else{
                    $fields = 'no-fields';
                }
            }

            set_transient('wpil_process_these_acf_fields', $fields, 15 * MINUTE_IN_SECONDS);
        }

        if($fields === 'no-fields'){
            return array();
        }

        return $fields;
    }

    /**
     * Gets any custom content fields that the user has defined on his site and wants to process for content.
     * @return array $fields Returns an array if there's fields, and an empty array if there's no fields.
     **/
    public static function getCustomFieldsToProcess(){
        $fields = get_transient('wpil_custom_fields_to_process');
        if(empty($fields)){
            $fields = get_option('wpil_custom_fields_to_process', array());

            if(empty($fields)){
                $fields = 'no-fields';
            }else{
                $fields = explode("\n", $fields);
                if(!empty($fields)){
                    $fields = array_map('trim', $fields);
                }else{
                    $fields = 'no-fields';
                }
            }

            set_transient('wpil_custom_fields_to_process', $fields, 15 * MINUTE_IN_SECONDS);
        }

        if($fields === 'no-fields'){
            return array();
        }

        return $fields;
    }

    /**
     * Gets any custom content fields that the user has defined on his site and wants to process for content.
     * @return array $fields Returns an array if there's fields, and an empty array if there's no fields.
     **/
    public static function getPostReferenceFields(){
        $fields = get_transient('wpil_acf_post_reference_fields');
        if(empty($fields)){
            $fields = get_option('wpil_acf_post_reference_fields', array());

            if(empty($fields)){
                $fields = 'no-fields';
            }else{
                $fields = explode("\n", $fields);
                if(!empty($fields)){
                    $fields = array_map('trim', $fields);
                }else{
                    $fields = 'no-fields';
                }
            }

            set_transient('wpil_acf_post_reference_fields', $fields, 15 * MINUTE_IN_SECONDS);
        }

        if($fields === 'no-fields'){
            return array();
        }

        return $fields;
    }

    public static function check_if_add_icon_to_link($internal = false){
        $add_icon = (empty($internal)) ? get_option('wpil_add_icon_to_external_link', 'never'): get_option('wpil_add_icon_to_internal_link', 'never');
        switch($add_icon){
            case 'never':
            case 'new_tab':
            case 'always':
                return $add_icon;
            default:
                return 'never';
        }
    }

    public static function get_link_icon($internal = false){
        $icon = (empty($internal)) ? get_option('wpil_external_link_icon', 'new-tab-1'): get_option('wpil_internal_link_icon', 'new-tab-1');
        $icon_names = Wpil_Base::get_svg_icon_names();

        return (in_array($icon, $icon_names, true)) ? $icon: 'new-tab-1';
    }

    public static function get_link_icon_title($internal = false){
        $icon_title = (empty($internal)) ? get_option('wpil_external_link_icon_title', 'Link goes to external site.'): get_option('wpil_internal_link_icon_title', 'Link goes to another page on this site.');
        $icon_title = trim(strip_tags($icon_title));
        return (!empty($icon_title)) ? $icon_title: '';
    }

    public static function get_link_icon_size($internal = false){
        $icon_size = (empty($internal)) ? get_option('wpil_external_link_icon_size', '16'): get_option('wpil_internal_link_icon_size', '16');
        $icon_size = intval($icon_size);
        return (!empty($icon_size)) ? $icon_size: '16';
    }
    
    public static function get_link_icon_color($internal = false){
        $icon_color = (empty($internal)) ? get_option('wpil_external_link_icon_color', '#000000'): get_option('wpil_internal_link_icon_color', '#000000');
        preg_match('/#(?:[A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})/', $icon_color, $color);
        return (isset($color[0]) && !empty($color[0])) ? $color[0]: '#000000';
    }

    /**
     * Gets the list of HTML parent tags whose child anchors won't be getting icons.
     * The tags are parent tags, so if it's something like "<p><a href="example.com">testing</a></p>", and the user chooses "p",
     * the "example" link won't get an icon.
     * 
     */
    public static function get_link_icon_html_exclude_tags($internal = false){
        $parent_icons = (empty($internal)) ? get_option('wpil_external_link_icon_html_exclude', array('button', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6')): get_option('wpil_internal_link_icon_html_exclude', array('button', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'));
        return (!empty($parent_icons)) ? $parent_icons: array();
    }

    /**
     * Gets a list of HTML tags for excluding link icons from.
     */
    public static function get_link_icon_html_ignore_tags(){
        return array(   'p', 'span', 'li', 'div', 'ul', 'ol', 'blockquote', 
                        'td', 'th', 'strong', 'i', 'code', 'button', 'input', 
                        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 
                        'table', 'thead', 'tfoot', 'label', 'center', 'aside');
    }

    /**
     * Gets the list of HTML tags that we should check to make sure a link doesn't have before added a link icon to it.
     * The tags are inner tags, so if it's something like "<p><a href="example.com"><img src="example.com/example.jgp"</a></p>", and the user chooses "img",
     * the link won't get an icon added to it.
     * 
     */
    public static function get_link_icon_inner_html_exclude_tags($internal = false){
        $tags = (empty($internal)) ? get_option('wpil_external_link_icon_inner_html_exclude', array('img', 'svg')): get_option('wpil_internal_link_icon_inner_html_exclude', array('img', 'svg'));
        return (!empty($tags)) ? $tags: array();
    }

    /**
     * Gets the list of posts that the user has ignored from the accessibility icon system
     */
    public static function get_link_icon_ignore_post_list($internal = false){
        $pages = (empty($internal)) ? get_option('wpil_external_link_icon_post_ignore', array()): get_option('wpil_internal_link_icon_post_ignore', array());
        return (!empty($pages)) ? $pages: array();
    }

    /**
     * Gets a list of inner HTML tags for excluding link icons from.
     */
    public static function get_link_icon_internal_html_ignore_tags(){
        return array('img', 'svg', 'i', 'b');
    }

    /**
     * Gets the currently supported languages
     * 
     * @return array
     **/
    public static function getSupportedLanguages(){
        $languages = array(
            'english'       => 'English',
            'spanish'       => 'Español',
            'french'        => 'Français',
            'german'        => 'Deutsch',
            'russian'       => 'Русский',
            'portuguese'    => 'Português',
            'dutch'         => 'Dutch',
            'danish'        => 'Dansk',
            'italian'       => 'Italiano',
            'polish'        => 'Polskie',
            'norwegian'     => 'Norsk bokmål',
            'swedish'       => 'Svenska',
            'slovak'        => 'Slovenčina',
            'arabic'        => 'عربي',
            'serbian'       => 'Српски / srpski',
            'finnish'       => 'Suomi',
            'hebrew'        => 'עִבְרִית',
            'hindi'         => 'हिन्दी',
            'hungarian'     => 'Magyar',
            'romanian'      => 'Română',
            'ukrainian'     => 'Українська',
            'indonesian'    => 'Bahasa Indonesia'
        );
        
        return $languages;
    }

    /**
     * Gets the currently selected language
     * 
     * @return array
     **/
    public static function getSelectedLanguage(){
        return get_option('wpil_selected_language', 'english');
    }

    /**
     * Gets the language for the current processing run.
     * Does a check to see if there's a translation plugin active.
     * If there is, it tries to set the current language to the current post's language.
     * If that's not possible, or there isn't a translation plugin, it defaults to the set language
     **/
    public static function getCurrentLanguage(){

        // if Polylang is active
        if(defined('POLYLANG_VERSION')){
            // see if we're creating suggestions and there's a post
            if( isset($_POST['action']) && ($_POST['action'] === 'get_post_suggestions' || $_POST['action'] === 'update_suggestion_display') &&
                isset($_POST['post_id']) && !empty($_POST['post_id']))
            {
                global $wpdb;
                $post_id = (int) $_POST['post_id'];

                // get the language ids
                $language_ids = $wpdb->get_col("SELECT `term_taxonomy_id` FROM $wpdb->term_taxonomy WHERE `taxonomy` = 'language'");

                // if there are no ids, return the selected language from the settings
                if(empty($language_ids)){
                    return self::getSelectedLanguage();
                }

                $language_ids = implode(', ', $language_ids);

                // check the term_relationships to see if any are applied to the current post
                $tax_id = $wpdb->get_var("SELECT `term_taxonomy_id` FROM $wpdb->term_relationships WHERE `object_id` = {$post_id} AND `term_taxonomy_id` IN ({$language_ids})");

                // if there are no ids, return the selected language from the settings
                if(empty($tax_id)){
                    return self::getSelectedLanguage();
                }

                // query the wp_terms to get the language code for the applied language
                $code = $wpdb->get_var("SELECT `slug` FROM $wpdb->terms WHERE `term_id` = {$tax_id}");

                // if we've gotten the language code, see if we support the language
                if($code){
                    $supported_language_codes = array(
                        'en' => 'english',
                        'es' => 'spanish',
                        'fr' => 'french',
                        'de' => 'german',
                        'ru' => 'russian',
                        'pt' => 'portuguese',
                        'nl' => 'dutch',
                        'da' => 'danish',
                        'it' => 'italian',
                        'pl' => 'polish',
                        'sk' => 'slovak',
                        'nb' => 'norwegian',
                        'sv' => 'swedish',
                        'sd' => 'arabic',
                        'snd' => 'arabic',
                        'sr' => 'serbian',
                        'fi' => 'finnish',
                        'he' => 'hebrew',
                        'hi' => 'hindi',
                        'hu' => 'hungarian',
                        'ro' => 'romanian',
                        'uk' => 'ukrainian',
                        'id' => 'indonesian'
                    );

                    // if we support the language, return it as the active one
                    if(isset($supported_language_codes[$code])){
                        return $supported_language_codes[$code];
                    }
                }
            }
        }

        // if WPML is active
        if(self::wpml_enabled()){
            // see if we're creating suggestions and there's a post
            if( isset($_POST['action']) && ($_POST['action'] === 'get_post_suggestions' || $_POST['action'] === 'update_suggestion_display') &&
            isset($_POST['post_id']) && !empty($_POST['post_id']))
            {
                global $wpdb;
                $post_id = (int) $_POST['post_id'];
                $post_type = get_post_type($post_id);
                $post_type = 'post_' . $post_type;
                $code = $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id = $post_id AND `element_type` = '{$post_type}'");

                if(!empty($code)){

                    $supported_language_codes = array(
                        'en' => 'english',
                        'es' => 'spanish',
                        'fr' => 'french',
                        'de' => 'german',
                        'ru' => 'russian',
                        'pt-br' => 'portuguese',
                        'pt-pt' => 'portuguese',
                        'nl' => 'dutch',
                        'da' => 'danish',
                        'it' => 'italian',
                        'pl' => 'polish',
                        'sk' => 'slovak',
                        'no' => 'norwegian',
                        'sv' => 'swedish',
                        'ar' => 'arabic',
                        'sr' => 'serbian',
                        'fi' => 'finnish',
                        'he' => 'hebrew',
                        'hi' => 'hindi',
                        'hu' => 'hungarian',
                        'ro' => 'romanian',
                        'uk' => 'ukrainian',
                        'id' => 'indonesian'
                    );

                    // if we support the language, return it as the active one
                    if(isset($supported_language_codes[$code])){
                        return $supported_language_codes[$code];
                    }
                }
            }
        }

        return self::getSelectedLanguage();
    }

    public static function getProcessingBatchSize(){
        $batch_size = (int) get_option('wpil_option_suggestion_batch_size', 300);
        if($batch_size < 10){
            $batch_size = 10;
        }
        return $batch_size;
    }

    /**
     * This function is used handle settting page submission
     *
     * @return  void
     */
    public static function save()
    {
        if (isset($_POST['wpil_save_settings_nonce'])
            && wp_verify_nonce($_POST['wpil_save_settings_nonce'], 'wpil_save_settings')
            && isset($_POST['hidden_action'])
            && $_POST['hidden_action'] == 'wpil_save_settings'
        ) {
            // ignore any external caches so they don't get in the way of the option saving
            Wpil_Base::ignore_external_object_cache(true);

            //prepare ignore words to save
            $ignore_words = sanitize_textarea_field(stripslashes(trim(base64_decode($_POST['ignore_words']))));
            $ignore_words = mb_split("\n|\r", $ignore_words);
            $ignore_words = array_unique($ignore_words);
            $ignore_words = array_filter(array_map('trim', $ignore_words));
            sort($ignore_words);
            $ignore_words = implode(PHP_EOL, $ignore_words);

            //update ignore words
            update_option(WPIL_OPTION_IGNORE_WORDS, $ignore_words);

            // set a flag so we know if the user recently activated GSC
            $activated_gsc = false;

            // if the customer has manually selected the active GSC profile
            if( isset($_POST['wpil_manually_select_gsc_profile']) && // only shows once GSC is activated
                !empty($_POST['wpil_manually_select_gsc_profile']))
            {
                // get the GSC setting data
                $setting_data = Wpil_SearchConsole::search_console_data();

                if(isset($setting_data['profiles'])){
                    $setting_data['profiles'] = array_map('sanitize_text_field', $_POST['wpil_manually_select_gsc_profile']);
                    Wpil_SearchConsole::search_console_data($setting_data);
                }
            }

            // save the API tokens if an access key is supplied
            $setting_update_msg = '';
            if( isset($_POST['wpil_gsc_access_code']) && !empty(trim($_POST['wpil_gsc_access_code']))){
                $response = Wpil_SearchConsole::get_access_token(trim($_POST['wpil_gsc_access_code']));
                $setting_update_msg = (!empty($response['access_valid'])) ? '&access_valid=1': '&access_valid=0';
                set_transient('wpil_gsc_access_status_message', $response['message'], 60);

                if(!empty($response['access_valid'])){
                    update_option('wpil_gsc_app_authorized', true);
                    $activated_gsc = true;
                }
            }

            if( isset($_POST['wpil_gsc_custom_app_name']) &&
                isset($_POST['wpil_gsc_custom_client_id']) &&
                isset($_POST['wpil_gsc_custom_client_secret']) &&
                !empty($_POST['wpil_gsc_custom_app_name']) &&
                !empty($_POST['wpil_gsc_custom_client_id']) &&
                !empty($_POST['wpil_gsc_custom_client_secret']))
            {
                $config = array('application_name'  => sanitize_text_field($_POST['wpil_gsc_custom_app_name']), 
                                'client_id'         => sanitize_text_field($_POST['wpil_gsc_custom_client_id']), 
                                'client_secret'     => sanitize_text_field($_POST['wpil_gsc_custom_client_secret']));

                $response = Wpil_SearchConsole::save_custom_auth_config($config);
                $setting_update_msg  = (!empty($response)) ? '&access_valid=1': '&access_valid=0';
                $save_message   = (!empty($response)) ? 'Your Google app credentials have been saved! Please scroll down and authorize the connection to your app.': 'There was an error in saving the app credentials.';
                set_transient('wpil_gsc_access_status_message', $save_message, 60);

                if(!empty($response['access_valid'])){
                    update_option('wpil_gsc_app_authorized', true);
                    $activated_gsc = true;
                }
            }

            if (empty($_POST[WPIL_OPTION_POST_TYPES]))
            {
                $_POST[WPIL_OPTION_POST_TYPES] = [];
            }

            if (empty($_POST['wpil_2_term_types'])) {
                $_POST['wpil_2_term_types'] = [];
            }

            if(empty($_POST['wpil_ignore_tags_from_linking'])){
                $_POST['wpil_ignore_tags_from_linking'] = [];
            }

            if(empty($_POST['wpil_ignore_elementor_from_linking'])){
                $_POST['wpil_ignore_elementor_from_linking'] = [];
            }

            if (empty($_POST['wpil_ignore_linking_roles'])) {
                $_POST['wpil_ignore_linking_roles'] = [];
            }

            // if the customer has selected WP Recipe fields
            if( isset($_POST['wpil_suggestion_wp_recipe_fields']) &&
                !empty($_POST['wpil_suggestion_wp_recipe_fields']))
            {
                // sanitize the array of fields
                $fields = self::simple_textfield_array_sanitizer($_POST['wpil_suggestion_wp_recipe_fields']);
                if(!empty($fields)){
                    update_option('wpil_suggestion_wp_recipe_fields', $fields);
                }
            }

            // if the settings aren't set for showing all post types, remove all but the public ones
            if( empty($_POST['wpil_2_show_all_post_types']) &&
                isset($_POST['wpil_2_post_types']) &&
                !empty($_POST['wpil_2_post_types']))
            {
                $types_available = get_post_types(['public' => true]);
                foreach($_POST['wpil_2_post_types'] as $key => $type){
                    if(!isset($types_available[$type])){
                        unset($_POST['wpil_2_post_types'][$key]);
                    }
                }
            }

            if (empty($_POST['wpil_selected_target_keyword_sources'])) {
                $_POST['wpil_selected_target_keyword_sources'] = [];
            }

            if (empty($_POST['wpil_selected_ai_batch_processes'])) {
                $_POST['wpil_selected_ai_batch_processes'] = [];
            }
            
            if (empty($_POST['wpil_selected_post_content_target_keyword_sources'])) {
                $_POST['wpil_selected_post_content_target_keyword_sources'] = [];
            }

            if (empty($_POST['wpil_related_post_cat_ignore'])) {
                $_POST['wpil_related_post_cat_ignore'] = [];
            }

            if (empty($_POST['wpil_related_post_tag_ignore'])) {
                $_POST['wpil_related_post_tag_ignore'] = [];
            }

            // update the list of known keyword sources
            update_option('wpil_available_target_keyword_sources', Wpil_TargetKeyword::get_available_keyword_sources()); // should mention at_save, but the name would be getting too long

            // if the OAI API key is in the data and it doesn't have the obfuscator text
            if( isset($_POST['wpil_open_ai_api_key']) &&
                !empty($_POST['wpil_open_ai_api_key']) &&
                false === strpos($_POST['wpil_open_ai_api_key'], '***'))
            {
                // update the api key
                update_option('wpil_open_ai_api_key', Wpil_Toolbox::encrypt(trim(sanitize_text_field($_POST['wpil_open_ai_api_key']))));
                // and check if this is a free key
                Wpil_AI::is_free_oai_subscription(true);
            }

            if((int)self::get_use_ai_suggestions() !== (int)$_POST['wpil_use_ai_suggestions']){
                if(!empty((int)$_POST['wpil_use_ai_suggestions'])){
                    Wpil_Telemetry::log_event('activated_ai_powered_suggestions');
                }else{
                    Wpil_Telemetry::log_event('deactivated_ai_powered_suggestions');
                }
            }

            //save other settings
            $opt_keys = self::$keys;
            foreach($opt_keys as $opt_key) {
                if (array_key_exists($opt_key, $_POST)) {
                    update_option($opt_key, $_POST[$opt_key]);
                }
            }

            // make sure GSC is a selected keyword source if the user just activated GSC
            if($activated_gsc){
                $selected_sources = get_option('wpil_selected_target_keyword_sources', array('custom'));
                if(!in_array('gsc', $selected_sources)){
                    $selected_sources = array_merge($selected_sources, array('gsc'));
                    update_option('wpil_selected_target_keyword_sources', $selected_sources);
                }
            }

            // make sure the external data table is created when external linking is activated
            if(array_key_exists('wpil_link_external_sites', $_POST) && !empty($_POST['wpil_link_external_sites'])){
                Wpil_SiteConnector::create_data_table();
            }

            // if the user has checked the option to cancel the active broken link scans
            if(isset($_POST['wpil_clear_error_checker_process']) && !empty($_POST['wpil_clear_error_checker_process'])){
                // run the finishing routine for the link checker
                update_option('wpil_error_reset_run', 0);
                Wpil_Error::mergeIgnoreLinks();
                Wpil_Error::deleteValidLinks();
                update_option('wpil_error_check_links_cron', 1);
                // tell the user that we've cancelled the process
                $setting_update_msg .= '&broken_link_scan_cancelled=1';
                set_transient('wpil_clear_error_checker_message', __('Broken Link scan cancelled!', 'wpil'), 60);
            }

            // if the user has checked the option to create the database tables
            if(isset($_POST['wpil_force_create_database_tables']) && !empty($_POST['wpil_force_create_database_tables'])){
                // run the table create routine
                Wpil_Base::createDatabaseTables();
                // tell the user that we've re-run the process
                $setting_update_msg .= '&database_creation_activated=1';
                set_transient('wpil_database_creation_message', __('Database creation routine complete!', 'wpil'), 60);
            }

            // if the user has checked the option to update the database tables
            if(isset($_POST['wpil_force_database_update']) && !empty($_POST['wpil_force_database_update'])){
                // run the table update routine
                Wpil_Base::updateTables(true);
                // tell the user that we've re-run the process
                $setting_update_msg .= '&database_update_activated=1';
                set_transient('wpil_database_update_message', __('Database update routine complete!', 'wpil'), 60);
            }

            // if the user has checked the option to update the database tables
            if(isset($_POST['wpil_reset_table_display_counts']) && !empty($_POST['wpil_reset_table_display_counts'])){
                // run the table update routine
                Wpil_Report::reset_display_counts();
                // tell the user that we've reset the tables
                $setting_update_msg .= '&table_item_count_reset=1';
                set_transient('wpil_table_item_count_reset_message', __('The Reports have been set to display 20 items!', 'wpil'), 60);
            }

            // if the user has checked the option to reset the AI setting cache
            if(isset($_POST['wpil_reset_ai_setting_cache']) && !empty($_POST['wpil_reset_ai_setting_cache'])){
                // clear the model settings
                delete_transient('wpil_ai_listed_models');
                // clear the live status processing
                delete_transient('wpil_doing_ai_data_download');
                // clear the delay on the batch processing
                delete_transient('wpil_oai_batch_process_delay');
            }

            // if the user has chosen to delete all stored user data
            if(isset($_POST['wpil_delete_stored_visitor_data']) && !empty($_POST['wpil_delete_stored_visitor_data'])){
                // delete the data
                Wpil_ClickTracker::delete_stored_visitor_data();
                // check for any stored data that wasn't deleted
                $erased = !Wpil_ClickTracker::check_for_stored_visitor_data();
                // and tell the user about the status
                $setting_update_msg .= '&user_data_deleted=';
                $setting_update_msg .= ($erased) ? '1': '0';
                if($erased){
                    set_transient('wpil_user_data_delete_message', __('Stored user data deleted!', 'wpil'), 60);
                }else{
                    set_transient('wpil_user_data_delete_message', __('All of the stored user data couldn\'t be deleted. Please try again.', 'wpil'), 60);
                }
            }

            // clear the item caches if they're set
            $setting_caches = array(
                'wpil_ignore_links',
                'wpil_ignore_sitemap_posts',
                'wpil_ignore_external_links',
                'wpil_ignore_keywords_posts',
                'wpil_ignore_keywords_posts_by_category',
                'wpil_ignore_categories',
                'wpil_domains_marked_as_internal',
                'wpil_links_to_ignore',
                'wpil_broken_links_to_ignore',
                'wpil_ignore_elements_by_class',
                'wpil_ignore_shortcodes_by_name',
                'wpil_ignore_linking_roles',
                'wpil_ignore_pages_completely',
                'wpil_suggest_to_outbound_posts',
                'wpil_ignore_acf_fields',
                'wpil_ignore_click_links',
                'wpil_sponsored_domains',
                'wpil_nofollow_domains',
                'wpil_dofollow_domains',
                'wpil_custom_fields_to_process',
                'wpil_acf_post_reference_fields',
                'wpil_process_these_acf_fields',
                'wpil_applied_link_attributes',
                'wpil_redirected_post_ids',
                'wpil_redirected_post_urls',
                'wpil_related_post_settings'
            );

            foreach($setting_caches as $cache){
                delete_transient($cache);
            }

            // set the tab that was last open
            if(isset($_POST['wpil_setting_selected_tab']) && !empty($_POST['wpil_setting_selected_tab'])){
                $setting_update_msg .= '&tab=' . sanitize_text_field($_POST['wpil_setting_selected_tab']);
            }else{
                $setting_update_msg .= '&tab=general-settings';
            }

            // flush the cache to make sure nothing's hanging
            wp_cache_flush();

            wp_redirect(admin_url('admin.php?page=link_whisper_settings&success' . $setting_update_msg));
            exit;
        }
    }

    public static function getSkipSectionType()
    {
        return get_option('wpil_skip_section_type', 'sentences');
    }

    public static function getSkipSentences()
    {
        return get_option('wpil_skip_sentences', 3);
    }

    /**
     * Gets the max number of suggestions that will be shown at once in the suggestion panel.
     * @return int
     **/
    public static function get_max_suggestion_count(){
        return (int) get_option('wpil_max_suggestion_count', 0);
    }

    /**
     * Checks to see if the site has a translation plugin active
     * 
     * @return bool
     **/
    public static function translation_enabled(){
        if(defined('POLYLANG_VERSION')){
            return true;
        }elseif(self::wpml_enabled()){
            return true;
        }

        return false;
    }

    /**
     * Check if WPML installed and has at least 2 languages
     *
     * @return bool
     */
    public static function wpml_enabled()
    {
        global $wpdb;

        // if WPML is activated
        if(function_exists('icl_object_id') || class_exists('SitePress')){
            $languages_count = 1;
            $table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}icl_languages'");
            if ($table == $wpdb->prefix . 'icl_languages') {
                $languages_count = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}icl_languages WHERE active = 1");
            } else {
                $languages_count = $wpdb->get_var("SELECT count(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'language'");
            }

            if (!empty($languages_count) && $languages_count > 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the list of WPML supported locales
     **/
    public static function get_wpml_locales(){
        $locales = array();

        if(function_exists('icl_get_languages_locales')){
            $locales = icl_get_languages_locales();
        }

        return $locales;
    }

    /**
     * Checks if the given local is one supported by WPML
     **/
    public static function is_supported_wpml_local($local = ''){
        if(empty($local)){
            return false;
        }

        $locales = self::get_wpml_locales();
        if(!empty($locales) && isset($locales[$local])){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Get checked term types
     *
     * @return array
     */
    public static function getTermTypes()
    {
        return get_option('wpil_2_term_types', []);
    }

    /**
     * Get ignore posts (posts & terms)
     * Pulls posts from cache if available to save processing time.
     *
     * @return array
     */
    public static function getIgnorePosts()
    {
        $posts = get_transient('wpil_ignore_links');
        if(empty($posts)){
            $posts = [];
            $links = get_option('wpil_ignore_links');
            $links = explode("\n", $links);
            foreach ($links as $link) {
                $link = trim($link);
                if(empty($link)){
                    continue;
                }

                $post = Wpil_Post::getPostByLink($link);
                if (!empty($post)) {
                    $posts[] = $post->type . '_' . $post->id;
                }
            }

            set_transient('wpil_ignore_links', $posts, 15 * MINUTE_IN_SECONDS);
        }

        return $posts;
    }

    /**
     * Get ignore posts from the externally linked sites
     * Pulls posts from cache if available to save processing time.
     *
     * @return array
     */
    public static function getIgnoreExternalPosts()
    {
        global $wpdb;

        $posts = get_transient('wpil_ignore_external_links');
        if(empty($posts)){
            $posts = [];
            $links = get_option('wpil_ignore_links');
            $links = explode("\n", $links);
            $linked_domains = array_filter(array_map(function($site_url){ return wp_parse_url(trim($site_url), PHP_URL_HOST);}, Wpil_SiteConnector::get_linked_sites()));
            $query_links = array();
            foreach ($links as $link) {
                // if the ignored link is one that goes to an external site, add it the list to query for
                if(in_array(wp_parse_url(trim($link), PHP_URL_HOST), $linked_domains, true)){
                    $query_links[] = trim($link);
                }
            }

            if(!empty($query_links)){
                $query_links = implode('\', \'', $query_links);
                $external_posts = $wpdb->get_results("SELECT `post_id`, `type` FROM {$wpdb->prefix}wpil_site_linking_data WHERE `post_url` IN ('{$query_links}')");
                if(!empty($external_posts)){
                    foreach($external_posts as $post){
                        $posts[] = $post->type . '_' . $post->post_id;
                    }
                }
            }

            if(empty($posts)){
                $posts = 'no-posts';
            }

            set_transient('wpil_ignore_external_links', $posts, 15 * MINUTE_IN_SECONDS);
        }

        // if there are no posts
        if($posts === 'no-posts'){
            // return an empty array
            $posts = array();
        }

        return $posts;
    }

    /**
     * Get ignore posts
     *
     * @return array
     */
    public static function getIgnoreKeywordsPosts()
    {
        $posts = get_transient('wpil_ignore_keywords_posts');
        if(empty($posts)){
            $posts = [];
            $links = get_option('wpil_ignore_keywords_posts');
            $links = explode("\n", $links);
            foreach ($links as $link) {
                $link = trim($link);
                if(empty($link)){
                    continue;
                }

                $post = Wpil_Post::getPostByLink($link);
                if (!empty($post)) {
                    $posts[] = $post->type . '_' . $post->id;
                }
            }

            $ignored_categories = explode("\n", get_option('wpil_ignore_keywords_posts_by_category', ''));
            if(!empty($ignored_categories)){
                foreach($ignored_categories as $cat_link){
                    $category = Wpil_Post::getPostByLink(trim($cat_link));
                    if (!empty($category)) {
                        $found = Wpil_Post::getCategoryPosts($category->id);
                        foreach($found as $id){
                            $posts[] = 'post_' . $id;
                        }
                    }
                }
            }

            $completely_ignored = self::get_completely_ignored_pages();
            if(!empty($completely_ignored)){
                $posts = array_merge($posts, $completely_ignored);
            }

            // if we have posts
            if(!empty($posts)){
                // remove any duplicate entries
                $posts = array_values(array_flip(array_flip($posts)));
            }

            set_transient('wpil_ignore_keywords_posts', $posts, 15 * MINUTE_IN_SECONDS);
        }

        return $posts;
    }

    /**
     * Get ignored orphaned posts
     * Used in the link report page
     *
     * @return array
     */
    public static function getIgnoreOrphanedPosts()
    {
        $posts = [];
        $links = get_option('wpil_ignore_orphaned_posts');
        $links = explode("\n", $links);
        foreach ($links as $link) {
            $link = trim($link);
            if(empty($link)){
                continue;
            }

            $post = Wpil_Post::getPostByLink($link);
            if (!empty($post)) {
                $posts[] = $post->type . '_' . $post->id;
            }
        }

        $ignored_categories = explode("\n", get_option('wpil_ignore_orphaned_posts_by_category', ''));
        if(!empty($ignored_categories)){
            foreach($ignored_categories as $cat_link){
                $category = Wpil_Post::getPostByLink(trim($cat_link));
                if (!empty($category)) {
                    $found = Wpil_Post::getCategoryPosts($category->id);
                    foreach($found as $id){
                        $posts[] = 'post_' . $id;
                    }
                }
            }
        }

        $completely_ignored = self::get_completely_ignored_pages();
        if(!empty($completely_ignored)){
            $posts = array_merge($posts, $completely_ignored);
        }

        // if we have posts
        if(!empty($posts)){
            // remove any duplicate entries
            $posts = array_values(array_flip(array_flip($posts)));
        }

        return $posts;
    }

    /**
     * Get categories list to be ignored
     *
     * @return array
     */
    public static function getIgnoreCategoriesPosts()
    {
        $posts = get_transient('wpil_ignore_categories');
        if(empty($posts)){
            $posts = [];
            $links = get_option('wpil_ignore_categories', '');
            $links = explode("\n", $links);
            foreach ($links as $link) {
                $category = Wpil_Post::getPostByLink(trim($link));
                if (!empty($category)) {
                    $posts = array_merge($posts, Wpil_Post::getCategoryPosts($category->id));
                }
            }
            $posts = array_values(array_flip(array_flip($posts)));

            set_transient('wpil_ignore_categories', $posts, 15 * MINUTE_IN_SECONDS);
        }

        return $posts;
    }

    /**
     * Gets the ids of all the posts and categories that have been ignored from the suggestion process.
     * So it counts BOTH the posts that have been ignored directly, and the ones that have been ignored by category.
     * Also loops in the pages that have been completely ignored.
     **/
    public static function getAllIgnoredPosts(){
        $posts = array();

        $ignored_posts = self::getIgnorePosts();
        if(!empty($ignored_posts)){
            $posts = array_merge($posts, $ignored_posts);
        }

        $ignored_posts = self::getIgnoreCategoriesPosts();
        if(!empty($ignored_posts)){
            foreach($ignored_posts as $id){
                $posts[] = 'post_' . $id;
            }
        }

        $completely_ignored = self::get_completely_ignored_pages();
        if(!empty($completely_ignored)){
            $posts = array_merge($posts, $completely_ignored);
        }

        if(!empty($posts)){
            $posts = array_values(array_flip(array_flip($posts)));
        }

        return $posts;
    }

    /**
     * Get if the ignored posts aren't supposed to be shown or referenced on the Report pages
     * @return bool
     **/
    public static function hideIgnoredPosts(){
        // check if the hide setting has been set from the Settings page
        if(!empty(get_option('wpil_dont_show_ignored_posts', false))){
            return true;
        }

        // get if the specific user want's to hide the posts
        $options = get_user_meta(get_current_user_id(), 'report_options', true);
        $hide_ignored = (isset($options['hide_ignore'])) ? ( ($options['hide_ignore'] == 'off') ? false : true) : false;

        return $hide_ignored;
    }

    /**
     * Gets all available cats and tags for the current post types
     **/
    public static function get_available_related_post_terms(){
        $terms = get_transient('wpil_available_related_post_terms');

        if($terms === '' || $terms === false){
            $post_types = self::get_related_posts_active_post_types();

            if(!empty($post_types)){
                $taxes = get_object_taxonomies($post_types);
                if(!empty($taxes)){

                }
            }
        }

        return $terms;
    }

    /**
     * Get ignore posts (posts & terms)
     * Pulls posts from cache if available to save processing time.
     *
     * @return array
     */
    public static function getIgnoreSitemapPosts()
    {
        $posts = get_transient('wpil_ignore_sitemap_posts');
        if(empty($posts)){
            $posts = [];
            $links = get_option('wpil_ignore_sitemap_posts');
            $links = explode("\n", $links);
            foreach ($links as $link) {
                $link = trim($link);
                if(empty($link)){
                    continue;
                }

                $post = Wpil_Post::getPostByLink($link);
                if (!empty($post)) {
                    $posts[] = $post->type . '_' . $post->id;
                }
            }

            set_transient('wpil_ignore_sitemap_posts', $posts, 15 * MINUTE_IN_SECONDS);
        }

        return $posts;
    }

    /**
     * Gets the 
     **/

    /**
     * Gets an array of post ids to affirmatively make outbound links to.
     *
     * @return array
     */
    public static function getOutboundSuggestionPostIds()
    {
        $posts = get_transient('wpil_suggest_to_outbound_posts');
        if(empty($posts)){
            $posts = [];
            $links = get_option('wpil_suggest_to_outbound_posts', '');
            $links = explode("\n", $links);
            foreach ($links as $link) {
                $post = Wpil_Post::getPostByLink($link);
                if (!empty($post)) {
                    $posts[] = $post->type . '_' . $post->id;
                }
            }

            if(empty($posts)){
                $posts = 'no-posts';
            }

            set_transient('wpil_suggest_to_outbound_posts', $posts, 15 * MINUTE_IN_SECONDS);
        }

        // if there are no posts
        if($posts === 'no-posts'){
            // return an empty array
            $posts = array();
        }

        return $posts;
    }

    /**
     * Gets an array of type specific ids from the url input settings.
     */
    public static function getItemTypeIds($ids = array(), $type = 'post'){
        $data = array('post' => array(), 'term' => array());

        foreach($ids as $id){
            $dat = explode('_', $id);
            if(isset($dat[0]) && !empty($dat[0]) && isset($dat[1]) && !empty($dat[1])){
                $data[$dat[0]][] = $dat[1];
            }
        }

        if(isset($data[$type])){
            return $data[$type];
        }else{
            return $data;
        }
    }

    //Check if need to show ALL links
    public static function showAllLinks()
    {
        return !empty(get_option('wpil_show_all_links'));
    }

    /**
     * Gets if the user wants to count links from related post plugins in the Links Report.
     * Returns false if the user has opted to show all links because that includes related post links already.
     **/
    public static function get_related_post_links()
    {
        return (!empty(get_option('wpil_count_related_post_links', false)) && !self::showAllLinks());
    }

    /**
     * Gets if the user wants to ignore links from latest post blocks/widgets in the Links Report.
     **/
    public static function ignore_latest_post_links()
    {
        return !empty(get_option('wpil_ignore_latest_posts', false));
    }

    /**
     * Gets if the user wants to run a special link update process when Gutenberg Reusable Blocks are Updated
     **/
    public static function update_reusable_block_links()
    {
        return !empty(get_option('wpil_update_reusable_block_links', false));
    }

    /**
     * Gets if the user wants to show comment links in the Links Report.
     * Returns false if the user has opted to show all links because that includes comments already.
     **/
    public static function getCommentLinks()
    {
        return (!empty(get_option('wpil_show_comment_links')) && !self::showAllLinks());
    }

    /**
     * Gets the current content formatting level when pulling links from content
     **/
    public static function getContentFormattingLevel()
    {
        // if the user has programattically disabled formatting, return zero
        if(apply_filters('wpil_disable_content_link_formatting', false)){
            return 0;
        }

        return (int) get_option('wpil_content_formatting_level', 2);
    }

    /**
     * Gets if the user wants to override the global $post varible during link scans with a new one that matches the content currently being scanned.
     * Mostly it's a compatibility setting for shortcodes that rely on the global $post variable to determine what to display
     **/
    public static function overrideGlobalPost()
    {
        return !empty(get_option('wpil_override_global_post_during_scan', false));
    }

    /**
     * Gets if the user wants to optimize the link scan for speed at the cost of error handling
     **/
    public static function optimize_link_scan_for_speed()
    {
        return !empty(get_option('wpil_optimize_link_scan_for_speed', false));
    }

    /**
     * Gets if the user wants to use the link data stored in the link table instead of the post meta
     **/
    public static function use_link_table_for_data()
    {
        return !empty(get_option('wpil_use_link_data_table', false));
    }

    /**
     * Gets a list of HTML tags that the user can choose to ignore from linking
     */
    public static function getPossibleIgnoreLinkingTags(){
        return array('p', 'span', 'li', 'div', 'ul', 'ol', 'blockquote', 'td', 'th', 'strong', 'i', 'code');
    }

    /**
     * 
     */
    public static function getIgnoreLinkingTags(){
        $tags = get_option('wpil_ignore_tags_from_linking', array());
        $tag_list = self::getPossibleIgnoreLinkingTags();
        $return_tags = array();

        if(!empty($tags) && is_array($tags)){
            foreach($tags as $tag){
                // if the tag is in the list of preapproved tags
                if(in_array($tag, $tag_list, true)){
                    // add it to the return list
                    $return_tags[] = $tag;
                }
            }
        }

        return $return_tags;
    }

    /**
     * Gets a list of Elementor modules that we could ignore if the user wants to
     */
    public static function getPossibleIgnoreElementorModules(){
        return Wpil_Editor_Elementor::getSupportedModules();
    }

    /**
     * Gets the list of Elementor modules that the user does want to ignore
     */
    public static function getIgnoreLinkingElementorModules(){
        $modules = get_option('wpil_ignore_elementor_from_linking', array());
        if(!empty($modules)){
            foreach($modules as $key => $module){
                $modules[$key] = trim($module);
            }
        }
        return $modules;
    }

    /** 
     * Gets the OpenAI API key that the user entered in the Settings.
     * Has optional obfuscation that hides all but the last four digites of the key
     **/
    public static function getOpenAIKey($obfuscate = false){
        $key = get_option('wpil_open_ai_api_key', '');
        if(empty($key)){
            return '';
        }

        $key = Wpil_Toolbox::decrypt($key);

        if(0 !== strpos(trim($key), 'sk-')){
            update_option('wpil_open_ai_key_decoding_error', '1');
        }

        if($obfuscate){
            $key = '**************************' . substr($key, -4);
        }

        return (!empty($key)) ? trim($key): '';
    }

    /** 
     * Gets the OpenAI API key that the user entered in the Settings.
     **/
    public static function getChatGPTVersion($key = ''){
        $defaults = array(
            'suggestion-scoring' => 'gpt-4o-mini',
            'post-summarizing' => 'gpt-4o',
            'product-detecting' => 'gpt-4o-mini',
            'keyword-detecting' => 'gpt-4o-mini',
        );

        $available_models = Wpil_AI::get_available_models();

        $standard = array(
            'create-post-embeddings' => 'text-embedding-3-large',
            'assess-sentence-anchors' => (isset($available_models['gpt-4o-mini']) ? 'gpt-4o-mini': 'gpt-3.5-turbo')
        );

        $data = get_option('wpil_chat_gpt_api', $defaults);

        $data = array_merge($data, $standard);

        if(empty($key)){
            return !empty($data) ? $data: $defaults;
        }

        if(isset($data[$key])){
            return trim($data[$key]);
        }

        return 'gpt-3.5-turbo';
    }

    public static function get_ai_suggestion_score_active(){
        return false;
        return get_option('wpil_ai_suggestion_score_active', false);
    }

    /**
     * Gets if the user has turned on the AI batch processing
     **/
    public static function get_ai_batch_processing_active(){
        return !empty(get_option('wpil_enable_ai_batch_processing', false));
    }

    /**
     * Gets the post relatedness threshold
     **/
    public static function get_ai_sitemap_relatedness_threshold(){
        return floatval(get_option('wpil_sitemap_embedding_relatedness_threshold', 0.8500));
    }

    
    public static function get_available_ai_batch_processes(){
        // return a list of the available batch processes, indexed to their process code
        return array(
            4 => 'create-post-embeddings',
//            2 => 'post-summarizing', // todo: re-enable later
            3 => 'product-detecting',
            5 => 'keyword-detecting'
        );
    }

    public static function get_selected_ai_batch_processes($return_process_names = false, $remove_embedding = false){
        $processes = self::get_available_ai_batch_processes();
        $selected_processes = get_option('wpil_selected_ai_batch_processes', $processes);
        $available = array_intersect($selected_processes, array_flip($processes));

        if($remove_embedding && in_array(4, $available)){
            $key = array_search(4, $available);
            unset($available[$key]);
        }

        return ($return_process_names) ? array_map(function($id){ return Wpil_AI::get_process_name_from_code($id); }, $available): $available;
    }

    /**
     * Creates a list of the supported AI batch names for display
     **/
    public static function get_ai_batch_name_list(){
        $names = array(
            'post-summarizing'          => __('Post Summarization', 'wpil'),
            'product-detecting'         => __('Product Detection', 'wpil'),
            'create-post-embeddings'    => __('AI Relation Analysis', 'wpil'),
            'keyword-detecting'         => __('Keyword Analysis', 'wpil'),
        );

        return $names;
    }

    /**
     * Gets the max number of keywords that we're going to ask ChatGPT for
     **/
    public static function get_ai_keyword_count_max(){
        $count = intval(get_option('wpil_ai_generated_keyword_max_count', 30));
        return (!empty($count)) ? $count: 30;
    }
    
    /**
     * Sets the number of dimensions to use in embeddings
     **/
    public static function get_ai_embedding_dimensions(){
        $dimensions = get_option('wpil_ai_embedding_dimension_count', 3072);
    }

    /**
     * Gets the max post per batch limits for the AI processes.
     * Handles both Live and Batch download limits
     **/
    public static function get_ai_batch_limits(){
        $defaults = array(
            'live' => array(
                'post-summarizing'          => 100,
                'product-detecting'         => 100,
                'create-post-embeddings'    => 500,
                'keyword-detecting'         => 100
            ),
            'batch' => array(
                'post-summarizing'          => 100,
                'product-detecting'         => 100,
                'create-post-embeddings'    => 500,
                'keyword-detecting'         => 100,
            )
        );
        
        $limits = get_option('wpil_ai_batch_processing_limits', array());

        if(isset($limits['live'])){
            $defaults['live'] = array_merge($defaults['live'], $limits['live']);
        }

        if(isset($limits['batch'])){
            $defaults['batch'] = array_merge($defaults['batch'], $limits['batch']);
        }

        return $defaults;
    }

    /**
     * Gets the processing limit for a specific AI process
     **/
    public static function get_ai_process_limit($process = '', $live = false){
        $limits = self::get_ai_batch_limits();
        $limit = 100;

        if($live){
            if(isset($limits['live'], $limits['live'][$process]) && !empty($limits['live'][$process])){
                $limit = (int) $limits['live'][$process];
            }
        }else{
            if(isset($limits['batch'], $limits['batch'][$process]) && !empty($limits['batch'][$process])){
                $limit = (int) $limits['batch'][$process];
            }
        }

        return $limit;
    }

    /**
     * Gets if the user wants to use AI to power the suggestions
     **/
    public static function get_use_ai_suggestions(){
        return (!empty(get_option('wpil_use_ai_suggestions', false)));
    }

    /**
     * Gets if the user wants to use AI to power the suggestions
     **/
    public static function get_disable_ai_anchor_building(){
        return (!empty(get_option('wpil_disable_ai_anchor_building', false)));
    }
    
    /**
     * Gets if the user wants to only see the top "AI" suggestions
     **/
    public static function get_show_top_ai_suggestions(){
        return (!empty(get_option('wpil_restrict_to_top_ai_suggestions', true)));
    }

    /**
     * Checks to make sure that it's possible to do AI Powered Suggestions
     **/
    public static function can_do_ai_powered_suggestions(){
        // currently just checking to make sure taht there's at least 10% of posts processed
        if(!empty(self::getOpenAIKey()) && Wpil_AI::get_batch_status_completion_percent('calculated-post-embeddings') > 10){
            return true;
        }

        return false;
    }

    public static function ajax_set_ai_suggestions_use(){
        Wpil_Base::verify_nonce('ai-suggestion-change-nonce');

        if(!isset($_POST['status'])){
            wp_send_json(array('status' => 'no status!'));
        }

        $status = (int) !empty($_POST['status']);

        if(!empty($status)){
            Wpil_Telemetry::log_event('activated_ai_powered_suggestions');
        }else{
            Wpil_Telemetry::log_event('deactivated_ai_powered_suggestions');
        }

        update_option('wpil_use_ai_suggestions', $status);
        wp_send_json(array('status' => 'updated!'));
    }

    /**
     * Gets if the user wants to update the Post Modified date when links are inserted.
     * Returns false by default, and only true if the user has activated the setting.
     **/
    public static function updatePostModifiedDate()
    {
        return (!empty(get_option('wpil_update_post_edit_date', false)));
    }

    /**
     * Gets if the user wants to prevent suggestions being made for posts marked as "noindex"
     **/
    public static function removeNoindexFromSuggestions()
    {
        return (!empty(get_option('wpil_remove_noindex_post_suggestions', false)));
    }

    /**
     * Gets if the user wants to force all LW created links to be in HTTPS.
     * Returns false by default, and only true if the user has activated the setting.
     **/
    public static function forceHTTPS()
    {
        return (!empty(get_option('wpil_force_https_links', false)));
    }

    /**
     * Gets if the user wants to use "Ugly" permalinks in the reports.
     * It turns out that calculating the "Pretty" permalinks in the reports can take a TON of time.
     * Using the ugly ones hardly takes any time at all
     **/
    public static function use_ugly_permalinks()
    {
        return (!empty(get_option('wpil_use_ugly_permalinks', false)));
    }

    /**
     * Gets if the user wants to keep the autolinking from inserting links when posts are saved/updated
     **/
    public static function disable_autolink_on_post_save()
    {
        return (!empty(get_option('wpil_disable_autolinking_on_post_update', false)));
    }

    /**
     * Gets if the user has activated the autolinking cron task
     **/
    public static function autolinking_cron_task_enabled()
    {
        return (!empty(get_option('wpil_enable_autolink_cron_task', false)));
    }

    /**
     * Gets if the user has disabled the autolinking insert run
     **/
    public static function autolinking_insert_run_disabled()
    {
        return (!empty(get_option('wpil_disable_autolink_insert_run', false)));
    }

    /**
     * Gets if the user wants to try clearing the CDN cache after link deletes
     * Returns false by default, and only true if the user has activated the setting.
     **/
    public static function clear_cdn()
    {
        return (!empty(get_option('wpil_clear_cdn_link_delete', false)));
    }
    
    /**
     * Gets if the user wants to try flushing the object cache after unspecified actions
     * Returns false by default, and only true if the user has activated the setting.
     **/
    public static function flush_object_cache()
    {
        return (!empty(get_option('wpil_object_cache_flush', false)));
    }

    /**
     * Gets if the user wants to try updating the post after certain actions. (Viz, link deletes)
     * Returns false by default, and only true if the user has activated the setting.
     * @return bool
     **/
    public static function update_post_after_actions()
    {
        return (!empty(get_option('wpil_update_post_after_action', false)));
    }

    /**
     * Gets if the user wants to try optimizing the options table.
     * Returns false by default, and only true if the user has activated the setting.
     * @return bool
     **/
    public static function get_if_options_should_optimize()
    {
        return (!empty(get_option('wpil_optimize_option_table', false)));
    }

    /**
     * Gets if the user wants to make suggestion matches based on some of the words in the post title.
     **/
    public static function matchPartialTitles()
    {
        return (!empty(get_option('wpil_get_partial_titles', false)));
    }

    /**
     * Checks to see if the user has saved auth credentials on the site and has gotten authed in the past
     * @return bool
     **/
    public static function HasGSCCredentials(){
        $credentials = get_option('wpil_search_console_data');
        return (!empty($credentials) && isset($credentials['authorized']) && $credentials['authorized'] != false && isset($credentials['access_token']) && !empty($credentials['access_token']));
    }

    /**
     * Gets the configuration data for the GSC integration.
     * Was formerly in the GSC class, but instantiating the class would trigger a call to Google.
     * If the site wasn't connected, this would be unnecessary and would result in a 401 error.
     * @return array
     **/
    public static function getGSCConfiguration(){
        // get the auth method
        $method = get_option('wpil_gsc_auth_method', 'standard');

        switch($method){
            case 'standard':
                $credentials = self::get_credentials();

                $state_args = array('rest' => get_rest_url(null, '/' . Wpil_Rest::REST_SLUG . '/' . Wpil_Rest::GSC_ROUTE));

                if(is_ssl() && current_user_can('activate_plugins')){
                    $user = wp_get_current_user();
                    if(!empty($user) && isset($user->data, $user->data->user_login)){
                        $state_args['check'] = base64_encode($user->data->user_login . ':' . Wpil_Toolbox::create_application_password_for_user($user->ID, 'link-whisper-gsc'));
                    }
                }

                $state = base64_encode(json_encode($state_args));

                $config = [
                    'application_name'  => 'Link Whisper',
                    'redirect_uri'      => WPIL_STORE_URL . '/wp-json/link-whisper/auth',
                    'scopes'            => [ 'https://www.googleapis.com/auth/webmasters.readonly' ],
                    'access_type'       => 'offline',
                    'state'             => $state,
                    'prompt'            => 'consent',
                ];

                $config = array_merge($config, $credentials);

            break;
            case 'custom_auth':
                $config = get_option('wpil_gsc_custom_config', array());
                if(!empty($config)){
                    $config['redirect_uri'] = 'urn:ietf:wg:oauth:2.0:oob';
                    $config['scopes']       = array('https://www.googleapis.com/auth/webmasters.readonly');
                }
            break;
            case 'legacy_api':
                // todo fill out
            break;
        }

        // todo handle empty config further down the line
        return $config;
    }

    public static function get_credentials ()
    {
        $credentials = get_option('wpil_gsc_remote_credentials', array());
        if(empty($credentials)){
            return self::get_remote_gsc_credentials();
        }else{
            $credentials = Wpil_Toolbox::deep_decrypt($credentials);
            
            // if the credentials don't have a valid client_id (probs because the salt/key has changed)
            if(!isset($credentials['client_id']) && !empty(Wpil_Toolbox::get_key()) && !empty(Wpil_Toolbox::get_salt())){
                // try getting some new ones and return the results of the attempt
                return self::get_remote_gsc_credentials();
            }

            return $credentials;
        }
        return [];
    }

    /**
     * Gets the GSC credentials from the proxy server and stores them in an option if they're available.
     **/
    private static function get_remote_gsc_credentials(){
        $response = wp_remote_get(WPIL_STORE_URL . '/wp-json/link-whisper/credentials', [
            'body' => [
                'name' => WPIL_PLUGIN_NAME
            ]
        ]);

        if ( !is_wp_error($response) && !empty($response = json_decode($response['body'], true)) ) {
            if ( isset($response['credentials']) ) {
                update_option('wpil_gsc_remote_credentials', Wpil_Toolbox::deep_encrypt($response['credentials']));
                return $response['credentials'];
            }
        }

        // if there's no creds, return an array
        return array();
    }

    /**
     * Gets the authentication URL for the GSC connection.
     * Was formerly in the GSC class, but instantiating the class would trigger a call to Google.
     * If the site wasn't connected, this would be unnecessary and would result in a 401 error.
     * @return string
     **/
    public static function getGSCAuthUrl(){
        $config = self::getGSCConfiguration();

        $args = array(
            [
                'response_type'    => 'code',
                'client_id'        => $config['client_id'],
                'redirect_uri'     => $config['redirect_uri'],
                'scope'            => implode(' ', $config['scopes']),
                'state'            => $config['state'],
                'access_type'      => $config['access_type'],
                'prompt'           => $config['prompt'],
            ]
        );

        $url = add_query_arg($args, 'https://accounts.google.com/o/oauth2/v2/auth');

        return esc_url_raw($url);
    }

    /**
     * Gets if the user wants to automatically select a number of GSC keywords as Target Keywords.
     * @return int
     **/
    public static function get_if_autotag_gsc_keywords(){
        return (int) get_option('wpil_autotag_gsc_keywords', 1);
    }

    /**
     * Gets the basis that the user wants to autotag the keywords on.
     * @return string
     **/
    public static function get_autotag_gsc_keyword_basis(){
        return ('impressions' === get_option('wpil_autotag_gsc_keyword_basis', 'impressions')) ? 'impressions': 'clicks';
    }

    /**
     * Gets the number of GSC keywords to automatically select as Target Keywords.
     * Default is 10 keywords
     * @return int
     **/
    public static function get_autotag_gsc_keyword_count(){
        return (int) get_option('wpil_autotag_gsc_keyword_count', 10);
    }

    /**
     * Gets the target keyword sources the user has selected from the settings.
     * Automatically includes new keyword sources if the user hasn't saved them
     **/
    public static function getSelectedKeywordSources()
    {
        $kw_sources_known_at_save = get_option('wpil_available_target_keyword_sources', array());
        $kw_sources = Wpil_TargetKeyword::get_available_keyword_sources();
        $diffed_kw_sources = array_diff($kw_sources, $kw_sources_known_at_save);
        $selected_sources = get_option('wpil_selected_target_keyword_sources', $kw_sources);
        return array_merge($selected_sources, $diffed_kw_sources, array('custom'));
    }

    /**
     * Gets the target keyword sources the user has selected from the settings.
     * Automatically includes new keyword sources if the user hasn't saved them
     **/
    public static function get_selected_post_content_keyword_sources()
    {
        return get_option('wpil_selected_post_content_target_keyword_sources', Wpil_TargetKeyword::get_available_post_content_keyword_sources());
    }

    /**
     * Gets if links should have any HTML tags in their anchor texts removed when they are deleted.
     **/
    public static function delete_link_inner_html(){
        return !empty(get_option('wpil_delete_link_inner_html', false));
    }

    /**
     * Gets if the Inbound Internal links pointing to a specific post should be deleted when that post is deleted
     **/
    public static function delete_inbound_internal_on_post_delete(){
        return !empty(get_option('wpil_delete_links_to_post_on_delete', false));
    }

    /**
     * Check if need to show full HTML in suggestions
     *
     * @return bool
     */
    public static function fullHTMLSuggestions()
    {
        return !empty(get_option('wpil_full_html_suggestions'));
    }

    /**
     * Checks to see if the user has disabled post updating on follow-up actions.
     * Things like the URL Changer's update_post call after the changing code
     **/
    public static function disable_followup_post_updating(){
        return apply_filters('wpil_disable_url_changer_update', false);
    }

    /**
     * Gets any active suggestion filter based on requested index
     * @param string $index The $_REQUEST or stored data index to search for
     * @return bool|array
     */
    public static function get_suggestion_filter($index = ''){
        if(empty($index)){
            return false;
        }

        $filters_persistent = !empty(get_option('wpil_make_suggestion_filtering_persistent', false));
        $filtering_settings = ($filters_persistent) ? get_user_meta(get_current_user_id(), 'wpil_persistent_filter_settings', true) : false;

        $status = false;
        switch ($index) {
            // bool filters
            case 'same_category':
            case 'same_tag':
            case 'select_post_types':
            case 'link_orphaned':
            case 'same_parent':
                if($filters_persistent){
                    $status = (isset($filtering_settings[$index]) && !empty($filtering_settings[$index])) ? true: false;
                }else{
                    $status = (isset($_REQUEST[$index]) && !empty($_REQUEST[$index])) ? true: false;
                }
            break;
            // number array filters
            case 'selected_category':
            case 'selected_tag':
                if($filters_persistent){
                    $data = (isset($filtering_settings[$index]) && !empty($filtering_settings[$index])) ? $filtering_settings[$index]: array();
                }else{
                    $data = (isset($_REQUEST[$index]) && !empty($_REQUEST[$index])) ? $_REQUEST[$index]: array();
                }

                $status = (!empty($data) && is_array($data)) ? array_filter(array_map(function($dat){ return (int)$dat; }, $data)): array();
            break;
            // selected post type filter
            case 'selected_post_types':
                if($filters_persistent){
                    $data = (isset($filtering_settings[$index]) && !empty($filtering_settings[$index])) ? $filtering_settings[$index]: array();
                }else{
                    $data = (isset($_REQUEST[$index]) && !empty($_REQUEST[$index])) ? $_REQUEST[$index]: array();
                }

                // make sure the post types that are being requested are ones that the user selected in the settings
                $status = (!empty($data) && is_array($data)) ? array_intersect(Wpil_Settings::getPostTypes(), $data): array();
            break;
            // float filter
            case 'ai_relatedness_threshold':
                if($filters_persistent){
                    $data = (isset($filtering_settings[$index]) && !empty($filtering_settings[$index])) ? $filtering_settings[$index]: 0;
                }else{
                    $data = (isset($_REQUEST[$index]) && !empty($_REQUEST[$index])) ? $_REQUEST[$index]: 0;
                }

                // make sure the post types that are being requested are ones that the user selected in the settings
                $status = (!empty($data)) ? floatval($data): Wpil_Settings::get_ai_sitemap_relatedness_threshold(); // TODO: Replace with post specific one.
            break;
            default:
                $status = false;
                break;
        }

        return $status;
    }

    /**
     * Updates the suggestion filter settings based on $_REQUEST data
     **/
    public static function update_suggestion_filters(){
        // if we're not making the filters persistent
        if(empty(get_option('wpil_make_suggestion_filtering_persistent', false))){
            // exit
            return;
        }

        // set the default state of the filters. (off)
        $setting_data = array(
            'same_category' => false,
            'same_tag' => false,
            'select_post_types' => false,
            'link_orphaned' => false,
            'same_parent' => false,
            'selected_category' => array(),
            'selected_tag' => array(),
            'selected_post_types' => array(),
            'ai_relatedness_threshold' => 0
        );

        // go over the $_REQUEST variable to see if any of the filters are turned on
        foreach($setting_data as $index => $default){
            switch ($index) {
                // bool filters
                case 'same_category':
                case 'same_tag':
                case 'select_post_types':
                case 'link_orphaned':
                case 'same_parent':
                    $status = (isset($_REQUEST[$index]) && !empty($_REQUEST[$index])) ? true: false;
                break;
                // number array filters
                case 'selected_category':
                case 'selected_tag':
                    $data = (isset($_REQUEST[$index]) && !empty($_REQUEST[$index])) ? $_REQUEST[$index]: array();
                    $status = (!empty($data) && is_array($data)) ? array_filter(array_map(function($dat){ return (int)$dat; }, $data)): array();
                break;
                // selected post type filter
                case 'selected_post_types':
                    $data = (isset($_REQUEST[$index]) && !empty($_REQUEST[$index])) ? $_REQUEST[$index]: array();
                    // make sure the post types that are being requested are ones that the user selected in the settings
                    $status = (!empty($data) && is_array($data)) ? array_intersect(Wpil_Settings::getPostTypes(), $data): array();
                break;
                case 'ai_relatedness_threshold':
                    $data = (isset($_REQUEST[$index]) && !empty($_REQUEST[$index])) ? $_REQUEST[$index]: array();
                    // make sure the post types that are being requested are ones that the user selected in the settings
                    $status = (!empty($data)) ? floatval($data): Wpil_Settings::get_ai_sitemap_relatedness_threshold();
                default:
                    $status = false;
                    break;
            }

            // if there is a filter active
            if(!empty($status)){
                // save the data
                $setting_data[$index] = $status;
            }
        }

        // update the stored settings with the results of our efforts
        update_user_meta(get_current_user_id(), 'wpil_persistent_filter_settings', $setting_data); // the settings are user-specific
    }

    /**
     * Gets the selected suggestion filtering options in a URL encoded string for when the suggestions are initially loaded
     * Checks for the global post type suggestion setting
     **/
    public static function get_suggestion_filter_string(){
        $indexes = array(
            'same_category',
            'same_tag',
            'select_post_types',
            'link_orphaned',
            'same_parent',
            'selected_category',
            'selected_tag',
            'selected_post_types'
        );

        $string_data = array();
        $suggestion_post_type_filtering = (!empty(get_option('wpil_limit_suggestions_to_post_types', false))) ? self::getSuggestionPostTypes() : false;

        foreach($indexes as $index){
            $filter_setting = self::get_suggestion_filter($index);
            if(!empty($filter_setting)){
                $string_data[$index] = is_array($filter_setting) ? implode(',', $filter_setting): $filter_setting;
            }
        }

        // if the user has selected a limited set of post types to point suggestions to
        if(!empty($suggestion_post_type_filtering) && is_array($suggestion_post_type_filtering)){
            $string_data['select_post_types'] = 1; // check the "filter post types" box
            $string_data['selected_post_types'] = implode(',', $suggestion_post_type_filtering); // and set the post types
        }

        return !empty($string_data) ? '&' . http_build_query($string_data): '';
    }

    /**
     * 
     **/
    public static function get_autolinking_post_types(){
        $process_types = self::getPostTypes();
        if(!empty(get_option('wpil_limit_autolinks_to_post_types', false))){
            $process_types = array_intersect($process_types, get_option('wpil_autolink_limited_post_types', $process_types));
        }
        
        return $process_types;
    }

    /**
     * Get the max number of posts to search for suggestions
     *
     * @return int
     */
    public static function get_max_suggestion_post_count(){
        return (int) get_option('wpil_max_suggestion_post_count', 0);
    }

    /**
     * Get links that was marked as external
     *
     * @return array
     */
    public static function getIgnoreNofollowDomains()
    {
        $domains = get_option('wpil_nofollow_ignore_domains', '');

        if (!empty($domains)) {
            $domains = explode("\n", $domains);
            foreach ($domains as $key => $domain) {
                $cleaned_domain = self::get_setting_domain($domain);
                if(empty($cleaned_domain)){
                    continue;
                }
                $domains[$key] = $cleaned_domain;
            }

            return $domains;
        }

        return [];
    }

    /**
     * Get links that was marked as external
     *
     * @return array
     */
    public static function getIgnoreOpenSameTabDomains()
    {
        $domains = get_option('wpil_same_tab_ignore_domains', '');

        if (!empty($domains)) {
            $domains = explode("\n", $domains);
            foreach ($domains as $key => $domain) {
                $cleaned_domain = self::get_setting_domain($domain);
                if(empty($cleaned_domain)){
                    continue;
                }
                $domains[$key] = $cleaned_domain;
            }

            return $domains;
        }

        return [];
    }

    /**
     * Get links that was marked as external
     *
     * @return array
     */
    public static function getIgnoreOpenNewTabDomains()
    {
        $domains = get_option('wpil_new_tab_ignore_domains', '');

        if (!empty($domains)) {
            $domains = explode("\n", $domains);
            foreach ($domains as $key => $domain) {
                $cleaned_domain = self::get_setting_domain($domain);
                if(empty($cleaned_domain)){
                    continue;
                }
                $domains[$key] = $cleaned_domain;
            }

            return $domains;
        }

        return [];
    }


    /**
     * Get a list of domains that have been marked as "sponsored"
     *
     * @return array
     */
    public static function getSponsoredDomains()
    {
        $domains = get_option('wpil_sponsored_domains', '');

        if (!empty($domains)) {
            $domains = explode("\n", $domains);
            foreach ($domains as $key => $domain) {
                $cleaned_domain = self::get_setting_domain($domain);
                if(empty($cleaned_domain)){
                    continue;
                }
                $domains[$key] = $cleaned_domain;
            }

            return $domains;
        }

        return [];
    }

    /**
     * Get a list of domains that have been marked as "nofollow"
     *
     * @return array
     */
    public static function getNofollowDomains()
    {
        $domains = get_option('wpil_nofollow_domains', '');

        if (!empty($domains)) {
            $domains = explode("\n", $domains);
            foreach ($domains as $key => $domain) {
                $cleaned_domain = self::get_setting_domain($domain);
                if(empty($cleaned_domain)){
                    continue;
                }
                $domains[$key] = $cleaned_domain;
            }

            return $domains;
        }

        return [];
    }

    /**
     * Get a list of domains that have been marked as "dofollow"
     *
     * @return array
     */
    public static function getDofollowDomains(){
        $domains = get_option('wpil_dofollow_domains', '');

        if (!empty($domains)) {
            $domains = explode("\n", $domains);
            foreach ($domains as $key => $domain) {
                $cleaned_domain = self::get_setting_domain($domain);
                if(empty($cleaned_domain)){
                    continue;
                }
                $domains[$key] = $cleaned_domain;
            }

            return $domains;
        }

        return [];
    }

    /**
     * Get a list of domains that are supposed to open in the same tab
     *
     * @return array
     */
    public static function getSameTabDomains()
    {
        $domains = get_option('wpil_same_tab_domains', '');

        if (!empty($domains)) {
            $domains = explode("\n", $domains);
            foreach ($domains as $key => $domain) {
                $cleaned_domain = self::get_setting_domain($domain);
                if(empty($cleaned_domain)){
                    continue;
                }
                $domains[$key] = $cleaned_domain;
            }

            return $domains;
        }

        return [];
    }

    /**
     * Get a list of domains that are supposed to open in new tabs
     *
     * @return array
     */
    public static function getNewTabDomains()
    {
        $domains = get_option('wpil_new_tab_domains', '');

        if (!empty($domains)) {
            $domains = explode("\n", $domains);
            foreach ($domains as $key => $domain) {
                $cleaned_domain = self::get_setting_domain($domain);
                if(empty($cleaned_domain)){
                    continue;
                }
                $domains[$key] = $cleaned_domain;
            }

            return $domains;
        }

        return [];
    }

    /**
     * Gets a cleaned domain that was entered in the settings
     **/
    public static function get_setting_domain($domain){
        // if the domain doesn't have the protocol included
        if(false === strpos($domain, 'http')){
            // add a protocol so that wp_parse_url can process it correctly
            $domain = 'http://' . ltrim($domain, '/:');
        }
        return wp_parse_url(str_replace('://www.', '://', trim($domain)), PHP_URL_HOST);
    }

    /**
     * Get links that the user wants to ignore
     *
     * @return array
     */
    public static function getIgnoreLinks()
    {
        $links = get_transient('wpil_links_to_ignore');
        if(empty($links)){

            $links = get_option('wpil_links_to_ignore', array());
            if (!empty($links)) {
                $links = explode("\n", $links);
                foreach ($links as $key => $link) {
                    if(empty(trim($link)) || empty(esc_url_raw($link)) && !Wpil_Link::isRelativeLink($link)){
                        unset($links[$key]);
                    }else{
                        $links[$key] = trim($link);
                    }
                }

            }
            if(empty($links)){
                $links = 'no-links-ignored';
            }

            set_transient('wpil_links_to_ignore', $links, 60 * MINUTE_IN_SECONDS);
        }

        if($links === 'no-links-ignored'){
            return array();
        }

        return $links;
    }

    /**
     * Get links that the user wants to ignore from the broken links report
     *
     * @return array
     */
    public static function get_broken_ignore_links()
    {
        $links = get_transient('wpil_broken_links_to_ignore');
        if(empty($links)){

            $links = get_option('wpil_broken_links_to_ignore', array());
            if (!empty($links)) {
                $links = explode("\n", $links);
                foreach ($links as $key => $link) {
                    if(empty(trim($link)) || empty(esc_url_raw($link)) && !Wpil_Link::isRelativeLink($link)){
                        unset($links[$key]);
                    }else{
                        $links[$key] = trim($link);
                    }
                }
            }
            if(empty($links)){
                $links = 'no-links-ignored';
            }

            set_transient('wpil_broken_links_to_ignore', $links, 60 * MINUTE_IN_SECONDS);
        }

        if($links === 'no-links-ignored'){
            return array();
        }

        return $links;
    }

    /**
     * Gets an array of any classes that the user wants to be ignored from both the Link Report and the Suggestions
     **/
    public static function get_ignored_element_classes(){
        $classes = get_transient('wpil_ignore_elements_by_class');
        if(empty($classes)){

            $classes = get_option('wpil_ignore_elements_by_class', array());
            if(!empty($classes)){
                $classes = explode("\n", $classes);
                foreach($classes as $key => $class){
                    $class = trim(trim($class, '.'));
                    if(empty($class)){
                        unset($classes[$key]);
                    }else{
                        $classes[$key] = $class;
                    }
                }
            }
            if(empty($classes)){
                $classes = 'no-elements-ignored';
            }

            set_transient('wpil_ignore_elements_by_class', $classes, 60 * MINUTE_IN_SECONDS);
        }

        if($classes === 'no-elements-ignored'){
            return array();
        }

        return $classes;
    }

    /**
     * Gets an array of shortcode names that the user wants to ignore
     **/
    public static function get_ignored_shortcode_names(){
        $shortcodes = get_transient('wpil_ignore_shortcodes_by_name');
        if(empty($shortcodes)){

            $shortcodes = get_option('wpil_ignore_shortcodes_by_name', array());
            if(!empty($shortcodes)){
                $shortcodes = explode("\n", $shortcodes);
                foreach($shortcodes as $key => $shortcode){
                    $shortcode = trim(preg_replace('`[^\w-]`', '', $shortcode)); // remove all non-word chars minus hyphens from the shortcode name
                    if(empty($shortcode)){
                        unset($shortcodes[$key]);
                    }else{
                        $shortcodes[$key] = $shortcode;
                    }
                }
            }

            $defaults = self::get_default_ignored_shortcodes();
            if(!empty($defaults) && is_array($shortcodes)){
                $shortcodes = array_merge($shortcodes, $defaults);
            }elseif(!empty($defaults) && empty($shortcodes)){
                $shortcodes = $defaults;
            }

            if(empty($shortcodes)){
                $shortcodes = 'no-shortcodes-ignored';
            }

            set_transient('wpil_ignore_shortcodes_by_name', $shortcodes, 60 * MINUTE_IN_SECONDS);
        }

        if($shortcodes === 'no-shortcodes-ignored'){
            return array();
        }

        return $shortcodes;
    }

    /**
     * Gets a list of the available roles
     **/
    public static function get_available_roles($format = false){
        $roles = apply_filters('wpil_filter_available_roles', wp_roles()->roles);

        // if we're supposed to format them for display
        if($format){
            $formatted = array();
            foreach($roles as $role => $data){
                $formatted[$role] = $data['name'];
            }

            return $formatted;
        }

        return $roles;
    }

    /**
     * Gets the list of roles that the user has selected.
     * For the time being, I'm going to make it so that admins are always in the list.
     **/
    public static function get_ignore_linking_roles(){
        $roles = get_option('wpil_ignore_linking_roles', array());
        $possible_roles = self::get_available_roles();

        // if there are stored roles
        if(!empty($roles)){
            // make sure that the stored roles are still valid
            $confirmed = array();
            foreach($roles as $role){
                if(isset($possible_roles[$role])){
                    $confirmed[$role] = $possible_roles[$role]['name'];
                }
            }
            $roles = $confirmed;
        }

        return $roles;
    }

    /**
     * Gets all of the shortcodes that we don't want to/can't process
     **/
    public static function get_default_ignored_shortcodes(){
        $shortcodes = array();
        // if GiveWP is active
        if(defined('GIVE_VERSION')){
            // add the payment reciept shortcode
            $shortcodes[] = 'give_receipt';
        }

        // return the list of assmebled shortcodes
        return $shortcodes;
    }

    /**
     * Gets an array of post & term ids that the user wants to ignore.
     **/
    public static function get_completely_ignored_pages(){
        $pages = get_transient('wpil_ignore_pages_completely');
        if(empty($pages)){
            $pages = array();

            $page_links = get_option('wpil_ignore_pages_completely', array());
            if(!empty($page_links)){
                $page_links = explode("\n", $page_links);
                foreach ($page_links as $link) {
                    $post = Wpil_Post::getPostByLink(trim($link));
                    if (!empty($post)) {
                        $pages[] = $post->type . '_' . $post->id;
                    }
                }
            }
            if(empty($pages)){
                $pages = 'no-pages-ignored';
            }

            set_transient('wpil_ignore_pages_completely', $pages, 60 * MINUTE_IN_SECONDS);
        }

        if($pages === 'no-pages-ignored'){
            return array();
        }

        return $pages;
    }

    /**
     * Get links that was marked as external
     *
     * @return array
     */
    public static function getMarkedAsExternalLinks()
    {
        $links = get_option('wpil_marked_as_external', '');

        if (!empty($links)) {
            $links = explode("\n", $links);
            foreach ($links as $key => $link) {
                $links[$key] = trim($link);
            }

            return $links;
        }

        return [];
    }

    /**
     * Gets if the user wants to use the post slug instead of the title for suggestions
     *
     * @return array
     */
    public static function use_post_slug_for_suggestions()
    {
        return !empty(get_option('wpil_post_slug_for_suggestions', false));
    }

    /**
     * Gets if the user wants to use the REST API to trade site interlinking data between posts
     *
     * @return array
     */
    public static function use_rest_api_for_site_interlinking()
    {
        return !empty(get_option('wpil_external_site_use_json_api', false));
    }

    /**
     * Gets an array of ACF fields that the user wants to ignore from processing
     **/
    public static function getIgnoredACFFields(){
        $field_data = get_transient('wpil_ignore_acf_fields');
        if(empty($field_data)){
            $field_data = get_option('wpil_ignore_acf_fields', array());

            if(is_string($field_data)){
                $field_data = array_map('trim', explode("\n", $field_data));
            }

            set_transient('wpil_ignore_acf_fields', $field_data, 60 * MINUTE_IN_SECONDS);
        }

        return $field_data;
    }

    /**
     * Gets an array of URLs and anchors that the user doesn't want tracked by the click tracking
     * @return array
     **/
    public static function getIgnoredClickLinks(){
        $click_data = get_transient('wpil_ignore_click_links');
        if(empty($click_data)){
            $click_data = get_option('wpil_ignore_click_links', array());

            if(is_string($click_data)){
                $click_data = array_map('trim', explode("\n", $click_data));
            }elseif(empty($click_data)){
                $click_data = 'no-links-ignored';
            }

            set_transient('wpil_ignore_click_links', $click_data, 60 * MINUTE_IN_SECONDS);
        }

        if($click_data === 'no-links-ignored'){
            return array();
        }

        return $click_data;
    }

    /**
     * Gets a list of posts that have had redirects applied to their urls.
     * Obtains the redirect list from plugins that offer redirects.
     * Results are cached for 5 minutes
     * 
     * @param bool $flip Should we return a flipped array of post ids so they can be searched easily?
     * @return array $post_ids And array of posts that have had redirections applied to them
     **/
    public static function getRedirectedPosts($flip = false){
        global $wpdb;

        $post_ids = get_transient('wpil_redirected_post_ids');

        if(!empty($post_ids) && $post_ids !== 'no-ids'){
            // refresh the transient
            set_transient('wpil_redirected_post_ids', $post_ids, 5 * MINUTE_IN_SECONDS);
            // and return the ids
            return ($flip) ? array_flip($post_ids) : $post_ids;
        }elseif($post_ids === 'no-ids'){
            // if a prevsious run hadn't found any ids, return an empty array
            return array();
        }

        // set up the id array
        $post_ids = array();

        // if RankMath is active and the redirections table exists
        if(defined('RANK_MATH_VERSION') && !empty($wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}rank_math_redirections'"))){
            $dest_url_cache = array();

            $permalink_format = get_option('permalink_structure', '');
            $post_name_position = false;

            if(false !== strpos($permalink_format, '%postname%')){
                $pieces = explode('/', $permalink_format);
                $piece_count = count($pieces);
                $post_name_position = array_search('%postname%', $pieces);
            }

            // get the active redirect rules from Rank Math
            $two_years_ago = date('Y-m-d H:i:s', strtotime('-2 years'));
            $active_redirections = $wpdb->get_results("SELECT `id`, `url_to`, `sources` FROM {$wpdb->prefix}rank_math_redirections WHERE `status` = 'active' AND (`last_accessed` > '{$two_years_ago}' OR `updated` > '{$two_years_ago}'  AND `last_accessed` = '0000-00-00 00:00:00')");

            // if there are redirections
            if(!empty($active_redirections)){
                $redirection_ids = array();
                foreach($active_redirections as $dat){
                    if(false !== strpos($dat->sources, 'regex')){
                        continue;
                    }

                    // create a list of the destination urls so that we can exclude posts that aren't hidden by redirects
                    if(!isset($dest_url_cache[$dat->url_to])){
                        $post = Wpil_Post::getPostByLink($dat->url_to);
                        if(!empty($post) && $post->type === 'post'){
                            $dest_url_cache[$dat->url_to] = $post->id;
                        }
                    }

                    $redirection_ids[] = $dat->id;
                }

                // filter the destination urls to pull the post ids
                $ignore_ids = (!empty($dest_url_cache) && !empty(array_filter(array_values($dest_url_cache)))) ? array_filter(array_values($dest_url_cache)): array();

                // if there are posts with updated urls, get the ids so we can ignore them
                $ignore_posts = '';
                if($ignore_ids){
                    $ignore_posts = "AND `object_id` NOT IN (" . implode(', ', $ignore_ids) . ")";
                }

                $redirection_ids = implode(', ', $redirection_ids);
                $redirection_data = $wpdb->get_results("SELECT `from_url`, `object_id` FROM {$wpdb->prefix}rank_math_redirections_cache WHERE `redirection_id` IN ({$redirection_ids}) {$ignore_posts}"); // we're getting the redriects from the cache to save processing time. Rules based searching could take a long time

                // go over the data from the Rank Math cache
                $post_names = array();
                foreach($redirection_data as $dat){
                    // if a redirect was specified for a post, grab the id directly
                    if(isset($dat->object_id) && !empty($dat->object_id)){
                        $post_ids[] = $dat->object_id;
                    }else{
                        // if a url was redirected based on a rule, try to get the post name from the data so we can search the post table for it
                        $url_pieces = explode('/', $dat->from_url);
                        $url_pieces_count = count($url_pieces);

                        if($post_name_position && $url_pieces_count === $piece_count){  // if the url uses the permalink settings and therefor has the same number of pieces as the permalink string (EX: it's a post)
                            $post_names[] = $url_pieces[$post_name_position];
                        }elseif($url_pieces_count === 1){                               // if the url is just the slug
                            $post_names[] = $dat->from_url;
                        }elseif($url_pieces_count === 2 || $url_pieces_count === 3){    // if the url is just the slug, but there's a slash or two
                            $post_names[] = $url_pieces[1];
                        }
                    }
                }

                // if we've found the post names
                if(!empty($post_names)){
                    // query the post table with them to get the post ids
                    $post_names = implode('\', \'', $post_names);
                    $ids = $wpdb->get_col("SELECT `ID` FROM {$wpdb->posts} WHERE `post_name` IN ('{$post_names}')");

                    // remove any ids that are supposed to be ignored, but were accidentally included
                    if(!empty($ignore_ids)){
                        $ids = array_diff($ids, $ignore_ids);
                    }

                    // if there's ids
                    if(!empty($ids)){
                        // add them to the list of post ids that are redirected away from
                        $post_ids = array_merge($post_ids, $ids);
                    }
                }
            }
        }

        // if SEOPress is active
        if(defined('SEOPRESS_PRO_VERSION')){
            // try getting redirected posts
            $query = "SELECT p.post_title as 'old_relative' FROM {$wpdb->posts} p 
                        LEFT JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
                        WHERE p.post_type = 'seopress_404' AND m.meta_key = '_seopress_redirections_enabled' AND m.meta_value = 'yes'";

            $results = $wpdb->get_results($query);

            // if there are some posts
            if(!empty($results)){
                $ids = array();
                // go over them and obtain the redirected post's ids
                foreach($results as $dat){
                    $post = Wpil_Post::getPostByLink($dat->old_relative);

                    if(!empty($post)){
                        $ids[] = $post->id;
                    }
                }

                // if there are ids
                if(!empty($ids)){
                    // add them to the list of post ids that are redirected away from
                    $post_ids = array_merge($post_ids, $ids);
                }
            }
        }

        // if there aren't any ids
        if(empty($post_ids)){
            // make a note that there aren't any and return an empty
            set_transient('wpil_redirected_post_ids', 'no-ids', 5 * MINUTE_IN_SECONDS);
        }else{
            // save the fruits of our labours in the cache
            set_transient('wpil_redirected_post_ids', $post_ids, 5 * MINUTE_IN_SECONDS);
        }

        return ($flip && !empty($post_ids)) ? array_flip($post_ids) : $post_ids;
    }

    /**
     * Obtains an array of URLs that have been redirected away from and their destination URLs.
     * The output is an array of new URLs keyed to the old URLs that are being redirected away from.
     * All URLs are trailing slashed for consistency.
     * When comparing URLs in content to the URLs, be sure to slash them.
     *
     * Currently supports Rank Math, Yoast, SEO Press and Redirection (John Godley)
     * At the moment, we're only focusing on the absolute versions of the URLs.
     * Nobody has asked for relative, and there's only been a couple users that have ever mentioned using relative links.
     * Added to this is the fact that the inbound linking functionality only counts absolute URLs makes adding relative moot.
     **/
    public static function getRedirectionUrls(){
        global $wpdb;

        $urls = get_transient('wpil_redirected_post_urls');

        if(!empty($urls) && $urls !== 'no-redirects'){
            // refresh the transient
            set_transient('wpil_redirected_post_urls', $urls, 5 * MINUTE_IN_SECONDS);
            // and return the URLs
            return $urls;
        }elseif($urls === 'no-redirects'){
            return array();
        }

        // set up the url array
        $urls = array();

        if(defined('RANK_MATH_VERSION') && !empty($wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}rank_math_redirections'"))){
            // get the active redirect rules from Rank Math
            $two_years_ago = date('Y-m-d H:i:s', strtotime('-2 years'));
            $active_redirections = $wpdb->get_results("SELECT `id`, `url_to`, `sources` FROM {$wpdb->prefix}rank_math_redirections WHERE `status` = 'active' AND (`last_accessed` > '{$two_years_ago}' OR `updated` > '{$two_years_ago}'  AND `last_accessed` = '0000-00-00 00:00:00')");

            // if there are redirections
            if(!empty($active_redirections)){

                $redirection_ids = array();
                foreach($active_redirections as $dat){
                    if(false !== strpos($dat->sources, 'regex')){
                        continue;
                    }

                    $redirection_ids[$dat->id] = trailingslashit($dat->url_to);
                }

                $id_string = implode(', ', array_keys($redirection_ids));
                $redirection_data = $wpdb->get_results("SELECT `from_url`, `object_id`, `redirection_id` FROM {$wpdb->prefix}rank_math_redirections_cache WHERE `redirection_id` IN ({$id_string})"); // we're getting the redriects from the cache to save processing time. Rules based searching could take a long time

                // go over the data from the Rank Math cache
                foreach($redirection_data as $dat){
                    $url = trailingslashit(self::makeLinkAbsolute($dat->from_url));
                    $redirected_url = trailingslashit(self::makeLinkAbsolute($redirection_ids[$dat->redirection_id]));
                    $urls[$url] = $redirected_url;
                }
            }
        }

        if(defined('WPSEO_VERSION')){
            $active_redirections   = $wpdb->get_results("SELECT option_name, option_value FROM  {$wpdb->options} WHERE option_name = 'wpseo-premium-redirects-export-plain'");
            foreach ( $active_redirections as $redirection ) {
                $dat = maybe_unserialize($redirection->option_value);
                if(!empty($dat)){
                    foreach($dat as $key => $d){
                        $url = trailingslashit(self::makeLinkAbsolute($key));
                        $redirected_url = trailingslashit(self::makeLinkAbsolute($d['url']));
                        $urls[$url] = $redirected_url;
                    }
                }
            }
        }

        /**
         * Search for the redirects from the dedicated redirect pl;ugin last to override the SEO plugins' redirects
         **/
        if(defined('REDIRECTION_VERSION') && !empty($wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}redirection_items'"))){
            // get the redirect plugin data
            $active_redirections = $wpdb->get_results("SELECT `url`, `action_data` FROM {$wpdb->prefix}redirection_items WHERE `match_type` ='url' AND `match_url` != 'regex'");

            // add the redirections to the url list
            foreach($active_redirections as $dat){
                if(is_string($dat->action_data)){
                    $url = trailingslashit(self::makeLinkAbsolute($dat->url));
                    $action_data = trailingslashit(self::makeLinkAbsolute($dat->action_data));
                    $urls[$url] = $action_data;
                }
            }
        }

        // if SEOPress is active
        if(defined('SEOPRESS_PRO_VERSION')){
            // try getting redirected posts...
            // We're specifically searching for posts that aren't regex-based, are currently active, and result in a 3xx response code so it's not a dead end result
            $query = "SELECT p.post_title AS 'old_relative', m.meta_value as 'new_absolute' FROM {$wpdb->posts} p 
                LEFT JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
                LEFT JOIN {$wpdb->postmeta} ex ON p.ID = ex.post_id AND ex.meta_key = '_seopress_redirections_enabled_regex' 
                LEFT JOIN {$wpdb->postmeta} act ON p.ID = act.post_id AND act.meta_key = '_seopress_redirections_enabled' 
                LEFT JOIN {$wpdb->postmeta} red ON p.ID = red.post_id AND red.meta_key = '_seopress_redirections_type' 
                WHERE p.post_type = 'seopress_404' AND m.meta_key = '_seopress_redirections_value' AND act.meta_value = 'yes' AND ex.meta_key IS NULL AND red.meta_value IN (301,302,307)";
            $results = $wpdb->get_results($query);

            // if there are some posts
            if(!empty($results)){
                // go over them 
                foreach($results as $dat){
                    $url = trailingslashit(self::makeLinkAbsolute($dat->old_relative));
                    $redirected_url = trailingslashit($dat->new_absolute);
                    $urls[$url] = $redirected_url;
                }
            }
        }

        // if Custom Permalinks is active
        if(defined('CUSTOM_PERMALINKS_FILE') && class_exists('Custom_Permalinks_Frontend') && false){

            // TODO: work out why I'm not getting the original post links like I expect to.
            // create a CP url handler class
            $permalink_handler = new Custom_Permalinks_Frontend();

            // search the db for changed urls
			$ids = $wpdb->get_col("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'custom_permalink'");

            // if there are some posts
            if(!empty($ids)){
                // go over them 
                foreach($ids as $id){
                    $url = trailingslashit($permalink_handler->original_post_link($id));
                    $redirected_url = $permalink_handler->custom_post_link($url, get_post($id));
                    $urls[$url] = $redirected_url;
                }
            }
        }

        // if we've found some redirected urls
        if(!empty($urls)){
            // save the fruits of our labours in the cache
            set_transient('wpil_redirected_post_urls', $urls, 5 * MINUTE_IN_SECONDS);
        }else{
            // otherwise, set a flag so we know there's no urls to keep an eye out for
            set_transient('wpil_redirected_post_urls', 'no-redirects', 5 * MINUTE_IN_SECONDS);
        }

        if('no-redirects' === $urls){
            return array();
        }

        return $urls;
    }

    /**
     * Obtains an array of ids from posts that we know have been hidden by redirects.
     * Our standard for 'hidden' are that the original post is inaccessible by url due to being redirected to a different post.
     * 
     * @param bool $return_hidden_ids Should we just return the ids of posts that have been hidden?
     * @return array
     **/
    public static function getPostsHiddenByRedirects($return_hidden_ids = false){
        $posts = get_transient('wpil_redirected_hidden_posts');

        if(!empty($posts) && $posts !== 'no-redirects'){
            // refresh the transient
            set_transient('wpil_redirected_hidden_posts', $posts, 15 * MINUTE_IN_SECONDS);
            // and return the URLs
            return ($return_hidden_ids)? array_keys($posts): $posts;
        }elseif($posts === 'no-redirects'){
            return array();
        }

        $urls = self::getRedirectionUrls();

        if(empty($urls)){
            set_transient('wpil_redirected_hidden_posts', 'no-redirects', 15 * MINUTE_IN_SECONDS);
            return array();
        }

        $posts = array();
        foreach($urls as $old_url => $new_url){
            $old_post = Wpil_Post::getPostByLink($old_url);

            // if we can't identify the original post
            if(empty($old_post)){
                // skip to the next URL since we can't confirm if the original post is hidden or not
                continue;
            }

            // try getting the new post
            $new_post = Wpil_Post::getPostByLink($new_url);
            // if there's no post that we can find
            if(empty($new_post)){
                // skip to the next one
                continue;
            }

            // if we've made it here, check if the ids are different between the posts
            if($old_post->id !== $new_post->id){
                // if it is different, we know that the post is hidden by a redirect
                $posts[$old_post->id] = $new_post->id;
            }
        }

        // if we've managed to find some hidden posts
        if(!empty($posts)){
            // save the fruits of our labours in the cache
            set_transient('wpil_redirected_hidden_posts', $posts, 15 * MINUTE_IN_SECONDS);
        }else{
            // otherwise, set a flag so we know there's no posts to keep an eye out for
            set_transient('wpil_redirected_hidden_posts', 'no-redirects', 15 * MINUTE_IN_SECONDS);
        }

        return $posts;
    }

    /**
     * Makes the supplied link an absolute one.
     * If the link is already absolute, the link is returned unchanged
     * 
     * @param string $url The relative link to make absolute
     * @return string $url The absolute version of the link
     **/
    public static function makeLinkAbsolute($url){
        $site_url = trailingslashit(get_home_url());
        $site_domain = wp_parse_url($site_url, PHP_URL_HOST);
        $site_scheme = wp_parse_url($site_url, PHP_URL_SCHEME);
        $url_domain = wp_parse_url($url, PHP_URL_HOST);

        // if the link isn't pointing to the current domain, 
        if( strpos($url, $site_domain) === false && 
            empty($url_domain) &&                       // but also isn't pointing to an external one
            strpos($url, 'www.') !== 0)                 // and doesn't start with "www.". (Even though browsers DO consider this to be a relative URL. The user didn't mean for it to be)
        {
            $url = ltrim($url, '/');
            $url_pieces = array_reverse(explode('/', rtrim(trim($site_url), '/')));

            foreach($url_pieces as $piece){
                if(empty($piece) || false === strpos(trim($url), $piece)){
                    $url = $piece . '/' . $url;
                }
            }
        }elseif(strpos($url, 'http') === false){
            $url = rtrim($site_scheme, ':') . '://' . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Gets the labels for the given post types.
     * Currently, only gets the labels for the public post types because the non-public ones are usually utility post types and the labels are often generic.
     * So if we used their given labels, it may confuse the user.
     *
     * @param string|array $post_types The list of post types that we're getting the labels for. Can also accept a single post type string
     * @return array $labled_types An array of post type labels keyed to their respective post types. Or an empty array if we can't find the post types...
     **/
    public static function getPostTypeLabels($post_types = array()){
        $labled_types = array();

        if(empty($post_types) || (!is_array($post_types) && !is_string($post_types))){
            return $labled_types;
        }

        if(is_string($post_types)){
            $post_types = array($post_types);
        }

        foreach($post_types as $type){
            $type_object = get_post_type_object($type);
            if(!empty($type_object)){
                if(!empty($type_object->public)){
                    $labled_types[$type_object->name] = $type_object->label;
                }else{
                    $labled_types[$type_object->name] = $type_object->name;
                }
            }
        }

        return $labled_types;
    }

    /**
     * Gets an array of WP constants that are active on the site and could have some impact on Link Whisper's functioning.
     **/
    public static function get_wp_constants($constant = ''){
        $constants = array();

        if(defined('WP_MEMORY_LIMIT')){
            $constants['WP_MEMORY_LIMIT'] = WP_MEMORY_LIMIT;
        }

        if(defined('WP_MAX_MEMORY_LIMIT')){
            $constants['WP_MAX_MEMORY_LIMIT'] = WP_MAX_MEMORY_LIMIT;
        }
        
        if(defined('DISABLE_WP_CRON')){
            $constants['DISABLE_WP_CRON'] = DISABLE_WP_CRON;
        }

        if(!empty($constant) && !empty($constants) && isset($constants[$constant])){
            return $constants[$constant];
        }elseif(!empty($constant)){
            return null;
        }

        return $constants;
    }

    /**
     * Gets a list of the attributes that are supported for link filtering
     **/
    public static function get_available_link_attributes($return_keys = false){
        $atts = array(
            '_blank'    => __('Opens in New Tab', 'wpil'),
            'no_blank'  => __('Opens in Same Tab', 'wpil'),
            'nofollow'  => __('No Follow', 'wpil'), 
            'dofollow'  => __('Do Follow', 'wpil'), 
            'sponsored' => __('Sponsored', 'wpil')
        );

        return ($return_keys) ? array_keys($atts): $atts;
    }

    /**
     * Gets the attributes that are currently being applied.
     * The attibutes are keyed by scope, so 
     **/
    public static function get_active_link_attributes(){

        // if we've already checked for link attrs in this load
        if(self::$link_attrs !== null){
            // return the class stored value
            return self::$link_attrs;
        }

        // try getting the stored link attributes
        $attrs = get_transient('wpil_applied_link_attributes');

        // if there aren't any
        if(empty($attrs)){
            // go over all the active rules and compile a list of them

            // get the nofollow domains
            foreach(self::getNofollowDomains() as $domain){
                $attrs[$domain][] = 'nofollow';
            }

            // get the dofollow domains
            foreach(self::getDofollowDomains() as $domain){
                $attrs[$domain][] = 'dofollow';
            }

            // get the sponsored domains
            foreach(self::getSponsoredDomains() as $domain){
                $attrs[$domain][] = 'sponsored';
            }

            // get the new tab domains
            foreach(self::getNewTabDomains() as $domain){
                $attrs[$domain][] = '_blank';
            }

            // get the same tab domains
            foreach(self::getSameTabDomains() as $domain){
                $attrs[$domain][] = 'no_blank';
            }

            // also get the exception to the global "nofollow" setting
            foreach(self::getIgnoreNofollowDomains() as $domain){
                $attrs[$domain][] = 'nofollow-exception';
            }

            // also get the exception to the global "nofollow" setting
            foreach(self::getIgnoreOpenSameTabDomains() as $domain){
                $attrs[$domain][] = 'no_blank-exception';
            }

            // also get the exception to the global "nofollow" setting
            foreach(self::getIgnoreOpenNewTabDomains() as $domain){
                $attrs[$domain][] = '_blank-exception';
            }

            // now check for the global settings
            if(!empty(get_option('wpil_open_all_internal_new_tab', false))){
                $attrs['internal'][] = '_blank';
            }

            // now check for the global settings
            if(!empty(get_option('wpil_open_all_internal_same_tab', false))){
                $attrs['internal'][] = 'no_blank';
            }

            // now check for the global settings
            if(!empty(get_option('wpil_open_all_external_new_tab', false))){
                $attrs['external'][] = '_blank';
            }

            // now check for the global settings
            if(!empty(get_option('wpil_open_all_external_same_tab', false))){
                $attrs['external'][] = 'no_blank';
            }

            // now check for the global settings
            if(!empty(get_option('wpil_add_nofollow', false))){
                $attrs['external'][] = 'nofollow';
            }

            // if there are no attrs
            if(empty($attrs)){
                // set a flag so we can skip processing next time
                $attrs = 'no-active-attributes';
            }
            
            set_transient('wpil_applied_link_attributes', $attrs, DAY_IN_SECONDS);
        }

        if($attrs === 'no-active-attributes'){
            return array();
        }

        self::$link_attrs = $attrs;

        return $attrs;
    }

    /**
     * Clears the attribute cache
     **/
    public static function clear_active_attribute_cache(){
        // unset the class variable
        self::$link_attrs = null;
        // and clear the transient
        delete_transient('wpil_applied_link_attributes');
    }

    /**
     * Gets the attributes that we're applying to links that are in the current domain.
     * 
     * @return array
     **/
    public static function get_active_link_attrs($url = false, $remove_execeptions = false, $url_is_host = false){
        $attrs = array();

        if(empty($url)){
            return $attrs;
        }

        // get the active attr rules
        $active_attrs = self::get_active_link_attributes();

        // if there aren't any, exit
        if(empty($active_attrs)){
            return $attrs;
        }

        // if there's no protocol and we haven't been handed the host
        if(false === strpos($url, 'http') && empty($url_is_host)){
            // check if we're looking at the site's domain
            if($url === self::get_setting_domain(get_site_url())){
                // if we are, set the host as the url and mark the link as internal
                $host = $url;
                $internal = true;
            }elseif(Wpil_link::isInternal($url)){
                // if the link is internal set the host for the current site's
                $internal = true;
                $host = wp_parse_url(trim(get_site_url()), PHP_URL_HOST);
            }else{
                // try adding a protocol to the url
                $url = 'http://' . ltrim($url, '/:');

                // check if the link is internal
                $internal = Wpil_link::isInternal($url);

                // if it is
                if($internal){
                    // set the host for the current site's
                    $host = wp_parse_url(trim(get_site_url()), PHP_URL_HOST);
                }else{
                    // if it's not, parse out the host
                    $host = wp_parse_url(trim($url), PHP_URL_HOST);
                }
            }
        }else{
            // get the URL's host so we can search it
            $host = self::get_setting_domain($url);
            // find out if the link is internal or external
            $internal = Wpil_link::isInternal($url);
        }

        // if there's attrs for the domain
        if(isset($active_attrs[$host]) && is_array($active_attrs[$host])){
            // add them to the list we're building
            $attrs = array_merge($attrs, $active_attrs[$host]);
        }

        // see if there's any global ones we need to consider
        if($internal && isset($active_attrs['internal'])){
            $attrs = self::resolve_attr_conflicts($attrs, $active_attrs['internal']);
        }elseif(!$internal && isset($active_attrs['external'])){
            $attrs = self::resolve_attr_conflicts($attrs, $active_attrs['external']);
        }

        // if this is an external url and the current site links to external sites
        if(!$internal && !empty(get_option('wpil_link_external_sites', false))){
            // get the external site links
            $external_site_links = array_map(function($url){ return self::get_setting_domain($url); }, Wpil_SiteConnector::get_registered_sites());
            // and make sure we don't "nofollow" any of them
            if( in_array($host, $external_site_links, true) || 
                false !== strpos($host, 'www.') && in_array(str_replace('www.', '', $host), $external_site_links, true))
            {
                if(in_array('nofollow', $attrs, true)){
                    $attrs = array_flip($attrs);
                    unset($attrs['nofollow']);
                    $attrs = array_flip($attrs);
                }
            }
        }

        if(!empty($attrs)){
            foreach($attrs as $key => $attr){
                if(strpos($attr, '-exception') !== false){
                    $sub = substr($attr, 0, strpos($attr, '-exception'));

                    if(in_array($sub, $attrs, true)){
                        $pos = array_search($sub, $attrs, true);
                        unset($attrs[$pos]);
                    }

                    if($remove_execeptions){
                        unset($attrs[$key]);
                    }
                }
            }
        }

        // remove any duplicates
        if(!empty($attrs)){
            $attrs = array_values(array_flip(array_flip($attrs))); // Also re-key the array so the JS doesn't think it's an object
        }

        // allow the user to filter the attrs if needed
        $attrs = apply_filters('wpil_filter_active_link_attrs', $attrs, $url);

        return $attrs;
    }

    /**
     * Determines which attrs will be applied when there are conflicts.
     * First checks to override global attrs with local ones, then decides which local ones take precedence
     * @param array $domain_attrs The attrs that are currently assigned to the attr ()
     * @param array $global_attrs The global attrs
     * @param array The merged/resolved results (can contain both local and global attrs)
     **/
    public static function resolve_attr_conflicts($domain_attrs = array(), $global_attrs = array()){
        if(empty($domain_attrs)){
            return !empty($global_attrs) ? $global_attrs: array();
        }

        // if there are global atts
        if(!empty($global_attrs)){

            // remove any from the domain atts so it only contains locals
            $domain_attrs = array_diff($domain_attrs, $global_attrs); // some getters pull the global atts, so we need to filter them out here

            // go over the list of globals and add the ones that don't conflict with the locals
            foreach($global_attrs as $attr){
                switch ($attr) {
                    case '_blank':
                        if(!in_array('no_blank', $domain_attrs, true) && !in_array('_blank-exception', $domain_attrs, true)){
                            $domain_attrs[] = $attr;
                        }
                    break;
                    case 'no_blank':
                        if(!in_array('_blank', $domain_attrs, true) && !in_array('no_blank-exception', $domain_attrs, true)){
                            $domain_attrs[] = $attr;
                        }
                    break;
                    case 'nofollow':
                        if(!in_array('dofollow', $domain_attrs, true) && !in_array('nofollow-exception', $domain_attrs, true)){
                            $domain_attrs[] = $attr;
                        }
                    break;
                    case 'dofollow':
                        if(!in_array('nofollow', $domain_attrs, true)){
                            $domain_attrs[] = $attr;
                        }
                    break;
                    case 'sponsored':
                        // there's no alt for sponsored...yet
                    break;
                }
            }
        }

        // now go over the remaining domain attrs are remove the conflicting ones
        $return_attrs = array();
        foreach($domain_attrs as $attr){
            switch ($attr) {
                case '_blank':
                    if(!in_array('no_blank', $domain_attrs, true) && !in_array('_blank-exception', $domain_attrs, true)){
                        $return_attrs[] = $attr;
                    }
                break;
                case 'no_blank':
                    if(!in_array('_blank', $domain_attrs, true) && !in_array('no_blank-exception', $domain_attrs, true)){
                        $return_attrs[] = $attr;
                    }
                break;
                case 'nofollow':
                    if(!in_array('dofollow', $domain_attrs, true) && !in_array('nofollow-exception', $domain_attrs, true)){
                        $return_attrs[] = $attr;
                    }
                break;
                case 'dofollow':
                    if(!in_array('nofollow', $domain_attrs, true)){
                        $return_attrs[] = $attr;
                    }
                break;
                case 'sponsored':
                default: 
                    $return_attrs[] = $attr;
                    // there's no alt for sponsored...yet
                break;
            }
        }

        return $return_attrs;
    }

    public static function ajax_save_domain_attributes(){
        // check the nonce
        Wpil_Base::verify_nonce('wpil_attr_save_nonce');

        // and make sure we have a domain
        if(!isset($_POST['domain']) || empty($_POST['domain']) || empty(trim(esc_url_raw($_POST['domain'])))){
            wp_send_json(array(
                'error' => array(
                    'title' => __('Data Error', 'wpil'),
                    'text'  => __('No domain was detected. Please reload the report and try again', 'wpil'),
                )
            ));
        }

        // now that that's behind us, let's calculate the attr settings to apply to the domain
        $domain = wp_parse_url(trim(esc_url_raw(trim($_POST['domain']))), PHP_URL_HOST);

        // then get the rules that have been applied to the domain
        $domain_rules = self::get_active_link_attrs($domain, false, true);

        // validate the rules from the user
        $selected_attrs = self::validate_selected_link_attrs($_POST['attrs']);

        // create a list of the processed attrs
        $processed = array();

        // unset any rules that the user has de-selected
        $deselect = array_diff($domain_rules, $selected_attrs);

        if(!empty($deselect)){
            $processed = array_replace_recursive($processed, self::unset_selected_domain_attrs($domain, $deselect));
        }

        // get all the rules that have changed
        $selected = array_diff($selected_attrs, $domain_rules);

        // if the user has in fact selected some rules to add
        if(!empty($selected)){
            $processed = array_replace_recursive(self::set_selected_domain_attrs($domain, $selected));
        }

        // get our success stats
        $success = array_filter($processed, function($value, $key){ return strpos($key, '-exception') === false && !empty($value);}, ARRAY_FILTER_USE_BOTH);
        $not_success = array_filter($processed, function($value, $key){ return strpos($key, '-exception') === false && empty($value);}, ARRAY_FILTER_USE_BOTH);

        // clear the attribute cache
        self::clear_active_attribute_cache();

        // get the current state of the attr rules so we can start creating a response for the user
        $current_attrs = self::get_active_link_attrs($domain, true, true);

        if(!empty($success)){
            Wpil_Telemetry::log_event_for_user('domains_changed_attrs');
            $text = sprintf(_n('Link Whisper has updated %d attribute!', 'Link Whisper has updated %d attributes!', count($success), 'wpil'), count($success));
            $text .= !empty(count($not_success)) ? sprintf(_n(' %d attribute could not be updated', ' %d attributes could not be updated', count($not_success), 'wpil'), count($not_success)) : '';
            $status = array(
                'success' => array(
                    'title' => __('Update Successful!', 'wpil'),
                    'text' => $text,
                    'data' => $current_attrs
                )
            );
        }else{
            $status = array(
                'info' => array(
                    'title' => __('Attributes Not Updated', 'wpil'),
                    'text' => __('Link Whisper wasn\'t able to update the domain\'s attributes.', 'wpil'),
                    'data' => $current_attrs
                )
            );
        }

        wp_send_json($status);
    }

    /**
     * Validates a supplied list of link attrs so we're sure that we're only playing with ones that are currently supported
     * @return array
     **/
    public static function validate_selected_link_attrs($attrs = array()){
        if(empty($attrs)){
            return array();
        }

        $valid = self::get_available_link_attributes(true);

        return array_intersect($attrs, $valid);
    }

    public static function unset_selected_domain_attrs($domain = '', $unset_attrs = array()){
        if(empty($domain) || empty($unset_attrs)){
            return array();
        }

        $unset = array();
        foreach($unset_attrs as $attr){
            $removed = false;
            switch ($attr) {
                case '_blank':
                    $removed = self::remove_domain_from_setting($domain, 'wpil_new_tab_domains');
                break;
                case 'no_blank':
                    $removed = self::remove_domain_from_setting($domain, 'wpil_same_tab_domains');
                break;
                case 'nofollow':
                    $removed = self::remove_domain_from_setting($domain, 'wpil_nofollow_domains');
                break;
                case 'dofollow':
                    $removed = self::remove_domain_from_setting($domain, 'wpil_dofollow_domains');
                break;
                case 'sponsored':
                    $removed = self::remove_domain_from_setting($domain, 'wpil_sponsored_domains');
                break;
            }

            $unset[$attr] = $removed;
        }

        self::add_domain_global_attr_exceptions($domain, $unset_attrs);

        // return the unset atts
        return $unset;
    }

    /**
     * Removes the listed domain from the text-area based setting data for the given key.
     * Takes the setting data, splits it into an array, removes any instances of the domain, and resaves it.
     * Intended for domain-level settings
     * @param string $domain The domain that we're going to remove from the setting
     * @param string $setting_key The setting that we're going to remove the domain from
     **/
    public static function remove_domain_from_setting($domain = '', $setting_key = ''){
        if(empty($domain) || empty($setting_key)){
            return true;
        }

        $data = get_option($setting_key, ''); // since these are text-area settings, the default value is an empty string

        // if there's no setting data
        if(empty($data)){
            // return true since the domain isn't here...
            return true;
        }

        $removed = false;
        $data = explode("\n", $data);
        foreach ($data as $key => $saved_domain) {
            // search for the presence of the domain withing the saved domain to handle different slashes and cases where the full url was entered
            if(false !== strpos(trim($saved_domain), $domain)){ 
                unset($data[$key]);
                $removed = true;
            }
        }
        
        // if we've removed the domain
        if($removed){
            // restringify the setting data and update the option
            $data = implode("\n", $data);
            update_option($setting_key, $data);
            // now that we're done with the option, clear any option cache that may exist
            delete_transient($setting_key);
        }

        // return our deletion status
        return $removed;
    }

    /**
     * Adds exceptions to the global attr rules
     * Intended for domain-level settings
     * @param string $domain The domain that we're going to remove from the setting
     * @param array $attrs An array of attrs to add exceptions for
     * @return array
     **/
    public static function add_domain_global_attr_exceptions($domain = '', $attrs = array()){
        if(empty($domain) || empty($attrs)){
            return array();
        }

        // get any active global settings
        $active_globals = self::get_active_global_attrs();

        // exit if there's no active globals
        if(empty($active_globals)){
            return array();
        }

        $added = array();
        foreach($attrs as $attr){
            $add = false;
            switch ($attr) {
                case '_blank':
                    $add = self::add_domain_to_setting($domain, 'wpil_new_tab_ignore_domains');
                break;
                case 'no_blank':
                    $add = self::add_domain_to_setting($domain, 'wpil_same_tab_ignore_domains');
                break;
                case 'nofollow':
                    $add = self::add_domain_to_setting($domain, 'wpil_nofollow_ignore_domains');
                break;
            }

            $added[$attr] = $add;
        }

        // return our exception status
        return $added;
    }

    /**
     * Removes exceptions to the global attr rules
     * Intended for domain-level settings
     * @param string $domain The domain that we're going to remove from the setting
     * @param array $attrs An array of attrs to remove exceptions for
     * @return array
     **/
    public static function remove_domain_global_attr_exceptions($domain = '', $attrs = array()){
        if(empty($domain) || empty($attrs)){
            return array();
        }

        // get any active global settings
        $active_globals = self::get_active_global_attrs();

        // exit if there's no active globals
        if(empty($active_globals)){
            return array();
        }

        $unset = array();
        foreach($attrs as $attr){
            $removed = false;
            switch ($attr) {
                case '_blank':
                    $removed = self::remove_domain_from_setting($domain, 'wpil_new_tab_ignore_domains');
                break;
                case 'no_blank':
                    $removed = self::remove_domain_from_setting($domain, 'wpil_same_tab_ignore_domains');
                break;
                case 'nofollow':
                    $removed = self::remove_domain_from_setting($domain, 'wpil_nofollow_ignore_domains');
                break;
            }

            $unset[$attr] = $removed;
        }

        // return our exception status
        return $unset;
    }

    /**
     * Adds the listed domain to the text-area based setting data for the given key.
     * Takes the setting data, splits it into an array, checks it for any instances of the domain, inserts the domain if it's not there, and resaves it.
     * Intended for domain-level settings
     * @param string $domain The domain that we're going to add to the setting
     * @param string $setting_key The setting that we're going to add the domain to
     **/
    public static function add_domain_to_setting($domain = '', $setting_key = ''){
        if(empty($domain) || empty($setting_key)){
            return true;
        }

        $data = get_option($setting_key, ''); // since these are text-area settings, the default value is an empty string

        // if there's no setting data
        if(empty($data)){
            // set it up as an array
            $data = array();
        }else{
            // if there is data, explode it
            $data = explode("\n", $data);
        }

        $added = false;

        if(!in_array($domain, $data, true)){
            $data[] = $domain;
            $added = true;
        }
        
        // if we've added the domain
        if($added){
            // restringify the setting data and update the option
            $data = implode("\n", $data);
            update_option($setting_key, $data);
            // now that we're done with the option, clear any option cache that may exist
            delete_transient($setting_key);
        }

        // return our addition status
        return $added;
    }

    /**
     * Sets the attributes that the user has selected for the domain.
     * Assumes that the selected attrs have been filtered so no colliding attrs will be provided. (EX: _blank and no_blank)
     * @param string
     * @param array
     * @return array
     **/
    public static function set_selected_domain_attrs($domain = '', $selected_attrs = array()){
        if(empty($domain) || empty($selected_attrs)){
            return array();
        }

        $set = array();
        foreach($selected_attrs as $attr){
            $added = false;
            switch ($attr) {
                case '_blank':
                    $added = self::add_domain_to_setting($domain, 'wpil_new_tab_domains');
                break;
                case 'no_blank':
                    $added = self::add_domain_to_setting($domain, 'wpil_same_tab_domains');
                break;
                case 'nofollow':
                    $added = self::add_domain_to_setting($domain, 'wpil_nofollow_domains');
                break;
                case 'dofollow':
                    $added = self::add_domain_to_setting($domain, 'wpil_dofollow_domains');
                break;
                case 'sponsored':
                    $added = self::add_domain_to_setting($domain, 'wpil_sponsored_domains');
                break;
            }

            $set[$attr] = $added;
        }

        // remove any global exceptions that are active
        self::remove_domain_global_attr_exceptions($domain, $selected_attrs); // we won't listen for the results now, we _shouldn't_ need to since these are exceptions to rules, so they are the abesence of selected rules.

        // return the results of our efforts
        return $set;
    }

    /**
     * Gets the active globally applied attrs so we can account for them
     **/
    public static function get_active_global_attrs(){
        $globals = array();

        if(get_option('wpil_open_all_internal_new_tab', false) || get_option('wpil_open_all_external_new_tab', false)){
            $globals[] = '_blank';
        }

        if(get_option('wpil_open_all_internal_same_tab', false) || get_option('wpil_open_all_external_same_tab', false)){
            $globals[] = 'no_blank';
        }

        if(get_option('wpil_add_nofollow', false)){
            $globals[] = 'nofollow';
        }

        return $globals;
    }

    /**
     * Checks to see if the given attr conflicts with attributes in the given list.
     * @param string The attr to check for conflicts
     * @param array The list of attrs to see if the lone attr conflicts with
     * @param bool Should we return which attrs we've found conflicts for? Defaults to no
     * @return bool|array Returns a bool if we're checking for simple conflicts. True if there is a conflict, false if there isn't. Returns an array of the conflicting attrs if we ask to return the conflicting results
     **/
    public static function check_if_attrs_conflict($attr = '', $attrs = array(), $return_conflict = false){
        if(empty($attr) || empty($attrs)){
            return ($return_conflict) ? array() : false;
        }

        $conflicts = array();

        switch ($attr) {
            case '_blank':
                if(in_array('no_blank', $attrs, true)){
                    $conflicts[] = 'no_blank';
                }
            break;
            case 'no_blank':
                if(in_array('_blank', $attrs, true)){
                    $conflicts[] = '_blank';
                }
            break;
            case 'nofollow':
                if(in_array('dofollow', $attrs, true)){
                    $conflicts[] = 'dofollow';
                }
            break;
            case 'dofollow':
                if(in_array('nofollow', $attrs, true) && !in_array('nofollow-exception', $attrs, true)){
                    $conflicts[] = 'nofollow';
                }
            break;
            case 'sponsored':
                // no problems with sponsored
            break;
        }

        return ($return_conflict) ? $conflicts : !empty($conflicts);
    }

    /**
     * Recursively applies sanitize_text_field to array values
     **/
    public static function simple_textfield_array_sanitizer($array){

        $cleaned = array();
        foreach($array as $index => $data){
            if(is_array($data)){
                $cleaned[sanitize_text_field($index)] = Wpil_Settings::simple_textfield_array_sanitizer($data);
            }else{
                $cleaned[sanitize_text_field($index)] = sanitize_text_field($data);
            }
        }

        return $cleaned;
    }

    /**
     * Outputs basic css classes for setting rows
     **/
    public static function output_setting_classes($current_tab = '', $visible_tab = ''){
        $tab = '';
        switch($current_tab){
            case 'wpil-general-settings':
            case 'wpil-content-ignoring-settings':
            case 'wpil-domain-settings':
            case 'wpil-advanced-settings':
            case 'wpil-licensing-settings':
                $tab = $current_tab;
                break;
            default:
                $tab = 'wpil-general-settings';
                break;
        }

        $visible = '';
        switch($visible_tab){
            case 'wpil-general-settings':
            case 'wpil-content-ignoring-settings':
            case 'wpil-domain-settings':
            case 'wpil-advanced-settings':
            case 'wpil-licensing-settings':
                $visible = $visible_tab;
                break;
            default:
                $visible = 'wpil-general-settings';
                break;
        }

        $classes = "{$tab} wpil-setting-row" . (($tab === $visible) ? " show-setting": "");

        return esc_attr($classes);
    }

    /**
     * 
     **/
    public static function get_related_post_settings($setting = '', $refresh = false){
        $settings = get_transient('wpil_related_post_settings');
        if(empty($settings) || $refresh){
            $default_styling = array(
                'full' => array(
                    'widget-margin-top' => 40,
                    'widget-margin-right' => 'false',
                    'widget-margin-bottom' => 30,
                    'widget-margin-left' => 'false',
                    'widget-title-font-size' => 'false',
                    'widget-description-font-size' => 'false',
                    'item-margin-top' => 'false',
                    'item-margin-right' => 'false',
                    'item-margin-bottom' => 'false',
                    'item-margin-left' => 'false',
                    'item-title-font-size' => 'false',
                    'item-title-font-weight' => 'site-default',
                    'item-title-font-style' => 'site-default',

                    'widget-background-color' => '',
                    'widget-title-text-color' => '',
                    'widget-title-text-hover-color' => '',
                    'widget-description-text-color' => '',
                    'item-background-color' => '',
                    'item-title-text-color' => '',
                    'item-title-text-hover-color' => '',

                    'item-list-style' => 'site-default'
                ),
                'mobile' => array(
                    'widget-margin-top' => 'false',
                    'widget-margin-right' => 'false',
                    'widget-margin-bottom' => 'false',
                    'widget-margin-left' => 'false',
                    'widget-title-font-size' => 'false',
                    'widget-description-font-size' => 'false',
                    'item-margin-top' => 'false',
                    'item-margin-right' => 'false',
                    'item-margin-bottom' => 'false',
                    'item-margin-left' => 'false',
                    'item-title-font-size' => 'false',
                    'item-title-font-weight' => 'site-default',
                    'item-title-font-style' => 'site-default',

                    'widget-background-color' => '',
                    'widget-title-text-color' => '',
                    'widget-title-text-hover-color' => '',
                    'widget-description-text-color' => '',
                    'item-background-color' => '',
                    'item-title-text-color' => '',
                    'item-title-text-hover-color' => '',

                    'item-list-style' => 'site-default',

                    'mobile_breakpoint' => 480
                ),
            );

            $settings = array(
                'active' => get_option('wpil_activate_related_posts', false),
                'insert_method' => get_option('wpil_related_posts_insert_method', 'append'),
                'link_handling' => get_option('wpil_related_post_existing_link_handling', 'none'),
                'link_count' => get_option('wpil_related_post_link_count', 8),
                'widget_layout' => get_option('wpil_related_post_widget_layout', false),
                'column_count' => get_option('wpil_related_posts_column_count', '2'),
                'term_search' => get_option('wpil_related_post_term_search', 'none'),
                'cat_ignore' => get_option('wpil_related_post_cat_ignore', array()),
                'tag_ignore' => get_option('wpil_related_post_tag_ignore', array()),
                'parent_search' => get_option('wpil_related_post_parent_search', 'none'),
                'select_method' => get_option('wpil_related_posts_select_method', 'auto'),
                'use_ai_data' => get_option('wpil_related_posts_use_ai_data', false),
                'ai_relation_threshold' => get_option('wpil_related_posts_ai_relation_threshold', 0.7500),
                'widget_text' => get_option('wpil_related_posts_widget_text', array('title' => 'Related Posts', 'title_tag' => 'h3', 'description' => '', 'description_tag' => 'span', 'empty_message' => 'No Related Posts Found.')),
                'use_thumbnail' => (int) get_option('wpil_related_posts_use_thumbnail', '0'),
                'use_first_img' => (int) get_option('wpil_related_posts_use_first_image_as_thumbnail', '0'),
                'thumbnail_position' => get_option('wpil_related_posts_thumbnail_position', 'above'),
                'thumbnail_size' => (int) get_option('wpil_related_posts_thumbnail_size', 150),
                'hide_empty_widget' => (int) get_option('wpil_related_posts_hide_empty_widget', '1'),
                'sort_order' => get_option('wpil_related_posts_sort_order', 'rand'),
                'orphaned_linking' => get_option('wpil_related_posts_orphaned_linking', 'none'),
                'widget_placement' => get_option('wpil_related_posts_widget_placement', 'bottom'),
                'widget_placement_paragraphs' => get_option('wpil_related_posts_widget_placement_paragraphs', 1),
                'styling' => get_option('wpil_related_posts_styling', $default_styling),
            );

            if(!isset($settings['widget_layout']) || empty($settings['widget_layout'])){
                $settings['widget_layout'] = array('display' => 'column', 'count' => (int)$settings['column_count']);
            }else{
                $settings['widget_layout'] = json_decode(wp_unslash($settings['widget_layout']), true);
            }

            if(!isset($settings['widget_text']['empty_message'])){
                $settings['widget_text']['empty_message'] = 'No Related Posts Found.';
            }

            // for compatibility with anyone using an older custom template
            $settings['show_thumbnail'] = $settings['use_thumbnail'];
            $settings['text'] = $settings['widget_text'];

            // if the site hasn't upgraded to the new mobile styling
            if( isset($settings['styling']) && !empty($settings['styling']) && 
                !isset($settings['styling']['full']))
            {
                // obtain the existing styling
                $existing_style = $settings['styling'];
                // reset the styling index to update it
                $settings['styling'] = $default_styling;
                // and import the user's styling into it
                $settings['styling']['full'] = array_merge($settings['styling']['full'], $existing_style);
            }

            if(isset($settings['styling']['full'])){
                if(!isset($settings['styling']['full']['widget-title-text-hover-color'])){
                    $settings['styling']['full']['widget-title-text-hover-color'] = '';
                }

                if(!isset($settings['styling']['full']['item-title-text-hover-color'])){
                    $settings['styling']['full']['item-title-text-hover-color'] = '';
                }
            }

            if(isset($settings['styling']['mobile'])){
                if(!isset($settings['styling']['mobile']['widget-title-text-hover-color'])){
                    $settings['styling']['mobile']['widget-title-text-hover-color'] = '';
                }

                if(!isset($settings['styling']['mobile']['item-title-text-hover-color'])){
                    $settings['styling']['mobile']['item-title-text-hover-color'] = '';
                }
            }

            set_transient('wpil_related_post_settings', $settings, 3 * DAY_IN_SECONDS);
        }

        // if we're doing a preview of the settings
        if( isset($_GET['nonce']) && 
            wp_verify_nonce($_GET['nonce'], 'wpil-related-posts-preview-nonce'))
        {
            // pull the supplied settings and merge them with the existing ones
            $new_settings = array();
            foreach($settings as $setting_name => $setting_value){
                foreach($_GET as $new_setting_name => $new_setting_value){
                    $pos = strpos($new_setting_name, $setting_name);
                    if(false !== $pos && ($pos + strlen($setting_name)) === strlen($new_setting_name)){
                        $new_settings[$setting_name] = $new_setting_value;
                    }
                }
            }

            $new_settings = self::validate_related_post_settings($new_settings);
            $settings = array_merge($settings, $new_settings);
        }

        if(!empty($setting)){
            return (array_key_exists($setting, $settings)) ? $settings[$setting]: false;
        }

        return $settings;
    }

    public static function validate_related_post_settings($settings = array()){

        if(empty($settings) && !is_array($settings)){
            return array();
        }

        foreach($settings as $setting_name => $value){
            switch ($setting_name) {
                case 'insert_method':
                    $value = ($value === 'append') ? 'append': 'shortcode';
                    break;
                case 'link_handling':
                    $possible_values = array(
                        "none", 
                        "no-outbound-internal", 
                        "prefer-outbound-internal", 
                        "only-outbound-internal", 
                        "no-inbound-internal", 
                        "prefer-inbound-internal", 
                        "only-inbound-internal"
                    );
                    $value = (in_array($value, $possible_values, true)) ? $value: 'none';
                    break;
                case 'link_count':
                case 'column_count':
                case 'use_ai_data':
                case 'use_thumbnail':
                case 'use_first_img':
                case 'thumbnail_size':
                case 'hide_empty_widget':
                case 'widget_placement_paragraphs':
                    $value = (int) $value;
                    break;
                case 'ai_relation_threshold':
                    $value = (float) $value;
                    break;
                case 'widget_layout':
                    $possible_values = array(
                        'column',
                        'row'
                    );

                    if(is_string($value) && strlen($value) > 3){
                        $maybe_encoded = json_decode(wp_unslash($value), true);
                        if(!empty($maybe_encoded)){
                            $value = $maybe_encoded;
                        }
                    }


                    if(is_array($value) && isset($value['display']) && isset($value['count'])){
                        $value['display'] = (in_array($value['display'], $possible_values, true)) ? $value['display']: 'column';
                        $value['count'] = (int) $value['count'];
                    }else{
                        $value = array('display' => 'column', 'count' => 2);
                    }
                    break;
                case 'term_search':
                    $possible_values = array(
                        "none",
                        "tags",
                        "only-tags",
                        "cats",
                        "only-cats",
                        "both",
                        "only-both"
                    );
                    $value = (in_array($value, $possible_values, true)) ? $value: 'none';
                    break;
                case 'cat_ignore':
                case 'tag_ignore':
                    if(is_array($value) && isset($value[0]) && false !== strpos($value[0], ',')){
                        $value = $value[0];
                    }

                    if(is_string($value) && false !== strpos($value, ',')){
                        $value = explode(',', $value);
                    }

                    if(is_array($value)){
                        $value = array_map(function($v){ return (int) $v;}, $value);
                    }else{
                        $value = array((int) $value);
                    }

                    $value = array_filter($value);

                    $value = (!empty($value)) ? $value: array();
                    break;
                case 'parent_search':
                    $possible_values = array(
                        "none",
                        "prefer-both",
                        "only-both"
                    );
                    $value = (in_array($value, $possible_values, true)) ? $value: 'none';
                    break;
                case 'select_method':
                    $value = ($value === 'auto') ? 'auto': 'manual';
                    break;
                case 'widget_text':
                    if(is_array($value)){
                        /** **/
                        if(isset($value['title'])){
                            $value['title'] = sanitize_text_field($value['title']);
                        }
                        /** **/
                        if(isset($value['title_tag'])){
                            $possible_values = array(
                                'h1',
                                'h2',
                                'h3',
                                'h4',
                                'h5',
                                'h6',
                                'div'
                            );
                            $value['title_tag'] = in_array($value['title_tag'], $possible_values, true) ? $value['title_tag']:'h3';
                        }

                        /** **/
                        if(isset($value['description'])){
                            if(Wpil_Link::checkIfBase64ed($value['description'])){
                                $value['description'] = base64_decode($value['description']);
                            }

                            $value['description'] = sanitize_textarea_field($value['description']);
                        }
                        /** Not currently used **/
                        if(isset($value['description_tag'])){
                            $value['description_tag'] = sanitize_text_field($value['description_tag']);
                        }
                        /** **/
                        if(isset($value['empty_message'])){
                            $value['empty_message'] = sanitize_text_field($value['empty_message']);
                        }
                    }else{
                        $value = array('title' => 'Related Posts', 'title_tag' => 'h3', 'description' => '', 'description_tag' => 'span', 'empty_message' => 'No Related Posts Found.');
                    }
                    break;
                case 'thumbnail_position':
                    $possible_values = array(
                        "above",
                        "below",
                        "inside"
                    );
                    $value = (in_array($value, $possible_values, true)) ? $value: 'above';
                    break;
                case 'sort_order':
                    $possible_values = array(
                        "default",
                        "rand",
                        "newest",
                        "oldest"
                    );
                    $value = (in_array($value, $possible_values, true)) ? $value: 'default';
                    break;
                case 'orphaned_linking':
                    $possible_values = array(
                        "none",
                        "prefer-orphaned",
                        "only-orphaned"
                    );
                    $value = (in_array($value, $possible_values, true)) ? $value: 'none';
                    break;
                case 'widget_placement':
                    $possible_values = array(
                        "bottom",
                        "paragraphs_from_bottom",
                        "paragraphs_from_top",
                        "top"
                    );
                    $value = (in_array($value, $possible_values, true)) ? $value: 'bottom';
                    break;
                case 'styling':
                    if(is_array($value)){
                        $cleared = array();
                        foreach($value as $context => $data){
                            if(!in_array($context, array('full', 'mobile'))){
                                continue;
                            }
                            $possible_values = array(
                                'widget-margin-top'             => 'int',
                                'widget-margin-right'           => 'int',
                                'widget-margin-bottom'          => 'int',
                                'widget-margin-left'            => 'int',
                                'widget-title-font-size'        => 'int',
                                'widget-description-font-size'  => 'int',
                                'item-margin-top'               => 'int',
                                'item-margin-right'             => 'int',
                                'item-margin-bottom'            => 'int',
                                'item-margin-left'              => 'int',
                                'item-title-font-size'          => 'int',
                                'item-title-font-weight'        => 'font-weight',
                                'item-title-font-style'         => 'font-style',
                                'mobile_breakpoint'             => 'int',
                                'widget-background-color'       => 'color',
                                'widget-title-text-color'       => 'color',
                                'widget-title-text-hover-color' => 'color',
                                'widget-description-text-color' => 'color',
                                'item-background-color'         => 'color',
                                'item-title-text-color'         => 'color',
                                'item-title-text-hover-color'   => 'color',
                                'item-list-style'               => 'list-style',
                            );

                            foreach($data as $key => $dat){
                                if(!isset($possible_values[$key])){
                                    continue;
                                }

                                if(!isset($cleared[$context])){
                                    $cleared[$context] = array();
                                }

                                if($possible_values[$key] === 'int'){
                                    $cleared[$context][$key] = ($dat === 'false') ? 'false': (int) $dat;
                                }elseif($possible_values[$key] === 'color'){
                                    preg_match('/#([a-f0-9]{3}){1,2}\b/', $dat, $match);
                                    if(!empty($match) && !empty($match[0])){
                                        $cleared[$context][$key] = $match[0];
                                    }else{
                                        $cleared[$context][$key] = '0';
                                    }
                                }elseif($possible_values[$key] === 'list-style'){
                                    $sub_possible = array(
                                        'site-default',
                                        'none',
                                        'disc',
                                        'circle',
                                        'decimal',
                                        'upper-roman'
                                    );
                                    $cleared[$context][$key] = (in_array($dat, $sub_possible, true)) ? $dat: 'site-default';
                                }elseif($possible_values[$key] === 'font-weight'){
                                    $sub_possible = array(
                                        'site-default',
                                        '100',
                                        '200',
                                        '300',
                                        '400',
                                        '500',
                                        '600',
                                        'bold'
                                    );
                                    $cleared[$context][$key] = (in_array($dat, $sub_possible, true)) ? $dat: 'site-default';
                                }elseif($possible_values[$key] === 'font-style'){
                                    $sub_possible = array(
                                        'site-default',
                                        'italic',
                                        'normal'
                                    );
                                    $cleared[$context][$key] = (in_array($dat, $sub_possible, true)) ? $dat: 'site-default';
                                }
                            }
                        }
                        $value = $cleared;
                    }else{
                        $value = false;
                    }
                    break;
                default:
                    // if we don't recognize the setting, default to false
                    $value = false;
                    break;
            }

            $settings[$setting_name] = $value;
        }

        return $settings;
    }

    public static function related_posts_active($post_id = false, $force_display = false){
        $related_active = !empty(get_option('wpil_activate_related_posts', false));
        if($related_active && empty($post_id))
        {
            return true;
        }

        // if the widget isn't being forced to display and the current post isn't one that the RP widget is active for
        if(!$force_display && !in_array(get_post_type($post_id), Wpil_Settings::get_related_posts_active_post_types(), true)){
            // say that the widget isn't active here
            return false;
        }

        $flag_set = get_post_meta($post_id, 'wpil_related_posts_active', true);
        if($related_active && (($flag_set === '' || !empty($flag_set) || $flag_set === 'true' || $flag_set === '1') && $flag_set !== '0' || $force_display)){
            return true;
        }

        return false;
    }

    public static function get_related_posts_active_post_types(){
        return get_option('wpil_related_posts_active_post_types', self::getPostTypes());
    }

    public static function get_disable_link_insert_tracking(){
        return !empty(get_option('wpil_disable_link_insert_tracking', '0'));
    }

    public static function get_filter_out_frontend_attributes(){
        return !empty(get_option('wpil_filter_out_frontend_link_attributes', '0'));
    }

    public static function get_if_telemetry_active(){
        return !empty(get_option('wpil_enable_telemetry', '1'));
    }
}
