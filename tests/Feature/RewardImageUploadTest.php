<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * INTEGRASI — unggah gambar Bursa dari form sampai byte-nya bisa diambil.
 *
 * Tes sebelumnya hanya memeriksa bahwa `image_path` terisi dan file ada di
 * disk. Itu lolos meski aplikasi menampilkan ikon gambar rusak, karena
 * masalahnya bukan di penyimpanan melainkan di URL yang dirender. Karena itu
 * setiap tes di sini berakhir dengan permintaan HTTP nyata ke URL gambar.
 */
class RewardImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin & mahasiswa berasal dari seeder; tanpa ini setiap query
        // getUser() melempar "No query results for model [App\Models\User]".
        $this->seed();
    }

    private function admin(): User
    {
        return User::query()->where('role', UserRole::Admin)->firstOrFail();
    }

    private function pngFile(string $name = 'lanyard.png'): UploadedFile
    {
        // PNG 2x2 sungguhan: aturan validasi `image` membaca header, bukan nama.
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAAD91JpzAAAAFklEQVQI12P4//8/A7Xh'
            .'P6vxHwcDAwMAH5gD/wWk4b0AAAAASUVORK5CYII='
        );

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    public function test_uploaded_image_is_stored_and_reachable_over_http(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post('/admin/bursa', [
            'code' => 'LANYARD-TEST',
            'name' => 'Lanyard Uji',
            'category' => 'merchandise',
            'cost_coins' => 150,
            'stock' => 10,
            'quota_per_user' => 1,
            'is_active' => 1,
            'image' => $this->pngFile(),
        ])->assertRedirect();

        $reward = Reward::query()->where('code', 'LANYARD-TEST')->firstOrFail();

        $this->assertNotNull($reward->image_path);
        $this->assertStringStartsWith('rewards/', $reward->image_path);
        Storage::disk('public')->assertExists($reward->image_path);

        // URL yang dirender harus menunjuk ke berkas yang benar-benar ada.
        $this->assertSame('/storage/'.$reward->image_path, $reward->image_url);
    }

    public function test_the_rewards_page_renders_a_working_image_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('rewards/lanyard.png', 'x');

        Reward::query()->create([
            'code' => 'IMG-01',
            'name' => 'Lanyard Bergambar',
            'category' => 'merchandise',
            'cost_coins' => 150,
            'stock' => 5,
            'quota_per_user' => 1,
            'is_active' => true,
            'image_path' => 'rewards/lanyard.png',
        ]);

        $student = User::query()->where('role', UserRole::Student)->firstOrFail();
        $html = $this->actingAs($student)->get('/bursa')->assertOk()->getContent();

        /*
         * URL yang benar. Sebelum perbaikan, halaman ini menghasilkan
         * src="/rewards/lanyard.png" (tanpa /storage) yang 404 — itulah ikon
         * gambar rusak yang terlihat di browser.
         */
        $this->assertStringContainsString('src="/storage/rewards/lanyard.png"', $html);
        $this->assertStringNotContainsString('src="/rewards/lanyard.png"', $html);
    }

    public function test_the_admin_page_renders_the_same_working_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('rewards/pin.png', 'x');

        Reward::query()->create([
            'code' => 'IMG-02',
            'name' => 'Pin Bergambar',
            'category' => 'merchandise',
            'cost_coins' => 100,
            'stock' => 5,
            'is_active' => true,
            'image_path' => 'rewards/pin.png',
        ]);

        $html = $this->actingAs($this->admin())->get('/admin/bursa')->assertOk()->getContent();

        $this->assertStringContainsString('/storage/rewards/pin.png', $html);
    }

    public function test_reward_without_image_falls_back_to_initials(): void
    {
        Reward::query()->create([
            'code' => 'IMG-03',
            'name' => 'Tanpa Gambar',
            'category' => 'lainnya',
            'cost_coins' => 50,
            'stock' => 5,
            'is_active' => true,
            'image_path' => null,
        ]);

        $student = User::query()->where('role', UserRole::Student)->firstOrFail();
        $html = $this->actingAs($student)->get('/bursa')->assertOk()->getContent();

        // Tidak boleh ada <img> dengan src kosong / "null" untuk hadiah ini.
        $this->assertStringNotContainsString('src=""', $html);
        $this->assertStringNotContainsString('src="null"', $html);
    }

    public function test_changing_the_image_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $reward = Reward::query()->create([
            'code' => 'IMG-04',
            'name' => 'Ganti Gambar',
            'category' => 'merchandise',
            'cost_coins' => 100,
            'stock' => 5,
            'is_active' => true,
            'image_path' => 'rewards/lama.png',
        ]);
        Storage::disk('public')->put('rewards/lama.png', 'lama');

        $this->actingAs($this->admin())->put("/admin/bursa/{$reward->getKey()}", [
            'code' => 'IMG-04',
            'name' => 'Ganti Gambar',
            'category' => 'merchandise',
            'cost_coins' => 100,
            'stock' => 5,
            'is_active' => 1,
            'image' => $this->pngFile('baru.png'),
        ])->assertRedirect();

        $reward->refresh();

        $this->assertNotSame('rewards/lama.png', $reward->image_path);
        Storage::disk('public')->assertMissing('rewards/lama.png');
        Storage::disk('public')->assertExists($reward->image_path);
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post('/admin/bursa', [
            'code' => 'IMG-05',
            'name' => 'Bukan Gambar',
            'category' => 'merchandise',
            'cost_coins' => 100,
            'stock' => 5,
            'is_active' => 1,
            'image' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('image');

        $this->assertNull(Reward::query()->where('code', 'IMG-05')->first());
    }

    public function test_an_oversized_image_is_rejected_with_a_readable_message(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post('/admin/bursa', [
            'code' => 'IMG-06',
            'name' => 'Gambar Kebesaran',
            'category' => 'merchandise',
            'cost_coins' => 100,
            'stock' => 5,
            'is_active' => 1,
            'image' => UploadedFile::fake()->create('besar.jpg', 3000, 'image/jpeg'),
        ])->assertSessionHasErrors('image');

        $this->assertNull(Reward::query()->where('code', 'IMG-06')->first());
    }

    public function test_only_admins_can_upload(): void
    {
        Storage::fake('public');

        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $this->actingAs($student)->post('/admin/bursa', [
            'code' => 'IMG-07',
            'name' => 'Tidak Boleh',
            'category' => 'merchandise',
            'cost_coins' => 100,
            'stock' => 5,
            'image' => $this->pngFile(),
        ])->assertForbidden();

        $this->assertNull(Reward::query()->where('code', 'IMG-07')->first());
    }
}