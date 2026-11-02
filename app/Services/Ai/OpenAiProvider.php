<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Models\AiRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OpenAiProvider implements AiProvider
{
    public function generate(AiRequest $request, array $credentials, string $model): array
    {
        $apiKey = $credentials['api_key'] ?? null;
        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('The team OpenAI API key is not configured.');
        }

        $response = Http::acceptJson()
            ->withToken($apiKey)
            ->timeout((int) config('ai.openai.timeout', 60))
            ->post(rtrim((string) config('ai.openai.base_url'), '/').'/responses', [
                'model' => $model,
                'input' => $request->input,
            ])
            ->throw()
            ->json();

        $text = $response['output_text'] ?? null;
        if (! is_string($text) || trim($text) === '') {
            foreach ($response['output'] ?? [] as $output) {
                if (! is_array($output)) {
                    continue;
                }

                foreach ($output['content'] ?? [] as $content) {
                    if (
                        is_array($content)
                        && ($content['type'] ?? null) === 'output_text'
                        && is_string($content['text'] ?? null)
                    ) {
                        $text = $content['text'];
                        break 2;
                    }
                }
            }
        }

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('The OpenAI response did not contain usable text.');
        }

        return [
            'provider' => 'openai',
            'model' => $response['model'] ?? $model,
            'text' => $text,
            'usage' => $response['usage'] ?? null,
        ];
    }
}
