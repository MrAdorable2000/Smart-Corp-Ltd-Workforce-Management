<?php
/**
 * Home Controller
 * Public-facing home page with user directory, analytics, and registration
 */
class HomeController extends Controller
{
    /**
     * Public home page - shows analytics + user directory + login/register buttons
     */
    public function index()
    {
        $db = Database::getInstance();

        // Public analytics
        $totalEmployees = $db->count('employees', 'is_active = 1');
        $totalUsers = $db->count('users', "status = 'active'");
        $totalDepartments = $db->count('departments', 'is_active = 1');
        $totalPresentToday = $db->count('attendance',
            'attendance_date = CURDATE() AND status IN ("present","late")');

        // Get all system users with employee data (public directory)
        $users = $db->fetchAll(
            "SELECT u.id, u.name, u.email, u.phone, u.avatar, u.status,
                    r.name AS role_name, r.slug AS role_slug,
                    e.employee_code, e.position, e.photo,
                    d.name AS department_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN employees e ON e.id = u.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE u.status = 'active'
             ORDER BY
                CASE r.slug
                    WHEN 'super_admin' THEN 1
                    WHEN 'hr_manager' THEN 2
                    WHEN 'department_manager' THEN 3
                    WHEN 'employee' THEN 4
                    WHEN 'auditor' THEN 5
                    ELSE 6
                END,
                u.name ASC"
        );

        // Role distribution for analytics
        $roleDistribution = $db->fetchAll(
            "SELECT r.name, r.slug, COUNT(u.id) as count
             FROM roles r
             LEFT JOIN users u ON u.role_id = r.id AND u.status = 'active'
             GROUP BY r.id, r.name, r.slug
             ORDER BY count DESC"
        );

        // Department distribution
        $deptDistribution = $db->fetchAll(
            "SELECT d.name, COUNT(e.id) as count
             FROM departments d
             LEFT JOIN employees e ON e.department_id = d.id AND e.is_active = 1
             WHERE d.is_active = 1
             GROUP BY d.id, d.name
             ORDER BY count DESC"
        );

        // Company info
        $company = $db->fetch("SELECT * FROM companies WHERE id = 1");

        // ============ NEW: Dynamic content ============

        // Active announcements (sorted: pinned first, then newest)
        $announcements = $this->safeFetchAll(
            "SELECT a.*, u.name AS author_name FROM announcements a
             LEFT JOIN users u ON u.id = a.created_by
             WHERE a.is_active = 1
               AND (a.start_date IS NULL OR a.start_date <= NOW())
               AND (a.end_date IS NULL OR a.end_date >= NOW())
             ORDER BY a.is_pinned DESC, a.created_at DESC
             LIMIT 5"
        );

        // Featured + active YouTube videos
        $youtubeVideos = $this->safeFetchAll(
            "SELECT * FROM youtube_links
             WHERE is_active = 1
             ORDER BY is_featured DESC, sort_order ASC, created_at DESC
             LIMIT 6"
        );

        // Weather cache — auto-refresh if older than 30 minutes
        $weather = $this->safeFetch("SELECT * FROM weather_cache ORDER BY fetched_at DESC LIMIT 1");

        // Auto-refresh weather if cache is stale (> 30 min) or empty
        $needRefresh = false;
        if (!$weather) {
            $needRefresh = true;
        } else {
            $fetchedTs = strtotime($weather['fetched_at']);
            if ($fetchedTs === false || (time() - $fetchedTs) > 1800) {
                $needRefresh = true;
            }
        }
        if ($needRefresh && !empty($weather['location'])) {
            // Auto-fetch using the previously configured location
            $this->autoFetchWeather($weather['location']);
            $weather = $this->safeFetch("SELECT * FROM weather_cache ORDER BY fetched_at DESC LIMIT 1");
        }

        // Upcoming events (for calendar + upcoming list)
        $events = $this->safeFetchAll(
            "SELECT * FROM events
             WHERE is_public = 1
               AND event_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
             ORDER BY event_date ASC
             LIMIT 20"
        );

        // Today's events (highlight on calendar)
        $todayEvents = $this->safeFetchAll(
            "SELECT * FROM events
             WHERE is_public = 1
               AND event_date = CURDATE()
             ORDER BY start_time ASC"
        );

        $this->layout = 'public';
        $this->view('home/index', [
            'title' => 'Smart Employee Attendance & Workforce Management',
            'users' => $users,
            'stats' => [
                'employees' => $totalEmployees,
                'users' => $totalUsers,
                'departments' => $totalDepartments,
                'present_today' => $totalPresentToday,
            ],
            'roleDistribution' => $roleDistribution,
            'deptDistribution' => $deptDistribution,
            'company' => $company,
            'announcements' => $announcements,
            'youtubeVideos' => $youtubeVideos,
            'weather' => $weather,
            'events' => $events,
            'todayEvents' => $todayEvents,
        ]);
    }

    /**
     * Safely fetch all (returns empty array if table doesn't exist)
     */
    private function safeFetchAll($sql, $params = [])
    {
        try {
            return Database::getInstance()->fetchAll($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Safely fetch single row
     */
    private function safeFetch($sql, $params = [])
    {
        try {
            return Database::getInstance()->fetch($sql, $params);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Auto-fetch weather from Open-Meteo API (no API key needed)
     * Called automatically when cache is older than 30 minutes
     */
    private function autoFetchWeather($location)
    {
        if (empty($location)) return;

        // Step 1: Geocode location name to lat/lon
        $geoUrl = "https://geocoding-api.open-meteo.com/v1/search?name=" . urlencode($location) . "&count=1&language=en&format=json";
        $geoJson = @file_get_contents($geoUrl);
        if ($geoJson === false) return;

        $geoData = json_decode($geoJson, true);
        if (empty($geoData['results'][0])) return;

        $geo = $geoData['results'][0];
        $lat = $geo['latitude'];
        $lon = $geo['longitude'];
        $cityName = $geo['name'];
        $country = $geo['country'] ?? '';

        // Step 2: Get weather from Open-Meteo
        $weatherUrl = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current=temperature_2m,relative_humidity_2m,apparent_temperature,wind_speed_10m,weather_code&timezone=auto";
        $weatherJson = @file_get_contents($weatherUrl);
        if ($weatherJson === false) return;

        $w = json_decode($weatherJson, true);
        if (empty($w['current'])) return;

        $current = $w['current'];
        $weatherCode = $current['weather_code'] ?? 0;
        $weatherMap = $this->getWeatherCodeMap($weatherCode);

        $weatherData = [
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

        try {
            // Clear old cache and insert new
            Database::getInstance()->delete('weather_cache', '1=1');
            Database::getInstance()->insert('weather_cache', $weatherData);
        } catch (Exception $e) {
            // Silently fail — don't break the home page over weather
        }
    }

    /**
     * Map Open-Meteo weather codes to descriptions + Bootstrap Icons
     */
    private function getWeatherCodeMap($code)
    {
        $map = [
            0   => ['description' => 'Clear sky', 'icon' => 'sun'],
            1   => ['description' => 'Mainly clear', 'icon' => 'sun'],
            2   => ['description' => 'Partly cloudy', 'icon' => 'cloud-sun'],
            3   => ['description' => 'Overcast', 'icon' => 'cloud'],
            45  => ['description' => 'Foggy', 'icon' => 'cloud-fog'],
            48  => ['description' => 'Rime fog', 'icon' => 'cloud-fog'],
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
            81  => ['description' => 'Heavy showers', 'icon' => 'cloud-rain-heavy'],
            82  => ['description' => 'Violent showers', 'icon' => 'cloud-rain-heavy'],
            85  => ['description' => 'Snow showers', 'icon' => 'cloud-snow'],
            86  => ['description' => 'Heavy snow showers', 'icon' => 'cloud-snow'],
            95  => ['description' => 'Thunderstorm', 'icon' => 'cloud-lightning'],
            96  => ['description' => 'Thunderstorm + hail', 'icon' => 'cloud-lightning'],
            99  => ['description' => 'Severe thunderstorm', 'icon' => 'cloud-lightning'],
        ];
        return $map[$code] ?? ['description' => 'Unknown', 'icon' => 'cloud'];
    }

    /**
     * Show registration form
     */
    public function register()
    {
        // If already logged in, redirect to dashboard
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        $roles = Database::getInstance()->fetchAll(
            "SELECT * FROM roles WHERE slug != 'super_admin' ORDER BY id"
        );

        $this->layout = 'public';
        $this->view('home/register', [
            'title' => 'Register - Smart Attendance',
            'roles' => $roles,
            'csrf' => CSRF::field()
        ]);
    }

    /**
     * Store new registration (status = pending, requires admin approval)
     */
    public function storeRegistration()
    {
        $this->validateCsrf();

        $data = $this->input();
        $validator = new Validator($data);
        $validator->required('name', 'Full name')
                  ->required('email', 'Email')
                  ->required('password', 'Password')
                  ->required('role_id', 'Role')
                  ->email('email')
                  ->min('password', 8, 'Password')
                  ->unique('email', 'users', null, null, 'Email');

        if ($validator->fails()) {
            Flash::set('error', $validator->firstError());
            $this->redirect('/register');
        }

        // Sanitize
        $phone = trim($data['phone'] ?? '');
        $phone = $phone === '' ? null : $phone;
        $employeeId = trim((string)($data['employee_id'] ?? ''));
        $employeeId = ($employeeId === '' || $employeeId === '0') ? null : (int)$employeeId;

        // Create user with PENDING status (requires admin approval)
        Database::getInstance()->insert('users', [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $phone,
            'role_id' => (int)$data['role_id'],
            'employee_id' => $employeeId,
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]),
            'status' => 'pending'  // ← requires admin approval
        ]);

        // Notify all admins about new registration
        $admins = Database::getInstance()->fetchAll(
            "SELECT u.id FROM users u
             INNER JOIN role_permissions rp ON rp.role_id = u.role_id
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE p.slug = 'manage_users' AND u.status = 'active'"
        );
        foreach ($admins as $admin) {
            Database::getInstance()->insert('notifications', [
                'user_id' => $admin['id'],
                'type' => 'system',
                'title' => 'New User Registration',
                'message' => "{$data['name']} ({$data['email']}) has registered and is awaiting approval.",
                'channel' => 'in_app',
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Log audit
        Database::getInstance()->insert('audit_logs', [
            'action' => 'register',
            'module' => 'users',
            'description' => "New registration: {$data['name']} ({$data['email']}) - awaiting admin approval",
            'ip_address' => Auth::clientIp(),
            'user_agent' => Auth::userAgent(),
            'http_method' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
            'request_url' => $_SERVER['REQUEST_URI'] ?? '',
            'severity' => 'info'
        ]);

        Flash::set('success', 'Registration submitted! Your account is now pending admin approval. You will receive an email once approved.');
        $this->redirect('/login');
    }
}
