<?php

declare(strict_types=1);

namespace Itseasy\Model\Plugin;

use Itseasy\Stdlib\ArrayUtils;

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
        return ArrayUtils::query($object, $paths, null, false);
    }
}
