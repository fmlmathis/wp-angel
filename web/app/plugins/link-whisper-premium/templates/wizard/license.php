<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=IBM+Plex+Serif:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
<script>
    var hasLicense = "<?php echo (Wpil_License::isValid()) ? 1: 0; ?>";
</script>
<div class="wpil-setup-wizard wrap wpil_styles wizard-license wpil-wizard-page">
    <div id="wpil-setup-wizard-progress">
        <div class="complete"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('License Activation', 'wpil'); ?></div>
        <div><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Settings Configuration', 'wpil'); ?></div>
        <div><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Connect to Google Search Console', 'wpil'); ?></div>
        <div><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Connect to OpenAI', 'wpil'); ?></div>
        <div><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Complete Installation', 'wpil'); ?></div>
    </div>
    <div class="wpil-setup-wizard-content" style="/*height: 550px;*/padding-left:110px;padding-right:110px;">
        <a href="<?php echo admin_url();?>" class="wpil-wizard-exit-button">EXIT WIZARD</a>
        <div id="wpil-setup-wizard-heading-container">
            <img src="<?php echo WP_INTERNAL_LINKING_PLUGIN_URL . '/images/lw-icon.png' ?>" width="128px" height="128px">
            <h1 id="wpil-setup-wizard-heading"><?php esc_html_e('Welcome to Link Whisper!', 'wpil'); ?></h1>
            <p class="wpil-setup-wizard-sub-heading"><?php esc_html_e('Your journey to better internal linking begins here.', 'wpil'); ?></p>
        </div>
        <div>
            <p class="wpil-setup-wizard-normal-text" style="font-size: 20px !important;">
                <?php esc_html_e('First, enter your license key.', 'wpil'); ?>
            </p>
            <div>
                <form id="wpil-setup-wizard-license-activate" method="post">
                    <?php settings_fields('wpil_license'); ?>
                    <input type="hidden" name="hidden_action" value="activate_license">
                    <div>
                        <div style="position: relative; display: inline-block;">
                            <input id="wpil_license_key" name="wpil_license_key" type="text" class="regular-text" value="" />
                            <div style="display:none;" class="wpil-setup-wizard-loading la-ball-clip-rotate la-md"><div></div></div>
                        </div>
                        <br>
                        <br>
                        <a style="font-size: 16px !important; font-weight:bold; margin: 0 0 0 15px;" href="https://linkwhisper.com/knowledge-base/how-to-install-and-activate-link-whisper/#activating-the-link-whisper-license" target="_blank">[<?php esc_html_e('Need help finding your key?', 'wpil'); ?>]</a>
                    </div>
                    <?php wp_nonce_field( 'wpil_activate_license_nonce', 'wpil_activate_license_nonce' ); ?>
                    <!--<br>
                    <p class="wpil-setup-wizard-normal-text"><?php esc_html_e('Then click "Verify Key" to activate.', 'wpil'); ?></p>
                    <div>
                        <input type="submit" class="wpil-setup-wizard-submit-button" style="font-family:'Barlow', sans-serif; padding: 15px 40px !important; font-size: 18px !important; cursor:pointer" value="<?php esc_attr_e('Verify Key', 'wpil'); ?>">
                    </div>
                    <br>
                    <div class="wpil-setup-wizard-message">
                        <p class="wpil_licensing_status_text"></p>
                        <div style="display:none;" class="wpil-setup-wizard-loading la-ball-clip-rotate la-md"><div></div></div>
                    </div>-->
                </form>
            </div>
        </div>
    </div>
</div>