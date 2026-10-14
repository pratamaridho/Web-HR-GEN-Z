<?php

namespace Tests\Unit\Shared;

use App\Shared\Enums\RoleUser;
use PHPUnit\Framework\TestCase;

/**
 * Aturan murni tanpa HTTP dan database.
 */
class RoleUserTest extends TestCase
{
    public function test_beranda_per_peran(): void
    {
        $this->assertSame('dashboard', RoleUser::Admin->homeRoute());
        $this->assertSame('dashboard', RoleUser::Management->homeRoute());
        $this->assertSame('jadwal-saya', RoleUser::Trainer->homeRoute());
    }

    public function test_label_peran(): void
    {
        $this->assertSame('Admin', RoleUser::Admin->label());
        $this->assertSame('Admin Operasional', RoleUser::Admin->labelPanjang());
        $this->assertSame('Management', RoleUser::Management->label());
    }

    public function test_nilai_peran_tak_dikenal_menjadi_null(): void
    {
        $this->assertNull(RoleUser::tryFrom('superadmin'));
        $this->assertSame(RoleUser::Trainer, RoleUser::tryFrom('trainer'));
    }
}
