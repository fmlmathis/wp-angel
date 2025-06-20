<?php

final class Wpil_Init
{
    /**
     * Store all the classes inside an array
     * @return array Full list of classes
     */
    public static function get_services()
    {
        return [
            Wpil_Base::class,
            Wpil_Error::class,
            Wpil_Keyword::class,
            Wpil_Link::class,
            Wpil_License::class,
            Wpil_Post::class,
            Wpil_Report::class,
            Wpil_StemmerLoader::class,
            Wpil_Term::class,
            Wpil_URLChanger::class,
            Wpil_TargetKeyword::class,
            Wpil_SiteConnector::class,
            Wpil_ClickTracker::class,
            Wpil_Rest::class,
            Wpil_Toolbox::class,
            Wpil_Widgets::class,
            Wpil_AI::class,
            Wpil_Sitemap::class,
            Wpil_Telemetry::class,
        ];
    }

    /**
     * Loop through the classes, initialize them,
     * and call the register() method if it exists
     * @return
     */
    public static function register_services()
    {
        foreach ( self::get_services() as $class ) {
            $service = self::instantiate( $class );
            if ( method_exists( $service, 'register' ) ) {
                $service->register();
            }
        }
    }

    /**
     * Initialize the class
     * @param  class $class    class from the services array
     * @return class instance  new instance of the class
     */
    private static function instantiate( $class )
    {
        $service = new $class();
        return $service;
    }
}
