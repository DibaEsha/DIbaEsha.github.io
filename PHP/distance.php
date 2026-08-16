<?php
// --- CONFIGURATION ---
// Turn OFF display_errors to prevent PHP warnings from breaking the JSON response
ini_set('display_errors', 0); 
error_reporting(E_ALL);

// Define the User Agent clearly for the API (Required by Nominatim)
define('USER_AGENT', 'EshaKarim_BCIT_Project/1.0 (ekarim4@my.bcit.ca)');

// Path to CSV
$csvFile = '../data/distances.csv';

// --- ROUTING ---
$action = $_GET['action'] ?? '';
if (empty($action) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
}

// Set header to JSON by default unless asking for the table
if ($action !== 'get_table') {
    header('Content-Type: application/json');
}

switch ($action) {
    case 'autocomplete': 
        handle_autocomplete(); 
        break;
    case 'submit_form': 
        handle_form_submission(); 
        break;
    case 'get_table': 
        handle_get_table(); 
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'No valid action specified.']);
}
exit;

// --- FUNCTIONS ---

function fetchUrl($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, USER_AGENT);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    } else {
        $options = [
            'http' => [
                'method' => "GET",
                'header' => "User-Agent: " . USER_AGENT . "\r\n"
            ]
        ];
        $context = stream_context_create($options);
        return @file_get_contents($url, false, $context);
    }
}

function handle_autocomplete() {
    $query = $_GET['query'] ?? '';
    if (strlen($query) < 3) { echo json_encode([]); return; }

    $url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($query) . '&format=json&limit=5';
    
    $json = fetchUrl($url);
    
    $results = [];
    if ($json) {
        $data = json_decode($json, true);
        if (is_array($data)) {
            foreach ($data as $item) {
                $results[] = $item['display_name'];
            }
        }
    }
    echo json_encode($results);
}

function handle_form_submission() {
    global $csvFile;

    $place1Name = trim($_POST['place1'] ?? '');
    $place2Name = trim($_POST['place2'] ?? '');
    
    // Check for explicit coordinates from the map (Hidden Inputs)
    $lat1 = $_POST['lat1'] ?? null;
    $lon1 = $_POST['lon1'] ?? null;
    $lat2 = $_POST['lat2'] ?? null;
    $lon2 = $_POST['lon2'] ?? null;

    if (empty($place1Name) || empty($place2Name)) {
        echo json_encode(['status' => 'error', 'message' => 'Please select both locations.']);
        return;
    }

    $geo1 = false;
    $geo2 = false;

    // Use explicit coords if available (Map was used), otherwise Geocode the name (Text input was used)
    if ($lat1 && $lon1) {
        $geo1 = ['lat' => $lat1, 'lon' => $lon1];
    } else {
        $geo1 = geocode($place1Name);
    }

    if ($lat2 && $lon2) {
        $geo2 = ['lat' => $lat2, 'lon' => $lon2];
    } else {
        $geo2 = geocode($place2Name);
    }

    if ($geo1 && $geo2) {
        $distance = getDistance($geo1['lat'], $geo1['lon'], $geo2['lat'], $geo2['lon']);
        $distanceRounded = round($distance, 2);
        
        // Ensure data directory exists
        $dir = dirname($csvFile);
        if (!is_dir($dir)) { mkdir($dir, 0755, true); }

        $fileHandle = fopen($csvFile, 'a');
        if (filesize($csvFile) == 0) {
            fputcsv($fileHandle, ['Timestamp', 'Origin', 'Lat1', 'Lon1', 'Destination', 'Lat2', 'Lon2', 'Distance (km)']);
        }
        
        date_default_timezone_set('America/Vancouver');
        fputcsv($fileHandle, [
            date('Y-m-d H:i:s'), 
            $place1Name, $geo1['lat'], $geo1['lon'], 
            $place2Name, $geo2['lat'], $geo2['lon'], 
            $distanceRounded
        ]);
        fclose($fileHandle);
        
        echo json_encode(['status' => 'success', 'message' => "Calculated Distance: $distanceRounded km"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Could not find coordinates for one or both locations.']);
    }
}

function handle_get_table() {
    header('Content-Type: text/html');
    global $csvFile;
    
    if (!file_exists($csvFile) || filesize($csvFile) == 0) {
        echo '<p class="text-center text-muted">No calculations recorded yet.</p>';
        return;
    }
    
    $html = '<div class="table-responsive"><table class="table table-hover align-middle shadow-sm" style="border-radius: 10px; overflow: hidden;">';
    $fileHandle = fopen($csvFile, 'r');
    
    if ($fileHandle !== FALSE) {
        $header = fgetcsv($fileHandle);
        $html .= '<thead class="table-dark"><tr>';
        foreach($header as $h) {
            $html .= "<th>" . htmlspecialchars($h) . "</th>";
        }
        $html .= '</tr></thead><tbody>';
        
        $rows = [];
        while (($row = fgetcsv($fileHandle)) !== FALSE) {
            $rows[] = $row;
        }
        fclose($fileHandle);
        
        // Show newest first
        $rows = array_reverse($rows);
        
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= "<td>" . htmlspecialchars($cell) . "</td>";
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        echo $html;
    }
}

function geocode($placeName) {
    $url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($placeName) . '&format=json&limit=1';
    $json = fetchUrl($url);
    $data = json_decode($json, true);
    
    if (!empty($data) && isset($data[0])) {
        return ['lat' => $data[0]['lat'], 'lon' => $data[0]['lon']];
    }
    return false;
}

function getDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; 
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}
?>