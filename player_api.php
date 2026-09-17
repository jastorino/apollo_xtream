<?php
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : 'auth';
$user = isset($_GET['username']) ? $_GET['username'] : 'unknown';
$password = isset($_GET['password']) ? $_GET['password'] : 'unknown';

// Log the incoming request
error_log("IPTV Request - User: $user, Password: $password Action: $action");


if ($action == 'get_live_streams') {
    error_log("Found Action: $action");
    header('Content-Type: application/json');

    $m3uContent = file_get_contents("https://tvnow.best/api/list/$user/$password/m3u8/livetv");
    $lines = explode("\n", $m3uContent);
    $channels = [];
    $currentChannel = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#EXTINF:') === 0) {
            // Extract Name
            preg_match('/,(.+)$/', $line, $nameMatches);
            $currentChannel['name'] = $nameMatches[1] ?? 'Unknown';
            
            // Extract Logo
            preg_match('/tvg-logo="([^"]+)"/', $line, $logoMatches);
            $currentChannel['stream_icon'] = $logoMatches[1] ?? '';
            
            // Extract Channel Number for ID
            preg_match('/tvg-chno="([^"]+)"/', $line, $chnoMatches);
            $currentChannel['stream_id'] = (int)($chnoMatches[1] ?? rand(1000, 9999));

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
    error_log("Found Action: $action");
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
                    "category_name" => "PlutoTV - " . $groupTitle,
                    "parent_id" => 0
                ];
            }
        }
    }      
    echo json_encode($categories); 
} elseif ($action == 'get_vod_streams') {
    error_log("Found Action: $action");
    echo json_encode([
        [
            "category_id" => "3000",
            "stream_id" => 100,
            "name" => "My Man godfrey",
            "stream_type" => "movie",
            "container_extension" => "mp4",
            "stream_icon" => "https://images.justwatch.com/poster/35151046/s332/my-man-godfrey.avif",
            "direct_source" => "https://dn600307.us.archive.org/0/items/MyManGodfrey1936/MyManGodfrey1936_512kb.mp4"
        ],
        [
            "category_id" => "3000",
            "stream_id" => 101,
            "name" => "Kansas City Confidential",
            "stream_type" => "movie",
            "container_extension" => "mp4",
            "stream_icon" => "https://images.justwatch.com/poster/134524696/s332/kansas-city-confidential.avif",
            "direct_source" => "https://dn720304.ca.archive.org/0/items/kansas-city-confidential-1952_202307/Kansas%20City%20Confidential%20%281952%29.mp4"
        ],
        [
            "category_id" => "3000",
            "stream_id" => 102,
            "name" => "House On Hunted Hill",
            "stream_type" => "movie",
            "container_extension" => "mp4",
            "stream_icon" => "https://images.justwatch.com/poster/354328131/s332/house-on-haunted-hill-1.avif",
            "direct_source" => "https://dn720307.ca.archive.org/0/items/house-of-hunted-hill-4-k/HouseOfHuntedHill4K.mp4"
        ],
        [
            "category_id" => "3000",
            "stream_id" => 103,
            "name" => "Sunrise: A Song of Two Humans",
            "stream_type" => "movie",
            "container_extension" => "mp4",
            "stream_icon" => "https://images.justwatch.com/poster/302413789/s332/sunrise-a-song-of-two-humans.avif",
            "direct_source" => "https://dn711400.ca.archive.org/0/items/sunrise.-a.-song.-of.-two.-humans.-1927.720p.-blu-ray.x-264-hdclub-public-hd/Sunrise.A.Song.Of.Two.Humans.1927.720p.BluRay.x264-HDCLUB%20%5BPublicHD%5D.mp4"
        ]
    ]);
} elseif ($action == 'get_vod_info') {
    $vod_id = isset($_GET['vod_id']) ? $_GET['vod_id'] : 0;
    
    switch ($vod_id) {
    case '100':
        echo json_encode([
            "info" => [
                "plot" => "Fifth Avenue socialite Irene Bullock needs a forgotten man to win a scavenger hunt, and no one is more forgotten than Godfrey Park, who resides in a dump by the East River. Irene hires Godfrey as a servant for her riotously unhinged family, to the chagrin of her spoiled sister, Cornelia, who tries her best to get Godfrey fired. As Irene falls for her new butler, Godfrey turns the tables and teaches the frivolous Bullocks a lesson or two.",
                "releasedate" => "1936",
                "rating" => "7.9",
                "genre" => "Comedy, Romance",
                "duration" => "93 min",
                "movie_image" => "https://images.justwatch.com/poster/35151046/s332/my-man-godfrey.avif"
            ]
        ]);    
        break;        
    case '101':
        echo json_encode([
            "info" => [
                "plot" => "A down-on-his-luck ex-GI finds himself framed for an armored car robbery. When he's finally released for lack of evidence--after having been beaten up and tortured by the police--he sets out to discover who set him up, and why. The trail leads him into Mexico and a web of hired killers and corrupt cops.",
                "releasedate" => "1952",
                "rating" => "7.3",
                "genre" => "Crime, Drama, Mystery & Thriller, Organized Crime",
                "duration" => "100 min",
                "movie_image" => "https://images.justwatch.com/poster/134524696/s332/kansas-city-confidential.avif"
            ]
        ]);    
        break;        
    case '102':
        echo json_encode([
            "info" => [
                "plot" => "A down-on-his-luck ex-GI finds himself framed for an armored car robbery. When he's finally released for lack of evidence--after having been beaten up and tortured by the police--he sets out to discover who set him up, and why. The trail leads him into Mexico and a web of hired killers and corrupt cops.",
                "releasedate" => "1952",
                "rating" => "6.7",
                "genre" => "Crime, Horror, Mystery & Thriller, Paranormal",
                "duration" => "75 min",
                "movie_image" => "https://images.justwatch.com/poster/354328131/s332/house-on-haunted-hill-1.avif"
            ]
        ]);    
        break;        
    case '103':
        echo json_encode([
            "info" => [
                "plot" => "A married farmer falls under the spell of a slatternly woman from the city, who tries to convince him to drown his wife.",
                "releasedate" => "1929",
                "rating" => "8.1",
                "genre" => "Drama, Romance, Romantic Drama",
                "duration" => "94 min",
                "movie_image" => "https://images.justwatch.com/poster/354328131/s332/house-on-haunted-hill-1.avif"
            ]
        ]);    
        break;        
    default:
        // Code for authentication (if no action matches)
        break;
    }
} elseif ($action == 'get_series') {
    error_log("Found Action: $action");
    echo json_encode([
        [
            "category_id" => "2000",
            "series_id" => 5000,
            "name" => "The Beverly Hillbillies",
            "cover" => "https://resizing.flixster.com/5kjb42IRSeDBUctfkk0rL-Vkr3I=/164x246/v2/https://resizing.flixster.com/-XZAfHZM39UwaGJIFWKAE8fS0ak=/v3/t/assets/p15108_p_v10_af.jpg",
            "plot" => "A poor backwoods family strikes oil and moves to Beverly Hills."
        ],
        [
            "category_id" => "2000",
            "series_id" => 5001,
            "name" => "The Dick Van Dyke Show",
            "cover" => "https://resizing.flixster.com/UxPN62SpdQIxfQ5i1EWeyU6HeNQ=/164x246/v2/https://resizing.flixster.com/-XZAfHZM39UwaGJIFWKAE8fS0ak=/v3/t/assets/p184002_b_v11_ag.jpg",
            "plot" => "The misadventures of a TV writer both at work and at home."
        ],
        [
            "category_id" => "2000",
            "series_id" => 5003,
            "name" => "The Lucy Show",
            "cover" => "https://resizing.flixster.com/eHarJ7bCr-YokoVSeOWGcano2T4=/164x246/v2/https://resizing.flixster.com/-XZAfHZM39UwaGJIFWKAE8fS0ak=/v3/t/assets/p507901_b_v10_ad.jpg",
            "plot" => "The comic misadventures of a widow and her friend."
        ],
        [
            "category_id" => "2000",
            "series_id" => 5004,
            "name" => "Sherlock Holmes (1954)",
            "cover" => "https://marvel-b1-cdn.bc0a.com/f00000000280066/cover.hoopladigital.com/qsv_5289101_640.jpeg",
            "plot" => "The classic detective solves mysteries in Victorian London."
        ]
    ]);
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
} elseif ($action == 'get_vod_categories') {
    error_log("Found Action: $action");
    echo json_encode([
        [
            "category_id" => "3000",
            "category_name" => "Oldies",
            "parent_id" => 0
        ]
    ]);    
} elseif ($action == 'get_series_categories') {
    error_log("Found Action: $action");
    echo json_encode([
        [
            "category_id" => "2000",
            "category_name" => "Oldies",
            "parent_id" => 0
        ]
    ]);
} else {
    echo json_encode([
        "user_info" => ["username" => "demo", "status" => "Active", "exp_date" => "1999999999"],
        "server_info" => ["url" => "https://" . $_SERVER['HTTP_HOST'], "port" => "443"]
    ]);
}
?>