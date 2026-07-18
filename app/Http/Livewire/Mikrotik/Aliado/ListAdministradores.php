<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Router;
use App\Models\UserSucursal;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ListAdministradores extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;

    // Propiedades del formulario
    public $user_id, $names, $surnames, $email, $role, $password, $active = true, $router_id;

    protected $paginationTheme = 'bootstrap';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $query = User::query();

        if ($user->role === 'admin') {
            // El admin ve todos los usuarios y la información de su sucursal
            $query->with(['sucursales.router']);
        } else {
            // El aliado solo ve los usuarios de sus routers
            $aliadoRouterIds = Router::where('user_id', $user->id)->pluck('id');
            $userIdsInSucursales = UserSucursal::whereIn('router_id', $aliadoRouterIds)->pluck('user_id');
            $query->whereIn('id', $userIdsInSucursales);
        }

        $query->where(function($q) {
            $q->where('names', 'like', '%' . $this->search . '%')
              ->orWhere('surnames', 'like', '%' . $this->search . '%')
              ->orWhere('email', 'like', '%' . $this->search . '%');
        });

        $users = $query->latest()->paginate(10);

        // Routers para el select del modal
        $routers = collect();
        if ($user->role === 'admin') {
            $routers = Router::orderBy('identity')->get();
        } else {
            $routers = Router::where('user_id', $user->id)->orderBy('identity')->get();
        }

        return view('livewire.mikrotik.aliado.list-administradores', [
            'users' => $users,
            'routers' => $routers,
            'isAdmin' => $user->role === 'admin'
        ]);
    }

    public function create()
    {
        $this->reset(['names', 'surnames', 'email', 'role', 'password', 'user_id', 'router_id']);
        $this->active = true;
        $this->openModal();
    }

    public function edit(User $user)
    {
        $this->user_id = $user->id;
        $this->names = $user->names;
        $this->surnames = $user->surnames;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->active = $user->active;

        // Cargar el router asignado
        $sucursal = UserSucursal::where('user_id', $user->id)->first();
        $this->router_id = $sucursal ? $sucursal->router_id : null;

        $this->openModal();
    }

    public function store()
    {
        $rules = [
            'names' => 'required|string|max:100',
            'surnames' => 'required|string|max:100',
            'role' => 'required',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->user_id)],
            'router_id' => 'required|exists:routers,id',
        ];

        // Password obligatorio solo en creación
        if (!$this->user_id) {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules);

        $data = [
            'names' => $this->names,
            'surnames' => $this->surnames,
            'name' => $this->names . ' ' . $this->surnames, // Sincronizamos campo name
            'email' => $this->email,
            'role' => $this->role,
            'active' => $this->active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        $user = User::updateOrCreate(['id' => $this->user_id], $data);

        // Asociar usuario con sucursal/router
        UserSucursal::updateOrCreate(
            ['user_id' => $user->id],
            ['router_id' => $this->router_id]
        );

        session()->flash('message', $this->user_id ? 'Usuario actualizado.' : 'Usuario creado.');
        $this->closeModal();
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; }
}