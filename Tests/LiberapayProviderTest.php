<?php

namespace Omnifund\Liberapay\Tests;

use Omnifund\Exception\NotSupportedException;
use Omnifund\Exception\ProviderException;
use Omnifund\Liberapay\LiberapayFactory;
use Omnifund\Liberapay\LiberapayProvider;
use Omnifund\Model\Period;
use Omnifund\Registry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/** Liberapay's public.json recorded (the Liberapay team's own of 2026-10-24, shortened), each request checked. */
final class LiberapayProviderTest extends TestCase
{
    /** @var list<array{method: string, url: string, options: array<mixed>}> */
    private array $requests = [];

    public function testThePublicProfile(): void
    {
        $profile = $this->provider('public')->profile();

        self::assertSame(['liberapay', 'Liberapay', 'Liberapay'], [$profile->provider, $profile->handle, $profile->name], 'no display name: the username');
        self::assertSame('https://liberapay.com/Liberapay', $profile->url);
        self::assertSame('We’re building the best recurrent donations platform in the world.', $profile->description, 'the summary in English');
        self::assertSame(1782, $profile->sponsors);
        self::assertSame(['636.28', 'EUR', Period::Week], [$profile->income?->amount->decimal(), $profile->income?->amount->currency, $profile->income?->period]);
        self::assertSame('2757.21', $profile->income?->monthly()?->decimal(), '636.28 a week, 52 weeks a year');
        self::assertSame(['1000.00', Period::Week, 63], [$profile->goal?->amount?->decimal(), $profile->goal?->period, $profile->goal?->percent]);

        self::assertSame(['GET', 'https://liberapay.com/Liberapay/public.json'], [$this->requests[0]['method'], $this->requests[0]['url']]);
        self::assertContains('User-Agent: omnifund (+https://github.com/glitchr-omnisense/omnifund)', $this->headers(0));
    }

    public function testAnAccountThatHidesWhatItReceives(): void
    {
        $profile = $this->provider('hidden', 'fr')->profile('octo');

        self::assertSame(['octo', 'Octo', 4], [$profile->handle, $profile->name, $profile->sponsors]);
        self::assertNull($profile->income);
        self::assertNull($profile->goal);
        self::assertSame('Je code des outils libres.', $profile->description);
        self::assertSame('https://liberapay.com/octo/public.json', $this->requests[0]['url']);
    }

    public function testASummaryInAnotherLanguageThanAsked(): void
    {
        self::assertSame('Je code des outils libres.', $this->provider('hidden', 'de')->profile('octo')->description, 'the first one');
    }

    public function testAnAccountLiberapayDoesNotKnow(): void
    {
        $provider = new LiberapayProvider(new MockHttpClient(new MockResponse('{"error_code":404,"error_message":"Not Found"}', ['http_code' => 404])), 'nobody-here-0');

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('Liberapay answered 404 for the account nobody-here-0.');
        $provider->profile();
    }

    public function testAProfileOnly(): void
    {
        $registry = new Registry([new LiberapayFactory(new MockHttpClient())], ['lp' => ['factory' => 'liberapay', 'options' => ['account' => 'Liberapay']]]);

        self::assertSame(['profile'], $registry->get('lp')->capabilities()->contracts());
        $this->expectException(NotSupportedException::class);
        $registry->sponsors('lp');
    }

    private function provider(string $answer, string $language = 'en'): LiberapayProvider
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options) use ($answer): MockResponse {
            $this->requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

            return new MockResponse((string) file_get_contents(__DIR__."/Fixtures/$answer.json"));
        });

        return new LiberapayProvider($http, 'Liberapay', $language);
    }

    /** @return list<string> */
    private function headers(int $request): array
    {
        $headers = $this->requests[$request]['options']['headers'] ?? [];

        return \is_array($headers) ? array_values(array_filter($headers, 'is_string')) : [];
    }
}
