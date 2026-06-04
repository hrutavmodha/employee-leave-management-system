<?php

namespace Tests\Feature;

use App\Services\ReportService;
use Tests\TestCase;

/**
 * Tests for Correctness Gap C#4:
 *
 * The dead `monthExtractionSql()` private method and its reflection-based
 * unit tests must not exist. This test verifies via reflection that the
 * dead code has been removed and does not regress.
 */
class CorrectnessGapC4DeadCodeTest extends TestCase
{
    /**
     * ReportService must NOT contain a method named `monthExtractionSql`.
     * That method was dead code (never called) and should have been removed.
     */
    public function test_report_service_has_no_month_extraction_sql_method(): void
    {
        $reflection = new \ReflectionClass(ReportService::class);

        $this->assertFalse(
            $reflection->hasMethod('monthExtractionSql'),
            'ReportService still contains dead method monthExtractionSql(). Remove it.'
        );
    }

    /**
     * ReportService should only contain public methods that are actually
     * used: getEmployeeReport, getDepartmentReport, getMonthlyStats.
     * No leftover private helpers should exist.
     */
    public function test_report_service_has_only_expected_public_methods(): void
    {
        $reflection = new \ReflectionClass(ReportService::class);

        $publicMethods = array_filter(
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
            fn (\ReflectionMethod $m) => $m->getDeclaringClass()->getName() === ReportService::class
        );

        $methodNames = array_map(
            fn (\ReflectionMethod $m) => $m->getName(),
            $publicMethods
        );

        sort($methodNames);

        $expected = ['getDepartmentReport', 'getEmployeeReport', 'getMonthlyStats'];

        $this->assertEquals(
            $expected,
            $methodNames,
            'ReportService has unexpected public methods: ' . implode(', ', array_diff($methodNames, $expected))
        );
    }

    /**
     * The MonthlyStatsPortabilityTest file must NOT contain reflection-based
     * tests that access private methods. Such tests are fragile and test
     * implementation details rather than behavior.
     */
    public function test_monthly_stats_portability_test_has_no_reflection_access(): void
    {
        $testFile = base_path('tests/Unit/MonthlyStatsPortabilityTest.php');

        if (!file_exists($testFile)) {
            $this->markTestSkipped('MonthlyStatsPortabilityTest.php does not exist (already removed).');
        }

        $contents = file_get_contents($testFile);

        $this->assertStringNotContainsString(
            'ReflectionMethod',
            $contents,
            'MonthlyStatsPortabilityTest still uses ReflectionMethod to access private methods.'
        );

        $this->assertStringNotContainsString(
            'setAccessible',
            $contents,
            'MonthlyStatsPortabilityTest still calls setAccessible() on private methods.'
        );

        $this->assertStringNotContainsString(
            'monthExtractionSql',
            $contents,
            'MonthlyStatsPortabilityTest still references the dead monthExtractionSql method.'
        );
    }
}
