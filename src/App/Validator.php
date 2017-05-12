<?php
/*********************************************************************************
 * Created by Ante Drnasin - http://www.drnasin.com                              *
 * Copyright (c) 2017. All rights reserved.                                      *
 *                                                                               *
 * Project: Middleware Collection                                                *
 * Url: https://github.com/drnasin/middleware-collection                         *
 *                                                                               *
 * File: Validator.php                                                           *
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

namespace App;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Respect\Validation\Validator as v;
use Slim\Container;

/**
 * Class Validator
 *
 * @package App
 * @author  Ante Drnasin
 */
class Validator
{
    /**
     * @var string regex
     */
    const UUID_REGEX_PATTERN = "/" . Uuid::VALID_PATTERN . "/";
    /**
     * @var string regex
     */
    const BCRYPT_PASSWORD_REGEX_PATTERN = '^\$2[ayb]\$.{56}^';
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var array
     */
    protected $databaseFields = [];

    /**
     * Validator constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Cheks if tha name of the parameter (not value) valid
     *
     * @param $param
     *
     * @return bool
     */
    public function isValidParameterName(string $param) : bool
    {
        return in_array($param, $this->getApiFields());
    }

    /**
    * @param $resource
    *
    * @return bool
    */
    public function isValidResource($resource) : bool
    {
        return in_array($resource, $this->getTableNames());
    }

    /**
     * Validates the given parameter
     *
     * @param mixed  $value Value to validate
     * @param string $useValidatorFor which validator to use?
     * @param bool   $exceptionMode Throw exceptions or return bool
     *
     * @return bool
     */
    public function validateParam($value, string $useValidatorFor, bool $exceptionMode = true) : bool
    {
        $validators = $this->getValidators();

        if (!isset($validators[$useValidatorFor])) {
            $this->container->get('logger')
                            ->error('RuntimeException - requested validator for request field is unknown',
                                [$useValidatorFor]);
            throw new \RuntimeException(sprintf("requested validator for '%s' doesn't exist. logging error",
                $useValidatorFor));
        }

        /** @var \Respect\Validation\Validator $validator */
        $validator = $validators[$useValidatorFor];

        if ($exceptionMode) {
            return $validator->check($value);
        }

        return $validator->validate($value);
    }

    /*********************************************************
     *              PROTECTED METHODS
     *********************************************************/

    /**
     * Same as getAllDatabaseFields but with filter
     *
     * @param array $filters Fileds that should NOT be in the returned array (ie. id's, passwords)
     *
     * @see getAllDatabaseFields()
     * @return array
     */
    protected function getApiFields(array $filters = []) : array
    {
        $fields = [];
        foreach ($this->getAllDatabaseFields() as $field) {
            if (!in_array($field, $filters)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Returns "distinct" list of all the table fields in the database.
     * @return array
     */
    protected function getAllDatabaseFields() : array
    {
        /** @var  \Doctrine\DBAL\Schema\MySqlSchemaManager $sm */
        $sm = $this->container->get('doctrine-schema-manager');

        $fields = [];
        foreach ($sm->listTables() as $table) {
            foreach ($table->getColumns() as $column) {
                $fieldName = $column->getName();
                if (!in_array($fieldName, $fields)) {
                    $fields[] = $fieldName;
                }
            }
        }

        return array_values($fields);
    }

    /**
     * @param bool $includePrefix
     *
     * @return array
     */
    protected function getTableNames(bool $includePrefix = false) : array
    {
        $tables = [
            'access_tokens',
            'animal_categories',
            'animals',
            'cool_room_entries',
            'cool_rooms',
            'districts',
            'events',
            'feeding_area_types',
            'feeding_areas',
            'galleries',
            'hunting_grounds',
            'images',
            'lookout_conditions',
            'lookout_reservations',
            'lookouts',
            'shots',
            'sightings',
            'users',
        ];

        /**
         * our table names don't change so no need for a call to database to check the names!
         * @todo add new table here
         */

        /** @var  \Doctrine\DBAL\Schema\MySqlSchemaManager $sm */
        /*$sm = $this->container['doctrine-schema-manager'];

        foreach($sm->listTables() as $table) {
            $tableName = ($includePrefix) ? $table->getName() : str_replace($prefix, "", $table->getName());
            $tables[] = $tableName;
        }*/

        if ($includePrefix) {
            $return = [];
            $prefix = $this->container->get('settings')['db']['prefix'];

            foreach ($tables as $tableName) {
                $return[] = sprintf('%s%s', $prefix, $tableName);
            }

            $tables = $return;
        }

        return $tables;
    }



    /**
     * @param array $filters
     *
     * @return array
     */
    protected function getValidators(array $filters = []) : array
    {

        $vRegexUuid = v::regex(self::UUID_REGEX_PATTERN);
        $vDate = v::date(DATE_ISO8601);

        $validators = [
            'id'                            => $vRegexUuid,
            'access_token'                  => v::stringType()->noWhitespace()->length(64),
            'name'                          => v::stringType()->length(3, 15),
            'category_id'                   => $vRegexUuid,
            'description'                   => v::stringType(),
            'cool_room_id'                  => $vRegexUuid,
            'animal_id'                     => $vRegexUuid,
            'animal_condition'              => $vRegexUuid,
            'animal_weight'                 => v::floatVal(),
            'delivered_at'                  => $vDate,
            'address'                       => v::stringType()->length(5, 50),
            'hunting_ground_id'             => $vRegexUuid,
            'coordinates'                   => v::stringType()->length(5, 100),
            'event_date'                    => $vDate,
            'status'                        => v::intVal()->length(2),
            'district_id'                   => $vRegexUuid,
            'type_id'                       => $vRegexUuid,
            'image'                         => v::stringType()->noWhitespace()->length(5, 100),
            'authority'                     => v::stringType()->length(5, 50),
            'society_identification_number' => v::numeric(),
            'vat_number'                    => v::stringType()->noWhitespace()->length(10),
            'registration_number'           => v::numeric(),
            'address_street'                => v::stringType()->length(5, 50),
            'address_zip'                   => v::numeric()->length(4),
            'address_city'                  => v::stringType()->length(5, 50),
            'gallery_id'                    => $vRegexUuid,
            'lookout_id'                    => $vRegexUuid,
            'start_date_time'               => $vDate,
            'end_date_time'                 => $vDate,
            'type'                          => v::intVal()->length(1, 2),
            'image_panorama'                => v::stringType()->noWhitespace()->length(5, 100),
            'shot_date'                     => $vDate,
            'sighted_at'                    => $vDate,
            'app_default_district_id'       => $vRegexUuid,
            'first_name'                    => v::stringType()->length(5, 100),
            'last_name'                     => v::stringType()->length(5, 100),
            'phone_number'                  => v::stringType()->length(5, 15),
            'username'                      => v::stringType()->noWhitespace()->length(2, 15),
            'password'                      => v::regex(self::BCRYPT_PASSWORD_REGEX_PATTERN),
            'email'                         => v::email(),
            'app_default_lang'              => v::alpha()->length(2)
        ];

        foreach ($validators as $field => $validator) {
            if (count($filters) && in_array($field, $filters)) {
                unset($validators[$field]);
            }

            $validator->setName(sprintf("validation: %s", $field));
        }

        return $validators;
    }
}