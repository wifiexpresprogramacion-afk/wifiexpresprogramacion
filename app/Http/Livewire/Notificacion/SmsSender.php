<?php

namespace App\Http\Livewire\Notificacion;

use Livewire\Component;
use Twilio\Rest\Client;

class SmsSender extends Component
{
    public $to = '+5804165800403';
    public $message = 'Esto es una prueba';
    public $status;

    protected $rules = [
        'to' => 'required|string',
        'message' => 'required|string|min:5|max:160',
    ];

    public function sendSms()
    {
        $this->validate();

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        
        $client = new Client($sid, $token);

        try {
            $client->messages->create(
                $this->to,
                [
                    'from' => $from,
                    'body' => $this->message,
                ]
            );
            $this->status = 'success';
        } catch (Exception $e) {
            $this->status = 'error';
            // Opcional: registrar el error para depuración
            // \Log::error('Twilio SMS error: ' . $e->getMessage());
        }
    }

    static function callSendSms($nroto, $message)
    {
        $to = '+58'.$nroto;

        $message = $message;

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        
        $client = new Client($sid, $token);

        try {
            $client->messages->create(
                $to,
                [
                    'from' => $from,
                    'body' => $message,
                ]
            );
            $status = 'success';
            return $status;
        } catch (Exception $e) {
            $status = 'error';
            // Opcional: registrar el error para depuración
            // \Log::error('Twilio SMS error: ' . $e->getMessage());
            return $status;
        }
    }

    public function render()
    {
        return view('livewire.notificacion.sms-sender');
    }
}