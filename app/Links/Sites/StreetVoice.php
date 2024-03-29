<?php

namespace App\Links\Sites;

use Illuminate\Support\Str;

class StreetVoice extends SiteContract
{
    public function name()
    {
        return 'street_voice';
    }

    /**
     * extract resource id from url
     *
     * the path must meets the three segments pattern
     *
     * @return string|null
     */
    public function getResourceId()
    {
        $path = trim($this->url->path(), '/');

        if (!$this->isThreeSegments($path)) {
            return null;
        }

        return $path ?: null;
    }

    public static function generalUrl($resource_id)
    {
        return "https://streetvoice.com/{$resource_id}";
    }

    public static function embeddedUrl($resource_id)
    {
        $resource_id = Str::between($resource_id, 'songs/', '/');

        return "https://streetvoice.com/music/embed/?id={$resource_id}";
    }

    /**
     * check the path is meets XXX/songs/XXX pattern or not
     *
     * @param string $path
     *
     * @return bool
     */
    protected function isThreeSegments($path)
    {
        $segments = explode('/', $path);

        return count($segments) === 3;
    }
}
