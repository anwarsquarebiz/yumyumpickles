<?php

namespace App\Services\Ads;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Capture Meta click identifiers at checkout so CAPI can attribute a later Purchase.
 *
 * @phpstan-type Snapshot array{
 *     event_id: string,
 *     fbp: string|null,
 *     fbc: string|null,
 *     client_ip: string|null,
 *     user_agent: string|null
 * }
 */
class MetaAttribution
{
    public function __construct(private readonly Request $request) {}

    /**
     * @return Snapshot
     */
    public function capture(): array
    {
        return [
            'event_id' => (string) Str::uuid(),
            'fbp' => $this->cookie('_fbp'),
            'fbc' => $this->fbc(),
            'client_ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ];
    }

    private function fbc(): ?string
    {
        $cookie = $this->cookie('_fbc');

        if ($cookie !== null) {
            return $cookie;
        }

        $fbclid = $this->request->query('fbclid');

        if (! is_string($fbclid) || $fbclid === '') {
            return null;
        }

        return 'fb.1.'.(int) floor(microtime(true) * 1000).'.'.$fbclid;
    }

    private function cookie(string $name): ?string
    {
        $value = $this->request->cookie($name);

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
