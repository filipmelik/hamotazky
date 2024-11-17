<?php
declare (strict_types=1);

namespace App\Entity\Api;

class Answer {

    public string $answerId;
    public string $text;
    public bool $isCorrect;

    /**
     * @param string $id
     * @param string $text
     * @param bool $isCorrect
     */
    public function __construct(
        string $id,
        string $text,
        bool $isCorrect,
    ) {
        $this->answerId = $id;
        $this->text = $text;
        $this->isCorrect = $isCorrect;
    }

}