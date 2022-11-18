<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
  //cld: closed
  //opn: open
  //asn: assigned
  public function index(Request $request){
    //0 meaning return json all result of query ORM
    $status   = 200;
    $caused   = 0;
    $take     = 0;

    $fields   = Ticket::getColumnTable('only-timestamp');
    $records  = Ticket::select('ticket_title', 'ticket_status', 'created_at', 'user_id')
      ->where('ticket_status', '=', 'opn');
      
    if($request->filter){
      $filter = $request->filter;
      $type   = $this->conditionFilter($filter);
      if($type != ""){
        $validationField = $this->validationFilter($fields, $filter);
        if($validationField){
          if($type == 'b'){
            if(array_key_exists("start_date", $filter) && array_key_exists("end_date", $filter)){
              $records = $records->whereBetween($filter['filter_name'], [$filter['start_date'] . " 00:00:00", $filter['end_date'] . " 23:59:59"]);
            }else{
              $status = 400;
              $caused = 4;
            }
          }else{
            $records = $records->where($filter['filter_name'], $type, $filter['filter_value']);
          }
        }else{
          $status = 400;
          $caused = 1;
        }
      }else{
        $status = 400;
        $caused = 3;
      }
    }

    if($request->sort){
      $sort     = $request->sort;
      if(strtolower($sort['sort_dir']) == 'asc' || strtolower($sort['sort_dir']) == 'desc'){
        $records  = $records->orderBy($sort['sort_name'], $sort['sort_dir']);
      }else{
        $status = 400;
        $caused = 2;
      }
    }
    
    $resCount   = $records->count();

    if($request->page_size > 0){
      $sizes = [10, 20, 30, 40, 50];
      $afterMinuses = [];
      
      foreach($sizes as $size){
        $val = $size - $request->page_size;
        array_push($afterMinuses, $val);
      }
      
      foreach($afterMinuses as $key => $afterMinus){
        if($afterMinus >= -5){
          $keyOfMin = $key;
          break;
        }
        if($afterMinus >= 0){
          $keyOfMin = $key;
          break;
        }
      }
      $take = $sizes[$keyOfMin];
      $records  = $records->take($take);
    }

    if($status == 200){
      $message  = "Success get list tickets.";
      $counted  = $records->count();
      $data     = $records->get();
    }else{
      //1: filter type doesnt match.
      //2: order direction doesnt match
      //3: filter type doesnt match.
      //4: if type is between so backend need start_date and end_date in a request
      if($caused == 1){
        $message  = "Failed get list tickets because filter_name doesnt match with our config.";
        $counted  = 0;
        $data     = [];
      }
      if($caused == 2){
        $message  = "Failed get list tickets because order direction doesnt match. Try 'asc' or desc'.";
        $counted  = 0;
        $data     = [];
      }
      if($caused == 3){
        $message  = "Failed get list tickets because filter type doesnt match.";
        $counted  = 0;
        $data     = [];
      }
      if($caused == 4){
        $message  = "Failed get list tickets because if type is between so backend need start_date and end_date in a request.";
        $counted  = 0;
        $data     = [];
      }
    }
    $response = [
      "status"          => $status,
      "message"         => $message,
      "countedRecords"  => $counted,
      "data"            => $data
    ];

    return response()->json($response);
  }

  public function create(Request $request){
    $this->validate($request, [
      'TicketTitle' => 'required|string|min:10|max:100',
      'TicketMessage' => 'required|string|min:100',
      'UserId' => 'required|integer'
    ]);

    try {
      $record = new Ticket();
      $record->ticket_title   = $request->TicketTitle;
      $record->ticket_msg     = $request->TicketMessage;
      $record->user_id        = $request->UserId;
      $record->created_by     = $request->UserId;
      $record->updated_by     = $request->UserId;
      $record->ticket_status  = 'opn';
      if($record->save()){
        $responseStatus   = 'Success';
        $responseCode     = 200;
        $responseMessage  = 'Success saving record of ticket into table tickets..';
      }else{
        $responseStatus   = 'Failed';
        $responseCode     = 400;
        $responseMessage  = 'Failed saving record of ticket into table tickets..';
      }
    
      $response = [
        'ResponseStatus'  => $responseStatus,
        'ResponseCode'    => $responseCode,
        'ResponseMessage' => $responseMessage,
        'ResponseData'    => $record
      ];

      return response()->json($response);
    } catch (\Throwable $th) {
      $response = [
        'ResponseStatus'  => 'Failed',
        'ResponseCode'    => 400,
        'ResponseMessage' => $th,
        'ResponseData'    => []
      ];
      return response()->json($response);
    }

  }

  //encapsulation
  public function conditionFilter($filter){
    switch ($filter['filter_type']) {
      case 'before':
        $type = '<';
        break;
      
      case 'after':
        $type = '>';
        break;
      
      case 'between':
        $type = 'b';
        break;
      
      default:
        $type = '';
        break;
    }
    
    return $type;
  }

  public function validationFilter($fields, $filter){
    $result = false;
    try {
      foreach($fields as $field){
        if($filter['filter_name'] == $field){
          $result = true;
          break;
        }
      }
    } catch (\Throwable $th){
      $result = false;
    }
    return $result;
  }
}