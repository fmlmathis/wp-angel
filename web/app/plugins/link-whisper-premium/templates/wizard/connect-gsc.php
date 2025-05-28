<?php
// if the user has authed GSC, check the status
if(Wpil_Settings::HasGSCCredentials()){
    Wpil_SearchConsole::refresh_auth_token();
    $authenticated = Wpil_SearchConsole::is_authenticated();
}else{
    $authenticated = false;
}

$authorized = get_option('wpil_gsc_app_authorized', false);
$has_custom = !empty(get_option('wpil_gsc_custom_config', false)) ? true : false;
$auth_message = (!$has_custom) ? __('Connect Link Whisper', 'wpil'): __('Authorize Your App', 'wpil');

?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=IBM+Plex+Serif:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
<div class="wpil-setup-wizard wrap wpil_styles wizard-connect-gsc wpil-wizard-page wpil-wizard-page-hidden">
    <div id="wpil-setup-wizard-progress-loading"><div id="wpil-setup-wizard-progress-loading-bar" style="width:50%"></div></div>
    <div id="wpil-setup-wizard-progress">
        <div class="complete"><a href="<?php echo admin_url('admin.php?page=link_whisper_wizard&wpil_wizard=license');?>" class="wpil-wizard-link" data-wpil-wizard-link-id="license"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('License Activation', 'wpil'); ?></a></div>
        <div class="complete"><a href="<?php echo admin_url('admin.php?page=link_whisper_settings&wpil_wizard=about-you');?>" class="wpil-wizard-link" data-wpil-wizard-link-id="about-you"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Settings Configuration', 'wpil'); ?></a></div>
        <div class="complete"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Connect to Google Search Console', 'wpil'); ?></div>
        <div><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Connect to OpenAI', 'wpil'); ?></div>
        <div><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Complete Installation', 'wpil'); ?></div>
    </div>
    <div class="wpil-setup-wizard-content" style="/*height: 550px;*/">
        <a href="<?php echo admin_url();?>" class="wpil-wizard-exit-button">EXIT WIZARD</a>
        <div id="wpil-setup-wizard-heading-container">
            <img src="<?php echo WP_INTERNAL_LINKING_PLUGIN_URL . '/images/lw-icon.png' ?>" width="128px" height="128px">
            <?php if(empty($authenticated) || empty($authorized)){ ?>
                <h1 id="wpil-setup-wizard-heading"><?php esc_html_e('Connected to Google Search Console!', 'wpil'); ?></h1>
                <p class="wpil-setup-wizard-sub-heading"><?php esc_html_e('Link Whisper is connected to Google Search Console and will use its keywords to make better links!.', 'wpil'); ?></p>
            <?php } else{ ?>
            <h1 id="wpil-setup-wizard-heading"><?php esc_html_e('Connect to Google Search Console?', 'wpil'); ?></h1>
            <p class="wpil-setup-wizard-sub-heading"><?php esc_html_e('Link Whisper makes much better links when connected to Google Search Console.', 'wpil'); ?></p>
            <?php } ?>
        </div>
        <div>
            <div>
            <?php
                if(empty($authenticated) || empty($authorized)){ ?>
                    <div class="wpil_gsc_app_inputs">
                        <input style="width: 100%;max-width: 400px;margin: 0 0 10px 0;" id="wpil_gsc_access_code" class="wpil_gsc_get_authorize" type="text" name="wpil_gsc_access_code"/>
                        <label for="wpil_gsc_access_code" class="wpil_gsc_get_authorize"><a class="wpil_gsc_enter_app_creds wpil_gsc_button button-primary"><?php esc_html_e('Authorize', 'wpil'); ?></a></label>
                        <a style="margin-top:5px; font-family:'Barlow', sans-serif; padding: 15px 40px !important; font-size: 18px !important;" class="wpil-get-gsc-access-token button-primary" target="_blank" href="<?php echo Wpil_Settings::getGSCAuthUrl(true); ?>"><?php echo $auth_message; ?></a>
                    </div>
                <?php }else{ ?>
                    <a href="<?php echo admin_url('admin.php?page=link_whisper_settings&wpil_wizard=connect-openai');?>" class="wpil-wizard-link" data-wpil-wizard-link-id="connect-openai" style="font-size: 20px;"><?php esc_html_e('Awesome! Let\'s proceed', 'wpil'); ?></a>
                <?php } ?>
            </div>
        </div>
        <br><br>
        <div>
            <?php if($authorized && $authorized){ ?>
                <!--<a href="<?php echo admin_url('admin.php?page=link_whisper_settings&wpil_wizard=connect-openai');?>" class="wpil-wizard-link" data-wpil-wizard-link-id="connect-openai" style="font-size: 20px;"><?php esc_html_e('Awesome! Let\'s proceed', 'wpil'); ?></a>-->
                <a class="wpil-gsc-deactivate-app button-primary" style="margin-top:5px; font-family:'Barlow', sans-serif; padding: 15px 40px !important; font-size: 18px !important;" data-nonce="<?php echo wp_create_nonce('disconnect-gsc'); ?>"><?php esc_html_e('Deactivate', 'wpil'); ?></a>
            <?php }else{ ?>
            <a href="<?php echo admin_url('admin.php?page=link_whisper_settings&wpil_wizard=connect-openai');?>" class="wpil-wizard-link" data-wpil-wizard-link-id="connect-openai" style="font-size: 20px;"><?php esc_html_e('Not right now, maybe later', 'wpil'); ?></a>
            <?php } ?>
        </div>
    </div>
</div>