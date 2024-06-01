<?php

namespace App\Http\Services;

class HttpRequestLog
{
    public static function factory(): self
    {
        return new self();
    }

    public function register($data, $filename, $path = '')
    {
        $filename = $filename . '_' . time() . '.json';
        $path = public_path('json/'. $path . $filename);

        if (!file_exists(public_path('json'))) {
            mkdir(public_path('json'), 0777, true);
        }
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }
}
