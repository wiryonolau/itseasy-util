<?php

declare(strict_types=1);

namespace Itseasy\Model;

use ArrayIterator;
use ArrayObject;
use Laminas\Stdlib\ArrayUtils;
use ValueError;

class ParameterSetModel extends ArrayObject
{
    public function __construct()
    {
        parent::__construct([], ArrayObject::STD_PROP_LIST, ArrayIterator::class);
    }

    protected function validateValue($value): void
    {
        // Check if the value is either string, int, float, or bool
        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value) || is_null($value)) {
            return;
        }
        throw new ValueError("Value must be string|int|float|bool|null");
    }

    public function setParameter(string $name, $value): void
    {
        $this->validateValue($value);
        $this->offsetSet($name, $value);
    }

    public function getParameter(string $name)
    {
        $value = $this->offsetGet($name);
        $this->validateValue($value);
        return $value;
    }

    public function hasParameter(string $name): bool
    {
        return $this->offsetExists($name);
    }

    public function append($item): void
    {
        if (!is_array($item)) {
            throw new ValueError("Parameter must consist of [key, value] or [key => key, value => value] array");
        }

        if (count($item) != 2) {
            throw new ValueError("Parameter must consist of [key, value] or [key => key, value => value] array");
        }

        if (isset($item["key"]) and isset($item["value"])) {
            $this->setParameter($item["key"], $item["value"]);
        } else {
            list($key, $value) = $item;
            $this->setParameter($key, $value);
        }
    }

    public function populate($data): void
    {
        if (is_null($data)) {
            return;
        }

        if (is_string($data)) {
            $data = json_decode($data, true);

            if ($data === false or is_null($data)) {
                throw new ValueError("value must be an array or implement Traversable");
            }
        }

        if (!(is_array($data) or !($data instanceof Traversable))) {
            throw new ValueError("value must be an array or implement Traversable");
        }

        if (ArrayUtils::hasStringKeys($data)) {
            foreach ($data as $index => $row) {
                $this->setParameter($index, $row);
            }
        } else {
            foreach ($data as $row) {
                $this->append($row);
            }
        }
    }

    public function getArrayCopy(): array
    {
        return $this->getIterator()->getArrayCopy();
    }

    public function toJson(int $flags = 0, int $depth = 512): string
    {
        $flags |= JSON_THROW_ON_ERROR;

        return json_encode($this->getArrayCopy(), $flags, $depth);
    }
}
