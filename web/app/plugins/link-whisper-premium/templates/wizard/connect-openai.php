<?php
$connected_to_oai = !empty(Wpil_Settings::getOpenAIKey());
?>
<div class="wpil-setup-wizard wrap wpil_styles wizard-connect-openai wpil-wizard-page wpil-wizard-page-hidden">
    <div id="wpil-setup-wizard-progress-loading"><div id="wpil-setup-wizard-progress-loading-bar" style="width: 70%"></div></div>
    <div id="wpil-setup-wizard-progress">
        <div class="complete"><a href="<?php echo admin_url('admin.php?page=link_whisper_wizard&wpil_wizard=license');?>" class="wpil-wizard-link" data-wpil-wizard-link-id="license"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('License Activation', 'wpil'); ?></a></div>
        <div class="complete"><a href="<?php echo admin_url('admin.php?page=link_whisper_wizard&wpil_wizard=about-you');?>" class="wpil-wizard-link" data-wpil-wizard-link-id="about-you"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Settings Configuration', 'wpil'); ?></a></div>
        <div class="complete"><a href="<?php echo admin_url('admin.php?page=link_whisper_wizard&wpil_wizard=connect-gsc');?>" class="wpil-wizard-link" data-wpil-wizard-link-id="connect-gsc"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Connect to Google Search Console', 'wpil'); ?></a></div>
        <div class="complete"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Connect to OpenAI', 'wpil'); ?></div>
        <div><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Complete Installation', 'wpil'); ?></div>
    </div>
    <div class="wpil-setup-wizard-content" style="/*height: 600px;*/">
        <a href="<?php echo admin_url();?>" class="wpil-wizard-exit-button">EXIT WIZARD</a>
        <?php if($connected_to_oai){ ?>
            <div id="wpil-setup-wizard-heading-container">
                <img src="<?php echo WP_INTERNAL_LINKING_PLUGIN_URL . '/images/lw-icon.png' ?>" width="128px" height="128px">
                <h1 id="wpil-setup-wizard-heading"><?php esc_html_e('Connected to OpenAI!', 'wpil'); ?></h1>
                <p class="wpil-setup-wizard-sub-heading"><?php esc_html_e('Link Whisper uses OpenAI for powerful AI features and to make smarter link suggestions.', 'wpil'); ?></p>
                <p class="wpil-setup-wizard-sub-heading"><?php esc_html_e('You\'re connected to OpenAI and have full access to Link Whisper\'s AI Features!', 'wpil'); ?></p>
            </div>
            <div>
            </div>
            <div>
                <a href="<?php echo admin_url('admin.php?page=link_whisper_settings&wpil_wizard=run-setup');?>" class="wpil-wizard-link button-primary wpil-setup-wizard-main-button" data-wpil-wizard-link-id="run-setup" style="font-size: 20px;"><?php esc_html_e('Awesome! Let\'s run the setup!', 'wpil'); ?></a>
                <br><br>
            </div>
        <?php }else{?>
            <div id="wpil-setup-wizard-heading-container">
                <img src="<?php echo WP_INTERNAL_LINKING_PLUGIN_URL . '/images/lw-icon.png' ?>" width="128px" height="128px">
                <h1 id="wpil-setup-wizard-heading"><?php esc_html_e('Connect to OpenAI?', 'wpil'); ?></h1>
                <p class="wpil-setup-wizard-sub-heading"><?php esc_html_e('Link Whisper uses OpenAI for powerful AI features and to make smarter link suggestions.', 'wpil'); ?></p>
                <p class="wpil-setup-wizard-sub-heading"><?php esc_html_e('If you don\'t want to use OpenAI, no worries, we\'ll still set it up using our original keyword-based methods.', 'wpil'); ?></p>
            </div>
            <div>
                <div>
                    <a style="font-size: 16px !important; margin: 0 0 0 15px; font-weight:600" href="https://linkwhisper.com/knowledge-base/how-do-i-get-my-open-ai-key/" target="_blank">[<?php esc_html_e('How to get your OpenAI API key', 'wpil'); ?>]</a>
                    <br>
                    <br>
                    <input type="text" name="wpil_open_ai_api_key" id="wpil_open_ai_api_key" class="regular-text" value="<?php //echo esc_attr(Wpil_Settings::getOpenAIKey(true)); ?>">
                </div>
                <br>
                <div style="position:relative; display:inline-block;">
                    <a class="button-primary button-disabled wpil-setup-wizard-main-button wpil-wizard-activate-oai-button" data-wpil-nonce="<?php echo wp_create_nonce(get_current_user_id() . 'wpil_wizard_save_nonce'); ?>"><?php esc_html_e('Activate', 'wpil'); ?></a>
                    <div style="display:none;" class="wpil-setup-wizard-loading la-ball-clip-rotate la-md"><div></div></div>
                </div>
            </div>
            <br><br>
            <div>
                <a href="<?php echo admin_url('admin.php?page=link_whisper_settings&wpil_wizard=run-setup');?>" class="wpil-wizard-link" data-wpil-wizard-link-id="run-setup" style="font-size: 20px;"><?php esc_html_e('Not right now, maybe later', 'wpil'); ?></a>
            </div>
        <?php } ?>
    </div>
</div>