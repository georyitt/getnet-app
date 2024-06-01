<?php

namespace App\Http\Utils;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class GetNetUtil
{
    private $nonce;
    private $seed;
    private $login;
    private $secretKey;

    public function __construct()
    {
        $this->login = env('GETNET_LOGIN');
        $this->secretKey = env('GETNET_SECRET_KEY');
        $this->nonce = random_bytes(16);
        $this->seed = Carbon::now()->toIso8601String();
    }

    public static function factory(): self
    {
        return new self();
    }

    public function getNonce(): string
    {
        return base64_encode($this->nonce);
    }

    public function getSeed(): string
    {
        return $this->seed;
    }

    public function getTransKey(): string
    {
        $transKey = $this->nonce . $this->seed . $this->secretKey;
        return base64_encode(hash('sha256', $transKey, true));
    }

    public function getExpiredDate(): string
    {
        $now = new DateTime();
        $now->add(new DateInterval('PT10M'));
        return $now->format(DateTimeInterface::ISO8601);
    }

    /**
     * @return mixed
     */
    public function getLogin()
    {
        return $this->login;
    }

    public function auth(): array
    {
        return [
            'login' => $this->getLogin(),
            'nonce' => $this->getNonce(),
            'seed' => $this->getSeed(),
            'tranKey' => $this->getTransKey(),
        ];
    }
}
