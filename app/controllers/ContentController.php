<?php
/**
 * Content Controller
 * Admin manages announcements, YouTube links, weather, and calendar events.
 */
class ContentController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('manage_settings');

        $db = Database::getInstance();

        // Counts
        $stats = [
            'announcements' => $db->count('announcements', '1=1'),
            'youtube'       => $db->count('youtube_links', '1=1'),
            'events'        => $db->count('events', '1=1'),
            'active_announcements' => $db->count('announcements', 'is_active = 1'),
        ];

        // Recent announcements
        $announcements = $db->fetchAll(
            "SELECT a.*, u.name AS author_name FROM announcements a
             LEFT JOIN users u ON u.id = a.created_by
             ORDER BY a.is_pinned DESC, a.created_at DESC LIMIT 10"
        );

        // YouTube links
        $youtubeLinks = $db->fetchAll(
            "SELECT y.*, u.name AS author_name FROM youtube_links y
             LEFT JOIN users u ON u.id = y.created_by
             ORDER BY y.sort_order ASC, y.created_at DESC LIMIT 20"
        );

        // Events (upcoming + this month)
        $events = $db->fetchAll(
            "SELECT e.*, u.name AS author_name FROM events e
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.event_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
             ORDER BY e.event_date ASC LIMIT 30"
        );

        // Weather cache
        $weather = $db->fetch("SELECT * FROM weather_cache ORDER BY fetched_at DESC LIMIT 1");

        $this->view('content/index', [
            'title' => 'Content Management',
            'pageTitle' => 'Content Management',
            'stats' => $stats,
            'announcements' => $announcements,
            'youtubeLinks' => $youtubeLinks,
            'events' => $events,
            'weather' => $weather,
            'csrf' => CSRF::field()
        ]);
    }

    // ============ ANNOUNCEMENTS ============

    public function storeAnnouncement()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_settings');

        $data = $this->input();
        $validator = new Validator($data);
        $validator->required('title')->required('content');

        if ($validator->fails()) {
            Flash::set('error', $validator->firstError());
            $this->redirect('/content');
        }

        Database::getInstance()->insert('announcements', [
            'title'      => $data['title'],
            'content'    => $data['content'],
            'type'       => $data['type'] ?? 'info',
            'is_pinned'  => !empty($data['is_pinned']) ? 1 : 0,
            'is_active'  => 1,
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date'   => !empty($data['end_date']) ? $data['end_date'] : null,
            'created_by' => Auth::id(),
        ]);

        Auth::audit('create', 'announcements', "Created announcement: {$data['title']}");
        Flash::set('success', 'Announcement published.');
        $this->redirect('/content');
    }

    public function updateAnnouncement($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_settings');

        $data = $this->input();
        unset($data[CSRF_TOKEN_NAME], $data['_method']);

        Database::getInstance()->update('announcements', [
            'title'      => $data['title'] ?? '',
            'content'    => $data['content'] ?? '',
            'type'       => $data['type'] ?? 'info',
            'is_pinned'  => !empty($data['is_pinned']) ? 1 : 0,
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date'   => !empty($data['end_date']) ? $data['end_date'] : null,
        ], 'id = :id', ['id' => $id]);

        Auth::audit('update', 'announcements', "Updated announcement ID {$id}");
        Flash::set('success', 'Announcement updated.');
        $this->redirect('/content');
    }

    public function deleteAnnouncement($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_settings');
        Database::getInstance()->delete('announcements', 'id = :id', ['id' => $id]);
        Auth::audit('delete', 'announcements', "Deleted announcement ID {$id}");
        Flash::set('success', 'Announcement deleted.');
        $this->redirect('/content');
    }

    // ============ YOUTUBE LINKS ============

    public function storeYoutube()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_settings');

        $data = $this->input();
        $validator = new Validator($data);
        $validator->required('title')->required('url');

        if ($validator->fails()) {
            Flash::set('error', $validator->firstError());
            $this->redirect('/content');
        }

        // Extract YouTube video ID from URL
        $videoId = $this->extractYoutubeId($data['url']);

        Database::getInstance()->insert('youtube_links', [
            'title'       => $data['title'],
            'url'         => $data['url'],
            'video_id'    => $videoId,
            'description' => $data['description'] ?? null,
            'category'    => $data['category'] ?? 'General',
            'thumbnail'   => $videoId ? "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg" : null,
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'is_active'   => 1,
            'sort_order'  => (int)($data['sort_order'] ?? 0),
            'created_by'  => Auth::id(),
        ]);

        Auth::audit('create', 'youtube_links', "Added YouTube link: {$data['title']}");
        Flash::set('success', 'YouTube video added.');
        $this->redirect('/content');
    }

    public function deleteYoutube($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_settings');
        Database::getInstance()->delete('youtube_links', 'id = :id', ['id' => $id]);
        Auth::audit('delete', 'youtube_links', "Deleted YouTube link ID {$id}");
        Flash::set('success', 'YouTube video removed.');
        $this->redirect('/content');
    }

    private function extractYoutubeId($url)
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    // ============ EVENTS ============

    public function storeEvent()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_settings');

        $data = $this->input();
        $validator = new Validator($data);
        $validator->required('title')->required('event_date');

        if ($validator->fails()) {
            Flash::set('error', $validator->firstError());
            $this->redirect('/content');
        }

        Database::getInstance()->insert('events', [
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'event_date'  => $data['event_date'],
            'end_date'    => !empty($data['end_date']) ? $data['end_date'] : null,
            'start_time'  => !empty($data['start_time']) ? $data['start_time'] : null,
            'end_time'    => !empty($data['end_time']) ? $data['end_time'] : null,
            'location'    => $data['location'] ?? null,
            'color'       => $data['color'] ?? '#6366F1',
            'type'        => $data['type'] ?? 'event',
            'is_public'   => 1,
            'created_by'  => Auth::id(),
        ]);

        Auth::audit('create', 'events', "Created event: {$data['title']} on {$data['event_date']}");
        Flash::set('success', 'Event added to calendar.');
        $this->redirect('/content');
    }

    public function deleteEvent($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_settings');
        Database::getInstance()->delete('events', 'id = :id', ['id' => $id]);
        Auth::audit('delete', 'events', "Deleted event ID {$id}");
        Flash::set('success', 'Event removed.');
        $this->redirect('/content');
    }

    // ============ WEATHER ============

    public function updateWeather()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_settings');

        $data = $this->input();
        $location = trim($data['location'] ?? '');

        if (empty($location)) {
            Flash::set('error', 'Location is required.');
            $this->redirect('/content');
        }

        // Try to fetch from Open-Meteo (free, no API key required)
        $weatherData = $this->fetchWeather($location);

        if ($weatherData === false) {
            Flash::set('error', 'Could not fetch weather. Check the location name or your internet connection.');
            $this->redirect('/content');
        }

        // Clear old cache and insert new
        Database::getInstance()->delete('weather_cache', '1=1');
        Database::getInstance()->insert('weather_cache', $weatherData);

        Auth::audit('update', 'weather_cache', "Updated weather for: {$location}");
        Flash::set('success', "Weather updated for {$weatherData['city_name']}.");
        $this->redirect('/content');
    }

    private function fetchWeather($location)
    {
        // Step 1: Geocode the location name to lat/lon using Open-Meteo geocoding
        $geoUrl = "https://geocoding-api.open-meteo.com/v1/search?name=" . urlencode($location) . "&count=1&language=en&format=json";
        $geoJson = @file_get_contents($geoUrl);
        if ($geoJson === false) return false;

        $geoData = json_decode($geoJson, true);
        if (empty($geoData['results'][0])) return false;

        $geo = $geoData['results'][0];
        $lat = $geo['latitude'];
        $lon = $geo['longitude'];
        $cityName = $geo['name'];
        $country = $geo['country'] ?? '';

        // Step 2: Get weather from Open-Meteo
        $weatherUrl = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current=temperature_2m,relative_humidity_2m,apparent_temperature,wind_speed_10m,weather_code&timezone=auto";
        $weatherJson = @file_get_contents($weatherUrl);
        if ($weatherJson === false) return false;

        $w = json_decode($weatherJson, true);
        if (empty($w['current'])) return false;

        $current = $w['current'];
        $weatherCode = $current['weather_code'] ?? 0;

        // Map weather code to description + emoji icon
        $weatherMap = $this->getWeatherCodeMap($weatherCode);

        return [
            'location'    => $location,
            'temperature' => $current['temperature_2m'] ?? null,
            'feels_like'  => $current['apparent_temperature'] ?? null,
            'humidity'    => $current['relative_humidity_2m'] ?? null,
            'wind_speed'  => $current['wind_speed_10m'] ?? null,
            'description' => $weatherMap['description'],
            'icon'        => $weatherMap['icon'],
            'city_name'   => $cityName,
            'country'     => $country,
            'raw_data'    => $weatherJson,
            'fetched_at'  => date('Y-m-d H:i:s'),
        ];
    }

    private function getWeatherCodeMap($code)
    {
        $map = [
            0   => ['description' => 'Clear sky', 'icon' => 'sun'],
            1   => ['description' => 'Mainly clear', 'icon' => 'sun'],
            2   => ['description' => 'Partly cloudy', 'icon' => 'cloud-sun'],
            3   => ['description' => 'Overcast', 'icon' => 'cloud'],
            45  => ['description' => 'Foggy', 'icon' => 'cloud-fog'],
            48  => ['description' => 'Depositing rime fog', 'icon' => 'cloud-fog'],
            51  => ['description' => 'Light drizzle', 'icon' => 'cloud-drizzle'],
            53  => ['description' => 'Moderate drizzle', 'icon' => 'cloud-drizzle'],
            55  => ['description' => 'Dense drizzle', 'icon' => 'cloud-drizzle'],
            61  => ['description' => 'Slight rain', 'icon' => 'cloud-rain'],
            63  => ['description' => 'Moderate rain', 'icon' => 'cloud-rain'],
            65  => ['description' => 'Heavy rain', 'icon' => 'cloud-rain-heavy'],
            71  => ['description' => 'Slight snow', 'icon' => 'cloud-snow'],
            73  => ['description' => 'Moderate snow', 'icon' => 'cloud-snow'],
            75  => ['description' => 'Heavy snow', 'icon' => 'cloud-snow'],
            77  => ['description' => 'Snow grains', 'icon' => 'cloud-snow'],
            80  => ['description' => 'Rain showers', 'icon' => 'cloud-rain'],
            81  => ['description' => 'Heavy rain showers', 'icon' => 'cloud-rain-heavy'],
            82  => ['description' => 'Violent rain showers', 'icon' => 'cloud-rain-heavy'],
            85  => ['description' => 'Snow showers', 'icon' => 'cloud-snow'],
            86  => ['description' => 'Heavy snow showers', 'icon' => 'cloud-snow'],
            95  => ['description' => 'Thunderstorm', 'icon' => 'cloud-lightning'],
            96  => ['description' => 'Thunderstorm with hail', 'icon' => 'cloud-lightning'],
            99  => ['description' => 'Severe thunderstorm', 'icon' => 'cloud-lightning'],
        ];
        return $map[$code] ?? ['description' => 'Unknown', 'icon' => 'cloud'];
    }
}
