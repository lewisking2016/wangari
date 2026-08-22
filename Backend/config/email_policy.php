<?php
declare(strict_types=1);

function wangariAllowedEmailDomains(): array
{
    return [
        'gmail.com',
        'googlemail.com',
        'outlook.com',
        'hotmail.com',
        'live.com',
        'msn.com',
    ];
}

function wangariNormalizeEmail(string $email): string
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }

    [$local, $domain] = explode('@', $email, 2);
    $domain = strtolower($domain);
    $local = preg_replace('/\+.*$/', '', $local) ?? $local;

    if ($domain === 'googlemail.com') {
        $domain = 'gmail.com';
    }

    if ($domain === 'gmail.com') {
        $local = str_replace('.', '', $local);
    }

    return $local . '@' . $domain;
}

function wangariEmailVariants(string $email): array
{
    $email = strtolower(trim($email));
    $variants = [$email, wangariNormalizeEmail($email)];
    $variants = array_filter(array_unique($variants), static fn ($value) => $value !== '');
    return array_values($variants);
}

function wangariIsAllowedEmail(string $email): bool
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
    return in_array($domain, wangariAllowedEmailDomains(), true);
}

