<?php
// Set the content type to JSON, as this file is now an API
header('Content-Type: application/json');

// This script will check the local dictionary first,
// and only if a term is not found, it will check the public API.

// --- 1. Get and sanitize the term from the POST request ---
$raw_term = $_POST['term'] ?? '';
$sanitized_term_key = strtolower(trim($raw_term)); // For local dictionary lookup
$sanitized_term_url = urlencode(trim($raw_term)); // For API lookup

if (empty($raw_term)) {
    echo json_encode([
        'status' => 'error',
        'term' => 'Empty', 
        'definition' => 'No search term was provided.'
    ]);
    exit;
}

// --- 2. Local GIS Dictionary (Our Primary Source) ---
$local_definitions = [
    'gis' => 'A Geographic Information System (GIS) is a system designed to capture, store, manipulate, analyze, manage, and present all types of geographical data.',
    'raster' => 'Raster data is a model that represents spatial data as a grid of cells (pixels). Each cell in the grid has a value representing information, such as temperature, elevation, or land cover. Common formats include JPEG and TIFF.',
    'vector' => 'Vector data is a model that represents spatial data using points, lines (polylines), and polygons. Points represent single locations, lines represent linear features (like rivers or roads), and polygons represent areas (like lakes or city boundaries).',
    'geocoding' => 'Geocoding is the process of converting an address or place name (e.g., "1600 Amphitheatre Parkway, Mountain View, CA") into geographic coordinates (e.g., latitude 37.422, longitude -122.084).',
    'projection' => 'A map projection is a method of representing the curved surface of the Earth (or other 3D body) on a flat 2D surface (a map). All projections introduce some distortion to properties like area, shape, direction, or distance.',
    'lidar' => 'LiDAR (Light Detection and Ranging) is a remote sensing method that uses light in the form of a pulsed laser to measure variable distances to the Earth. These light pulses—combined with other data recorded by the airborne system— generate precise, three-dimensional information about the shape of the Earth and its surface characteristics.',
    'remote sensing' => 'Remote sensing is the science of obtaining information about objects or areas from a distance, typically from aircraft or satellites. It uses sensors to detect energy (like light or radar) reflected or emitted from the Earth\'s surface.',
    'attribute' => 'In GIS, an attribute is non-spatial information associated with a spatial feature. For example, a vector polygon representing a city might have attributes like "Population", "City_Name", and "Mayor". This data is typically stored in a table (an attribute table).',
    'shapefile' => 'A shapefile (.shp) is a popular vector data format developed by Esri. It is not a single file, but a collection of multiple files (e.g., .shp, .shx, .dbf) that must be kept together in the same directory to function.',
    'dem' => 'A Digital Elevation Model (DEM) is a raster-based representation of a terrain\'s surface, specifically its elevation. Each pixel in the raster grid holds a value representing the elevation at that location. DEMs are commonly used for terrain analysis, hydrology, and 3D visualization.',
    'sql' => 'SQL (Structured Query Language) is a standard language for managing and querying data in relational databases. In GIS, it is used to select, filter, and analyze data stored in spatial databases (like PostGIS).',
    'metadata' => 'Metadata is "data about data." In GIS, it provides crucial information about a dataset, such as its creator, date of creation, coordinate system, accuracy, and attribute descriptions. It is essential for understanding and using data correctly.',
    'gps' => 'The Global Positioning System (GPS) is a U.S.-owned satellite-based navigation system. It provides geolocation and time information to a GPS receiver anywhere on or near the Earth where there is an unobstructed line of sight to four or more GPS satellites.',
    'topology' => 'Topology describes the spatial relationships between features (points, lines, polygons) that do not change under continuous transformation (like stretching or bending). It defines rules like "polygons must not overlap" or "lines must connect at nodes," which are essential for data integrity and network analysis.',
    'geomatics' => 'Geomatics is the science and technology of geographically referencing information. It is a broader field that includes GIS, remote sensing, GPS, surveying, and cartography.',
    'python' => 'Python is a high-level, interpreted programming language widely used in GIS for scripting, automation, data analysis, and developing custom tools. Libraries like ArcPy (for ArcGIS) and PyQGIS (for QGIS) allow users to automate complex workflows.',
    'machine learning' => 'Machine Learning (ML) is a subset of Artificial Intelligence (AI) where algorithms are trained on data to find patterns and make predictions. In GIS, it is used for tasks like image classification (e.g., identifying land cover from satellite imagery), feature extraction, and predictive modeling.',
    'vancouver' => 'Vancouver is a major city in western Canada, located in the Lower Mainland of British Columbia. It is known for its high-density urban center, surrounding natural beauty (mountains and ocean), and as a major hub for tech and film.',
    'database' => 'A database is an organized collection of structured information, or data, typically stored electronically in a computer system. A spatial database (like PostGIS or an Esri Geodatabase) is optimized to store and query data that represents geographic objects.',
    'json' => 'JSON (JavaScript Object Notation) is a lightweight, text-based data-interchange format. It is easy for humans to read and write and easy for machines to parse and generate. GeoJSON is a popular standard format based on JSON for encoding geographic vector data.',
    'api' => 'An API (Application Programming Interface) is a set of rules and protocols that allows different software applications to communicate with each other. In web GIS, APIs (like the Google Maps API or Leaflet) are used to embed maps and access geospatial data and services.',
    'cartography' => 'Cartography is the art, science, and technology of making maps. It involves everything from data collection and design (symbology, layout, typography) to the final production and understanding of maps.',
    'photogrammetry' => 'Photogrammetry is the science of making measurements from photographs. In GIS, it is commonly used to create 3D models, digital elevation models (DEMs), and orthomosaics (geometrically corrected aerial images) from overlapping photos, often captured by drones or satellites.',
    'javascript' => 'JavaScript (JS) is a high-level programming language that is one of the core technologies of the World Wide Web. In web GIS, it is the primary language used to create interactive maps in the browser, using libraries like Leaflet, OpenLayers, and the ArcGIS API for JavaScript.',
    'html' => 'HTML (HyperText Markup Language) is the standard markup language for documents designed to be displayed in a web browser. It forms the basic structure and content of all web pages.',
    'css' => 'CSS (Cascading Style Sheets) is a style sheet language used for describing the presentation and design of a document written in HTML. It controls the layout, colors, fonts, and overall visual appearance of a web page.',
    'geospatial' => 'Geospatial is an adjective used to describe data or technology that has a geographic component. It implies that the data is referenced to a specific location on, above, or below the Earth\'s surface.',
    'kriging' => 'Kriging is an advanced geostatistical interpolation method. It uses the spatial correlation between measured points (defined by a variogram) to estimate values at unmeasured locations. It is considered a very accurate interpolation technique because it also provides a measure of prediction error.',
    'interpolation' => 'Spatial interpolation is the process of estimating values at unmeasured locations based on a set of known sample points. Common methods include Inverse Distance Weighting (IDW), Spline, and Kriging. It is often used to create a continuous raster surface (e.s., a temperature map) from scattered weather station data.',
    'buffer' => 'A buffer is a polygon (an area) created around a GIS feature (a point, line, or polygon) at a specified distance. It is one of the most common proximity analysis tools, used to answer questions like "What properties are within 100 meters of this river?"',
    'symbology' => 'Symbology refers to the use of symbols (color, shape, size, texture) to represent geographic features on a map. Good symbology is essential for clear communication and effective cartography.',
    'qgis' => 'QGIS (formerly Quantum GIS) is a free and open-source (FOSS) desktop GIS application. It is a powerful, user-friendly, and widely used alternative to proprietary software like ArcGIS.',
    'arcgis' => 'ArcGIS is a family of client, server, and online GIS software developed and maintained by Esri. ArcGIS Pro is its flagship desktop application, a widely used industry-standard proprietary software.',
    'geojson' => 'GeoJSON is an open-standard format for encoding a variety of geographic data structures (points, lines, polygons) using JavaScript Object Notation (JSON). It is lightweight, human-readable, and the most common format for web mapping.',
	'artificial intelligence' => 'AI (Artificial Intelligence) is a wide-ranging branch of computer science concerned with building smart machines capable of performing tasks that typically require human intelligence. In GIS, AI (especially Machine Learning) is used for feature extraction from imagery, spatial pattern detection, and optimization of logistics and routing.',
    'wms' => 'WMS (Web Map Service) is an OGC (Open Geospatial Consortium) standard protocol for serving georeferenced map *images* over the internet. A WMS server generates an image (like a PNG or JPEG) based on a client\'s request for a specific area and style.',
    'wfs' => 'WFS (Web Feature Service) is an OGC (Open Geospatial Consortium) standard protocol for serving vector *data* over the internet. Unlike a WMS (which sends an image), a WFS sends the actual feature geometry (points, lines, polygons) and attributes, allowing the client to style and analyze the data.',
    'cloud computing' => 'Cloud computing is the on-demand delivery of IT resources (like servers, storage, and software) over the internet with pay-as-you-go pricing. In GIS, this includes platforms like ArcGIS Online, Google Earth Engine, and running GIS servers on platforms like AWS or Azure.',
    'data science' => 'Data Science is an interdisciplinary field that uses scientific methods, processes, algorithms, and systems to extract knowledge and insights from structured and unstructured data. Spatial Data Science applies these techniques to geographic data to uncover spatial patterns and build predictive models.',
    'ai' => 'AI (Artificial Intelligence) is a wide-ranging branch of computer science concerned with building smart machines capable of performing tasks that typically require human intelligence. In GIS, AI (especially Machine Learning) is used for feature extraction from imagery, spatial pattern detection, and optimization of logistics and routing.',
    'blockchain' => 'Blockchain is a decentralized, distributed, and immutable digital ledger used to record transactions. Its application in GIS and spatial data is an emerging field, primarily explored for securely managing land records and tracking supply chains.',
    'datum' => 'A datum (or geodetic datum) is a coordinate system and a set of reference points used to locate places on the Earth. It defines the size and shape of the Earth (the ellipsoid) and the origin and orientation of the coordinate system. Examples include NAD83 (North American Datum 1983) and WGS84 (World Geodetic System 1984).',
    'ellipsoid' => 'An ellipsoid (or spheroid) is a 3D mathematical shape that approximates the shape of the Earth. Because the Earth is not a perfect sphere (it bulges at the equator), an ellipsoid provides a more accurate model for geodetic calculations. It is a key component of a datum.',
    'tin' => 'A TIN (Triangulated Irregular Network) is a vector-based model for representing a 3D surface. It is composed of a network of non-overlapping triangles created by connecting sample points (nodes). The vertices of the triangles represent the measured elevation points, making it efficient for storing terrain data where elevation changes abruptly (like cliffs or ridges).',
    'urban planning' => 'Urban planning is a technical and political process concerned with the development and design of land use and the built environment. GIS is a fundamental tool for planners, used for zoning analysis, transportation modeling, demographic studies, and visualizing proposed developments.',
    'forestry' => 'Forestry is the science and craft of creating, managing, using, conserving, and repairing forests and associated resources. GIS is used extensively for mapping forest stands, planning timber harvests, modeling fire risk, and monitoring forest health with remote sensing.',
    'geology' => 'Geology is the science that deals with the Earth\'s physical structure and substance, its history, and the processes which act on it. GIS is used by geologists to create geologic maps, analyze seismic data, model subsurface structures, and assess mineral and petroleum resources.',
    'climate change' => 'Climate change refers to long-term shifts in temperatures and weather patterns. GIS and remote sensing are essential tools for monitoring climate change, for example, by mapping melting glaciers, rising sea levels, changes in vegetation, and modeling future climate scenarios.',
    'sustainability' => 'Sustainability focuses on meeting the needs of the present without compromising the ability of future generations to meet their needs. GIS helps in sustainability planning by modeling environmental impacts, analyzing renewable energy potential, and managing natural resources.',
    'photosynthesis' => 'Photosynthesis is the process used by plants, algae, and some bacteria to convert light energy into chemical energy, through a process that converts carbon dioxide and water into sugars (food) and oxygen. In GIS, remote sensing indices like NDVI (Normalized Difference Vegetation Index) are used to measure vegetation health, which is a proxy for photosynthetic activity.'
];
// --- End of Local Dictionary ---


// --- 3. Check Local Dictionary FIRST ---
$definition = null;
$source = null;

if (isset($local_definitions[$sanitized_term_key])) {
    // Term was found in our local dictionary
    $definition = $local_definitions[$sanitized_term_key];
    $source = "Local GIS Dictionary";
} else {
    // --- 4. If not found, try fetching from the public dictionary API ---
    $api_url = "https://api.dictionaryapi.dev/api/v2/entries/en/" . $sanitized_term_url;

    // Use file_get_contents with a stream context to handle errors gracefully
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Esha-Karim-BCIT-Project/1.0\r\n',
            'ignore_errors' => true // This is key to reading the 404 response instead of just failing
        ]
    ];
    $context = stream_context_create($options);
    $response_json = @file_get_contents($api_url, false, $context);

    if ($response_json !== false) {
        $response_data = json_decode($response_json, true);
        
        // Check if the API returned a valid definition
        if (is_array($response_data) && !isset($response_data['title'])) {
            // Success! Extract the first definition.
            $definition = $response_data[0]['meanings'][0]['definitions'][0]['definition'] ?? null;
            $source = "Public Dictionary API";
        }
    }
    // If $definition is still null, it means neither the local dict nor the public API found it.
}

// --- 5. Prepare and send the final JSON response ---
if ($definition !== null) {
    // Found a definition
    echo json_encode([
        'status' => 'success',
        'term' => $raw_term,
        'definition' => $definition,
        'source' => $source
    ]);
} else {
    // Not found anywhere
    echo json_encode([
        'status' => 'error',
        'term' => $raw_term,
        'definition' => "Sorry, no definition was found for \"" . htmlspecialchars($raw_term) . "\" in the public dictionary or our local GIS dictionary.",
        'source' => "PHP Dictionary Search"
    ]);
}

?>

