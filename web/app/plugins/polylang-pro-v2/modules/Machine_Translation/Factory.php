<?php
/**
 * @package Polylang-Pro
 */

namespace WP_Syntex\Polylang_Pro\Modules\Machine_Translation;

use PLL_Model;
use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang_Pro\Modules\Machine_Translation\Services\Deepl;
use WP_Syntex\Polylang_Pro\Modules\Machine_Translation\Services\Service_Interface;

/**
 * Factory for machine translation services.
 *
 * @since 3.6
 */
class Factory {
	/**
	 * List of the service class names.
	 *
	 * @var string[]
	 *
	 * @phpstan-var non-empty-list<class-string<Service_Interface>>
	 */
	const SERVICES = array(
		Deepl::class,
	);

	/**
	 * List of the service instances.
	 *
	 * @var Service_Interface[]
	 *
	 * @phpstan-var array<non-falsy-string, Service_Interface>
	 */
	private $services = array();

	/**
	 * Stores the plugin options.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Polylang's model.
	 *
	 * @var PLL_Model
	 */
	private $model;

	/**
	 * Constructor.
	 *
	 * @since 3.6
	 *
	 * @param PLL_Model $model Polylang's model.
	 */
	public function __construct( PLL_Model $model ) {
		$this->options = $model->options;
		$this->model   = $model;
	}

	/**
	 * Tells if the machine translation feature is enabled.
	 *
	 * @since 3.6
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) $this->options['machine_translation_enabled'];
	}

	/**
	 * Returns the active service.
	 *
	 * @since 3.6
	 *
	 * @return Service_Interface|null
	 */
	public function get_active_service() {
		foreach ( static::get_classnames() as $service ) {
			$service = $this->build_service( $service );

			if ( $service->is_active() ) {
				return $service;
			}
		}

		return null;
	}

	/**
	 * Returns all services.
	 *
	 * @since 3.6
	 * @return Service_Interface[]
	 */
	public function get_all(): array {
		foreach ( static::get_classnames() as $service ) {
			$this->build_service( $service );
		}

		return $this->services;
	}

	/**
	 * Returns all services classnames for static usage.
	 *
	 * @since 3.7
	 *
	 * @return string[]
	 * @phpstan-return non-empty-list<class-string<Service_Interface>>
	 */
	public static function get_classnames(): array {
		return self::SERVICES;
	}

	/**
	 * Builds a service instance.
	 *
	 * @since 3.6
	 *
	 * @param string $class_name Service's slug.
	 * @return Service_Interface
	 *
	 * @phpstan-param class-string<Service_Interface> $class_name
	 */
	private function build_service( string $class_name ): Service_Interface {
		$slug = $class_name::get_slug();

		if ( ! empty( $this->services[ $slug ] ) ) {
			return $this->services[ $slug ];
		}

		$this->services[ $slug ] = new $class_name( $this->options['machine_translation_services'][ $slug ], $this->model );

		return $this->services[ $slug ];
	}
}
