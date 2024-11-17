<?php
declare (strict_types=1);

namespace App\Entity\Api;

class TopicGroup {

    public string $topicGroupId;
    public string $name;
    public array $topics;

    /**
     * @param string $id
     * @param string $name
     * @param Topic $topics
     */
    public function __construct(
        string $id,
        string $name,
        array $topics,
    ) {
        $this->topicGroupId = $id;
        $this->name = $name;
        $this->topics = $topics;
    }

    /**
     * @return string[]
     */
    public function getTopicIds(): array {
        $topicIds = [];

        foreach ($this->topics as $topic) {
            $topicIds[] = (string)$topic->topicId;
        }

        return $topicIds;
    }

}