<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; 

class AsanaService
{
  protected Client $client;
  protected string $accessToken;

  public function __construct()
  {
    $user = auth()->user();
    $token = $user->asana_access_token ?? $user->access_token ?? null;

    if (!$user || !$token) {
      Auth::logout();
      redirect()->route('landing')->send();
      exit;
    }

    $this->accessToken = $token;
    $this->client = new Client([
      'base_uri' => 'https://app.asana.com/api/1.0/',
      'headers' => [
        'Authorization' => 'Bearer ' . $this->accessToken,
        'Accept' => 'application/json',
      ],
    ]);
  }

  private function request(string $method, string $endpoint, array $options = [])
  {
    try {
      $response = match ($method) {
        'GET' => $this->client->get($endpoint, ['query' => $options]),
        'POST' => $this->client->post($endpoint, ['json' => $options]), 
        'PUT' => $this->client->put($endpoint, ['json' => $options]),
        'DELETE' => $this->client->delete($endpoint),
        default => throw new \Exception("Método HTTP no soportado: {$method}")
      };
      return json_decode($response->getBody()->getContents(), true);

    } catch (ClientException $e) {
      $response = $e->getResponse();
      $code = $response?->getStatusCode();
      $body = $response?->getBody()->getContents();
      Log::error('Error Asana API [' . $method . ' ' . $endpoint . ' - ' . $code . ']: ' . $body);
      
      if ($code == 401) {
        Auth::logout(); 
        redirect()->route('landing')->send();
        exit;
      }

      $decodedBody = json_decode($body, true);
      $errorMessage = $decodedBody['errors'][0]['message'] ?? 'Error en API de Asana: HTTP ' . $code;
      
      throw new \Exception($errorMessage);

    } catch (\Exception $e) {
      Log::error('Error Asana API [' . $method . ' ' . $endpoint . ']: ' . $e->getMessage());
      throw $e;
    }
  }

  public function getUserDetails(array $fields = [])
  {
    $defaultFields = [
      'name', 'email', 'photo.image_60x60', 'photo.image_128x128', 'workspaces', 
      'about_me', 'title', 'teams'
    ];
    $fields = array_unique(array_merge($defaultFields, $fields));
    $options = ['opt_fields' => implode(',', $fields)];
    return $this->request('GET', 'users/me', $options)['data'] ?? [];
  }
  
  public function getWorkspaces()
  {
    return $this->request('GET', 'workspaces')['data'] ?? [];
  }

  public function getDefaultWorkspaceGid()
  {
    $workspaces = $this->getWorkspaces();
    return $workspaces[0]['gid'] ?? null;
  }

  public function getTeamsByWorkspace(string $workspaceGid): array
  {
    return $this->request('GET', "organizations/{$workspaceGid}/teams", [
      'opt_fields' => 'name'
    ])['data'] ?? [];
  }

  public function getMyTeamsByWorkspace(string $workspaceGid): array
  {
    return $this->request('GET', "workspaces/{$workspaceGid}/teams", [
      'user' => 'me',
      'opt_fields' => 'name,description'
    ])['data'] ?? [];
  }

  public function getUserProjects(?string $workspaceId = null)
  {
    $workspaceToUse = $workspaceId ?? $this->getDefaultWorkspaceGid();
    if (!$workspaceToUse) return [];
    return $this->getProjectsByWorkspace($workspaceToUse);
  }
  
  public function getUserActiveProjects(?string $workspaceId = null): array
  {
    $workspaceToUse = $workspaceId ?? $this->getDefaultWorkspaceGid();
    if (!$workspaceToUse) return [];
    $projects = $this->request('GET', 'projects', [
      'workspace' => $workspaceToUse,
      'assignee' => 'me',
      'opt_fields' => 'name,current_status,due_date'
    ])['data'] ?? [];
    
    return array_filter($projects, function($project) {
      return !($project['archived'] ?? false); 
    });
  }

  public function getProjectsByWorkspace(string $id)
  {
    return $this->request('GET', "workspaces/{$id}/projects")['data'] ?? [];
  }

  public function getProject(string $projectGid, array $fields = [])
  {
    $options = [];
    if (!empty($fields)) {
      $options['opt_fields'] = implode(',', $fields);
    }
    return $this->request('GET', "projects/{$projectGid}", $options);
  }

  public function getSections($projectId)
  {
    return $this->request('GET', "projects/{$projectId}/sections")['data'] ?? [];
  }

  public function createSectionForProject(string $projectGid, string $sectionName)
  {
    return $this->request('POST', "projects/{$projectGid}/sections", [
      'data' => [
        'name' => $sectionName
      ]
    ]);
  }

  public function ensureSections(string $projectId): array
  {
    $sections = $this->getSections($projectId);
    $map = [];
    foreach ($sections as $section) {
      $map[strtolower($section['name'])] = $section['gid'];
    }
    
    $requiredSections = [
      'IM/UR', 'IM/NO UR', 'NO IM/UR', 'NO IM/NO UR'
    ];

    foreach ($requiredSections as $sectionName) {
      if (!isset($map[strtolower($sectionName)])) {
        $created = $this->createSectionForProject($projectId, $sectionName);
        $map[strtolower($sectionName)] = $created['data']['gid'] ?? null;
      }
    }
    Log::info('Secciones detectadas y creadas en proyecto ' . $projectId, $map);
    return $map;
  }

  public function moveTaskToSection(string $taskGid, string $projectGid, ?string $sectionGid)
  {
    if (!$sectionGid) {
      throw new \Exception('No se proporcionó GID de sección válido.');
    }
    return $this->request('POST', "sections/{$sectionGid}/addTask", [
      'data' => [
        'task' => $taskGid
      ]
    ]);
  }

  public function listTasks(array $filters, array $fields = []): array
  {
    $query = $filters;
    $hasProjectFilter = isset($query['project']) && !empty($query['project']);

    if ($hasProjectFilter) {
      if (isset($query['assignee'])) {
        unset($query['assignee']);
        Log::warning("Filtro 'assignee' eliminado. No se puede usar con 'project' en GET /tasks (Asana API).");
      }
      if (isset($query['workspace'])) {
        unset($query['workspace']);
        Log::warning("Filtro 'workspace' eliminado. No se puede usar con 'project' en GET /tasks (Asana API).");
      }
    } else {
      if (!isset($query['assignee'])) { 
        $query['assignee'] = 'me';
      }
    }
    
    if (!empty($fields)) {
      $query['opt_fields'] = implode(',', $fields);
    }
    
 
    $allTasks = [];
    $query['limit'] = 100; 

    while (true) {
      $response = $this->request('GET', 'tasks', $query);
      $pageTasks = $response['data'] ?? ($response ?? []);
      
      if (is_array($pageTasks) && !empty($pageTasks)) {
        $allTasks = array_merge($allTasks, $pageTasks);
      }

      $nextPageOffset = $response['next_page']['offset'] ?? null;
      
      if ($nextPageOffset) {
        $query['offset'] = $nextPageOffset;
      } else {
        break; 
      }
    }
    
    return ['data' => $allTasks];
  }

  public function getUsersByWorkspace(string $workspaceGid): array
  {
    return $this->request('GET', "workspaces/{$workspaceGid}/users", [
      'opt_fields' => 'name,email,title,photo.image_128x128'
    ])['data'] ?? [];
  }

  public function createTask(array $data)
  {
    return $this->request('POST', 'tasks', ['data' => $data]);
  }
  
  public function updateTask(string $taskGid, array $data)
  {
    return $this->request('PUT', "tasks/{$taskGid}", ['data' => $data]);
  }

  public function deleteTask(string $taskGid)
  {
    return $this->request('DELETE', "tasks/{$taskGid}");
  }
}