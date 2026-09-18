<?php
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : 'auth';
$user = isset($_GET['username']) ? $_GET['username'] : 'unknown';
$password = isset($_GET['password']) ? $_GET['password'] : 'unknown';
$apiKey = "e08a763a7f3242cab49afdcc1ec63987";

// Log the incoming request
error_log("IPTV Request - User: $user, Password: $password Action: $action");


if ($action == 'get_live_streams') {
    header('Content-Type: application/json');

    $m3uContent = file_get_contents("https://tvnow.best/api/list/$user/$password/m3u8/livetv");
    $lines = explode("\n", $m3uContent);
    $channels = [];
    $currentChannel = [];
    $lineNum = 0;

    foreach ($lines as $line) {
        $line = trim($line);
        $lineNum = $lineNum + 1;
        if (strpos($line, '#EXTINF:') === 0) {
            // Extract Name
            preg_match('/,(.+)$/', $line, $nameMatches);
            $currentChannel['name'] = $nameMatches[1] ?? 'Unknown';
            
            // Extract Logo
            preg_match('/tvg-logo="([^"]+)"/', $line, $logoMatches);
            $currentChannel['stream_icon'] = $logoMatches[1] ?? '';
            
            // Extract Channel Number for ID
            //preg_match('/tvg-id="([^"]+)"/', $line, $chnoMatches);
            $currentChannel['stream_id'] = $lineNum;

            // Extract Group Title for Category ID
            preg_match('/group-title="([^"]+)"/', $line, $groupMatches);
            $currentChannel['category_id'] = $groupMatches[1] ?? '';    
            
            $currentChannel['stream_type'] = 'live';
        } elseif (strpos($line, 'http') === 0) {
            $currentChannel['direct_source'] = $line;
            $channels[] = $currentChannel;
            $currentChannel = [];
        }
    }

    echo json_encode($channels); 
} elseif ($action == 'get_live_categories') {
    header('Content-Type: application/json');

    $m3uContent = file_get_contents("https://tvnow.best/api/list/$user/$password/m3u8/livetv");
    $lines = explode("\n", $m3uContent);
    $categories = [];
    $uniqueGroups = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#EXTINF:') === 0) {
            // Extract Group Title
            preg_match('/group-title="([^"]+)"/', $line, $groupMatches);
            $groupTitle = $groupMatches[1] ?? '';
            
            // If a group title exists and we haven't seen it yet
            if (!empty($groupTitle) && !in_array($groupTitle, $uniqueGroups)) {
                $uniqueGroups[] = $groupTitle;
                
                $categories[] = [
                    "category_id" => $groupTitle,
                    "category_name" => $groupTitle,
                    "parent_id" => 0
                ];
            }
        }
    }      
    echo json_encode($categories); 
} elseif ($action == 'get_vod_streams') {
    header('Content-Type: application/json');

    $m3uContent = file_get_contents("https://tvnow.best/api/list/$user/$password/m3u8/movies");
    $lines = explode("\n", $m3uContent);
    $channels = [];
    $currentChannel = [];
    $count = 0;

    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#EXTINF:') === 0) {
            $count = $count + 1;
            // Extract Channel Number for ID
            preg_match('/tvg-id="tt([^"]+)"/', $line, $chnoMatches);
            $currentChannel['stream_id'] = (int)$chnoMatches[1];

            $data = getTMDbByIMDbId("tt" . strval($chnoMatches[1]), $apiKey);
            foreach ($data['movie_results'] as $details) {
                $currentChannel['name'] = $details['title'];
                $currentChannel['stream_icon'] = "https://image.tmdb.org/t/p/w185/" . $details['poster_path'];
                $currentChannel['category_id'] = $details['genre_ids'][0];
            }            
            $currentChannel['stream_type'] = 'movie';
        } elseif (strpos($line, 'http') === 0) {
            $currentChannel['direct_source'] = $line;
            $channels[] = $currentChannel;
            $currentChannel = [];
        }
        if ($count === 10) {
            break;
        }
    }

    echo json_encode($channels); 
} elseif ($action == 'get_vod_categories') {
    header('Content-Type: application/json');

    $categories = [];
    $url = "https://api.themoviedb.org/3/genre/movie/list";
    
    $params = [
        "api_key" => $apiKey,
        "language" => "en-US"
    ];

    $queryUrl = $url . "?" . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $queryUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);

    foreach ($data['genres'] as $genre) {
        $categories[] = [
            'category_id'   => $genre['id'],
            'category_name' => $genre['name'],
            'parent_id'     => 0
        ];
    }

    echo json_encode($categories); 
} elseif ($action == 'get_vod_info') {
    $vod_id = isset($_GET['vod_id']) ? $_GET['vod_id'] : 0;
    $info = [];

    $data = getTMDbByIMDbId($vod_id, $apiKey);
    foreach ($data['movie_results'] as $details) {
        $info['plot'] = $details['overview'];
        $info['releasedate'] = $details['release_date'];
        $info['rating'] = $details['vote_average'];
        $info['genre'] = $details['genre_ids'][0];
    }            

    echo json_encode($info); 
} elseif ($action == 'get_series') {
    header('Content-Type: application/json');
    $channels = [];
    $currentChannel = [];
    $uniqueGroups = [];

    for ($i = 1; $i <= 30; $i++) {
        $m3uContent = file_get_contents("https://tvnow.best/api/list/$user/$password/m3u8/tvshows/$i");
        $lines = explode("\n", $m3uContent);

        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '#EXTINF:') === 0) {
                // Extract Name
                preg_match('/,(.+)$/', $line, $nameMatches);
                $cleanString = preg_replace('/\s\(\d{4}\)\sS\d{2,4}\sE\d{2}/', '', $nameMatches[1]);
                $currentChannel['name'] = $cleanString ?? 'Unknown';
                
                // Extract Logo
                preg_match('/tvg-logo="([^"]+)"/', $line, $logoMatches);
                $currentChannel['cover'] = $logoMatches[1] ?? '';
                
                // Extract Channel Number for ID
                preg_match('/tvg-id="tt([^"]+)"/', $line, $chnoMatches);
                $currentChannel['series_id'] = (int)$chnoMatches[1];

                // Extract Group Title for Category ID
                //preg_match('/group-title="([^"]+)"/', $line, $groupMatches);
                $currentChannel['category_id'] = strval($i); //$groupMatches[1] ?? '';    

                if (!in_array($cleanString, $uniqueGroups)) {
                    $uniqueGroups[] = $cleanString;
                    $channels[] = $currentChannel;
                }
                $currentChannel = [];
            }
        }
    }

    echo json_encode($channels); 
} elseif ($action == 'get_series_categories') {
    header('Content-Type: application/json');

    $categories = [];
    $uniqueGroups = [];
    for ($i = 1; $i <= 30; $i++) {
        $m3uContent = file_get_contents("https://tvnow.best/api/list/$user/$password/m3u8/tvshows/$i");
        $lines = explode("\n", $m3uContent);
        $catID = strval($i);

        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '#EXTINF:') === 0) {
                // Extract Group Title
                preg_match('/group-title="([^"]+)"/', $line, $groupMatches);
                $groupTitle = $groupMatches[1] ?? '';
                
                // If a group title exists and we haven't seen it yet
                //if (!empty($groupTitle) && !in_array($groupTitle, $uniqueGroups)) {
                if (!empty($catID) && !in_array($catID, $uniqueGroups)) {
                    $uniqueGroups[] = $catID; //$groupTitle;
                    
                    $categories[] = [
                        "category_id" => $catID, //$groupTitle,
                        "category_name" => $catID, //$groupTitle,
                        "parent_id" => 0
                    ];
                }
            }
        }      
    }
    echo json_encode($categories);
} elseif ($action == 'get_series_info') {
    $series_id = isset($_GET['series_id']) ? (int)$_GET['series_id'] : 0;
    
    $response = ["info" => [], "seasons" => [], "episodes" => []];
    
    switch ($series_id) {
        case 5000: // The Beverly Hillbillies
            $response = [
                "info" => ["name" => "The Beverly Hillbillies", "plot" => "A poor backwoods family strikes oil."],
                "seasons" => [["season_number" => 1, "episode_count" => 1]],
                "episodes" => [
                    "1" => [
                        [
                            "id" => 50001, 
                            "title" => "The Clampetts Strike Oil", 
                            "season" => 1, 
                            "episode_num" => 1, 
                            "direct_source" => "https://dn710007.ca.archive.org/0/items/731d-0c-436b-6236618f-110f-67a-65cb-9d-0-360p/00efdd5717132ce3a95944dd2f83dba6-360p.mp4"
                        ],
                        [
                            "id" => 50002, 
                            "title" => "Getting Settled", 
                            "season" => 1, 
                            "episode_num" => 2, 
                            "direct_source" => "https://dn710007.ca.archive.org/0/items/731d-0c-436b-6236618f-110f-67a-65cb-9d-0-360p/0570d5a39fca358ee78cd3a7e3b1b30e-360p.mp4"
                        ]                        
                    ],
                    "2" => [
                        [
                            "id" => 500037, 
                            "title" => "Jed Gets the Misery", 
                            "season" => 2, 
                            "episode_num" => 1, 
                            "direct_source" => "https://dn720400.ca.archive.org/0/items/1ce-6aa-4d-1419f-3ea-53108b-15c-5179948-360p/030689f2423e6b68d344052806099f39-360p.mp4"
                        ],
                        [
                            "id" => 500038, 
                            "title" => "Hair-Raising Holiday", 
                            "season" => 2, 
                            "episode_num" => 2, 
                            "direct_source" => "https://dn800200.us.archive.org/0/items/1ce-6aa-4d-1419f-3ea-53108b-15c-5179948-360p/11fdbb529bd768372fe784c9dee5357e-360p.mp4"
                        ]   
                    ]

                ]
            ];
            break;
        case 5001: // The Dick Van Dyke Show
            $response = [
                "info" => [
                    "name" => "The Dick Van Dyke Show", 
                    "plot" => "The misadventures of a TV writer."
                ],
                "seasons" => [["season_number" => 1, "episode_count" => 1]],
                "episodes" => [
                    "1" => [
                        [
                            "id" => 500011, 
                            "title" => "A Man's Teeth are not his Own", 
                            "season" => 1, 
                            "episode_num" => 1, 
                            "direct_source" => "https://dn710705.ca.archive.org/0/items/The_Dick_van_Dyke_Show/A_MANS_TEETH_ARE_NOT_HIS_OWN.mp4"
                        ],
                        [
                            "id" => 500012, 
                            "title" => "Give me your Walls", 
                            "season" => 1, 
                            "episode_num" => 2, 
                            "direct_source" => "https://dn710705.ca.archive.org/0/items/The_Dick_van_Dyke_Show/GIVE_ME_YOUR_WALLS.mp4"
                        ],
                        [
                            "id" => 500013, 
                            "title" => "Hustling the Hustler", 
                            "season" => 1, 
                            "episode_num" => 3, 
                            "direct_source" => "https://dn710705.ca.archive.org/0/items/The_Dick_van_Dyke_Show/HUSTLING_THE_HUSTLER.mp4"
                        ]

                    ]
                ]
            ];
            break;
        case 5003: // The Lucy Show
            $response = [
                "info" => ["name" => "The Lucy Show", "plot" => "The comic misadventures of a widow."],
                "seasons" => [["season_number" => 1, "episode_count" => 1]],
                "episodes" => [
                    "1" => [
                        [
                            "id" => 500031, 
                            "title" => "Chris Goes Steady", 
                            "season" => 1, 
                            "episode_num" => 1, 
                            "direct_source" => "https://dn720400.ca.archive.org/0/items/the-lucy-show-lucy-buys-a-boat/The%20Lucy%20Show%20-%20Chris%20Goes%20Steady.mp4"
                        ],
                        [
                            "id" => 500032, 
                            "title" => "Chris New Years Eve Party", 
                            "season" => 1, 
                            "episode_num" => 2, 
                            "direct_source" => "https://dn720400.ca.archive.org/0/items/the-lucy-show-lucy-buys-a-boat/The%20Lucy%20Show%20-%20Chris%20New%20Years%20Eve%20Party.mp4"
                        ],
                        [
                            "id" => 500033, 
                            "title" => "Ethel Merman and the Boy Scout Show", 
                            "season" => 1, 
                            "episode_num" => 3, 
                            "direct_source" => "https://dn720400.ca.archive.org/0/items/the-lucy-show-lucy-buys-a-boat/The%20Lucy%20Show%20-%20Ethel%20Merman%20And%20The%20Boy%20Scout%20Show.mp4"
                        ]
                    ]
                ]
            ];
            break;
        case 5004: // Sherlock Holmes
            $response = [
                "info" => ["name" => "Sherlock Holmes (1954)", "plot" => "The classic detective solves mysteries."],
                "seasons" => [["season_number" => 1, "episode_count" => 1]],
                "episodes" => ["1" => [["id" => 50041, "title" => "The Case of the Cunningham Heritage", "season" => 1, "episode_num" => 1, "direct_source" => "https://archive.org/download/SherlockHolmesTheCaseOfTheCunninghamHeritage/SherlockHolmesTheCaseOfTheCunninghamHeritage.mp4"]]]
            ];
            break;
    }
    
    echo json_encode($response);
} else {
    echo json_encode([
        "user_info" => ["username" => "demo", "status" => "Active", "exp_date" => "1999999999"],
        "server_info" => ["url" => "https://" . $_SERVER['HTTP_HOST'], "port" => "443"]
    ]);
}

function getTMDbByIMDbId($imdbId, $apiKey) {
    // TMDb find endpoint for external IDs
    $url = "https://api.themoviedb.org/3/find/" . urlencode($imdbId);
    
    $params = [
        "api_key" => $apiKey,
        "external_source" => "imdb_id"
    ];

    $queryUrl = $url . "?" . http_build_query($params);
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $queryUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return "Error: " . curl_error($ch);
    }
    
    curl_close($ch);
    
    // Decode the JSON response
    $data = json_decode($response, true);
    
    return $data;
}

?>