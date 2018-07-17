<?php

namespace Technodelight\Jira\Connector\Tempo2;

use DateTime;
use ICanBoogie\Storage\Storage;
use Technodelight\Jira\Api\Tempo2\Api;
use Technodelight\Jira\Connector\WorklogHandler as WorklogHandlerInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\WorklogCollection;
use Technodelight\Jira\Helper\DateHelper;

class WorklogHandler implements WorklogHandlerInterface
{
    const DATETIME_FORMAT = 'Y-m-d';
    /**
     * @var Api
     */
    private $api;
    /**
     * @var \ICanBoogie\Storage\Storage
     */
    private $storage;

    public function __construct(Api $api, Storage $storage)
    {
        $this->api = $api;
        $this->storage = $storage;
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @return WorklogCollection
     */
    public function find(DateTime $from, DateTime $to)
    {
        $worklogs = $this->api->find(
            $from->format(self::DATETIME_FORMAT),
            $to->format(self::DATETIME_FORMAT)
        );

        $collection = WorklogCollection::createEmpty();
        foreach ($worklogs as $worklog) {
            $collection->push(
                $this->worklogFromTempoArray($worklog)
            );
        }
        return $collection;
    }

    /**
     * @param Issue $issue
     * @param null $limit
     * @return WorklogCollection
     */
    public function findByIssue(Issue $issue, $limit = null)
    {
        if ($limit) {
            $from = date(self::DATETIME_FORMAT, strtotime(sprintf('-%d days', $limit)));
        } else {
            $from = $issue->created()->format(self::DATETIME_FORMAT);
        }
        $to = date(self::DATETIME_FORMAT);
        $worklogs = array_filter(
            $this->api->find($from, $to),
            function (array $worklog) use ($issue) {
                return $worklog['issue']['key'] == $issue->key();
            }
        );

        $collection = WorklogCollection::createEmpty();
        foreach ($worklogs as $worklog) {
            $collection->push($this->worklogFromTempoArray($worklog));
        }
        return $collection;
    }

    /**
     * @param int $worklogId
     * @return Worklog
     */
    public function retrieve($worklogId)
    {
        $response = $this->api->retrieve($worklogId);
        return $this->worklogFromTempoArray($response);
    }

    /**
     * @param Worklog $worklog
     * @return Worklog
     */
    public function create(Worklog $worklog)
    {
        $response = $this->api->create(
            $worklog->issueKey(),
            $worklog->author()->key(),
            $worklog->date()->format(Api::TEMPO_DATETIME_FORMAT),
            $worklog->timeSpentSeconds(),
            $worklog->comment()
        );
        $this->storage->clear();
        return $this->worklogFromTempoArray($response);
    }

    /**
     * @param Worklog $worklog
     * @return Worklog
     */
    public function update(Worklog $worklog)
    {
        $response = $this->api->update(
            $worklog->id(),
            $worklog->date()->format(Api::TEMPO_DATETIME_FORMAT),
            $worklog->timeSpentSeconds(),
            $worklog->comment()
        );
        $this->storage->clear();
        return $this->worklogFromTempoArray($response);
    }

    /**
     * @param \Technodelight\Jira\Domain\Worklog $worklog
     * @return bool
     */
    public function delete(Worklog $worklog)
    {
        $this->storage->clear();
        return (bool) $this->api->delete($worklog->id());
    }

    /**
     * @param array $worklog
     * @return Worklog
     */
    private function worklogFromTempoArray(array $worklog)
    {
        /*
            self: required (string)
            tempoWorklogId: required (integer)
            jiraWorklogId: (integer)
            issue: required (Issue)
            self: required (string)
            key: required (string)
            timeSpentSeconds: required (number)
            startDate: required (date-only)
            startTime: required (time-only)
            description: required (string)
            createdAt: required (datetime)
            updatedAt: required (datetime)
            author: required (User)
            self: required (string)
            username: required (string)
            displayName: required (string)
            attributes: (attributes)
            self: required (string)
            values: required (array of Work Attribute Value)
            Items: Work Attribute Value
         */
        return Worklog::fromArray([
            'id' => $worklog['tempoWorklogId'],
            'author' => [
                'key' => isset($worklog['author']['username']) ? $worklog['author']['username'] : null,
                'name' => isset($worklog['author']['username']) ? $worklog['author']['username'] : null,
                'displayName' => $worklog['author']['displayName'],
            ],
            'comment' => isset($worklog['description']) ? $worklog['description'] : null,
            'started' => $this->convertDateFormat($worklog['startDate'], $worklog['startTime']),
            'timeSpentSeconds' => $worklog['timeSpentSeconds'],
        ], $worklog['issue']['key']);
    }

    /**
     * @param string $startDate
     * @param string $startTime
     * @return string
     */
    private function convertDateFormat($date, $time)
    {
        return (new DateTime($date . ' ' . $time))->format(DateHelper::FORMAT_FROM_JIRA);
    }
}
