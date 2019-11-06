<?php

namespace App\Http\Controllers;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuilController extends Controller
{

    public function getEstado(Request $request)
    {
        $user = auth('api')->user();
        $estados = $user->getEstado($request);
        return response()->json(['data' => $estados]);
    }

}
?>   