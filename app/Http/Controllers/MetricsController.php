<?php

namespace App\Http\Controllers;

use App\Services\AsanaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MetricsController extends Controller
{
    public function index()
    {
        $asana = new AsanaService();
        $workspace = $asana->getDefaultWorkspaceGid();
        $projects  = $workspace ? $asana->getProjectsByWorkspace($workspace) : [];
        $workspaces = $asana->getWorkspaces() ?? [];

        return view('pages.metrics', [
            'workspaceGid' => $workspace,
            'projects'     => $projects,
            'workspaces'   => $workspaces,
        ]);
    }

    public function apiOverview(Request $request)
    {
        try {
            $asana     = new AsanaService();
            $workspace = $request->get('workspace') ?? $asana->getDefaultWorkspaceGid();
            $days      = (int) ($request->get('days') ?? 30);
            $since     = Carbon::now()->subDays($days)->toIso8601String();

            
            $completed = $asana->listTasks([
                'workspace'       => $workspace,
                'assignee'        => 'me',
                'completed_since' => $since,
                'opt_fields'      => 'gid,completed',
            ]);
            $completedData  = $completed['data'] ?? ($completed ?? []);
            $completedCount = count(array_filter($completedData, fn($t) => !empty($t['completed'])));

            
            $open = $asana->listTasks([
                'workspace'  => $workspace,
                'assignee'   => 'me',
                'opt_fields' => 'gid,completed,due_on',
            ]);
            $openData  = $open['data'] ?? ($open ?? []);
            $openCount = count(array_filter($openData, fn($t) => empty($t['completed'])));

            
            $today        = Carbon::now()->toDateString();
            $overdueCount = count(array_filter($openData, fn($t) =>
                !empty($t['due_on']) && !$t['completed'] && $t['due_on'] < $today
            ));

            
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

    public function apiTasksCompleted(Request $request)
    {
        try {
            $asana     = new AsanaService();
            $days      = (int) ($request->get('days') ?? 30);
            $workspace = $request->get('workspace') ?? $asana->getDefaultWorkspaceGid();
            $since     = Carbon::now()->subDays($days)->toIso8601String(); 

            $response = $asana->listTasks([
                'workspace'       => $workspace,
                'assignee'        => 'me',
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

    public function apiTasksByProject(Request $request)
    {
        try {
            $asana     = new AsanaService();
            $workspace = $request->get('workspace') ?? $asana->getDefaultWorkspaceGid();
            $limit     = (int) ($request->get('top') ?? 10);

            $response = $asana->listTasks([
                'workspace'  => $workspace,
                'assignee'   => 'me',
                'opt_fields' => 'gid,projects.name,projects.gid',
            ]);
            $tasks = $response['data'] ?? ($response ?? []);

            $projectMap = [];
            foreach ($tasks as $task) {
                if (empty($task['projects'])) continue;
                foreach ($task['projects'] as $project) {
                    $gid = $project['gid'];
                    $name = $project['name'] ?? 'Unnamed Project';
                    if (!isset($projectMap[$gid])) {
                        $projectMap[$gid] = ['project_name' => $name, 'total_tasks' => 0];
                    }
                    $projectMap[$gid]['total_tasks']++;
                }
            }

            $metrics = collect($projectMap)
                ->sortByDesc('total_tasks')
                ->take($limit)
                ->values()
                ->all();

            return response()->json(['success' => true, 'data' => $metrics]);
        } catch (\Exception $e) {
            Log::error('[MetricsController] apiTasksByProject failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    
    public function apiTopAssignees(Request $request)
    {
        try {
            $asana     = new AsanaService();
            $days      = (int) ($request->get('days') ?? 30);
            $workspace = $request->get('workspace') ?? $asana->getDefaultWorkspaceGid();
            $limit     = (int) ($request->get('top') ?? 10);
            $since     = Carbon::now()->subDays($days)->toIso8601String();

            
            $projects = $asana->getUserActiveProjects($workspace);

            $map = [];

            
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
            
            $result = collect($map)
                ->map(fn($data, $gid) => ['gid' => $gid, 'name' => $data['name'], 'count' => $data['count']])
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