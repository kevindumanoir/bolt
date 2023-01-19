<?php

namespace Bolt\Storage\Entity;

class ContentStatusStrategy implements ContentStatusStrategyInterface
{
    function allStatuses(): array
    {
        return ['published', 'timed', 'held', 'draft'];
    }

    function fallbackStatus(): string
    {
        return 'draft';
    }


}