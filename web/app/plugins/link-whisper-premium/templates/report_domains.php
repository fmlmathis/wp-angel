<div class="wrap wpil-report-page wpil_styles">
    <?=Wpil_Base::showVersion()?>
    <h1 class="wp-heading-inline wpil-is-tooltipped wpil-no-overlay wpil-no-scale" data-wpil-tooltip-read-time="2500" <?php echo Wpil_Toolbox::generate_tooltip_text('domain-report-intro'); ?>>Domains Report</h1>
    <hr class="wp-header-end">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div id="post-body-content" style="position: relative;">
                <input id="wpil-object-cache-flush-nonce" type="hidden" value="<?php echo wp_create_nonce('wpil-flush-object-cache'); ?>" />
                <?php include_once 'report_tabs.php'; ?>
                <div id="report_domains">
                    <div>
                        <?php echo $report_description; ?>
                        <form>
                            <input type="hidden" name="page" value="link_whisper" />
                            <input type="hidden" name="type" value="domains" />
                            <?php $table->search_box('Search', 'search'); ?>
                        </form>
                    </div>
                    <div class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-target-child wpil-tooltip-target.wp-list-table wpil-tooltip-no-position" data-wpil-tooltip-read-time="4500" <?php echo Wpil_Toolbox::generate_tooltip_text('domain-report-table'); ?>>
                        <?php $table->display(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
