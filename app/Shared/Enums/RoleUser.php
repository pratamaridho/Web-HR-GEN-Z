<?php

namespace App\Shared\Enums;

enum RoleUser: string
{
    case Admin = 'admin';
    case Trainer = 'trainer';
    case Management = 'management';

    /**
     * Nama peran untuk pesan akses ditolak.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Trainer => 'Trainer',
            self::Management => 'Management',
        };
    }

    /**
     * Nama peran untuk identitas pengguna di sidebar.
     */
    public function labelPanjang(): string
    {
        return match ($this) {
            self::Admin => 'Admin Operasional',
            self::Trainer => 'Trainer',
            self::Management => 'Management',
        };
    }

    /**
     * Nama rute beranda setelah login: Dashboard untuk Admin dan
     * Management, Jadwal saya untuk Trainer.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin, self::Management => 'dashboard',
            self::Trainer => 'jadwal-saya',
        };
    }
}
