<?php
declare(strict_types=1);

/**
 * @throws \JsonException
 * @throws \RuntimeException
 */
(static function () {
    $cache = [];
    $mods = \json_decode(file_get_contents(__DIR__ . '/src/mods.json'), false, 512, JSON_THROW_ON_ERROR);
    $types = [
        1 => 'Stable',
        2 => 'Beta',
        3 => 'Alpha',
    ];

    foreach ($mods as $modId => $modInfo) {
        $modId = +$modId;
        $lastFileId = max((array) $modInfo->files);
        sleep(1);
        $raw = file_get_contents("https://addons-ecs.forgesvc.net/api/v2/addon/{$modId}/files");
        if (!$raw) {
            echo 'ERROR fetching ', $modInfo->name, ' check ', $modInfo->url, PHP_EOL;
            continue;
        }
        try {
            $json = \json_decode($raw, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            echo 'ERROR parsing ', $modInfo->name, ' check ', $modInfo->url, PHP_EOL;
            echo $e->getMessage(), PHP_EOL;
            echo $raw;
            continue;
        }
        $cache[$modId] = $json;
        $newestFileId = max(array_column((array) $json, "id"));
        if ($newestFileId <= $lastFileId) {
            continue;
        }
        echo 'Updated ', $modInfo->name, ' check ', $modInfo->url, PHP_EOL;
        $updates = [];
        foreach ($json as $fileMeta) {
            $fileId = +$fileMeta->id;
            if($fileId <= $lastFileId) {
                continue;
            }
            $date = substr($fileMeta->fileDate, 0, 10);
            $gameVersions = implode(', ', $fileMeta->gameVersion);
            $type = $types[$fileMeta->releaseType] ?? $fileMeta->releaseType;
            $symbol = in_array('Forge', $fileMeta->gameVersion) ? '+' : (in_array('Fabric', $fileMeta->gameVersion) ? '-' : '*');
            $updates[$fileId] = " {$symbol} {$fileId} {$type} @ {$date}: {$fileMeta->displayName} for {$gameVersions}";
        }
        krsort($updates);
        foreach ($updates as $fielId => $udpate) {
            echo $udpate, PHP_EOL;
        }
        echo PHP_EOL;
    }
    file_put_contents(__DIR__ . '/build/cache.json', json_encode($cache, 64*7));
})();
