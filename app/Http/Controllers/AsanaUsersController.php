<?php

namespace App\Http\Controllers;

use App\Services\AsanaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class AsanaUsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('pages.asana_users');
    }
    public function getUsersForWorkspace(Request $request, AsanaService $asana)
    {
        // workspace valdiator
        $workspaceGid = $request->query('workspace_gid');

        if (empty($workspaceGid)) {
            return response()->json(['error' => 'Workspace GID is required'], 400);
        }

        try {
            $users = $asana->getUsersByWorkspace($workspaceGid);

            // filter
            $formattedUsers = collect($users)->map(function ($user) {
                return [
                    'gid' => $user['gid'],
                    'name' => $user['name'] ?? 'Unnamed User',
                    'photo' => $user['photo']['image_32x32'] ?? null,
                ];
            })->sortBy('name')->values(); // order by name

            // return on json
            return response()->json($formattedUsers);

        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios del workspace', [
                'workspace' => $workspaceGid,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getUserInfo(Request $request)
    {
        try {
            $asana = new AsanaService();

            $userData = $asana->getUserDetails([
                'name', 
                'email', 
                'photo', 
                'workspaces.gid', 
                'workspaces.name'
            ]);

            $workspaces = $asana->getWorkspaces();
            $defaultWorkspaceId = $asana->getDefaultWorkspaceGid();
            $activeProjects = $defaultWorkspaceId ? $asana->getUserActiveProjects($defaultWorkspaceId) : [];

            $userTeams = [];
            if ($defaultWorkspaceId) {
                $userTeams = $asana->getMyTeamsByWorkspace($defaultWorkspaceId);
            }

            $userInfo = $userData ?? [];

            if (empty($userInfo)) {
                return response()->json(['error' => 'Información de usuario vacía o token inválido.'], 500);
            }

            return response()->json([
                'name' => $userInfo['name'] ?? 'N/A',
                'email' => $userInfo['email'] ?? 'N/A',
                'role' => $userInfo['title'] ?? 'Rol no especificado',
                'about_me' => $userInfo['about_me'] ?? 'No se ha proporcionado una descripción.',
                'photo_url' => $userInfo['photo']['image_128x128'] ?? null,
                'workspaces' => $workspaces ?? [],
                'user_teams' => $userTeams,
                'active_projects' => $activeProjects,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener la información del usuario de Asana: ' . $e->getMessage());
            
            if (str_contains($e->getMessage(), '401') || str_contains($e->getMessage(), 'token')) {
                return response()->json(['error' => 'Token expirado o inválido. Por favor, inicia sesión de nuevo.'], 401);
            }
            
            return response()->json(['error' => 'No se pudo obtener la información de Asana.'], 500);
        }
    }
}
