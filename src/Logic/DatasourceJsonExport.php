<?php
declare (strict_types=1);

namespace App\Logic;

use App\DataSource\DataSourceV1;

class DatasourceJsonExport {

    private DataSourceV1 $dataSource;

    public function __construct(DataSourceV1 $dataSource)
    {
        $this->dataSource = $dataSource;
    }

    /**
     * Prepare datasource JSON export
     *
     * @return string
     */
    public function getJson(): string 
    {
        $allQuestionIds = $this->dataSource->getAllQuestionIds();
        $questions = $this->dataSource->getQuestionsByIds($allQuestionIds, false);
        $groupedTopics = $this->dataSource->getGroupedTopics();

        $result = [
            'questions'     => [],
            'groupedTopics' => [],
        ];
        foreach ($questions as $q) {
            $result['questions'][] = $q;
        }
        foreach ($groupedTopics as $gt) {
            $result['groupedTopics'][] = $gt;
        }

        return json_encode($result);
    }

}