<?php
class NotificationController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $notifs = Database::getInstance()->fetchAll(
            "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 100",
            ['uid' => Auth::id()]
        );
        $this->view('notifications/index', [
            'title' => 'Notifications',
            'pageTitle' => 'My Notifications',
            'notifications' => $notifs,
            'csrf' => CSRF::field()
        ]);
    }

    public function apiList()
    {
        $this->requireAuth();
        $notifs = Database::getInstance()->fetchAll(
            "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 20",
            ['uid' => Auth::id()]
        );
        $unread = Database::getInstance()->count('notifications',
            'user_id = :uid AND status != :s',
            ['uid' => Auth::id(), 's' => 'read']);
        $this->json(['success' => true, 'data' => $notifs, 'unread' => $unread]);
    }

    public function markRead($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        Database::getInstance()->update('notifications',
            ['status' => 'read', 'read_at' => date('Y-m-d H:i:s')],
            'id = :id AND user_id = :uid',
            ['id' => $id, 'uid' => Auth::id()]
        );
        if ($this->isAjax()) $this->json(['success' => true]);
        $this->redirect('/notifications');
    }

    public function send()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_notifications');
        $data = $this->input();
        Database::getInstance()->insert('notifications', [
            'user_id' => $data['user_id'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'type' => $data['type'] ?? 'system',
            'title' => $data['title'],
            'message' => $data['message'],
            'channel' => $data['channel'] ?? 'in_app',
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s')
        ]);
        $this->json(['success' => true, 'message' => 'Notification sent']);
    }
}
