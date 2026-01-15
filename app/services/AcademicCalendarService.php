<?php

class AcademicCalendarService {
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