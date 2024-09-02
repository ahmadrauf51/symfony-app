<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TeamHierarchyController extends AbstractController
{
    #[Route('/team-hierarchy', name: 'team_hierarchy', methods: ['POST'])]
    public function index(Request $request): JsonResponse
    {
        $apiToken = $request->headers->get('Authorization');
        if ($apiToken !== 'Bearer ' . $_ENV['API_TOKEN']) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $file = $request->files->get('csv_file');
        if (!$file || $file->getClientOriginalExtension() !== 'csv') {
            return new JsonResponse(['error' => 'Invalid file'], 400);
        }

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

        // TODO: Build hierarchy
        $tree = [];
        foreach ($teams as &$team) {
            if (empty($team['parentTeam'])) {
                $tree[$team['teamName']] = &$team;
            } else {
                $teams[$team['parentTeam']]['teams'][$team['teamName']] = &$team;
            }
        }

        // TODO: Filter if _q is present
        $q = $request->query->get('_q');
        if ($q && isset($teams[$q])) {
            $filteredTree = $this->filterTree($teams[$q], $teams);
            return new JsonResponse($filteredTree);
        }

        return new JsonResponse($tree);
    }

    private function filterTree($team, $teams)
    {
        $parentTeam = $teams[$team['parentTeam']] ?? null;
        if ($parentTeam) {
            $parentTeam['teams'] = [$team['teamName'] => $team];
            return $this->filterTree($parentTeam, $teams);
        }

        return [$team['teamName'] => $team];
    }
}
