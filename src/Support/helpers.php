<?php

if (! function_exists('spy_csv')) {
    function spy_csv(string $key, string $default = ''): array
    {
        return array_filter(array_map('trim', explode(',', env($key, $default))));
    }
}

if (! function_exists('spy_rule_map')) {
    function spy_rule_map(string $key, string $default = ''): array
    {
        $result = [];
        foreach (explode('|', env($key, $default)) as $part) {

            if (strpos($part, ':') === false) {
                $result['*'] = array_merge($result['*'] ?? [], explode(',', $part));
            } else {
                [$domains, $keys] = explode(':', $part, 2);
                $keyList = explode(',', $keys);
                foreach (explode(',', $domains) as $domain) {
                    $domain = trim($domain);
                    $result[$domain] = array_merge($result[$domain] ?? [], $keyList);
                }
            }
        }

        return $result;
    }

}
