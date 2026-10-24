# omnifund/liberapay

Liberapay for [omniscient/omnifund](https://github.com/glitchr-studio/omnifund), under any PHP
host: an account's public profile read from `https://liberapay.com/<user>/public.json` - patrons
counted, what it receives and its goal (by the week, as Liberapay counts), its summary. No key.

```php
use Omnifund\Liberapay\LiberapayFactory;
use Omnifund\Registry;

$registry = new Registry([new LiberapayFactory($httpClient)], [
    'liberapay' => ['factory' => 'liberapay', 'options' => ['account' => 'Liberapay']],
]);
$registry->profile('liberapay')->profile()->income?->monthly();
```

Options: `account` (the username, required), `language` (the summary's, `en`; else the first
one), `base_url`, `user_agent`.

A **profile only**: Liberapay shows no patron by name and has no tiers, so `sponsors()` and
`tiers()` are refused by the registry (`NotSupportedException`). An account may hide what it
receives: `income` and the goal's percentage are then null.

No library is wrapped: there is none, and the endpoint is one JSON document.

## Terms

Liberapay's [legal information](https://liberapay.com/about/legal) and
[privacy policy](https://liberapay.com/about/privacy). Liberapay is run by a non-profit on
donations: send a User-Agent naming your application, and cache what you read (its data changes
weekly).

## Verified, and not

- Verified on recorded answers (the Liberapay team's public.json of 2026-10-24, shortened): the
  profile, a weekly income by the month, the goal's percentage, an account hiding its income, the
  summary's language, an unknown account, the capability refused; `BareTest`; PHPStan at the
  highest level.
- Verified live without a key (2026-10-24): the Liberapay team's profile.

## Responsibility

This package is an independent, unofficial client: it is not affiliated with, endorsed by or
sponsored by Liberapay. Its MIT licence covers this package's code only and grants no right to
the service or its data. Whoever installs and runs it is responsible for complying with
Liberapay's terms and for any use made of what it returns; the authors accept no liability for how
it is used (see [LICENSE](LICENSE)).

## Licence

MIT.
