<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Browser/phone push subscriptions of the signed-in staff member (one per device). */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => $this->endpointRules(),
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'in:aesgcm,aes128gcm'],
        ]);

        $request->user()->updatePushSubscription($data['endpoint'], $data['keys']['p256dh'], $data['keys']['auth'], $data['contentEncoding'] ?? null);

        return response()->json(status: 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->deletePushSubscription($request->validate(['endpoint' => $this->endpointRules()])['endpoint']);

        return response()->json(status: 204);
    }

    /** @return list<mixed> */
    private function endpointRules(): array
    {
        return ['required', 'url:https', 'max:500', function (string $attribute, mixed $value, \Closure $fail) {
            $host = strtolower((string) parse_url($value, PHP_URL_HOST));

            if (! Str::is(config('webpush.allowed_hosts'), $host)) {
                $fail(__('validation.url', ['attribute' => $attribute]));
            }
        }];
    }
}
