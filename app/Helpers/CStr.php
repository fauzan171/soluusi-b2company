<?php

namespace App\Helpers;

class CStr
{
    /**
     * props for form
     * 
     * @return array
     */
    public static function getFormProps()
    {
        return [
            'action' => null,
            'method' => 'POST', // POST, GET, PUT, DELETE
            'enctype' => 'multipart/form-data',
        ];
    }
}
