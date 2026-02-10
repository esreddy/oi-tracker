<?php namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;

class NseClient
{
    private Client $http;
    private FileCookieJar $jar;

    public function __construct()
    {
        $cookieFile = WRITEPATH . 'nse_cookies.json'; // persisted cookies
        $this->jar = new FileCookieJar($cookieFile, true);

        $this->http = new Client([
            'base_uri' => 'https://www.nseindia.com/',
            'timeout'  => 20,
            'headers'  => [
                'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36',
                'Accept'          => 'application/json, text/plain, */*',
                'Accept-Language' => 'en-IN,en;q=0.9',
                'Referer'         => 'https://www.nseindia.com/option-chain',
                'Origin'          => 'https://www.nseindia.com',
                'Connection'      => 'keep-alive',
            ],
            'cookies' => $this->jar,
            'allow_redirects' => true,
        ]);
    }

    /** Warm up session to get cookies */
    private function warmup(): void
    {
        // hit home & OC page to receive CloudFront cookies
        $this->http->get('/', ['cookies' => $this->jar]);
        $this->http->get('/option-chain', ['cookies' => $this->jar]);
    }

    /** Fetch option chain JSON for indices (NIFTY/BANKNIFTY/FINNIFTY) */
    public function fetchOptionChain(string $symbol): array
    {
        $symbol = strtoupper($symbol);
        $this->warmup();

        $resp = $this->http->get("/api/option-chain-indices", [
            'query'   => ['symbol' => $symbol],
            'cookies' => $this->jar,
            'headers' => [
                'Cache-Control' => 'no-cache',
            ],
        ]);

        $json = json_decode($resp->getBody()->getContents(), true);
        if (!is_array($json)) {
            throw new \RuntimeException('Invalid NSE response');
        }
        return $json;
    }

    /** Utility: extract ordered expiryDates from OC payload */
    public static function expiryDates(array $oc): array
    {
        $arr = $oc['records']['expiryDates'] ?? [];
        // NSE returns ascending order (nearest first); keep as-is
        return array_values(array_unique($arr));
    }
}
