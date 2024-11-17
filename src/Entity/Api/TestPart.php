<?php
declare (strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\TopicGroup;
use App\Entity\Api\Question;

class TestPart {

    public TopicGroup $topicGroup;
    public array $topicGroupQuestions;

    /**
     * @param TopicGroup $topicGroup
     * @param Question[] $topicGroupQuestions
     */
    public function __construct(
        TopicGroup $topicGroup,
        array $topicGroupQuestions,
    ) {
        $this->topicGroup = $topicGroup;
        $this->topicGroupQuestions = $topicGroupQuestions;
    }

}