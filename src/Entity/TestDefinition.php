<?php
declare (strict_types=1);

namespace App\Entity;

class TestDefinition {

    const TEST_DEFINITION_HAREC = [
        '1' => [
            ['topicSet' => ['1'], 'questionCount' => 6],
            ['topicSet' => ['2'], 'questionCount' => 2],
            ['topicSet' => ['3'], 'questionCount' => 12],
        ],
        '2' => [
            ['topicSet' => ['4'], 'questionCount' => 6],
            ['topicSet' => ['5', '6'], 'questionCount' => 3],
            ['topicSet' => ['7'], 'questionCount' => 4],
            ['topicSet' => ['8'], 'questionCount' => 4],
            ['topicSet' => ['9'], 'questionCount' => 15],
            ['topicSet' => ['10'], 'questionCount' => 8],
        ],
        '3' => [
            ['topicSet' => ['11'], 'questionCount' => 4],
            ['topicSet' => ['12'], 'questionCount' => 2],
            ['topicSet' => ['13'], 'questionCount' => 4],
            ['topicSet' => ['14'], 'questionCount' => 2],
            ['topicSet' => ['15'], 'questionCount' => 2],
            ['topicSet' => ['16'], 'questionCount' => 1],
            ['topicSet' => ['17'], 'questionCount' => 1],
            ['topicSet' => ['18'], 'questionCount' => 1],
            ['topicSet' => ['19'], 'questionCount' => 2],
            ['topicSet' => ['20'], 'questionCount' => 1],
        ],
    ];

    const TEST_DEFINITION_NOVICE = [
        '1' => [
            ['topicSet' => ['1'], 'questionCount' => 6],
            ['topicSet' => ['2'], 'questionCount' => 2],
            ['topicSet' => ['3'], 'questionCount' => 12],
        ],
        '2' => [
            ['topicSet' => ['4'], 'questionCount' => 6],
            ['topicSet' => ['5', '6'], 'questionCount' => 3],
            ['topicSet' => ['7'], 'questionCount' => 4],
            ['topicSet' => ['8'], 'questionCount' => 4],
            ['topicSet' => ['9'], 'questionCount' => 15],
            ['topicSet' => ['10'], 'questionCount' => 8],
        ],
        '3' => [
            ['topicSet' => ['11'], 'questionCount' => 4],
            ['topicSet' => ['12'], 'questionCount' => 2],
            ['topicSet' => ['13'], 'questionCount' => 4],
            ['topicSet' => ['14', '15', '16'], 'questionCount' => 2],
            ['topicSet' => ['17'], 'questionCount' => 2],
            ['topicSet' => ['18'], 'questionCount' => 2],
            ['topicSet' => ['19'], 'questionCount' => 2],
            ['topicSet' => ['20'], 'questionCount' => 2],
        ],
    ];

    const LICENCE_CLASS_TO_TEST_DEFINITION = [
        LicenceClass::CLASS_A => self::TEST_DEFINITION_HAREC,
        LicenceClass::CLASS_N => self::TEST_DEFINITION_NOVICE,
    ];

}