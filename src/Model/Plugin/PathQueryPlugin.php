<?php

declare(strict_types=1);

namespace Itseasy\Model\Plugin;

use ArrayAccess;
use Exception;
use Itseasy\Model\AbstractModel;

class PathQueryPlugin implements PluginInterface
{
    public static $name = "pathQuery";

    public function getName(): string
    {
        return self::$name;
    }

    public function __invoke(
        array $paths,
        $object
    ) {
        return $this->traversePath($paths, $object);
    }

    private function traversePath(
        array $paths,
        $object
    ) {
        $path = array_shift($paths);

        $index = null;
        preg_match("/\[(\d+)\]/", $path, $matches);
        if (count($matches) > 1) {
            $index = $matches[1];
        }

        if (is_null($index)) {
            if (is_array($object)) {
                $value = $object[$path];
            } else if ($object instanceof AbstractModel or is_object($object)) {
                $value = $object->{$path};
            } else {
                throw new Exception("Unrecognized Query Path");
            }
        } else {
            if (is_array($object)) {
                $value = $object[$index];
            } else if ($object instanceof ArrayAccess) {
                $value = $object->offsetGet($index);
            } else {
                throw new Exception("Unrecognized Query Path");
            }
        }

        if (count($paths)) {
            $value = $this->traversePath($paths, $value);
        }

        return $value;
    }
}
