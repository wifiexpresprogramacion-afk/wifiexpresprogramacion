<?php

namespace App\Http\Livewire\Admin\Profile;

use App\Http\Livewire\Admin\AdminComponent;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\DatosDeliveryUser;
use App\Models\Country;
use App\Models\Estado;
use App\Models\Cities;
use App\Models\DeliveryArea;

class BillingDetails extends AdminComponent
{

    public $stateF = [];

    public $user_id;
    public $countryF = 237;
    public $provinceF;
    public $cityF;
    public $zonaF;
    public $countries = [], $provinces = [], $cities = [], $zonas = [];

    public function mount($user_id)
    {
        $this->user_id = $user_id;

        $this->stateF['zipcode'] = '123';

        $this->countries = Country::all();

        $datosfacturacion = DatosDeliveryUser::where('user_id', $user_id)->first();

        if($datosfacturacion)
        {
            $this->stateF = $datosfacturacion->toArray();
        }else{
            return false;
        }

        $this->provinces = collect();
        $this->cities = collect();
        $this->zonas = collect();

        

        if($this->stateF['country_id'] == null || $this->stateF['country_id'] == 0 )
        {
            return false;
        }
        $this->countryF = $this->stateF['country_id'];

        $this->provinces = Estado::where('country_id', 237)->get();
        $this->provinceF = $this->stateF['state_id'];

        $this->cities = Cities::where('state_id', 24)->get();
        $this->cityF = $this->stateF['city_id'];

        $this->zonas = DeliveryArea::where('city_id', $this->cityF)->get();

        $this->zonaF = $this->stateF['deliveryarea_id'];
        
    }

    public function updateBillingDetails()
    {

        $validatedData = Validator::make($this->stateF, [
            'identificationNac' => 'required|not_in:0',
            'identificationNumber' => 'required',
            'names' => 'required',
            'surnames' => 'required',
            'cellphonecode' => 'required|not_in:0',
            'cellphone' => 'required',
			'address' => 'required',
            'zipcode' => 'required',
		])->validate();

        $validatedData['country_id'] = $this->countryF;
        $validatedData['state_id'] = $this->provinceF;
        $validatedData['city_id'] = $this->cityF;
        $validatedData['deliveryarea_id'] = $this->zonaF;

        $datosfacturacion = DatosDeliveryUser::where('user_id', $this->user_id)->first();

        if($datosfacturacion)
        {
            $datosfacturacion->update($validatedData);
        }else{
            $validatedData['user_id'] = $this->user_id;

            DatosDeliveryUser::create($validatedData);

        }

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Datos Básicos actualizados satisfactoriamente!']);
    }

    public function updatedCountryF($value)
	{
		$this->provinces = Estado::where('country_id', $value)->get();
		// $this->subcategory = $this->subcategories->first()->id ?? null;
	}

    public function updatedProvinceF($value)
	{
		$this->cities = Cities::where('state_id', $value)->get();
		// $this->subcategory = $this->subcategories->first()->id ?? null;
	}

    public function updatedCityF($value)
	{
		$this->zonas = DeliveryArea::where('city_id', $value)->get();
		// $this->subcategory = $this->subcategories->first()->id ?? null;
	}

    public function render()
    {
        return view('livewire.admin.profile.billing-details');
    }
}
