<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Ticket extends Model
{
  use HasFactory;

  public function users(){
    return $this->belongsTo(User::class, 'id', 'user_id');
  }

  public function getColumnTable($condition){
    $rawField = new Ticket();
    $table    = $rawField->getTable();
    $fields   = collect(DB::getSchemaBuilder()->getColumnListing($table));
    if($condition == 'only-timestamp'){
      $fields   = $fields->filter(function ($value) {
        return in_array($value, ['id', 'created_by', 'updated_by', 'ticket_title', 'ticket_msg', 'user_id', 'ticket_status']) === false;
      });
    }

    return $fields;
  }
}