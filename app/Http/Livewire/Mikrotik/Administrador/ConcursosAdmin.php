<?php

namespace App\Http\Livewire\Mikrotik\Administrador;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\AdvertisingConcurso;
use App\Models\Router;
use App\Models\UserMikrotik;
use App\Models\ConcursoResponse;
use App\Models\AgeRange;
use App\Models\User;
use Exception;
use App\Models\UserSucursal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConcursosAdmin extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    
    // Propiedades del Formulario
    public $isModalOpen = false;
    public $selected_id, $name, $description, $target_gender = 'todos', $router_identity;
    public $age_range_id;
    public $media_type = 'imagen', $media, $current_media_path;
    public $question_text, $question_type = 'simple';
    public $options = [];
    public $etapa = 'inscripcion';

    public $user_id;

    public function mount()
    {
        $this->user_id = Auth::id();
        $this->age_range_id = 0;
    }

    public function openModal()
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->description = '';
        $this->target_gender = 'todos';
        $this->router_identity = '';
        $this->age_range_id = 0;
        $this->media_type = 'imagen';
        $this->media = null;
        $this->question_text = 'Concurso Estándar';
        $this->question_type = 'simple';
        $this->options = [];
        $this->selected_id = null;
        $this->current_media_path = null;
        $this->etapa = 'inscripcion';
    }

    public function toggleStatus($id)
    {
        $concurso = AdvertisingConcurso::findOrFail($id);
        $concurso->active = !$concurso->active;
        $concurso->save();
    }

    public function edit($id)
    {
        $concurso = AdvertisingConcurso::findOrFail($id);
        $this->selected_id = $id;
        $this->name = $concurso->name;
        $this->description = $concurso->description;
        $this->target_gender = $concurso->target_gender;
        $this->router_identity = $concurso->router_identity;
        $this->age_range_id = $concurso->age_range_id;
        $this->media_type = $concurso->media_type;
        $this->question_text = $concurso->question_text;
        $this->question_type = $concurso->question_type;
        $this->options = $concurso->options ?? [];
        $this->etapa = $concurso->etapa;

        $this->user_id = $concurso->user_id;
        $this->current_media_path = $concurso->media_path;
        
        $this->isModalOpen = true;
    }

    public function delete($id)
    {
        $concurso = AdvertisingConcurso::findOrFail($id);
        if ($concurso->media_path) {
            Storage::disk('public')->delete($concurso->media_path);
        }
        $concurso->delete();
        session()->flash('message', 'Concurso eliminado correctamente.');
    }

    public function save()
    {
        $this->validate([
            'name' => 'required',
            'router_identity' => 'required',
            'age_range_id' => 'required',
            'etapa' => 'required|in:inscripcion,votacion,finalizado',
            'media' => $this->selected_id ? 'nullable|max:20480' : 'required|max:20480',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'router_identity' => $this->router_identity,
            'user_id' => $this->user_id,
            'target_gender' => $this->target_gender,
            'age_range_id' => $this->age_range_id ?: 0,
            'media_type' => $this->media_type,
            'question_text' => $this->question_text,
            'question_type' => $this->question_type,
            'options' => $this->question_type != 'simple' ? $this->options : null,
            'etapa' => $this->etapa,
        ];

        if ($this->media) {
            if ($this->selected_id && $this->current_media_path) {
                Storage::disk('public')->delete($this->current_media_path);
            }
            $originalName = $this->media->getClientOriginalName();
            $path = $this->media->storeAs('concursos', $originalName, 'public');
            $data['media_path'] = $path;
        }

        AdvertisingConcurso::updateOrCreate(['id' => $this->selected_id], $data);

        session()->flash('message', $this->selected_id ? 'Concurso actualizado.' : 'Concurso creado.');
        $this->closeModal();
    }

    public function addOption()
    {
        $this->options[] = '';
    }

    public function removeOption($index)
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
    }

    public function render()
    {
        $user = Auth::user();
        $query = AdvertisingConcurso::withCount('responses');

        if ($user->role === 'administrador') {
            $sucursal = UserSucursal::where('user_id', $user->id)->first();
            if ($sucursal && $sucursal->router) {
                $query->where('router_identity', $sucursal->router->identity);
            } else {
                $query->whereRaw('1 = 0'); // No mostrar nada si no tiene router asignado
            }
        } else {
            // Lógica para 'aliado' y 'aliadoSmartData'
            $query->where('user_id', $user->id);
        }

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $ageRanges = AgeRange::where('user_id', $user->id)->get();

        // Los routers pueden ser de ambos roles para el aliado
        $routers = Router::where('user_id', $user->id)->get();

        return view('livewire.mikrotik.administrador.concursos-admin', [
            'concursos' => $query->latest()->paginate(10),
            'ageRanges' => $ageRanges,
            'routers' => $routers
        ])->layout('layouts.app');
    }

    /**
     * Obtiene únicamente el concurso activo para un router específico.
     */
    public static function getActiveConcursoByIdentity(Request $request)
    {
        $identity = $request->query('identity');
        $mac = $request->query('mac');

        $router = Router::when($identity, function($q) use ($identity) {
                return $q->where('identity', $identity);
            })
            ->when($mac, function($q) use ($mac) {
                return $q->orWhere('macAddress', $mac);
            })
            ->first();

        $concurso = AdvertisingConcurso::where('router_identity', $router ? $router->identity : $identity)
            ->where('active', true)
            ->first();

        return [
            'concurso' => $concurso
        ];
    }

    /**
     * Procesa y guarda la información del portal cautivo para un concurso.
     */
    public static function savePortalDataConcurso(Request $request)
    {
        try {
            $mac = strtoupper($request->input('mac_cliente'));
            $identity = $request->input('identity');
            $concursoId = $request->input('concurso_id');

            $router = Router::where('identity', $identity)->orWhere('macAddress', $identity)->first();
            if (!$router) {
                return response()->json(['success' => false, 'message' => 'Router no identificado'], 404);
            }

            $concurso = AdvertisingConcurso::find($concursoId);
            if (!$concurso) {
                return response()->json(['success' => false, 'message' => 'Concurso no encontrado'], 404);
            }

            // 1. Gestionar UserMikrotik
            $userData = [
                'router_id' => $router->id,
                'server'    => $identity,
                'macaddress'=> $mac,
                'password'  => '12345',
                'active'    => true,
                'full_name' => $request->input('full_name'),
                'cellphonecode' => $request->input('cellphonecode'),
                'cellphone' => $request->input('cellphone'),
            ];

            $userMikrotik = UserMikrotik::updateOrCreate(['name' => $mac, 'router_id' => $router->id], $userData);

            // 2. Gestionar Respuesta de Concurso
            ConcursoResponse::create([
                'concurso_id'      => $concursoId,
                'user_mikrotik_id' => $userMikrotik->id,
                'mac_address'      => $mac,
                'router_identity'  => $identity,
                'answer'           => is_array($request->input('answer')) 
                                      ? json_encode($request->input('answer')) 
                                      : $request->input('answer'),
                'full_name'        => $request->input('full_name'),
                'cellphonecode'    => $request->input('cellphonecode'),
                'cellphone'        => $request->input('cellphone'),
                'concurso_name'    => $concurso->name,
                'concurso_etapa'   => $concurso->etapa,
            ]);

            return response()->json(['success' => true]);

        } catch (Exception $e) {
            \Log::error("Error al guardar data del portal (concurso): " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }
}
