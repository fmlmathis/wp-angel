<div class="wrap wpil-loading-screen">
    <h1 class="wp-heading-inline"><?php esc_html_e("Broken Links Report","wpil"); ?></h1>
    <hr class="wp-header-end">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div id="post-body-content" style="position: relative;">
                <div>
                    <h3><?php esc_html_e('Processing Site Posts...', 'wpil'); ?></h3>
                    <span class="wpil-loading-status-message">
                        <?php esc_html_e('Please don\'t close this tab otherwise the process will stop and have to be continued later.', 'wpil'); ?>
                    </span>
                </div>
                <div class="syns_div wpil_report_need_prepare">
                    <h4 class="progress_panel_msg hide"><?php esc_html_e('Synchronizing your data..','wpil'); ?></h4>
                    <div class="progress_panel">
                        <div class="progress_count" style="width: 0%"><span class="wpil-loading-status"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var error_reset_run = <?=!empty($error_reset_run) ? 1 : 0?>;
</script>
