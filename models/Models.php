<?php
/**
 * Admin Model
 * Version: 1.0
 */

class AdminModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all admins
     */
    public function getAll($limit = 50, $offset = 0)
    {
        $this->db->query('SELECT * FROM admins ORDER BY created_at DESC LIMIT ? OFFSET ?', [$limit, $offset]);
        return $this->db->resultSet();
    }

    /**
     * Get admin by ID
     */
    public function getById($id)
    {
        $this->db->query('SELECT * FROM admins WHERE id = ?', [$id]);
        return $this->db->single();
    }

    /**
     * Get total count
     */
    public function getCount()
    {
        $this->db->query('SELECT COUNT(*) as count FROM admins');
        $result = $this->db->single();
        return $result['count'];
    }

    /**
     * Create new admin
     */
    public function create($username, $email, $password, $first_name, $last_name, $role = 'viewer')
    {
        // Check if username exists
        $this->db->query('SELECT id FROM admins WHERE username = ?', [$username]);
        if ($this->db->rowCount() > 0) {
            throw new Exception('Username already exists');
        }

        // Check if email exists
        $this->db->query('SELECT id FROM admins WHERE email = ?', [$email]);
        if ($this->db->rowCount() > 0) {
            throw new Exception('Email already exists');
        }

        $data = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => $role,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('admins', $data);
        return $this->db->lastInsertId();
    }

    /**
     * Update admin
     */
    public function update($id, $data)
    {
        try {
            // Ensure updated_at is always set
            if (!isset($data['updated_at'])) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            
            // Build update query
            $set = implode(', ', array_map(fn($key) => "$key = ?", array_keys($data)));
            $sql = "UPDATE admins SET $set WHERE id = ?";
            
            $params = array_merge(array_values($data), [$id]);
            $this->db->query($sql, $params);
            
            return $this->db->rowCount();
        } catch (Exception $e) {
            throw new Exception('Failed to update admin: ' . $e->getMessage());
        }
    }

    /**
     * Delete admin
     */
    public function delete($id)
    {
        $this->db->delete('admins', 'id = ?', [$id]);
        return true;
    }

    /**
     * Change password
     */
    public function changePassword($id, $newPassword)
    {
        $hashedPassword = Security::hashPassword($newPassword);
        $this->db->update('admins', ['password' => $hashedPassword], 'id = ?', [$id]);
        return true;
    }

    /**
     * Search admins
     */
    public function search($searchTerm)
    {
        $term = '%' . $searchTerm . '%';
        $this->db->query(
            'SELECT * FROM admins WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ? ORDER BY created_at DESC',
            [$term, $term, $term, $term]
        );
        return $this->db->resultSet();
    }
}

/**
 * Team Model
 * Version: 1.0
 */

class TeamModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all teams
     */
    public function getAll($limit = 50, $offset = 0)
    {
        $this->db->query('SELECT * FROM teams ORDER BY created_at DESC LIMIT ? OFFSET ?', [$limit, $offset]);
        return $this->db->resultSet();
    }

    /**
     * Get team by ID
     */
    public function getById($id)
    {
        $this->db->query('SELECT * FROM teams WHERE id = ?', [$id]);
        return $this->db->single();
    }

    /**
     * Get total count
     */
    public function getCount()
    {
        $this->db->query('SELECT COUNT(*) as count FROM teams');
        $result = $this->db->single();
        return $result['count'];
    }

    /**
     * Get active teams count
     */
    public function getActiveCount()
    {
        $this->db->query('SELECT COUNT(*) as count FROM teams WHERE status = ?', ['active']);
        $result = $this->db->single();
        return $result['count'];
    }

    /**
     * Update team
     */
    public function update($id, $data)
    {
        unset($data['password']); // Don't update password here
        $this->db->update('teams', $data, 'id = ?', [$id]);
        return true;
    }

    /**
     * Delete team
     */
    public function delete($id)
    {
        $this->db->delete('teams', 'id = ?', [$id]);
        return true;
    }

    /**
     * Delete multiple teams
     */
    public function deleteMultiple(array $ids)
    {
        if (empty($ids)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $this->db->query("DELETE FROM teams WHERE id IN ($placeholders)", $ids);
        return true;
    }

    /**
     * Change password
     */
    public function changePassword($id, $newPassword)
    {
        $hashedPassword = Security::hashPassword($newPassword);
        $this->db->update('teams', ['password' => $hashedPassword], 'id = ?', [$id]);
        return true;
    }

    /**
     * Search teams
     */
    public function search($searchTerm)
    {
        $term = '%' . $searchTerm . '%';
        $this->db->query(
            'SELECT * FROM teams WHERE school_name LIKE ? OR team_name LIKE ? OR leader_name LIKE ? OR username LIKE ? ORDER BY created_at DESC',
            [$term, $term, $term, $term]
        );
        return $this->db->resultSet();
    }

    /**
     * Search teams with pagination
     */
    public function searchPaginated($searchTerm, $limit, $offset)
    {
        $term = '%' . $searchTerm . '%';
        $this->db->query(
            'SELECT * FROM teams WHERE school_name LIKE ? OR team_name LIKE ? OR leader_name LIKE ? OR username LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$term, $term, $term, $term, $limit, $offset]
        );
        return $this->db->resultSet();
    }

    /**
     * Get count of search results
     */
    public function getSearchCount($searchTerm)
    {
        $term = '%' . $searchTerm . '%';
        $this->db->query(
            'SELECT COUNT(*) as count FROM teams WHERE school_name LIKE ? OR team_name LIKE ? OR leader_name LIKE ? OR username LIKE ?',
            [$term, $term, $term, $term]
        );
        $result = $this->db->single();
        return $result['count'];
    }

    /**
     * Get teams by school
     */
    public function getBySchool($schoolName)
    {
        $this->db->query('SELECT * FROM teams WHERE school_name = ? ORDER BY team_name ASC', [$schoolName]);
        return $this->db->resultSet();
    }

    /**
     * Deactivate team
     */
    public function deactivate($id)
    {
        $this->db->update('teams', ['status' => 'inactive'], 'id = ?', [$id]);
        return true;
    }

    /**
     * Activate team
     */
    public function activate($id)
    {
        $this->db->update('teams', ['status' => 'active'], 'id = ?', [$id]);
        return true;
    }

    /**
     * Check if username exists
     */
    public function usernameExists($username, $excludeId = null)
    {
        if ($excludeId) {
            $this->db->query('SELECT id FROM teams WHERE username = ? AND id != ?', [$username, $excludeId]);
        } else {
            $this->db->query('SELECT id FROM teams WHERE username = ?', [$username]);
        }
        return $this->db->single() ? true : false;
    }

    /**
     * Insert team
     */
    public function insert($table, $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert($table, $data);
    }
}

/**
 * Round Model
 * Version: 1.0
 */

class RoundModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all rounds
     */
    public function getAll()
    {
        $this->db->query('SELECT * FROM rounds ORDER BY sequence ASC');
        return $this->db->resultSet();
    }

    /**
     * Get round by ID
     */
    public function getById($id)
    {
        $this->db->query('SELECT * FROM rounds WHERE id = ?', [$id]);
        return $this->db->single();
    }

    /**
     * Get active round
     */
    public function getActiveRound()
    {
        $this->db->query('SELECT * FROM rounds WHERE is_active = ? AND status = ?', [1, 'active']);
        return $this->db->single();
    }

    /**
     * Get total count
     */
    public function getCount()
    {
        $this->db->query('SELECT COUNT(*) as count FROM rounds');
        $result = $this->db->single();
        return $result['count'];
    }

    /**
     * Create round
     */
    public function create($data)
    {
        // Get next sequence
        $this->db->query('SELECT MAX(sequence) as max_seq FROM rounds');
        $result = $this->db->single();
        $data['sequence'] = ($result['max_seq'] ?? 0) + 1;

        return $this->db->insert('rounds', $data);
    }

    /**
     * Update round
     */
    public function update($id, $data)
    {
        $this->db->update('rounds', $data, 'id = ?', [$id]);
        return true;
    }

    /**
     * Delete round
     */
    public function delete($id)
    {
        $this->db->delete('rounds', 'id = ?', [$id]);
        return true;
    }

    /**
     * Activate round (deactivate others)
     */
    public function activateRound($id)
    {
        $this->db->beginTransaction();
        try {
            // Deactivate all other rounds
            $this->db->update('rounds', ['is_active' => 0], '1=1');

            // Activate selected round
            $this->db->update('rounds', ['is_active' => 1, 'status' => 'active'], 'id = ?', [$id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Deactivate round
     */
    public function deactivateRound($id)
    {
        $this->db->update('rounds', ['is_active' => 0, 'status' => 'inactive'], 'id = ?', [$id]);
        return true;
    }

    /**
     * Lock round
     */
    public function lockRound($id)
    {
        $this->db->update('rounds', ['status' => 'locked'], 'id = ?', [$id]);
        return true;
    }

    /**
     * Unlock round
     */
    public function unlockRound($id)
    {
        $this->db->update('rounds', ['status' => 'inactive'], 'id = ?', [$id]);
        return true;
    }

    /**
     * Complete round
     */
    public function completeRound($id)
    {
        $this->db->update('rounds', ['status' => 'completed', 'is_active' => 0], 'id = ?', [$id]);
        return true;
    }

    /**
     * Update round sequence
     */
    public function updateSequence($id, $sequence)
    {
        $this->db->update('rounds', ['sequence' => $sequence], 'id = ?', [$id]);
        return true;
    }

    /**
     * Get round questions count
     */
    public function getQuestionsCount($roundId)
    {
        $this->db->query('SELECT COUNT(*) as count FROM questions WHERE round_id = ?', [$roundId]);
        $result = $this->db->single();
        return $result['count'];
    }
}
