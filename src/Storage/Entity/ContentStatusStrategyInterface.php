<?php

namespace Bolt\Storage\Entity;

interface ContentStatusStrategyInterface
{
    function allStatuses(): array;
    function fallbackStatus(): string;
}