<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Tests\Feature;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Xcodiedev\QueueGuard\Detectors\EagerLoadedRelationDetector;
use Xcodiedev\QueueGuard\JobInspector;
use Xcodiedev\QueueGuard\Tests\TestCase;

final class EagerLoadedRelationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('authors', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create('books', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('author_id');
            $table->string('title');
        });

        GuardAuthor::create(['name' => 'Ann']);
        GuardBook::create(['author_id' => 1, 'title' => 'One']);
        GuardBook::create(['author_id' => 1, 'title' => 'Two']);
    }

    public function test_model_with_eager_loaded_relations_is_flagged(): void
    {
        $author = GuardAuthor::with('books')->first();

        $report = (new JobInspector([new EagerLoadedRelationDetector]))
            ->inspect(new JobWithModel($author));

        $codes = array_map(static fn ($f) => $f->code, $report->findings);
        $this->assertContains('eager_loaded_relations', $codes);
    }

    public function test_model_without_relations_is_not_flagged(): void
    {
        $author = GuardAuthor::first();

        $report = (new JobInspector([new EagerLoadedRelationDetector]))
            ->inspect(new JobWithModel($author));

        $this->assertSame([], $report->findings);
    }
}

class GuardAuthor extends Model
{
    protected $table = 'authors';

    public $timestamps = false;

    protected $guarded = [];

    public function books()
    {
        return $this->hasMany(GuardBook::class, 'author_id');
    }
}

class GuardBook extends Model
{
    protected $table = 'books';

    public $timestamps = false;

    protected $guarded = [];
}

final class JobWithModel implements ShouldQueue
{
    use Queueable;

    public function __construct(public Model $model) {}

    public function handle(): void {}
}
