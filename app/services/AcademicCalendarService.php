<?php

class AcademicCalendarService {
    public static function getCurrentSession() {
        $settings_file = __DIR__ . '/../config/settings.json';
        if (file_exists($settings_file)) {
            $settings = json_decode(file_get_contents($settings_file), true);
            if (!empty($settings['session_year'])) {
                return $settings['session_year'];
            }
        }
        $year = (int)date('Y');
        return $year . '/' . ($year + 1);
    }

    public static function getCurrentSemester() {
        $currentMonth = (int)date('n'); // n = month number without leading zeros (1-12)

        if ($currentMonth >= 8 || $currentMonth <= 1) {
            return "1st Semester";
        } 
        else {
            return "2nd Semester";
        }
    }
}