<?php
/**
 * File Path: /app/core/dependency_injector.php
 * Description: Implements a dependency injection container for managing service instances and reducing tight coupling.
 * Changelog:
 * - 2025-01-20: Created basic DI container.
 * - 2025-01-25: Enhanced to allow singleton and factory services.
 */

 namespace App\Controllers\Utils;

class DependencyInjector
{
    private $services = [];
    private $sharedInstances = [];

    /**
     * Register a service in the DI container.
     * @param string $name The service name.
     * @param callable $definition The factory function or instance.
     * @param bool $shared Whether the service should be a singleton.
     */
    public function register($name, $definition, $shared = true)
    {
        $this->services[$name] = compact('definition', 'shared');
    }

    /**
     * Retrieve a service from the container.
     * @param string $name The service name.
     * @return mixed The resolved service.
     */
    public function get($name)
    {
        if (!isset($this->services[$name])) {
            throw new \Exception("Service '$name' not found in container.");
        }

        $service = $this->services[$name];

        if ($service['shared']) {
            if (!isset($this->sharedInstances[$name])) {
                $this->sharedInstances[$name] = call_user_func($service['definition'], $this);
            }
            return $this->sharedInstances[$name];
        }

        return call_user_func($service['definition'], $this);
    }
}
?>
