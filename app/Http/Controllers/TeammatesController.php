<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AsanaService; 
use Illuminate\Support\Facades\Log;

class TeammatesController extends Controller
{
    /**
     * Muestra la página del directorio del equipo.
     */
    public function index(Request $request)
    {
        $asana = new AsanaService();

        // worspaces
        $workspaceId = $request->query('workspace');
        if (!$workspaceId) {
            $workspaceId = $asana->getDefaultWorkspaceGid();
        }
        $workspaces = $asana->getWorkspaces();
        
        $teammates = [];
        if ($workspaceId) {
            try {
                // all users
                $teammates = $asana->getUsersByWorkspace($workspaceId);
            } catch (\Exception $e) {
                Log::error('Error al obtener teammates', ['error' => $e->getMessage()]);
                // empty array
            }
        }

        // return to view
        return view('pages.teammates', compact(
            'teammates', 
            'workspaces', 
            'workspaceId'
        ));
    }
    public function showTasks(Request $request, AsanaService $asana, string $userGid)
    {
        // 1. Necesitamos saber en qué workspace buscar
        $workspaceId = $request->query('workspace');
        if (!$workspaceId) {
            return response()->json(['error' => 'Workspace GID required'], 400);
        }

        try {
            // 2. Pedimos las tareas de ESE usuario en ESE workspace
            $filters = [
                'workspace' => $workspaceId,
                'assignee'  => $userGid,
                'completed_since' => 'now', // Solo tareas abiertas
            ];
            
            $fields = [
                'gid', 'name', 'due_on', 'permalink_url',
                'projects.name', 'memberships.section.name'
            ];

            $response = $asana->listTasks($filters, $fields);
            $tasks = $response['data'] ?? [];
            
            // 3. Formateamos los datos (opcional pero limpio)
            $formattedTasks = collect($tasks)->map(function($task) {
                return [
                    'gid' => $task['gid'],
                    'name' => $task['name'],
                    'due_on' => $task['due_on'] ?? '—',
                    'url' => $task['permalink_url'],
                    'project' => $task['projects'][0]['name'] ?? 'No Project',
                    'section' => $task['memberships'][0]['section']['name'] ?? 'No Section',
                ];
            })->sortBy('due_on')->values(); // Ordenamos por fecha

            return response()->json(['data' => $formattedTasks]);

        } catch (\Exception $e) {
            Log::error('Error al obtener tareas del teammate', [
                'userGid' => $userGid,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Could not fetch tasks.'], 500);
        }
    }
}