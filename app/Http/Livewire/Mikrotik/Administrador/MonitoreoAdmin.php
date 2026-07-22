<?php

namespace App\Http\Livewire\Mikrotik\Administrador;

use Livewire\Component;
use App\Models\TicketLog;
use App\Models\Router;
use App\Models\UserSucursal;
use App\Models\UserMikrotik;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MonitoreoAdmin extends Component
{
    public function render()
    {
        $user = Auth::user();
        $allowedRouterIds = [];
        
        if ($user->role === 'administrador') {
            $sucursal = UserSucursal::where('user_id', $user->id)->first();
            if ($sucursal) {
                $allowedRouterIds = [$sucursal->router_id];
            }
        } else {
            $allowedRouterIds = Router::where('user_id', $user->id)->where('is_active', true)->pluck('id')->toArray();
        }

        $todayStart = Carbon::now()->startOfDay();
        $todayEnd = Carbon::now()->endOfDay();

        // 1. Estadísticas de hoy
        // Conectados Ahora: Registros en TicketLog creados HOY que no tienen fecha de desconexión.
        // Se filtra por hoy para no contar irregularidades de días anteriores.
        $connectedNow = TicketLog::whereIn('router_id', $allowedRouterIds)
            ->whereNull('disconnected_at')
            ->where('created_at', '>=', $todayStart)
            ->count();

        // Entradas Hoy: Registros de TicketLog creados hoy
        $entriesToday = TicketLog::whereIn('router_id', $allowedRouterIds)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        // Salidas Hoy: Registros de TicketLog que ya se desconectaron hoy
        $exitsToday = TicketLog::whereIn('router_id', $allowedRouterIds)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->whereNotNull('disconnected_at')
            ->count();

        // 2. Lista de Movimientos (últimos 20 eventos)
        $logs = TicketLog::with('router')
            ->whereIn('router_id', $allowedRouterIds)
            ->latest()
            ->take(20)
            ->get();

        // Mapear nombres de usuario para evitar N+1 consultas
        $macs = $logs->pluck('username')->map(fn($u) => Str::after($u, 'T-'))->unique();
        $mikrotikUsers = UserMikrotik::whereIn('name', $macs)
            ->whereIn('router_id', $allowedRouterIds)
            ->get()
            ->keyBy('name');

        $movements = $logs->map(function($log) use ($mikrotikUsers) {
            $mac = Str::after($log->username, 'T-');
            $u = $mikrotikUsers->get($mac);
            
            $clientName = $u ? ($u->full_name ?? $u->name) : $mac;
            
            $action = $log->disconnected_at 
                ? "se desconectó (salió del local)" 
                : "se acaba de conectar";

            return [
                'time' => $log->created_at->format('H:i'),
                'user' => $clientName,
                'action_type' => $log->disconnected_at ? 'disconnect' : 'connect',
                'action' => $action,
                'location' => $log->ubicacion_fisica
            ];
        });

        return view('livewire.mikrotik.administrador.monitoreo-admin', [
            'connectedNow' => $connectedNow,
            'entriesToday' => $entriesToday,
            'exitsToday' => $exitsToday,
            'movements' => $movements
        ]);
    }
}
