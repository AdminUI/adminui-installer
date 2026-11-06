<?php

use AdminUI\AdminUI\Models\Admin;
use AdminUI\AdminUIInstaller\Actions\CreateFirstAdminAction;
use AdminUI\AdminUIInstaller\Facades\Install;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

require_once __DIR__.'/../Stubs/Admin.php';

const REGISTER_URL = '/install-adminui/register';

function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'company' => 'Analytical Engines',
        'password' => 'Str0ngPassw0rd',
        'password_confirmation' => 'Str0ngPassw0rd',
    ], $overrides);
}

beforeEach(function () {
    Schema::create('admins', function ($table) {
        $table->id();
        $table->string('email')->nullable();
    });

    // The real action needs AdminUI's models, roles and guards. Record the call and create the admin row instead.
    $this->created = [];
    $this->app->instance(CreateFirstAdminAction::class, new class($this->created) extends CreateFirstAdminAction
    {
        public function __construct(private array &$created) {}

        public function execute(array $data)
        {
            $this->created[] = $data;
            Admin::create(['email' => $data['email']]);
        }
    });

    // Not hitting haveibeenpwned.com from tests
    Http::fake();
});

function fakeInstallState(bool $installed, bool $migrated = true): void
{
    Install::shouldReceive('isInstalled')->andReturn($installed);
    Install::shouldReceive('isMigrated')->andReturn($migrated);
}

it('creates the first admin when installed and none exist', function () {
    fakeInstallState(installed: true);

    $this->postJson(REGISTER_URL, registrationPayload())->assertOk();

    expect($this->created)->toHaveCount(1)
        ->and(Admin::count())->toBe(1);
});

it('refuses to register when an admin already exists', function () {
    fakeInstallState(installed: true);
    Admin::create(['email' => 'existing@example.com']);

    $this->postJson(REGISTER_URL, registrationPayload())
        ->assertForbidden()
        ->assertJson(['message' => 'An admin account has already been registered.']);

    expect($this->created)->toBeEmpty()
        ->and(Admin::count())->toBe(1);
});

it('refuses to register when AdminUI is not installed', function () {
    fakeInstallState(installed: false);

    $this->postJson(REGISTER_URL, registrationPayload())->assertForbidden();

    expect($this->created)->toBeEmpty()
        ->and(Admin::count())->toBe(0);
});

it('refuses to register when the database has not been migrated', function () {
    fakeInstallState(installed: true, migrated: false);

    $this->postJson(REGISTER_URL, registrationPayload())->assertForbidden();

    expect($this->created)->toBeEmpty();
});

it('only lets the first of two registrations through', function () {
    fakeInstallState(installed: true);

    $this->postJson(REGISTER_URL, registrationPayload())->assertOk();
    $this->postJson(REGISTER_URL, registrationPayload(['email' => 'second@example.com']))->assertForbidden();

    expect($this->created)->toHaveCount(1)
        ->and(Admin::count())->toBe(1);
});

it('rejects a blocked request before validating it', function () {
    fakeInstallState(installed: true);
    Admin::create(['email' => 'existing@example.com']);

    // An invalid payload still gets 403, not 422, and never reaches the password breach check
    $this->postJson(REGISTER_URL, [])->assertForbidden();

    Http::assertNothingSent();
});

it('validates the payload when registration is open', function () {
    fakeInstallState(installed: true);

    $this->postJson(REGISTER_URL, registrationPayload(['email' => 'not-an-email']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect($this->created)->toBeEmpty();
});
