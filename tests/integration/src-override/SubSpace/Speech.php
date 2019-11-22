<?php

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\SubSpace;

class Speech
{
    public function whisper(int $amount): string
    {
        return \str_repeat('_', $amount);
    }
}
