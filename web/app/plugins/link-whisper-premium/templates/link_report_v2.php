<div class="wrap wpil-report-page wpil_styles">
    <?=Wpil_Base::showVersion()?>
    <?php $user = wp_get_current_user(); ?>
    <h1 class="wp-heading-inline wpil-is-tooltipped wpil-no-overlay wpil-no-scale" data-wpil-tooltip-read-time="4500" <?php echo Wpil_Toolbox::generate_tooltip_text('link-report-header'); ?>><?php echo (isset($_GET['orphaned'])) ? __('Orphaned Posts Report', 'wpil') : __('Internal Links Report', 'wpil');?></h1>
    <hr class="wp-header-end">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div id="post-body-content" style="position: relative;">
                <input id="wpil-object-cache-flush-nonce" type="hidden" value="<?php echo wp_create_nonce('wpil-flush-object-cache'); ?>" />
                <?php include_once 'report_tabs.php'; ?>
                <div class="tbl-link-reports">
                    <div>
                        <form>
                            <input type="hidden" name="page" value="link_whisper" />
                            <input type="hidden" name="type" value="links" />
                            <?php $tbl->search_box('Search', 'search_posts'); ?>
                        </form>
                    </div>
                    <div class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-target-child wpil-tooltip-target.linkingstats" style="display:inline-block" <?php echo Wpil_Toolbox::generate_tooltip_text('link-report-table'); ?>>
                        <?php $tbl->display(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var wpil_admin_url = '<?php echo admin_url()?>';
</script>
