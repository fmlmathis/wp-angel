<div class="wrap wpil-report-page wpil_styles">
    <?=Wpil_Base::showVersion()?>
    <h1 class="wp-heading-inline wpil-is-tooltipped wpil-no-overlay wpil-no-scale" <?php echo Wpil_Toolbox::generate_tooltip_text('click-report-intro'); ?>>Clicks Report</h1>
    <hr class="wp-header-end">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div id="post-body-content" style="position: relative;">
                <?php include_once 'report_tabs.php'; ?>
                <div id="report_clicks">
                    <form>
                        <input type="hidden" name="page" value="link_whisper" />
                        <input type="hidden" name="type" value="clicks" />
                        <input type="hidden" name="click_delete_confirm_text" value="<?php esc_attr_e('Do you really want to delete all the click data in the row?', 'wpil'); ?>" />
                        <?php $table->search_box('Search', 'search'); ?>
                    </form>
                    <div class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-target-child wpil-tooltip-target.wp-list-table" style="display:inline-block" <?php echo Wpil_Toolbox::generate_tooltip_text('click-report-table'); ?>>
                        <?php $table->display(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var wpil_admin_url = '<?php echo admin_url()?>';
</script>