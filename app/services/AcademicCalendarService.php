<?php

class AcademicCalendarService {
    /**
     * Determines the current academic semester based on the current month.
     *
     * @return string "1st Semester" or "2nd Semester".
     */
    public static function getCurrentSemester() {
        $currentMonth = (int)date('n'); // n = month number without leading zeros (1-12)

        // 1st Semester is typically August-Jan
        if ($currentMonth >= 8 || $currentMonth <= 1) {
            return "1st Semester";
        } 
        // 2nd Semester is typically Feb-July
        else {
            return "2nd Semester";
        }
    }
}