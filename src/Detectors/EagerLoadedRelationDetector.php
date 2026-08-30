<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Detectors;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Xcodiedev\QueueGuard\Finding;
use Xcodiedev\QueueGuard\Severity;
use Xcodiedev\QueueGuard\Support\PropertyWalker;

/**
 * Flags Eloquent models carried with eager-loaded relations, and whole Eloquent
 * collections. With SerializesModels these are all written to the payload; a key
 * (or list of keys) plus a reload inside handle() is almost always smaller and
 * fresher.
 */
final class EagerLoadedRelationDetector implements Detector
{
    public function __construct(
        private readonly PropertyWalker $walker = new PropertyWalker,
        private readonly int $relationThreshold = 1,
    ) {}

    public function inspect(object $job, array $context): array
    {
        if (! class_exists(Model::class)) {
            return [];
        }

        $findings = [];

        $this->walker->walk($job, function (string $path, mixed $value) use (&$findings): void {
            if ($path === '') {
                return;
            }

            if ($value instanceof Model) {
                $relations = array_keys($value->getRelations());
                if (count($relations) >= $this->relationThreshold) {
                    $findings[] = new Finding(
                        Severity::Warning,
                        'eager_loaded_relations',
                        sprintf(
                            '%s is carried with %d eager-loaded relation(s): %s.',
                            class_basename($value),
                            count($relations),
                            implode(', ', $relations),
                        ),
                        $path,
                        'Pass $model->getKey() and call $model->fresh() / load() inside handle().',
                    );
                }

                return;
            }

            if ($value instanceof EloquentCollection && $value->isNotEmpty()) {
                $findings[] = new Finding(
                    Severity::Warning,
                    'eager_loaded_collection',
                    sprintf('An Eloquent collection of %d model(s) is serialized in full.', $value->count()),
                    $path,
                    'Pass $collection->modelKeys() and reload inside handle().',
                );
            }
        });

        return $findings;
    }
}
