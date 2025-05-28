<?php

class Wpil_Rest
{
    const REST_SLUG = 'link-whisper';

    const GSC_ROUTE     = 'code';
    const SI_ROUTE      = 'site-interlinking';

    public function register ()
    {
        $this->register_rest();
        add_action('plugins_loaded', [$this, 'whitelist_json_endpoints']);
    }

    public function register_rest ()
    {
        add_action('rest_api_init', function ( $wp_rest_server )
        {
            /**
             * @var WP_REST_Server $wp_rest_server
             */

            register_rest_route(self::REST_SLUG, self::GSC_ROUTE, [
                'methods'             => 'POST',
                'callback'            => [
                    $this,
                    'handler_rest'
                ],
                'permission_callback' => "__return_true",
                'show_in_index'       => false
            ]);

            /**
             * @var WP_REST_Server $wp_rest_server
             */
            register_rest_route(self::REST_SLUG, self::SI_ROUTE, [
                'methods'             => 'POST, GET',
                'callback'            => [
                    $this,
                    'site_interlinking_handler'
                ],
                'permission_callback' => "__return_true",
                'show_in_index'       => false
            ]);
        });
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return string|\WP_Error
     */
    public function handler_rest ( WP_REST_Request $request )
    {
        if ( !empty($request->get_param('code')) ) {
            $code     = $request->get_param('code');
            $response = Wpil_SearchConsole::get_access_token(trim($code));

            $message = [
                'status' => $response['access_valid'],
                'text'   => $response['message']
            ];

            set_transient('wpil_gsc_access_status_message', $message, 20);

            if ( !empty($response['access_valid']) ) {
                // track that the authentication happened
                Wpil_Telemetry::log_event('gsc_connection_authorized');
                // and update the flag so we know it's live
                update_option('wpil_gsc_app_authorized', true);
            }

            return 'ok';
        } elseif ( !empty($request->get_param('error')) ) {
            $message = [
                'status' => false,
                'text'   => __('Access denied', 'rank-logic')
            ];

            set_transient('wpil_gsc_access_status_message', $message, 20);
        }

        return new WP_Error(400, 'Bad request', [ 'status' => 404 ]);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return string|\WP_Error
     */
    public function site_interlinking_handler ( WP_REST_Request $request )
    {
        die(); // die because we do the validation elsewhere at the moment
    }

    /**
     * Adds the link whisper json endpoint to any known whitelists so the GSC connection attempts aren't blocked
     **/
    public function whitelist_json_endpoints(){
        if(class_exists('Clearfy_Plugin')){
            add_filter('clearfy_rest_api_white_list', array($this, 'add_directly'));
        }

        if(defined('PERFMATTERS_VERSION')){
            add_filter('perfmatters_rest_api_exceptions', array($this, 'add_directly'));
        }
    }

    /**
     * Adds the json endpoint directly to an array of endpoint names
     **/
    public function add_directly($whitelist = array()){
        if(is_array($whitelist) && !in_array('link-whisper', $whitelist)){
            $whitelist[] = 'link-whisper';
        }

        return $whitelist;
    }
}
