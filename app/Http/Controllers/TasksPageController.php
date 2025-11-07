<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AsanaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator; 

class TasksPageController extends Controller
{
    /**
     * Muestra la lista de tareas.
     */
    public function index(Request $request)
    {
        $asana = new AsanaService();

        $projectId   = $request->query('project');
        $workspaceId = $request->query('workspace');
        if ($projectId && !$workspaceId) {
            try {
                $projectResp = $asana->getProject($projectId, ['gid', 'name', 'workspace']);
                $inferred = $projectResp['data']['workspace']['gid'] ?? null;
                if ($inferred) {
                    Log::info('TasksPage: inferido workspace desde project', ['project' => $projectId, 'workspace' => $inferred]);
                    return redirect($request->fullUrlWithQuery(['workspace' => $inferred]));
                }
            } catch (\Exception $e) {
                Log::warning('No se pudo inferir workspace desde proyecto', ['error' => $e->getMessage(), 'project' => $projectId]);
            }
        }

        // Obtener workspaces
        $workspaces = $asana->getWorkspaces();

        // usar el default 
        if (!$workspaceId) {
            $workspaceId = $asana->getDefaultWorkspaceGid();
        }

        // Obtener proyectos filtrados 
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
            'assignee.name' 
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
            
            // asignee
            $raw_name = $task['assignee']['name'] ?? null;
            $task['assignee_name'] = $raw_name ? Str::limit($raw_name, 15, '...') : null;
        }

        Log::info('TasksPage filtros', ['workspace' => $workspaceId, 'project' => $projectId, 'filters' => $filters]);

        return view('pages.tasks', compact('tasks', 'projects', 'workspaces', 'workspaceId'));
    }


    
    /**
     * Almacena una nueva tarea en Asana.
     * (ACTUALIZADO para 'assignee_gid')
     */
    public function store(Request $request)
    {
        // 1. Validar los datos (con el nuevo campo 'assignee_gid')
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'notes'         => 'nullable|string',
            'workspace_gid' => 'required|string', 
            'project_gid'   => 'nullable|string', 
            'assignee_gid'  => 'nullable|string', // <-- CAMPO NUEVO
        ]);

        // validacion
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $asana = new AsanaService();
            $data = $validator->validated();

            // 2. datos para la api
            $taskData = [
                'name'      => $data['name'],
                'notes'     => $data['notes'] ?? '',
                'workspace' => $data['workspace_gid'],
            ];

            // 3. Añadir asignado
            // Si el valor es 'me', lo pasamos. Si está vacío (Unassigned), no lo enviamos.
            if (!empty($data['assignee_gid'])) {
                $taskData['assignee'] = $data['assignee_gid']; // Asana entiende "me"
            }

            // 4. project
            if (!empty($data['project_gid'])) {
                $taskData['projects'] = [$data['project_gid']];
            }
            
            Log::info('Creando nueva tarea en Asana', $taskData);

            // 5. service
            $result = $asana->createTask($taskData);

            // 6. json return
            return response()->json([
                'message' => 'Task created successfully!',
                'task'    => $result['data'] ?? null
            ]);

        } catch (\Exception $e) {
            // 7. error
            Log::error('Error al crear tarea en Asana', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return response()->json(['error' => 'Could not create task in Asana: ' . $e->getMessage()], 500);
        }
    }
}