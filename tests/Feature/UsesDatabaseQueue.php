<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configures the "database" queue driver on an in-memory sqlite connection.
 *
 * The sync driver is deliberately not used: it bypasses Laravel's
 * JobQueueing event, which is the hook the automatic guard listens on.
 */
trait UsesDatabaseQueue
{
    protected function applyDatabaseQueueConfig($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('queue.default', 'database');
        $app['config']->set('queue.connections.database', [
            'driver' => 'database',
            'connection' => 'testing',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ]);
    }

    protected function createJobsTable(): void
    {
        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }
}
