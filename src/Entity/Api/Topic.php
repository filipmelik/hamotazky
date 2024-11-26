<?php
declare (strict_types=1);

namespace App\Entity\Api;

class Topic {

    public string $topicId;
    public string $name;
    public string $topicSlug;
    public int $questionCount;
    public string $topicGroupId;

    /**
     * @param string  $id
     * @param string  $name
     * @param string  $topicSlug
     * @param integer $questionCount
     * @param string  $topicGroupId
     */
    public function __construct(
        string $id,
        string $name,
        string $topicSlug,
        int $questionCount,
        string $topicGroupId,
    ) {
        $this->topicId = $id;
        $this->name = $name;
        $this->topicSlug = $topicSlug;
        $this->questionCount = $questionCount;
        $this->topicGroupId = $topicGroupId;
    }

}