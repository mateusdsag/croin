<?php

class FearGreedService {

    private static  $cacheFile = '';

    private static function getCacheFile(): string {
        if(self::$cacheFile === ''){
            self::$cacheFile = __DIR__ . '/../../cache/fear_greed.json';
        }
        return self::$cacheFile;
    }

    public static function getIndex(): ?array {

        $file = self::getCacheFile();

        // Cache de 5 minutos
        if(file_exists($file) && (time() - filemtime($file) < 300)){
            $raw = file_get_contents($file);
            $data = json_decode($raw, true);
            if(is_array($data) && isset($data['value'])){
                return $data;
            }
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 10,
                'header'  =>
                    "User-Agent: Mozilla/5.0 (compatible; CroinBot/1.0)\r\n" .
                    "Accept: application/json\r\n",
                'ignore_errors' => true,
            ]
        ]);

        $raw = @file_get_contents(
            'https://api.alternative.me/fng/?limit=1',
            false,
            $context
        );

        if(!$raw) return self::getStaleCache($file);

        $data = json_decode($raw, true);

        if(!isset($data['data'][0])) return self::getStaleCache($file);

        $result = $data['data'][0];

        $dir = dirname($file);
        if(!is_dir($dir)) @mkdir($dir, 0755, true);

        @file_put_contents($file, json_encode($result));

        return $result;
    }

    private static function getStaleCache(string $file): ?array {
        if(!file_exists($file)) return null;
        $data = json_decode(file_get_contents($file), true);
        return (is_array($data) && isset($data['value'])) ? $data : null;
    }
}