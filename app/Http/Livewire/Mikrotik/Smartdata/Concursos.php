<?php

namespace App\Http\Livewire\Mikrotik\Smartdata;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AdvertisingConcurso;
use App\Models\AgeRange;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;

class Concursos extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $query = AdvertisingConcurso::withCount('responses')
            ->where('user_id', Auth::id());

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $ageRanges = AgeRange::where('user_id', Auth::id())->get();

        $routers = Router::where('user_id', Auth::id())
            ->whereHas('user', function($q) {
                $q->whereIn('role', ['aliado', 'aliadoSmartData']);
            })
            ->get();

        return view('livewire.mikrotik.smartdata.concursos', [
            'concursos' => $query->latest()->paginate(10),
            'ageRanges' => $ageRanges,
            'routers' => $routers
        ])->layout('layouts.app');
    }
}
