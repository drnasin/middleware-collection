<?php
/*********************************************************************************
 * Created by Ante Drnasin - http://www.drnasin.com                              *
 * Copyright (c) 2017. All rights reserved.                                      *
 *                                                                               *
 * Project: Middleware Collection                                                *
 * Url: https://github.com/drnasin                                               *
 *                                                                               *
 * File: HttpStatusCodes.php                                                     *
 * Last Modified: 12.5.2017 22:13                                                *
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

namespace App\Utils;

/**
 * Class HttpStatusCodes
 * @package   App\Utils
 * @author    Ante Drnasin
 * @copyright Ante Drnasin
 */
class HttpStatusCodes
{
    /**
     * General success appStatus code.
     * Most common code to indicate success.
     * @var integer
     */
    const STATUS_OK = 200;
    /**
     * Successful creation occurred (via either POST or PUT)
     * (Set the Location header to contain a link to the newly-createdAt resource. Response body content may or may not
     * be present.)
     * @var integer
     */
    const STATUS_CREATED = 201;
    /**
     * General error when fulfilling the request would cause an invalid state. Domain validation errors, missing data,
     * etc. are some examples.
     * @var integer
     */
    const STATUS_BAD_REQUEST = 400;
    /**
     * Error code for a missing or invalid authentication token.
     * @var integer
     */
    const STATUS_UNAUTHORIZED = 401;
    /**
     * Error code for user not authorized to perform the operation, doesn't have rights to access the resource, or the
     * resource is unavailable for some reason (e.g. time constraints, etc.).
     * @var integer
     */
    const STATUS_FORBIDDEN = 403;
    /**
     * Used when the requested resource is not found, whether it doesn't exist or if there was a 401 or 403 that, for
     * security reasons, the service wants to mask.
     * @var integer
     */
    const STATUS_NOT_FOUND = 404;
    /**
     * Used when we get a call for MTHOD (ie DELETE) where it shouldn't ne issued
     * @var integer
     */
    const STATUS_METHOD_NOT_ALLOWED = 405;
    /**
     * Only returned in HeaderParameterAuthentication middleware
     * @var integer
     */
    const STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
    /**
     * The general catch-all error when the server-side throws anexception.
     * @var integer
     */
    const STATUS_INTERNAL_SERVER_ERROR = 500;
    /**
     * Meybe we return 501 insetad of 405?
     * @var integer
     */
    const STATUS_SERVICE_UNAVAILABLE = 501;
}