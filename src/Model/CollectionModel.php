<?php

declare(strict_types=1);

namespace Itseasy\Model;

use ArrayIterator;
use ArrayObject;
use Exception;
use Laminas\Stdlib\ArraySerializableInterface;
use Traversable;

class CollectionModel extends ArrayObject implements ArraySerializableInterface
{
    private $objectPrototype = null;

    public function __construct(
        $objectPrototype = null,
        $data = [],
        int $flags = ArrayObject::STD_PROP_LIST,
        string $iteratorClass = ArrayIterator::class
    ) {
        parent::__construct([], $flags, $iteratorClass);

        if (!is_null($objectPrototype)) {
            $this->setObjectPrototype($objectPrototype);
        }

        $this->populate($data);
    }

    public function setObjectPrototype($object): void
    {
        if (is_string($object) and class_exists($object)) {
            $object = new $object;
        }

        if (!is_object($object)) {
            throw new Exception("Object not exist");
        }

        $this->objectPrototype = $object;
    }

    /*
     * return object
     */
    public function getObjectPrototype()
    {
        return $this->objectPrototype;
    }


    public function append($item): void
    {
        if (is_null($this->getObjectPrototype())) {
            parent::append($item);
        } else if (is_array($item)) {
            $obj = clone $this->getObjectPrototype();
            $obj->populate($item);
            parent::append($obj);
        } else {
            $instance = get_class($this->getObjectPrototype());
            if ($item instanceof $instance) {
                parent::append($item);
            }
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
            throw new Exception("value must be an array or implement Traversable");
        }

        foreach ($data as $row) {
            $this->append($row);
        }
    }

    public function exchangeArray($data): array
    {
        $old = $this->getArrayCopy();
        $this->clear();
        $this->populate($data);
        return $old;
    }

    public function clear(): void
    {
        parent::exchangeArray([]);
    }

    // Return an array of object. Only root object is change to array.
    public function getArray(): array
    {
        return $this->getIterator()->getArrayCopy();
    }

    /**
     * Return all root and nested object as an array
     */
    public function getArrayCopy(): array
    {
        $result = [];
        foreach ($this as $data) {
            if ($data instanceof ArraySerializableInterface) {
                $result[] = $data->getArrayCopy();
            } elseif (
                method_exists($data, "getArrayCopy")
                and is_callable([$data, "getArrayCopy"])
            ) {
                $result[] = $data->getArrayCopy();
            } else {
                $result[] = $data;
            }
        }
        return $result;
    }

    /**
     * Return filtered attribute 
     * If object does not implement getFilteredArrayCopy it will pass as normal array
     * Check AbstractModel getFilteredArrayCopy document
     */
    public function getFilteredArrayCopy(
        array $filters = [],
        bool $reverse = false
    ): array {
        $result = [];
        foreach ($this as $data) {
            if (
                method_exists($data, "getFilteredArrayCopy")
                and is_callable([$data, "getFilteredArrayCopy"])
            ) {
                $result[] = $data->getFilteredArrayCopy($filters, $reverse);
            } else if ($data instanceof ArraySerializableInterface) {
                $result[] = $data->getArrayCopy();
            } else {
                $result[] = $data;
            }
        }
        return $result;
    }

    // Shortcut, 1 level only
    public function getArrayColumn($column_key, $index_key = null): array
    {
        return array_column($this->getArray(), $column_key, $index_key);
    }

    public function toJson(int $flags = 0, int $depth = 512): string
    {
        $flags |= JSON_THROW_ON_ERROR;

        return json_encode($this->getArrayCopy(), $flags, $depth);
    }
}
