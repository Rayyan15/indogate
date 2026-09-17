<?php

/**
 * PRD M2: 3 locale — id (default), en, ar. Prefix always shown in the URL
 * (hideDefaultLocaleInURL = false), so "/" always redirects into one of
 * /id, /en, /ar.
 */
return [
    'supportedLocales' => [
        'id' => ['name' => 'Indonesian', 'script' => 'Latn', 'native' => 'Bahasa Indonesia', 'regional' => 'id_ID'],
        'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_US'],
        'ar' => ['name' => 'Arabic', 'script' => 'Arab', 'native' => 'العربية', 'regional' => 'ar_SA'],
    ],

    'useAcceptLanguageHeader' => false,

    'hideDefaultLocaleInURL' => false,

    'localesOrder' => ['id', 'en', 'ar'],

    'utf8suffix' => '; charset=UTF-8',

    'browserLanguagesOrder' => [],
];
