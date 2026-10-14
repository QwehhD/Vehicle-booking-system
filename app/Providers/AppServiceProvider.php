<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Region;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ActivityLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureModels();
        $this->configureActivityLogging();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure Eloquent: morph map for activity log subjects, lazy loading
     * guard so N+1 queries surface during development, and unwrapped API
     * resources for Inertia props.
     */
    protected function configureModels(): void
    {
        Relation::enforceMorphMap([
            'activity_log' => ActivityLog::class,
            'booking' => Booking::class,
            'booking_approval' => BookingApproval::class,
            'driver' => Driver::class,
            'location' => Location::class,
            'region' => Region::class,
            'user' => User::class,
            'vehicle' => Vehicle::class,
        ]);

        Model::preventLazyLoading(! app()->isProduction());

        JsonResource::withoutWrapping();
    }

    /**
     * Record authentication events to the activity log.
     */
    protected function configureActivityLogging(): void
    {
        Event::listen(function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            app(ActivityLogger::class)->log(
                'auth.login',
                $event->user,
                "{$event->user->name} masuk ke aplikasi",
            );
        });

        Event::listen(function (Logout $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            app(ActivityLogger::class)->log(
                'auth.logout',
                $event->user,
                "{$event->user->name} keluar dari aplikasi",
            );
        });

        Event::listen(function (Failed $event): void {
            $email = (string) ($event->credentials['email'] ?? '');

            app(ActivityLogger::class)->log(
                'auth.failed',
                $event->user instanceof User ? $event->user : null,
                "Percobaan masuk gagal untuk {$email}",
                ['email' => $email],
            );
        });
    }
}
