<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regresi: jarak vertikal antar item menu sidebar harus SAMA di mobile & desktop = 16px.
 *
 * Sebelumnya hanya blok `@media (max-width: 767.98px)` yang mengatur
 * `.app-sidebar-drawer .nav-link { margin-bottom: 12px }`, sedangkan desktop tidak
 * punya aturan sama sekali (margin 0 dari inline style) sehingga ritme menunya beda.
 * Sekarang jarak ditentukan SATU aturan global (di luar media query) sebesar 16px,
 * dipakai oleh drawer mobile maupun sidebar statis desktop.
 */
class SidebarMenuSpacingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Sidebar',
            'email' => 'admin.sidebar@presensi.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);
    }

    public function test_jarak_menu_sidebar_16px_berlaku_untuk_mobile_dan_desktop(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.students.trash'));

        $response->assertOk();

        $html = $response->getContent();

        // 1. Aturan global 16px untuk item menu sidebar ada di layout
        $this->assertStringContainsString('.app-sidebar-drawer .nav-link {', $html);
        $this->assertStringContainsString('margin-bottom: 16px !important;', $html);

        // 2. Override lama khusus mobile (12px) sudah tidak ada lagi
        $this->assertStringNotContainsString('margin-bottom: 12px !important;', $html);
    }

    public function test_aturan_16px_berada_di_luar_media_query_mobile(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.students.trash'))->getContent();

        $posisiMediaQuery = strpos($html, '@media (max-width: 767.98px)');
        $posisiAturan16px = strpos($html, 'margin-bottom: 16px !important;');

        $this->assertNotFalse($posisiAturan16px, 'Aturan 16px tidak ditemukan di layout.');

        // Diletakkan setelah blok media query mobile supaya menang cascade
        // (mengalahkan `style="margin: 0 0.75rem"` pada markup sidebar).
        $this->assertGreaterThan(
            $posisiMediaQuery,
            $posisiAturan16px,
            'Aturan 16px harus berada setelah media query mobile agar berlaku di mobile & desktop.'
        );
    }
}
