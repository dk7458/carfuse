<?php

namespace App\Helpers;

class LoggingHelper
{
    public function getDefaultLogger()
    {
        return $this->getLoggerByCategory('default');
    }

    public function getLoggerByCategory($category)
    {
        // Call the global getLogger function
        return \getLogger($category);
    }
}
