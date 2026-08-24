<?php
/**
 * Route Definitions
 * Format: Router::method('pattern', 'Controller@action', ['middleware'])
 */

// ============ PUBLIC ROUTES (no auth required) ============
Router::get('',                'Home@index');
Router::get('/',               'Home@index');
Router::get('/register',       'Home@register');
Router::post('/register',      'Home@storeRegistration');

// ============ AUTH ROUTES ============
Router::get('/login',          'Auth@login');
Router::post('/login',         'Auth@loginPost');
Router::get('/logout',         'Auth@logout');
Router::get('/forgot-password','Auth@forgot');
Router::post('/forgot-password','Auth@forgotPost');
Router::get('/reset-password', 'Auth@reset');
Router::post('/reset-password','Auth@resetPost');
Router::get('/verify-otp',     'Auth@verifyOtp');
Router::post('/verify-otp',    'Auth@verifyOtpPost');

// ============ DASHBOARD ============
Router::get('/dashboard',      'Dashboard@index', ['auth']);

// ============ EMPLOYEES ============
Router::get('/employees',              'Employee@index',    ['auth']);
Router::get('/employees/create',       'Employee@create',   ['auth']);
Router::post('/employees/store',       'Employee@store',    ['auth']);
Router::get('/employees/{id}',         'Employee@show',     ['auth']);
Router::get('/employees/{id}/edit',    'Employee@edit',     ['auth']);
Router::post('/employees/{id}/update', 'Employee@update',   ['auth']);
Router::post('/employees/{id}/delete', 'Employee@destroy',  ['auth']);
Router::post('/employees/{id}/face-enroll', 'Employee@faceEnroll', ['auth']);
Router::get('/api/employees',          'Employee@apiList',  ['auth']);
Router::get('/api/employees/{id}',     'Employee@apiShow',  ['auth']);

// ============ DEPARTMENTS ============
Router::get('/departments',            'Department@index',   ['auth']);
Router::get('/departments/create',     'Department@create',  ['auth']);
Router::post('/departments/store',     'Department@store',   ['auth']);
Router::get('/departments/{id}/edit',  'Department@edit',    ['auth']);
Router::post('/departments/{id}/update','Department@update', ['auth']);
Router::post('/departments/{id}/delete','Department@destroy',['auth']);
Router::get('/api/departments',        'Department@apiList', ['auth']);

// ============ ATTENDANCE ============
Router::get('/attendance',                  'Attendance@index',        ['auth']);
Router::get('/attendance/face-scan',        'Attendance@faceScan',     ['auth']);
Router::post('/attendance/check-in',        'Attendance@checkIn',      ['auth']);
Router::post('/attendance/check-out',       'Attendance@checkOut',     ['auth']);
Router::get('/attendance/my',               'Attendance@myAttendance', ['auth']);
Router::get('/attendance/report',           'Attendance@report',       ['auth']);
Router::post('/attendance/manual',          'Attendance@manualAdd',    ['auth']);
Router::post('/attendance/{id}/delete',     'Attendance@delete',       ['auth']);
Router::post('/attendance/export',          'Attendance@exportReport', ['auth']);
Router::get('/api/attendance/today',        'Attendance@apiToday',     ['auth']);
Router::get('/api/attendance/stats',        'Attendance@apiStats',     ['auth']);
Router::get('/api/attendance/history',      'Attendance@apiHistory',   ['auth']);

// ============ KIOSK (public — no login required) ============
Router::get('/attendance/kiosk',            'Attendance@kiosk');
Router::post('/attendance/kiosk/scan',      'Attendance@kioskFaceScan');

// ============ FACE RECOGNITION ============
Router::get('/face/enroll/{employeeId}','Face@enroll',     ['auth']);
Router::post('/face/store',             'Face@store',       ['auth']);
Router::post('/face/verify',            'Face@verify',      ['auth']);
Router::post('/face/match',             'Face@match');              // PUBLIC — no auth (kiosk + face_scan)
Router::get('/face/descriptors',        'Face@descriptors', ['auth']);
Router::post('/face/delete/{id}',       'Face@destroy',     ['auth']);

// ============ SHIFTS ============
Router::get('/shifts',             'Shift@index',   ['auth']);
Router::get('/shifts/create',      'Shift@create',  ['auth']);
Router::post('/shifts/store',      'Shift@store',   ['auth']);
Router::get('/shifts/{id}/edit',   'Shift@edit',    ['auth']);
Router::post('/shifts/{id}/update','Shift@update',  ['auth']);
Router::post('/shifts/{id}/delete','Shift@destroy', ['auth']);

// ============ LEAVES ============
Router::get('/leaves',              'Leave@index',    ['auth']);
Router::get('/leaves/apply',        'Leave@create',   ['auth']);
Router::post('/leaves/apply',       'Leave@store',    ['auth']);
Router::get('/leaves/{id}',         'Leave@show',     ['auth']);
Router::post('/leaves/{id}/approve','Leave@approve',  ['auth']);
Router::post('/leaves/{id}/reject', 'Leave@reject',   ['auth']);
Router::get('/leaves/balance',      'Leave@balance',  ['auth']);

// ============ PAYROLL ============
Router::get('/payroll',                'Payroll@index',     ['auth']);
Router::get('/payroll/generate',       'Payroll@generate',  ['auth']);
Router::post('/payroll/process',       'Payroll@process',   ['auth']);
Router::get('/payroll/{id}',           'Payroll@show',      ['auth']);
Router::get('/payroll/{id}/payslip',   'Payroll@payslip',   ['auth']);
Router::post('/payroll/{id}/approve',  'Payroll@approve',   ['auth']);

// ============ REPORTS ============
Router::get('/reports',                  'Report@index',           ['auth']);
Router::get('/reports/attendance',       'Report@attendance',      ['auth']);
Router::get('/reports/employees',        'Report@employees',       ['auth']);
Router::get('/reports/payroll',          'Report@payroll',         ['auth']);
Router::get('/reports/leaves',           'Report@leaves',          ['auth']);
Router::get('/reports/department',       'Report@department',      ['auth']);
Router::post('/reports/export',          'Report@export',          ['auth']);

// ============ COMPANY / BRANCHES ============
Router::get('/company',              'Company@index',   ['auth']);
Router::post('/company/update',      'Company@update',  ['auth']);
Router::get('/branches',             'Branch@index',    ['auth']);
Router::post('/branches/store',      'Branch@store',    ['auth']);
Router::post('/branches/{id}/update','Branch@update',   ['auth']);
Router::post('/branches/{id}/delete','Branch@destroy',  ['auth']);

// ============ HOLIDAYS ============
Router::get('/holidays',             'Holiday@index',   ['auth']);
Router::post('/holidays/store',      'Holiday@store',   ['auth']);
Router::post('/holidays/{id}/update','Holiday@update',  ['auth']);
Router::post('/holidays/{id}/delete','Holiday@destroy', ['auth']);

// ============ NOTIFICATIONS ============
Router::get('/notifications',            'Notification@index',    ['auth']);
Router::get('/api/notifications',        'Notification@apiList',  ['auth']);
Router::post('/notifications/{id}/read', 'Notification@markRead', ['auth']);
Router::post('/api/notifications/send',  'Notification@send',     ['auth']);

// ============ USERS ============
Router::get('/users',               'User@index',    ['auth']);
Router::get('/users/create',        'User@create',   ['auth']);
Router::post('/users/store',        'User@store',    ['auth']);
Router::get('/users/{id}/edit',     'User@edit',     ['auth']);
Router::post('/users/{id}/update',  'User@update',   ['auth']);
Router::post('/users/{id}/delete',  'User@destroy',  ['auth']);
Router::post('/users/{id}/approve', 'User@approve',  ['auth']);
Router::post('/users/{id}/suspend', 'User@suspend',  ['auth']);
Router::post('/users/{id}/reactivate', 'User@reactivate', ['auth']);
Router::post('/users/{id}/reject',  'User@reject',   ['auth']);

// ============ AUDIT LOGS ============
Router::get('/audit-logs',          'AuditLog@index', ['auth']);

// ============ DATABASE ACTIVITY TRACKER ============
Router::get('/database-activity',           'DatabaseActivity@index', ['auth']);
Router::get('/database-activity/{id}',      'DatabaseActivity@view',  ['auth']);
Router::get('/database-activity/export',    'DatabaseActivity@export',['auth']);

// ============ SETTINGS ============
Router::get('/settings',            'Setting@index',  ['auth']);
Router::post('/settings/update',    'Setting@update', ['auth']);

// ============ CONTENT MANAGEMENT ============
Router::get('/content',                                  'Content@index',              ['auth']);
Router::post('/content/announcements/store',             'Content@storeAnnouncement',  ['auth']);
Router::post('/content/announcements/{id}/update',       'Content@updateAnnouncement', ['auth']);
Router::post('/content/announcements/{id}/delete',       'Content@deleteAnnouncement', ['auth']);
Router::post('/content/youtube/store',                   'Content@storeYoutube',       ['auth']);
Router::post('/content/youtube/{id}/delete',             'Content@deleteYoutube',      ['auth']);
Router::post('/content/events/store',                    'Content@storeEvent',         ['auth']);
Router::post('/content/events/{id}/delete',              'Content@deleteEvent',        ['auth']);
Router::post('/content/weather/update',                  'Content@updateWeather',      ['auth']);

// ============ PROFILE ============
Router::get('/profile',             'Profile@index',  ['auth']);
Router::post('/profile/update',     'Profile@update', ['auth']);
Router::post('/profile/password',   'Profile@password',['auth']);

// ============ API (REST) ============
Router::post('/api/auth/login',          'Api\Auth@login');
Router::post('/api/auth/logout',         'Api\Auth@logout');
Router::get('/api/dashboard/stats',      'Api\Dashboard@stats', ['auth']);
Router::post('/api/attendance/check-in',  'Api\Attendance@checkIn',  ['auth']);
Router::post('/api/attendance/check-out', 'Api\Attendance@checkOut', ['auth']);
Router::post('/api/attendance/face-scan', 'Api\Attendance@faceScan', ['auth']);
Router::get('/api/attendance/reports',    'Api\Attendance@reports',  ['auth']);

// ============ QR CODE ATTENDANCE ============
Router::get('/attendance/qr',           'Qr@myQr',         ['auth']);
Router::post('/attendance/qr/regen',    'Qr@regenerate',   ['auth']);
Router::get('/attendance/qr-scan',      'Qr@scanPage');
Router::post('/attendance/qr-scan',     'Qr@processScan');
Router::get('/attendance/qr-admin',     'Qr@adminList',    ['auth']);
Router::post('/attendance/qr-bulk',     'Qr@bulkGenerate', ['auth']);

// ============ MOBILE ATTENDANCE ============
Router::get('/attendance/mobile',       'MobileAttendance@index',   ['auth']);
Router::post('/attendance/mobile',      'MobileAttendance@process', ['auth']);

// ============ ATTENDANCE CORRECTIONS ============
Router::get('/corrections',             'Correction@myRequests', ['auth']);
Router::post('/corrections/create',     'Correction@create',     ['auth']);
Router::get('/corrections/admin',       'Correction@index',      ['auth']);
Router::post('/corrections/{id}/approve','Correction@approve',   ['auth']);
Router::post('/corrections/{id}/reject', 'Correction@reject',    ['auth']);

// ============ REAL-TIME DASHBOARD API ============
Router::get('/api/dashboard/realtime',  'Dashboard@realtime',    ['auth']);
Router::get('/api/dashboard/activity',  'Dashboard@activityFeed',['auth']);

// ============ EMPLOYEE SELF-SERVICE DASHBOARD ============
Router::get('/my-attendance',  'SelfService@dashboard', ['auth']);

// ============ SELF-REGISTRATION ============
Router::get('/register',                                'Registration@index');
Router::post('/registration/check-duplicate',           'Registration@checkDuplicate');
Router::post('/registration/submit',                    'Registration@submit');
Router::get('/registration/status/{token}',             'Registration@status');
Router::post('/registration/resubmit/{token}',          'Registration@resubmit');
Router::post('/registration/check-employee-status',     'Registration@checkEmployeeStatus');

// Admin approval center
Router::get('/registration/admin',                      'Registration@adminList',    ['auth']);
Router::get('/registration/admin/review/{id}',          'Registration@adminReview',  ['auth']);
Router::post('/registration/admin/approve/{id}',        'Registration@approve',      ['auth']);
Router::post('/registration/admin/reject/{id}',         'Registration@reject',       ['auth']);
Router::post('/registration/admin/changes/{id}',        'Registration@requestChanges', ['auth']);
