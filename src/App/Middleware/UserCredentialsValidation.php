<?php
/*********************************************************************************
 * Created by Ante Drnasin - http://www.drnasin.com                              *
 * Copyright (c) 2017. All rights reserved.                                      *
 *                                                                               *
 * Project Name: Middleware Collection                                           *
 * Repository: https://github.com/drnasin/middleware-collection                  *
 *                                                                               *
 * File: UserCredentialsValidation.php                                           *
 * Last Modified: 15.5.2017 17:14                                                *
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
use App\Exceptions\AuthenticationException;
use App\Exceptions\RequestParameterInvalidException;
use App\Exceptions\RequestParameterMissingException;
use App\Response;
use App\Utils\HttpStatusCodes;
use App\Validator;
use Respect\Validation\Exceptions\ValidationException;
use Slim\Container;
use Slim\Http\Request;

/**
 * Class UserCredentialsValidation
 * @package   App\Middleware
 * @author    Ante Drnasin
 */
class UserCredentialsValidation
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * UserCredentialsValidation constructor.
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

            $v = new Validator($this->container);

            // exceptionMode = true
            $v->validateParam($username, 'username');
            $v->validateParam($password, 'password');

            /** @var \App\Entities\User $user */
            $user = $this->container->get('doctrine-em')->getRepository('App\\Entities\\User')->findOneBy([
                'username' => $username,
                'password' => $password,
                'status'   => AbstractEntity::ACTIVE
            ]);

            if (!$user) {
                throw new AuthenticationException('no such user');
            }

            /** we are good to go */
            return $next($request, $response);
        } catch (ValidationException | AuthenticationException | RequestParameterMissingException | RequestParameterInvalidException $e) {
            return $response->withAddedHeader('X-Status-Reason', $e->getMessage())
                            ->withStatus($e->getCode() ?: HttpStatusCodes::STATUS_UNAUTHORIZED);
        }
    }
}