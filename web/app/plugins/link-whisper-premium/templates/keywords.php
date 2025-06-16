<div class="wrap wpil-report-page wpil_styles">
<style type="text/css">
        <?php
            $num = 1;

            $options = get_user_meta(get_current_user_id(), 'wpil_keyword_options', true);
            $select_links_active = Wpil_Keyword::keywordLinkSelectActive();

            if($select_links_active && ( empty($options) || (!empty($options) && isset($options['hide_select_links_column']) && $options['hide_select_links_column'] === 'off') ) ){
                $num += 1;
            }

            switch ($num) {
                case '2':
                    ?>
                    tr .wpil-dropdown-column:nth-of-type(2n+2) .wpil-content{
                        width: calc(200% + 50px);
                        position: relative;
                        right: 0;
                    }

                    tr .wpil-dropdown-column:nth-of-type(2n+2) .insert-selected-autolinks{
                        width: 200%;
                    }

                    tr .wpil-dropdown-column:nth-of-type(2n+3) .wpil-content{
                        width: calc(200% + 50px);
                        position: relative;
                        right: calc(100% + 60px);
                    }

                    .rtl tr .wpil-dropdown-column:nth-of-type(2n+3) .wpil-content {
                        left: calc(100% + 60px);
                        right: initial;
                    }
                    <?php
                break;
            }
            ?>
    </style>
    <?=Wpil_Base::showVersion()?>
    <h1 class="wp-heading-inline wpil-is-tooltipped wpil-no-overlay wpil-no-scale" data-wpil-tooltip-read-time="2500" <?php echo Wpil_Toolbox::generate_tooltip_text('autolinking-intro'); ?>><?php esc_html_e('Auto-Linking','wpil'); ?></h1>
    <hr class="wp-header-end">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div id="post-body-content" style="position: relative;">
                <input id="wpil-object-cache-flush-nonce" type="hidden" value="<?php echo wp_create_nonce('wpil-flush-object-cache'); ?>" />
                <input id="wpil-autolink-rule-delete-de-autolink" type="hidden" value="0">
                <div id="wpil_keywords_table">
                    <form  class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-target-child wpil-tooltip-target.search-box" data-wpil-tooltip-read-time="2500" <?php echo Wpil_Toolbox::generate_tooltip_text('autolinking-search'); ?>>
                        <input type="hidden" name="page" value="link_whisper_keywords" />
                        <?php $table->search_box('Search', 'search'); ?>
                    </form>
                    <div method="post" id="add_keyword_form">
                        <div class="wpil-is-tooltipped" data-wpil-tooltip-read-time="4500" <?php echo Wpil_Toolbox::generate_tooltip_text('autolinking-create-one'); ?>>
                            <input type="text" name="keyword" placeholder="Keyword">
                            <input type="text" name="link" placeholder="URL">
                        </div>
                        <div class="progress_panel loader">
                            <div class="progress_count"></div>
                        </div>
                        <a href="javascript:void(0)" class="button-primary single-autolink-create"><?php esc_html_e('Create Autolink Rule', 'wpil')?></a>
                        <br />
                        <br />
                        <div class="wpil-autolink-bulk-action-container wpil-is-tooltipped" data-wpil-tooltip-read-time="5500" <?php echo Wpil_Toolbox::generate_tooltip_text('autolinking-bulk-settings'); ?>>
                            <select class="wpil-autolink-bulk-action-selector" style="height: 39px; margin-top: -3px; padding: 0px 20px 0 10px;">
                                <option value="0"><?php esc_html_e('Bulk Action', 'wpil'); ?></option>
                                <option value="bulk-create"><?php esc_html_e('Bulk Create Autolink Rules', 'wpil'); ?></option>
                                <option value="refresh-selected"><?php esc_html_e('Refresh Selected Rules', 'wpil'); ?></option>
                                <option value="export-rules"><?php esc_html_e('Export Selected Rules', 'wpil'); ?></option>
                                <option value="delete-selected"><?php esc_html_e('Delete Selected Rules', 'wpil'); ?></option>
                            </select>
                            <a href="javascript:void(0)" class="button-primary wpil-autolink-do-bulk-action"><?php esc_html_e('Do Bulk Action', 'wpil'); ?></a>
                        </div>
                    </div>
                    <form method="post" class="wpil_keywords_settings_form">
                        <div id="wpil_keywords_settings" class="wpil-is-tooltipped wpil-no-scale" data-wpil-tooltip-read-time="3500" <?php echo Wpil_Toolbox::generate_tooltip_text('autolinking-create-one-settings'); ?>>
                            <i class="dashicons dashicons-admin-generic"></i>
                            <div class="block">
                                <input type="hidden" name="wpil_keywords_add_same_link" value="0" />
                                <input type="checkbox" id="wpil_keywords_add_same_link" name="wpil_keywords_add_same_link" <?=get_option('wpil_keywords_add_same_link')==1?'checked':''?> value="1" />
                                <label for="wpil_keywords_add_same_link"><?php esc_html_e('Add link if post already has this link?', 'wpil'); ?></label>
                                <br>
                                <br>
                                <input type="hidden" name="wpil_keywords_link_once" value="0" />
                                <input type="checkbox" id="wpil_keywords_link_once" name="wpil_keywords_link_once" checked="checked" value="1" />
                                <label for="wpil_keywords_link_once"><?php esc_html_e('Only link once per post', 'wpil'); ?></label>
                                <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                    <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                    <div>
                                        <?php esc_html_e('By default, Link Whisper only inserts a link with a specific URL once per post. If a post already has a link with the URL, Link Whisper won\'t add another one to it.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php esc_html_e('However, you can override this rule by activating the "Add link if post already has this link?" setting. When that setting is active, an autolink can be inserted in a page that already has a link with the same URL.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php esc_html_e('To keep the autolinks from inserting in every available keyword, this setting makes sure an autolink can only be inserted once per post. Since turning this "off" can have significant effects on a site, this setting isn\'t saved with the "Save" button and defaults to "On" for new auto linking rules.', 'wpil'); ?>
                                    </div>
                                </div>
                                <br>
                                <br>
                                <input type="hidden" name="wpil_keywords_restrict_to_live" value="0" />
                                <input type="checkbox" id="wpil_keywords_restrict_to_live" name="wpil_keywords_restrict_to_live" <?php echo (get_option('wpil_keywords_restrict_to_live', 1) == 1) ? 'checked': ''; ?> value="1" />
                                <label for="wpil_keywords_restrict_to_live"><?php esc_html_e('Only create internal links when target is published?', 'wpil'); ?></label>
                                <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                    <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                    <div>
                                        <?php esc_html_e('By default, Autolinks are not inserted if the they point to a post on this site the post is not published, ensuring links lead to accessible content.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php esc_html_e('You can turn this off by unchecking this option, and Link Whisper will insert Autolinks even if the target post is unpublished.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php esc_html_e('Category archive pages are always consided "published", so they are always linkable.', 'wpil'); ?>
                                    </div>
                                </div>
                                <br>
                                <br>
                                <input type="hidden" name="wpil_keywords_force_insert" value="0" />
                                <input type="checkbox" id="wpil_keywords_force_insert" name="wpil_keywords_force_insert" value="1" />
                                <label for="wpil_keywords_force_insert"><?php esc_html_e('Override "One Link per Sentence" rule?', 'wpil'); ?></label>
                                <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                    <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                    <div><?php esc_html_e('By default, Link Whisper only inserts one link per sentence. If a sentence already has a link, Link Whisper won\'t add another one to it. This option allows you to override the rule so autolinks can be inserted in sentences that already have links. Since this can have significant effects on a site, this setting isn\'t saved with the "Save" button and defaults to "Off" for new auto linking rules.', 'wpil'); ?></div>
                                </div>
                                <br>
                                <br>
                                <input type="hidden" name="wpil_keywords_limit_inserts" value="0" />
                                <input type="checkbox" id="wpil_keywords_limit_inserts" name="wpil_keywords_limit_inserts" class="wpil_keywords_limit_inserts_checkbox" <?=get_option('wpil_keywords_limit_inserts')==1?'checked':''?> value="1" />
                                <label for="wpil_keywords_limit_inserts"><?php esc_html_e('Limit how many autolinks are created?', 'wpil'); ?></label>
                                <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                    <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                    <div><?php esc_html_e('Setting a limit for how many autolinks are created will tell Link Whisper how many times an autolink should be inserted on the site. Once the limit is reached, Link Whisper will stop inserting the rule\'s links. But if a link is deleted, Link Whisper will insert another link somewhere to bring the total back up to the limit.', 'wpil'); ?></div>
                                </div>
                                <div class="wpil_keywords_insert_limit_container" style="<?=get_option('wpil_keywords_limit_inserts')==1?'display:block;':''?>">
                                    <input type="number" id="wpil_keywords_insert_limit" style="max-width: 60px;" name="wpil_keywords_insert_limit" value="<?php echo (int)get_option('wpil_keywords_insert_limit', 0); ?>" min="0" step="1"/>
                                </div>
                                <br>
                                <br>
                                <input type="hidden" name="wpil_keywords_select_links" value="0" />
                                <input type="checkbox" id="wpil_keywords_select_links" name="wpil_keywords_select_links" <?=get_option('wpil_keywords_select_links')==1?'checked':''?> value="1" />
                                <label for="wpil_keywords_select_links"><?php esc_html_e('Select links before inserting?', 'wpil'); ?></label>
                                <br>
                                <br>
                                <input type="hidden" name="wpil_keywords_set_priority" value="0" />
                                <input type="checkbox" id="wpil_keywords_set_priority" name="wpil_keywords_set_priority" class="wpil_keywords_set_priority_checkbox" <?=get_option('wpil_keywords_set_priority')==1?'checked':''?> value="1" />
                                <label for="wpil_keywords_set_priority"><?php esc_html_e('Set priority for auto link insertion?', 'wpil'); ?></label>
                                <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                    <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                    <div><?php esc_html_e('Setting a priority for the auto link will tell Link Whisper which link to insert if it comes across a sentence that has keywords that match multiple auto links. The auto link with the highest priority will be the one inserted in such a case.', 'wpil'); ?></div>
                                </div>
                                <div class="wpil_keywords_priority_setting_container" style="<?=get_option('wpil_keywords_set_priority')==1?'display:block;':''?>">
                                    <input type="number" id="wpil_keywords_priority_setting" style="max-width: 60px;" name="wpil_keywords_priority_setting" value="<?php echo get_option('wpil_keywords_priority_setting', 0)?>" min="0" step="1"/>
                                </div>
                                <br>
                                <br>
                                <input type="hidden" name="wpil_keywords_prioritize_longtail" value="0" />
                                <input type="checkbox" id="wpil_keywords_prioritize_longtail" name="wpil_keywords_prioritize_longtail" <?=get_option('wpil_keywords_prioritize_longtail')==1?'checked':''?> value="1" />
                                <label for="wpil_keywords_prioritize_longtail"><?php esc_html_e('Prioritize long-tail autolinks?', 'wpil'); ?></label>
                                <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                    <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                    <div><?php esc_html_e('Prioritizing long-tail autolinks will tell Link Whisper to prefer inserting long keyword autolinks to short ones. So "The best shoes in the world" would be preferred to "Best Shoes" since it\'s longer.', 'wpil'); ?></div>
                                </div>
                                <br>
                                <br>
                                <input type="hidden" name="wpil_keywords_restrict_date" value="0" />
                                <input type="checkbox" id="wpil_keywords_restrict_date" class="wpil_keywords_restrict_date_checkbox" name="wpil_keywords_restrict_date" value="1"/>
                                <label for="wpil_keywords_restrict_date"><?php esc_html_e('Only add links to posts published after the given date', 'wpil'); ?></label>
                                <div class="wpil_keywords_restricted_date_container">
                                    <input type="date" id="wpil_keywords_restricted_date" name="wpil_keywords_restricted_date"/>
                                </div>
                                <br>
                                <br>
                                <input type="hidden" name="wpil_keywords_case_sensitive" value="0" />
                                <input type="checkbox" id="wpil_keywords_case_sensitive" class="wpil_keywords_case_sensitive_checkbox" name="wpil_keywords_case_sensitive" <?=get_option('wpil_keywords_case_sensitive')==1?'checked':''?> value="1"/>
                                <label for="wpil_keywords_case_sensitive"><?php esc_html_e('Make Keyword case sensitive', 'wpil'); ?></label>
                                <br>
                                <br>
                                <input type="hidden" name="wpil_keywords_same_lang" value="0" />
                                <input type="checkbox" id="wpil_keywords_same_lang" class="wpil_keywords_same_lang_checkbox" name="wpil_keywords_same_lang"  <?=get_option('wpil_keywords_same_lang')==1?'checked':''?> value="1"/>
                                <label for="wpil_keywords_same_lang"><?php esc_html_e('Restrict autolinks to target page\'s language', 'wpil'); ?></label>
                                <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                    <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                    <div>
                                        <?php esc_html_e('Restricting autolinks to the target page\'s language tells Link Whisper to try to detect the language of the page that the "Link" is pointing to, and only insert links in pages that are in the same language.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php esc_html_e('This requires either WPML or Polylang to be active on this site, and only can detect language for pages that are on this site.', 'wpil'); ?>
                                    </div>
                                </div>
                                <br>
                                <br>
                                <div class="wpil_keywords_restrict_to_cats_container">
                                    <input type="hidden" name="wpil_keywords_restrict_to_cats" value="0" />
                                    <input type="checkbox" id="wpil_keywords_restrict_to_cats" class="wpil_keywords_restrict_to_cats" name="wpil_keywords_restrict_to_cats" <?php echo get_option('wpil_keywords_restrict_to_cats')==1?'checked':'' ?> value="1" />
                                    <label for="wpil_keywords_restrict_to_cats"><?php esc_html_e('Restrict autolinks to specific categories or tags?', 'wpil'); ?></label>
                                    <div style="position: relative; left: 10px;"><span class="wpil-keywords-restrict-cats-show"></span></div>
                                </div>
                                <br>
                                <?php 
                                $terms = Wpil_Term::getAllCategoryTerms();
                                $selected_terms = get_option('wpil_keywords_restricted_cats', array());
                                    if(!empty($terms)){
                                        // build the tax cache
                                        $tax_cache = array();
                                        foreach($terms as $term){
                                            if(!isset($tax_cache[$term->taxonomy])){
                                                $tax_cache[$term->taxonomy] = get_taxonomy($term->taxonomy);
                                            }
                                        }

                                        // build the term options
                                        $cat_options = '';
                                        $tag_options = '';
                                        foreach($terms as $term){
                                            $checked = (in_array($term->term_taxonomy_id, $selected_terms)) ? 'checked': '';
                                            if($tax_cache[$term->taxonomy]->hierarchical){
                                                $cat_options .= '<li>
                                                        <input type="hidden" name="wpil_keywords_restrict_term_' . $term->term_taxonomy_id . '" value="0" />
                                                        <input type="checkbox" class="wpil-restrict-keywords-input" name="wpil_keywords_restrict_term_' . $term->term_taxonomy_id . '" data-term-id="' . $term->term_taxonomy_id . '" ' . $checked . '>' . esc_html($term->name) . '</li>';
                                            }else{
                                                $tag_options .= '<li>
                                                        <input type="hidden" name="wpil_keywords_restrict_term_' . $term->term_taxonomy_id . '" value="0" />
                                                        <input type="checkbox" class="wpil-restrict-keywords-input" name="wpil_keywords_restrict_term_' . $term->term_taxonomy_id . '" data-term-id="' . $term->term_taxonomy_id . '" ' . $checked . '>' . esc_html($term->name) . '</li>';
                                                }
                                        }

                                        echo '<ul class="wpil-keywords-restrict-cats" style="display:none;">';
                                        echo '<li>' . __('Available Categories:', 'wpil') . '</li>';
                                        echo $cat_options;
                                        echo '</ul>';
                                        echo '<br />';
                                        echo '<br />';
                                        echo '<ul class="wpil-keywords-restrict-cats" style="display:none;">';
                                        echo '<li>' . __('Available Tags:', 'wpil') . '</li>';
                                        echo $tag_options;
                                        echo '</ul>';
                                        echo '<br />';
                                        echo '<br />';
                                    }
                                ?>
                                <input type="hidden" name="save_settings" value="1">
                                <input type="submit" class="button-primary" value="Save">
                            </div>
                        </div>
                    </form>
                    <input type="hidden" id="wpil-autolink-bulk-max-input" value="<?php echo (int) ini_get('max_input_vars')?>">
                    <div class="wpil-autolink-bulk-create-background hidden" style="background: #000; opacity: 0.7; filter: alpha(opacity=70); position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 1000500; height: 32px;"></div>
                    <div class="wpil-autolink-bulk-create-background hidden" style="background: #000; opacity: 0.7; filter: alpha(opacity=70); position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 100050;"></div>
                    <div class="wpil-autolink-bulk-create-wrapper hidden" style="
                                                                            position: absolute;
                                                                            width: 75%;
                                                                            top: 0px;
                                                                            left: 10%;
                                                                            z-index: 100060;
                                                                            background: #ffffff;
                                                                            padding: 25px 30px;
                                                                            box-shadow: 0 0 0 transparent;
                                                                            border-radius: 4px;
                                                                            border: 1px solid #8c8f94;
                                                                            color: #2c3338;">
                        <span id="wpil-bulk-keywords-close" class="dashicons dashicons-no"></span>
                        <div class="wpil-autolink-bulk-create-header">
                            <h3 style="font-size: 24px;"><?php esc_html_e('Bulk Create Autolink Rules', 'wpil'); ?></h3>
                        </div>
                        <div class="wpil-autolink-bulk-create-container">
                            <div class="wpil-autolink-bulk-import-method-container">
                                <h4 style="display: inline-block;font-size: 18px;"><?php esc_html_e('Import Method:', 'wpil'); ?></h4>
                                <div style="display: inline-block; margin: 0 0 0 10px;">
                                    <label for="wpil-autolink-keyword-method-csv" style="vertical-align: baseline;font-size: 16px;"><?php esc_html_e('CSV Import', 'wpil'); ?></label>
                                    <input type="radio" id="wpil-autolink-keyword-method-csv" class="wpil-autolink-import-method" name="wpil-autolink-import-method" value="csv" checked>
                                    <label for="wpil-autolink-keyword-method-field" style="vertical-align: baseline;font-size: 16px;"><?php esc_html_e('Field Import', 'wpil'); ?></label>
                                    <input type="radio" id="wpil-autolink-keyword-method-field" class="wpil-autolink-import-method" name="wpil-autolink-import-method" value="field">
                                </div>
                            </div>
                            <div class="wpil-autolink-csv-import-container" style="text-align: center;">
                                <div class="wpil-autolink-csv-import-text" style="display: inline-block;">
                                    <p><?php esc_html_e('You can bulk import autolink rules from a CSV file here.', 'wpil'); ?></p>
                                </div>
                                <br />
                                <div class="wpil-autolink-csv-import" style="padding: 30px; display: inline-block; margin: auto; background: #f8f8f8; border: 1px solid #c3c4c7;">
                                    <input type="file" id="wpil-autolink-csv-import-file" multiple="" accept=".csv" style="padding: 3px 15px 3px 0px;">
                                    <input type="button" value="<?php esc_attr_e('Import Autolink Rules', 'wpil'); ?>" class="button-primary btn btn-info wpil-bulk-keywords-import disabled">
                                </div>
                                <br />
                                <div class="wpil-autolink-csv-import-text" style="display: inline-block;">
                                    <p>(<?php echo sprintf(__('Click %s to download an example CSV file for a sample of the format to use.', 'wpil'), '<a href="' . trailingslashit(WP_INTERNAL_LINKING_PLUGIN_URL) . 'autolink-import-sample.csv" download>' . __('here', 'wpil') . '</a>'); ?>)</p>
                                </div>
                                <br />
                                <div class="wpil-autolink-csv-import-text wpil-autolink-csv-many-rows hidden">
                                    <p><?php echo __('This file is quite large, importing the keywords will take some time and may slow down the page.', 'wpil');?></p>
                                    <p><?php echo __('If you experience errors, please consider breaking the file into several smaller files and upload them individually.', 'wpil'); ?></p>
                                </div>
                            </div>
                            <div class="wpil-autolink-field-import-container hidden" style="text-align: center;">
                                <div class="wpil-autolink-field-import">
                                    <div style="width: 48%; display: inline-block;text-align: left;">
                                        <label for="wpil-autolink-keyword-field" style="font-size: 14px;font-weight: bold;"><?php esc_html_e('Autolink Keywords', 'wpil'); ?></label><br />
                                        <textarea id="wpil-autolink-keyword-field" rows=10 style="width: 100%;margin: 3px 0 0 0;" placeholder="Example Keyword 1
Example Keyword 2
Example Keyword 3"></textarea><br />
                                    </div>
                                    <div style="width: 48%; display: inline-block;text-align: left; float: right;">
                                        <label for="wpil-autolink-url-field" style="font-size: 14px;font-weight: bold;"><?php esc_html_e('Autolink URLs', 'wpil'); ?></label><br />
                                        <textarea id="wpil-autolink-url-field" rows=10 style="width: 100%;margin: 3px 0 0 0;" placeholder="https://example.com/example-page-1
https://example.com/example-page-2
https://example.com/example-page-3"></textarea><br />
                                    </div>
                                </div>
                                <input type="button" value="<?php esc_attr_e('Import Autolink Rules', 'wpil'); ?>" class="button-primary btn btn-info wpil-bulk-keywords-import disabled">
                            </div>
                        </div>
                        <div class="wpil-autolink-bulk-keyword-global-setting-container" style="position:relative;">
                                <div class="wpil-autolink-bulk-keyword-heading-container">
                                    <h4 style="display: inline-block; font-size: 16px; margin-bottom: 0px; padding:0 0 10px 0;"><?php esc_html_e('Bulk Configure Autolink Settings:', 'wpil'); ?></h4>
                                    <i class="dashicons dashicons-admin-generic wpil-bulk-autolink-setting-icon wpil-global-setting-icon" style="font-size: 36px;margin: 2px 0 0 5px;color: #33c7fd;position: relative;top: 9px;left: -4px;"></i>
                                </div>
                                <div class="wpil-bulk-autolink-global-settings block">
                                    <div class="wpil-bulk-autolink-settings block" style="background:#f0f0f1; border: 2px solid #cdcdcd; padding: 10px;">
                                    <input type="checkbox" id="wpil_keywords_bulk_global_add_same_link" class="wpil-bulk-autolinks-add-same-link" <?=get_option('wpil_keywords_add_same_link')==1?'checked':''?> value="1" />
                                        <label for="wpil_keywords_bulk_global_add_same_link"><?php esc_html_e('Add link if post already has this link?', 'wpil'); ?></label>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_bulk_global_link_once" class="wpil-bulk-autolinks-link-once" checked="checked" value="1" />
                                        <label for="wpil_keywords_bulk_global_link_once"><?php esc_html_e('Only link once per post', 'wpil'); ?></label>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_bulk_global_restrict_to_live" class="wpil-bulk-autolinks-restrict-to-live" checked="checked" value="1" />
                                        <label for="wpil_keywords_bulk_global_restrict_to_live"><?php esc_html_e('Only create internal links when target is published?', 'wpil'); ?></label>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_bulk_global_force_insert" class="wpil-bulk-autolinks-force-insert" value="1" />
                                        <label for="wpil_keywords_bulk_global_force_insert"><?php esc_html_e('Override "One Link per Sentence" rule?', 'wpil'); ?></label>
                                        <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                            <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                            <div><?php esc_html_e('By default, Link Whisper only inserts one link per sentence. If a sentence already has a link, Link Whisper won\'t add another one to it. This option allows you to override the rule so autolinks can be inserted in sentences that already have links.', 'wpil'); ?></div>
                                        </div>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_bulk_global_limit_inserts" class="wpil-bulk-autolinks-limit-inserts-checkbox" <?=get_option('wpil_keywords_limit_inserts')==1?'checked':''?> value="1" />
                                        <label for="wpil_keywords_bulk_global_limit_inserts"><?php esc_html_e('Limit how many autolinks are created?', 'wpil'); ?></label>
                                        <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                            <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                            <div><?php esc_html_e('Setting a limit for how many autolinks are created will tell Link Whisper how many times an autolink should be inserted on the site. Once the limit is reached, Link Whisper will stop inserting the rule\'s links. But if a link is deleted, Link Whisper will insert another link somewhere to bring the total back up to the limit.', 'wpil'); ?></div>
                                        </div>
                                        <div class="wpil_keywords_bulk_global_insert_limit_container" style="<?=get_option('wpil_keywords_limit_inserts')==1?'display:block;':''?>">
                                            <input type="number" id="wpil_keywords_bulk_global_insert_limit" class="wpil-bulk-autolinks-insert-limit" style="max-width: 60px;" name="wpil_keywords_bulk_global_insert_limit" value="<?php echo get_option('wpil_keywords_insert_limit', 0)?>" min="0" step="1"/>
                                        </div>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_bulk_global_select_links" class="wpil-bulk-autolinks-select-links" <?=get_option('wpil_keywords_select_links')==1?'checked':''?> value="1" />
                                        <label for="wpil_keywords_bulk_global_select_links"><?php esc_html_e('Select links before inserting?', 'wpil'); ?></label>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_bulk_global_set_priority" name="wpil_keywords_set_priority" class="wpil-bulk-autolinks-set-priority-checkbox" <?=get_option('wpil_keywords_set_priority')==1?'checked':''?> value="1" />
                                        <label for="wpil_keywords_bulk_global_set_priority"><?php esc_html_e('Set priority for auto link insertion?', 'wpil'); ?></label>
                                        <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                            <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                            <div><?php esc_html_e('Setting a priority for the auto link will tell Link Whisper which link to insert if it comes across a sentence that has keywords that match multiple auto links. The auto link with the highest priority will be the one inserted in such a case.', 'wpil'); ?></div>
                                        </div>
                                        <div class="wpil_keywords_priority_setting_container" style="<?=get_option('wpil_keywords_set_priority')==1?'display:block;':''?>">
                                            <input type="number" id="wpil_keywords_bulk_global_priority_setting" style="max-width: 60px;" class="wpil-bulk-autolinks-priority-setting" value="<?php echo get_option('wpil_keywords_priority_setting', 0)?>" min="0" step="1"/>
                                        </div>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_bulk_global_prioritize_longtail" class="wpil-bulk-autolinks-prioritize-longtail" <?=get_option('wpil_keywords_prioritize_longtail')==1?'checked':''?> value="1" />
                                        <label for="wpil_keywords_bulk_global_prioritize_longtail"><?php esc_html_e('Prioritize long-tail autolinks?', 'wpil'); ?></label>
                                        <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                            <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                            <div><?php esc_html_e('Prioritizing long-tail autolinks will tell Link Whisper to prefer inserting long keyword autolinks to short ones. So "The best shoes in the world" would be preferred to "Best Shoes" since it\'s longer.', 'wpil'); ?></div>
                                        </div>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_bulk_global_restrict_date" class="wpil-bulk-autolinks-date-checkbox" value="1"/>
                                        <label for="wpil_keywords_bulk_global_restrict_date"><?php esc_html_e('Only add links to posts published after the given date', 'wpil'); ?></label>
                                        <div class="wpil_keywords_restricted_date_container">
                                            <input type="date" id="wpil_keywords_bulk_global_restricted_date" class="wpil-bulk-autolinks-restricted-date"/>
                                        </div>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_bulk_global_case_sensitive" class="wpil_keywords_case_sensitive_checkbox wpil-bulk-autolinks-case-sensitive" value="1"/>
                                        <label for="wpil_keywords_bulk_global_case_sensitive"><?php esc_html_e('Make Keyword case sensitive', 'wpil'); ?></label>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_bulk_global_same_lang" class="wpil_keywords_same_lang_checkbox wpil-bulk-autolinks-same-lang" value="1"/>
                                        <label for="wpil_keywords_bulk_global_same_lang"><?php esc_html_e('Restrict autolinks to target page\'s language', 'wpil'); ?></label>
                                        <br>
                                        <br>
                                        <div class="wpil_keywords_bulk_global_restrict_to_cats_container" style="display: inline-block;position: relative;">
                                            <input type="checkbox" id="wpil_keywords_bulk_global_restrict_to_cats" class="wpil_keywords_restrict_to_cats wpil-bulk-autolinks-restrict-to-cats" <?php echo get_option('wpil_keywords_restrict_to_cats')==1?'checked':'' ?> value="1" />
                                            <label for="wpil_keywords_bulk_global_restrict_to_cats"><?php esc_html_e('Restrict autolinks to specific categories or tags?', 'wpil'); ?></label>
                                            <div style="position: absolute;right: -10px;top: 5px;"><span class="wpil-keywords-restrict-cats-show"></span></div>
                                        </div>
                                        <br>
                                        <?php 
                                        $terms = Wpil_Term::getAllCategoryTerms();
                                            if(!empty($terms)){
                                                // build the tax cache
                                                $tax_cache = array();
                                                foreach($terms as $term){
                                                    if(!isset($tax_cache[$term->taxonomy])){
                                                        $tax_cache[$term->taxonomy] = get_taxonomy($term->taxonomy);
                                                    }
                                                }

                                                // build the term options
                                                $cat_options = '';
                                                $tag_options = '';
                                                foreach($terms as $term){
                                                    if($tax_cache[$term->taxonomy]->hierarchical){
                                                        $cat_options .= '<li>
                                                                <input type="hidden" name="wpil_keywords_bulk_global_restrict_term_' . $term->term_taxonomy_id . '" value="0" />
                                                                <input type="checkbox" class="wpil-bulk-autolinks-restrict-keywords-input" name="wpil_keywords_bulk_global_restrict_term_' . $term->term_taxonomy_id . '" data-term-id="' . $term->term_taxonomy_id . '">' . esc_html($term->name) . '</li>';
                                                    }else{
                                                        $tag_options .= '<li>
                                                                <input type="hidden" name="wpil_keywords_bulk_global_restrict_term_' . $term->term_taxonomy_id . '" value="0" />
                                                                <input type="checkbox" class="wpil-bulk-autolinks-restrict-keywords-input" name="wpil_keywords_bulk_global_restrict_term_' . $term->term_taxonomy_id . '" data-term-id="' . $term->term_taxonomy_id . '">' . esc_html($term->name) . '</li>';
                                                        }
                                                }

                                                echo '<ul class="wpil-keywords-restrict-cats" style="display:none;">';
                                                echo '<li>' . __('Available Categories:', 'wpil') . '</li>';
                                                echo $cat_options;
                                                echo '</ul>';
                                                echo '<br />';
                                                echo '<br />';
                                                echo '<ul class="wpil-keywords-restrict-cats" style="display:none;">';
                                                echo '<li>' . __('Available Tags:', 'wpil') . '</li>';
                                                echo $tag_options;
                                                echo '</ul>';
                                                echo '<br />';
                                                echo '<br />';
                                            }
                                        ?>
                                        <input type="button" id="wpil-bulk-keywords-global-set" value="<?php esc_attr_e('Apply Settings', 'wpil'); ?>" class="button-primary btn btn-info bulk-create-temp-display">
                                    </div>
                                </div>
                            </div>
                        <div class="wpil-autolink-bulk-keyword-heading-container">
                            <h4 style="display: inline-block; font-size: 16px; border-bottom: 2px solid #cdcdcd; width: 100%; margin-bottom: 0px; padding:0 0 10px 0;"><?php esc_html_e('Autolinks to Create:', 'wpil'); ?></h4>
                        </div>
                        <div class="wpil-autolink-bulk-keyword-container" style="background:#f0f0f1;">
                            <div class="wpil-bulk-autolink-rows" style="border-left: 2px solid #cdcdcd;border-right: 2px solid #cdcdcd;border-bottom: 2px solid #cdcdcd;">
                            </div>
                            <div class="wpil-bulk-autolink-row wpil-row-template">
                                <div style="display: inline-block;margin: 0px 8px;">
                                    <label style="font-size: 16px; color: #444; font-weight: bold;"><?php esc_html_e('Create', 'wpil'); ?></label>
                                    <input type="checkbox" name="wpil-create-autolink" style="margin: 8px calc(50% - 8px);" checked>
                                </div>
                                <div style="width: 100%; display: inline-block;">
                                    <input type="text" name="keyword" placeholder="Keyword" class="wpil-bulk-autolink-input"><i class="dashicons dashicons-admin-generic wpil-bulk-autolink-setting-icon" style="font-size: 25px; margin: 2px 0 0 5px; color: #33c7fd; cursor: pointer;"></i>
                                    <input type="text" name="link" placeholder="Link"  class="wpil-bulk-autolink-input">

                                    <div class="wpil-bulk-autolink-settings block">
                                        <input type="checkbox" id="wpil_keywords_add_same_link" class="wpil-bulk-autolinks-add-same-link" <?=get_option('wpil_keywords_add_same_link')==1?'checked':''?> value="1" />
                                        <label for="wpil_keywords_add_same_link"><?php esc_html_e('Add link if post already has this link?', 'wpil'); ?></label>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_link_once" class="wpil-bulk-autolinks-link-once" checked="checked" value="1" />
                                        <label for="wpil_keywords_link_once"><?php esc_html_e('Only link once per post', 'wpil'); ?></label>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_restrict_to_live" class="wpil-bulk-autolinks-restrict-to-live" <?php echo (get_option('wpil_keywords_restrict_to_live', 1) == 1) ? 'checked': ''; ?> value="1" />
                                        <label for="wpil_keywords_restrict_to_live"><?php esc_html_e('Only create internal links when target is published?', 'wpil'); ?></label>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_force_insert" class="wpil-bulk-autolinks-force-insert" value="1" />
                                        <label for="wpil_keywords_force_insert"><?php esc_html_e('Override "One Link per Sentence" rule?', 'wpil'); ?></label>
                                        <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                            <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                            <div><?php esc_html_e('By default, Link Whisper only inserts one link per sentence. If a sentence already has a link, Link Whisper won\'t add another one to it. This option allows you to override the rule so autolinks can be inserted in sentences that already have links.', 'wpil'); ?></div>
                                        </div>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_limit_inserts" class="wpil-bulk-autolinks-limit-inserts-checkbox" <?=get_option('wpil_keywords_limit_inserts')==1?'checked':''?> value="1" />
                                        <label for="wpil_keywords_limit_inserts"><?php esc_html_e('Limit how many autolinks are created?', 'wpil'); ?></label>
                                        <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                            <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                            <div><?php esc_html_e('Setting a limit for how many autolinks are created will tell Link Whisper how many times an autolink should be inserted on the site. Once the limit is reached, Link Whisper will stop inserting the rule\'s links. But if a link is deleted, Link Whisper will insert another link somewhere to bring the total back up to the limit.', 'wpil'); ?></div>
                                        </div>
                                        <div class="wpil_keywords_insert_limit_container" style="<?=get_option('wpil_keywords_limit_inserts')==1?'display:block;':''?>">
                                            <input type="number" id="wpil_keywords_insert_limit" style="max-width: 60px;" class="wpil-bulk-autolinks-insert-limit" value="<?php echo (int)get_option('wpil_keywords_insert_limit', 0); ?>" min="0" step="1"/>
                                        </div>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_select_links" class="wpil-bulk-autolinks-select-links" <?=get_option('wpil_keywords_select_links')==1?'checked':''?> value="1" />
                                        <label for="wpil_keywords_select_links"><?php esc_html_e('Select links before inserting?', 'wpil'); ?></label>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_set_priority" name="wpil_keywords_set_priority" class="wpil-bulk-autolinks-set-priority-checkbox" <?=get_option('wpil_keywords_set_priority')==1?'checked':''?> value="1" />
                                        <label for="wpil_keywords_set_priority"><?php esc_html_e('Set priority for auto link insertion?', 'wpil'); ?></label>
                                        <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                            <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                            <div><?php esc_html_e('Setting a priority for the auto link will tell Link Whisper which link to insert if it comes across a sentence that has keywords that match multiple auto links. The auto link with the highest priority will be the one inserted in such a case.', 'wpil'); ?></div>
                                        </div>
                                        <div class="wpil_keywords_priority_setting_container" style="<?=get_option('wpil_keywords_set_priority')==1?'display:block;':''?>">
                                            <input type="number" id="wpil_keywords_priority_setting" style="max-width: 60px;" class="wpil-bulk-autolinks-priority-setting" value="<?php echo get_option('wpil_keywords_priority_setting', 0)?>" min="0" step="1"/>
                                        </div>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_prioritize_longtail" class="wpil-bulk-autolinks-prioritize-longtail" <?=get_option('wpil_keywords_prioritize_longtail')==1?'checked':''?> value="1" />
                                        <label for="wpil_keywords_prioritize_longtail"><?php esc_html_e('Prioritize long-tail autolinks?', 'wpil'); ?></label>
                                        <div class="wpil_help" style="display: inline-block; float:none; height: 5px;">
                                            <i class="dashicons dashicons-editor-help" style="font-size: 20px; color: #444; margin: 2px 0 8px;"></i>
                                            <div><?php esc_html_e('Prioritizing long-tail autolinks will tell Link Whisper to prefer inserting long keyword autolinks to short ones. So "The best shoes in the world" would be preferred to "Best Shoes" since it\'s longer.', 'wpil'); ?></div>
                                        </div>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_restrict_date" class="wpil-bulk-autolinks-date-checkbox" value="1"/>
                                        <label for="wpil_keywords_restrict_date"><?php esc_html_e('Only add links to posts published after the given date', 'wpil'); ?></label>
                                        <div class="wpil_keywords_restricted_date_container">
                                            <input type="date" id="wpil_keywords_restricted_date" class="wpil-bulk-autolinks-restricted-date"/>
                                        </div>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_case_sensitive" class="wpil_keywords_case_sensitive_checkbox wpil-bulk-autolinks-case-sensitive" value="1"/>
                                        <label for="wpil_keywords_case_sensitive"><?php esc_html_e('Make Keyword case sensitive', 'wpil'); ?></label>
                                        <br>
                                        <br>
                                        <input type="checkbox" id="wpil_keywords_same_lang" class="wpil_keywords_same_lang_checkbox wpil-bulk-autolinks-same-lang" value="1"/>
                                        <label for="wpil_keywords_same_lang"><?php esc_html_e('Restrict autolinks to target page\'s language', 'wpil'); ?></label>
                                        <br>
                                        <br>
                                        <div class="wpil_keywords_restrict_to_cats_container">
                                            <input type="checkbox" id="wpil_keywords_restrict_to_cats" class="wpil_keywords_restrict_to_cats wpil-bulk-autolinks-restrict-to-cats" <?php echo get_option('wpil_keywords_restrict_to_cats')==1?'checked':'' ?> value="1" />
                                            <label for="wpil_keywords_restrict_to_cats"><?php esc_html_e('Restrict autolinks to specific categories or tags?', 'wpil'); ?></label>
                                            <div style="position: relative; left: 10px;"><span class="wpil-keywords-restrict-cats-show"></span></div>
                                        </div>
                                        <br>
                                        <?php 
                                        $terms = Wpil_Term::getAllCategoryTerms();
                                            if(!empty($terms)){
                                                // build the tax cache
                                                $tax_cache = array();
                                                foreach($terms as $term){
                                                    if(!isset($tax_cache[$term->taxonomy])){
                                                        $tax_cache[$term->taxonomy] = get_taxonomy($term->taxonomy);
                                                    }
                                                }

                                                // build the term options
                                                $cat_options = '';
                                                $tag_options = '';
                                                foreach($terms as $term){
                                                    if($tax_cache[$term->taxonomy]->hierarchical){
                                                        $cat_options .= '<li>
                                                                <input type="hidden" name="wpil_keywords_restrict_term_' . $term->term_taxonomy_id . '" value="0" />
                                                                <input type="checkbox" class="wpil-bulk-autolinks-restrict-keywords-input" name="wpil_keywords_restrict_term_' . $term->term_taxonomy_id . '" data-term-id="' . $term->term_taxonomy_id . '">' . esc_html($term->name) . '</li>';
                                                    }else{
                                                        $tag_options .= '<li>
                                                                <input type="hidden" name="wpil_keywords_restrict_term_' . $term->term_taxonomy_id . '" value="0" />
                                                                <input type="checkbox" class="wpil-bulk-autolinks-restrict-keywords-input" name="wpil_keywords_restrict_term_' . $term->term_taxonomy_id . '" data-term-id="' . $term->term_taxonomy_id . '">' . esc_html($term->name) . '</li>';
                                                        }
                                                }

                                                echo '<ul class="wpil-keywords-restrict-cats" style="display:none;">';
                                                echo '<li>' . __('Available Categories:', 'wpil') . '</li>';
                                                echo $cat_options;
                                                echo '</ul>';
                                                echo '<br />';
                                                echo '<br />';
                                                echo '<ul class="wpil-keywords-restrict-cats" style="display:none;">';
                                                echo '<li>' . __('Available Tags:', 'wpil') . '</li>';
                                                echo $tag_options;
                                                echo '</ul>';
                                                echo '<br />';
                                                echo '<br />';
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="button" id="wpil-bulk-keywords-create" value="<?php esc_attr_e('Create Autolinks', 'wpil'); ?>" class="button-primary btn btn-info" style="margin: 20px calc(50% - 70px);">
                        <div class="progress-panel-container">
                            <div class="progress_panel loader">
                                <div class="progress_count"><?php esc_html_e('Creating Autolink Rules'); ?></div>
                            </div>
                            <div style="text-align: center;">
                                <p><?php esc_html_e('Please keep this tab open until the Autolink Creation process is complete.', 'wpil'); ?></p>
                                <p><?php esc_html_e('Closing the tab will stop Link Whisper from creating all of the autolinks.', 'wpil'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div style="clear: both"></div>
                    <a href="javascript:void(0)" class="button-primary wpil-is-tooltipped" <?php echo Wpil_Toolbox::generate_tooltip_text('autolinking-refresh-report'); ?> id="wpil_keywords_reset_button"><?php esc_html_e('Refresh Auto-Link Report', 'wpil'); ?></a>
                    <div class="wpil_help" style="float:right; position: relative; right: 10px; top: 5px;">
                        <i class="dashicons dashicons-editor-help" style="font-size: 26px; color: #444; margin: 2px 0 8px;"></i>
                        <div style="margin: 0px; top: 35px; background: rgba(0, 0, 0, .8);">
                            <?php esc_html_e('The "Refresh Auto-Links Report" button clears the existing Autolinking Report data and tells Link Whisper to search the site for Autolinks that have been created.', 'wpil'); ?>
                            <br>
                            <br>
                            <?php esc_html_e('It\'s extremely useful for identifying links that were inserted in post content, but aren\'t registering in the Autolinking Report.', 'wpil'); ?>
                            <br>
                            <br>
                            <?php esc_html_e('Since it\'s purpose is to find links that currently exist on the site, it doesn\'t insert any links while running.', 'wpil'); ?>
                        </div>
                    </div>
                    <?php if (!$reset) : ?>
                        <div class="table wpil-is-tooltipped wpil-no-scale wpil-tooltip-target-child wpil-tooltip-target.wp-list-table wpil-tooltip-no-position" <?php echo Wpil_Toolbox::generate_tooltip_text('autolinking-table'); ?>>
                            <?php $table->display(); ?>
                        </div>
                    <?php endif; ?>
                    <div class="progress" <?=$reset?'style="display:block"':''?>>
                        <h4 class="progress_panel_msg"><?php esc_html_e('Synchronizing your data..','wpil'); ?></h4>
                        <div class="progress_panel loader">
                            <div class="progress_count"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var wpil_keyword_nonce = '<?=wp_create_nonce($user->ID . 'wpil_keyword')?>';
    var is_wpil_keyword_reset = <?=$reset?'true':'false'?>;
</script>