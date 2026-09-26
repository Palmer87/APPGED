<?php

namespace Tests\Feature;

use App\Enums\FolderType;
use App\Models\Folder;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DocumentStructureSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_runs_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@ged-demo.test',
        ]);

        $admin = User::where('email', 'admin@ged-demo.test')->first();
        $this->assertNotNull($admin);

        $folders = Folder::all();
        $this->assertNotEmpty($folders);

        foreach ($folders as $folder) {
            $this->assertNotNull($folder->created_by, "Folder {$folder->name} has null created_by");
            $this->assertEquals($admin->id, $folder->created_by);
        }

        $this->assertDatabaseHas('folders', [
            'name' => 'Comptabilité & Finance',
            'folder_type' => FolderType::Department->value,
            'created_by' => $admin->id,
        ]);
    }

    public function test_document_structure_seeder_can_run_when_no_user_exists(): void
    {
        $this->seed(OrganizationSeeder::class);

        $this->assertEquals(0, User::count());

        $this->seed(DocumentStructureSeeder::class);

        $this->assertGreaterThan(0, User::count());

        $folders = Folder::all();
        $this->assertNotEmpty($folders);

        foreach ($folders as $folder) {
            $this->assertNotNull($folder->created_by, "Folder {$folder->name} has null created_by");
        }
    }
}
