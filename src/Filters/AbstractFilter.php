<?php

namespace JRDev\Imagery\Filters;

use Intervention\Image\Filters\FilterInterface;

abstract class AbstractFilter implements FilterInterface
{
    /**
     * Filter data
     *
     * @var mixed
     */
    protected $data;

    /**
     * Creates new instance of filter
     *
     * @param mixed $data
     */
    public function __construct($data = null)
    {
        $this->data = $data;
    }
}
