<?php

namespace App\Http\Controllers;

use App\Services\AsanaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AsanaInfoController extends Controller
{
    public function dashboard(Request $request)
    {
        $asana = new AsanaService();

        $projectId   = $request->query('project');
        $workspaceId = $request->query('workspace');

       
        if ($projectId && !$workspaceId) {
            try {
                $projectResp = $asana->getProject($projectId, ['gid', 'name', 'workspace']);
                $projectWorkspaceGid = $projectResp['data']['workspace']['gid'] ?? null;

                if ($projectWorkspaceGid) {
                    Log::info('Dashboard: inferido workspace desde project', [
                        'project' => $projectId,
                        'workspace_inferido' => $projectWorkspaceGid,
                    ]);
                    return redirect($request->fullUrlWithQuery(['workspace' => $projectWorkspaceGid]));
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener workspace desde project', [
                    'project' => $projectId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        //Obtener workspaces
        $workspaces = $asana->getWorkspaces();
        if (!$workspaceId) {
            $workspaceId = $asana->getDefaultWorkspaceGid();
        }

        //Proyectos del workspace
        $projects = $asana->getUserProjects($workspaceId);

        // solo tareas no completadas
        $filters = [
            'assignee'  => 'me',
            'limit'     => 100,
            'completed' => false, 
        ];

        if ($projectId) {
            $filters['project'] = $projectId;
        } else {
            $filters['workspace'] = $workspaceId;
            $filters['completed_since'] = 'now';
        }

        Log::info('DEBUG FILTRO ASANA', [
            'workspace' => $workspaceId,
            'project'   => $projectId,
            'filtros'   => $filters,
        ]);

        // campos
        $fields = [
            'gid', 'name', 'due_on', 'completed', 'permalink_url',
            'projects.name', 'projects.permalink_url', 'projects.gid',
            'notes', 'created_at', 'modified_at',
            'memberships.section.name', 'memberships.section.gid',
            'assignee.name'
        ];

        // 🔹 Obtener tareas
        $resp = $asana->listTasks($filters, $fields);
        $tasks = collect($resp['data'] ?? [])
            ->filter(fn($t) => empty($t['completed']))
            ->values()
            ->toArray();

        // secciones
        $sectionMap = ['pending-tasks-list' => null];

        if ($projectId) {
            try {
                $sections = $asana->ensureSections($projectId);

                $quadrantAliases = [
                    'im/ur'        => 'do-list',       // Importante - Urgente
                    'im/no ur'     => 'decide-list',   // Importante - No Urgente
                    'no im/ur'     => 'delegate-list', // No Importante - Urgente
                    'no im/no ur'  => 'delete-list',   // No Importante - No Urgente
                ];

                foreach ($quadrantAliases as $alias => $key) {
                    $sectionGid = collect($sections)
                        ->first(fn($gid, $name) => str_contains(strtolower($name), strtolower($alias)));
                    if ($sectionGid) {
                        $sectionMap[$key] = $sectionGid;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener secciones del proyecto', [
                    'error' => $e->getMessage(),
                    'project_gid' => $projectId,
                ]);
            }
        }

        // cuadrantes
        $tasksByQuadrant = [
            'do' => [], 'decide' => [], 'delegate' => [], 'delete' => [], 'pending' => [],
        ];

        $quadrantMap = [
            'im/ur'        => 'do',
            'im/no ur'     => 'decide',
            'no im/ur'     => 'delegate',
            'no im/no ur'  => 'delete',
        ];

        foreach ($tasks as $task) {
            $sectionName = strtolower($task['memberships'][0]['section']['name'] ?? '');

            $task['updated'] = !empty($task['modified_at'])
                ? Carbon::parse($task['modified_at'])->diffForHumans()
                : '—';
            $task['project_name'] = $task['projects'][0]['name'] ?? 'N/A';
            $task['project_gid']  = $task['projects'][0]['gid'] ?? null;

            $quadrantKey = $quadrantMap[$sectionName] ?? 'pending';
            $raw_name = $task['assignee']['name'] ?? null;
            $task['assignee_name'] = $raw_name ? Str::limit($raw_name, 15, '...') : null;
            $tasksByQuadrant[$quadrantKey][] = $task;
        }

        $sectionMapJson = json_encode($sectionMap, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return view('pages.dashboard', compact(
            'projects',
            'tasksByQuadrant',
            'sectionMapJson',
            'workspaces',
            'workspaceId'
        ));
    }

    public function moveTaskToSection(Request $request, string $gid)
    {
        $projectGid = $request->input('project_gid');
        $sectionGid = $request->input('section_gid');

        if (!$projectGid) {
            return response()->json(['error' => 'Falta project_gid.'], 400);
        }

        try {
            $asana = new AsanaService();
            $result = $asana->moveTaskToSection($gid, $projectGid, $sectionGid);

            return response()->json([
                'message' => 'Tarea movida con éxito',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mover tarea', [
                'task_gid' => $gid,
                'project_gid' => $projectGid,
                'section_gid' => $sectionGid,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProjects()
    {
        try {
            $asana = new AsanaService();
            $projects = $asana->getUserProjects();

            $data = collect($projects)
                ->map(fn($p) => [
                    'gid' => $p['gid'] ?? null,
                    'name' => $p['name'] ?? 'Proyecto sin nombre',
                ])
                ->filter(fn($p) => !empty($p['gid']))
                ->values();

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error al obtener proyectos desde Asana', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'No se pudieron cargar los proyectos.'], 500);
        }
    }
}
