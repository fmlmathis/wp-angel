<?php
$link_external = get_option('wpil_link_external_sites', false);
$taxonomies = get_taxonomies(array('public' => true, 'show_ui' => true), 'names', 'or');
$taxonomies = (!empty($taxonomies)) ? array_keys($taxonomies): array();
$has_suggestions = false;
foreach($phrase_groups as $phrase_group_type => $phrases){
    // omit the external section if external linking isn't enabled
    if(empty($link_external) && 'external_site' === $phrase_group_type){
        continue;
    }

    // output the spacer if this is the external suggestions
    if('external_site' === $phrase_group_type && !empty($phrases)){
        echo '<div style="border-top: solid 2px #ccd0d4; margin: 0 -13px;"></div>';
    }
?>
<div style="display:none"><div><textarea id="wpil-editor-target"></textarea></div></div>
<table class="wp-list-table widefat fixed striped posts tbl_keywords_x js-table wpil-outbound-links wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" data-wpil-tooltip-read-time="9500" <?php echo Wpil_Toolbox::generate_tooltip_text('outbound-suggestions-table'); ?> id="tbl_keywords" >
    <?php if (!empty($phrases)) : ?>
        <?php $has_suggestions = true; ?>
        <thead>
        <tr class="wpil-suggestion-table-heading">
            <th>
                <div>
                    <b><?php if('internal_site' === $phrase_group_type){ esc_html_e('Add Internal Outbound Links', 'wpil'); }else{ esc_html_e('Add Outbound Links to External Sites', 'wpil'); } ?></b>
                    <br />
                    <div style="margin:5px 0 0 0;">
                        <input type="checkbox" id="select_all"><span style="margin:0 0 0 5px; font-weight:300"><?php esc_html_e('Check All', 'wpil'); ?></span>
                    </div>
                </div>
            </th>
            <th>
                <div>
                    <br />
                    <b><?php esc_html_e('Posts to link to', 'wpil'); ?></b>
                </div>
            </th>
        </tr>
        </thead>
        <tbody id="the-list">
        <?php foreach ($phrases as $key_phrase => $phrase) : ?>
            <tr data-wpil-sentence-id="<?=esc_attr($key_phrase)?>">
                <td class="sentences">
                    <?php foreach ($phrase->suggestions as $suggestion) : ?>
                        <div class="sentence top-level-sentence <?php echo $suggestion->has_ai_scored() && !$suggestion->get_ai_related() ? 'wpil-suggestion-not-related': '';?>" data-id="<?=esc_attr($suggestion->post->id)?>" data-type="<?=esc_attr($suggestion->post->type)?>">
                            <?php //print_r($suggestion->get_ai_score_data()); ?>
                            <div class="wpil_edit_sentence_form">
                                <textarea class="wpil_content"><?=$suggestion->sentence_src_with_anchor?></textarea>
                                <span class="button-primary">Save</span>
                                <span class="button-secondary">Cancel</span>
                                <span> <input type="checkbox" class="wpil-sentence-allow-multiple-links" data-nonce="<?php echo wp_create_nonce(get_current_user_id() . 'allow_multiple_links_editor') ?>">Allow multiple links in sentence</span>
                            </div>
                            <input type="checkbox" name="link_keywords[]" class="chk-keywords" wpil-link-new="">
                            <span class="wpil_sentence_with_anchor"><span class="wpil_sentence" title="<?php esc_attr_e('Double clicking a word will select it.', 'wpil');?>"><?=$suggestion->sentence_with_anchor?></span><span class="dashicons dashicons-image-rotate wpil-reload-sentence-with-anchor" title="<?php esc_attr_e('Click to undo changes', 'wpil'); ?>"></span></span>
                            <span class="wpil_edit_sentence link-form-button">| <a href="javascript:void(0)">Edit Sentence</a></span>
                            <?=!empty(Wpil_Suggestion::$undeletable)?' ('.esc_attr($suggestion->anchor_score).')':''?>
                            <input type="hidden" name="sentence" value="<?=base64_encode($phrase->sentence_src)?>">
                            <input type="hidden" name="custom_sentence" value="">
                            <input type="hidden" name="original_sentence_with_anchor" value="<?php echo base64_encode($suggestion->original_sentence_with_anchor)?>">

                            <?php if (Wpil_Settings::fullHTMLSuggestions()) : ?>
                                <div class="raw_html"><?=htmlspecialchars($suggestion->sentence_src_with_anchor)?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                </td>
                <td>
                    <?php if (count($phrase->suggestions) > 1) : ?>
                        <?php 
                            $index = key($phrase->suggestions);
                            $a_post = $phrase->suggestions[$index]->post;

                            if(null === $index){
                                reset($phrase->suggestions);
                                $index = key($phrase->suggestions);
                            }

                            if(empty($a_post)){
                                continue;
                            }

                            $terms = get_terms(array(
                                'taxonomy' => $taxonomies,
                                'hide_empty' => false,
                                'object_ids' => $a_post->id,
                            ));

                            $categories = array();
                            $tags = array();
                            if(!is_wp_error($terms) && !empty($terms)){
                                foreach($terms as $term){
                                    if(get_taxonomy($term->taxonomy)->hierarchical){
                                        $categories[] = $term->name;
                                    }else{
                                        $tags[] = $term->name;
                                    }
                                }

                                $cats_found = count($categories);
                                $tags_found = count($tags);
                                $categories = implode(', ', $categories);
                                $tags = implode(', ', $tags);
                            }

                            $ai_post_relatedness_score = 0;
                            $post_to_post_score = 0;
                            $sentence_to_post_score = 0;
                            $post_origin = (!isset($a_post->site_url)) ? 'internal': 'external';
                            if($post_origin === 'internal'){
                                $post_to_post_score = Wpil_AI::get_post_relationship_score($post, $a_post);
                                $sentence_to_post_score = Wpil_AI::get_sentence_relationship_score($post, $a_post, Wpil_Suggestion::get_ai_phrase_text($phrase));
                                $ai_post_relatedness_score = (is_numeric($post_to_post_score) && !empty($post_to_post_score)) ? (round($post_to_post_score, 4) * 100) . '%': esc_html__('Unknown', 'wpil');
                                $ai_sentence_relatedness_score = (is_numeric($sentence_to_post_score) && !empty($sentence_to_post_score)) ? (round($sentence_to_post_score, 4) * 100) . '%': esc_html__('Unknown', 'wpil');
                            }

                            $suggestion_datas = array(
                                'data-id="' . esc_attr($a_post->id) . '"',
                                'data-type="' . esc_attr($a_post->type) . '"',
                                'data-post-origin="' . $post_origin . '"',
                                'data-site-url="' . ((isset($a_post->site_url)) ? esc_url($a_post->site_url): '') . '"',

                                'data-wpil-post-published-date="' . strtotime(get_the_date('', $post_id)) . '"',
                                'data-wpil-suggestion-score="' . intval($phrase->suggestions[$index]->total_score) . '"',
                                'data-wpil-ai-post-relatedness-score="' . $post_to_post_score . '"',
                                'data-wpil-ai-sentence-relatedness-score="' . $sentence_to_post_score . '"'
                            );
            
                            // if we're looking at an internal post
                            if(is_a($a_post, 'Wpil_Model_Post')){
                                // include the link stat data to the suggestion datas
                                $suggestion_datas = array_merge($suggestion_datas, array(                                
                                    'data-wpil-inbound-internal-links="' . (int)$a_post->getInboundInternalLinks(true) . '"',
                                    'data-wpil-outbound-internal-links="' . (int)$a_post->getOutboundInternalLinks(true) . '"',
                                    'data-wpil-outbound-external-links="' . (int)$a_post->getOutboundExternalLinks(true) . '"'));
                            }

                            $suggestion_datas = implode(' ', $suggestion_datas);

                        ?>
                        <div class="wpil-collapsible-wrapper">
                            <div class="wpil-collapsible wpil-collapsible-static wpil-links-count">
                                <div class="<?php echo $phrase->suggestions[$index]->has_ai_scored() && !$phrase->suggestions[$index]->get_ai_related() ? 'wpil-suggestion-not-related': '';?>" style="opacity:<?=$phrase->suggestions[$index]->opacity?>" <?php echo $suggestion_datas ?>>
                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('Title:', 'wpil'); ?></strong> <?=esc_html($a_post->getTitle())?></div>
                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('Type: ', 'wpil'); ?></strong> <?=esc_html($a_post->getType())?><br></div>
                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('Published:', 'wpil'); ?></strong> <?=get_the_date('', $a_post->id)?></div>
                                    <div class="suggested-post-data-container"><?php echo (!empty($categories)) ? '<b>' . _n(__('Category: ', 'wpil'), __('Categories: ', 'wpil'), $cats_found) . '</b>' . $categories . '<br>': ''; ?></div>
                                    <div class="suggested-post-data-container"><?php echo (!empty($tags)) ? '<b>' . _n(__('Tag: ', 'wpil'), __('Tags: ', 'wpil'), $tags_found) . '</b>' . $tags . '<br>': ''; ?></div>

                                    <?php if($phrase->suggestions[$index]->has_ai_scored()){ ?>
                                    <div class="suggested-post-data-container"><strong><?php _e('AI Relatedness Score:', 'wpil'); ?></strong> <?php echo $phrase->suggestions[$index]->get_ai_similarity_score() . "/10";?> 
                                        <div class="wpil_help wpil-ai-help">
                                            <i class="dashicons dashicons-format-status"></i>
                                            <div class="wpil-help-text" style="display: none; width: 300px">
                                                <?php _e('Reasoning for Score:', 'wpil'); ?>
                                                <br />
                                                <br />
                                                <?php echo $phrase->suggestions[$index]->get_ai_related_explanation(); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php } ?>

                                    <?php if($post_origin === 'internal' && !empty($ai_post_relatedness_score)){ ?>
                                        <div class="suggested-post-data-container"><?php echo '<b>' . __('Post Match AI Score: ', 'wpil') . '</b>'; echo (!empty($ai_post_relatedness_score)) ? $ai_post_relatedness_score . '<br>': '0%'; ?></div>
                                        <?php if(!empty($ai_use_ai_suggestions)){ ?>
                                        <div class="suggested-post-data-container"><?php echo '<b>' . __('Sentence Match AI Score: ', 'wpil') . '</b>'; echo (!empty($ai_sentence_relatedness_score)) ? $ai_sentence_relatedness_score . '<br>': '0%'; ?></div>
                                        <?php } ?>
                                    <?php } ?>

                                    <?php if(is_a($a_post, 'Wpil_Model_Post')){ ?>
                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('Inbound Internal Links: ', 'wpil'); ?></strong> <?=(int)$a_post->getInboundInternalLinks(true) . '<br>'; ?></div>
                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('Outbound Internal Links: ', 'wpil'); ?></strong> <?=(int)$a_post->getOutboundInternalLinks(true) . '<br>'; ?></div>
                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('Outbound External Links: ', 'wpil'); ?></strong> <?=(int)$a_post->getOutboundExternalLinks(true) . '<br>'; ?></div>
                                    <?php } ?>

                                    <strong><?php esc_html_e('URL:', 'wpil'); ?></strong> <a class="post-slug" target="_blank" href="<?=esc_url($a_post->getLinks()->view)?>"><?php echo esc_html($a_post->getSlug()); ?></a>

                                    <span class="add_custom_link_button link-form-button"> | <a href="javascript:void(0)">Custom Link</a></span>
                                    <span class="wpil_add_link_to_ignore link-form-button"> | <a href="javascript:void(0)">Ignore Link</a></span>
                                </div>
                            </div>
                            <div class="wpil-content" style="display: none;">
                                <ul>
                                    <?php $first = key($phrase->suggestions); ?>
                                    <?php foreach ($phrase->suggestions as $key_suggestion => $suggestion) : ?>
                                        <?php 
                                            $post_published_date = get_the_date('', $suggestion->post->id); 
                                            $terms = get_terms(array(
                                                'taxonomy' => $taxonomies,
                                                'hide_empty' => false,
                                                'object_ids' => $suggestion->post->id,
                                            ));

                                            $categories = array();
                                            $tags = array();
                                            if(!is_wp_error($terms) && !empty($terms)){
                                                foreach($terms as $term){
                                                    if(get_taxonomy($term->taxonomy)->hierarchical){
                                                        $categories[] = $term->name;
                                                    }else{
                                                        $tags[] = $term->name;
                                                    }
                                                }

                                                $cats_found = count($categories);
                                                $tags_found = count($tags);
                                                $categories = implode(', ', $categories);
                                                $tags = implode(', ', $tags);
                                            }

                                            $post_to_post_score = 0;
                                            $sentence_to_post_score = 0;
                                            $ai_post_relatedness_score = 0;
                                            $ai_sentence_relatedness_score = 0;
                                            $post_origin = (!isset($suggestion->post->site_url)) ? 'internal': 'external';
                                            if($post_origin === 'internal'){
                                                $post_to_post_score = Wpil_AI::get_post_relationship_score($post, $suggestion->post);
                                                $sentence_to_post_score = Wpil_AI::get_sentence_relationship_score($post, $suggestion->post, Wpil_Suggestion::get_ai_phrase_text($phrase));
                                                $ai_post_relatedness_score = (is_numeric($post_to_post_score) && !empty($post_to_post_score)) ? (round($post_to_post_score, 4) * 100) . '%': esc_html__('Unknown', 'wpil');
                                                $ai_sentence_relatedness_score = (is_numeric($sentence_to_post_score) && !empty($sentence_to_post_score)) ? (round($sentence_to_post_score, 4) * 100) . '%': esc_html__('Unknown', 'wpil');
                                            
                                            }

                                            $suggestion_datas = array(
                                                'data-id="' . esc_attr($suggestion->post->id) . '"',
                                                'data-type="' . esc_attr($suggestion->post->type) . '"',
                                                'data-post-origin="' . ((!isset($suggestion->post->site_url)) ? 'internal': 'external') . '"',
                                                'data-site-url="' . ((isset($suggestion->post->site_url)) ? esc_url($suggestion->post->site_url): '') . '"',
                                                'data-suggestion="' . esc_attr($key_suggestion) . '"',
                
                                                'data-wpil-post-published-date="' . strtotime($post_published_date) . '"',
                                                'data-wpil-suggestion-score="' . intval($suggestion->total_score) . '"',
                                                'data-wpil-ai-post-relatedness-score="' . $post_to_post_score . '"',
                                                'data-wpil-ai-sentence-relatedness-score="' . $sentence_to_post_score . '"',
                                            );
                            
                                            // if we're looking at an internal post
                                            if(is_a($suggestion->post, 'Wpil_Model_Post')){
                                                // include the link stat data to the suggestion datas
                                                $suggestion_datas = array_merge($suggestion_datas, array(                                
                                                    'data-wpil-inbound-internal-links="' . (int)$suggestion->post->getInboundInternalLinks(true) . '"',
                                                    'data-wpil-outbound-internal-links="' . (int)$suggestion->post->getOutboundInternalLinks(true) . '"',
                                                    'data-wpil-outbound-external-links="' . (int)$suggestion->post->getOutboundExternalLinks(true) . '"'));
                                            }

                                            $suggestion_datas = implode(' ', $suggestion_datas);
                                        ?>
                                        <li class="dated-outbound-suggestion <?php echo $suggestion->has_ai_scored() && !$suggestion->get_ai_related() ? 'wpil-suggestion-not-related': '';?>" data-wpil-post-published-date="<?php echo strtotime($post_published_date); ?>" <?php echo $suggestion_datas; ?>>
                                            <div>
                                                <input type="radio" <?=$key_suggestion==$first?'checked':''?> <?php echo $suggestion_datas; ?>>
                                                <span class="data">
                                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('Title:', 'wpil'); ?></strong> <span class="suggested-post-title" style="opacity:<?=$suggestion->opacity?>"><?=esc_html($suggestion->post->getTitle())?></span></div>
                                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('Type: ', 'wpil'); ?></strong> <?=esc_html($suggestion->post->getType())?><br></div>
                                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('Published:', 'wpil'); ?></strong> <span class="suggested-post-published"><?=esc_attr($post_published_date)?></span></div>
                                                    
                                                    <?php if($suggestion->has_ai_scored()){ ?>
                                                    <div class="suggested-post-data-container"><strong><?php _e('AI Relatedness Score:', 'wpil'); ?></strong> <?php echo $suggestion->get_ai_similarity_score() . "/10";?>
                                                        <div class="wpil_help wpil-ai-help">
                                                            <i class="dashicons dashicons-format-status"></i>
                                                            <div class="wpil-help-text" style="display: none; width: 300px">
                                                                <?php _e('Reasoning for Score:', 'wpil'); ?>
                                                                <br />
                                                                <br />
                                                                <?php echo $suggestion->get_ai_related_explanation(); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php } ?>
                                                    <div class="suggested-post-data-container"><?php echo (!empty($categories)) ? '<b>' . _n(__('Category: ', 'wpil'), __('Categories: ', 'wpil'), $cats_found) . '</b>' . $categories . '<br>': ''; ?></div>
                                                    <div class="suggested-post-data-container"><?php echo (!empty($tags)) ? '<b>' . _n(__('Tag: ', 'wpil'), __('Tags: ', 'wpil'), $tags_found) . '</b>' . $tags . '<br>': ''; ?></div>

                                                    <?php if($post_origin === 'internal' && !empty($ai_post_relatedness_score)){ ?>
                                                        <div class="suggested-post-data-container"><?php echo '<b>' . __('Post Match AI Score: ', 'wpil') . '</b>'; echo (!empty($ai_post_relatedness_score)) ? $ai_post_relatedness_score . '<br>': '0%'; ?></div>
                                                        <?php if(!empty($ai_use_ai_suggestions)){ ?>
                                                            <div class="suggested-post-data-container"><?php echo '<b>' . __('Sentence Match AI Score: ', 'wpil') . '</b>'; echo (!empty($ai_sentence_relatedness_score)) ? $ai_sentence_relatedness_score . '<br>': '0%'; ?></div>
                                                        <?php } ?>
                                                    <?php } ?>

                                                    <?php if(is_a($suggestion->post, 'Wpil_Model_Post')){ ?>
                                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('Inbound Internal Links: ', 'wpil'); ?></strong> <?=(int)$suggestion->post->getInboundInternalLinks(true) . '<br>'; ?></div>
                                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('Outbound Internal Links: ', 'wpil'); ?></strong> <?=(int)$suggestion->post->getOutboundInternalLinks(true) . '<br>'; ?></div>
                                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('Outbound External Links: ', 'wpil'); ?></strong> <?=(int)$suggestion->post->getOutboundExternalLinks(true) . '<br>'; ?></div>
                                                    <?php } ?>

                                                    <div class="suggested-post-data-container"><strong><?php esc_html_e('URL:', 'wpil'); ?></strong> <a class="post-slug" target="_blank" href="<?=esc_url($suggestion->post->getLinks()->view)?>"><?php echo esc_html($suggestion->post->getSlug()); ?></a></div>
                                                </span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php else : ?>
                        <?php
                        if(empty(count($phrase->suggestions))){
                            continue;
                        }
                        $index = key($phrase->suggestions);

                        if(null === $index){
                            reset($phrase->suggestions);
                            $index = key($phrase->suggestions);
                        }

                        $a_post = $phrase->suggestions[$index]->post;

                        if(empty($a_post)){
                            continue;
                        }

                        $terms = get_terms(array(
                            'taxonomy' => $taxonomies,
                            'hide_empty' => false,
                            'object_ids' => $a_post->id,
                        ));

                        $categories = array();
                        $tags = array();
                        if(!is_wp_error($terms) && !empty($terms)){
                            foreach($terms as $term){
                                if(get_taxonomy($term->taxonomy)->hierarchical){
                                    $categories[] = $term->name;
                                }else{
                                    $tags[] = $term->name;
                                }
                            }

                            $cats_found = count($categories);
                            $tags_found = count($tags);
                            $categories = implode(',', $categories);
                            $tags = implode(',', $tags);
                        }

                            $post_to_post_score = 0;
                            $sentence_to_post_score = 0;
                            $ai_post_relatedness_score = 0;
                            $ai_sentence_relatedness_score = 0;
                            $post_origin = (!isset($a_post->site_url)) ? 'internal': 'external';
                            if($post_origin === 'internal'){
                                $post_to_post_score = Wpil_AI::get_post_relationship_score($post, $a_post);
                                $sentence_to_post_score = Wpil_AI::get_sentence_relationship_score($post, $a_post, Wpil_Suggestion::get_ai_phrase_text($phrase));
                                $ai_post_relatedness_score = (is_numeric($post_to_post_score) && !empty($post_to_post_score)) ? (round($post_to_post_score, 4) * 100) . '%': esc_html__('Unknown', 'wpil');
                                $ai_sentence_relatedness_score = (is_numeric($sentence_to_post_score) && !empty($sentence_to_post_score)) ? (round($sentence_to_post_score, 4) * 100) . '%': esc_html__('Unknown', 'wpil');
                                
                            }

                            $suggestion_datas = array(
                                'data-id="' . esc_attr($a_post->id) . '"',
                                'data-type="' . esc_attr($a_post->type) . '"',
                                'data-post-origin="' . $post_origin . '"',
                                'data-site-url="' . (($post_origin === 'external') ? esc_url($a_post->site_url): '') . '"',

                                'data-wpil-post-published-date="' . strtotime(get_the_date('', $a_post->id)) . '"',
                                'data-wpil-suggestion-score="' . intval($phrase->suggestions[$index]->total_score) . '"',
                                'data-wpil-ai-post-relatedness-score="' . $post_to_post_score . '"',
                                'data-wpil-ai-sentence-relatedness-score="' . $sentence_to_post_score . '"',
                            );
            
                            // if we're looking at an internal post
                            if(is_a($a_post, 'Wpil_Model_Post')){
                                // include the link stat data to the suggestion datas
                                $suggestion_datas = array_merge($suggestion_datas, array(                                
                                    'data-wpil-inbound-internal-links="' . (int)$a_post->getInboundInternalLinks(true) . '"',
                                    'data-wpil-outbound-internal-links="' . (int)$a_post->getOutboundInternalLinks(true) . '"',
                                    'data-wpil-outbound-external-links="' . (int)$a_post->getOutboundExternalLinks(true) . '"'));
                            }

                            $suggestion_datas = implode(' ', $suggestion_datas);
                        ?>
                        <div style="opacity:<?=$phrase->suggestions[$index]->opacity?>" class="suggestion dated-outbound-suggestion <?php echo $phrase->suggestions[$index]->has_ai_scored() && !$phrase->suggestions[$index]->get_ai_related() ? 'wpil-suggestion-not-related': '';?>" <?php echo $suggestion_datas; ?>>
                            <div class="suggested-post-data-container"><strong><?php esc_html_e('Title:', 'wpil'); ?></strong> <span class="suggested-post-title"><?=esc_html($a_post->getTitle())?></span></div>
                            <div class="suggested-post-data-container"><strong><?php esc_html_e('Type: ', 'wpil'); ?></strong> <?=esc_html($a_post->getType())?><br></div>
                            <div class="suggested-post-data-container"><strong><?php esc_html_e('Published:', 'wpil'); ?></strong> <?=get_the_date('', $a_post->id)?></div>
                            <?php if($phrase->suggestions[$index]->has_ai_scored()){ ?>
                            <div class="suggested-post-data-container"><strong><?php _e('AI Relatedness Score:', 'wpil'); ?></strong> <?php echo $phrase->suggestions[$index]->get_ai_similarity_score() . "/10";?>
                                <div class="wpil_help wpil-ai-help">
                                    <i class="dashicons dashicons-format-status"></i>
                                    <div class="wpil-help-text" style="display: none; width: 300px">
                                        <?php _e('Reasoning for Score:', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php echo $phrase->suggestions[$index]->get_ai_related_explanation(); ?>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            <div class="suggested-post-data-container"><?php echo (!empty($categories)) ? '<b>' . _n(__('Category: ', 'wpil'), __('Categories: ', 'wpil'), $cats_found) . '</b>' . $categories . '<br>': ''; ?></div>
                            <div class="suggested-post-data-container"><?php echo (!empty($tags)) ? '<b>' . _n(__('Tag: ', 'wpil'), __('Tags: ', 'wpil'), $tags_found) . '</b>' . $tags . '<br>': ''; ?></div>
                            
                            <?php if($post_origin === 'internal' && !empty($ai_post_relatedness_score)){ ?>
                                <div class="suggested-post-data-container"><?php echo '<b>' . __('Post Match AI Score: ', 'wpil') . '</b>'; echo (!empty($ai_post_relatedness_score)) ? $ai_post_relatedness_score . '<br>': '0%'; ?></div>
                                <?php if(!empty($ai_use_ai_suggestions)){ ?>
                                    <div class="suggested-post-data-container"><?php echo '<b>' . __('Sentence Match AI Score: ', 'wpil') . '</b>'; echo (!empty($ai_sentence_relatedness_score)) ? $ai_sentence_relatedness_score . '<br>': '0%'; ?></div>
                                <?php } ?>
                            <?php } ?>

                            <?php if(is_a($a_post, 'Wpil_Model_Post')){ ?>
                            <div class="suggested-post-data-container"><strong><?php esc_html_e('Inbound Internal Links: ', 'wpil'); ?></strong> <?=(int)$a_post->getInboundInternalLinks(true) . '<br>'; ?></div>
                            <div class="suggested-post-data-container"><strong><?php esc_html_e('Outbound Internal Links: ', 'wpil'); ?></strong> <?=(int)$a_post->getOutboundInternalLinks(true) . '<br>'; ?></div>
                            <div class="suggested-post-data-container"><strong><?php esc_html_e('Outbound External Links: ', 'wpil'); ?></strong> <?=(int)$a_post->getOutboundExternalLinks(true) . '<br>'; ?></div>
                            <?php } ?>

                            <strong><?php esc_html_e('URL:', 'wpil'); ?></strong> <a class="post-slug" target="_blank" href="<?=esc_url($a_post->getLinks()->view)?>"><?php echo urldecode($a_post->getSlug()); ?></a>
                            <span class="add_custom_link_button link-form-button"> | <a href="javascript:void(0)">Custom Link</a></span>
                            <span class="wpil_add_link_to_ignore link-form-button"> | <a href="javascript:void(0)">Ignore Link</a></span>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
            <tr class="wpil-no-posts-in-range" style="display:none">
                <td>No suggestions found</td>
            </tr>
        </tbody>
    <?php else : ?>
        <?php
            if('external_site' === $phrase_group_type){
                echo '<div style="border-top: solid 2px #ccd0d4; margin: 0 -13px;"></div>';
            }
        ?>
        <thead>
            <tr class="wpil-suggestion-table-heading">
                <th>
                    <div>
                        <b><?php if('internal_site' === $phrase_group_type){ esc_html_e('Add Internal Outbound Links', 'wpil'); }else{ esc_html_e('Add Outbound Links to External Sites', 'wpil'); } ?></b>
                        <br />
                    </div>
                </th>
                <th>
                    <div>
                        <br />
                        <b><?php esc_html_e('Posts to link to', 'wpil'); ?></b>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php esc_html_e('No suggestions found', 'wpil'); ?></td>
            </tr>
        </tbody>
    <?php endif; ?>
</table>
<?php
}

// if there weren't any internal or external suggestions, 
if(!$has_suggestions){ ?>
<table class="wp-list-table widefat fixed striped posts tbl_keywords_x js-table wpil-outbound-links" id="tbl_keywords">
    <tbody>
        <tr>
            <td><?php esc_html_e('No suggestions found', 'wpil'); ?></td>
        </tr>
    </tbody>
</table>
<?php } ?>