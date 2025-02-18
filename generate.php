<?php

$jsonUrl = "https://streamed.su/api/matches/all";
$defaultPoster = 'https://streamed.su/api/images/poster/fallback.webp';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $jsonUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0",
    "Accept: application/json",
    "Accept-Language: en-US,en;q=0.5"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$jsonData = curl_exec($ch);
if ($jsonData === false) {
    die('cURL Error: ' . curl_error($ch));
}
curl_close($ch);

$data = json_decode($jsonData, true);
if ($data === null) {
    die('Failed to decode JSON data');
}

// Function to check if an event should be skipped
function shouldSkipEvent($eventDate, $currentDateTime) {
    if ($eventDate == 0) {
        return false;
    }
    $eventDateTime = new DateTime("@".($eventDate / 1000));
    $interval = $eventDateTime->getTimestamp() - $currentDateTime->getTimestamp();
    $hoursDiff = $interval / 3600;
    return $hoursDiff < -4 || $hoursDiff > 24;
}

$currentDateTime = new DateTime("now", new DateTimeZone('Australia/Sydney'));

$m3u8Content = "#EXTM3U\n\n";

foreach ($data as $match) {
    if (shouldSkipEvent($match['date'], $currentDateTime)) {
        continue;
    }

    $poster = isset($match['poster']) ? 'https://streamed.su' . $match['poster'] : $defaultPoster;
    $category = ($match['date'] == 0) ? "24/7 Live" : $match['category'];
    $categoryFormatted = ($category === "24/7 Live") ? "24/7 Live" : ucwords(str_replace('-', ' ', $category));

    foreach ($match['sources'] as $source) {
        $sourceName = ucwords(strtolower($source['source']));
        $id = $source['id'];
        $dateTime = new DateTime("@".($match['date'] / 1000));
        $dateTime->setTimezone(new DateTimeZone('Australia/Sydney'));
        $formattedTime = $dateTime->format('h:i A');
        $formattedDate = $dateTime->format('d/m/Y');

        if ($category === "24/7 Live") {
            $m3u8Content .= "#EXTINF:-1 tvg-name=\"{$match['title']}\" tvg-logo=\"{$poster}\" group-title=\"{$categoryFormatted}\",{$match['title']}\n";
        } else {
            $matchName = "{$formattedTime} - {$match['title']} [{$sourceName}] - ({$formattedDate})";
            $m3u8Content .= "#EXTINF:-1 tvg-name=\"{$match['title']}\" tvg-logo=\"{$poster}\" group-title=\"{$categoryFormatted}\",{$matchName}\n";
        }

        // Removed the random string, now just append the URL without '?id'
        $m3u8Content .= "https://rr.vipstreams.in/{$sourceName}/js/{$id}/1/playlist.m3u8\n";
    }
}

 $items[] = [
                            'id' => $match['id'],
                            'date' => $formattedDate,
                            'time' => $match['date'],
                            'title' => $match['title'],
                            'posterImage' => $poster,
                            'url' => "https://streamed.su/watch/" . $match['id'],
                            'stream' => $streamUrl,
                            'Referer' => 'https://embedme.top/',
                            'type' => ucwords(strtolower($match['category'])),
                            'epg' => $epgId
                        ];
                    }
                }
                return $items;
            } else {
                return ["error" => "No live matches found in the JSON data."];
            }
        } else {
            return ["error" => "Failed to decode the JSON data. Error: " . json_last_error_msg()];
        }
    } else {
        return ["error" => "Could not find embedded JSON in the HTML."];
    }
}


function generateM3U8($items) {
    $m3u8 = "#EXTM3U url-tvg=\"https://raw.githubusercontent.com/dtankdempse/streamed-su-sports/main/epg.xml\"\n";
    foreach ($items as $item) {        
        $date = new DateTime("@".($item['time'] / 1000));
        $date->setTimezone(new DateTimeZone('America/New_York'));
        $formattedTime = $date->format('h:i A -');

        $m3u8 .= "#EXTINF:-1 tvg-id=\"" . $item['epg'] . "\" tvg-name=\"" . $item['title'] . "\" tvg-logo=\"" . $item['posterImage'] . "\" group-title=\"" . $item['type'] . "\",";
        $m3u8 .= $formattedTime . " " . $item['title'] . " - " . $item['date'] . "\n";
        $m3u8 .= $item['stream'] . "\n";
    }
    file_put_contents('playlist.m3u8', $m3u8);
}

function generateProxyM3U8($items) {
    $m3u8 = "#EXTM3U url-tvg=\"https://raw.githubusercontent.com/dtankdempse/streamed-su-sports/main/epg.xml\"\n";
    foreach ($items as $item) {        
        $date = new DateTime("@".($item['time'] / 1000));
        $date->setTimezone(new DateTimeZone('America/New_York'));
        $formattedTime = $date->format('h:i A -');

        $m3u8 .= "#EXTINF:-1 tvg-id=\"" . $item['epg'] . "\" tvg-name=\"" . $item['title'] . "\" tvg-logo=\"" . $item['posterImage'] . "\" group-title=\"" . $item['type'] . "\",";
        $m3u8 .= $formattedTime . " " . $item['title'] . " - " . $item['date'] . "\n";
        $m3u8 .= "https://m3u8.justchill.workers.dev?url=" . urlencode($item['stream']) . "&referer=" . $item['Referer'] . "\n";
    }
    file_put_contents('proxied_playlist.m3u8', $m3u8);
}

function generateTivimateM3U8($items) {
    $m3u8 = "#EXTM3U url-tvg=\"https://raw.githubusercontent.com/dtankdempse/streamed-su-sports/main/epg.xml\"\n";
    foreach ($items as $item) {        
        $date = new DateTime("@".($item['time'] / 1000));
        $date->setTimezone(new DateTimeZone('America/New_York'));
        $formattedTime = $date->format('h:i A -');

        $m3u8 .= "#EXTINF:-1 tvg-id=\"" . $item['epg'] . "\" tvg-name=\"" . $item['title'] . "\" tvg-logo=\"" . $item['posterImage'] . "\" group-title=\"" . $item['type'] . "\",";
        $m3u8 .= $formattedTime . " " . $item['title'] . " - " . $item['date'] . "\n";
        $m3u8 .= $item['stream'] . "|Referer=" . $item['Referer'] . "\n";
    }
    file_put_contents('tivimate_playlist.m3u8', $m3u8);
}

function generateVLC($items) {
	$vlc = "#EXTM3U url-tvg=\"https://raw.githubusercontent.com/dtankdempse/streamed-su-sports/main/epg.xml\"\n";
    foreach ($items as $item) {
        $date = new DateTime("@".($item['time'] / 1000));
        $date->setTimezone(new DateTimeZone('America/New_York'));
        $formattedTime = $date->format('h:i A -');

        $vlc .= "#EXTINF:-1 tvg-id=\"" . $item['epg'] . "\" tvg-name=\"" . $item['title'] . "\" tvg-logo=\"" . $item['posterImage'] . "\" group-title=\"" . $item['type'] . "\",";
        $vlc .= $formattedTime . " " . $item['title'] . " - " . $item['date'] . "\n";
        $vlc .= "#EXTVLCOPT:http-referrer=" . $item['Referer'] . "\n";
        $vlc .= $item['stream'] . "\n";
    }
    file_put_contents('vlc_playlist.m3u8', $vlc);
}

function generateKODIPOP($items) {
	$kodipop = "#EXTM3U url-tvg=\"https://raw.githubusercontent.com/dtankdempse/streamed-su-sports/main/epg.xml\"\n";
    foreach ($items as $item) {
        $date = new DateTime("@".($item['time'] / 1000));
        $date->setTimezone(new DateTimeZone('America/New_York'));
        $formattedTime = $date->format('h:i A -');

        $kodipop .= "#EXTINF:-1 tvg-id=\"" . $item['epg'] . "\" tvg-name=\"" . $item['title'] . "\" tvg-logo=\"" . $item['posterImage'] . "\" group-title=\"" . $item['type'] . "\",";
        $kodipop .= $formattedTime . " " . $item['title'] . " - " . $item['date'] . "\n";
        $kodipop .= "#KODIPROP:inputstream.adaptive.stream_headers=Referer=" . urlencode($item['Referer']) . "\n";
        $kodipop .= $item['stream'] . "\n";
    }
    file_put_contents('kodi_playlist.m3u8', $kodipop);
}

function generateEPG($items) {
    $epg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $epg .= '<tv>' . "\n";

    foreach ($items as $item) {
        $epg .= '  <channel id="' . $item['epg'] . '">' . "\n";
        $epg .= '    <display-name>' . htmlspecialchars($item['title'] . ' - ' . $item['date']) . '</display-name>' . "\n";
        $epg .= '    <icon src="' . htmlspecialchars($item['posterImage']) . '" />' . "\n";
        $epg .= '  </channel>' . "\n";
    }
	
	$currentTime = time() - 3600;

    foreach ($items as $item) {          
        $startTime = date('YmdHis', $currentTime) . ' +0000';
        $endTime = date('YmdHis', $currentTime + (48 * 3600)) . ' +0000';

        $date = new DateTime();
        $date->setTimestamp($item['time'] / 1000);

        $date->setTimezone(new DateTimeZone('America/Los_Angeles'));
        $ptTime = $date->format('h:i A T');

        $date->setTimezone(new DateTimeZone('America/Denver'));
        $mtTime = $date->format('h:i A T');

        $date->setTimezone(new DateTimeZone('America/New_York'));
        $etTime = $date->format('h:i A T');

        $formattedDate = $date->format('m/d/Y');
        $description = "$ptTime / $mtTime / $etTime - ($formattedDate)";

        $epg .= '  <programme start="' . $startTime . '" stop="' . $endTime . '" channel="' . $item['epg'] . '">' . "\n";
        $epg .= '    <title>' . htmlspecialchars($item['title'] . ' - ' . $item['date']) . '</title>' . "\n";
        $epg .= '    <desc>' . htmlspecialchars($description) . '</desc>' . "\n";
        $epg .= '  </programme>' . "\n";
    }

    $epg .= '</tv>';

    file_put_contents('epg.xml', $epg);
}



function fix_json($j){
  $j = trim( $j );
  $j = ltrim( $j, '(' );
  $j = rtrim( $j, ')' );
  $a = preg_split('#(?<!\\\\)\"#', $j );
  for( $i=0; $i < count( $a ); $i+=2 ){
    $s = $a[$i];
    $s = preg_replace('#([^\s\[\]\{\}\:\,]+):#', '"\1":', $s );
    $a[$i] = $s;
  }
  $j = implode( '"', $a );
  return $j;
}

function saveItemsToJson($items) {
    $jsonData = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonData === false) {
        echo "JSON encode error: " . json_last_error_msg();
        return;
    }
    $result = file_put_contents('streamed_su.json', $jsonData);
    if ($result === false) {
        echo "Failed to write to file.";
        exit;
    }
}

// Filter out events that have passed by more than 4 hours
// Sort the remaining events by time (ascending, so soonest events first)
function filterAndSortEvents($items) {
    $currentTime = time();
    $fourHoursAgo = $currentTime - (4 * 3600); // 4 hours ago in seconds
   
    $upcomingEvents = array_filter($items, function ($item) use ($fourHoursAgo) {
        return ($item['time'] / 1000) >= $fourHoursAgo;
    });

    usort($upcomingEvents, function ($a, $b) {
        return ($a['time'] - $b['time']);
    });

    return $upcomingEvents;
}


header('Content-Type: application/json');
$items = discoverListings();
if (isset($items['error']) || empty($items)) {
    echo json_encode($items); 
    exit(1);
}
$filteredSortedItems = filterAndSortEvents($items);
generateM3U8($filteredSortedItems);
generateTivimateM3U8($filteredSortedItems);
generateVLC($filteredSortedItems);
generateProxyM3U8($filteredSortedItems);
generateKODIPOP($filteredSortedItems);
generateEPG($filteredSortedItems);
saveItemsToJson($filteredSortedItems);
echo json_encode($filteredSortedItems);

?>

file_put_contents('streamed.m3u8', $m3u8Content);

echo "M3U8 file has been saved as streamed.m3u8.\n";

?>
