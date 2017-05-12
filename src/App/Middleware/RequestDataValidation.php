<?php
/*********************************************************************************
 * Created by Ante Drnasin - http://www.drnasin.com                              *
 * Copyright (c) 2017. All rights reserved.                                      *
 *                                                                               *
 * Project: Middleware Collection                                                *
 * Url: https://github.com/drnasin/middleware-collection                         *
 *                                                                               *
 * File: RequestDataValidation.php                                               *
 * Last Modified: 12.5.2017 23:28                                                *
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

use App\Exceptions\RequestParameterInvalidException;
use App\Exceptions\RequestParameterMissingException;
use App\Exceptions\RequestParameterUnknownException;
use App\Response;
use App\Utils\HttpStatusCodes;
use App\Validator;
use Respect\Validation\Exceptions\ValidationException;
use Slim\Container;
use Slim\Http\Request;

/**
 * Class RequestDataValidation
 * @package   App\Middleware
 * @author    Ante Drnasin
 */
class RequestDataValidation
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * RequestDataValidation constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param callable $next
     *
     * @return Response|\Slim\Http\Response
     */
    public function __invoke(Request $request, Response $response, callable $next) : Response
    {
        try {
            /**
             * This middleware is only used if request method is POST, PUT, PATCH
             * Those are the only methods that should carry request data fields.
             */
            if ($request->isPut() || $request->isPost() || $request->isPatch()) {
                $requestParams = $request->getParams();

                if (!isset($requestParams['data']) || !count($requestParams['data'])) {
                    throw new RequestParameterMissingException('data');
                }

                $requestData = $request->getParam('data');

                /** silently remove id from the request data */
                if (isset($requestData['id'])) {
                    unset($requestData['id']);
                }

                /** let's sanitize the data first */
                foreach ($requestData as $field => $value) {
                    $field = strtolower(filter_var(trim($field), FILTER_SANITIZE_STRING));
                    $value = filter_var(trim($value), FILTER_SANITIZE_STRING);

                    if (!$field) {
                        throw new RequestParameterInvalidException($field);
                    }

                    $requestData[$field] = $value;
                }

                /** let's check to see if the field names are valid and give back the info about which ones are wrong */
                $v = new Validator($this->container);
                $invalidRequestFields = [];
                foreach ($requestData as $field => $value) {
                    if (false === $v->isValidParameterName($field)) {
                        $invalidRequestFields[] = $field;
                    }
                }

                if (count($invalidRequestFields)) {
                    throw new RequestParameterUnknownException(implode(',', $invalidRequestFields));
                }

                /** let's validate! if anything goes wrong we will get a ValidationException */
                foreach ($requestData as $field => $value) {
                    $v->validateParam($value, $field);
                }

                /** overwrite the request data with our clean and sanitized data and we are good to go! */
                $request->getParams()['data'] = $requestData;
            }

            return $next($request, $response);
        } catch (RequestParameterMissingException | RequestParameterUnknownException | ValidationException | RequestParameterInvalidException $e) {
            return $response->withAddedHeader('X-Status-Reason', $e->getMessage())
                            ->withStatus($e->getCode() ?: HttpStatusCodes::STATUS_BAD_REQUEST);
        }
    }
}