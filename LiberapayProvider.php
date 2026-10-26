<?php

namespace Omnifund\Liberapay;

use Omnifund\Capabilities;
use Omnifund\Http\Json;
use Omnifund\Model\Goal;
use Omnifund\Model\Income;
use Omnifund\Model\Money;
use Omnifund\Model\Period;
use Omnifund\Model\Profile;
use Omnifund\ProfileInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * A Liberapay account's public profile (https://liberapay.com/<user>/public.json):
 * the patrons counted, what it receives and its goal - both by the week, as
 * Liberapay counts them; null when the account hides them. Liberapay shows
 * no patron by name and has no tiers: this provider is a profile only.
 */
final class LiberapayProvider implements ProfileInterface
{
    public const BASE_URL = 'https://liberapay.com';

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $account,
        private readonly string $language = 'en',
        private readonly string $baseUrl = self::BASE_URL,
        private readonly string $userAgent = 'omnifund (+https://github.com/glitchr-omnisense/omnifund)',
    ) {
    }

    public function getName(): string
    {
        return 'liberapay';
    }

    public function getTitle(): string
    {
        return 'Liberapay';
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities(profile: true, key: false, terms: ['https://liberapay.com/about/legal', 'https://liberapay.com/about/privacy']);
    }

    public function profile(?string $account = null): Profile
    {
        $account ??= $this->account;
        $url = rtrim($this->baseUrl, '/').'/'.rawurlencode($account);
        $data = Json::request($this->http, 'GET', $url.'/public.json', ['headers' => ['User-Agent' => $this->userAgent, 'Accept' => 'application/json']], 'Liberapay', "the account $account");
        $receiving = self::money(Json::get($data, 'receiving'));
        $goal = self::money(Json::get($data, 'goal'));
        $handle = Json::string($data, 'username') ?? $account;

        return new Profile(
            'liberapay',
            $handle,
            Json::string($data, 'display_name') ?? $handle,
            $this->summary(Json::list($data, 'summaries')),
            rtrim($this->baseUrl, '/').'/'.rawurlencode($handle),
            Json::string($data, 'avatar'),
            Json::int($data, 'npatrons'),
            null === $receiving ? null : new Income($receiving, Period::Week),
            null === $goal ? null : new Goal(amount: $goal, period: Period::Week, percent: null !== $receiving && $goal->cents > 0 ? (int) floor($receiving->cents * 100 / $goal->cents) : null),
        );
    }

    /** @param list<mixed> $summaries */
    private function summary(array $summaries): ?string
    {
        foreach ($summaries as $summary) {
            if ($this->language === Json::string($summary, 'lang')) {
                return Json::string($summary, 'content');
            }
        }

        return Json::string($summaries, 0, 'content');
    }

    private static function money(mixed $amount): ?Money
    {
        $value = Json::string($amount, 'amount');
        $currency = Json::string($amount, 'currency');

        return null === $value || null === $currency ? null : Money::fromDecimal($value, $currency);
    }
}
