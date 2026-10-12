<?php
/**
 * DFMS Document Pattern Registry for OCR Field Extraction
 * 
 * Defines regex extraction patterns per document classification type:
 * - Admission Letter (JAMB / Institutional)
 * - Senior School Certificate (WAEC / NECO / NABTEB)
 * - Birth Certificate / National Population Commission (NPC)
 * - Local Government Identification / Certificate of Origin
 * - Reference Letter / Recommendation
 */

return [
    'admission_letter' => [
        'name' => 'Admission Letter',
        'keywords' => ['admission', 'jamb', 'provisional', 'faculty', 'department', 'matriculation'],
        'fields' => [
            'registration_number' => [
                'pattern' => '/(?:registration\s*(?:no|number)|reg\s*(?:no|number)|jamb\s*reg(?:\s*no)?|matric(?:ulation)?\s*(?:no|number)?)\s*[:.\-]?\s*([A-Z0-9\/]{5,20})/i',
                'description' => 'JAMB or Institutional Registration / Matric Number'
            ],
            'candidate_name' => [
                'pattern' => '/(?:candidate(?:\'?s)?\s*name|name\s*of\s*candidate|student(?:\'?s)?\s*name|full\s*name)\s*[:.\-]?\s*([A-Za-z\t ,\.\-]{3,60})/i',
                'description' => 'Full Candidate Name'
            ],
            'academic_session' => [
                'pattern' => '/(?:academic\s*session|session|year\s*of\s*admission)\s*[:.\-]?\s*(\d{4}\s*\/\s*\d{4}|\d{4})/i',
                'description' => 'Academic Session'
            ],
            'reference_number' => [
                'pattern' => '/(?:ref(?:erence)?(?:\s*(?:no|number))?)\s*[:.\-]?\s*([A-Z0-9\/\-]{5,30})/i',
                'description' => 'Reference / Letter Number'
            ]
        ]
    ],

    'waec_neco_certificate' => [
        'name' => 'O\'Level Certificate (WAEC/NECO/NABTEB)',
        'keywords' => ['waec', 'neco', 'nabteb', 'west african examinations', 'senior school certificate', 'wassce', 'ssce', 'statement of result', 'result'],
        'fields' => [
            'candidate_number' => [
                'pattern' => '/(?:candidate\s*(?:no|number)|examination\s*(?:no|number)|exam\s*(?:no|number)|index\s*(?:no|number))\s*[:.\-]?\s*([A-Z0-9]{8,12})/i',
                'description' => 'Candidate / Exam Number'
            ],
            'candidate_name' => [
                'pattern' => '/(?:this\s*is\s*to\s*certify\s*that|candidate(?:\'?s)?\s*name|name)\s*[:.\-]?\s*([A-Za-z\t ,\.\-]{3,60})/i',
                'description' => 'Candidate Name'
            ],
            'exam_year' => [
                'pattern' => '/(?:held\s*in|year\s*of\s*examination|examination\s*year|exam\s*year|session|year)\s*[:.\-]?\s*([12]\d{3})/i',
                'description' => 'Examination Year'
            ],
            'certificate_number' => [
                'pattern' => '/(?:certificate\s*(?:no|number)|cert\s*(?:no|number)|ref(?:erence)?(?:\s*(?:no|number))?)\s*[:.\-]?\s*([A-Z0-9\/\-]{6,30})/i',
                'description' => 'Certificate Number'
            ]
        ]
    ],

    'birth_certificate' => [
        'name' => 'Birth Certificate / NPC Attestation',
        'keywords' => ['birth', 'population commission', 'national population', 'born', 'attestation of birth', 'certificate of birth'],
        'fields' => [
            'registration_number' => [
                'pattern' => '/(?:entry\s*(?:no|number)|certificate\s*(?:no|number)|registration\s*(?:no|number)|cert\s*(?:no|number)|reg\s*(?:no|number)|b\s*(?:no|number))\s*[:.\-]?\s*([A-Z0-9\/\-]{5,30})/i',
                'description' => 'Birth Registration Number'
            ],
            'child_name' => [
                'pattern' => '/(?:name\s*of\s*child|child(?:\'?s)?\s*name|full\s*name\s*of\s*child|this\s*is\s*to\s*certify\s*that|name)\s*[:.\-]?\s*([A-Za-z\t ,\.\-]{3,60})/i',
                'description' => 'Full Name of Child'
            ],
            'date_of_birth' => [
                'pattern' => '/(?:date\s*of\s*birth|born\s*on|born\s*at)\s*[:.\-]?\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}|\d{1,2}(?:st|nd|rd|th)?(?:\s+day\s+of)?\s+(?:january|february|march|april|may|june|july|august|september|october|november|december)[,\s]+\d{4})/i',
                'description' => 'Date of Birth'
            ]
        ]
    ],

    'lga_certificate' => [
        'name' => 'LGA Certificate of Origin / State Identification',
        'keywords' => ['local government', 'lga', 'indigene', 'origin', 'state of origin', 'citizen', 'identification'],
        'fields' => [
            'candidate_name' => [
                'pattern' => '/(?:certify\s*that|name\s*of\s*indigene|full\s*name|bearer)\s*[:.\-]?\s*([A-Za-z\t ,\.\-]{3,60})/i',
                'description' => 'Indigene Full Name'
            ],
            'lga_name' => [
                'pattern' => '/(?:local\s*government(?:\s*area)?|lga)\s*[:.\-]?\s*([A-Za-z\t ]{3,35})/i',
                'description' => 'Local Government Area'
            ],
            'state_of_origin' => [
                'pattern' => '/(?:state\s*of\s*origin|state)\s*[:.\-]?\s*([A-Za-z\t ]{3,25}(?:\s*state)?)/i',
                'description' => 'State of Origin'
            ],
            'reference_number' => [
                'pattern' => '/(?:ref(?:erence)?(?:\s*(?:no|number))?)\s*[:.\-]?\s*([A-Z0-9\/\-]{5,30})/i',
                'description' => 'Reference / Certificate Number'
            ],
            'date_of_issue' => [
                'pattern' => '/(?:date(?:\s*of\s*issue)?|issued\s*on)\s*[:.\-]?\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}|\d{1,2}(?:st|nd|rd|th)?(?:\s+day\s+of)?\s+(?:january|february|march|april|may|june|july|august|september|october|november|december)[,\s]+\d{4}|\d{4})/i',
                'description' => 'Date or Year of Issue'
            ]
        ]
    ],

    'reference_letter' => [
        'name' => 'Letter of Reference / Recommendation',
        'keywords' => ['reference', 'recommendation', 'referee', 'attestation', 'character'],
        'fields' => [
            'candidate_name' => [
                'pattern' => '/(?:re(?:\s*[:.\-])|re:\s*|reference\s*(?:for|on)|concerning|attestation\s*on)\s*[:.\-]?\s*([A-Za-z\t ,\.\-]{3,60})/i',
                'description' => 'Candidate Name'
            ],
            'matric_number' => [
                'pattern' => '/(?:matric(?:\s*no|\s*number)?|registration\s*(?:no|number))\s*[:.\-]?\s*([A-Z0-9\/]{5,16})/i',
                'description' => 'Matriculation Number'
            ]
        ]
    ]
];
