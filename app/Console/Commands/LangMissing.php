<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * PRD M2 step 11: "Perintah artisan pendeteksi kunci terjemahan kosong."
 * Walks lang/{locale}/*.php, flattens each file's keys, and reports any
 * key present in one locale but missing from another.
 */
class LangMissing extends Command
{
    protected $signature = 'lang:missing';

    protected $description = 'Report translation keys missing across the id/en/ar lang files';

    public function handle(): int
    {
        $langPath = lang_path();
        $locales = array_keys(config('laravellocalization.supportedLocales'));

        $keysByLocale = [];

        foreach ($locales as $locale) {
            $keysByLocale[$locale] = $this->collectKeys($langPath.'/'.$locale);
        }

        $allKeys = collect($keysByLocale)->flatten()->unique()->values();

        $missingFound = false;

        foreach ($allKeys as $key) {
            $missingFrom = collect($locales)->filter(fn ($locale) => ! in_array($key, $keysByLocale[$locale], true));

            if ($missingFrom->isNotEmpty()) {
                $missingFound = true;
                $this->error("{$key} missing from: ".$missingFrom->implode(', '));
            }
        }

        if (! $missingFound) {
            $this->info('No missing translation keys across '.implode(', ', $locales).'.');
        }

        return $missingFound ? self::FAILURE : self::SUCCESS;
    }

    private function collectKeys(string $localeDir): array
    {
        if (! File::isDirectory($localeDir)) {
            return [];
        }

        $keys = [];

        foreach (File::files($localeDir) as $file) {
            $domain = $file->getFilenameWithoutExtension();
            $translations = require $file->getPathname();

            foreach (array_keys($translations) as $key) {
                $keys[] = "{$domain}.{$key}";
            }
        }

        return $keys;
    }
}
