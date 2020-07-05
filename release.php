<?php
declare(strict_types=1);

/**
 * @throws \JsonException
 * @throws \RuntimeException
 */
(static function () {
    $modVersion = '1.3.0';
    $mcVersions = [
        '1.12' => ['1.12.2', '14.23.5.2854'],
        '1.14' => ['1.14.4', '28.2.0'],
        '1.15' => ['1.15.2', '31.2.0'],
        '1.16' => ['1.16.1', '32.0.47'],
    ];
    $jsonOptions = JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    $mods = \json_decode(file_get_contents(__DIR__ . '/src/mods.json'), false, 512, JSON_THROW_ON_ERROR);
    $template = \json_decode(file_get_contents(__DIR__ . '/src/manifest.json'), false, 512, JSON_THROW_ON_ERROR);

    foreach ($mcVersions as $mcVersion => [$mcVersionPatch, $forgeVersion]) {
        $manifest = $template;
        $manifest->minecraft->version = $mcVersionPatch;
        $manifest->minecraft->modLoaders[0]->id = 'forge-' . $forgeVersion;
        $manifest->version = $modVersion;
        $manifest->files = [];

        foreach ($mods as $modId => $modInfo) {
            $fileId = $modInfo->files->$mcVersion ?? 0;
            if ($fileId) {
                $manifest->files[] = [
                    "projectID" => +$modId,
                    "fileID" => +$fileId,
                    "required" => true,
                ];
            }
        }

        $dir = __DIR__ . '/build/' . $mcVersion;
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException("Directory '{$dir}' was not created");
        }
        file_put_contents($dir . '/manifest.json', json_encode($manifest, $jsonOptions) . PHP_EOL);
        $zipFile = __DIR__ . "/releases/SundragonMods-{$mcVersion}-{$modVersion}.zip";
        $bashCommands = [
            'cd ' . escapeshellarg($dir),
            'zip ' . escapeshellarg($zipFile) . ' manifest.json',
        ];
        echo shell_exec(implode(' && ', $bashCommands)), PHP_EOL;
    }
})();
