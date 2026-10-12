<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/services/AcademicCalendarService.php';

class AcademicCalendarServiceTest extends TestCase {
    public function testGetCurrentSemester() {
        $semester = AcademicCalendarService::getCurrentSemester();
        $this->assertContains($semester, ["1st Semester", "2nd Semester"]);
        
        // If we want to be more specific, we can check based on current real month
        $currentMonth = (int)date('n');
        if ($currentMonth >= 8 || $currentMonth <= 1) {
            $this->assertEquals("1st Semester", $semester);
        } else {
            $this->assertEquals("2nd Semester", $semester);
        }
    }
}
