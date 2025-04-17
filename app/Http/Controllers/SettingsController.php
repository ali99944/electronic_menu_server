<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = app(SettingsService::class)->getAllSettings();
        return response()->json($settings);
    }
}
