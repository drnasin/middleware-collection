<?php
/*********************************************************************************
 * Created by Ante Drnasin - http://www.drnasin.com                              *
 * Copyright (c) 2017. All rights reserved.                                      *
 *                                                                               *
 * Project Name: Middleware Collection                                           *
 * Repository: https://github.com/drnasin/middleware-collection                  *
 *                                                                               *
 * File: RequestTokenValidation.php                                              *
 * Last Modified: 15.5.2017 16:59                                                *
 *                                                                               *
 * The MIT License                                                               *
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

use App\Entities\AbstractEntity;
use App\Entities\User;
use App\Exceptions\AuthenticationException;
use App\Exceptions\HttpHeaderException;
use App\Exceptions\RequestParameterInvalidException;
use App\Exceptions\RequestParameterMissingException;
use App\Response;
use Slim\Container;
use Slim\Http\Request;

/**
 * Class RequestTokenValidation
 * Token based validation middleware.
 * @package   App\Middleware
 * @author    Ante Drnasin
 */
class RequestTokenValidation
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * RequestTokenValidation constructor.
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
     * @return Response
     * @throws AuthenticationException
     * @throws RequestParameterInvalidException
     * @throws RequestParameterMissingException
     */
    public function __invoke(Request $request, Response $response, callable $next) : Response
    {
        try {
            /** First let's check the Authorization header */
            if (!$request->hasHeader('Authorization')) {
                throw new HttpHeaderException('Authorization header missing');
            }
            if (!preg_match('#Bearer\s(\S+)#', $request->getHeaderLine('Authorization'), $matches)) {
                throw new HttpHeaderException('no Bearer token found in Authorization header');
            }

            $token = $matches[1];

            /**
             * If by any chance Access Token is already in session
             * let's cut this process short and just compare
             * the values before we go to full validation.
             * If they match we're good to go.
             */
            if (isset($_SESSION['access_token'])) {
                if ($token === $_SESSION['access_token']) {
                    return $next($request, $response);
                } else {
                    unset($_SESSION['access_token']);
                }
            }

            $username = $request->getParam('username');
            $password = $request->getParam('password');

            if (is_null($username)) {
                throw new RequestParameterMissingException('username');
            }

            if (is_null($password)) {
                throw new RequestParameterMissingException('password');
            }

            $username = filter_var($username, FILTER_SANITIZE_STRING);
            $password = filter_var($password, FILTER_SANITIZE_STRING);

            if (!$username) {
                throw new RequestParameterInvalidException('username');
            }

            if (!$password) {
                throw new RequestParameterInvalidException('password');
            }

            /** @var User $user */
            $user = $this->container->get('doctrine-em')->getRepository('App\\Entities\\User')->findOneBy([
                'username' => $username,
                'password' => $password,
                'status'   => AbstractEntity::ACTIVE
            ]);

            if (!$user) {
                throw new AuthenticationException('no user with such credentials');
            }

            /** @var AccessToken $accessToken */
            $accessToken = $user->getAccessToken();

            if (!$accessToken) {
                throw new AuthenticationException('user has no token. request new token by visiting /token/request');
            }

            if ($token !== $accessToken->getToken()) {
                throw new AuthenticationException('token mismatch');
            }

            if (!$accessToken->isValid()) {
                throw new AuthenticationException('token expired. request a new one');
            }

            //and finally!
            $_SESSION['access_token'] = $accessToken->getToken();

            return $next($request, $response);
        } catch (HttpHeaderException | RequestParameterInvalidException | RequestParameterMissingException | AuthenticationException $e) {
            return $response->withAddedHeader('X-Status-Reason', $e->getMessage())->withStatus($e->getCode());
        }
    }
}