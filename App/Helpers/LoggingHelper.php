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
        // Assuming getLogger is a global function that accepts a category
        return getLogger($category);
    }
}
