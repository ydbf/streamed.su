<?php

// Base64 encoded URL
$encodedUrl = "aHR0cHM6Ly93ZWJ2aWV3ZXIuaGFyZHdhcmUwODgwLndvcmtlcnMuZGV2Lz90YXJnZXQ9aHR0cHM6Ly9zdHJlYW1lZC5zdS9hcGkvbWF0Y2hlcy9hbGw=";

// Decode the Base64 string to get the actual URL
$jsonUrl = base64_decode($encodedUrl);

// Default poster image URL
$defaultPoster = 'https://streamed.su/api/images/poster/fallback.webp';

// Initialize cURL to fetch JSON data
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $jsonUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0",
    "Accept: application/json",
    "Accept-Language: en-US,en;q=0.5"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute cURL and fetch data
$jsonData = curl_exec($ch);
if ($jsonData === false) {
    die('cURL Error: ' . curl_error($ch));
}
curl_close($ch);

// Decode JSON data
$data = json_decode($jsonData, true);
if ($data === null) {
    die('Failed to decode JSON data');
}

// Initialize M3U8 content with a blank #EXTM3U entry
$m3u8Content = "#EXTM3U\n\n";  // Blank #EXTM3U entry at the top

// Process each match to generate M3U8 entries
foreach ($data as $match) {
    // Determine poster image URL (use fallback if not available)
    $poster = isset($match['poster']) ? 'https://streamed.su' . $match['poster'] : $defaultPoster;

    // Set category to "24/7 Live" if the date is 0
    $category = ($match['date'] == 0) ? "24/7 Live" : $match['category'];

    // Format category name (capitalize each word and replace hyphens with spaces)
    $categoryFormatted = ($category === "24/7 Live") ? "24/7 Live" : ucwords(str_replace('-', ' ', $category));

    // Process each source for the match
    foreach ($match['sources'] as $source) {
        // Preserve the source name in its original case
        $sourceName = $source['source']; 
        $id = $source['id'];

        // Convert the date from Unix timestamp to Australia/Sydney time
        $dateTime = new DateTime("@".($match['date'] / 1000));
        $dateTime->setTimezone(new DateTimeZone('Australia/Sydney'));
        $formattedTime = $dateTime->format('h:i A');  // Time formatted with AM/PM
        $formattedDate = $dateTime->format('d/m/Y');  // Date in DD/MM/YYYY format

        // If category is "24/7 Live", exclude time and date, only show match name
        if ($category === "24/7 Live") {
            $m3u8Content .= "#EXTINF:-1 tvg-name=\"{$match['title']}\" tvg-logo=\"{$poster}\" group-title=\"{$categoryFormatted}\",{$match['title']}\n";
        } else {
            // Format the match name with the new scheme
            $matchName = "{$formattedTime} - {$match['title']} [{$sourceName}] - ({$formattedDate})";
            $m3u8Content .= "#EXTINF:-1 tvg-name=\"{$match['title']}\" tvg-logo=\"{$poster}\" group-title=\"{$categoryFormatted}\",{$matchName}\n";
        }

        // Add the URL for the stream, ensuring source name is in the original case
        $m3u8Content .= "https://rr.vipstreams.in/{$sourceName}/js/{$id}/1/playlist.m3u8\n";
    }
}

// Save the generated M3U8 content to a file
file_put_contents('streamed.m3u8', $m3u8Content);

echo "M3U8 file has been saved as streamed.m3u8.\n";

?>
