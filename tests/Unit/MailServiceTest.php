<?php
// Mock the mail function in the global namespace (or relative if needed)
// Depending on how MailService calls it. MailService calls global `mail()`.
// To mock a global function used in a class, we need to redefine it in the namespace of the class being tested IF that class is namespaced.
// BUT MailService is not namespaced (it's in global).
// Mocking global functions in global namespace is hard without runkit.
// However, we can declare a namespace for the TEST and include MailService.
// If MailService is global, it calls \mail(). We can't override \mail easily.

// ALTERNATIVE STRATEGY:
// Since we can't easily mock global `mail()` without extensions, we will refactor MailService slightly to use a sender delegate OR just test the HTML generation part if possible.
// `MailService::wrapMessage` is private.
// `MailService::send` returns the result of `mail()`.

// Let's try a simpler approach used in legacy apps: 
// define a custom function `custom_mail` and have MailService use it if a flag is set?
// OR, use the "namespace trick" assuming we can wrap the class?
// Actually, since MailService is just a file `class MailService`, if I include it inside a namespace in the test file, it doesn't change where it thinks it is (global).

// Refactoring Strategy for Testability:
// I will modify `MailService.php` to accept a `Transport` callable or dependency.
// BUT I should avoid modifying app code if possible.
// Let's assume for now I will skip mocking `mail()` and just test the `wrapMessage` logic if I can access it (Reflection).

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/services/MailService.php';

class MailServiceTest extends TestCase {
    
    public function testWrapMessageFormat() {
        // Use reflection to test private method
        $service = new MailService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('wrapMessage');
        $method->setAccessible(true);
        
        $title = "Test Subject";
        $content = "Test Body";
        
        $result = $method->invoke(null, $title, $content);
        
        $this->assertStringContainsString('<html>', $result);
        $this->assertStringContainsString('Test Subject', $result);
        $this->assertStringContainsString('Test Body', $result);
        $this->assertStringContainsString('background: #01017A', $result); // Check styling
    }
}
