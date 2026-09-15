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
        $m3u8Content .= "https://rr.buytommy.top/secure/GrYrzMUODC/{$sourceName}/stream/{$id}/1/playlist.m3u8\n";
    }
}

file_put_contents('streamed.m3u8', $m3u8Content);

echo "M3U8 file has been saved as streamed.m3u8.\n";

?>
