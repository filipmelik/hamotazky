<?php
declare (strict_types=1);

namespace App\Entity\Api;

class Question {

    public string $questionId;
    public string $questionCode;
    public string $text;
    public string $topicId;
    public int $points;
    public array $answers;

    /**
     * @param string $id
     * @param string $code
     * @param string $text
     * @param int $points
     * @param Answer[] $answers
     * @param string $topicId
     */
    public function __construct(
        string $id,
        string $code,
        string $text,
        int $points,
        array $answers,
        string $topicId,
    ) {
        $this->questionId = $id;
        $this->questionCode = $code;
        $this->text = $text;
        $this->points = $points;
        $this->answers = $answers;
        $this->topicId = $topicId;
    }

}