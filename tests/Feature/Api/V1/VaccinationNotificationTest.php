<?php

use App\Events\VaccinationRecorded;
use App\Models\Breed;
use App\Models\Farm;
use App\Models\Flock;
use App\Models\Notification;
use App\Models\NotificationOutbox;
use App\Models\Shed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Staff member jo production log submit karega
    $this->staff = User::factory()->create();
    Sanctum::actingAs($this->staff);

    // Farm owner alag hoga
    $this->owner = User::factory()->create();

    // Farm setup
    $this->farm = Farm::factory()->create(['owner_id' => $this->owner->id]);

    // Shed farm se linked
    $this->shed = Shed::factory()->create(['farm_id' => $this->farm->id]);

    // Breed (FlockFactory uses breed_id: 1)
    $this->breed = Breed::firstOrCreate(['id' => 1], ['name' => 'Test Breed']);

    // Flock shed se linked
    $this->flock = Flock::factory()->create([
        'shed_id'  => $this->shed->id,
        'breed_id' => $this->breed->id,
    ]);
});

// -------------------------------------------------------
// Test 1: Event fires when is_vaccinated = true
// -------------------------------------------------------
it('fires VaccinationRecorded event when is_vaccinated is true', function () {
    Event::fake([VaccinationRecorded::class]);

    $this->postJson('/api/v1/production', [
        'shed_id'              => $this->shed->id,
        'flock_id'             => $this->flock->id,
        'day_mortality_count'  => 2,
        'night_mortality_count'=> 1,
        'day_feed_consumed'    => 50000,
        'night_feed_consumed'  => 45000,
        'day_water_consumed'   => 80000,
        'night_water_consumed' => 75000,
        'is_vaccinated'        => true,
    ])->assertStatus(201);

    Event::assertDispatched(VaccinationRecorded::class);
});

// -------------------------------------------------------
// Test 2: Event does NOT fire when is_vaccinated = false
// -------------------------------------------------------
it('does NOT fire VaccinationRecorded event when is_vaccinated is false', function () {
    Event::fake([VaccinationRecorded::class]);

    $this->postJson('/api/v1/production', [
        'shed_id'              => $this->shed->id,
        'flock_id'             => $this->flock->id,
        'day_mortality_count'  => 0,
        'night_mortality_count'=> 0,
        'day_feed_consumed'    => 50000,
        'night_feed_consumed'  => 45000,
        'day_water_consumed'   => 80000,
        'night_water_consumed' => 75000,
        'is_vaccinated'        => false,
    ])->assertStatus(201);

    Event::assertNotDispatched(VaccinationRecorded::class);
});

// -------------------------------------------------------
// Test 3: In-app notification created for owner
// -------------------------------------------------------
it('creates in-app notification for farm owner on vaccination', function () {
    $this->postJson('/api/v1/production', [
        'shed_id'              => $this->shed->id,
        'flock_id'             => $this->flock->id,
        'day_mortality_count'  => 0,
        'night_mortality_count'=> 0,
        'day_feed_consumed'    => 50000,
        'night_feed_consumed'  => 45000,
        'day_water_consumed'   => 80000,
        'night_water_consumed' => 75000,
        'is_vaccinated'        => true,
    ])->assertStatus(201);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $this->owner->id,
        'farm_id' => $this->farm->id,
        'type'    => 'vaccination',
        'is_read' => false,
    ]);
});

// -------------------------------------------------------
// Test 4: Push notification queued for owner
// -------------------------------------------------------
it('queues push notification for farm owner on vaccination', function () {
    $this->postJson('/api/v1/production', [
        'shed_id'              => $this->shed->id,
        'flock_id'             => $this->flock->id,
        'day_mortality_count'  => 0,
        'night_mortality_count'=> 0,
        'day_feed_consumed'    => 50000,
        'night_feed_consumed'  => 45000,
        'day_water_consumed'   => 80000,
        'night_water_consumed' => 75000,
        'is_vaccinated'        => true,
    ])->assertStatus(201);

    $this->assertDatabaseHas('notification_outboxes', [
        'target_type' => 'user',
        'target_id'   => $this->owner->id,
        'status'      => 'pending',
    ]);
});

// -------------------------------------------------------
// Test 5: Manager bhi notification receive kare
// -------------------------------------------------------
it('creates notifications for farm managers on vaccination', function () {
    $manager = User::factory()->create();
    $this->farm->managers()->attach($manager->id, ['link_date' => now()]);

    $this->postJson('/api/v1/production', [
        'shed_id'              => $this->shed->id,
        'flock_id'             => $this->flock->id,
        'day_mortality_count'  => 0,
        'night_mortality_count'=> 0,
        'day_feed_consumed'    => 50000,
        'night_feed_consumed'  => 45000,
        'day_water_consumed'   => 80000,
        'night_water_consumed' => 75000,
        'is_vaccinated'        => true,
    ])->assertStatus(201);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $manager->id,
        'type'    => 'vaccination',
    ]);
});

// -------------------------------------------------------
// Test 6: Staff bhi notification receive kare
// -------------------------------------------------------
it('creates notifications for farm staff on vaccination', function () {
    $worker = User::factory()->create();
    $this->farm->staff()->attach($worker->id, ['link_date' => now()]);

    $this->postJson('/api/v1/production', [
        'shed_id'              => $this->shed->id,
        'flock_id'             => $this->flock->id,
        'day_mortality_count'  => 0,
        'night_mortality_count'=> 0,
        'day_feed_consumed'    => 50000,
        'night_feed_consumed'  => 45000,
        'day_water_consumed'   => 80000,
        'night_water_consumed' => 75000,
        'is_vaccinated'        => true,
    ])->assertStatus(201);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $worker->id,
        'type'    => 'vaccination',
    ]);
});

// -------------------------------------------------------
// Test 7: Notification ka title aur type correct ho
// -------------------------------------------------------
it('notification has correct type and title', function () {
    $this->postJson('/api/v1/production', [
        'shed_id'              => $this->shed->id,
        'flock_id'             => $this->flock->id,
        'day_mortality_count'  => 0,
        'night_mortality_count'=> 0,
        'day_feed_consumed'    => 50000,
        'night_feed_consumed'  => 45000,
        'day_water_consumed'   => 80000,
        'night_water_consumed' => 75000,
        'is_vaccinated'        => true,
    ])->assertStatus(201);

    $notification = Notification::where('user_id', $this->owner->id)
        ->where('type', 'vaccination')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe('vaccination')
        ->and($notification->title)->toContain('Vaccination Recorded')
        ->and($notification->title)->toContain($this->shed->name)
        ->and($notification->is_read)->toBeFalse();
});

// -------------------------------------------------------
// Test 8: No notification when vaccination = false
// -------------------------------------------------------
it('does not create vaccination notification when is_vaccinated is false', function () {
    $this->postJson('/api/v1/production', [
        'shed_id'              => $this->shed->id,
        'flock_id'             => $this->flock->id,
        'day_mortality_count'  => 0,
        'night_mortality_count'=> 0,
        'day_feed_consumed'    => 50000,
        'night_feed_consumed'  => 45000,
        'day_water_consumed'   => 80000,
        'night_water_consumed' => 75000,
        'is_vaccinated'        => false,
    ])->assertStatus(201);

    $this->assertDatabaseMissing('notifications', [
        'type' => 'vaccination',
    ]);
});
