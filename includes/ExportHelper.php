<?php
/**
 * Export Helper Functions
 * Provides CSV export and print functionality for admin pages
 */

class ExportHelper {
    /**
     * Export data to CSV
     */
    public static function exportToCSV($filename, $headers, $data) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Generate CSV data for teams
     */
    public static function generateTeamsCSV($teams) {
        $headers = ['School', 'Team Name', 'Leader', 'Username', 'Email', 'Status'];
        $data = [];
        
        foreach ($teams as $team) {
            $data[] = [
                $team['school_name'],
                $team['team_name'],
                $team['leader_name'],
                $team['username'],
                $team['email'],
                $team['status']
            ];
        }
        
        return ['headers' => $headers, 'data' => $data];
    }
    
    /**
     * Generate CSV data for admins
     */
    public static function generateAdminsCSV($admins) {
        $headers = ['Name', 'Username', 'Email', 'Role', 'Status'];
        $data = [];
        
        foreach ($admins as $admin) {
            $data[] = [
                $admin['first_name'] . ' ' . $admin['last_name'],
                $admin['username'],
                $admin['email'],
                $admin['role'],
                $admin['status']
            ];
        }
        
        return ['headers' => $headers, 'data' => $data];
    }
    
    /**
     * Generate CSV data for results
     */
    public static function generateResultsCSV($results) {
        $headers = ['Team', 'School', 'Correct', 'Wrong', 'Total Marks', 'Percentage'];
        $data = [];
        
        foreach ($results as $result) {
            $percentage = ($result['total_answered'] > 0) ? 
                round(($result['total_marks'] / ($result['total_answered'] * 1)) * 100, 2) : 0;
            
            $data[] = [
                $result['team_name'],
                $result['school_name'],
                $result['correct_count'],
                $result['wrong_count'],
                $result['total_marks'],
                $percentage . '%'
            ];
        }
        
        return ['headers' => $headers, 'data' => $data];
    }
    
    /**
     * Generate CSV data for questions
     */
    public static function generateQuestionsCSV($questions) {
        $headers = ['Round', 'Question', 'Marks', 'Status'];
        $data = [];
        
        foreach ($questions as $question) {
            $data[] = [
                $question['round_name'],
                substr($question['question_text'], 0, 100),
                $question['marks'],
                $question['status']
            ];
        }
        
        return ['headers' => $headers, 'data' => $data];
    }
    
    /**
     * Generate CSV data for rounds
     */
    public static function generateRoundsCSV($rounds) {
        $headers = ['Round Name', 'Time/Question', 'Total Questions', 'Status'];
        $data = [];
        
        foreach ($rounds as $round) {
            $data[] = [
                $round['name'],
                $round['time_per_question'] . 's',
                $round['total_questions'],
                $round['is_active'] ? 'Active' : 'Inactive'
            ];
        }
        
        return ['headers' => $headers, 'data' => $data];
    }
}
