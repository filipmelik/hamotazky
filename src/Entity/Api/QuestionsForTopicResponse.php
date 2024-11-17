<?php
declare (strict_types=1);

namespace App\Entity\Api;

class QuestionsForTopicResponse {

    public array $questions;
    public Topic $topic;
    public array $licenceClasses;

    /**
     * @param Question[] $questions
     * @param Topic $topic
     * @param string[] $licenceClasses
     */
    public function __construct(
        array $questions,
        Topic $topic,
        array $licenceClasses,
    ) {
        $this->questions = $questions;
        $this->topic = $topic;
        $this->licenceClasses = $licenceClasses;
    }

}