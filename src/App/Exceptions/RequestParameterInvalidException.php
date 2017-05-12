<?php
/*********************************************************************************
 * Created by Ante Drnasin - http://www.drnasin.com                              *
 * Copyright (c) 2017. All rights reserved.                                      *
 *                                                                               *
 * Project: Middleware Collection                                                *
 * Url: https://github.com/drnasin                                               *
 *                                                                               *
 * File: RequestParameterInvalidException.php                                    *
 * Last Modified: 12.5.2017 23:02                                                *
 *                                                                               *
 * Redistribution and use in source and binary forms, with or without            *
 * The MIT License (MIT)                                                         *
 *                                                                               *
 * Permission is hereby granted, free of charge, to any person obtaining a copy  *
 * of this software and associated documentation files (the "Software"), to deal *
 * in the Software without restriction, including without limitation the rights  *
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell     *
 * copies of the Software, and to permit persons to whom the Software is         *
 * furnished to do so, subject to the following conditions:                      *
 *                                                                               *
 * The above copyright notice and this permission notice shall be included in    *
 * all copies or substantial portions of the Software.                           *
 *                                                                               *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR    *
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,      *
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE   *
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER        *
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, *
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN     *
 * THE SOFTWARE.                                                                 *
 *********************************************************************************/

namespace App\Exceptions;

use App\Utils\HttpStatusCodes;

/**
 * Class RequestParameterInvalidException
 *
 * Exception uses Http Status code 400
 *
 * @package   App\Exceptions
 * @author    Ante Drnasin
 */
class RequestParameterInvalidException extends \Exception
{
    /**
     * RequestParameterInvalidException constructor.
     *
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        $message = sprintf("request parameter '%s' invalid", $message);
        parent::__construct($message, HttpStatusCodes::STATUS_BAD_REQUEST, $previous);
    }
}