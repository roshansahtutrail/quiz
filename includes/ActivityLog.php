<?php
/**
 * ActivityLog Class
 * Tracks all user activities
 * Version: 1.0
 */

class ActivityLog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Log activity
     */
    public function log($action, $module, $entityType, $entityId = null, $oldValues = null, $newValues = null)
    {
        try {
            $auth = new Auth();
            $admin = $auth->getCurrentAdmin();
            $team = $auth->getCurrentTeam();

            $logData = [
                'admin_id' => $admin ? $admin['id'] : null,
                'team_id' => $team ? $team['id'] : null,
                'action' => $action,
                'module' => $module,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => Security::getClientIP(),
                'user_agent' => Security::getUserAgent(),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('activity_logs', $logData);
            return true;
        } catch (Exception $e) {
            Logger::log('ActivityLog Error: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get activity logs
     */
    public function getLogs($module = null, $limit = 100, $offset = 0)
    {
        $where = '1=1';
        $params = [];

        if ($module) {
            $where .= ' AND module = ?';
            $params[] = $module;
        }

        $this->db->query(
            "SELECT * FROM activity_logs WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        return $this->db->resultSet();
    }

    /**
     * Get activity logs count
     */
    public function getLogsCount($module = null)
    {
        $where = '1=1';
        $params = [];

        if ($module) {
            $where .= ' AND module = ?';
            $params[] = $module;
        }

        $this->db->query("SELECT COUNT(*) as count FROM activity_logs WHERE $where", $params);
        $result = $this->db->single();
        return $result['count'];
    }

    /**
     * Delete old logs
     */
    public function deleteOldLogs($days = 90)
    {
        $date = date('Y-m-d', strtotime("-$days days"));
        $this->db->delete('activity_logs', 'created_at < ?', [$date]);
        return true;
    }
}
