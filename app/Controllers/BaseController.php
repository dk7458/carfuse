<?php
/**
 * File Path: /app/controllers/base_controller.php
 * Description: Provides a base class for all controllers, handling dependency injection and shared logic.
 * Changelog:
 * - 2025-01-20: Initial creation with dependency injection system.
 * - 2025-01-25: Refactored to improve modularity and reduce coupling.
 */

namespace App\Controllers;

use App\Core\DependencyInjector;

class BaseController
{
    protected $di;

    public function __construct(DependencyInjector $di)
    {
        $this->di = $di;
    }

    /**
     * Get a dependency from the DI container.
     */
    protected function get($name)
    {
        return $this->di->get($name);
    }
}
?>
