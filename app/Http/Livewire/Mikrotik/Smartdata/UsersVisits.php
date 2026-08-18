<?php

namespace App\Http\Livewire\Mikrotik\Smartdata;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UserMikrotik;
use App\Models\TicketLog;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UsersVisits extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $selectedUserId = null;
    public $selectedRouterId = '';
    public $from = null;
    public $routers = [];

    public function mount($userId = null)
    {
        if ($userId) {
            $this->selectedUserId = $userId;
        }

        $user = Auth::user();
        // Cargar routers solo si el usuario es un aliado
        if (in_array($user->role, ['aliado', 'aliadoSmartData'])) {
            $this->routers = Router::where('user_id', $user->id)->get();
        }
        $this->from = request()->query('from');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function selectUser($id)
    {
        $this->selectedUserId = $id;
    }

    public function deselectUser()
    {
        if ($this->from === 'permanencia') {
            return redirect()->route('smartdata.permanencia');
        }
        $this->selectedUserId = null;
    }

    public function render()
    {
        $authUser = Auth::user();
        $allowedRouterIds = [];

        if (in_array($authUser->role, ['aliado', 'aliadoSmartData'])) {
            $allowedRouterIds = $this->routers->pluck('id')->toArray();
        }

        $selectedUser = $this->selectedUserId ? UserMikrotik::find($this->selectedUserId) : null;
        
        $usersQuery = UserMikrotik::query()
            ->whereIn('router_id', $allowedRouterIds)
            ->when($this->selectedRouterId, function ($query) {
                $query->where('router_id', $this->selectedRouterId);
            })
            ->where(function($query) {
                $term = '%' . $this->search . '%';
                $query->where('full_name', 'like', $term)
                      ->orWhere('name', 'like', $term)
                      ->orWhere('server', 'like', $term)
                      ->orWhere('email', 'like', $term)
                      ->orWhere(DB::raw("CONCAT(COALESCE(cellphonecode,''), COALESCE(cellphone,''))"), 'like', $term);
            });

        $users = $usersQuery->latest()->paginate(15);

        // Corregimos la consulta para que coincida con el formato 'T-MAC' de TicketLog
        $visits = $selectedUser 
            ? TicketLog::where('username', 'T-' . $selectedUser->name)->orderBy('created_at', 'desc')->get() 
            : collect();
            
        $totalVisits = $visits->count();

        return view('livewire.mikrotik.smartdata.users-visits', compact('users', 'selectedUser', 'visits', 'totalVisits', 'authUser'));
    }
}
