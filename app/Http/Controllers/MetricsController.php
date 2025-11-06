<?php

namespace App\Http\Controllers;

use App\Services\AsanaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles analytical metrics for Asana data,
 * used by the Metrics Dashboard.
 */
class MetricsController extends Controller
{
    /**
     * Display the metrics dashboard view.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $asana = new AsanaService();

        $workspace = $asana->getDefaultWorkspaceGid();
        $projects  = $workspace ? $asana->getProjectsByWorkspace($workspace) : [];
        $workspaces = $asana->getWorkspaces() ?? [];

        return view('pages.metrics', [
            'workspaceGid' => $workspace,
            'projects'     => $projects,
            'workspaces'   => $workspaces, // ← esta faltaba
        ]);

    }

    /**
     * Returns general overview metrics for the authenticated user.
     */
    public function apiOverview(Request $request)
    {
        try {
            $asana     = new AsanaService();
            $workspace = $request->get('workspace') ?? $asana->getDefaultWorkspaceGid();
            $days      = (int) ($request->get('days') ?? 30);
            $since     = Carbon::now()->subDays($days)->toIso8601String();

            // Completed tasks in period
            $completed = $asana->listTasks([
                'workspace'       => $workspace,
                'completed_since' => $since,
                'opt_fields'      => 'gid,completed,completed_at',
            ]);
            $completedData  = $completed['data'] ?? ($completed ?? []);
            $completedCount = count(array_filter($completedData, fn($t) => !empty($t['completed'])));

            // Open (incomplete) tasks
            $open = $asana->listTasks([
                'workspace'  => $workspace,
                'assignee'   => 'me',
                'opt_fields' => 'gid,completed,due_on',
            ]);
            $openData  = $open['data'] ?? ($open ?? []);
            $openCount = count(array_filter($openData, fn($t) => empty($t['completed'])));

            // Overdue tasks
            $today        = Carbon::now()->toDateString();
            $overdueCount = count(array_filter($openData, fn($t) =>
                !empty($t['due_on']) && !$t['completed'] && $t['due_on'] < $today
            ));

            // Active projects
            $projects      = $asana->getUserActiveProjects($workspace);
            $projectsCount = count($projects);

            return response()->json([
                'success' => true,
                'data' => [
                    'completed_last_days' => $completedCount,
                    'open_tasks'          => $openCount,
                    'overdue_tasks'       => $overdueCount,
                    'active_projects'     => $projectsCount,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[MetricsController] apiOverview failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Returns time series data for tasks completed over time.
     */
    public function apiTasksCompleted(Request $request)
    {
        try {
            $asana     = new AsanaService();
            $days      = (int) ($request->get('days') ?? 30);
            $workspace = $request->get('workspace') ?? $asana->getDefaultWorkspaceGid();
            $since     = Carbon::now()->subDays($days)->toIso8601String();

            $response = $asana->listTasks([
                'workspace'       => $workspace,
                'completed_since' => $since,
                'opt_fields'      => 'gid,completed,completed_at',
            ]);
            $tasks = $response['data'] ?? ($response ?? []);

            $dateBuckets = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $dateBuckets[Carbon::now()->subDays($i)->toDateString()] = 0;
            }

            foreach ($tasks as $task) {
                if (!empty($task['completed']) && !empty($task['completed_at'])) {
                    $completedDate = Carbon::parse($task['completed_at'])->toDateString();
                    if (isset($dateBuckets[$completedDate])) {
                        $dateBuckets[$completedDate]++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'labels' => array_keys($dateBuckets),
                    'series' => array_values($dateBuckets),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[MetricsController] apiTasksCompleted failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Returns total task count per project.
     */
    public function apiTasksByProject(Request $request)
    {
        try {
            $asana     = new AsanaService();
            $workspace = $request->get('workspace') ?? $asana->getDefaultWorkspaceGid();
            $limit     = (int) ($request->get('top') ?? 10);

            $projects = $asana->getProjectsByWorkspace($workspace) ?? [];
            $metrics  = [];

            foreach ($projects as $project) {
                $projectGid = $project['gid'] ?? null;
                if (!$projectGid) continue;

                $tasksResponse = $asana->listTasks([
                    'project'    => $projectGid,
                    'opt_fields' => 'gid,completed',
                ]);

                $tasks = $tasksResponse['data'] ?? ($tasksResponse ?? []);
                $metrics[] = [
                    'project_gid'  => $projectGid,
                    'project_name' => $project['name'] ?? 'Unnamed Project',
                    'total_tasks'  => count($tasks),
                    'completed'    => count(array_filter($tasks, fn($t) => !empty($t['completed']))),
                ];
            }

            usort($metrics, fn($a, $b) => $b['total_tasks'] <=> $a['total_tasks']);
            $metrics = array_slice($metrics, 0, $limit);

            return response()->json(['success' => true, 'data' => $metrics]);
        } catch (\Exception $e) {
            Log::error('[MetricsController] apiTasksByProject failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Returns the top assignees based on completed tasks.
     */
    public function apiTopAssignees(Request $request)
    {
        try {
            $asana     = new AsanaService();
            $days      = (int) ($request->get('days') ?? 30);
            $workspace = $request->get('workspace') ?? $asana->getDefaultWorkspaceGid();
            $limit     = (int) ($request->get('top') ?? 10);
            $since     = Carbon::now()->subDays($days)->toIso8601String();

            // 🔹 Obtener todos los proyectos del workspace (no solo los del usuario)
            $projects = $asana->getProjectsByWorkspace($workspace) ?? [];

            $map = [];

            // 🔹 Recorrer todos los proyectos y acumular tareas completadas por cada usuario
            foreach ($projects as $project) {
                $projectGid = $project['gid'] ?? null;
                if (!$projectGid) continue;

                $tasksResponse = $asana->listTasks([
                    'project'         => $projectGid,
                    'completed_since' => $since,
                    'opt_fields'      => 'gid,completed,completed_at,assignee.name,assignee.gid'
                ]);

                $tasks = $tasksResponse['data'] ?? ($tasksResponse ?? []);

                foreach ($tasks as $task) {
                    if (!empty($task['completed']) && !empty($task['assignee'])) {
                        $assignee = $task['assignee'];
                        $gid = $assignee['gid'] ?? 'unknown';
                        $name = $assignee['name'] ?? 'Unknown';

                        if (!isset($map[$gid])) {
                            $map[$gid] = ['name' => $name, 'count' => 0];
                        }

                        $map[$gid]['count']++;
                    }
                }
            }

            // 🔹 Ordenar los resultados por número de tareas completadas
            $result = collect($map)
                ->map(fn($data, $gid) => [
                    'gid'   => $gid,
                    'name'  => $data['name'],
                    'count' => $data['count']
                ])
                ->sortByDesc('count')
                ->take($limit)
                ->values()
                ->all();

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('[MetricsController] apiTopAssignees failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Returns overdue tasks for the current user.
     */
    public function apiOverdue(Request $request)
    {
        try {
            $asana     = new AsanaService();
            $workspace = $request->get('workspace') ?? $asana->getDefaultWorkspaceGid();
            $limit     = (int) ($request->get('limit') ?? 20);

            $response = $asana->listTasks([
                'workspace'  => $workspace,
                'assignee'   => 'me',
                'opt_fields' => 'gid,name,due_on,completed,permalink_url',
            ]);

            $tasks = $response['data'] ?? ($response ?? []);
            $today = Carbon::now()->toDateString();

            $overdue = array_filter($tasks, fn($t) =>
                !empty($t['due_on']) && empty($t['completed']) && $t['due_on'] < $today
            );

            usort($overdue, fn($a, $b) => strcmp($a['due_on'] ?? '', $b['due_on'] ?? ''));
            $overdue = array_slice($overdue, 0, $limit);

            return response()->json(['success' => true, 'data' => array_values($overdue)]);
        } catch (\Exception $e) {
            Log::error('[MetricsController] apiOverdue failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
