<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    public static function getVariantTitle($id){
        $title =  self::find($id);
        return empty($title['variant']) ? '' : $title['variant'];
    }
    public function variant(){
        return $this->belongsTo(Variant::class,'variant_id');
    }
}
