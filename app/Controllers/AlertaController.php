<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Alerta;

class AlertaController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('alertas/index');
    }

    public function activas()
    {
        $this->requireAuth();
        $model = new Alerta();
        $alertas = $model->findActivas();
        Response::json(['data' => $alertas]);
    }

    public function atender($id)
    {
        $this->requireAuth();
        $this->requireMethod('POST');

        $model = new Alerta();
        $alerta = $model->findById($id);
        if (!$alerta) {
            Response::error('Alerta no encontrada', 404);
        }

        $model->update($id, [
            'estado' => 'atendida'
        ]);

        Response::success(null, 'Alerta marcada como atendida');
    }

    public function derivar($id)
    {
        $this->requireAuth();
        $this->requireMethod('POST');

        $derivadoA = trim(Request::post('derivado_a', ''));
        $observaciones = trim(Request::post('observaciones', ''));

        if (empty($derivadoA)) {
            Response::error('Debe especificar a quién se deriva la alerta', 422);
        }

        $model = new Alerta();
        $alerta = $model->findById($id);
        if (!$alerta) {
            Response::error('Alerta no encontrada', 404);
        }

        $updateData = [
            'derivado_a' => $derivadoA,
            'estado' => 'activa'
        ];
        if (!empty($observaciones)) {
            $updateData['observaciones'] = $observaciones;
        }

        $model->update($id, $updateData);
        Response::success(null, 'Alerta derivada exitosamente');
    }

    public function search($q)
    {
        $this->requireAuth();
        $model = new Alerta();
        $alertas = $model->search(['observaciones', 'derivado_a'], $q);
        Response::json(['data' => $alertas]);
    }
}
