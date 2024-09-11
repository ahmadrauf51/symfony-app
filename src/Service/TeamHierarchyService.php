<?php

namespace App\Service;

class TeamHierarchyService
{
    public function parseCsv($file)
    {
        $csvData = array_map('str_getcsv', file($file->getPathname()));
        $headers = array_shift($csvData);

        $teams = [];
        foreach ($csvData as $row) {
            $teamData = array_combine($headers, $row);
            $teams[$teamData['team']] = [
                'teamName' => $teamData['team'],
                'parentTeam' => $teamData['parent_team'],
                'managerName' => $teamData['manager_name'],
                'businessUnit' => $teamData['business_unit'] ?? '',
                'teams' => []
            ];
        }

        return $teams;
    }

    public function buildTree(array &$teams)
    {
        $tree = [];
        foreach ($teams as &$team) {
            if (empty($team['parentTeam'])) {
                $tree[$team['teamName']] = &$team;
            } else {
                $teams[$team['parentTeam']]['teams'][$team['teamName']] = &$team;
            }
        }
        return $tree;
    }

    public function filterTree($team, $teams)
    {
        $parentTeam = $teams[$team['parentTeam']] ?? null;
        if ($parentTeam) {
            $parentTeam['teams'] = [$team['teamName'] => $team];
            return $this->filterTree($parentTeam, $teams);
        }
        return [$team['teamName'] => $team];
    }
}
