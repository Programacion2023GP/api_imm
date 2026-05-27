<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    protected $model;

    public function index(Request $request)
    {
        try {
            $data = $this->model::all();

            return ApiResponse::success($data, 'lista de datos ' . $this->model->getName());
        } catch (Exception $e) {
            Log::info($this->model->getName() . ' - ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
