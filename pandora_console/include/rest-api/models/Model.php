<?php

declare(strict_types=1);

namespace Models;

/**
 * This class should be extended to add functionalities to
 * fetch, validate, transform and represent data entities.
 */
abstract class Model
{

    /**
     * Internal data of the model.
     *
     * @var array
     */
    private $data;


    /**
     * Validate the received data structure to ensure if we can extract the
     * values required to build the model.
     *
     * @param array $data Input data.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If any input value is considered
     * invalid.
     *
     * @abstract
     */
    abstract protected function validateData(array $data): void;


    /**
     * Returns a valid representation of the model.
     *
     * @param array $data Input data.
     *
     * @return array Data structure representing the model.
     *
     * @abstract
     */
    abstract protected function decode(array $data): array;


    /**
     * Constructor of the model. It won't be public. The instances
     * will be created through factories which start with from*.
     *
     * @param array $unknownData Input data structure.
     */
    protected function __construct(array $unknownData)
    {
        $this->validateData($unknownData);
        $this->data = $this->decode($unknownData);
    }


    /**
     * Instance the class with the unknown input data.
     *
     * @param array $data Unknown data structure.
     *
     * @return self Instance of the model.
     */
    public static function fromArray(array $data)
    {
        // The reserved word static refers to the invoked class at runtime.
        return new static($data);
    }


    /**
     * Obtain a data structure from the database using a filter.
     *
     * @param array $filter Filter to retrieve the modeled element.
     *
     * @return array The modeled element data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     *
     * @abstract
     */
    abstract protected static function fetchDataFromDB(array $filter);


    /**
     * Obtain a model's instance from the database using a filter.
     *
     * @param array $filter Filter to retrieve the modeled element.
     *
     * @return self A modeled element's instance.
     */
    public static function fromDB(array $filter): self
    {
        // The reserved word static refers to the invoked class at runtime.
        return static::fromArray(static::fetchDataFromDB($filter));
    }


    /**
     * JSON representation of the model.
     *
     * @return string
     */
    public function toJson(): string
    {
        return \json_encode($this->data);
    }


    /**
     * Text representation of the model.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }


    /*
     * -------------
     * - UTILITIES -
     * -------------
     */


    /**
     * From a unknown value, it will try to extract a valid boolean value.
     *
     * @param mixed $value Unknown input.
     *
     * @return boolean Valid boolean value.
     */
    protected static function parseBool($value): bool
    {
        if (\is_bool($value) === true) {
            return $value;
        } else if (\is_numeric($value) === true) {
            return $value > 0;
        } else if (\is_string($value) === true) {
            return $value === '1' || $value === 'true';
        } else {
            return false;
        }
    }


    /**
     * Return a not empty string or a default value from a unknown value.
     *
     * @param mixed $val Input value.
     * @param mixed $def Default value.
     *
     * @return mixed A valid string (not empty) extracted from the input
     * or the default value.
     */
    protected static function notEmptyStringOr($val, $def)
    {
        return (\is_string($val) === true && strlen($val) > 0) ? $val : $def;
    }


    /**
     * Return a valid integer or a default value from a unknown value.
     *
     * @param mixed $val Input value.
     * @param mixed $def Default value.
     *
     * @return mixed A valid int extracted from the input or the default value.
     */
    protected static function parseIntOr($val, $def)
    {
        return (is_numeric($val) === true) ? (int) $val : $def;
    }


    /**
     * Get a value from a dictionary from a possible pool of keys.
     *
     * @param array $dict Input array.
     * @param array $keys Possible keys.
     *
     * @return mixed The first value found with the pool of keys or null.
     */
    protected static function issetInArray(array $dict, array $keys)
    {
        foreach ($keys as $key => $value) {
            if (isset($dict[$value]) === true) {
                return $dict[$value];
            }
        }

        return null;
    }


}
