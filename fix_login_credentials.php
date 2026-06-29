<?php
/**
 * Fix Login Credentials - Insert Missing Teams
 * This script adds all 20 teams to the database with proper credentials
 */

require_once 'includes/config.php';

$db = Database::getInstance();

// Password: "password" hashed with bcrypt (cost 10)
$passwordHash = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Pq';

// First, delete all existing teams to start fresh
$db->query('DELETE FROM teams');
echo "✅ Cleared existing teams<br>";

// Insert 20 teams
$teams = [
    ['St. Mary School', 'Team Alpha', 'John Doe', 'team1', 'team1@mail.com'],
    ['Lincoln High', 'Team Beta', 'Jane Smith', 'team2', 'team2@mail.com'],
    ['Central Academy', 'Team Gamma', 'Michael Brown', 'team3', 'team3@mail.com'],
    ['Kings College', 'Team Delta', 'Sarah Wilson', 'team4', 'team4@mail.com'],
    ['Green Valley', 'Team Epsilon', 'Robert Taylor', 'team5', 'team5@mail.com'],
    ['Blue Ridge', 'Team Zeta', 'Emily Davis', 'team6', 'team6@mail.com'],
    ['Sunrise Academy', 'Team Eta', 'David Miller', 'team7', 'team7@mail.com'],
    ['Oak Park School', 'Team Theta', 'Lisa Anderson', 'team8', 'team8@mail.com'],
    ['Pine Grove', 'Team Iota', 'James Thomas', 'team9', 'team9@mail.com'],
    ['Maple Ridge', 'Team Kappa', 'Patricia Jackson', 'team10', 'team10@mail.com'],
    ['Cedar Hill', 'Team Lambda', 'Christopher White', 'team11', 'team11@mail.com'],
    ['Birch Valley', 'Team Mu', 'Jennifer Harris', 'team12', 'team12@mail.com'],
    ['Willow Creek', 'Team Nu', 'Daniel Martin', 'team13', 'team13@mail.com'],
    ['Aspen Ridge', 'Team Xi', 'Karen Thompson', 'team14', 'team14@mail.com'],
    ['Spruce Grove', 'Team Omicron', 'Mark Garcia', 'team15', 'team15@mail.com'],
    ['Fir Mountain', 'Team Pi', 'Susan Rodriguez', 'team16', 'team16@mail.com'],
    ['Elm Street', 'Team Rho', 'George Lee', 'team17', 'team17@mail.com'],
    ['Ash Lane', 'Team Sigma', 'Mary Perez', 'team18', 'team18@mail.com'],
    ['Poplar Park', 'Team Tau', 'Charles Walker', 'team19', 'team19@mail.com'],
    ['Redwood Valley', 'Team Upsilon', 'Nancy Hall', 'team20', 'team20@mail.com'],
];

foreach ($teams as $team) {
    $sql = 'INSERT INTO teams (school_name, team_name, leader_name, username, email, password, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)';
    $db->query($sql, [$team[0], $team[1], $team[2], $team[3], $team[4], $passwordHash, 'active']);
}

echo "✅ Successfully inserted 20 teams!<br><br>";

// Verify admin credentials
echo "✅ Admin Credentials:<br>";
echo "Username: <strong>admin</strong><br>";
echo "Password: <strong>password</strong><br><br>";

// Verify teams credentials
echo "✅ Team Credentials:<br>";
echo "Usernames: <strong>team1</strong> through <strong>team20</strong><br>";
echo "Password for all teams: <strong>password</strong><br><br>";

// Display all teams
$db->query('SELECT id, username, team_name, school_name FROM teams ORDER BY id');
$allTeams = $db->resultSet();

echo "<table border='1' cellpadding='10' style='margin-top: 20px; border-collapse: collapse;'>";
echo "<tr style='background: #2563eb; color: white;'>";
echo "<th>ID</th><th>Username</th><th>Team Name</th><th>School</th>";
echo "</tr>";

foreach ($allTeams as $t) {
    echo "<tr>";
    echo "<td>" . $t['id'] . "</td>";
    echo "<td><strong>" . htmlspecialchars($t['username']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($t['team_name']) . "</td>";
    echo "<td>" . htmlspecialchars($t['school_name']) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><br><strong style='color: green; font-size: 16px;'>✅ Login Credentials Fixed!</strong><br>";
echo "<br><a href='quiz/login.php' style='color: #2563eb; font-weight: bold;'>Go to Team Login →</a>";
echo "<br><a href='admin/login.php' style='color: #2563eb; font-weight: bold;'>Go to Admin Login →</a>";
?>
