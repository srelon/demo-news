<?php
namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait GlobalTrait
{
    public function createUrl($table, $column= 'url', $title= '') {
        $str= $url= Str::slug($title);

        while(DB::table($table)->where($column,$str)->first()){
            $id = Str::random(6);
            $str= "$id-$url";
        }
        return $str;
    }

    public function randomNumber($length= 6) {
        $a= '';
        for ($i= 0; $i< $length; $i++) {
            $a.= mt_rand(1,9);
        }
        return $a;
    }

    public function createPublicId($table, $column= 'public_id') {
        $id= $this->randomNumber();

        while(DB::table($table)->where($column,$id)->first()){
            $id= $this->randomNumber();
        }
        return $id;
    }
}
