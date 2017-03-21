<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use validator;
use App\Models\Category;
use App\Models\Amenite;
use App\Models\Accommodation;
use App\Models\Calendar;
use App\Models\Duration;
use App\Models\Type;
use DB;

class ControllerCombobox extends Controller
{
    public function GetCategory(){
         return Category::all();
    }

    public function GetAmenities(){
         return Amenite::all();
    }

    public function GetAccommodation(){
          return Accommodation::all();        
    }
    
    
    public function GetCalendar(){
          return Calendar::all();        
    }
    
    public function GetType(){
          return Type::all();        
    }

    public function GetDuration(){
          return Duration::all();        
    }

    public function GetCity(Request $request){
        $rule=[
           'city_id' => 'required|numeric|min:1'
      ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
        return response()->json($validator->errors()->all());
        }else{
              $getrent = DB::table('city')->join('state','city.state_id','=','state.id')
              ->join('country','country.id','=','state.country_id')
              ->where('city.id','=',$request->input("city_id"))
              ->select('country.name as country','state.name as state','city.name as city','country.iso','country.iso3','country.numcode','country.phonecode')
             ->get(); 
             if(count($getrent)>0){
               return response()->json($getrent);
             }else{
                return response()->json("city_id not found");
             }
        }
    }
    
    

}
    