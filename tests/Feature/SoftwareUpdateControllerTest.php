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
}
