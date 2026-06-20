<?php

namespace App\Support\Testing\Fakes;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter as BaseInMemoryFilesystemAdapter;
use League\Flysystem\UnableToRetrieveMetadata;
use Throwable;

class InMemoryFilesystemAdapter extends BaseInMemoryFilesystemAdapter
{
    public function lastModified(string $path): FileAttributes
    {
        try {
            return parent::lastModified($path);
        } catch (UnableToRetrieveMetadata $exception) {
            $contents = iterator_to_array($this->listContents($path, true));

            if (empty($contents)) {
                throw $exception;
            }

            $timestamps = [];
            foreach ($contents as $file) {
                if ($file instanceof FileAttributes) {
                    $timestamps[] = $file->lastModified();
                }
            }

            $timestamps = array_filter(
                $timestamps,
                fn (?int $time): bool => ! is_null($time)
            );

            return new FileAttributes(
                $path,
                null,
                null,
                ! empty($timestamps) ? max($timestamps) : time()
            );
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        foreach (parent::listContents($path, $deep) as $attributes) {
            if ($attributes->isDir()) {
                try {
                    $lastModified = $this->lastModified($attributes->path())->lastModified();
                } catch (Throwable $e) {
                    $lastModified = null;
                }

                yield new DirectoryAttributes(
                    $attributes->path(),
                    $attributes->visibility(),
                    $lastModified,
                    $attributes->extraMetadata()
                );
            } else {
                yield $attributes;
            }
        }
    }
}
