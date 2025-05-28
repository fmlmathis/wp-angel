<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=IBM+Plex+Serif:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
<div class="wpil-setup-wizard wrap wpil_styles wizard-about-you wpil-wizard-page wpil-wizard-page-hidden">
    <div id="wpil-setup-wizard-progress-loading"><div id="wpil-setup-wizard-progress-loading-bar" style="width:25%"></div></div>
    <div id="wpil-setup-wizard-progress">
        <div class="complete"><a href="<?php echo admin_url('admin.php?page=link_whisper_wizard&wpil_wizard=license');?>" class="wpil-wizard-link" data-wpil-wizard-link-id="license"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('License Activation', 'wpil'); ?></a></div>
        <div class="complete"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Settings Configuration', 'wpil'); ?></div>
        <div><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Connect to Google Search Console', 'wpil'); ?></div>
        <div><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Connect to OpenAI', 'wpil'); ?></div>
        <div><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Complete Installation', 'wpil'); ?></div>
    </div>
    <div class="wpil-setup-wizard-content" style="/*height: 580px;*/">
        <a href="<?php echo admin_url();?>" class="wpil-wizard-exit-button">EXIT WIZARD</a>
        <div id="wpil-setup-wizard-heading-container">
            <img src="<?php echo WP_INTERNAL_LINKING_PLUGIN_URL . '/images/lw-icon.png' ?>" width="128px" height="128px">
            <h1 id="wpil-setup-wizard-heading"><?php esc_html_e('Use best practices for Link Whisper setup?', 'wpil'); ?></h1>
        </div>
        <div class="wpil-setup-wizard-radio-button-wrapper">
            <div class="wpil-setup-wizard-radio-button-container">
                <label class="wpil-setup-wizard-radio-button"><input type="radio" class="wpil-setup-wizard-radio" name="wpil_setup_wizard_configure_settings" value="yes" required><div style="margin-left:35px;font-size:19px;font-weight:400;"><?php esc_html_e('Yes, configure away, genius! 🙂', 'wpil'); ?></div></label>
            </div>
            <div class="wpil-setup-wizard-radio-button-container">
                <label class="wpil-setup-wizard-radio-button"><input type="radio" class="wpil-setup-wizard-radio" name="wpil_setup_wizard_configure_settings" value="no"><div style="margin-left:35px;font-size:19px;font-weight:400;"><?php esc_html_e('No, I will set it up.', 'wpil'); ?></div></label>
            </div>
        </div>
        <br><br>
        <div style="position:relative; display:inline-block;">
            <a class="button-primary button-disabled wpil-setup-wizard-main-button wpil-wizard-about-you-next-button" data-wpil-nonce="<?php echo wp_create_nonce(get_current_user_id() . 'wpil_wizard_save_nonce'); ?>"><?php esc_html_e('Next', 'wpil'); ?></a>
            <div style="display:none;" class="wpil-setup-wizard-loading la-ball-clip-rotate la-md"><div></div></div>
        </div>
    </div>
</div>