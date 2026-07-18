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
    public $user_id, $names, $surnames, $email, $role, $password, $active = true, $router_id, $aliado_id;
    public $showPassword = false;
    public $aliados = [];

    protected $paginationTheme = 'bootstrap';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $query = User::query();

        // Siempre cargamos la relación para mostrarla en la tabla
        $query->with(['sucursales.router']);

        if ($user->role !== 'admin') {
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
        $routersQuery = Router::query();
        if ($user->role === 'admin') {
            // Si un aliado está seleccionado en el modal, filtramos los routers
            if ($this->aliado_id) {
                $routersQuery->where('user_id', $this->aliado_id);
            } else {
                // Si no, no mostramos ningún router hasta que se seleccione un aliado
                $routersQuery->where('id', -1); // Condición que no devuelve nada
            }
            $this->aliados = User::whereIn('role', ['aliado', 'aliadoSmartData'])->orderBy('name')->get();
        } else {
            // El aliado solo ve sus propios routers
            $routersQuery->where('user_id', $user->id);
        }

        $routers = $routersQuery->orderBy('identity')->get();

        return view('livewire.mikrotik.aliado.list-administradores', [
            'users' => $users,
            'routers' => $routers,
            'isAdmin' => $user->role === 'admin',
        ]);
    }

    public function create()
    {
        $this->reset(['names', 'surnames', 'email', 'role', 'password', 'user_id', 'router_id']);
        $this->active = true;
        $this->aliado_id = null;
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
        if ($sucursal && $sucursal->router) {
            $this->router_id = $sucursal->router_id;
            // Si somos admin, pre-seleccionamos el aliado dueño del router
            if (Auth::user()->role === 'admin') {
                $this->aliado_id = $sucursal->router->user_id;
            }
        }

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

    public function togglePasswordVisibility()
    {
        $this->showPassword = !$this->showPassword;
    }

    public function updatedAliadoId()
    {
        // Cuando el admin cambia de aliado, reseteamos el router seleccionado.
        $this->router_id = null;
    }
}