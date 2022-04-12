<?php

namespace App\Components;

use Exception;
use Phalcon\Escaper;


/**
 * escaper class return sanitize outputs
 */
class MyEscaper
{
    public function __construct()
    {
        $this->escaper = new Escaper();
    }
    public function sanitize($html)
    {
        try {
            return $this->escaper->escapeHtml($html);
        } catch (Exception $e) {
            print_r($e);
        }
    }
}
