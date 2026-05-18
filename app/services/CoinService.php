<?php

class CoinService
{

    /*
    ====================================================
    REQUEST API
    ====================================================
    */

    private static function request($url)
    {

        $context = stream_context_create([

            'http' => [

                'method' => 'GET',

                'header' =>
                "User-Agent: Mozilla/5.0\r\n" .
                    "Accept: application/json\r\n",

                'timeout' => 5

            ]

        ]);

        $response = @file_get_contents(
            $url,
            false,
            $context
        );

        if ($response === false) {

            return null;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            return null;
        }

        return $data;
    }

    /*
    ====================================================
    MARKET DATA
    ====================================================
    */

    public static function getMarketData()
    {

        $url = "https://api.coingecko.com/api/v3/coins/markets?" .
            "vs_currency=usd&order=market_cap_desc" .
            "&per_page=100&page=1&sparkline=false";

        $data = self::request($url);

        /*
        ====================================================
        VALIDAR
        ====================================================
        */

        if (!$data || !is_array($data)) {

            return self::fallbackCoins();
        }

        $coins = [];

        foreach ($data as $coin) {

            /*
            ================================================
            IGNORAR DADOS INVÁLIDOS
            ================================================
            */

            if (
                !isset($coin['name']) ||
                !isset($coin['symbol'])
            ) {
                continue;
            }

            $coins[] = [

                'rank' => (int)($coin['market_cap_rank'] ?? 0),

                'name' => $coin['name'] ?? 'Unknown',

                'symbol' => strtoupper(
                    $coin['symbol'] ?? '---'
                ),

                'price' => (float)(
                    $coin['current_price'] ?? 0
                ),

                'change' => (float)(
                    $coin['price_change_percentage_24h'] ?? 0
                ),

                'market_cap' => (float)(
                    $coin['market_cap'] ?? 0
                ),

                'volume' => (float)(
                    $coin['total_volume'] ?? 0
                ),

                'ath' => (float)(
                    $coin['ath'] ?? 0
                ),

                'supply' => (float)(
                    $coin['circulating_supply'] ?? 0
                ),

                'image' => $coin['image']
                    ?? 'https://via.placeholder.com/100'

            ];
        }

        /*
        ====================================================
        EVITAR ARRAY VAZIO
        ====================================================
        */

        if (empty($coins)) {

            return self::fallbackCoins();
        }

        return $coins;
    }

    /*
    ====================================================
    FALLBACK
    ====================================================
    */

    private static function fallbackCoins()
    {

        return [

            [

                'rank' => 1,
                'name' => 'Bitcoin',
                'symbol' => 'BTC',
                'price' => 0,
                'change' => 0,
                'market_cap' => 0,
                'volume' => 0,
                'ath' => 0,
                'supply' => 0,
                'image' =>
                'https://assets.coingecko.com/coins/images/1/large/bitcoin.png'

            ],

            [

                'rank' => 2,
                'name' => 'Ethereum',
                'symbol' => 'ETH',
                'price' => 0,
                'change' => 0,
                'market_cap' => 0,
                'volume' => 0,
                'ath' => 0,
                'supply' => 0,
                'image' =>
                'https://assets.coingecko.com/coins/images/279/large/ethereum.png'

            ],

            [

                'rank' => 3,
                'name' => 'Solana',
                'symbol' => 'SOL',
                'price' => 0,
                'change' => 0,
                'market_cap' => 0,
                'volume' => 0,
                'ath' => 0,
                'supply' => 0,
                'image' =>
                'https://assets.coingecko.com/coins/images/4128/large/solana.png'

            ]

        ];
    }
}
