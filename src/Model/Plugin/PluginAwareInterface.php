<?php

declare(strict_types=1);

namespace Itseasy\Model\Plugin;

interface PluginAwareInterface
{
    // Return attached plugin
    public function getAttachedPlugin(): array;
}
