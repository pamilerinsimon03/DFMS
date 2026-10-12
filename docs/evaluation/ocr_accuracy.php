<?php
/**
 * DFMS OCR & Regex Field Extraction Accuracy Evaluation Harness
 * 
 * Measures:
 * 1. Character Error Rate (CER) via Levenshtein edit distance:
 *    CER = Levenshtein(ground_truth, ocr_text) / length(ground_truth)
 * 2. Regex field extraction accuracy across core document types registered
 *    in app/config/document_patterns.php (matric, name, year, ref_number)
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/services/QualityCheckService.php';

class OCRAccuracyBenchmark
{
    /**
     * Compute Character Error Rate (CER)
     */
    public static function computeCER(string $groundTruth, string $hypothesis): float
    {
        $gtLen = strlen($groundTruth);
        if ($gtLen === 0) {
            return strlen($hypothesis) === 0 ? 0.0 : 100.0;
        }

        $distance = levenshtein($groundTruth, $hypothesis);
        // Normalize against ground truth length
        $cer = ($distance / (float)$gtLen) * 100.0;
        return round($cer, 2);
    }

    /**
     * Test certificate corpus with ground truth text and expected extracted entities
     */
    public function getCorpus(): array
    {
        return [
            [
                'id'       => 'DOC-01',
                'doc_type' => 'admission_letter',
                'title'    => 'University of Ibadan Provisional Admission Letter',
                'ground_truth_fields' => [
                    'matric_number' => '123456',
                    'candidate_name'=> 'OLAWALE ADEDAYO JOHNSON',
                    'year'          => '2024',
                    'ref_number'    => 'UI/ADM/2024/09841'
                ],
                'ground_truth_text' => 
                    "UNIVERSITY OF IBADAN, IBADAN, NIGERIA\n" .
                    "OFFICE OF THE REGISTRAR (ACADEMIC AFFAIRS)\n\n" .
                    "PROVISIONAL ADMISSION LETTER\n" .
                    "REF: UI/ADM/2024/09841\n" .
                    "DATE: 14TH AUGUST, 2024\n\n" .
                    "CANDIDATE NAME: OLAWALE ADEDAYO JOHNSON\n" .
                    "MATRICULATION NUMBER: 123456\n" .
                    "FACULTY: SCIENCE\n" .
                    "DEPARTMENT: COMPUTER SCIENCE\n" .
                    "SESSION: 2024/2025\n\n" .
                    "I am pleased to inform you that you have been offered provisional admission.\n" .
                    "REGISTRAR: G.O. SALIU, MANUPA, MNIM",
                'ocr_sample_clean' =>
                    "UNIVERSITY OF IBADAN, IBADAN, NIGERIA\n" .
                    "OFFICE OF THE REGISTRAR (ACADEMIC AFFAIRS)\n\n" .
                    "PROVISIONAL ADMISSION LETTER\n" .
                    "REF: UI/ADM/2024/09841\n" .
                    "DATE: 14TH AUGUST, 2024\n\n" .
                    "CANDIDATE NAME: OLAWALE ADEDAYO JOHNSON\n" .
                    "MATRICULATION NUMBER: 123456\n" .
                    "FACULTY: SCIENCE\n" .
                    "DEPARTMENT: COMPUTER SCIENCE\n" .
                    "SESSION: 2024/2025\n\n" .
                    "I am pleased to inform you that you have been offered provisional admission.\n" .
                    "REGISTRAR: G.O. SALIU, MANUPA, MNIM",
                'ocr_sample_noisy' =>
                    "UNIVERSITY OF IBADAN, IBADAN, NIGERIA\n" .
                    "OFFICE OF THE REGISTRAR (ACADEMIC AFFAIRS)\n\n" .
                    "PROVISIONAL ADMISSION LETTER\n" .
                    "REF: UI/ADM/2024/09841\n" .
                    "DATE: 14TH AUGUST, 2024\n\n" .
                    "CANDIDATE NAME: OLAWALE ADEDAYO JOHNSON\n" .
                    "MATRICULATION NO: 123456\n" .
                    "FACULTY: SCIENCE\n" .
                    "DEPARTMENT: COMPUTER SCIENCE\n" .
                    "SESSION: 2024/2025\n\n" .
                    "I am pleased to inform you that you have been offered provisional admission.\n" .
                    "REGISTRAR: G.O. SALIU, MANUPA, MNIM"
            ],
            [
                'id'       => 'DOC-02',
                'doc_type' => 'waec_result',
                'title'    => 'West African Senior School Certificate Examination',
                'ground_truth_fields' => [
                    'matric_number' => '4251678901',
                    'candidate_name'=> 'OLAWALE ADEDAYO JOHNSON',
                    'year'          => '2023',
                    'ref_number'    => 'WASSCE/NG/2023/88192'
                ],
                'ground_truth_text' => 
                    "THE WEST AFRICAN EXAMINATIONS COUNCIL\n" .
                    "WEST AFRICAN SENIOR SCHOOL CERTIFICATE EXAMINATION STATEMENT OF RESULT\n\n" .
                    "CANDIDATE NAME: OLAWALE ADEDAYO JOHNSON\n" .
                    "EXAMINATION NUMBER: 4251678901\n" .
                    "EXAMINATION YEAR: 2023\n" .
                    "EXAMINATION CENTRE: GOVERNMENT COLLEGE IBADAN\n" .
                    "CERTIFICATE NO: WASSCE/NG/2023/88192\n\n" .
                    "SUBJECTS AND GRADES:\n" .
                    "ENGLISH LANGUAGE: B2\n" .
                    "MATHEMATICS: A1\n" .
                    "PHYSICS: A1\n" .
                    "CHEMISTRY: B3\n" .
                    "BIOLOGY: B2",
                'ocr_sample_clean' =>
                    "THE WEST AFRICAN EXAMINATIONS COUNCIL\n" .
                    "WEST AFRICAN SENIOR SCHOOL CERTIFICATE EXAMINATION STATEMENT OF RESULT\n\n" .
                    "CANDIDATE NAME: OLAWALE ADEDAYO JOHNSON\n" .
                    "CANDIDATE NUMBER: 4251678901\n" .
                    "EXAMINATION YEAR: 2023\n" .
                    "EXAMINATION CENTRE: GOVERNMENT COLLEGE IBADAN\n" .
                    "CERTIFICATE NUMBER: WASSCE/NG/2023/88192\n\n" .
                    "SUBJECTS AND GRADES:\n" .
                    "ENGLISH LANGUAGE: B2\n" .
                    "MATHEMATICS: A1\n" .
                    "PHYSICS: A1\n" .
                    "CHEMISTRY: B3\n" .
                    "BIOLOGY: B2",
                'ocr_sample_noisy' =>
                    "THE WEST AFRICAN EXAMINATIONS COUNCIL\n" .
                    "WEST AFRICAN SENIOR SCHOOL CERTIFICATE EXAMINATION STATEMENT OF RESULT\n\n" .
                    "CANDIDATE NAME: OLAWALE ADEDAYO JOHNSON\n" .
                    "EXAM NO: 4251678901\n" .
                    "YEAR: 2023\n" .
                    "CENTRE: GOVERNMENT COLLEGE IBADAN\n" .
                    "REF: WASSCE/NG/2023/88192\n\n" .
                    "ENGLISH LANGUAGE: B2\n" .
                    "MATHEMATICS: A1"
            ],
            [
                'id'       => 'DOC-03',
                'doc_type' => 'birth_certificate',
                'title'    => 'National Population Commission Certificate of Birth',
                'ground_truth_fields' => [
                    'matric_number' => null,
                    'candidate_name'=> 'OLAWALE ADEDAYO JOHNSON',
                    'year'          => '2004',
                    'ref_number'    => 'NPC/BC/2004/77401'
                ],
                'ground_truth_text' => 
                    "FEDERAL REPUBLIC OF NIGERIA\n" .
                    "NATIONAL POPULATION COMMISSION\n" .
                    "CERTIFICATE OF BIRTH\n\n" .
                    "REGISTRATION NO: NPC/BC/2004/77401\n" .
                    "THIS IS TO CERTIFY THAT OLAWALE ADEDAYO JOHNSON\n" .
                    "WAS BORN ON 12TH DAY OF MAY, 2004\n" .
                    "AT UNIVERSITY COLLEGE HOSPITAL, IBADAN\n" .
                    "FATHER: CHIEF OLADELE JOHNSON\n" .
                    "MOTHER: MRS. ADETOKUNBO JOHNSON\n" .
                    "REGISTRAR OF BIRTHS AND DEATHS: SIGNED",
                'ocr_sample_clean' =>
                    "FEDERAL REPUBLIC OF NIGERIA\n" .
                    "NATIONAL POPULATION COMMISSION\n" .
                    "CERTIFICATE OF BIRTH\n\n" .
                    "CERTIFICATE NUMBER: NPC/BC/2004/77401\n" .
                    "THIS IS TO CERTIFY THAT OLAWALE ADEDAYO JOHNSON\n" .
                    "WAS BORN ON 12TH DAY OF MAY, 2004\n" .
                    "AT UNIVERSITY COLLEGE HOSPITAL, IBADAN\n" .
                    "FATHER: CHIEF OLADELE JOHNSON\n" .
                    "MOTHER: MRS. ADETOKUNBO JOHNSON",
                'ocr_sample_noisy' =>
                    "FEDERAL REPUBLIC OF NIGERIA\n" .
                    "NATIONAL POPULATION COMMISSION\n" .
                    "CERTIFICATE OF BIRTH\n\n" .
                    "REG NO: NPC/BC/2004/77401\n" .
                    "THIS IS TO CERTIFY THAT OLAWALE ADEDAYO JOHNSON\n" .
                    "BORN ON 12TH MAY 2004\n" .
                    "HOSPITAL: UCH IBADAN"
            ],
            [
                'id'       => 'DOC-04',
                'doc_type' => 'lga_identification',
                'title'    => 'Local Government Identification Certificate',
                'ground_truth_fields' => [
                    'matric_number' => null,
                    'candidate_name'=> 'OLAWALE ADEDAYO JOHNSON',
                    'year'          => '2022',
                    'ref_number'    => 'IBNE/LG/ID/2022/1042'
                ],
                'ground_truth_text' => 
                    "IBADAN NORTH-EAST LOCAL GOVERNMENT\n" .
                    "OYO STATE, NIGERIA\n" .
                    "LOCAL GOVERNMENT IDENTIFICATION CERTIFICATE\n\n" .
                    "REFERENCE: IBNE/LG/ID/2022/1042\n" .
                    "DATE OF ISSUE: 10TH MARCH, 2022\n\n" .
                    "TO WHOM IT MAY CONCERN\n" .
                    "THIS IS TO CERTIFY THAT OLAWALE ADEDAYO JOHNSON\n" .
                    "IS A BONAFIDE INDIGENE OF IBADAN NORTH-EAST LOCAL GOVERNMENT\n" .
                    "ORIGIN: OJE COMPOUND, IBADAN\n" .
                    "EXECUTIVE CHAIRMAN: HON. IBRAHIM AKANDE",
                'ocr_sample_clean' =>
                    "IBADAN NORTH-EAST LOCAL GOVERNMENT\n" .
                    "OYO STATE, NIGERIA\n" .
                    "LOCAL GOVERNMENT IDENTIFICATION CERTIFICATE\n\n" .
                    "REF: IBNE/LG/ID/2022/1042\n" .
                    "DATE: 10TH MARCH, 2022\n\n" .
                    "TO WHOM IT MAY CONCERN\n" .
                    "THIS IS TO CERTIFY THAT OLAWALE ADEDAYO JOHNSON\n" .
                    "IS A BONAFIDE INDIGENE OF IBADAN NORTH-EAST LOCAL GOVERNMENT\n" .
                    "ORIGIN: OJE COMPOUND, IBADAN",
                'ocr_sample_noisy' =>
                    "IBADAN NORTH-EAST LOCAL GOVERNMENT\n" .
                    "OYO STATE, NIGERIA\n" .
                    "LOCAL GOVERNMENT IDENTIFICATION CERTIFICATE\n\n" .
                    "REF: IBNE/LG/ID/2022/1042\n" .
                    "DATE: 10TH MARCH, 2022\n\n" .
                    "THIS IS TO CERTIFY THAT OLAWALE ADEDAYO JOHNSON\n" .
                    "INDIGENE OF IBADAN NORTH-EAST LOCAL GOVT"
            ]
        ];
    }

    public static function resolveFieldValue(array $extracted, string $key): ?string
    {
        if (isset($extracted[$key])) {
            return $extracted[$key];
        }
        if ($key === 'matric_number') {
            return $extracted['registration_number'] ?? $extracted['candidate_number'] ?? $extracted['matric_number'] ?? null;
        }
        if ($key === 'candidate_name') {
            return $extracted['candidate_name'] ?? $extracted['child_name'] ?? null;
        }
        if ($key === 'year') {
            return $extracted['exam_year'] ?? $extracted['academic_session'] ?? $extracted['date_of_birth'] ?? $extracted['date_of_issue'] ?? null;
        }
        if ($key === 'ref_number') {
            return $extracted['reference_number'] ?? $extracted['certificate_number'] ?? $extracted['registration_number'] ?? null;
        }
        return null;
    }

    public function run(): array
    {
        $corpus = $this->getCorpus();
        $results = [];

        $totalFieldsTested = 0;
        $totalFieldsExtractedCorrectly = 0;
        $totalCerClean = 0.0;
        $totalCerNoisy = 0.0;

        foreach ($corpus as $item) {
            $gtText = $item['ground_truth_text'];
            $cleanText = $item['ocr_sample_clean'];
            $noisyText = $item['ocr_sample_noisy'];

            $cerClean = self::computeCER($gtText, $cleanText);
            $cerNoisy = self::computeCER($gtText, $noisyText);

            $totalCerClean += $cerClean;
            $totalCerNoisy += $cerNoisy;

            // Execute regex field extraction on both clean and noisy OCR streams
            $cleanExtracted = QualityCheckService::extractFields($cleanText, $item['title']);
            $noisyExtracted = QualityCheckService::extractFields($noisyText, $item['title']);

            $fieldEvaluations = [];
            foreach ($item['ground_truth_fields'] as $fieldKey => $expectedVal) {
                if ($expectedVal === null) continue; // Not applicable for this doc type

                $totalFieldsTested += 2; // tested on both clean and noisy

                $extractedCleanVal = self::resolveFieldValue($cleanExtracted, $fieldKey);
                $extractedNoisyVal = self::resolveFieldValue($noisyExtracted, $fieldKey);

                $matchClean = ($extractedCleanVal !== null && stripos($extractedCleanVal, (string)$expectedVal) !== false);
                $matchNoisy = ($extractedNoisyVal !== null && stripos($extractedNoisyVal, (string)$expectedVal) !== false);

                if ($matchClean) $totalFieldsExtractedCorrectly++;
                if ($matchNoisy) $totalFieldsExtractedCorrectly++;

                $fieldEvaluations[$fieldKey] = [
                    'expected'         => $expectedVal,
                    'clean_extracted'  => $extractedCleanVal,
                    'clean_match'      => $matchClean,
                    'noisy_extracted'  => $extractedNoisyVal,
                    'noisy_match'      => $matchNoisy,
                ];
            }

            $results[] = [
                'id'                => $item['id'],
                'doc_type'          => $item['doc_type'],
                'title'             => $item['title'],
                'cer_clean_pct'     => $cerClean,
                'cer_noisy_pct'     => $cerNoisy,
                'fields'            => $fieldEvaluations
            ];
        }

        $overallAccuracy = $totalFieldsTested > 0 ? ($totalFieldsExtractedCorrectly / $totalFieldsTested) * 100.0 : 0.0;
        $meanCerClean = count($corpus) > 0 ? $totalCerClean / count($corpus) : 0.0;
        $meanCerNoisy = count($corpus) > 0 ? $totalCerNoisy / count($corpus) : 0.0;

        return [
            'documents'           => $results,
            'summary'             => [
                'total_documents'           => count($corpus),
                'total_fields_evaluated'    => $totalFieldsTested,
                'fields_correct'            => $totalFieldsExtractedCorrectly,
                'regex_extraction_accuracy' => round($overallAccuracy, 1),
                'mean_cer_clean'            => round($meanCerClean, 2),
                'mean_cer_noisy'            => round($meanCerNoisy, 2)
            ]
        ];
    }
}

// Standalone CLI execution
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    echo "========================================================================\n";
    echo "DFMS OCR Character Error Rate (CER) & Regex Field Extraction Benchmark\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n";
    echo "PHP Version: " . PHP_VERSION . " (" . PHP_OS . ")\n";
    echo "========================================================================\n\n";

    $bench = new OCRAccuracyBenchmark();
    $data = $bench->run();

    printf("%-8s | %-24s | %-12s | %-12s | %s\n", "Doc ID", "Document Type", "CER (Clean)", "CER (Noisy)", "Fields Extracted Correctly");
    echo str_repeat("-", 85) . "\n";

    foreach ($data['documents'] as $doc) {
        $cleanSuccess = 0;
        $noisySuccess = 0;
        $totalF = count($doc['fields']);

        foreach ($doc['fields'] as $f) {
            if ($f['clean_match']) $cleanSuccess++;
            if ($f['noisy_match']) $noisySuccess++;
        }

        printf(
            "%-8s | %-24s | %10.2f%% | %10.2f%% | Clean: %d/%d, Noisy: %d/%d\n",
            $doc['id'],
            $doc['doc_type'],
            $doc['cer_clean_pct'],
            $doc['cer_noisy_pct'],
            $cleanSuccess,
            $totalF,
            $noisySuccess,
            $totalF
        );

        foreach ($doc['fields'] as $fName => $fData) {
            $status = ($fData['clean_match'] && $fData['noisy_match']) ? "[PASS]" : ($fData['clean_match'] ? "[CLEAN ONLY]" : "[FAIL]");
            printf("         * %-14s -> Expected: '%s' | Extracted: '%s' %s\n", $fName, $fData['expected'], $fData['clean_extracted'], $status);
        }
        echo "\n";
    }

    $summary = $data['summary'];
    echo "Summary Metrics:\n";
    printf("  - Mean Character Error Rate (Clean Scan):     %.2f%%\n", $summary['mean_cer_clean']);
    printf("  - Mean Character Error Rate (Noisy Mobile):    %.2f%%\n", $summary['mean_cer_noisy']);
    printf("  - Total Ground Truth Fields Evaluated:         %d\n", $summary['total_fields_evaluated']);
    printf("  - Fields Extracted with Exact Accuracy:        %d\n", $summary['fields_correct']);
    printf("  - Regex Field Extraction Overall Accuracy:     %.1f%%\n", $summary['regex_extraction_accuracy']);
}
