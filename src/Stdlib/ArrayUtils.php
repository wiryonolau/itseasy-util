<?php

declare(strict_types=1);

namespace Itseasy\Stdlib;

use ArrayAccess;
use Exception;
use Itseasy\Model\AbstractModel;

class ArrayUtils
{
    /**
     * Query array / object that implement ArrayAccess using dot notation
     * accessing array index require to be enclosed by square bracket 
     * 
     * Example :
     * $data = [
     *   "companies" => [
     *     "name" => "My Company",
     *     "employee" => [
     *       [
     *         "name" => "Tom"
     *       ],
     *       [
     *         "name" => "Jerry"
     *       ]
     *     ]
     *   ]
     * ]
     * 
     * ArrayUtils::query($data, "companies.[0].name") will return "Tom"
     * 
     * @param array|ArrayAccess $object object to query by path
     * @param string|array $paths query path
     * @param mixed $placeholder return value if not found
     * @param bool $strict throw Exception if not found
     * @param string $separator path separator
     */
    public static function query(
        $object,
        $paths,
        $placeholder = null,
        bool $strict = false,
        string $separator = "."
    ) {
        if (is_string($paths)) {
            $paths = explode($separator, $paths);
        }

        if (!is_array($paths)) {
            return $placeholder;
        }

        $path = array_shift($paths);

        $index = null;
        preg_match("/\[(\d+)\]/", $path, $matches);
        if (count($matches) > 1) {
            $index = $matches[1];
        }

        if (is_null($index)) {
            if (is_array($object) and isset($object[$path])) {
                $value = $object[$path];
            } else if ($object instanceof AbstractModel or is_object($object)) {
                $value = $object->{$path};
            } else if ($strict) {
                throw new Exception("Unrecognized Query Path");
            } else {
                return $placeholder;
            }
        } else {
            if (is_array($object) and isset($object[$index])) {
                $value = $object[$index];
            } else if ($object instanceof ArrayAccess) {
                $value = $object->offsetGet($index);
            } else if ($strict) {
                throw new Exception("Unrecognized Query Path");
            } else {
                return $placeholder;
            }
        }

        if (count($paths)) {
            $value = self::query(
                $value,
                $paths,
                $placeholder,
                $strict,
                $separator
            );
        }

        return $value;
    }
}
