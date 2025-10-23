<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AsanaService;
use Illuminate\Support\Carbon;

class TasksPageController extends Controller
{
    public function index(Request $request)
    {
        // 🚀 Ahora instanciamos AsanaService dentro del método,
        // cuando ya existe un usuario autenticado
        $asana = new AsanaService();

        $projectId   = $request->query('project');
        $workspaceId = $request->query('workspace');

        if (!$projectId && !$workspaceId) {
            $workspaceId = $asana->getDefaultWorkspaceGid();
        }

        $filters = $projectId
            ? ['project' => $projectId, 'limit' => 50]
            : [
                'workspace'        => $workspaceId,
                'assignee'         => 'me',
                'completed_since'  => 'now',
                'limit'            => 50
            ];

        // Campos a pedir a la API
        $fields = [
            'gid',
            'name',
            'due_on',
            'completed',
            'permalink_url',
            'projects.name',
            'projects.permalink_url',
            'projects.gid',
            'notes',
            'created_at',
            'modified_at',
            'memberships.section.name',
        ];

        $resp  = $asana->listTasks($filters, $fields);
        $tasks = $resp['data'] ?? [];

        foreach ($tasks as &$task) {
            // 🔹 Sección
            $task['section_name'] = $task['memberships'][0]['section']['name'] ?? 'No asignada';

            // 🔹 Fecha de actualización
            if (!empty($task['modified_at'])) {
                $task['updated'] = Carbon::parse($task['modified_at'])->diffForHumans();
            } elseif (!empty($task['created_at'])) {
                $task['updated'] = Carbon::parse($task['created_at'])->diffForHumans();
            } else {
                $task['updated'] = '—';
            }
        }

        // 🔥 Lista de proyectos del usuario
        $projects = $asana->getUserProjects();

        return view('pages.tasks', compact('tasks', 'projects'));
    }
}
