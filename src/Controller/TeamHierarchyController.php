<?php

namespace App\Controller;

use App\Service\TeamHierarchyService;
use App\Service\TokenAuthenticationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TeamHierarchyController extends AbstractController
{
    private $teamHierarchyService;
    private $tokenAuthenticationService;

    public function __construct(TeamHierarchyService $teamHierarchyService, TokenAuthenticationService $tokenAuthenticationService)
    {
        $this->teamHierarchyService = $teamHierarchyService;
        $this->tokenAuthenticationService = $tokenAuthenticationService;
    }

    #[Route('/team-hierarchy', name: 'team_hierarchy', methods: ['POST'])]
    public function index(Request $request): JsonResponse
    {
        // Validate API Token using the TokenAuthenticationService
        $apiToken = $request->headers->get('Authorization');
        if (!$this->tokenAuthenticationService->validateToken($apiToken)) {
            return $this->tokenAuthenticationService->unauthorizedResponse();
        }

        // File validation logic
        $file = $request->files->get('csv_file');
        if (!$file || $file->getClientOriginalExtension() !== 'csv') {
            return new JsonResponse(['error' => 'Invalid file'], 400);
        }

        // Use service for parsing and building hierarchy
        $teams = $this->teamHierarchyService->parseCsv($file);
        $tree = $this->teamHierarchyService->buildTree($teams);

        // Filter the tree if _q query parameter is present
        $q = $request->query->get('_q');
        if ($q && isset($teams[$q])) {
            $filteredTree = $this->teamHierarchyService->filterTree($teams[$q], $teams);
            return new JsonResponse($filteredTree);
        }

        return new JsonResponse($tree);
    }
}
