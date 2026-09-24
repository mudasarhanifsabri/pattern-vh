<?php

namespace Tests\Feature;

use App\Http\Controllers\SoftwareUpdateController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SoftwareUpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_updater_falls_back_when_configured_composer_path_is_missing(): void
    {
        config(['erp.composer_binary' => '/missing/cpanel/composer']);

        $method = new ReflectionMethod(SoftwareUpdateController::class, 'composerCommand');
        $command = $method->invoke(app(SoftwareUpdateController::class));

        $this->assertIsArray($command);
        $this->assertNotSame('/missing/cpanel/composer', $command[0]);
        $this->assertFileExists($command[0]);
    }

    public function test_updater_skips_composer_instead_of_failing_when_it_is_unavailable(): void
    {
        $originalPath = getenv('PATH');
        putenv('PATH=');
        config(['erp.composer_binary' => '/missing/cpanel/composer']);

        try {
            $method = new ReflectionMethod(SoftwareUpdateController::class, 'composerCommand');
            $command = $method->invoke(app(SoftwareUpdateController::class));
        } finally {
            putenv('PATH='.($originalPath === false ? '' : $originalPath));
        }

        $this->assertSame('__skip__', $command[0]);
        $this->assertStringContainsString('/missing/cpanel/composer', $command[1]);
    }

    public function test_get_request_to_update_action_returns_to_update_page(): void
    {
        $permission = Permission::create(['name' => 'software-updates.manage']);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        $this->actingAs($user)
            ->get('/software-updates/run')
            ->assertRedirect(route('software-updates.index'));
    }
}
