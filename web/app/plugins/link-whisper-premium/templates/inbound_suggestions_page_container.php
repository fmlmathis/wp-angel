<form method="post" action="">
    <div id="wpil-inbound-suggestions-head-controls">
        <div style="margin-bottom: 15px; display: flex; justify-content: space-between;">
            <div id="wpil-inbound-suggestions-head-controls-left">
                <input type="hidden" class="wpil-suggestion-input wpil-suggestions-can-be-regenerated" value="0" data-suggestion-input-initial-value="0">
                <?php if(empty($source_id)) {?>
                    <?php if(!empty($has_parent)){ ?>
                        <div class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" data-wpil-tooltip-read-time="5500" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-same-parent'); ?>>
                            <input style="margin-bottom: -5px;" type="checkbox" name="same_parent" id="field_same_parent" class="wpil-suggestion-input" data-suggestion-input-initial-value="<?php echo !empty($same_parent) ? 1: 0;?>" <?=(isset($same_parent) && !empty($same_parent)) ? 'checked' : ''?>> <label for="field_same_parent"><?php esc_html_e('Only Show Link Suggestions From Posts With the Same Page Parent as This Post', 'wpil'); ?></label>
                        </div>
                    <?php } ?>
                    <?php if(!empty($categories)){ ?>
                    <div class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" data-wpil-tooltip-read-time="5500" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-same-category'); ?>>
                        <input style="margin-bottom: -5px;" type="checkbox" name="same_category" id="field_same_category" class="wpil-suggestion-input" data-suggestion-input-initial-value="<?php echo !empty($same_category) ? 1: 0;?>" <?=(isset($same_category) && !empty($same_category)) ? 'checked' : ''?>> <label for="field_same_category"><?php esc_html_e('Only Show Link Suggestions in the Same Category as This Post', 'wpil'); ?></label>
                        <br>
                        <div class="same_category-aux wpil-aux">
                            <select multiple name="wpil_selected_category" class="wpil-suggestion-input wpil-suggestion-multiselect" data-suggestion-input-initial-value="<?php echo implode(',', $selected_categories);?>" style="width: 400px;">
                                <?php foreach ($categories as $cat){ ?>
                                    <option value="<?php echo $cat->term_taxonomy_id; ?>" <?php echo (in_array($cat->term_taxonomy_id, $selected_categories, true) || empty($selected_categories))?'selected':''; ?>><?php esc_html_e($cat->name)?></option>
                                <?php } ?>
                            </select>
                            <br>
                            <br>
                        </div>
                    </div>
                    <br class="same_category-aux wpil-aux">
                    <?php if(!empty($same_category)){ ?>
                        <style>
                            #wpil-inbound-suggestions-head-controls .same_category-aux{
                                display: inline-block;
                            }
                        </style>
                        <?php } ?>
                    <?php } ?>
                    <?php if(!empty($tags)){ ?>
                    <div class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" data-wpil-tooltip-read-time="5500" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-same-tags'); ?>>
                        <input type="checkbox" name="same_tag" id="field_same_tag" class="wpil-suggestion-input" data-suggestion-input-initial-value="<?php echo !empty($same_tag) ? 1: 0;?>"  <?=!empty($same_tag) ? 'checked' : ''?>> <label for="field_same_tag"><?php esc_html_e('Only Show Link Suggestions with the Same Tag as This Post', 'wpil'); ?></label>
                        <br>
                        <div class="same_tag-aux wpil-aux">
                            <select multiple name="wpil_selected_tag" class="wpil-suggestion-input wpil-suggestion-multiselect" data-suggestion-input-initial-value="<?php echo implode(',', $selected_tags);?>" style="width: 400px;">
                                <?php foreach ($tags as $tag){ ?>
                                    <option value="<?php echo $tag->term_taxonomy_id; ?>" <?php echo (in_array($tag->term_taxonomy_id, $selected_tags, true))?'selected':''; ?>><?php esc_html_e($tag->name)?></option>
                                <?php } ?>
                            </select>
                            <br>
                            <br>
                        </div>
                    </div>
                    <br class="same_tag-aux wpil-aux">
                        <?php if(!empty($same_tag)){ ?>
                        <style>
                            #wpil-inbound-suggestions-head-controls .same_tag-aux{
                                display: inline-block;
                            }
                        </style>
                        <?php } ?>
                    <?php } ?>
                    <div class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" data-wpil-tooltip-read-time="5500" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-select-post-type'); ?>>
                        <input type="checkbox" name="select_post_types" id="field_select_post_types" class="wpil-suggestion-input" data-suggestion-input-initial-value="<?php echo !empty($select_post_types) ? 1: 0;?>" <?=!empty($select_post_types) ? 'checked' : ''?>> <label for="field_select_post_types"><?php esc_html_e('Select the Post Types to use in Suggestions', 'wpil'); ?></label>
                        <br>
                        <div class="select_post_types-aux wpil-aux">
                            <select multiple name="selected_post_types" class="wpil-suggestion-input wpil-suggestion-multiselect" data-suggestion-input-initial-value="<?php echo implode(',', $selected_post_types);?>" style="width: 400px;">
                                <?php foreach ($post_types as $post_type => $lable){ ?>
                                    <option value="<?php echo $post_type; ?>" <?php echo (in_array($post_type, $selected_post_types, true))?'selected':''; ?>><?php esc_html_e(ucfirst($lable))?></option>
                                <?php } ?>
                            </select>
                            <br>
                            <br>
                        </div>
                    </div>
                    <div style="display: none;">
                        <?php if(false && !empty(Wpil_Settings::getOpenAIKey()) && !empty(Wpil_AI::get_calculated_embedding_data($post->id, $post->type))){  ?>
                            <input type="range" name="ai_relatedness_threshold" id="field_ai_relatedness_threshold" class="wpil-suggestion-input wpil-thick-range" min="<?php echo Wpil_Settings::get_ai_suggestion_relatedness_threshold(); ?>" max="1" step="0.001" value="<?php echo $ai_relatedness_threshold;?>" data-suggestion-input-initial-value="<?php echo $ai_relatedness_threshold;?>"><label for="field_ai_relatedness_threshold"><?php _e('Only Show Suggestions for Posts Which Are', 'wpil'); ?></label>
                            <div>
                                <span class="wpil-embedding-relatedness-threshold"><?php echo round($ai_relatedness_threshold * 100, 3) . '%'; ?></span><span> Similar to This Post</span>
                            </div>
                        <?php }  ?>
                    </div>
                    <br />
                    <br />
                    <div>
                        <?php 
                        $has_key = !empty(Wpil_Settings::getOpenAIKey());
                        $show_toggle = true;
                        if(Wpil_Settings::can_do_ai_powered_suggestions()){
                            $possible = true;
                            $message = '(AI Powered Suggestions will incur a very small charge from OpenAI)';
                        } else if($has_key && Wpil_AI::is_free_oai_subscription()){
                            $possible = false;
                            $settings = '<a href="https://linkwhisper.com/knowledge-base/how-do-i-get-my-open-ai-key/#setting-up-a-payment-method" target="_blank">' . esc_html__('put some money on your OpenAI API key', 'wpil') . '</a>';
                            $message = sprintf(esc_html__('To use the AI Powered Suggestions, please %s.', 'wpil'), $settings);
                        } else if($has_key && !in_array('4', Wpil_Settings::get_selected_ai_batch_processes())) {
                            $possible = false;
                            $settings = '<a href="' . admin_url("admin.php?page=link_whisper_settings&tab=ai-settings") . '">' . esc_html__('go to the AI Settings', 'wpil') . '</a>';
                            $message = sprintf(esc_html__('To use the AI Powered Suggestions, please enable "AI Relation Analysis" from the Link Whisper AI Settings and perform a scan of the site.', 'wpil'), $settings);
                        } else if($has_key) {
                            $possible = false;
                            $settings = '<a href="' . admin_url("admin.php?page=link_whisper_settings&tab=ai-settings") . '">' . esc_html__('go to the AI Settings', 'wpil') . '</a>';
                            $message = 
                                esc_html__('Unfortunately, Link Whisper hasn\'t processed enough posts with the "AI Relation Analysis" to be able to use the AI-Powered Suggestions effectively.', 'wpil') . 
                                '<br><br>' . 
                                esc_html__('At a minimum, Link Whisper needs 10% of the posts to be processed.', 'wpil') .
                                '<br><br>' . 
                                sprintf(esc_html__('Please %s, and run the AI Processing to scan the site.', 'wpil'), $settings);
                            
                        } else {
                            $possible = false;
                            $show_toggle = false;
                            $message = esc_html__('Want to use suggestions powered by OpenAI?!', 'wpil') . '<br><br>' . sprintf(esc_html__('Please connect Link Whisper to %s, and then %s.', 'wpil'), '<a href="https://linkwhisper.com/knowledge-base/how-do-i-get-my-open-ai-key/">' . __('your OpenAI account', 'wpil') . '</a>', '<a href="https://linkwhisper.com/knowledge-base/how-do-i-have-openai-process-my-sites-data/">' . __('run an AI Scan', 'wpil') . '</a>');
                        } ?>
                        <div class="ai-powered-suggestion-container">
                            <?php if($show_toggle){ ?>
                            <label style="font-weight: bold; font-size: 16px !important; display: inline-block; <?php echo (!$possible) ? 'font-style: italic; opacity: 0.9;': '';?>"><?php _e('Enable AI-Powered Suggestions', 'wpil'); ?><input type="checkbox" id="wpil_use_ai_suggestions" style="margin-left:10px;" class="wpil-slider-checkbox wpil-suggestion-input" data-nonce="<?php echo wp_create_nonce(wp_get_current_user()->ID . 'ai-suggestion-change-nonce'); ?>" data-suggestion-input-initial-value="<?php echo !empty($ai_use_ai_suggestions) ? 1: 0;?>" <?=!empty($ai_use_ai_suggestions && $possible)?'checked':''?> value="1" <?php echo (!$possible) ? 'disabled': '';?> /></label>
                            <?php } ?>
                            <div style="margin: 15px 0 0 0; font-style: italic; font-weight: 600; max-width: 340px;">
                                <?php echo $message; ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <br />
                <br />
                <button id="wpil-regenerate-suggestions" class="button disabled" disabled><?php esc_html_e('Regenerate Suggestions', 'wpil'); ?></button>
                <br>
                <br>
                <!--<a class="wpil-export-suggestions" data-export-type="excel" data-suggestion-type="inbound" data-type="<?php echo esc_attr($post->type); ?>" data-id="<?php echo esc_attr($post->id); ?>" data-nonce="<?php echo wp_create_nonce(get_current_user_id() . 'export-suggestions-' . $post->id); ?>" href="#">Export Suggestions to Excel</a><br>-->
                <a class="wpil-export-suggestions" data-export-type="csv" data-suggestion-type="inbound" data-type="<?php echo esc_attr($post->type); ?>" data-id="<?php echo esc_attr($post->id); ?>" data-nonce="<?php echo wp_create_nonce(get_current_user_id() . 'export-suggestions-' . $post->id); ?>" href="#">Export Suggestions to CSV</a><br>
                <?php if(!empty($select_post_types)){ ?>
                <style>
                    #wpil-inbound-suggestions-head-controls .select_post_types-aux{
                        display: inline-block;
                    }
                </style>
                <?php } ?>
                <script>
                    jQuery('.wpil-suggestion-multiselect').select2();
                </script>
                <?php if (!empty($phrases)){ ?>
                <br />
                <div style="display: inline-block;" class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-filter-date'); ?>>
                    <label for="wpil-inbound-daterange" style="font-weight: bold; font-size: 16px !important; margin: 18px 0 8px; display: block; display: inline-block;"><?php esc_html_e('Filter Displayed Posts by Published Date', 'wpil'); ?></label><br/>
                    <input id="wpil-inbound-daterange" type="text" name="daterange" class="wpil-date-range-filter" value="<?php echo date($filter_time_format, strtotime('Jan 1, 2000')) . ' - ' . date($filter_time_format, strtotime('today')); ?>">
                </div>
                <script>
                    var sentences = jQuery('.wpil-inbound-sentence');
                    jQuery('#wpil-inbound-daterange').on('apply.wpil-daterangepicker, hide.wpil-daterangepicker', function(ev, picker) {
                        var format = '<?php echo Wpil_Toolbox::convert_date_format_for_js() ?>';
                        jQuery(this).val(picker.startDate.format(format) + ' - ' + picker.endDate.format(format));
                        var start = picker.startDate.unix();
                        var end = picker.endDate.unix();

                        sentences.each(function(index, element){
                            var elementTime = jQuery(element).data('wpil-post-published-date');
                            if(!start || (start < elementTime && elementTime < end)){
                                jQuery(element).css({'display': 'table-row'});
                            }else{
                                jQuery(element).css({'display': 'none'}).find('input.chk-keywords').prop('checked', false);
                            }
                        });

                        // handle the results of hiding any posts
                        handleHiddenPosts();
                    });

                    jQuery('#wpil-inbound-daterange').on('cancel.wpil-daterangepicker', function(ev, picker) {
                        jQuery(this).val('');
                        sentences.each(function(index, element){
                            jQuery(element).css({'display': 'table-row'});
                        });
                    });

                    jQuery('#wpil-inbound-daterange').daterangepicker({
                        autoUpdateInput: false,
                        linkedCalendars: false,
                        locale: {
                            cancelLabel: 'Clear',
                            format: '<?php echo Wpil_Toolbox::convert_date_format_for_js() ?>'
                        }
                    });

                    /**
                     * Handles the table display elements when the date range changes
                     **/
                    function handleHiddenPosts(){
                        if(jQuery('.inbound-checkbox:visible').length < 1){
                            // hide the table elements
                            jQuery('.wp-list-table thead, #inbound_suggestions_button, #inbound_suggestions_button_2').css({'display': 'none'});
                            // make sure the "Check All" box is unchecked
                            jQuery('.inbound-check-all-col input').prop('checked', false);
                            // show the "No matches" message
                            jQuery('.wpil-no-posts-in-range').css({'display': 'table-row'});
                        }else{
                            // show the table elements
                            jQuery('.wp-list-table thead').css({'display': 'table-header-group'});
                            jQuery('#inbound_suggestions_button, #inbound_suggestions_button_2').css({'display': 'inline-block'});
                            // hide the "No matches" message
                            jQuery('.wpil-no-posts-in-range').css({'display': 'none'});
                        }
                    }

                    jQuery('#wpil-inbound-suggestions-sorting-select, #wpil-inbound-suggestions-sorting-select-direction').on('change', function(){
                        var sort = jQuery('#wpil-inbound-suggestions-sorting-select').val(),
                            direction = jQuery('#wpil-inbound-suggestions-sorting-select-direction').val();
                            sortTableByUserSelection(sort, direction);
                    });

                    /**
                     * Sorts the Inbound Suggestion table by the order the user has selected
                     **/
                    function sortTableByUserSelection(sort, direction) {
                        var table = document.getElementById('tbl_keywords');
                        var tbody = table.querySelector('tbody');

                        // Detach tbody to avoid live DOM manipulations
                        var detachedTbody = tbody.parentNode.removeChild(tbody);

                        var rows = Array.from(detachedTbody.querySelectorAll('tr'));
                        rows.sort(function(a, b) {

                            if(direction === 'desc'){
                                return parseFloat(b.getAttribute('data-' + sort)) - parseFloat(a.getAttribute('data-' + sort));
                            }else{
                                return parseFloat(a.getAttribute('data-' + sort)) - parseFloat(b.getAttribute('data-' + sort));
                            }
                        });

                        // Re-append rows to the detached tbody
                        rows.forEach(function(row) {
                            detachedTbody.appendChild(row);
                        });

                        // Reattach the tbody to the table
                        table.appendChild(detachedTbody);
                    }
                </script>
                <br>
                <br>
                <?php if (!empty($phrases)){ ?>
                    <button id="inbound_suggestions_button" class="sync_linking_keywords_list button-primary" data-id="<?=esc_attr($post->id)?>" data-type="<?=esc_attr($post->type)?>" data-page="inbound">Add links</button>
                <?php } ?>
                <?php $same_category = !empty(get_user_meta(get_current_user_id(), 'wpil_same_category_selected', true)); ?>
            </div>
            <div id="wpil-inbound-suggestions-head-controls-right">
                <div style="display: flex; flex-direction: column;">
                    <div class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" style="display: flex; flex-direction: column;" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-filter-keywords'); ?>>
                        <label for="suggestion_filter_field" style="font-weight: bold; font-size: 16px !important; margin: 18px 0 8px; display: block; display: inline-block;">Filter Suggestions by Keyword</label>
                        <textarea id="suggestion_filter_field"></textarea>
                    </div>
                    <br>
                    <?php if(!empty(Wpil_Settings::getOpenAIKey()) && !empty(Wpil_AI::get_calculated_embedding_data($post->id, $post->type))){  ?>
                    <div class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" style="display: flex; flex-direction: column;" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-filter-ai-score'); ?>>
                        <label for="field_ai_relatedness_threshold" style="font-weight: bold; font-size: 16px !important; margin: 18px 0 8px; display: block; display: inline-block;">
                            <?php _e('Filter Suggestions by AI Score', 'wpil'); ?>
                        </label>
                        <input type="range" name="ai_relatedness_threshold" id="field_ai_relatedness_threshold" class="wpil-suggestion-input wpil-thick-range" min="<?php echo Wpil_Settings::get_ai_suggestion_relatedness_threshold(); ?>" max="1" step="0.001" value="0.00" data-suggestion-input-initial-value="<?php echo $ai_relatedness_threshold;?>">
                        <div>
                            <span><?php _e('Minimum Relatedness Score:', 'wpil'); ?> </span><span class="wpil-embedding-relatedness-threshold"><?php echo (Wpil_Settings::get_ai_suggestion_relatedness_threshold() * 100) . '%'; ?></span>
                        </div>
                    </div>
                    <br>
                    <?php }  ?>
                    <div class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" style="display: flex; flex-direction: column;" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-sort-suggestions'); ?>>
                        <label for="wpil-inbound-suggestions-sorting-select" style="font-weight: bold; font-size: 16px !important; margin: 18px 0 8px; display: block; display: inline-block;"><?php esc_html_e('Sort Inbound Suggestions By', 'wpil'); ?></label>
                        <div>
                            <select id="wpil-inbound-suggestions-sorting-select">
                            <?php if(Wpil_AI::has_calculated_embedding_data()){ ?>
                                <option value="wpil-ai-post-relatedness-score"><?php esc_html_e('Post Match AI Score', 'wpil'); ?></option>
                                <option value="wpil-ai-sentence-relatedness-score" style="display:none"><?php esc_html_e('Sentence Match AI Score', 'wpil'); ?></option>
                            <?php } ?>
                                <option value="wpil-suggestion-score"><?php esc_html_e('Suggestion Score', 'wpil'); ?></option>
                                <option value="wpil-post-published-date"><?php esc_html_e('Publish Date', 'wpil'); ?></option>
                                <option value="wpil-inbound-internal-links"><?php esc_html_e('Inbound Internal Links', 'wpil'); ?></option>
                                <option value="wpil-outbound-internal-links"><?php esc_html_e('Outbound Internal Links', 'wpil'); ?></option>
                                <option value="wpil-outbound-external-links"><?php esc_html_e('Outbound External Links', 'wpil'); ?></option>
                            </select>
                            <select id="wpil-inbound-suggestions-sorting-select-direction">
                                <option value="desc"><?php esc_html_e('Desc', 'wpil'); ?></option>
                                <option value="asc"><?php esc_html_e('Asc', 'wpil'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
        <?php require WP_INTERNAL_LINKING_PLUGIN_DIR . 'templates/table_inbound_suggestions.php'?>
        <?php if (!empty($phrases)){ ?>
        <button id="inbound_suggestions_button_2" class="sync_linking_keywords_list button-primary" data-id="<?=esc_attr($post->id)?>" data-type="<?=esc_attr($post->type)?>" data-page="inbound">Add links</button>
        <?php } ?>
</form>