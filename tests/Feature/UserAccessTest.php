<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function pengguna_nonaktif_yang_sedang_login_terlogout_di_request_berikutnya()
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        // Request pertama, masih aktif
        $response = $this->get('/akun');
        $response->assertOk();

        // Admin (atau via db) menonaktifkan user
        $user->update(['is_active' => false]);

        // Request berikutnya harusnya redirect ke login (logout)
        $response = $this->get('/akun');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /** @test */
    public function customer_yang_membuka_owner_dashboard_dapat_404()
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $response = $this->actingAs($customer)->get('/owner/dashboard');
        $response->assertStatus(404);
    }

    /** @test */
    public function owner_bisa_membuka_owner_dashboard_dan_pengguna_200()
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);

        $response = $this->actingAs($owner)->get('/owner/dashboard');
        $response->assertOk();

        $response = $this->actingAs($owner)->get('/owner/pengguna');
        $response->assertOk();
    }

    /** @test */
    public function toggle_untuk_owner_ditolak()
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $otherOwner = User::factory()->create(['role' => User::ROLE_OWNER]);

        $response = $this->actingAs($owner)->patch('/owner/pengguna/' . $otherOwner->id . '/toggle-status');
        $response->assertRedirect();
        $response->assertSessionHasErrors('user');
    }
}
