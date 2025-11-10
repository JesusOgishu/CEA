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
    /**ks
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

        $workspaces = $asana->getWorkspaces();
        if (!$workspaceId) {
            $workspaceId = $asana->getDefaultWorkspaceGid();
        }

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

        // campos
        $fields = [
            'gid', 'name', 'due_on', 'completed', 'permalink_url',
            'projects.name', 'projects.permalink_url', 'projects.gid',
            'notes', 'created_at', 'modified_at',
            'memberships.section.name',
            'assignee' 
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
            
            $raw_name = $task['assignee']['name'] ?? null;
            $task['assignee_name'] = $raw_name ? Str::limit($raw_name, 15, '...') : null;
        }

        Log::info('TasksPage filtros', ['workspace' => $workspaceId, 'project' => $projectId, 'filters' => $filters]);

        return view('pages.tasks', compact('tasks', 'projects', 'workspaces', 'workspaceId'));
    }


    
    /**
     * Almacena una nueva tarea en Asana.
     */
    public function store(Request $request)
    {
        // validacion
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'notes'         => 'nullable|string',
            'workspace_gid' => 'required|string', 
            'project_gid'   => 'nullable|string', 
            'assignee_gid'  => 'nullable|string',
            'due_on'        => 'nullable|date', 
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $asana = new AsanaService();
            $data = $validator->validated();

            // api data
            $taskData = [
                'name'      => $data['name'],
                'notes'     => $data['notes'] ?? '',
                'workspace' => $data['workspace_gid'],
            ];

            //asignee
            if (!empty($data['assignee_gid'])) {
                $taskData['assignee'] = $data['assignee_gid']; 
            }

            //project
            if (!empty($data['project_gid'])) {
                $taskData['projects'] = [$data['project_gid']];
            }
            
            //due_on (fecha)
            if (!empty($data['due_on'])) {
                $taskData['due_on'] = $data['due_on'];
            }
            
            Log::info('Creando nueva tarea en Asana', $taskData);

            //service
            $result = $asana->createTask($taskData);

            //json return
            return response()->json([
                'message' => 'Task created successfully!',
                'task'    => $result['data'] ?? null
            ]);

        } catch (\Exception $e) {
            //error
            Log::error('Error al crear tarea en Asana', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return response()->json(['error' => 'Could not create task in Asana: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Actualiza una tarea existente en Asana.
     */
    public function update(Request $request, string $taskGid)
    {
        // validacion
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'notes'        => 'nullable|string',
            'assignee_gid' => 'nullable|string',
            'due_on'       => 'nullable|date', 
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $asana = new AsanaService();
            $data = $validator->validated();
            
            // datos para api
            $taskData = [
                'name'  => $data['name'],
                'notes' => $data['notes'] ?? '',
            ];
            
            // asignar
            if (isset($data['assignee_gid'])) { 
                $taskData['assignee'] = $data['assignee_gid'] ?: null; 
            }
            
            // due_on (fecha)
            if (isset($data['due_on'])) { 
                $taskData['due_on'] = $data['due_on'] ?: null; 
            }
            
            Log::info("Actualizando tarea $taskGid", $taskData);

            // service 
            $result = $asana->updateTask($taskGid, $taskData);

            // json return
            return response()->json([
                'message' => 'Task updated successfully!',
                'task'    => $result['data'] ?? null
            ]);

        } catch (\Exception $e) {
            // error
            Log::error("Error al actualizar tarea $taskGid", [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            return response()->json(['error' => 'Could not update task in Asana: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Borra múltiples tareas (una por una).
     */
    public function bulkDelete(Request $request)
    {
        // validacion
        $validator = Validator::make($request->all(), [
            'gids'   => 'required|array',
            'gids.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $asana = new AsanaService();
        $gids = $request->input('gids');
        
        $successCount = 0;
        $failCount = 0;
        $errors = [];

        // loop para borrar
        foreach ($gids as $gid) {
            try {
                $asana->deleteTask($gid);
                $successCount++;
            } catch (\Exception $e) {
                $failCount++;
                $errors[] = "Task $gid: " . $e->getMessage();
            }
        }

        // log si algo falló
        if ($failCount > 0) {
            Log::warning('Error en borrado múltiple de tareas', [
                'failed' => $failCount,
                'errors' => $errors
            ]);
        }

        // json return
        return response()->json([
            'message' => "Delete complete: $successCount succeeded, $failCount failed.",
            'success' => $successCount,
            'failed'  => $failCount,
            'errors'  => $errors
        ]);
    }
}