<?php

declare(strict_types=1);

namespace Eightfold\Package\Tests;

use PHPUnit\Framework\TestCase;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

final class BaseTest extends TestCase
{
    #[Test]
    public function test_to_pass_checks_with_failed_test(): void
    {
        parent::assertTrue(false);
    }
}
