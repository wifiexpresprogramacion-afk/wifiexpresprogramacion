<?php

namespace App\Http\Livewire\Dashboards;

use Livewire\Component;
use App\Models\Package;
use App\Models\Router;
use App\Models\UserSucursal;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AdministradorDashboard extends Component
{
    public $periodo = 'semana'; 
    public $fecha_desde, $fecha_hasta;
    public $router_id = ''; 

    public function mount()
    {
        $this->updatedPeriodo($this->periodo);

        $user = Auth::user();
        // Para el rol 'administrador', buscamos sus routers asignados
        if ($user->role === 'administrador') {
            $sucursales = UserSucursal::where('user_id', $user->id)->get();
            $routerIds = $sucursales->pluck('router_id');
            $misRouters = Router::whereIn('id', $routerIds)->get();

            // Si solo tiene un router, lo seleccionamos por defecto
            if ($misRouters->count() === 1) {
                $this->router_id = $misRouters->first()->id;
            }
        }
    }

    public function updatedPeriodo($value)
    {
        if ($value === 'dia') {
            $this->fecha_desde = now()->format('Y-m-d');
        } elseif ($value === 'semana') {
            $this->fecha_desde = now()->subDays(7)->format('Y-m-d');
        } elseif ($value === 'mes') {
            $this->fecha_desde = now()->subMonth()->format('Y-m-d');
        }
        if ($value !== 'personalizado')
        $this->fecha_hasta = now()->format('Y-m-d');
    }

    /**
     * Consulta al Bridge para obtener cuántos routers del aliado están realmente online
     */
    private function getRoutersOnlineCount($misRouters)
    {
        try {
            $response = Http::timeout(3)->get('http://188.95.113.44:3000/api/routers-online');
            
            if ($response->successful()) {
                $onlineRoutersData = $response->json();
                
                // Extraer MACs online limpias
                $activeMacs = collect($onlineRoutersData)->map(function($item) {
                    return strtoupper(trim($item['mac']));
                })->toArray();

                $count = 0;
                foreach ($misRouters as $r) {
                    $macLimpia = strtoupper(trim($r->macAddress));
                    if (in_array($macLimpia, $activeMacs)) {
                        $count++;
                    }
                }
                return $count;
            }
        } catch (\Exception $e) { 
            return 0; 
        }
        return 0;
    }

    public function render()
    {
        $user = Auth::user();
        $misRouters = collect(); // Inicializamos como colección vacía

        // Para el rol 'administrador', obtenemos sus routers desde UserSucursal
        if ($user->role === 'administrador') {
            $sucursales = UserSucursal::where('user_id', $user->id)->get();
            $routerIds = $sucursales->pluck('router_id');
            $misRouters = Router::whereIn('id', $routerIds)->get();
        } else {
            // Aquí iría la lógica para otros roles como 'aliadoSmartData' si es necesario
        }

        $desde = Carbon::parse($this->fecha_desde)->startOfDay();
        $hasta = Carbon::parse($this->fecha_hasta)->endOfDay();

        // 1. Eje X
        $labels = [];
        $temp = clone $desde;
        while ($temp <= $hasta) {
            $labels[] = $temp->format('Y-m-d');
            $temp->addDay();
        }

        // 2. Datos
        $targetRouterIds = $this->router_id ? [$this->router_id] : $misRouters->pluck('id')->toArray();
        $logsQuery = TicketLog::whereIn('ticket_logs.router_id', $targetRouterIds)
            ->whereBetween('ticket_logs.created_at', [$desde, $hasta]);
        
        $logs = (clone $logsQuery)
            ->select(DB::raw('DATE(ticket_logs.created_at) as fecha'), 'ticket_logs.router_id', DB::raw('count(*) as total'))
            ->groupBy('fecha', 'ticket_logs.router_id')
            ->get();

        // 3. Datasets para Gráfica
        $colores = ['#0d6efd', '#198754', '#ffc107', '#0dcaf0', '#6610f2', '#fd7e14', '#dc3545', '#20c997'];
        $datasets = [];

        foreach ($misRouters as $index => $router) {
            if (!empty($this->router_id) && $this->router_id != $router->id) continue;

            $dataValues = [];
            foreach ($labels as $label) {
                $val = $logs->where('fecha', $label)->where('router_id', $router->id)->first();
                $dataValues[] = $val ? $val->total : 0;
            }
            $datasets[] = [
                'label' => $router->identity,
                'data' => $dataValues,
                'backgroundColor' => $colores[$index % count($colores)],
                'borderRadius' => 5,
            ];
        }

        $this->dispatchBrowserEvent('updateMultiChart', ['labels' => $labels, 'datasets' => $datasets]);

        return view('livewire.dashboards.administrador-dashboard', [
            'userPackages' => collect(), // Se envía una colección vacía
            'routers' => $misRouters,
            'stats' => [
                'total_routers' => $misRouters->count(),
                'routers_online' => $this->getRoutersOnlineCount($misRouters), // NUEVO: Real de Bridge
                'usuarios_online' => TicketLog::whereIn('router_id', $targetRouterIds)->whereNull('disconnected_at')->where('created_at', '>=', now()->subDay())->count(),
            ],
            'topUsuarios' => (clone $logsQuery)
                ->leftJoin('user_mikrotiks', function($join) {
                    $join->on('user_mikrotiks.router_id', '=', 'ticket_logs.router_id')
                         ->on('user_mikrotiks.name', '=', DB::raw("REPLACE(ticket_logs.username, 'T-', '')"));
                })
                ->select(
                    'ticket_logs.username', 
                    DB::raw('MAX(ticket_logs.router_id) as router_id'), 
                    DB::raw('count(ticket_logs.id) as total_conexiones'), 
                    DB::raw('sum(ticket_logs.duration_seconds) as tiempo_total'),
                    'user_mikrotiks.full_name',
                    'user_mikrotiks.name as profile_name',
                    'user_mikrotiks.created_at as registered_at'
                )
                ->groupBy('ticket_logs.username', 'user_mikrotiks.full_name', 'user_mikrotiks.name', 'user_mikrotiks.created_at')
                ->orderBy('total_conexiones', 'desc')
                ->take(5)->get(),
            'ultimosLogs' => TicketLog::whereIn('ticket_logs.router_id', $targetRouterIds)
                ->leftJoin('user_mikrotiks', function($join) {
                    $join->on('user_mikrotiks.router_id', '=', 'ticket_logs.router_id')
                         ->on('user_mikrotiks.name', '=', DB::raw("REPLACE(ticket_logs.username, 'T-', '')"));
                })
                ->with('router')
                ->select(
                    'ticket_logs.*',
                    'user_mikrotiks.full_name',
                    'user_mikrotiks.name as profile_name'
                )
                ->latest('ticket_logs.created_at')
                ->take(10)->get(),
            'labels' => $labels,
            'datasets' => $datasets
        ])->layout('layouts.app');
    }
}