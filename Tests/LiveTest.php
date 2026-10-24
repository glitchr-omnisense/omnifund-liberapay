<?php

namespace Omnifund\Liberapay\Tests;

use Omnifund\Liberapay\LiberapayProvider;
use Omnifund\Model\Period;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

/** Liberapay itself, without a key: one public.json, the Liberapay team's. `docker compose run --rm omnifund live`. */
#[Group('live')]
final class LiveTest extends TestCase
{
    public function testThePublicProfileOfTheLiberapayTeam(): void
    {
        $profile = (new LiberapayProvider(HttpClient::create(['timeout' => 20]), 'Liberapay', userAgent: 'omnifund-tests/1.0 (+https://github.com/glitchr-studio/omnifund)'))->profile();

        self::assertSame('Liberapay', $profile->handle);
        self::assertGreaterThan(100, $profile->sponsors);
        self::assertSame(Period::Week, $profile->income?->period);
        self::assertNotNull($profile->description);
    }
}
