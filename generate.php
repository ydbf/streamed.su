<?php

$encodedUrl = "aHR0cHM6Ly9zdHJlYW1lZC5zdS9hcGkvbWF0Y2hlcy9hbGw=";
$jsonUrl = base64_decode($encodedUrl);
$defaultPoster = 'https://streamed.su/api/images/poster/fallback.webp';

// Fetch JSON data
$ch = curl_init($jsonUrl);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0",
        "Accept: application/json",
        "Accept-Language: en-US,en;q=0.5"
    ],
    CURLOPT_RETURNTRANSFER => true,
]);

$jsonData = curl_exec($ch);
if ($jsonData === false) {
    die('cURL Error: ' . curl_error($ch));
}
curl_close($ch);

$data = json_decode($jsonData, true);
if ($data === null) {
    die('Failed to decode JSON data');
}

// Check if an event should be skipped
function shouldSkipEvent($eventDate, DateTime $currentDateTime) {
    if ($eventDate == 0) return false;

    $eventDateTime = new DateTime("@".($eventDate / 1000));
    $hoursDiff = ($eventDateTime->getTimestamp() - $currentDateTime->getTimestamp()) / 3600;

    return $hoursDiff < -4 || $hoursDiff > 24;
}

$currentDateTime = new DateTime("now", new DateTimeZone('Australia/Sydney'));
$m3u8Content = "#EXTM3U\n\n";

foreach ($data as $match) {
    if (shouldSkipEvent($match['date'], $currentDateTime)) continue;

    $poster = !empty($match['poster']) ? 'https://streamed.su' . $match['poster'] : $defaultPoster;
    $category = $match['date'] == 0 ? "24/7 Live" : ucwords(str_replace('-', ' ', $match['category']));

    foreach ($match['sources'] as $source) {
        $sourceName = ucwords(strtolower($source['source']));
        $id = $source['id'];

        if ($category === "24/7 Live") {
            $m3u8Content .= "#EXTINF:-1 tvg-name=\"{$match['title']}\" tvg-logo=\"{$poster}\" group-title=\"{$category}\",{$match['title']}\n";
        } else {
            $dateTime = new DateTime("@".($match['date'] / 1000), new DateTimeZone('Australia/Sydney'));
            $formattedTime = $dateTime->format('h:i A');
            $formattedDate = $dateTime->format('d/m/Y');
            $matchName = "{$formattedTime} - {$match['title']} [{$sourceName}] - ({$formattedDate})";

            $m3u8Content .= "#EXTINF:-1 tvg-name=\"{$match['title']}\" tvg-logo=\"{$poster}\" group-title=\"{$category}\",{$matchName}\n";
        }

        $m3u8Content .= "https://rr.vipstreams.in/{$sourceName}/js/{$id}/1/playlist.m3u8\n";
    }
}

file_put_contents('streamed.m3u8', $m3u8Content);

echo "M3U8 file has been saved as streamed.m3u8.\n";

?>
