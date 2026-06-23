<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use App\Models\Setting;
use App\Models\HotspotVersion;
use App\Models\Package;
use App\Models\PackageUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ListRouters extends Component
{
    public $isModalOpen = false;
    public $router_id, $user_id, $package_id, $identity, $ip, $api_port, $macAddress, $admin, $password, $location, $dns, $comercio_nombre, $hotspot_url;
    public $is_active;
    
    // Propiedades heredadas para mantener la integridad del registro
    public $status, $hotspot_version_id;
    
    public $routerStatus = [];

    public function render()
    {
        $user = Auth::user();
        
        $routers = Router::where("user_id", $user->id)
                    ->with(['package'])
                    ->latest()
                    ->get();

        // IMPORTANTE: Ajustado a 'active' según el estándar de tu modelo User
        $packages = $user->packages()
                        ->wherePivot('status', 'active')
                        ->wherePivot('end_date', '>=', now())
                        ->get();

        $this->refreshStatus();

        return view("livewire.mikrotik.aliado.list-routers", [
            "routers" => $routers,
            "packages" => $packages
        ]);
    }

    public function refreshStatus()
    {
        try {
            // Consultamos al Bridge
            $response = Http::timeout(3)->get('http://188.95.113.44:3000/api/routers-online');
            
            if ($response->successful()) {
                $onlineRouters = $response->json();
                
                // Extraemos las MACs y las ponemos en Mayúsculas y sin espacios
                $activeMacs = collect($onlineRouters)->map(function($item) {
                    return strtoupper(trim($item['mac']));
                })->toArray();

                // Obtenemos los routers actuales de la vista
                $routers = Router::where('user_id', Auth::id())->get();

                foreach ($routers as $r) {
                    $macLimpia = strtoupper(trim($r->macAddress));
                    $this->routerStatus[$r->id] = in_array($macLimpia, $activeMacs);
                }
            }
        } catch (\Exception $e) { 
            // Si falla la conexión al Bridge, reseteamos estados
            $this->routerStatus = []; 
        }
    }

    public function store()
    {
        $this->validate([
            'package_id' => 'required',
            'identity' => 'required',
            'macAddress' => 'required',
            'comercio_nombre' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $this->user_id = $user->id;

            $newPackagePivot = PackageUser::where('user_id', $this->user_id)
                                        ->where('package_id', $this->package_id)
                                        ->where('status', 'active')
                                        ->where('end_date', '>=', now())
                                        ->first();

            if (!$newPackagePivot) {
                session()->flash('error', 'El plan seleccionado no está activo o ha expirado.');
                DB::rollBack();
                return;
            }

            $oldRouter = $this->router_id ? Router::find($this->router_id) : null;
            $oldPackageId = $oldRouter ? $oldRouter->package_id : null;

            // Si es un router nuevo o se está cambiando de plan, verificar el límite
            if (!$oldRouter || ($oldPackageId != $this->package_id)) {
                if ($newPackagePivot->router_quantity >= $newPackagePivot->allowed_routers) {
                    session()->flash('error', 'Límite de routers para este plan alcanzado.');
                    DB::rollBack();
                    return;
                }
            }

            // Ajustar contadores si se cambia de plan
            if ($oldRouter && $oldPackageId && $oldPackageId != $this->package_id) {
                PackageUser::where('user_id', $this->user_id)
                            ->where('package_id', $oldPackageId)
                            ->where('status', 'active')
                            ->decrement('router_quantity');
                $newPackagePivot->increment('router_quantity');
            } elseif (!$oldRouter || ($oldRouter && !$oldPackageId)) {
                // Incrementar solo si es un router nuevo o no tenía plan asignado
                $newPackagePivot->increment('router_quantity');
            }

            Router::updateOrCreate(["id" => $this->router_id], [
                "user_id"            => $this->user_id,
                "package_id"         => $this->package_id,
                "identity"           => $this->identity,
                "macAddress"         => $this->macAddress,
                "location"           => $this->location,
                "comercio_nombre"    => $this->comercio_nombre,
                "hotspot_url"        => $this->hotspot_url,
                "status"             => $this->status ?? 'Habilitado',
                "is_active"          => ($this->status ?? 'Habilitado') === 'Habilitado',
                "hotspot_version_id" => $this->hotspot_version_id ?? 1, // Asignar una versión por defecto si no existe
            ]);

            DB::commit();
            session()->flash("message", "Operación realizada con éxito.");
            $this->closeModal();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al guardar el router: ' . $e->getMessage());
        }
    }

    public function edit(Router $router)
    {
        if ($router->user_id !== Auth::id()) {
            session()->flash('error', 'No tienes permiso para editar este router.');
            return;
        }

        $this->router_id = $router->id;
        $this->identity = $router->identity;
        $this->macAddress = $router->macAddress;
        $this->location = $router->location;
        $this->package_id = $router->package_id;
        $this->comercio_nombre = $router->comercio_nombre;
        $this->hotspot_url = $router->hotspot_url;
        $this->status = $router->status;
        $this->hotspot_version_id = $router->hotspot_version_id;
        $this->openModal();
    }

    public function destroy($id)
    {
        try {
            $router = Router::where('user_id', Auth::id())->findOrFail($id);

            // Liberar el cupo del paquete si el router tiene uno asignado
            if ($router->package_id) {
                PackageUser::where('user_id', $router->user_id)
                    ->where('package_id', $router->package_id)
                    ->where('status', 'active')
                    ->decrement('router_quantity');
            }

            $router->delete();
            session()->flash('message', 'Router eliminado correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar el router: ' . $e->getMessage());
        }
    }

    public function create() {
        $this->reset(['router_id', 'identity', 'package_id', 'macAddress', 'location', 'comercio_nombre', 'hotspot_url', 'status', 'hotspot_version_id']);
        $this->status = 'Habilitado';
        $this->openModal();
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; }
}
                return;
            }
        }

        Router::updateOrCreate(["id" => $this->router_id], [
            "user_id"            => $user->id,
            "package_id"         => $this->package_id,
            "identity"           => $this->identity,
            "macAddress"         => $this->macAddress,
            "location"           => $this->location,
            "comercio_nombre"    => $this->comercio_nombre,
            "status"             => $this->status ?? 'Habilitado',
            "hotspot_version_id" => $this->hotspot_version_id ?? 1,
        ]);

        session()->flash("message", "Operación realizada con éxito.");
        $this->closeModal();
    }

    public function edit(Router $router)
    {
        $this->router_id = $router->id;
        $this->identity = $router->identity;
        $this->macAddress = $router->macAddress;
        $this->location = $router->location;
        $this->package_id = $router->package_id;
        $this->comercio_nombre = $router->comercio_nombre;
        $this->hotspot_url = $router->hotspot_url;
        $this->status = $router->status;
        $this->hotspot_version_id = $router->hotspot_version_id;
        $this->openModal();
    }

    public function destroy($id)
    {
        try {
            $router = Router::where('user_id', Auth::id())->findOrFail($id);
            $router->delete();
            session()->flash('message', 'Router eliminado correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar el router: ' . $e->getMessage());
        }
    }

    public function create() {
        $this->reset(['router_id', 'identity', 'package_id', 'macAddress', 'location', 'comercio_nombre', 'hotspot_url']);
        $this->status = 'Habilitado';
        $this->openModal();
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; }
}