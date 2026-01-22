<?php
namespace Tests\Feature;
use App\Models\ChildPhoto;
use App\Models\User;
use App\Models\LandlordUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use Database\Seeders\RoleSeeder;
class DashboardTest extends \Tests\TestCase
{
    // use RefreshDatabase;
    public function test_database_has_category_table()
    {
        $this->assertTrue(
            Schema::connection('landlord')->hasTable('categories')
        );

        $this->assertTrue(
            Schema::connection('landlord')->hasColumns('categories', [
                'id',
                'title_en',
                'title_ar',
                'created_at',
                'updated_at'
            ])
        );
    }

    public function test_database_has_video_table()
    {
        $this->assertTrue(
            Schema::connection('landlord')->hasTable('videos')
        );

        $this->assertTrue(
            Schema::connection('landlord')->hasColumns('videos', [
                'id',
                'title_en',
                'title_ar',
                'description_en',
                'description_ar',
                'video_url_en',
                'video_url_ar',
                'thumbnail_url_en',
                'thumbnail_url_ar',
                'category_id',
                'user_id',
                'created_at',
                'updated_at'
            ])
        );
    }


    public function test_database_has_category_relation_with_videos()
    {
        $this->assertTrue(
            Schema::connection('landlord')->hasTable('categories')
        );

        $this->assertTrue(
            Schema::connection('landlord')->hasColumns('categories', [
                'id',
                'title_en',
                'title_ar',
                'created_at',
                'updated_at'
            ])
        );
    }

    public function test_admin_accept_child_photo(): void
    {
        $child = User::factory()->create([
            'name' => 'cofa',
            'phone' => '+201012345671',
            'password' => 'CDCD12345##'
        ]);

        $admin = LandlordUser::create([
            'name' => 'admin',
            'phone' => '+201012345672',
            'password' => bcrypt('CDCD12345##')
        ]);

        $image = ChildPhoto::create([
            'image_url' => 'photo.png',
            'child_id' => $child->id,
            'description' => "hello world"
        ]);

        $sut = $this->putJson('/api/v1/accept-child-photo/' . $image->id, [], [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . auth('admin')->attempt(['phone' => $admin->phone, 'password' => 'CDCD12345##']),
            'X-Tenant' => 'test.localhost',
            'x-api-key' => 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A',
        ]);

        $sut->assertStatus(\Illuminate\Http\JsonResponse::HTTP_OK);
        $sut->assertJsonStructure([
            'message'
        ]);

        // Re-establish tenant context because middleware clears it
        Tenant::where('domain', 'test.localhost')->first()->makeCurrent();

        $this->assertDatabaseHas('child_photos', [
            'id' => $image->id,
            'description' => $image->description,
            'child_id' => $child->id,
            'isAccepted' => true
        ], 'tenant');
    }

    public function test_show_child_photo(): void
    {
        $child = User::factory()->create([
            'name' => 'cofa',
            'phone' => '+201012345671',
            'password' => 'CDCD12345##'
        ]);

        $admin = LandlordUser::create([
            'name' => 'admin',
            'phone' => '+201012345672',
            'password' => bcrypt('CDCD12345##')
        ]);

        $image = ChildPhoto::create([
            'image_url' => 'photo.png',
            'child_id' => $child->id,
            'description' => 'hello world'
        ]);

        $sut = $this->getJson('/api/v1/child-photo/' . $image->id, [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . auth('admin')->attempt(['phone' => $admin->phone, 'password' => 'CDCD12345##']),
            'X-Tenant' => 'test.localhost',
            'x-api-key' => 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A',
        ]);


        $sut->assertStatus(JsonResponse::HTTP_OK);
        $sut->assertJsonStructure([
            'id',
            'image_url',
            'child_name',
            'isAccepted',
            'description'
        ]);

        $sut->assertJson([
            'id' => $image->id,
            'image_url' => $image->image_url,
            'child_name' => $child->name,
            'description' => $image->description
        ]);
    }

}
