<div class="wrap wpil-report-page wpil_styles" id="inbound_suggestions_page" data-id="<?=$post->id?>" data-type="<?=$post->type?>">
    <?=Wpil_Base::showVersion()?>
    <h1 class="wp-heading-inline wpil-is-tooltipped wpil-no-overlay wpil-no-scale" data-wpil-tooltip-read-time="3500" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-intro'); ?>><?php esc_html_e("Inbound Linking Suggestions", "wpil"); ?></h1>
    <a href="<?=esc_url($return_url)?>" class="page-title-action return_to_report wpil-is-tooltipped" data-wpil-tooltip-read-time="3500" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-return-to-report'); ?>><?php esc_html_e('Return to Report','wpil'); ?></a>
    <h2 id="inbound-suggestions-dest-post-title"><div class="wpil-is-tooltipped" style="display:inline-block;" data-wpil-tooltip-read-time="3500" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-link-target'); ?>><?php esc_html_e('Creating links pointing to: ', 'wpil'); ?><a href="<?php echo esc_url($post->getViewLink()); ?>"><?php echo esc_html($post->getTitle());?></a></div></h2>
    <?php
    if(!empty($source_id)){
    ?>
    <h2 id="inbound-suggestions-dest-post-title" class="wpil-is-tooltipped" data-wpil-tooltip-read-time="3500" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-link-source'); ?>><?php esc_html_e('From: ', 'wpil'); ?><a href="<?php echo esc_url($source_post->getViewLink()); ?>"><?php echo esc_html($source_post->getTitle());?></a></h2>
    <?php
    }
    ?>
    <div id="keywords" class="wpil-is-tooltipped" data-wpil-tooltip-read-time="3500" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-keyword-search'); ?>>
        <form action="" method="post">
            <label for="keywords_field">Search by Keyword</label>
            <textarea name="keywords" id="keywords_field"><?=!empty($_POST['keywords'])?sanitize_textarea_field($_POST['keywords']):''?></textarea>
            <button type="submit" class="button-primary">Search</button>
        </form>
    </div>
    <br />
    <div id="wpil-inbound-show-link-stats">
        <div class="wpil-inbound-show-link-stats-button"><button class="button-primary" data-nonce="<?php echo wp_create_nonce('wpil-inbound-show-link-stats-nonce'); ?>"><?php esc_html_e('Target Post\'s Link Stats', 'wpil'); ?></button></div>
        <div class="wpil-inbound-show-link-stats-form" style="<?php echo (empty(get_user_meta(get_current_user_id(), 'wpil_inbound_show_link_stats_visible', true))) ? 'display: none;' : ''; ?>"><?php
            $user = wp_get_current_user();?>
            <div id="wpil_show-link-stats" class="postbox wpil-is-tooltipped wpil-no-scale" data-wpil-tooltip-read-time="5500" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-link-stats'); ?>>
                <?php $datatype = ($post->type === 'post') ? __('Post', 'wpil'): __('Term', 'wpil'); ?>
                <h2 class="hndle no-drag"><span><?php echo sprintf(__('Current %s Link Stats', 'wpil'), $datatype); ?></span></h2>
                <div class="inside"><?php
                    $is_metabox = false;
                    include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/inbound_suggestions_post_link_stats.php';?>
                </div>
            </div>
        </div>
    </div>
    <div id="wpil-inbound-target-keywords">
        <div class="wpil-inbound-target-keyword-edit-button"><button class="button-primary" data-nonce="<?php echo wp_create_nonce('wpil-inbound-keyword-visibility-nonce'); ?>"><?php esc_html_e('Edit Target Keywords', 'wpil'); ?></button></div>
        <div class="wpil-inbound-target-keyword-edit-form" style="<?php echo (empty(get_user_meta(get_current_user_id(), 'wpil_inbound_target_keyword_visible', true))) ? 'display: none;' : ''; ?>"><?php
            $user = wp_get_current_user();
            $keywords = Wpil_TargetKeyword::get_keywords_by_post_ids($post->id, $post->type);
            $keyword_sources = Wpil_TargetKeyword::get_active_keyword_sources();?>
            <div id="wpil_target-keywords" class="postbox ">
                <h2 class="hndle no-drag"><span><?php esc_html_e('Link Whisper Target Keywords', 'wpil'); ?></span></h2>
                <div class="inside"><?php
                    $is_metabox = false;
                    include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/target_keyword_list.php';?>
                </div>
            </div>
        </div>
    </div>
    <hr class="wp-header-end">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div id="post-body-content" style="position: relative;">
                <div id="wpil_link-articles" class="postbox wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" data-wpil-tooltip-read-time="5500" <?php echo Wpil_Toolbox::generate_tooltip_text('inbound-suggestions-table'); ?>>
                    <h2 class="hndle no-drag"><span><?php esc_html_e('Link Whisper Inbound Suggestions', 'wpil'); ?></span></h2>
                    <div class="inside">
                        <div class="tbl-link-reports">
                            <?php   $user = wp_get_current_user();
                                    $load_without_animation = get_user_meta(get_current_user_id(), 'wpil_disable_load_with_animation', true);
                                    $max_inbound_links = get_option('wpil_max_inbound_links_per_post', 0);
                            ?>
                            <?php if(empty($max_inbound_links) || $max_inbound_links > $post->getInboundInternalLinks(true)){ ?>
                                <?php if (!empty($_GET['wpil_no_preload']) || !empty($load_without_animation)){ ?>
                                    <?php if($manually_trigger_suggestions){ ?>
                                        <div class="wpil_styles wpil-get-manual-suggestions-container" style="min-height: 200px">
                                            <a href="#" id="wpil-get-manual-suggestions" style="margin: 15px 0px;" class="button-primary"><?php esc_html_e('Get Suggestions', 'wpil'); ?></a>
                                        </div>
                                    <?php } ?>
                                    <form method="post" action="">
                                        <div data-wpil-ajax-container data-wpil-ajax-container-url="<?=esc_url(admin_url('admin.php?page=link_whisper&type=inbound_suggestions_page_container&'.($post->type=='term'?'term_id=':'post_id=').$post->id.(!empty($source_id) ? '&source=' . $source_id: '').(!empty($user->ID) ? '&nonce='.wp_create_nonce($user->ID . 'wpil_suggestion_nonce') : '')).Wpil_Suggestion::getKeywordsUrl().'&wpil_no_preload=1' . ((empty($source_id)) ? Wpil_Settings::get_suggestion_filter_string(): '')); ?>" data-wpil-manual-suggestions="<?php echo ($manually_trigger_suggestions) ? 1: 0;?>" <?php echo ($manually_trigger_suggestions) ? 'style="display:none"': ''; ?> data-wpil-suggestion-nonce="<?php echo wp_create_nonce($user->ID .'wpil_suggestion_nonce'); ?>">
                                            <div style="margin-bottom: 30px;">
                                                <?php if(!empty($has_parent)){ ?>
                                                <input style="margin-bottom: -5px;" type="checkbox" name="same_parent" id="field_same_parent" class="wpil-suggestion-input" data-suggestion-input-initial-value="<?php echo !empty($same_parent) ? 1: 0;?>" <?=(isset($same_parent) && !empty($same_parent)) ? 'checked' : ''?>> <label for="field_same_parent"><?php esc_html_e('Only Show Link Suggestions From Posts With the Same Page Parent as This Post', 'wpil'); ?></label>
                                                <br>
                                                <?php } ?>
                                                <input style="margin-bottom: -5px;" type="checkbox" name="same_category" id="field_same_category_page" <?=(Wpil_Settings::get_suggestion_filter('same_category')) ? 'checked' : ''?>> <label for="field_same_category_page">Only Show Link Suggestions in the Same Category as This Post</label>
                                                <br>
                                                <input type="checkbox" name="same_tag" id="field_same_tag" <?=!empty($same_tag) ? 'checked' : ''?>> <label for="field_same_tag">Only Show Link Suggestions with the Same Tag as This Post</label>
                                            </div>
                                            <button id="inbound_suggestions_button" class="sync_linking_keywords_list button-primary" data-id="<?=esc_attr($post->id)?>" data-type="<?=esc_attr($post->type)?>" data-page="inbound">Add links</button>
                                        </div>
                                        <p style="margin-top: 50px;">
                                            <a href="<?=esc_url(str_replace('&wpil_no_preload=1', '', $_SERVER['REQUEST_URI']))?>" class="wpil-animation-load-setting" data-disable-load-with-animation="0" data-nonce="<?php echo wp_create_nonce('wpil-load-with-animation-nonce'); ?>">Load with animation</a>
                                        </p>
                                    </form>
                                <?php }else{ ?>
                                    <?php if($manually_trigger_suggestions){ ?>
                                        <div class="wpil_styles wpil-get-manual-suggestions-container" style="min-height: 200px">
                                            <a href="#" id="wpil-get-manual-suggestions" style="margin: 15px 0px;" class="button-primary"><?php esc_html_e('Get Suggestions', 'wpil'); ?></a>
                                        </div>
                                    <?php } ?>
                                <div data-wpil-ajax-container data-wpil-ajax-container-url="<?=esc_url(admin_url('admin.php?page=link_whisper&type=inbound_suggestions_page_container&'.($post->type=='term'?'term_id=':'post_id=').$post->id.(!empty($source_id) ? '&source=' . $source_id: '').(!empty($user->ID) ? '&nonce='.wp_create_nonce($user->ID . 'wpil_suggestion_nonce') : '')).Wpil_Suggestion::getKeywordsUrl() . ((empty($source_id)) ? Wpil_Settings::get_suggestion_filter_string(): ''))?>" data-wpil-manual-suggestions="<?php echo ($manually_trigger_suggestions) ? 1: 0;?>" <?php echo ($manually_trigger_suggestions) ? 'style="display:none"': ''; ?> data-wpil-suggestion-nonce="<?php echo wp_create_nonce($user->ID .'wpil_suggestion_nonce'); ?>">
                                    <div class='progress_panel loader'>
                                        <div class="progress_count" style="width: 100%"><?php esc_html_e('Gathering Suggestion Data', 'wpil'); ?></div>
                                    </div>
                                    <div class="wpil-process-loading-error-message">
                                        <p><?php esc_html_e('The suggestions are taking longer than normal, so there might have been an error.', 'wpil'); ?></p>
                                        <p><?php esc_html_e('If you don\'t see any progress in the next 2 minutes, please try reloading the page and re-starting the process.', 'wpil'); ?></p>
                                    </div>
                                </div>
                                <p style="margin-top: 50px;">
                                    <a href="<?=esc_url($_SERVER['REQUEST_URI'] . '&wpil_no_preload=1')?>" class="wpil-animation-load-setting" data-disable-load-with-animation="1" data-nonce="<?php echo wp_create_nonce('wpil-load-with-animation-nonce'); ?>">Load without animation</a>
                                </p>
                                <?php } ?>
                                <div data-wpil-page-inbound-links=1> </div>
                            <?php }else{ ?>
                                <div class="wpil_styles" style="min-height: 200px">
                                    <p style="display: inline-block;"><?php esc_html_e('Post has reached the max link limit. To generate suggestions for this post, please increase the Max Inbound Links Per Post setting from the Link Whisper Settings.', 'wpil') ?></p>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
