<?php
/**
 * Authentication Class
 * Handles admin and team authentication
 * Version: 1.0
 */

class Auth
{
    private $db;
    private $sessionTimeout = SESSION_TIMEOUT;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->checkSessionTimeout();
    }

    /**
     * Login admin
     */
    public function loginAdmin($username, $password)
    {
        $username = Security::sanitizeInput($username);

        $this->db->query('SELECT * FROM admins WHERE username = ? AND status = ?', [$username, 'active']);
        $admin = $this->db->single();

        if (!$admin || !Security::verifyPassword($password, $admin['password'])) {
            Logger::warning('Failed admin login attempt', ['username' => $username, 'ip' => Security::getClientIP()]);
            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        // Update last login
        $this->db->update('admins', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$admin['id']]);

        // Set session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
        $_SESSION['login_time'] = time();

        Logger::info('Admin logged in', ['admin_id' => $admin['id'], 'username' => $username]);

        return ['success' => true, 'message' => 'Login successful', 'admin' => $admin];
    }

    /**
     * Login team
     */
    public function loginTeam($username, $password)
    {
        $username = Security::sanitizeInput($username);

        $this->db->query('SELECT * FROM teams WHERE username = ? AND status = ?', [$username, 'active']);
        $team = $this->db->single();

        if (!$team || !Security::verifyPassword($password, $team['password'])) {
            Logger::warning('Failed team login attempt', ['username' => $username, 'ip' => Security::getClientIP()]);
            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        // Set session
        $_SESSION['team_id'] = $team['id'];
        $_SESSION['team_username'] = $team['username'];
        $_SESSION['team_name'] = $team['team_name'];
        $_SESSION['school_name'] = $team['school_name'];
        $_SESSION['login_time'] = time();

        Logger::info('Team logged in', ['team_id' => $team['id'], 'team_name' => $team['team_name']]);

        return ['success' => true, 'message' => 'Login successful', 'team' => $team];
    }

    /**
     * Logout
     */
    public function logout()
    {
        if (isset($_SESSION['admin_id'])) {
            Logger::info('Admin logged out', ['admin_id' => $_SESSION['admin_id']]);
        } elseif (isset($_SESSION['team_id'])) {
            Logger::info('Team logged out', ['team_id' => $_SESSION['team_id']]);
        }

        session_destroy();
        return true;
    }

    /**
     * Check if admin is logged in
     */
    public function isAdminLoggedIn()
    {
        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    }

    /**
     * Check if team is logged in
     */
    public function isTeamLoggedIn()
    {
        return isset($_SESSION['team_id']) && !empty($_SESSION['team_id']);
    }

    /**
     * Get current admin
     */
    public function getCurrentAdmin()
    {
        if (!$this->isAdminLoggedIn()) {
            return null;
        }
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'role' => $_SESSION['admin_role'],
            'name' => $_SESSION['admin_name']
        ];
    }

    /**
     * Get current team
     */
    public function getCurrentTeam()
    {
        if (!$this->isTeamLoggedIn()) {
            return null;
        }
        return [
            'id' => $_SESSION['team_id'],
            'username' => $_SESSION['team_username'],
            'team_name' => $_SESSION['team_name'],
            'school_name' => $_SESSION['school_name']
        ];
    }

    /**
     * Check admin permission
     */
    public function checkAdminPermission($requiredRole = null)
    {
        if (!$this->isAdminLoggedIn()) {
            return false;
        }

        if ($requiredRole && $_SESSION['admin_role'] !== $requiredRole && $_SESSION['admin_role'] !== 'super_admin') {
            return false;
        }

        return true;
    }

    /**
     * Check whether admin can manage team, round, and question pages.
     */
    public function canManageContent()
    {
        return $this->isAdminLoggedIn() && in_array($_SESSION['admin_role'], ['super_admin', 'quiz_master'], true);
    }

    /**
     * Check session timeout
     */
    private function checkSessionTimeout()
    {
        if ($this->isAdminLoggedIn() || $this->isTeamLoggedIn()) {
            $loginTime = $_SESSION['login_time'] ?? time();
            if (time() - $loginTime > $this->sessionTimeout) {
                session_destroy();
            }
        }
    }

    /**
     * Update last activity
     */
    public function updateLastActivity()
    {
        if ($this->isAdminLoggedIn() || $this->isTeamLoggedIn()) {
            $_SESSION['login_time'] = time();
        }
    }

    /**
     * Create admin account
     */
    public function createAdmin($data)
    {
        // Validate data
        $validation = Helper::validateInput($data, [
            'username' => 'required|min:4|max:100',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
            'role' => 'required'
        ]);

        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        // Check if username exists
        $this->db->query('SELECT id FROM admins WHERE username = ?', [$data['username']]);
        if ($this->db->single()) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        // Create admin
        $adminData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Security::hashPassword($data['password']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role' => $data['role'],
            'status' => 'active'
        ];

        $adminId = $this->db->insert('admins', $adminData);
        Logger::info('Admin account created', ['admin_id' => $adminId, 'username' => $data['username']]);

        return ['success' => true, 'message' => 'Admin account created successfully', 'admin_id' => $adminId];
    }

    /**
     * Create team account
     */
    public function createTeam($data)
    {
        // Validate data
        $validation = Helper::validateInput($data, [
            'school_name' => 'required|min:2',
            'team_name' => 'required|min:2',
            'leader_name' => 'required|min:2',
            'username' => 'required|min:4|max:100',
            'password' => 'required|min:6'
        ]);

        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        // Check if username exists
        $this->db->query('SELECT id FROM teams WHERE username = ?', [$data['username']]);
        if ($this->db->single()) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        // Create team
        $teamData = [
            'school_name' => $data['school_name'],
            'team_name' => $data['team_name'],
            'leader_name' => $data['leader_name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? '',
            'password' => Security::hashPassword($data['password']),
            'status' => 'active'
        ];

        $teamId = $this->db->insert('teams', $teamData);
        Logger::info('Team account created', ['team_id' => $teamId, 'team_name' => $data['team_name']]);

        return ['success' => true, 'message' => 'Team account created successfully', 'team_id' => $teamId];
    }

    /**
     * Reset password
     */
    public function resetPassword($userId, $newPassword, $userType = 'admin')
    {
        $validation = Security::validatePasswordStrength($newPassword);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        $table = $userType === 'admin' ? 'admins' : 'teams';
        $this->db->update($table, ['password' => Security::hashPassword($newPassword)], 'id = ?', [$userId]);

        Logger::info('Password reset for ' . $userType, ['user_id' => $userId]);
        return ['success' => true, 'message' => 'Password reset successfully'];
    }
}
