<?php
/*********************************************************************************
 * Created by Ante Drnasin - http://www.drnasin.com                              *
 * Copyright (c) 2017. All rights reserved.                                      *
 *                                                                               *
 * Project: Middleware Collection                                                *
 * Url: https://github.com/drnasin/middleware-collection                         *
 *                                                                               *
 * File: RequestHeadersValidation.php                                            *
 * Last Modified: 12.5.2017 23:06                                                *
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

namespace App\Middleware;

use App\Exceptions\HttpHeaderException;
use App\Response;
use Slim\Http\Request;

/**
 * Class RequestHeadersValidation
 * @package   App\Middleware
 * @author    Ante Drnasin

 */
class RequestHeadersValidation
{
    /**
     * @param Request  $request
     * @param Response $response
     * @param callable $next
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $next) : Response
    {
        try {
            if (!$request->hasHeader('Accept')) {
                throw new HttpHeaderException("'Accept' header not found in request");
            }

            $acceptHeader = $request->getHeaderLine('Accept');
            if ('application/json' !== $acceptHeader) {
                throw new HttpHeaderException(sprintf("value '%s' for 'Accept' header not allowed", $acceptHeader));
            }

            $mediaType = $request->getMediaType();
            if ('application/json' !== $mediaType) {
                throw new HttpHeaderException(sprintf("value '%s' for content type header not allowed", $mediaType));
            }

            $contentCharset = $request->getContentCharset();
            if ('utf-8' !== $contentCharset) {
                throw new HttpHeaderException(sprintf("charset '%s' not supported. use 'utf-8'", $contentCharset));
            }

            $mediaTypeParams = $request->getMediaTypeParams();
            if (isset($mediaTypeParams['version']) && '1' !== $mediaTypeParams['version']) {
                throw new HttpHeaderException(sprintf("version '%s' of content type is not supported. use version '1'",
                    $mediaTypeParams['version']));
            }

            return $next($request, $response);
        } catch (HttpHeaderException $e) {
            return $response->withAddedHeader('X-Status-Reason', $e->getMessage())->withStatus($e->getCode());
        }
    }
}