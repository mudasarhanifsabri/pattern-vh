<?php

namespace Tests\Feature;

use App\Http\Controllers\SoftwareUpdateController;
use ReflectionMethod;
use Tests\TestCase;

class SoftwareUpdateControllerTest extends TestCase
{
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
}
