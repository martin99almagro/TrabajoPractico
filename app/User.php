<?php

namespace App;
use Illuminate\Support\Facades\Schema;
use Cache;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function setPasswordAttribute($password)
    {
        if ( !empty($password) ) {
            $this->attributes['password'] = bcrypt($password);
        }
    }

    public function verificaRequest(){
        return DB::table('cuota_request')
        ->select(DB::raw('((cuota_request.max)-(cuota_request.actual)) as disponible'))
        ->where('cuota_request.user_id',$this->id)
        ->first();

    }

    public function setRequest($hits){
        DB::table('cuota_request')
        ->where('user_id', $this->id)
        ->update(['actual' => $hits]);
    }
   
    public function getEstado($request){
        $resultado= array();
        $datos=$request->input('datos');
       $hits=count($datos);
       if($hits>5000){
            return 'error';
        } else{
        $hits_disponibles=$this->verificaRequest();
        if($hits>$hits_disponibles->disponible){
            return 'error';
        }else{  
        foreach ($datos as $valor){
            $cuil=$valor['cuil'];
            $resultado[]=DB::table('personas')
            ->select('personas.cuil','personas.estado')
            ->where('personas.cuil',$cuil)
            ->first();
        }
        $this->setRequest($hits);
            return $resultado;
        }
    }
        
        
        
        
    }
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
