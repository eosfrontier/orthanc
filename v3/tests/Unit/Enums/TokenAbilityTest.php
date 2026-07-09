<?php

namespace Tests\Unit\Enums;

use App\Enums\TokenAbility;
use PHPUnit\Framework\TestCase;

class TokenAbilityTest extends TestCase
{
    public function test_read_case_has_correct_value(): void
    {
        $this->assertSame('storage:read', TokenAbility::Read->value);
    }

    public function test_write_case_has_correct_value(): void
    {
        $this->assertSame('storage:write', TokenAbility::Write->value);
    }

    public function test_admin_case_has_correct_value(): void
    {
        $this->assertSame('storage:admin', TokenAbility::Admin->value);
    }

    public function test_values_returns_flat_array(): void
    {
        $this->assertSame(
            ['storage:read', 'storage:write', 'storage:admin'],
            TokenAbility::values()
        );
    }

    public function test_from_rejects_invalid_ability(): void
    {
        $this->expectException(\ValueError::class);
        TokenAbility::from('invalid:ability');
    }
}
