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
                    <br>
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
                    <br />
                    <div>
                        <?php if(false && !empty(Wpil_Settings::getOpenAIKey()) && !empty(Wpil_AI::get_calculated_embedding_data($post->id, $post->type))){  ?>
                            <input type="range" name="ai_relatedness_threshold" id="field_ai_relatedness_threshold" class="wpil-suggestion-input wpil-thick-range" min="0" max="1" step="0.001" value="<?php echo $ai_relatedness_threshold;?>" data-suggestion-input-initial-value="<?php echo $ai_relatedness_threshold;?>"><label for="field_ai_relatedness_threshold"><?php _e('Only Show Suggestions for Posts Which Are', 'wpil'); ?></label>
                            <div>
                                <span class="wpil-embedding-relatedness-threshold"><?php echo round($ai_relatedness_threshold * 100, 3) . '%'; ?></span><span> Similar to This Post</span>
                            </div>
                        <?php }  ?>
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
                        <input type="range" name="ai_relatedness_threshold" id="field_ai_relatedness_threshold" class="wpil-suggestion-input wpil-thick-range" min="0" max="1" step="0.001" value="0.00" data-suggestion-input-initial-value="<?php //echo $ai_relatedness_threshold;?>">
                        <div>
                            <span><?php _e('Minimum Relatedness Score:', 'wpil'); ?> </span><span class="wpil-embedding-relatedness-threshold"><?php echo '0.00%'; ?></span>
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