<?php declare(strict_types=1);

namespace WebPageTest;

class ValidatorPatterns {
    static string $symphony = '^([^<>&]|&([^#]|$))+$';
}
