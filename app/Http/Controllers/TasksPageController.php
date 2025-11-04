<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AsanaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TasksPageController extends Controller
{
    public function index(Request $request)
    {
        $asana = new AsanaService();

        $projectId   = $request->query('project');
        $workspaceId = $request->query('workspace');

        // Si se pasó project pero no workspace, intentamos inferir el workspace del project
        if ($projectId && !$workspaceId) {
            try {
                $projectResp = $asana->getProject($projectId, ['gid', 'name', 'workspace']);
                $inferred = $projectResp['data']['workspace']['gid'] ?? null;
                if ($inferred) {
                    Log::info('🔀 TasksPage: inferido workspace desde project', ['project' => $projectId, 'workspace' => $inferred]);
                    return redirect($request->fullUrlWithQuery(['workspace' => $inferred]));
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ No se pudo inferir workspace desde proyecto', ['error' => $e->getMessage(), 'project' => $projectId]);
            }
        }

        // Obtener workspaces (para el selector)
        $workspaces = $asana->getWorkspaces();

        // Si no hay workspace definido, usar el default del servicio
        if (!$workspaceId) {
            $workspaceId = $asana->getDefaultWorkspaceGid();
        }

        // Obtener proyectos filtrados por workspace actual
        $projects = $asana->getUserProjects($workspaceId);
        
        $filters = [
            'assignee' => 'me',
            'limit' => 100,
            'completed_since' => 'now',
        ];

        if ($workspaceId) {
            $filters['workspace'] = $workspaceId;
        }

        if ($projectId) {
            $filters['project'] = $projectId;
        }

        $fields = [
            'gid', 'name', 'due_on', 'completed', 'permalink_url',
            'projects.name', 'projects.permalink_url', 'projects.gid',
            'notes', 'created_at', 'modified_at',
            'memberships.section.name',
        ];

        $resp = $asana->listTasks($filters, $fields);
        $tasks = $resp['data'] ?? [];

        foreach ($tasks as &$task) {
            $task['section_name'] = $task['memberships'][0]['section']['name'] ?? 'No asignada';
            $task['project_name'] = $task['projects'][0]['name'] ?? 'N/A';
            $task['project_gid']  = $task['projects'][0]['gid'] ?? null;

            $task['updated'] = !empty($task['modified_at'])
                ? Carbon::parse($task['modified_at'])->diffForHumans()
                : (!empty($task['created_at'])
                    ? Carbon::parse($task['created_at'])->diffForHumans()
                    : '—');
        }

        Log::info('✅ TasksPage filtros', ['workspace' => $workspaceId, 'project' => $projectId, 'filters' => $filters]);

        return view('pages.tasks', compact('tasks', 'projects', 'workspaces', 'workspaceId'));
    }
}
