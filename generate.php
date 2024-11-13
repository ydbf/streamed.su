<?php

// URL to fetch the JSON data
$jsonUrl = 'https://streamed.su/api/matches/all';

// Default poster image URL
$defaultPoster = 'https://streamed.su/api/images/poster/fallback.webp';

// Fetch the JSON data from the URL
$jsonData = file_get_contents($jsonUrl);
$data = json_decode($jsonData, true);

// Check if JSON decoding is successful
if ($data === null) {
    die('Failed to decode JSON data');
}

// Initialize the M3U8 content with a blank #EXTM3U entry at the top
$m3u8Content = "#EXTM3U\n\n";  // Blank #EXTM3U entry on the first line

// Process each match to generate the M3U8 entries
foreach ($data as $match) {
    // Check if the poster URL exists, otherwise use the fallback
    $poster = isset($match['poster']) ? 'https://streamed.su' . $match['poster'] : $defaultPoster;
    
    foreach ($match['sources'] as $source) {
        $sourceName = strtoupper($source['source']);
        $id = $source['id'];
        
        // Convert the date from Unix timestamp (milliseconds) to Australia/Sydney time
        $dateTime = new DateTime("@".($match['date'] / 1000));
        $dateTime->setTimezone(new DateTimeZone('Australia/Sydney'));
        $formattedTime = $dateTime->format('h:i A');
        $formattedDate = $dateTime->format('m/d/Y');
        
        // Format the M3U8 entry with square brackets around the source name
        $m3u8Content .= "#EXTINF:-1 tvg-name=\"{$match['title']}\" tvg-logo=\"{$poster}\" group-title=\"{$match['category']}\",[{$sourceName}] {$match['title']} - {$formattedTime} AEST - ({$formattedDate})\n";
        $m3u8Content .= "https://rr.vipstreams.in/{$sourceName}/js/{$id}/1/playlist.m3u8\n";
    }
}

// Save the M3U8 content to a file named streamed.m3u8
file_put_contents('streamed.m3u8', $m3u8Content);

echo "M3U8 file has been saved as streamed.m3u8.\n";

?>
