<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Elastic\Elasticsearch\ClientBuilder;

class Elastic extends Model
{
    public $_isoCode;
    public $client;


    public function __construct() {
        $this->client = ClientBuilder::create()
            ->setHosts([env('ELASTIC_HOST') . ':' . env('ELASTIC_PORT')])
            ->setBasicAuthentication(env('ELASTIC_USER'), env('ELASTIC_PASS'))
            ->build();
    }
    // function __construct(){
	// 	parent::__construct();
	// 	$hosts= [

    //                 'host'=>'mwstore.music-worx.com',
    //                 'scheme' => 'https',
    //                 'port'=>'443',
    //                 'user'=>'admin',
    //                 'pass'=>'hyrin'
    //     ];

    //   	$this->client = ClientBuilder::create()->setHosts($hosts)->build();

	// 	$this->_isoCode=0;
	// 	// if(!is_cli()){ $this->load->library('session'); }
	// 	// $this->load->library('redis_lib');
	// }

    // function __construct()
    // {
    //     parent::__construct();
    //     // Ensure this is a simple array of strings, not an array of arrays.
    //     $hosts = config('elastic')['hosts'];  // Should be an array like ['http://localhost:9200']

    //     // If $hosts is an array of arrays, flatten it.
    //     if (is_array($hosts) && count($hosts) > 0 && is_array($hosts[0])) {
    //         $hosts = array_map(function ($host) {
    //             return $host['url'];  // Adjust based on your actual host structure
    //         }, $hosts);
    //     }

    //     $this->client = ClientBuilder::create()->setHosts($hosts)->build();
    //     $this->_isoCode = 0;
    // }

    // public function __construct()
    // {
    //     $hosts = config('elastic.hosts');
    //     $this->client = ClientBuilder::create()->setHosts($hosts)->build();
    //     $this->_isoCode = 'WW';
    // }

    public function search_releases($term, $num_rec_per_page, $start_from, $promoted = 0)
    {
        $countries = ['WW'];
        // if ($this->_isoCode() != 'WW') {
        //     array_push($countries, $this->_isoCode());
        // }

        $countries = array_map('strtolower', $countries);
        dd($term);

        $must_not = [];
        $exclude_genre = array(1, "Jazz", "Funk & Soul", "Reggae / Dub", "Ambient", "Lounge / Chill Out");
        foreach ($exclude_genre as $genre) {
            array_push($must_not, ['term' => ['gener.keyword' => $genre]]);
        }
        // Query
        $params = [
            'index' => 'mw_releases',
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'nested' => [
                                    'path' => 'tracks',
                                    'query' => [
                                        'multi_match' => [
                                            'query' => $term,
                                            'fields' => ['tracks.song_name', 'tracks.mix_name'],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'multi_match' => [
                                    'query' => $term,
                                    'fields' => ['title^3', 'artists.artist_name^2', 'description']
                                ]
                            ],
                        ],
                        'minimum_should_match' => 1,
                        'filter' => [
                            'bool' => [
                                'must_not' => $must_not,
                                'must' => [
                                    ['exists' => ['field' => 'slug']],
                                    [
                                        'term' => [
                                            'online' => true,
                                        ]
                                    ],
                                    [
                                        'term' => [
                                            'is_deleted' => false,
                                        ]
                                    ],
                                    [
                                        'terms' => [
                                            'countries.country' => $countries,
                                        ]
                                    ],
                                    [
                                        'range' => [
                                            'release_date' => [
                                                'lte' => date('Y-m-d')
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                "sort" => [
                    "_score" => ["order" => "desc"],
                    "original_release_date" => ["order" => "desc"]
                ],
                'size' => $num_rec_per_page,
                'from' => $start_from,
            ]
        ];
        if ($promoted) {
            $params['body']['query']['bool']['filter']['bool']['must'][] = ['term' => ['is_promoted' => true]];
        }
        $response = $this->client->search($params);
        $found_releases = [];
        foreach ($response['hits']['hits'] as $key => $value) {
            array_push($found_releases, $value['_source']);
        }
        return ['releases' => $found_releases, 'total' => $response['hits']['total']['value']];
    }
}
