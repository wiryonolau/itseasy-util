<?php

declare(strict_types=1);

namespace Itseasy\Model;

use Exception;
use Laminas\Stdlib\ArraySerializableInterface;
use ReflectionClass;
use Itseasy\Model\Plugin\PluginAwareTrait;
use Itseasy\Model\Plugin\PluginAwareInterface;
use Itseasy\Model\Plugin\JsonToObjectPlugin;
use Itseasy\Model\Plugin\ObjectToJsonPlugin;
use Itseasy\Model\Plugin\DateToObjectPlugin;
use Itseasy\Model\Plugin\FormatDatePlugin;
use Itseasy\Model\Plugin\PathQueryPlugin;

abstract class AbstractModel implements ArraySerializableInterface, PluginAwareInterface
{
    use PluginAwareTrait;

    // Model Properties
    private $_modelProperties = [];

    public function getAttachedPlugin(): array
    {
        return [
            new JsonToObjectPlugin(),
            new ObjectToJsonPlugin(),
            new DateToObjectPlugin(),
            new FormatDatePlugin(),
            new PathQueryPlugin()
        ];
    }

    public function query(string $paths, $default = null, $separator = ".")
    {
        try {
            $paths = explode($separator, $paths);
            return $this->pathQuery($paths, $this);
        } catch (Exception $e) {
            return $default;
        }
    }

    public function __get(string $name)
    {
        $method = $this->getPropertyClassMethod("get", $name);
        if (is_null($method)) {
            return $this->{$name};
        }
        return $this->{$method}();
    }

    public function __set(string $name, $value): void
    {
        if (empty($this->getModelProperties()[$name])) {
            throw new Exception("\$$name is not valid property in " . get_class($this));
        }

        $this->populate([
            $name => $value
        ]);
    }

    public function __isset(string $name): bool
    {
        return isset($this->_modelProperties[$name]);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    public function populate(?array $data = []): void
    {
        if (is_null($data)) {
            return;
        }

        foreach ($data as $k => $v) {
            if (empty($this->getModelProperties()[$k])) {
                continue;
            }

            $method = $this->getPropertyClassMethod("set", $k);
            if (!is_null($method)) {
                $this->{$method}($v);
            } elseif ($this->{$k} === $v) {
                $this->{$k} = $v;
            } elseif ($this->isCallable($this->{$k}, "populate")) {
                $this->{$k}->populate($v);
            } elseif ($this->{$k} instanceof ArraySerializableInterface) {
                $this->{$k}->exchangeArray($v);
            } elseif (property_exists($this, $k)) {
                $this->{$k} = $v;
            }
        }
    }

    public function exchangeArray(array $data): array
    {
        $old = $this->getArrayCopy();
        $this->populate($data);
        return $old;
    }

    /**
     * Convert model public / protected attributes to array recursively 
     * will also apply each attribute filter if exist
     *
     * function cannot have arguments since it require to compatible with PHP ArrayObject / ArrayIterator
     */
    public function getArrayCopy(): array
    {
        $result = [];
        foreach ($this->getModelProperties() as $property => $scope) {
            $method = $this->getPropertyClassMethod("get", $property);
            if (!is_null($method)) {
                $result[$property] = $this->{$method}();
            } elseif ($this->isCallable($this->{$property}, "getArrayCopy")) {
                $result[$property] = $this->{$property}->getArrayCopy();
            } else {
                $result[$property] = $this->{$property};
            }
        }
        return $result;
    }

    /**
     * Convert model public / protected attributes to array that compatible with database column
     * Some column might be require to be encode as json, datetime to string etc
     * 
     * Override this function, by default call getArrayCopy
     */
    public function getArrayForDb(): array
    {
        return $this->getArrayCopy();
    }


    /**
     * Return include / exclude attribute only
     * Only filter by attribute name not attribute value
     * Filter can be nested array
     *  e.g ["id" , "data" => ["name"]]
     * 
     * @param array $filters return only selected public/private attribute
     * @param bool $reverse exclude if true else include
     * 
     * @return array
     */
    public function getFilteredArrayCopy(
        array $filters = [],
        bool $reverse = false
    ): array {
        // No filters, return as is
        if (!count($filters)) {
            return $this->getArrayCopy();
        }

        // Flatten array, use key if nested array else value
        $currentFilters = [];
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $currentFilters[] = $key;
            } else {
                $currentFilters[] = $value;
            }
        }

        $result = [];
        foreach ($this->getModelProperties() as $property => $scope) {
            // Exclude any attribute from filters
            if ($reverse and in_array($property, $currentFilters)) {
                continue;
            }

            // Include any attribute from filters
            if (!$reverse and !in_array($property, $currentFilters)) {
                continue;
            }

            $method = $this->getPropertyClassMethod("get", $property);
            if (!is_null($method)) {
                $result[$property] = $this->{$method}();
            } elseif ($this->isCallable($this->{$property}, "getFilteredArrayCopy")) {
                $result[$property] = $this->{$property}->getFilteredArrayCopy(
                    $filters[$property] ?? [],
                    $reverse
                );
            } else {
                $result[$property] = $this->{$property};
            }
        }
        return $result;
    }

    public function toJson(int $flags = 0, int $depth = 512): string
    {
        $flags |= JSON_THROW_ON_ERROR;

        return json_encode($this->getArrayCopy(), $flags, $depth);
    }

    public function isCallable($object, ?string $function): bool
    {
        if (!is_object($object) or is_null($function)) {
            return false;
        }

        return (method_exists($object, $function) and is_callable([$object, $function]));
    }

    /**
     * Retrieve and cache all child Model property
     * Only non static public and non static protected count as model attribute
     */
    private function getModelProperties(): array
    {
        if (!count($this->_modelProperties)) {
            $reflection = new ReflectionClass($this);

            foreach ($reflection->getProperties() as $property) {
                if (!($property->isPublic() or $property->isProtected())) {
                    continue;
                }

                if ($property->isStatic()) {
                    continue;
                }

                $this->_modelProperties[$property->name] = ($property->isPublic() ? "public" : "protected");
            }
        }
        return $this->_modelProperties;
    }

    private function getPropertyClassMethod(string $type, string $property, bool $throw_error = true): ?string
    {
        $method = sprintf("%s%s", $type, implode('', array_map('ucfirst', explode('_', $property))));
        if ($this->isCallable($this, $method)) {
            return $method;
        }
        return null;
    }
}
